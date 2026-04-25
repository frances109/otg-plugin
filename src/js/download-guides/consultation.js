/**
 * download-guides/consultation.js
 * Handles the "Book a Consultation" button on the download-guides page.
 *
 * Reads contact details from window.MagellanConfig.contact (injected from
 * the PHP session by outsourcing-download-guides.php — no re-prompting required)
 * and the last-clicked guide name from guide-tracker.js.
 *
 * Export:
 *   initConsultationBtn(getGuideName)
 */

import { toast } from '../shared/ui.js';

// ── Loading state ─────────────────────────────────────────────────────────────

function setConsultLoading(on) {
  const btn     = document.getElementById('mg-consult-btn');
  if (!btn) return;

  const label   = btn.querySelector('.mg-btn-label');
  const spinner = btn.querySelector('.mg-spinner');

  btn.disabled = on;
  if (label)   label.style.display  = on ? 'none'         : '';
  if (spinner) spinner.style.display = on ? 'inline-block' : 'none';
  if (label)   label.style.opacity  = on ? '0.6'          : '1';
}

// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Attach the click handler to #mg-consult-btn.
 * Posts contact details + the last-downloaded guide name to the WP REST endpoint.
 *
 * @param {() => string} getGuideName  Callback that returns the last guide name.
 *                                     Passed in rather than imported directly so
 *                                     this module stays decoupled from guide-tracker.
 */
export function initConsultationBtn(getGuideName) {
  const btn = document.getElementById('mg-consult-btn');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    const MG      = window.MagellanConfig ?? {};
    const contact = MG.contact ?? {};
    const url     = MG.consultationUrl ?? '';

    // Dev preview — no REST endpoint available
    if (!url) {
      toast('Consultation request noted! (REST endpoint not available in dev preview)', 'success');
      return;
    }

    setConsultLoading(true);

    const payload = {
      first_name:   contact.first_name   ?? '',
      last_name:    contact.last_name    ?? '',
      company_name: contact.company_name ?? '',
      work_email:   contact.work_email   ?? '',
      phone_number: contact.phone_number ?? '',
      guide_name:   getGuideName(),
    };

    const headers = { 'Content-Type': 'application/json' };
    if (MG.nonce) headers['X-WP-Nonce'] = MG.nonce;

    try {
      const res  = await fetch(url, { method: 'POST', headers, body: JSON.stringify(payload) });
      const data = await res.json();

      if (data?.success) {
        toast("Your consultation request has been sent. We'll be in touch soon!", 'success');
        btn.disabled = true;   // prevent double-sending
      } else {
        throw new Error(data?.message ?? 'Request failed. Please try again.');
      }
    } catch (err) {
      toast(err.message || 'Something went wrong. Please try again.', 'error');
    } finally {
      setConsultLoading(false);
    }
  });
}
