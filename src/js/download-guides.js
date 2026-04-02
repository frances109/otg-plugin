/**
 * download-guides.js  — Page 2
 * Plain IIFE script — NO ES module imports.
 */
(function () {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  function setYear() {
    var els = document.querySelectorAll('.mg-year');
    var y   = new Date().getFullYear();
    els.forEach(function(el) { el.textContent = y; });
  }

  function setGreeting() {
    var firstName = sessionStorage.getItem('mg_first_name');
    var el        = document.getElementById('mg-greeting');
    if (!el) return;
    if (firstName) {
      el.innerHTML =
        'Hi ' + firstName +
        ', your guides are <span style="color:var(--mg-cyan);font-style:italic">Ready.</span>';
    }
  }

  function spawnDots() {
    var wrap = document.getElementById('mg-dots');
    if (!wrap) return;
    var colors = ['#38d9f5','#1133a8','#c8a96e','rgba(255,255,255,.25)'];
    for (var i = 0; i < 16; i++) {
      var d = document.createElement('div');
      var s = Math.random() * 8 + 4;
      d.className = 'mg-dot';
      d.style.cssText = [
        'width:'              + s + 'px',
        'height:'             + s + 'px',
        'background:'         + colors[Math.floor(Math.random() * colors.length)],
        'left:'               + Math.random() * 100 + 'vw',
        'bottom:-20px',
        'animation-duration:' + (Math.random() * 14 + 8) + 's',
        'animation-delay:'    + (Math.random() * 8) + 's',
      ].join(';');
      wrap.appendChild(d);
    }
  }

  function guardAccess() {
    if ( ! sessionStorage.getItem('mg_first_name') ) {
      window.location.replace('technical-guides.html');
    }
  }

  function boot() {
    // guardAccess();
    setYear();
    setGreeting();
    spawnDots();
  }

})();
