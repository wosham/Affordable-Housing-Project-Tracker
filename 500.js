/* TRANS-NZOIA AHP — 500 Page Script */
document.addEventListener('DOMContentLoaded', function () {

  /* ── Populate technical details ── */
  var timestamp = new Date().toISOString();
  var url       = window.location.href;
  var browser   = (navigator.userAgent.match(/^[^(]+/) || ['Unknown'])[0].trim();

  var elTimestamp = document.getElementById('errTimestamp');
  var elUrl       = document.getElementById('errUrl');
  var elBrowser   = document.getElementById('errBrowser');
  if (elTimestamp) elTimestamp.textContent = timestamp;
  if (elUrl)       elUrl.textContent       = url;
  if (elBrowser)   elBrowser.textContent   = browser;

  /* ── Toggle technical details ── */
  var toggle   = document.getElementById('techToggle');
  var techBody = document.getElementById('techBody');
  if (toggle && techBody) {
    toggle.addEventListener('click', function () {
      var open = techBody.classList.toggle('is-open');
      toggle.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      techBody.setAttribute('aria-hidden', String(!open));
    });
  }

  /* ── Retry countdown (auto-reload in 30 s) ── */
  var retryBtn   = document.getElementById('retryBtn');
  var retryCount = document.getElementById('retryCount');
  var seconds    = 30;

  var tick = setInterval(function () {
    seconds--;
    if (retryCount) retryCount.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(tick);
      window.location.reload();
    }
  }, 1000);

  if (retryBtn) {
    retryBtn.addEventListener('click', function () {
      clearInterval(tick);
      window.location.reload();
    });
  }

  /* ── Copy error details ── */
  var copyBtn = document.getElementById('copyErrBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var details =
        'Error: 500 Internal Server Error\n' +
        'Timestamp: ' + timestamp + '\n' +
        'URL: ' + url + '\n' +
        'Browser: ' + browser;

      var write = navigator.clipboard && navigator.clipboard.writeText
        ? navigator.clipboard.writeText(details)
        : Promise.reject();

      write.then(function () {
        copyBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied!';
        setTimeout(function () {
          copyBtn.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i> Copy Details';
        }, 2200);
      }).catch(function () {
        var ta = document.createElement('textarea');
        ta.value = details;
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        copyBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied!';
        setTimeout(function () {
          copyBtn.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i> Copy Details';
        }, 2200);
      });
    });
  }
});
