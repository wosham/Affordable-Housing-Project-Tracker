(function () {
  'use strict';

  var REDIRECT_DELAY = 5; /* seconds before auto-redirect to login */

  /* ---- Password requirements ---- */
  var REQS = {
    length:  function (v) { return v.length >= 8; },
    upper:   function (v) { return /[A-Z]/.test(v); },
    lower:   function (v) { return /[a-z]/.test(v); },
    number:  function (v) { return /[0-9]/.test(v); },
    special: function (v) { return /[^A-Za-z0-9]/.test(v); }
  };

  var STRENGTH_LABELS = ['', 'Weak', 'Fair', 'Good', 'Strong'];

  /* ---- Evaluate strength score (1-4) ---- */
  function getScore(val) {
    if (!val) return 0;
    var passed = Object.keys(REQS).filter(function (k) { return REQS[k](val); }).length;
    if (passed <= 1) return 1;
    if (passed === 2) return 2;
    if (passed === 3 || passed === 4) return 3;
    return 4;
  }

  /* ---- Update strength meter ---- */
  function updateStrength(val) {
    var fill  = document.getElementById('resetStrengthFill');
    var label = document.getElementById('resetStrengthLabel');
    var score = val.length ? getScore(val) : 0;
    if (fill)  { fill.setAttribute('data-score', score || ''); }
    if (label) {
      label.textContent = score ? STRENGTH_LABELS[score] : '';
      label.setAttribute('data-score', score || '');
    }
  }

  /* ---- Update requirements checklist ---- */
  function updateReqs(val) {
    Object.keys(REQS).forEach(function (key) {
      var el = document.getElementById('req' + key.charAt(0).toUpperCase() + key.slice(1));
      if (!el) return;
      var met = val.length > 0 && REQS[key](val);
      el.classList.toggle('is-met', met);
      var icon = el.querySelector('.rp-req-icon');
      if (icon) icon.className = met ? 'fa-solid fa-circle-check rp-req-icon' : 'fa-solid fa-circle rp-req-icon';
    });
  }

  /* ---- Show/hide password toggle ---- */
  function initToggle(toggleId, inputId) {
    var toggle = document.getElementById(toggleId);
    var input  = document.getElementById(inputId);
    if (!toggle || !input) return;
    toggle.addEventListener('click', function () {
      var isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', String(!isText));
      toggle.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
      var icon = toggle.querySelector('i');
      if (icon) icon.className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });
  }

  /* ---- Field state ---- */
  function setFieldState(inputId, errId, msg) {
    var input = document.getElementById(inputId);
    var err   = document.getElementById(errId);
    if (!input || !err) return;
    err.textContent = msg;
    input.classList.toggle('is-error', !!msg);
    input.classList.toggle('is-valid', !msg && input.value.length > 0);
  }

  /* ---- Validate new password ---- */
  function validateNewPassword(val) {
    if (!val) return 'New password is required.';
    if (val.length < 8) return 'Password must be at least 8 characters.';
    if (!REQS.upper(val))   return 'Must include at least one uppercase letter.';
    if (!REQS.lower(val))   return 'Must include at least one lowercase letter.';
    if (!REQS.number(val))  return 'Must include at least one number.';
    if (!REQS.special(val)) return 'Must include at least one special character.';
    return '';
  }

  /* ---- Token validation ---- */
  function checkToken() {
    var params     = new URLSearchParams(window.location.search);
    var token      = params.get('token');
    var formState  = document.getElementById('resetFormState');
    var invalidSt  = document.getElementById('resetInvalidState');

    /* Demo: treat any non-empty token as valid */
    if (token && token.length > 4) {
      if (formState)  formState.hidden  = false;
      if (invalidSt)  invalidSt.hidden  = true;
    } else {
      if (formState)  formState.hidden  = true;
      if (invalidSt)  invalidSt.hidden  = false;
    }
  }

  /* ---- Success redirect countdown ---- */
  function startRedirectCountdown() {
    var countdownEl = document.getElementById('resetCountdown');
    var stayBtn     = document.getElementById('resetStayBtn');
    var remaining   = REDIRECT_DELAY;
    var cancelled   = false;

    if (stayBtn) {
      stayBtn.addEventListener('click', function () {
        cancelled = true;
        stayBtn.textContent = 'Redirect cancelled';
        stayBtn.disabled    = true;
      });
    }

    var tick = setInterval(function () {
      if (cancelled) { clearInterval(tick); return; }
      remaining -= 1;
      if (countdownEl) countdownEl.textContent = remaining;
      if (remaining <= 0) {
        clearInterval(tick);
        window.location.href = 'login.php?reason=password_reset';
      }
    }, 1000);
  }

  /* ---- Show success state ---- */
  function showSuccess() {
    var formState    = document.getElementById('resetFormState');
    var successState = document.getElementById('resetSuccessState');
    if (formState)    formState.hidden    = true;
    if (successState) successState.hidden = false;
    startRedirectCountdown();
  }

  /* ---- Form ---- */
  function initForm() {
    var form    = document.getElementById('resetForm');
    var btn     = document.getElementById('resetSubmitBtn');
    var newIn   = document.getElementById('resetNewPassword');
    var confIn  = document.getElementById('resetConfirmPassword');
    var errAlert = document.getElementById('resetErrorAlert');
    var errMsg   = document.getElementById('resetErrorMsg');
    if (!form) return;

    /* Live strength + reqs update */
    if (newIn) {
      newIn.addEventListener('input', function () {
        updateStrength(this.value);
        updateReqs(this.value);
      });
      newIn.addEventListener('blur', function () {
        setFieldState('resetNewPassword', 'resetNewPasswordErr', validateNewPassword(this.value));
      });
    }

    /* Confirm password blur */
    if (confIn) {
      confIn.addEventListener('blur', function () {
        var msg = '';
        if (!this.value) msg = 'Please confirm your password.';
        else if (newIn && this.value !== newIn.value) msg = 'Passwords do not match.';
        setFieldState('resetConfirmPassword', 'resetConfirmPasswordErr', msg);
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var newVal  = newIn  ? newIn.value  : '';
      var confVal = confIn ? confIn.value : '';
      var nErr = validateNewPassword(newVal);
      var cErr = '';
      if (!confVal)           cErr = 'Please confirm your password.';
      else if (confVal !== newVal) cErr = 'Passwords do not match.';

      setFieldState('resetNewPassword',    'resetNewPasswordErr',    nErr);
      setFieldState('resetConfirmPassword','resetConfirmPasswordErr', cErr);

      if (nErr || cErr) {
        document.getElementById(nErr ? 'resetNewPassword' : 'resetConfirmPassword').focus();
        return;
      }

      btn.classList.add('is-loading');
      btn.disabled = true;
      if (errAlert) errAlert.hidden = true;

      /* Simulated async — replace with real API call */
      setTimeout(function () {
        btn.classList.remove('is-loading');
        btn.disabled = false;
        /* ----- DEMO: always succeeds ----- */
        var success = true;
        if (success) {
          showSuccess();
        } else {
          if (errAlert) errAlert.hidden = false;
          if (errMsg) errMsg.textContent = 'Unable to update your password. Please try again.';
        }
      }, 1500);
    });
  }

  /* ---- Init ---- */
  function init() {
    checkToken();
    initToggle('resetNewPwdToggle',     'resetNewPassword');
    initToggle('resetConfirmPwdToggle', 'resetConfirmPassword');
    initForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
