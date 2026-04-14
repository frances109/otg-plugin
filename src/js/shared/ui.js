/**
 * shared/ui.js
 * UI utilities shared by both technical-guides and download-guides pages.
 *
 * Exports:
 *   setYear()               — writes the current year into every .mg-year element
 *   toast(msg, type)        — shows a temporary notification banner
 */

/**
 * Write the current year into every element with class .mg-year.
 * Called once on boot — keeps copyright lines correct without hard-coding a year.
 */
export function setYear() {
  const year = new Date().getFullYear();
  document.querySelectorAll('.mg-year').forEach(el => {
    el.textContent = year;
  });
}

/**
 * Show a self-dismissing toast notification.
 * Creates the #mg-toast element on first call and reuses it thereafter.
 *
 * @param {string} msg   The message to display.
 * @param {'success'|'error'} [type='success']
 */
export function toast(msg, type = 'success') {
  let el = document.getElementById('mg-toast');
  if (!el) {
    el    = document.createElement('div');
    el.id = 'mg-toast';
    document.body.appendChild(el);
  }

  const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
  el.className  = `mg-toast ${type}`;
  el.innerHTML  = `<i class="bi bi-${icon}"></i><span>${msg}</span>`;
  el.classList.add('show');

  setTimeout(() => el.classList.remove('show'), 5000);
}
