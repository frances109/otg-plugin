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
        ', your guides are <span style="color:var(--mg-cyan);font-style:unset">Ready.</span>';
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

  /* ── GUIDE TRACKING ─────────────────────────────────────────
     Listens for clicks on any download button and stores the
     guide name so the consultation handler can include it.
  ─────────────────────────────────────────────────────────── */
  var lastDownloadedGuide = '';
 
  function initGuideTracking() {
    document.querySelectorAll('.mg-dl-btn[data-guide-name]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        lastDownloadedGuide = btn.getAttribute('data-guide-name') || '';
      });
    });
  }

  /* ── TOAST ──────────────────────────────────────────────── */
  function toast(msg, type) {
    var el = document.getElementById('mg-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'mg-toast';
      document.body.appendChild(el);
    }
    el.className = 'mg-toast ' + (type || 'success');
    var icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
    el.innerHTML =
      '<i class="bi bi-' + icon + '"></i>' +
      '<span>' + msg + '</span>';
    el.classList.add('show');
    setTimeout(function() { el.classList.remove('show'); }, 5000);
  }
 
  /* ── CONSULTATION BUTTON ────────────────────────────────────
     POSTs to /wp-json/otg/v1/consultation with:
       - contact details from window.MagellanConfig.contact
         (PHP session data injected into the page on load)
       - guide_name: the last PDF button the user clicked
         (empty string if they haven't clicked any yet)
  ─────────────────────────────────────────────────────────── */
  function initConsultationBtn() {
    var btn = document.getElementById('mg-consult-btn');
    if (!btn) return;
 
    btn.addEventListener('click', function() {
      var MG      = window.MagellanConfig || {};
      var contact = MG.contact || {};
      var url     = MG.consultationUrl || '';
 
      // Dev preview fallback — no REST endpoint available locally
      if (!url) {
        toast('Consultation request noted! (REST endpoint not available in dev preview)', 'success');
        return;
      }
 
      setConsultLoading(true);
 
      var payload = {
        first_name:   contact.first_name   || '',
        last_name:    contact.last_name    || '',
        company_name: contact.company_name || '',
        work_email:   contact.work_email   || '',
        phone_number: contact.phone_number || '',
        guide_name:   lastDownloadedGuide,
      };
 
      var headers = { 'Content-Type': 'application/json' };
      if (MG.nonce) headers['X-WP-Nonce'] = MG.nonce;
 
      fetch(url, {
        method:  'POST',
        headers: headers,
        body:    JSON.stringify(payload),
      })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data && data.success) {
            toast('Your consultation request has been sent. We\'ll be in touch soon!', 'success');
            btn.disabled = true; // prevent double-sending
          } else {
            throw new Error((data && data.message) ? data.message : 'Request failed. Please try again.');
          }
        })
        .catch(function(err) {
          toast(err.message || 'Something went wrong. Please try again.', 'error');
        })
        .finally(function() {
          setConsultLoading(false);
        });
    });
  }
 
  function setConsultLoading(on) {
    var btn     = document.getElementById('mg-consult-btn');
    var label   = btn  && btn.querySelector('.mg-btn-label');
    var spinner = btn  && btn.querySelector('.mg-spinner');
    if (!btn) return;
    btn.disabled = on;
    if (label)   label.style.display   = on ? 'none'         : '';
    if (spinner) spinner.style.display = on ? 'inline-block' : 'none';
    if (label)   label.style.opacity = on ? '0.6' : '1';
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
    initGuideTracking();
    initConsultationBtn();
  }

})();
