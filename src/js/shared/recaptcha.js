/**
 * shared/recaptcha.js
 * reCAPTCHA v3 token helper.
 * Returns 'dev-bypass' when no site key is configured (local dev).
 *
 * Export:
 *   getToken(siteKey, action) → Promise<string>
 */

/**
 * Execute reCAPTCHA v3 and resolve with a token string.
 *
 * @param {string} siteKey  window.MagellanConfig.recaptchaSiteKey
 * @param {string} [action='submit']
 * @returns {Promise<string>}
 */
export function getToken(siteKey, action = 'submit') {
  return new Promise(resolve => {
    if (!siteKey) { resolve('dev-bypass'); return; }
    if (typeof grecaptcha === 'undefined') { resolve('not-loaded'); return; }

    grecaptcha.ready(() => {
      grecaptcha
        .execute(siteKey, { action })
        .then(resolve)
        .catch(() => resolve(''));
    });
  });
}
