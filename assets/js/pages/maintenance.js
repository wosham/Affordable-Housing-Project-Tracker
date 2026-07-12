/* TRANS-NZOIA AHP - Maintenance Page Script */
(function () {
  var body = document.body;
  var endValue = body ? body.getAttribute('data-maintenance-end') : '';
  var endTime = endValue ? new Date(endValue) : null;
  var countdown = document.getElementById('maintCountdown');
  var title = document.getElementById('maintEtaTitle');
  var text = document.getElementById('maintEtaText');
  var retryBtn = document.getElementById('maintRetryBtn');
  var elH = document.getElementById('countH');
  var elM = document.getElementById('countM');
  var elS = document.getElementById('countS');

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  function retry() {
    window.location.reload();
  }

  function showOpenEndedMessage() {
    if (title) title.textContent = 'Service update in progress';
    if (text) text.textContent = 'Please check again shortly. No project data has been lost.';
    if (countdown) countdown.hidden = true;
  }

  function updateCountdown() {
    if (!endTime || Number.isNaN(endTime.getTime())) {
      showOpenEndedMessage();
      return;
    }

    var remaining = endTime.getTime() - Date.now();
    if (remaining <= 0) {
      if (title) title.textContent = 'Maintenance window is ending';
      if (text) text.textContent = 'The service should be available again shortly. Try refreshing the page.';
      if (countdown) countdown.hidden = true;
      return;
    }

    var totalSeconds = Math.floor(remaining / 1000);
    var hours = Math.floor(totalSeconds / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    var seconds = totalSeconds % 60;

    if (title) title.textContent = 'Estimated time remaining';
    if (text) text.textContent = 'The public tracker is expected back online after the maintenance window.';
    if (countdown) countdown.hidden = false;
    if (elH) elH.textContent = pad(hours);
    if (elM) elM.textContent = pad(minutes);
    if (elS) elS.textContent = pad(seconds);
  }

  if (retryBtn) retryBtn.addEventListener('click', retry);

  updateCountdown();
  window.setInterval(updateCountdown, 1000);
}());
