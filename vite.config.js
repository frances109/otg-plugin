// vite.config.js
// ─────────────────────────────────────────────────────────────
//  npm run dev     → hot-reload dev server at http://localhost:3000
//  npm run build   → minifies src/ → plugin/dist/  (via build.mjs)
//  npm run preview → preview the production build locally
//
// ─────────────────────────────────────────────────────────────

import { defineConfig } from 'vite';
import { resolve }      from 'path';

export default defineConfig({
  base: './',

  server: {
    port: 3000,
    open: '/src/preview/technical-guides.html',
    host: true,
  },

  resolve: {
    alias: {
      '~bootstrap-icons': resolve(__dirname, 'node_modules/bootstrap-icons'),
    },
  },
});