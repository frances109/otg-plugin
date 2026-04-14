/**
 * download-guides/main.js
 * Entry point for Page 2 — the guide download page.
 *
 * Wires together:
 *   shared/ui.js                      setYear
 *   download-guides/dots.js           spawnDots
 *   download-guides/guide-tracker.js  initGuideTracking, getLastDownloadedGuide
 *   download-guides/consultation.js   initConsultationBtn
 *
 * window.MagellanConfig is injected by page-download-guides.php:
 *   consultationUrl — /wp-json/otg/v1/consultation
 *   nonce           — wp_rest nonce
 *   contact         — { first_name, last_name, company_name, work_email, phone_number }
 *                     (PHP session data — no re-prompting needed)
 */

import { setYear }                                    from '../shared/ui.js';
import { spawnDots }                                  from './dots.js';
import { initGuideTracking, getLastDownloadedGuide }  from './guide-tracker.js';
import { initConsultationBtn }                        from './consultation.js';

// ── Personalised greeting ─────────────────────────────────────────────────────

/**
 * Render a personalised greeting if the user's first name is in sessionStorage.
 * Falls back to the default static heading if no name is found.
 */
function setGreeting() {
  const firstName = sessionStorage.getItem('mg_first_name');
  const el        = document.getElementById('mg-greeting');
  if (!el || !firstName) return;

  el.innerHTML =
    `Hi ${firstName}, your guides are <span style="color:var(--mg-cyan);font-style:unset">Ready.</span>`;
}

// ── Boot ──────────────────────────────────────────────────────────────────────

function boot() {
  setYear();
  setGreeting();
  spawnDots();
  initGuideTracking();

  // Pass getLastDownloadedGuide as a callback so consultation.js stays
  // decoupled from guide-tracker.js and can be tested independently.
  initConsultationBtn(getLastDownloadedGuide);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
