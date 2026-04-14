#!/usr/bin/env node
/**
 * build.mjs — Outsourcing Technical Guides plugin build
 *
 * Why esbuild instead of Vite/Rollup?
 *   Both entry points must output as IIFEs (plain <script> tags in the PHP
 *   templates). Rollup forbids multiple IIFE inputs because it implicitly
 *   enables inlineDynamicImports — a hard constraint. esbuild handles it
 *   cleanly with no restrictions.
 *
 * What changed from the original:
 *   • bundle: true  — esbuild now resolves all ES module imports and inlines
 *     them, so the shared/ modules are bundled into each entry point.
 *     Previously bundle:false was used because both files were plain IIFEs
 *     with no imports to resolve.
 *   • Two entry points map to two output filenames that the PHP templates
 *     already reference — no PHP changes needed for the JS filenames.
 *   • Watch mode added (--watch flag).
 *   • CSS build is unchanged.
 *
 * Usage:
 *   npm run build        — production build (minified)
 *   npm run build:watch  — watches src/js for changes, rebuilds on save
 */

import { build, context }  from 'esbuild';
import CleanCSS            from 'clean-css';
import {
  existsSync, mkdirSync, rmSync,
  readFileSync, writeFileSync,
} from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath }    from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT_DIR   = resolve(__dirname, 'plugin/outsourcing-technical-guides/dist');
const WATCH     = process.argv.includes('--watch');

// ── Helpers ───────────────────────────────────────────────────────────────────

function ensureDir(dir) {
  if (!existsSync(dir)) mkdirSync(dir, { recursive: true });
}

function log(msg) {
  process.stdout.write(msg + '\n');
}

// ── 1. Clean output dir ───────────────────────────────────────────────────────

if (existsSync(OUT_DIR)) {
  rmSync(OUT_DIR, { recursive: true, force: true });
  log('✓ Cleaned ' + OUT_DIR);
}
ensureDir(resolve(OUT_DIR, 'js'));
ensureDir(resolve(OUT_DIR, 'css'));

// ── 2. JS bundles ─────────────────────────────────────────────────────────────
log('\nBuilding JS…');

// Shared esbuild options
const sharedOpts = {
  bundle:   true,      // resolve all ES module imports — shared/ modules inlined
  minify:   !WATCH,
  platform: 'browser',
  target:   ['es2017'],
  format:   'iife',    // plain <script> tag in PHP template — no type="module"
  logLevel: 'warning',
  // intl-tel-input is loaded as a global <script> tag by the PHP template.
  // Marking it external prevents esbuild bundling it a second time.
  external: ['intl-tel-input'],
};

const entries = [
  {
    in:  'src/js/technical-guides/main.js',
    out: 'js/technical-guides.js',          // name unchanged — PHP template already uses this
  },
  {
    in:  'src/js/download-guides/main.js',
    out: 'js/download-guides.js',           // name unchanged — PHP template already uses this
  },
];

if (WATCH) {
  for (const e of entries) {
    const ctx = await context({
      ...sharedOpts,
      entryPoints: [resolve(__dirname, e.in)],
      outfile:     resolve(OUT_DIR, e.out),
    });
    await ctx.watch();
    log(`  ✓ Watching ${e.in} → ${e.out}`);
  }
} else {
  for (const e of entries) {
    await build({
      ...sharedOpts,
      entryPoints: [resolve(__dirname, e.in)],
      outfile:     resolve(OUT_DIR, e.out),
    });
    log(`  ✓ ${e.out}`);
  }
}

// ── 3. CSS — unchanged from original ─────────────────────────────────────────
if (!WATCH) {
  log('\nBuilding CSS…');

  const cssEntries = [
    'src/css/base.css',
    'src/css/technical-guides.css',
    'src/css/download-guides.css',
  ];

  const cc = new CleanCSS({ level: 2, returnPromise: true });

  for (const entry of cssEntries) {
    const name   = entry.split('/').pop();
    const src    = readFileSync(resolve(__dirname, entry), 'utf8');
    const result = await cc.minify(src);

    if (result.errors.length) {
      console.error(`CSS errors in ${entry}:`, result.errors);
      process.exit(1);
    }

    writeFileSync(resolve(OUT_DIR, 'css', name), result.styles, 'utf8');
    log(`  ✓ css/${name}`);
  }

  log('\n✓ Build complete → ' + OUT_DIR + '\n');
}
