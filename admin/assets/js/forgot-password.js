(function () {
  'use strict';

  var RESEND_DELAY = 60;
  var lastEmail = '';

  function meta(name, fallback) {
    var node = document.querySelector('meta[name="' + name + '"]');
    return node ? node.getAttribute('content') || fallback || '' : fallback || '';
  }

  function apiUrl(path) {
    var parts = window.location.pathname.split('/admin/');
    var base = parts.length > 1 ? parts[0] : '';
    return base.replace(/\/$/, '') + '/' + path.replace(/^\/+/, '');
  }

  function request(path, body) {
    return fetch(apiUrl(path), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': meta('csrf-token', '')
      },
      body: JSON.stringify(body || {})
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) throw new Error(data.message || 'Request failed.');
        return data;
      });
    });
  }

  function validateEmail(val) {
    if (!val.trim()) return 'Email address is required.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return 'Please enter a valid email address.';
    return '';
  }

  function setFieldState(inputId, errId, msg) {
    var input = document.getElementById(inputId);
    var err = document.getElementById(errId);
    if (!input || !err) return;
    err.textContent = msg;
    input.classList.toggle('is-error', !!msg);
    input.classList.toggle('is-valid', !msg && input.value.length > 0);
  }

  function startResendTimer() {
    var resendBtn = document.getElementById('forgotResendBtn');
    var timerSpan = document.getElementById('forgotResendTimer');
    var countdownEl = document.getElementById('forgotCountdown');
    if (!resendBtn || !timerSpan || !countdownEl) return;
    var remaining = RESEND_DELAY;
    resendBtn.disabled = true;
    timerSpan.hidden = false;
    countdownEl.textContent = remaining;
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
    var formState = document.getElementById('forgotFormState');
    var successState = document.getElementById('forgotSuccessState');
    var successEmail = document.getElementById('forgotSuccessEmail');
    if (formState) formState.hidden = true;
    if (successState) successState.hidden = false;
    if (successEmail) successEmail.textContent = email;
    startResendTimer();
  }

  function setLoading(button, loading) {
    if (!button) return;
    button.classList.toggle('is-loading', loading);
    button.disabled = loading;
  }

  function sendReset(email, button) {
    setLoading(button, true);
    return request('api/auth/forgot-password.php', { email: email }).then(function () {
      lastEmail = email;
      showSuccess(email);
    }).catch(function (error) {
      var errAlert = document.getElementById('forgotErrorAlert');
      var errMsg = document.getElementById('forgotErrorMsg');
      if (errMsg) errMsg.textContent = error.message || 'Something went wrong. Please try again.';
      if (errAlert) errAlert.hidden = false;
    }).finally(function () {
      setLoading(button, false);
    });
  }

  function initForm() {
    var form = document.getElementById('forgotForm');
    var btn = document.getElementById('forgotSubmitBtn');
    var errAlert = document.getElementById('forgotErrorAlert');
    var emailIn = document.getElementById('forgotEmail');
    if (!form) return;

    if (emailIn) emailIn.addEventListener('blur', function () {
      setFieldState('forgotEmail', 'forgotEmailErr', validateEmail(this.value));
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = emailIn ? emailIn.value.trim() : '';
      var eErr = validateEmail(email);
      setFieldState('forgotEmail', 'forgotEmailErr', eErr);
      if (eErr) {
        if (emailIn) emailIn.focus();
        return;
      }
      if (errAlert) errAlert.hidden = true;
      sendReset(email, btn);
    });

    var resendBtn = document.getElementById('forgotResendBtn');
    if (resendBtn) {
      resendBtn.addEventListener('click', function () {
        if (lastEmail) sendReset(lastEmail, resendBtn);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initForm);
  } else {
    initForm();
  }
}());
