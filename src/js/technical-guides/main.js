/**
 * technical-guides/main.js
 * Entry point for Page 1 — the lead capture form.
 *
 * Wires together:
 *   shared/ui.js            setYear, toast
 *   shared/phone.js         initPhoneInput
 *   shared/validation.js    validateField, clearErr, validateAll
 *   technical-guides/counters.js  initCounters
 *   technical-guides/api.js       submitForm
 *
 * window.MagellanConfig is injected by outsourcing-technical-guides.php:
 *   ajaxUrl          — /wp-json/otg/v1/submit
 *   nonce            — wp_rest nonce
 *   recaptchaSiteKey — reCAPTCHA v3 site key (empty = skip)
 *   downloadPage     — URL of the download-guides page (redirect target)
 *   itiUtilsUrl      — URL to intl-tel-input utils.js (local vendor copy)
 */

import { setYear, toast }                        from '../shared/ui.js';
import { initPhoneInput }                        from '../shared/phone.js';
import { validateField, clearErr, validateAll }  from '../shared/validation.js';
import { initCounters }                          from './counters.js';
import { submitForm }                            from './api.js';

/* global intlTelInput */

// ── Config ────────────────────────────────────────────────────────────────────

const MG = window.MagellanConfig ?? {};

// ── intl-tel-input instance (shared by validation + submit handler) ───────────

let iti = null;

// ── Submit handler ────────────────────────────────────────────────────────────

async function handleSubmit(e) {
  e.preventDefault();
  e.stopPropagation();

  const form = e.currentTarget;
  if (!validateAll(form, iti)) return;

  if (!MG.ajaxUrl) {
    toast('Configuration error: REST endpoint not set. Contact the site administrator.', 'error');
    return;
  }

  setLoading(true);

  const phone = iti
    ? iti.getNumber()
    : (document.getElementById('phone_number')?.value ?? '').trim();

  const payload = {
    first_name:   (document.getElementById('first_name')?.value   ?? '').trim(),
    last_name:    (document.getElementById('last_name')?.value    ?? '').trim(),
    company_name: (document.getElementById('company_name')?.value ?? '').trim(),
    work_email:   (document.getElementById('work_email')?.value   ?? '').trim(),
    phone_number: phone,
  };

  try {
    const data = await submitForm(payload, MG);

    if (data?.success) {
      sessionStorage.setItem('mg_first_name', payload.first_name);
      toast('Success! Redirecting you now\u2026', 'success');
      setTimeout(() => {
        window.location.href = data.redirect_url || MG.downloadPage;
      }, 1000);
    } else {
      throw new Error(data?.message ?? 'Submission failed. Please try again.');
    }
  } catch (err) {
    toast(err.message || 'Something went wrong. Please try again.', 'error');
    setLoading(false);
  }
}

// ── Loading state ─────────────────────────────────────────────────────────────

function setLoading(on) {
  const btn = document.getElementById('mg-submit-btn');
  if (!btn) return;
  btn.disabled = on;
  btn.classList.toggle('loading', on);
}

// ── Boot ──────────────────────────────────────────────────────────────────────

async function boot() {
  setYear();
  initCounters();

  // Initialise phone input — resolves asynchronously after intlTelInput loads
  iti = await initPhoneInput('phone_number', {
    utilsScript: MG.itiUtilsUrl
      || 'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.4/build/js/utils.js',
  });

  const form = document.getElementById('mg-guide-form');
  if (!form) return;

  form.addEventListener('submit', handleSubmit);

  // Per-field inline validation
  form.querySelectorAll('.mg-input').forEach(el => {
    el.addEventListener('blur',  () => validateField(el, iti));
    el.addEventListener('input', () => clearErr(el));
  });
}

// Run after the DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
