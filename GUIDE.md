# Outsourcing Technical Guides — Complete Setup & Maintenance Guide
### Vite (local dev) → WordPress Plugin (production)

---

## How It Works — The Full Picture

```
Your Machine (Dev)                         WordPress Server (Production)
──────────────────────────────────         ──────────────────────────────────────────
src/
  css/base.css              ──┐
  css/technical-guides.css  ──┤  npm run build
  css/download-guides.css   ──┤  ────────────►  plugin/dist/css/technical-guides.css
  js/technical-guides.js    ──┤                 plugin/dist/css/download-guides.css
  js/download-guides.js     ──┘                 plugin/dist/js/technical-guides.js
                                                plugin/dist/js/download-guides.js
                                     ↓
                               Upload plugin/ folder via FTP
                                     ↓
                               Plugin intercepts the two WP page slugs,
                               outputs standalone HTML (no Betheme CSS),
                               loads only dist/ + CDN assets
```

**You never touch JS or CSS inside WordPress.**
Edit locally → build → upload `dist/` → done.

---

## Project Structure

```
outsourcing-technical-guides/
│
├── package.json               ← npm config + build scripts
├── vite.config.js             ← Vite build: src/ → plugin/dist/
│
├── src/                       ← ✏️  EDIT THESE locally
│   ├── css/
│   │   ├── base.css               ← Tokens, nav, footer, animations (shared)
│   │   ├── technical-guides.css   ← Page 1 styles only
│   │   └── download-guides.css    ← Page 2 styles only
│   ├── js/
│   │   ├── technical-guides.js    ← Page 1 JS: form, validation, reCAPTCHA, fetch POST
│   │   └── download-guides.js     ← Page 2 JS: greeting, confetti dots
│   └── preview/
│       ├── technical-guides.html  ← Local dev preview (mirrors WP PHP output)
│       └── download-guides.html   ← Local dev preview
│
└── plugin/                    ← 📦 THIS FOLDER goes to WordPress
    └── outsourcing-technical-guides/
        ├── outsourcing-technical-guides.php   ← Plugin entry: routes pages, REST API, emails
        ├── dist/                              ← ⚙️  Built by `npm run build`
        │   ├── css/
        │   │   ├── technical-guides.css
        │   │   └── download-guides.css
        │   └── js/
        │       ├── technical-guides.js
        │       └── download-guides.js
        ├── assets/                            ← 🖼️  Upload YOUR images here
        │   ├── logo.webp                      ← Replace with real Magellan logo
        │   └── background.webp                ← Replace with real background image
        ├── pdf/                               ← 📄  Upload YOUR guide PDFs here
        │   ├── omnichannel-contact-center.pdf
        │   ├── back-office-process-support.pdf
        │   └── technical-support-helpdesk.pdf
        └── templates/                         ← PHP page templates (standalone HTML)
            ├── outsourcing-technical-guides.php      ← Page 1 full HTML
            └── outsourcing-download-guides.php       ← Page 2 full HTML
```

---

## Part 1 — Local Development

### Step 1 — Install Node.js

Download the **LTS** version from https://nodejs.org and install it.

Verify in your terminal:
```bash
node -v    # v18.x.x or higher
npm -v     # 9.x.x or higher
```

---

### Step 2 — Open the Project

Unzip `outsourcing-technical-guides.zip` and open the folder in VS Code.
Open a terminal inside it (Terminal → New Terminal in VS Code).

---

### Step 3 — Install Dependencies

```bash
npm install
```

Downloads Bootstrap, intl-tel-input, Vite, and all other packages into `node_modules/`.
Run this **once** after first unzip, and again whenever `package.json` changes.

---

### Step 4 — Start the Dev Server

```bash
npm run dev
```

Opens **http://localhost:3000/src/preview/technical-guides.html** automatically.
Vite watches your `src/` files and hot-reloads CSS and JS instantly on every save.

> The preview files in `src/preview/` mirror exactly what the PHP templates render in WordPress.
> What you see at localhost:3000 is what will appear in WordPress.

To also preview Page 2:
```
http://localhost:3000/src/preview/download-guides.html
```

> **Note:** Form submission won't work locally because there's no WordPress REST API running.
> Use the dev server for visual/CSS work only. Test form submission on WordPress staging.

---

### Step 5 — Edit Source Files

All editable source files live in `src/`:

| File | What to edit |
|---|---|
| `src/css/base.css` | CSS variables (colors, spacing), navbar, footer, animations |
| `src/css/technical-guides.css` | Page 1 layout, form card, inputs, hero |
| `src/css/download-guides.css` | Page 2 layout, guide cards, what's next panel |
| `src/js/technical-guides.js` | Form logic, validation, reCAPTCHA, POST to REST API |
| `src/js/download-guides.js` | Greeting personalisation, confetti animation |
| `plugin/…/templates/outsourcing-technical-guides.php` | Page 1 HTML structure |
| `plugin/…/templates/outsourcing-download-guides.php` | Page 2 HTML structure |

**Changing colors:** Edit the CSS variables at the top of `src/css/base.css`:
```css
:root {
  --mg-navy:      #040d2b;   ← main dark background
  --mg-cyan:      #38d9f5;   ← primary accent / highlight color
  --mg-white:     #f0f4ff;   ← text color
  --mg-gold:      #c8a96e;   ← badge / secondary accent
  /* etc. */
}
```

---

### Step 6 — Build for Production

```bash
npm run build
```

Vite compiles and minifies everything into `plugin/outsourcing-technical-guides/dist/`.

Expected output:
```
vite v5.x.x building for production...
✓ 4 modules transformed.
dist/css/technical-guides.css   XX kB
dist/css/download-guides.css    XX kB
dist/js/technical-guides.js     XX kB
dist/js/download-guides.js      XX kB
✓ built in X.XXs
```

> The `dist/` folder already contains a **pre-built version** so the plugin works immediately
> after upload without running `npm run build`. You only need to build after editing `src/`.

---

## Part 2 — First-Time WordPress Setup

### Step 7 — Install Required WordPress Plugins

Go to **WP Admin → Plugins → Add New** and install:

| Plugin | Purpose |
|---|---|
| **WP Mail SMTP** | Routes `wp_mail()` through a real SMTP provider (Gmail, SendGrid, Mailgun). Without this, WordPress emails go to spam or don't send at all. |
| **Flamingo** | Stores every form submission as a lead record under **WP Admin → Flamingo → Inbound Messages**. Think of it as a built-in CRM for your form leads. |

reCAPTCHA does **not** need a separate plugin — keys are entered directly in the plugin settings.

---

### Step 8 — Configure WP Mail SMTP

1. Go to **WP Mail SMTP → Settings**
2. Choose your mailer:
   - **Gmail (OAuth2)** — free, most reliable for Google Workspace
   - **SendGrid** — free tier covers up to 100 emails/day
   - **Mailgun** — good for higher volume
3. Follow the setup wizard for your chosen provider
4. After saving, go to **WP Mail SMTP → Tools → Email Test** and send a test to confirm delivery

---

### Step 9 — Upload the Plugin

You have two options:

**Option A — WP Admin Upload (easier)**

1. On your local machine, zip **only** the plugin folder:
   - Right-click `plugin/outsourcing-technical-guides/` → Compress/Zip
   - This creates `outsourcing-technical-guides.zip`
2. In WP Admin, go to **Plugins → Add New → Upload Plugin**
3. Choose the zip → **Install Now** → **Activate Plugin**

**Option B — FTP / File Manager (recommended for updates)**

1. Connect via FTP (FileZilla, Cyberduck) or use your host's File Manager
2. Navigate to `/wp-content/plugins/`
3. Upload the entire `outsourcing-technical-guides/` folder here
4. Go to **WP Admin → Plugins** → Activate **Outsourcing Technical Guides**

---

### Step 10 — Upload Your Assets

Navigate via FTP to:
```
/wp-content/plugins/outsourcing-technical-guides/assets/
```

Upload your files — **exact filenames required**:

| File | Description |
|---|---|
| `logo.webp` | Magellan Solutions logo (recommended: 300–400px wide, transparent background) |
| `background.webp` | Full-page background image (recommended: 1920×1080px minimum, `.webp` for performance) |

> **Why `.webp`?** It gives the best balance of quality and file size.
> If you have `.png` or `.jpg` files, convert them at https://squoosh.app before uploading.
> If you want to use a different format (e.g. `.jpg`), update the filename in both
> `templates/outsourcing-technical-guides.php` and `templates/outsourcing-download-guides.php`
> on the `$bg_url` / `$logo_url` lines.

---

### Step 11 — Upload Your PDF Guides

Navigate via FTP to:
```
/wp-content/plugins/outsourcing-technical-guides/pdf/
```

Upload your three PDF files — **exact filenames required**:

| File | Guide |
|---|---|
| `omnichannel-contact-center.pdf` | Omnichannel Contact Center Operations |
| `back-office-process-support.pdf` | Back-Office & Process Support |
| `technical-support-helpdesk.pdf` | Technical Support & Helpdesk |

---

### Step 12 — Configure Plugin Settings

Go to **WP Admin → Settings → Outsourcing Guides**

| Setting | What to enter |
|---|---|
| **reCAPTCHA v3 Site Key** | From https://www.google.com/recaptcha/admin/create → reCAPTCHA v3 → add your domain |
| **reCAPTCHA v3 Secret Key** | Same page as above |
| **Lead Notification Email(s)** | One or more emails separated by commas: `sales@company.com, manager@company.com` |
| **Form Page Slug** | Must exactly match your WordPress page slug (default: `outsourcing-technical-guides`) |
| **Download Page Slug** | Must exactly match your WordPress page slug (default: `outsourcing-download-guides`) |

Click **Save Changes**.

---

### Step 13 — Create the Two WordPress Pages

The plugin intercepts these pages at the PHP level and outputs its own HTML — you do **not** need to add any shortcodes or paste any code.

#### Page 1 — Lead Capture Form

1. Go to **Pages → Add New**
2. Title: `Outsourcing Technical Guides`
3. Verify the slug is: `outsourcing-technical-guides` (shown under the title field)
4. Leave the content editor **empty** — the plugin renders the full page
5. Page template: can be anything — the plugin overrides all template output
6. Click **Publish**

#### Page 2 — Download Guides

1. Go to **Pages → Add New**
2. Title: `Outsourcing Download Guides`
3. Verify the slug is: `outsourcing-download-guides`
4. Leave the content editor **empty**
5. Click **Publish**

---

### Step 14 — Test the Full Flow

1. Visit `https://your-site.com/outsourcing-technical-guides`
   - You should see the full-page design with background image, logo, and lead form
   - Phone field should show the flag selector (Philippines default)

2. Fill in all fields and click **Download the Guides**
   - The button shows a loading spinner
   - reCAPTCHA runs silently in the background
   - On success, you're redirected to the download page

3. On `https://your-site.com/outsourcing-download-guides`
   - Your first name appears in the greeting
   - Three **Download PDF** buttons link to your uploaded PDFs

4. Check **WP Admin → Flamingo → Inbound Messages** — your submission should appear

5. Check your notification email inbox — lead alert should have arrived

---

## Part 3 — Updating the Plugin (No Deletion Required)

> ⚠️ **Never delete and reinstall the plugin just to update files.**
> Deleting a plugin erases all its settings. Use the table below instead.

### What changed → What to upload → Does it need plugin reactivation?

| What you changed | Files to upload via FTP | Plugin reactivation needed? |
|---|---|---|
| CSS styles (`src/css/`) | Run `npm run build` first, then upload `plugin/dist/css/` | ❌ No — just upload and hard-refresh browser |
| JavaScript (`src/js/`) | Run `npm run build` first, then upload `plugin/dist/js/` | ❌ No — just upload and hard-refresh browser |
| Background or logo image | Upload new file to `plugin/assets/` (same filename) | ❌ No — takes effect immediately |
| PDF guides | Upload new file to `plugin/pdf/` (same filename) | ❌ No — takes effect immediately |
| PHP templates (HTML layout) | Upload changed `.php` file to `plugin/templates/` | ❌ No — PHP is interpreted fresh each request |
| Plugin settings (slug, email, keys) | Change in WP Admin → Settings → Outsourcing Guides | ❌ No — settings saved in database |
| Main plugin PHP file (`outsourcing-technical-guides.php`) | Upload to `plugin/` root | ✅ Yes — deactivate then reactivate |

### Step-by-step update workflow (CSS/JS changes)

```
1. Edit src/css/ or src/js/ locally
2. Save file
3. Run:  npm run build
4. Open your FTP client
5. Navigate to:  /wp-content/plugins/outsourcing-technical-guides/dist/
6. Upload and OVERWRITE:
   - dist/css/technical-guides.css   (if you changed Page 1 CSS)
   - dist/css/download-guides.css    (if you changed Page 2 CSS)
   - dist/js/technical-guides.js     (if you changed Page 1 JS)
   - dist/js/download-guides.js      (if you changed Page 2 JS)
7. Open the page in your browser
8. Hard refresh:  Ctrl+Shift+R  (Windows/Linux)  or  Cmd+Shift+R  (Mac)
```

### Step-by-step update workflow (images / PDFs)

```
1. Prepare your new file (same filename as existing)
2. Open FTP → navigate to the correct folder:
   - For images:  /wp-content/plugins/outsourcing-technical-guides/assets/
   - For PDFs:    /wp-content/plugins/outsourcing-technical-guides/pdf/
3. Upload → confirm overwrite when prompted
4. No browser refresh needed for PDFs
5. For images: hard refresh the page  Ctrl+Shift+R
```

### Reactivating the plugin (only when main .php file changes)

```
1. Upload the new outsourcing-technical-guides.php via FTP
2. WP Admin → Plugins
3. Click Deactivate next to "Outsourcing Technical Guides"
4. Click Activate
5. Go to Settings → Outsourcing Guides — your settings are still there
```

> **Why do settings survive?** Settings are stored in the WordPress database (`wp_options` table),
> not inside the plugin folder. Deactivating/reactivating only toggles the plugin's hooks —
> it does not delete any stored data.

---

## Part 4 — Design Customisation Reference

### Changing colors

Edit CSS variables in `src/css/base.css`:

```css
:root {
  --mg-navy:      #040d2b;   /* Main background color */
  --mg-navy-mid:  #071140;   /* Secondary background (form column) */
  --mg-blue-dark: #0a1a5c;   /* Gradient endpoint */
  --mg-cyan:      #38d9f5;   /* Primary accent: links, borders, icons, button */
  --mg-white:     #f0f4ff;   /* Main text color */
  --mg-muted:     rgba(240,244,255,.55); /* Secondary/subdued text */
  --mg-gold:      #c8a96e;   /* Badge color */
  --mg-error:     #f87171;   /* Form validation error color */
}
```

After editing, run `npm run build` and upload `dist/css/`.

### Changing the background image

1. Prepare `background.webp` (1920×1080px, compressed at https://squoosh.app)
2. Upload to `/wp-content/plugins/outsourcing-technical-guides/assets/background.webp`
3. Hard refresh the page — no build or reactivation needed

### Changing the logo

1. Prepare `logo.webp` (max 300px wide, transparent `.webp` recommended)
2. Upload to `/wp-content/plugins/outsourcing-technical-guides/assets/logo.webp`
3. Hard refresh — done

### Changing page text (headlines, descriptions)

Edit the PHP template files in `plugin/outsourcing-technical-guides/templates/`:

- `outsourcing-technical-guides.php` — Page 1 content (quote, stats, features, form labels)
- `outsourcing-download-guides.php` — Page 2 content (guide card text, what's next items)

Upload the changed `.php` file via FTP. No build step or reactivation needed.

### Changing notification emails

Go to **WP Admin → Settings → Outsourcing Guides → Lead Notification Email(s)**

Enter one or more emails separated by commas:
```
sales@company.com, manager@company.com, ceo@company.com
```

All listed addresses receive the lead alert simultaneously.

---

## Part 5 — Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Page shows white/blank | Plugin not activated | WP Admin → Plugins → Activate |
| Styles not loading (unstyled page) | `dist/` files missing or wrong path | Re-upload `dist/` folder; check Settings slugs match page slugs exactly |
| CSS variables not working (inspect shows `var(--mg-navy)` as unresolved) | `@import` blocked or `:root` not parsed | Ensure `dist/css/*.css` files start with `@import url(...)` as the very first line — no comments above it |
| Background image not showing | `assets/background.webp` not uploaded or wrong filename | Upload via FTP to `/wp-content/plugins/outsourcing-technical-guides/assets/` |
| Logo not showing | `assets/logo.webp` not uploaded | Same as above |
| Phone flag not showing | intl-tel-input flag image path broken | Already fixed in CSS: flags load from CDN. Hard-refresh the page. |
| Phone flag dropdown empty | `utils.js` failed to load | Check browser console for blocked CDN URLs; some hosts block `cdn.jsdelivr.net` |
| Form sends GET request / URL shows data | JS failed to load or `method` attribute missing | Check browser console for JS errors; confirm `dist/js/technical-guides.js` is uploaded |
| Form shows "Configuration error: REST endpoint not set" | `MagellanConfig.ajaxUrl` is empty | Plugin settings not saved; re-save Settings page |
| reCAPTCHA not working | Domain not registered / keys wrong | Re-check keys at https://www.google.com/recaptcha/admin and confirm domain matches |
| Emails not sending | `wp_mail()` not configured | Run WP Mail SMTP test email; check mailer credentials |
| Flamingo not recording | Flamingo plugin inactive | WP Admin → Plugins → Activate Flamingo |
| PDF download shows blank / 404 | PDF not uploaded | Upload PDF to `/wp-content/plugins/outsourcing-technical-guides/pdf/` — exact filename required |
| Redirect goes to wrong page | Download Page Slug mismatch | Settings → confirm slug exactly matches the WP page slug |
| Changes not visible after upload | Browser cached old files | Hard refresh: `Ctrl+Shift+R` (Win/Linux) or `Cmd+Shift+R` (Mac) |
| `npm run build` errors | `node_modules/` missing | Run `npm install` first |
| `npm run dev` doesn't open browser | Port 3000 already in use | Change `port: 3000` in `vite.config.js` to `3001` or another free port |

---

## CDN Dependencies Reference

All CDN resources are loaded in the PHP templates — no installation required.

| Library | Version | Purpose |
|---|---|---|
| Bootstrap CSS | 5.3.3 | Layout grid, utilities |
| Bootstrap Icons | 1.11.3 | Icon font (bi-headset, bi-lock, etc.) |
| intl-tel-input CSS | 21.1.4 | Phone flag styles |
| intl-tel-input JS | 21.1.4 | Phone input with country dial codes |
| intl-tel-input utils | 21.1.4 | Phone number validation |
| Bootstrap JS | 5.3.3 | Toast, modal utilities |
| Google Fonts (Poppins) | — | Typography |
| Google reCAPTCHA v3 | — | Bot prevention (only loaded if site key configured) |

---

## Part 6 — Git Workflow

### Repository structure

The project uses Git for version control. The initial commit captures the full baseline.
Use feature branches for every change so the `main` branch always reflects what is live in WordPress.

### Recommended commit message convention

```
type: short description

- detail 1
- detail 2
```

**Types:**
| Type | When to use |
|---|---|
| `feat` | New feature or page section |
| `fix` | Bug fix |
| `style` | CSS-only change (no logic change) |
| `refactor` | Code restructure with no behaviour change |
| `content` | Copy, text, or PDF update |
| `chore` | Dependency update, config change |
| `deploy` | Marks a commit that was uploaded to WordPress |

### Everyday workflow

```bash
# 1. Create a branch for your change
git checkout -b fix/intl-tel-input-flags

# 2. Make your edits in src/css/ or src/js/

# 3. Build
npm run build

# 4. Stage and commit
git add .
git commit -m "fix: force intl-tel-input flag images from CDN"

# 5. Merge back to main when done
git checkout main
git merge fix/intl-tel-input-flags

# 6. Upload dist/ to WordPress via FTP (see Part 3 above)

# 7. Tag the deploy
git tag -a v1.0.1 -m "fix: intl-tel-input flags"
```

### Tagging WordPress deployments

After every FTP upload, tag the commit so you always know exactly what version is live:

```bash
git tag -a v1.0.1 -m "deploy: fix intl-tel-input flags + dynamic year"
git log --oneline --tags
```

### Viewing history

```bash
git log --oneline          # compact history
git log --stat             # with file change counts
git diff HEAD~1 HEAD       # what changed in the last commit
git show v1.0.0            # full detail of a specific tag
```

### Rolling back a bad deploy

If you uploaded a broken version to WordPress and need to revert:

```bash
# Find the last good tag
git log --oneline --tags

# Check out the files from that tag (without losing current branch)
git checkout v1.0.0 -- plugin/outsourcing-technical-guides/dist/
git checkout v1.0.0 -- plugin/outsourcing-technical-guides/templates/

# Re-upload dist/ and templates/ to WordPress via FTP
# Then commit the rollback
git add .
git commit -m "revert: roll back to v1.0.0 (broken flags in v1.0.1)"
```

### Connecting to a remote (GitHub / GitLab / Bitbucket)

```bash
# Add your remote (replace URL with your actual repo)
git remote add origin https://github.com/your-org/outsourcing-technical-guides.git

# Push main branch and all tags
git push -u origin main
git push origin --tags

# Future pushes
git push
```
