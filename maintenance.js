/* TRANS-NZOIA AHP — Maintenance Page Script */
(function () {

  /* ──────────────────────────────────────────
     CONFIGURE: update these two dates as needed
     All times in EAT (Africa/Nairobi, UTC+3)
  ────────────────────────────────────────── */
  var MAINTENANCE_START = new Date('2026-05-31T22:00:00+03:00');
  var MAINTENANCE_END   = new Date('2026-06-01T06:00:00+03:00');
  /* ────────────────────────────────────────── */

  /* ── Progress bar ── */
  var now     = new Date();
  var total   = MAINTENANCE_END - MAINTENANCE_START;
  var elapsed = now - MAINTENANCE_START;
  var pct     = Math.max(0, Math.min(100, Math.round((elapsed / total) * 100)));

  var bar     = document.getElementById('maintProgress');
  var pctSpan = document.getElementById('maintPct');
  if (bar)     bar.style.width   = pct + '%';
  if (pctSpan) pctSpan.textContent = pct + '%';

  /* ── Last updated timestamp ── */
  var lu = document.getElementById('lastUpdated');
  if (lu) {
    try {
      lu.textContent = now.toLocaleTimeString('en-KE', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Africa/Nairobi'
      });
    } catch (e) {
      lu.textContent = now.toLocaleTimeString();
    }
  }

  /* ── Countdown timer ── */
  var elH = document.getElementById('countH');
  var elM = document.getElementById('countM');
  var elS = document.getElementById('countS');
  var statusEl = document.getElementById('maintStatus');

  function pad(n) { return String(n).padStart(2, '0'); }

  function updateCountdown() {
    var diff = MAINTENANCE_END - new Date();

    if (diff <= 0) {
      if (elH) elH.textContent = '00';
      if (elM) elM.textContent = '00';
      if (elS) elS.textContent = '00';
      if (statusEl) statusEl.textContent = 'Back online shortly — refreshing…';
      /* Automatically refresh after 5 s */
      setTimeout(function () { window.location.reload(); }, 5000);
      return;
    }

    var totalSecs = Math.floor(diff / 1000);
    var h = Math.floor(totalSecs / 3600);
    var m = Math.floor((totalSecs % 3600) / 60);
    var s = totalSecs % 60;

    if (elH) elH.textContent = pad(h);
    if (elM) elM.textContent = pad(m);
    if (elS) elS.textContent = pad(s);

    /* Update progress bar every tick */
    var newPct = Math.max(0, Math.min(100, Math.round(
      ((new Date() - MAINTENANCE_START) / total) * 100
    )));
    if (bar)     bar.style.width         = newPct + '%';
    if (pctSpan) pctSpan.textContent     = newPct + '%';
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);

}());
