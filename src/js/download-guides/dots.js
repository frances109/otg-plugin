/**
 * download-guides/dots.js
 * Spawns 16 floating animated dots in the #mg-dots container.
 * Pure DOM side-effect — no exports needed beyond the one init function.
 *
 * Export:
 *   spawnDots()
 */

const DOT_COLORS  = ['#38d9f5', '#1133a8', '#c8a96e', 'rgba(255,255,255,.25)'];
const DOT_COUNT   = 16;
const SIZE_MIN    = 4;   // px
const SIZE_RANGE  = 8;   // random added on top of SIZE_MIN
const DUR_MIN     = 8;   // animation-duration seconds
const DUR_RANGE   = 14;
const DELAY_RANGE = 8;   // animation-delay seconds

/**
 * Create animated dot elements and append them to #mg-dots.
 * Each dot gets randomised size, colour, horizontal position, duration, and delay
 * so they rise at different rates from the bottom of the viewport.
 */
export function spawnDots() {
  const wrap = document.getElementById('mg-dots');
  if (!wrap) return;

  for (let i = 0; i < DOT_COUNT; i++) {
    const size  = Math.random() * SIZE_RANGE + SIZE_MIN;
    const color = DOT_COLORS[Math.floor(Math.random() * DOT_COLORS.length)];

    const dot       = document.createElement('div');
    dot.className   = 'mg-dot';
    dot.style.cssText = [
      `width:${size}px`,
      `height:${size}px`,
      `background:${color}`,
      `left:${Math.random() * 100}vw`,
      'bottom:-20px',
      `animation-duration:${(Math.random() * DUR_RANGE + DUR_MIN).toFixed(2)}s`,
      `animation-delay:${(Math.random() * DELAY_RANGE).toFixed(2)}s`,
    ].join(';');

    wrap.appendChild(dot);
  }
}
