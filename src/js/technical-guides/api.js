/**
 * technical-guides/api.js
 * Handles the lead capture form submission — POSTs to the WP REST endpoint
 * and returns the parsed response.
 *
 * Export:
 *   submitForm(payload, config) → Promise<object>
 */

import { getToken } from '../shared/recaptcha.js';

/**
 * Submit the lead capture form to the WordPress REST API.
 * Obtains a reCAPTCHA v3 token, attaches the WP nonce, and POSTs as JSON.
 *
 * @param {object} payload  Contact fields (first_name, last_name, etc.)
 * @param {{ ajaxUrl: string, nonce: string, recaptchaSiteKey: string }} config
 * @returns {Promise<object>}  Parsed JSON response from the server.
 */
export async function submitForm(payload, config) {
  const token = await getToken(config.recaptchaSiteKey, 'submit_guide_form');

  const headers = { 'Content-Type': 'application/json' };
  if (config.nonce) headers['X-WP-Nonce'] = config.nonce;

  const response = await fetch(config.ajaxUrl, {
    method:      'POST',
    credentials: 'same-origin',  // send PHPSESSID cookie so session persists to redirect page
    headers,
    body:    JSON.stringify({ ...payload, recaptcha_token: token }),
  });

  return response.json();
}