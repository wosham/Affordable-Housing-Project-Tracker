/* TRANS-NZOIA AHP — 404 Page Script */
document.addEventListener('DOMContentLoaded', function () {

  var brokenUrl = window.location.href;

  /* ── Populate report mailto link ── */
  var reportLink = document.getElementById('reportLink');
  if (reportLink) {
    var subject = encodeURIComponent('Broken Link Report — ' + brokenUrl);
    var body    = encodeURIComponent(
      'Hello,\n\nI followed a link that led to a 404 Page Not Found error.\n\n' +
      'Broken URL: ' + brokenUrl + '\n\n' +
      'Please investigate and update the link. Thank you.'
    );
    reportLink.href = 'mailto:housing@transnzoia.go.ke?subject=' + subject + '&body=' + body;
  }

  /* ── Copy broken URL to clipboard ── */
  var copyBtn = document.getElementById('copyUrlBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(brokenUrl).then(function () {
          copyBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied!';
          setTimeout(function () {
            copyBtn.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i> Copy URL';
          }, 2200);
        }).catch(function () {
          fallbackCopy(brokenUrl, copyBtn);
        });
      } else {
        fallbackCopy(brokenUrl, copyBtn);
      }
    });
  }

  function fallbackCopy(text, btn) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied!';
    setTimeout(function () {
      btn.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i> Copy URL';
    }, 2200);
  }

  /* ── Go Back button ── */
  var backBtn = document.getElementById('goBackBtn');
  if (backBtn) {
    backBtn.addEventListener('click', function () {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = 'index.html';
      }
    });
  }
});
