(function () {
  'use strict';

  /* ---- 1. Session / reason banner ---- */
  function initReasonBanner() {
    const params  = new URLSearchParams(window.location.search);
    const reason  = params.get('reason');
    const alert   = document.getElementById('authTimeoutAlert');
    if (!alert) return;
    if (reason === 'timeout' || reason === 'session_expired') {
      alert.hidden = false;
    }
  }

  /* ---- 2. Alert dismiss ---- */
  function initAlertClose() {
    document.querySelectorAll('.auth-alert-close').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.closest('.auth-alert').hidden = true;
      });
    });
  }

  /* ---- 3. Password show / hide ---- */
  function initPasswordToggle() {
    var toggle = document.getElementById('loginPwdToggle');
    var input  = document.getElementById('loginPassword');
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

  /* ---- 4. Validation helpers ---- */
  function validateEmail(val) {
    if (!val.trim()) return 'Email address is required.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return 'Please enter a valid email address.';
    return '';
  }
  function validatePassword(val) {
    if (!val) return 'Password is required.';
    if (val.length < 6) return 'Password must be at least 6 characters.';
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

  /* ---- 5. Remember me ---- */
  function initRememberMe() {
    var checkbox = document.getElementById('loginRemember');
    var emailIn  = document.getElementById('loginEmail');
    if (!checkbox || !emailIn) return;
    var saved = localStorage.getItem('ahp_remember_email');
    if (saved) { emailIn.value = saved; checkbox.checked = true; }
    checkbox.addEventListener('change', function () {
      if (!checkbox.checked) localStorage.removeItem('ahp_remember_email');
    });
  }

  /* ---- 6. Form submit ---- */
  function initForm() {
    var form        = document.getElementById('loginForm');
    var btn         = document.getElementById('loginSubmitBtn');
    var errorAlert  = document.getElementById('authErrorAlert');
    var errorMsg    = document.getElementById('authErrorMsg');
    if (!form) return;

    /* Blur validation */
    var emailIn = document.getElementById('loginEmail');
    var passIn  = document.getElementById('loginPassword');
    if (emailIn) emailIn.addEventListener('blur', function () {
      setFieldState('loginEmail', 'loginEmailErr', validateEmail(this.value));
    });
    if (passIn) passIn.addEventListener('blur', function () {
      setFieldState('loginPassword', 'loginPasswordErr', validatePassword(this.value));
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      /* Honeypot */
      var hp = form.querySelector('[name="_gotcha"]');
      if (hp && hp.value) return;

      var email = emailIn ? emailIn.value : '';
      var pass  = passIn  ? passIn.value  : '';
      var eErr  = validateEmail(email);
      var pErr  = validatePassword(pass);

      setFieldState('loginEmail',    'loginEmailErr',    eErr);
      setFieldState('loginPassword', 'loginPasswordErr', pErr);

      if (eErr || pErr) {
        document.getElementById(eErr ? 'loginEmail' : 'loginPassword').focus();
        return;
      }

      /* Save remember-me */
      var remember = document.getElementById('loginRemember');
      if (remember && remember.checked) localStorage.setItem('ahp_remember_email', email);
      else localStorage.removeItem('ahp_remember_email');

      /* Loading state */
      btn.classList.add('is-loading');
      btn.disabled = true;
      if (errorAlert) errorAlert.hidden = true;

      /* Simulated async auth — replace with real API call */
      setTimeout(function () {
        btn.classList.remove('is-loading');
        btn.disabled = false;

        /* ----- DEMO: always shows invalid (flip `success` to true when backend is wired up) ----- */
        var success = false;
        if (success) {
          window.location.href = '../dashboard/index.html';
        } else {
          if (errorAlert) errorAlert.hidden = false;
          if (errorMsg) errorMsg.textContent = 'Invalid email or password. Please check your credentials and try again.';
          if (passIn) { passIn.value = ''; passIn.focus(); }
          passIn && passIn.classList.remove('is-valid');
        }
      }, 1400);
    });
  }

  /* ---- Init ---- */
  function init() {
    initReasonBanner();
    initAlertClose();
    initPasswordToggle();
    initRememberMe();
    initForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
