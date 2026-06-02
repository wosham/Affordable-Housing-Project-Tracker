(function () {
  'use strict';

  var RULES = [
    { id: 'reqLength', test: function (v) { return v.length >= 8; } },
    { id: 'reqUpper', test: function (v) { return /[A-Z]/.test(v); } },
    { id: 'reqLower', test: function (v) { return /[a-z]/.test(v); } },
    { id: 'reqNumber', test: function (v) { return /[0-9]/.test(v); } },
    { id: 'reqSpecial', test: function (v) { return /[^A-Za-z0-9]/.test(v); } }
  ];
  var STRENGTH_LABELS = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
  var redirectInterval = null;
  var redirectCancelled = false;

  function meta(name, fallback) {
    var node = document.querySelector('meta[name="' + name + '"]');
    return node ? node.getAttribute('content') || fallback || '' : fallback || '';
  }

  function apiUrl(path) {
    var parts = window.location.pathname.split('/admin/');
    var base = parts.length > 1 ? parts[0] : '';
    return base.replace(/\/$/, '') + '/' + path.replace(/^\/+/, '');
  }

  function request(path, options) {
    options = options || {};
    var method = (options.method || 'POST').toUpperCase();
    var headers = Object.assign({ Accept: 'application/json' }, options.headers || {});
    if (method !== 'GET') headers['X-CSRF-Token'] = meta('csrf-token', '');
    var body = options.body;
    if (body && typeof body === 'object') {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }
    return fetch(apiUrl(path), { method: method, headers: headers, body: body }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) throw new Error(data.message || 'Request failed.');
        return data;
      });
    });
  }

  function getStrengthLevel(val) {
    if (!val) return 0;
    return RULES.filter(function (r) { return r.test(val); }).length;
  }

  function updateStrength(val) {
    var fill = document.getElementById('resetStrengthFill');
    var label = document.getElementById('resetStrengthLabel');
    var level = getStrengthLevel(val);
    RULES.forEach(function (r) {
      var li = document.getElementById(r.id);
      if (!li) return;
      var met = r.test(val);
      li.classList.toggle('is-met', met);
      var icon = li.querySelector('.rp-req-icon');
      if (icon) icon.className = met ? 'fa-solid fa-circle-check rp-req-icon' : 'fa-solid fa-circle rp-req-icon';
    });
    if (fill) fill.setAttribute('data-level', val ? String(level) : '');
    if (label) label.textContent = val ? STRENGTH_LABELS[level] : '';
  }

  function allRulesMet(val) {
    return RULES.every(function (r) { return r.test(val); });
  }

  function setFieldState(inputId, errId, msg) {
    var input = document.getElementById(inputId);
    var err = document.getElementById(errId);
    if (!input || !err) return;
    err.textContent = msg;
    input.classList.toggle('is-error', !!msg);
    input.classList.toggle('is-valid', !msg && input.value.length > 0);
  }

  function makeToggle(toggleId, inputId) {
    var btn = document.getElementById(toggleId);
    var input = document.getElementById(inputId);
    if (!btn || !input || btn.dataset.bound === '1') return;
    btn.dataset.bound = '1';
    btn.addEventListener('click', function () {
      var isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      btn.setAttribute('aria-pressed', String(!isText));
      btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
      var icon = btn.querySelector('i');
      if (icon) icon.className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });
  }

  function showInvalid() {
    var formState = document.getElementById('resetFormState');
    var invalidState = document.getElementById('resetInvalidState');
    if (formState) formState.hidden = true;
    if (invalidState) invalidState.hidden = false;
  }

  function showForm() {
    var formState = document.getElementById('resetFormState');
    var invalidState = document.getElementById('resetInvalidState');
    if (invalidState) invalidState.hidden = true;
    if (formState) formState.hidden = false;
  }

  function initTokenCheck() {
    var token = meta('reset-token', '') || new URLSearchParams(window.location.search).get('token') || '';
    if (!token || token.length < 32) {
      showInvalid();
      return;
    }
    request('api/auth/reset-password.php?token=' + encodeURIComponent(token), { method: 'GET' }).then(function (data) {
      if (data.success) showForm();
      else showInvalid();
    }).catch(showInvalid);
  }

  function startRedirectCountdown() {
    var el = document.getElementById('resetCountdown');
    var btn = document.getElementById('resetStayBtn');
    var remaining = 5;
    redirectCancelled = false;
    if (btn) btn.addEventListener('click', function () {
      redirectCancelled = true;
      if (redirectInterval) clearInterval(redirectInterval);
      var wrap = document.querySelector('.rp-redirect-notice');
      if (wrap) wrap.hidden = true;
      btn.hidden = true;
    });
    redirectInterval = setInterval(function () {
      if (redirectCancelled) {
        clearInterval(redirectInterval);
        return;
      }
      remaining--;
      if (el) el.textContent = remaining;
      if (remaining <= 0) {
        clearInterval(redirectInterval);
        if (!redirectCancelled) window.location.href = '../login.php?reason=password_reset';
      }
    }, 1000);
  }

  function showSuccess() {
    var formState = document.getElementById('resetFormState');
    var successState = document.getElementById('resetSuccessState');
    var icon = successState && successState.querySelector('.auth-card-icon');
    if (formState) formState.hidden = true;
    if (successState) successState.hidden = false;
    if (icon) icon.classList.add('auth-card-icon--anim');
    startRedirectCountdown();
  }

  function setLoading(button, loading) {
    if (!button) return;
    button.classList.toggle('is-loading', loading);
    button.disabled = loading;
  }

  function initForm() {
    var form = document.getElementById('resetForm');
    var btn = document.getElementById('resetSubmitBtn');
    var errAlert = document.getElementById('resetErrorAlert');
    var errMsg = document.getElementById('resetErrorMsg');
    var newIn = document.getElementById('resetNewPassword');
    var confIn = document.getElementById('resetConfirmPassword');
    if (!form) return;

    makeToggle('resetNewPwdToggle', 'resetNewPassword');
    makeToggle('resetConfirmPwdToggle', 'resetConfirmPassword');

    if (newIn) newIn.addEventListener('input', function () {
      updateStrength(this.value);
      if (this.value) setFieldState('resetNewPassword', 'resetNewPasswordErr', allRulesMet(this.value) ? '' : 'Password does not meet all requirements.');
    });
    if (newIn) newIn.addEventListener('blur', function () {
      if (!this.value) setFieldState('resetNewPassword', 'resetNewPasswordErr', 'Password is required.');
    });
    if (confIn) confIn.addEventListener('blur', function () {
      var msg = !this.value ? 'Please confirm your password.' : (this.value !== (newIn ? newIn.value : '') ? 'Passwords do not match.' : '');
      setFieldState('resetConfirmPassword', 'resetConfirmPasswordErr', msg);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var token = meta('reset-token', '') || new URLSearchParams(window.location.search).get('token') || '';
      var pw = newIn ? newIn.value : '';
      var conf = confIn ? confIn.value : '';
      var pwErr = !pw ? 'Password is required.' : (!allRulesMet(pw) ? 'Password does not meet all requirements.' : '');
      var confErr = !conf ? 'Please confirm your password.' : (conf !== pw ? 'Passwords do not match.' : '');
      setFieldState('resetNewPassword', 'resetNewPasswordErr', pwErr);
      setFieldState('resetConfirmPassword', 'resetConfirmPasswordErr', confErr);
      if (pwErr || confErr) {
        document.getElementById(pwErr ? 'resetNewPassword' : 'resetConfirmPassword').focus();
        return;
      }

      setLoading(btn, true);
      if (errAlert) errAlert.hidden = true;

      request('api/auth/reset-password.php', {
        body: { token: token, password: pw, confirm_password: conf }
      }).then(showSuccess).catch(function (error) {
        if (errMsg) errMsg.textContent = error.message || 'Password could not be updated.';
        if (errAlert) errAlert.hidden = false;
      }).finally(function () {
        setLoading(btn, false);
      });
    });
  }

  function init() {
    initTokenCheck();
    initForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
