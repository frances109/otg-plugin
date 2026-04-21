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

    const execute = () => {
      grecaptcha.ready(() => {
        grecaptcha
          .execute(siteKey, { action })
          .then(resolve)
          .catch(() => resolve(''));
      });
    };

    if (typeof grecaptcha !== 'undefined') {
      execute();
      return;
    }

    // Wait up to 10s for the reCAPTCHA script to load
    let elapsed = 0;
    const interval = setInterval(() => {
      elapsed += 200;
      if (typeof grecaptcha !== 'undefined') {
        clearInterval(interval);
        execute();
      } else if (elapsed >= 10000) {
        clearInterval(interval);
        resolve('');
      }
    }, 200);
  });
}