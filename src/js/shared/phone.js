/**
 * shared/phone.js
 * intl-tel-input initialisation helper.
 *
 * The intl-tel-input library is loaded as a global <script> tag by the PHP
 * template before this module runs (window.intlTelInput).
 * On the technical-guides page the library may not yet be available at
 * DOMContentLoaded time, so this module retries with a small delay.
 *
 * Export:
 *   initPhoneInput(fieldId, options) → Promise<object|null>
 *     Resolves with the iti instance once initialised, or null if the
 *     field element does not exist on this page.
 */

/* global intlTelInput */

const RETRY_DELAY_MS = 80;
const MAX_RETRIES    = 25;   // ~2 s before giving up

/**
 * Initialise intl-tel-input on a <input type="tel"> element.
 * Retries up to MAX_RETRIES times if the library is not yet on window.
 *
 * @param {string} fieldId  The id attribute of the phone input element.
 * @param {object} [opts]   intl-tel-input options (merged with defaults).
 * @returns {Promise<object|null>}  Resolves with the iti instance or null.
 */
export function initPhoneInput(fieldId, opts = {}) {
  return new Promise((resolve, reject) => {
    const el = document.getElementById(fieldId);
    if (!el) { resolve(null); return; }

    let attempts = 0;

    function tryInit() {
      if (typeof intlTelInput !== 'function') {
        if (++attempts >= MAX_RETRIES) {
          console.warn(`[phone] intlTelInput not available after ${MAX_RETRIES} retries.`);
          resolve(null);
          return;
        }
        setTimeout(tryInit, RETRY_DELAY_MS);
        return;
      }

      const iti = intlTelInput(el, {
        initialCountry:     'ph',
        separateDialCode:   true,
        preferredCountries: ['ph', 'us', 'gb', 'au', 'sg'],
        ...opts,
      });

      // Re-apply our input class so styling rules work after intl-tel-input
      // wraps the element inside its own .iti container.
      const inner = el.closest('.iti')?.querySelector('input') ?? el;
      inner.classList.add('mg-input');

      resolve(iti);
    }

    tryInit();
  });
}
