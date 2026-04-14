/**
 * download-guides/guide-tracker.js
 * Tracks which PDF guide the user most recently clicked.
 * The guide name is included in the consultation POST payload so the admin
 * knows which guide prompted the request.
 *
 * Exports:
 *   initGuideTracking()       — attaches click listeners to all download buttons
 *   getLastDownloadedGuide()  — returns the guide name string (empty before any click)
 */

let lastDownloadedGuide = '';

/**
 * Attach click listeners to every .mg-dl-btn[data-guide-name] button.
 * Stores the guide name for later use by the consultation handler.
 */
export function initGuideTracking() {
  document.querySelectorAll('.mg-dl-btn[data-guide-name]').forEach(btn => {
    btn.addEventListener('click', () => {
      lastDownloadedGuide = btn.getAttribute('data-guide-name') ?? '';
    });
  });
}

/**
 * Return the name of the guide most recently clicked by the user.
 * Returns an empty string if the user has not clicked any download button yet.
 *
 * @returns {string}
 */
export function getLastDownloadedGuide() {
  return lastDownloadedGuide;
}
