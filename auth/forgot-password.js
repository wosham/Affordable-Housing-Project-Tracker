(function () {
  'use strict';

  var RESEND_DELAY = 60; /* seconds */

  /* ---- Validation ---- */
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

  /* ---- Resend countdown timer ---- */
  function startResendTimer() {
    var resendBtn    = document.getElementById('forgotResendBtn');
    var timerSpan    = document.getElementById('forgotResendTimer');
    var countdownEl  = document.getElementById('forgotCountdown');
    if (!resendBtn || !timerSpan || !countdownEl) return;

    var remaining = RESEND_DELAY;
    resendBtn.disabled = true;
    timerSpan.hidden   = false;
    countdownEl.textContent = remaining;

    var tick = setInterval(function () {
      remaining -= 1;
      countdownEl.textContent = remaining;
      if (remaining <= 0) {
        clearInterval(tick);
        resendBtn.disabled   = false;
        timerSpan.hidden     = true;
      }
    }, 1000);

    resendBtn.addEventListener('click', function handleResend() {
      resendBtn.removeEventListener('click', handleResend);
      remaining = RESEND_DELAY;
      resendBtn.disabled = true;
      timerSpan.hidden   = false;
      countdownEl.textContent = remaining;
      var retick = setInterval(function () {
        remaining -= 1;
        countdownEl.textContent = remaining;
        if (remaining <= 0) {
          clearInterval(retick);
          resendBtn.disabled = false;
          timerSpan.hidden   = true;
        }
      }, 1000);
    }, { once: true });
  }

  /* ---- Show success state ---- */
  function showSuccess(email) {
    var formState    = document.getElementById('forgotFormState');
    var successState = document.getElementById('forgotSuccessState');
    var successEmail = document.getElementById('forgotSuccessEmail');
    if (formState)    formState.hidden    = true;
    if (successState) successState.hidden = false;
    if (successEmail) successEmail.textContent = email;
    startResendTimer();
  }

  /* ---- Form submit ---- */
  function initForm() {
    var form        = document.getElementById('forgotForm');
    var btn         = document.getElementById('forgotSubmitBtn');
    var emailIn     = document.getElementById('forgotEmail');
    var errorAlert  = document.getElementById('forgotErrorAlert');
    var errorMsg    = document.getElementById('forgotErrorMsg');
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
      if (errorAlert) errorAlert.hidden = true;

      /* Simulated async send — replace with real API call */
      setTimeout(function () {
        btn.classList.remove('is-loading');
        btn.disabled = false;

        /* ----- DEMO: always succeeds ----- */
        var success = true;
        if (success) {
          showSuccess(email);
        } else {
          if (errorAlert) errorAlert.hidden = false;
          if (errorMsg) errorMsg.textContent = 'Unable to send reset link. Please try again later.';
        }
      }, 1500);
    });
  }

  /* ---- Init ---- */
  function init() { initForm(); }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
