(function () {
  'use strict';

  var REDIRECT_DELAY = 20; /* seconds */
  var CIRCUMFERENCE  = 2 * Math.PI * 18; /* r=18 on the SVG circle */

  /* ---- Reason banner from URL param ---- */
  function initReasonBanner() {
    var params = new URLSearchParams(window.location.search);
    var reason = params.get('reason');
    var map = {
      'session_expired': 'reasonTimeout',
      'timeout':         'reasonTimeout',
      'insufficient_role': 'reasonRole',
      'forbidden':         'reasonRole',
      'not_logged_in':     'reasonNoAuth',
      'unauthenticated':   'reasonNoAuth'
    };
    var id = map[reason];
    if (id) {
      var el = document.getElementById(id);
      if (el) el.hidden = false;
    }
  }

  /* ---- Countdown + SVG ring ---- */
  function initCountdown() {
    var wrap        = document.getElementById('unauthCountdownWrap');
    var numEl       = document.getElementById('unauthCountdownNum');
    var secEl       = document.getElementById('unauthCountdownSec');
    var circle      = document.getElementById('unauthCountdownCircle');
    var stayBtn     = document.getElementById('unauthStayBtn');
    if (!wrap) return;

    var remaining = REDIRECT_DELAY;
    var cancelled = false;

    /* Set initial dash */
    if (circle) {
      circle.style.strokeDasharray  = CIRCUMFERENCE;
      circle.style.strokeDashoffset = 0;
    }

    function updateRing() {
      if (!circle) return;
      var progress = remaining / REDIRECT_DELAY;
      circle.style.strokeDashoffset = CIRCUMFERENCE * (1 - progress);
    }

    if (stayBtn) {
      stayBtn.addEventListener('click', function () {
        cancelled = true;
        wrap.classList.add('is-cancelled');
        stayBtn.textContent = 'Redirect cancelled';
        stayBtn.disabled = true;
      });
    }

    var tick = setInterval(function () {
      if (cancelled) { clearInterval(tick); return; }
      remaining -= 1;
      if (numEl) numEl.textContent = remaining;
      if (secEl) secEl.textContent = remaining;
      updateRing();
      if (remaining <= 0) {
        clearInterval(tick);
        window.location.href = '../index.html';
      }
    }, 1000);
  }

  /* ---- Init ---- */
  function init() {
    initReasonBanner();
    initCountdown();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
