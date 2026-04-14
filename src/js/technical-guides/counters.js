/**
 * technical-guides/counters.js
 * Animated number counters for the hero stats section.
 *
 * Export:
 *   initCounters()  — starts all counter animations on the page
 */

/**
 * Animate a single counter element from 0 to `target` over `duration` ms.
 * Uses requestAnimationFrame for smooth, composited animation.
 *
 * @param {HTMLElement} el
 * @param {number}      target    Final value to count up to.
 * @param {number}      duration  Animation duration in milliseconds.
 * @param {string}      [suffix]  Text appended after the number (e.g. '+').
 */
function animateCounter(el, target, duration, suffix = '') {
  let startTime = null;

  function tick(timestamp) {
    if (!startTime) startTime = timestamp;

    const elapsed  = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    el.textContent = Math.floor(progress * target) + suffix;

    if (progress < 1) {
      requestAnimationFrame(tick);
    } else {
      el.textContent = target + suffix;  // clamp to exact final value
    }
  }

  requestAnimationFrame(tick);
}

/**
 * Initialise all stat counters on the page.
 *
 * Counters with a [data-target] attribute count up to that value.
 * A '+' suffix is appended for values 80 and 116 (matching the original behaviour).
 *
 * The "Years in BPO" counter (#years-counter) is computed dynamically
 * from the founding year (2005) so it stays correct every year without edits.
 */
export function initCounters() {
  // Fixed-target counters (e.g. "116+ SMEs Served")
  document.querySelectorAll('.mg-stat-num[data-target]').forEach(el => {
    const target = parseInt(el.getAttribute('data-target'), 10);
    const suffix = (target === 116 || target === 80) ? '+' : '';
    animateCounter(el, target, 2000, suffix);
  });

  // Dynamic years-in-BPO counter
  const yearsEl = document.getElementById('years-counter');
  if (yearsEl) {
    const years = new Date().getFullYear() - 2005;
    animateCounter(yearsEl, years, 2000, '+');
  }
}
