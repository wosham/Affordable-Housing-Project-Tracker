(function () {
  'use strict';

  var COUNTDOWN_SEC  = 20;
  var CIRCUMFERENCE  = 2 * Math.PI * 18; /* r=18 → 113.1 */

  /* ---- Show reason banner ---- */
  function initReasonBanner() {
    var params = new URLSearchParams(window.location.search);
    var reason = params.get('reason');
    var map = {
      'timeout':         'reasonTimeout',
      'session_expired': 'reasonTimeout',
      'role':            'reasonRole',
      'insufficient_role': 'reasonRole',
      'noauth':          'reasonNoAuth',
      'not_logged_in':   'reasonNoAuth'
    };
    var id = reason ? map[reason] : null;
    if (id) {
      var el = document.getElementById(id);
      if (el) el.hidden = false;
    }
  }

  /* ---- Countdown + SVG ring ---- */
  function initCountdown() {
    var numEl    = document.getElementById('unauthCountdownNum');
    var secEl    = document.getElementById('unauthCountdownSec');
    var circle   = document.getElementById('unauthCountdownCircle');
    var stayBtn  = document.getElementById('unauthStayBtn');
    var wrapEl   = document.getElementById('unauthCountdownWrap');
    if (!numEl || !circle) return;

    var remaining  = COUNTDOWN_SEC;
    var cancelled  = false;
    var interval;

    function tick() {
      remaining--;
      if (numEl) numEl.textContent = remaining;
      if (secEl) secEl.textContent = remaining;
      var progress = (COUNTDOWN_SEC - remaining) / COUNTDOWN_SEC;
      circle.style.strokeDashoffset = String(CIRCUMFERENCE * (1 - progress));
      if (remaining <= 0 && !cancelled) {
        clearInterval(interval);
        window.location.href = '../../index.php';
      }
    }

    circle.style.strokeDasharray  = String(CIRCUMFERENCE);
    circle.style.strokeDashoffset = '0';

    interval = setInterval(tick, 1000);

    if (stayBtn) {
      stayBtn.addEventListener('click', function () {
        cancelled = true;
        clearInterval(interval);
        if (wrapEl) wrapEl.hidden = true;
      });
    }
  }

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
