/* TRANS-NZOIA AHP - 500 Page Script */
document.addEventListener('DOMContentLoaded', function () {
  var retryBtn = document.getElementById('retryBtn');

  if (retryBtn) {
    retryBtn.addEventListener('click', function () {
      window.location.reload();
    });
  }
});
