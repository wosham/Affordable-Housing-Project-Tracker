(function () {
  'use strict';

  var RESEND_DELAY = 60;

  function validateEmail(val) {
    if (!val.trim()) return 'Email address is required.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return 'Please enter a valid email address.';
    return '';
  }
  function setFieldState(inputId, errId, msg) {
    var input = document.getElementById(inputId);
    var err   = document.getElementById(errId);
    if (!input || !err) return;
    err.textContent = msg;
    input.classList.toggle('is-error', !!msg);
    input.classList.toggle('is-valid', !msg && input.value.length > 0);
  }

  function startResendTimer() {
    var resendBtn   = document.getElementById('forgotResendBtn');
    var timerSpan   = document.getElementById('forgotResendTimer');
    var countdownEl = document.getElementById('forgotCountdown');
    if (!resendBtn || !timerSpan || !countdownEl) return;
    var remaining = RESEND_DELAY;
    resendBtn.disabled = true;
    timerSpan.hidden = false;
    var interval = setInterval(function () {
      remaining--;
      countdownEl.textContent = remaining;
      if (remaining <= 0) {
        clearInterval(interval);
        resendBtn.disabled = false;
        timerSpan.hidden = true;
      }
    }, 1000);
  }

  function showSuccess(email) {
    var formState    = document.getElementById('forgotFormState');
    var successState = document.getElementById('forgotSuccessState');
    var successEmail = document.getElementById('forgotSuccessEmail');
    if (formState)    formState.hidden    = true;
    if (successState) successState.hidden = false;
    if (successEmail) successEmail.textContent = email;
    startResendTimer();
  }

  function initForm() {
    var form       = document.getElementById('forgotForm');
    var btn        = document.getElementById('forgotSubmitBtn');
    var errAlert   = document.getElementById('forgotErrorAlert');
    var errMsg     = document.getElementById('forgotErrorMsg');
    var emailIn    = document.getElementById('forgotEmail');
    if (!form) return;

    if (emailIn) emailIn.addEventListener('blur', function () {
      setFieldState('forgotEmail', 'forgotEmailErr', validateEmail(this.value));
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = emailIn ? emailIn.value : '';
      var eErr  = validateEmail(email);
      setFieldState('forgotEmail', 'forgotEmailErr', eErr);
      if (eErr) { emailIn && emailIn.focus(); return; }

      btn.classList.add('is-loading');
      btn.disabled = true;
      if (errAlert) errAlert.hidden = true;

      setTimeout(function () {
        btn.classList.remove('is-loading');
        btn.disabled = false;
        showSuccess(email);
      }, 1200);
    });

    var resendBtn = document.getElementById('forgotResendBtn');
    if (resendBtn) {
      resendBtn.addEventListener('click', function () {
        resendBtn.disabled = true;
        setTimeout(function () { startResendTimer(); }, 800);
      });
    }
  }

  function init() { initForm(); }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
