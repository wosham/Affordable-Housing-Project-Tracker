(function () {
  'use strict';

  /* ---- Strength rules ---- */
  var RULES = [
    { id: 'reqLength',  test: function (v) { return v.length >= 8; } },
    { id: 'reqUpper',   test: function (v) { return /[A-Z]/.test(v); } },
    { id: 'reqLower',   test: function (v) { return /[a-z]/.test(v); } },
    { id: 'reqNumber',  test: function (v) { return /[0-9]/.test(v); } },
    { id: 'reqSpecial', test: function (v) { return /[^A-Za-z0-9]/.test(v); } }
  ];

  function getStrengthLevel(val) {
    if (!val) return 0;
    return RULES.filter(function (r) { return r.test(val); }).length;
  }
  var STRENGTH_LABELS = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];

  function updateStrength(val) {
    var fill  = document.getElementById('resetStrengthFill');
    var label = document.getElementById('resetStrengthLabel');
    var level = getStrengthLevel(val);
    RULES.forEach(function (r) {
      var li = document.getElementById(r.id);
      if (!li) return;
      var met = r.test(val);
      li.classList.toggle('is-met', met);
      var icon = li.querySelector('.rp-req-icon');
      if (icon) icon.className = met
        ? 'fa-solid fa-circle-check rp-req-icon'
        : 'fa-solid fa-circle rp-req-icon';
    });
    if (fill) fill.setAttribute('data-level', val ? String(level) : '');
    if (label) label.textContent = val ? STRENGTH_LABELS[level] : '';
  }

  function allRulesMet(val) {
    return RULES.every(function (r) { return r.test(val); });
  }

  /* ---- Helpers ---- */
  function setFieldState(inputId, errId, msg) {
    var input = document.getElementById(inputId);
    var err   = document.getElementById(errId);
    if (!input || !err) return;
    err.textContent = msg;
    input.classList.toggle('is-error', !!msg);
    input.classList.toggle('is-valid', !msg && input.value.length > 0);
  }

  function makeToggle(toggleId, inputId) {
    var btn   = document.getElementById(toggleId);
    var input = document.getElementById(inputId);
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
      var isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      btn.setAttribute('aria-pressed', String(!isText));
      btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
      var icon = btn.querySelector('i');
      if (icon) icon.className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });
  }

  /* ---- Token check ---- */
  function initTokenCheck() {
    var params      = new URLSearchParams(window.location.search);
    var token       = params.get('token');
    var formState   = document.getElementById('resetFormState');
    var invalidState = document.getElementById('resetInvalidState');
    if (!token || token.length < 32) {
      if (invalidState) invalidState.hidden = false;
    } else {
      if (formState) formState.hidden = false;
    }
  }

  /* ---- Countdown + redirect ---- */
  var _redirectInterval = null;
  var _redirectCancelled = false;

  function startRedirectCountdown() {
    var el  = document.getElementById('resetCountdown');
    var btn = document.getElementById('resetStayBtn');
    var remaining = 5;
    _redirectCancelled = false;
    if (btn) btn.addEventListener('click', function () {
      _redirectCancelled = true;
      if (_redirectInterval) clearInterval(_redirectInterval);
      var wrap = document.querySelector('.rp-redirect-notice');
      if (wrap) wrap.hidden = true;
      btn.hidden = true;
    });
    _redirectInterval = setInterval(function () {
      if (_redirectCancelled) { clearInterval(_redirectInterval); return; }
      remaining--;
      if (el) el.textContent = remaining;
      if (remaining <= 0) {
        clearInterval(_redirectInterval);
        if (!_redirectCancelled) window.location.href = '../login.php?reason=password_reset';
      }
    }, 1000);
  }

  /* ---- Show success ---- */
  function showSuccess() {
    var formState    = document.getElementById('resetFormState');
    var successState = document.getElementById('resetSuccessState');
    var icon         = successState && successState.querySelector('.auth-card-icon');
    if (formState)    formState.hidden    = true;
    if (successState) successState.hidden = false;
    if (icon) icon.classList.add('auth-card-icon--anim');
    startRedirectCountdown();
  }

  /* ---- Form ---- */
  function initForm() {
    var form    = document.getElementById('resetForm');
    var btn     = document.getElementById('resetSubmitBtn');
    var errAlert = document.getElementById('resetErrorAlert');
    var newIn   = document.getElementById('resetNewPassword');
    var confIn  = document.getElementById('resetConfirmPassword');
    if (!form) return;

    makeToggle('resetNewPwdToggle',     'resetNewPassword');
    makeToggle('resetConfirmPwdToggle', 'resetConfirmPassword');

    if (newIn) newIn.addEventListener('input', function () {
      updateStrength(this.value);
      if (this.value) setFieldState('resetNewPassword', 'resetNewPasswordErr',
        allRulesMet(this.value) ? '' : 'Password does not meet all requirements.');
    });
    if (newIn) newIn.addEventListener('blur', function () {
      if (!this.value) setFieldState('resetNewPassword', 'resetNewPasswordErr', 'Password is required.');
    });
    if (confIn) confIn.addEventListener('blur', function () {
      var msg = !this.value ? 'Please confirm your password.'
              : (this.value !== (newIn ? newIn.value : '') ? 'Passwords do not match.' : '');
      setFieldState('resetConfirmPassword', 'resetConfirmPasswordErr', msg);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var pw   = newIn  ? newIn.value  : '';
      var conf = confIn ? confIn.value : '';
      var pwErr   = !pw ? 'Password is required.' : (!allRulesMet(pw) ? 'Password does not meet all requirements.' : '');
      var confErr = !conf ? 'Please confirm your password.' : (conf !== pw ? 'Passwords do not match.' : '');
      setFieldState('resetNewPassword',     'resetNewPasswordErr',     pwErr);
      setFieldState('resetConfirmPassword', 'resetConfirmPasswordErr', confErr);
      if (pwErr || confErr) { document.getElementById(pwErr ? 'resetNewPassword' : 'resetConfirmPassword').focus(); return; }

      btn.classList.add('is-loading');
      btn.disabled = true;
      if (errAlert) errAlert.hidden = true;

      setTimeout(function () {
        btn.classList.remove('is-loading');
        btn.disabled = false;
        showSuccess();
      }, 1400);
    });
  }

  function init() { initTokenCheck(); makeToggle('resetNewPwdToggle', 'resetNewPassword'); makeToggle('resetConfirmPwdToggle', 'resetConfirmPassword'); initForm(); }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
}());
