/* =========================================================
   TRANS-NZOIA AHP TRACKER — Contact Page JS
   ========================================================= */

(function () {
  'use strict';

  /* ---------------------------------------------------------
     1. FADE-UP SCROLL OBSERVER
     --------------------------------------------------------- */
  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const obs = new IntersectionObserver(
      (entries) => entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); obs.unobserve(e.target); }
      }),
      { threshold: 0.07, rootMargin: '0px 0px -40px 0px' }
    );
    els.forEach((el) => obs.observe(el));
  }

  /* ---------------------------------------------------------
     2. HERO ENTRANCE STAGGER
     --------------------------------------------------------- */
  function initHeroEntrance() {
    const elems = [
      document.querySelector('.ct-hero-eyebrow'),
      document.querySelector('.ct-hero-title'),
      document.querySelector('.ct-hero-sub'),
      document.querySelector('.ct-hero-stats'),
    ].filter(Boolean);

    elems.forEach((el) => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(22px)';
    });

    requestAnimationFrame(() => {
      elems.forEach((el, i) => {
        setTimeout(() => {
          el.style.transition = 'opacity .6s ease, transform .6s ease';
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }, 120 + i * 110);
      });
    });
  }

  /* ---------------------------------------------------------
     3. OFFICE HOURS — live status
     --------------------------------------------------------- */
  function initOfficeHours() {
    const now  = new Date();
    const day  = now.getDay();
    const time = now.getHours() * 60 + now.getMinutes();

    const weekdayEl  = document.getElementById('ctWeekdayStatus');
    const saturdayEl = document.getElementById('ctSaturdayStatus');
    if (!weekdayEl) return;

    if (day >= 1 && day <= 5) {
      if (time >= 480 && time < 1020) {
        weekdayEl.textContent = 'Open Now';
        weekdayEl.classList.add('ct-hours-open');
      } else {
        weekdayEl.textContent = 'Closed Now';
        weekdayEl.className = 'ct-hours-status ct-hours-closed';
      }
    } else {
      weekdayEl.textContent = '—';
      weekdayEl.className = 'ct-hours-status';
    }

    if (saturdayEl) {
      if (day === 6 && time >= 540 && time < 780) {
        saturdayEl.textContent = 'Open Now';
        saturdayEl.className = 'ct-hours-status ct-hours-sat-open';
      } else if (day === 6) {
        saturdayEl.textContent = 'Closed Now';
        saturdayEl.className = 'ct-hours-status ct-hours-closed';
      } else {
        saturdayEl.textContent = '—';
        saturdayEl.className = 'ct-hours-status';
      }
    }
  }

  /* ---------------------------------------------------------
     4. CHARACTER COUNTER
     --------------------------------------------------------- */
  function initCharCounter() {
    const textarea = document.getElementById('ctMessage');
    const counter  = document.getElementById('ctCharCount');
    if (!textarea || !counter) return;

    textarea.addEventListener('input', () => {
      const len = textarea.value.length;
      counter.textContent = `${len} / 1000`;
      counter.className = 'ct-char-count';
      if (len > 900)  counter.classList.add('is-warning');
      if (len >= 1000) { counter.classList.remove('is-warning'); counter.classList.add('is-limit'); }
    });
  }

  /* ---------------------------------------------------------
     5. FILE INPUT LABEL UPDATE
     --------------------------------------------------------- */
  function initFileInput() {
    const fileInput = document.getElementById('ctAttachment');
    const fileName  = document.getElementById('ctFileName');
    const fileLabel = document.getElementById('ctFileLabel');
    const fileErr   = document.getElementById('ctFileErr');
    if (!fileInput) return;

    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (!file) return;

      const maxSize = 5 * 1024 * 1024;
      if (file.size > maxSize) {
        if (fileErr) fileErr.textContent = 'File exceeds 5MB limit. Please choose a smaller file.';
        fileInput.value = '';
        fileLabel.classList.remove('has-file');
        if (fileName) fileName.textContent = 'Choose file (PDF, JPG, PNG — max 5MB)';
        return;
      }

      if (fileErr) fileErr.textContent = '';
      fileLabel.classList.add('has-file');
      if (fileName) fileName.textContent = `${file.name} (${(file.size / 1024).toFixed(0)} KB)`;
    });
  }

  /* ---------------------------------------------------------
     6. FORM VALIDATION + SUBMISSION
     --------------------------------------------------------- */
  function initForm() {
    const form       = document.getElementById('contactForm');
    const submitBtn  = document.getElementById('ctSubmitBtn');
    const successBox = document.getElementById('ctFormSuccess');
    const errorBox   = document.getElementById('ctFormErrorState');
    const resetBtn   = document.getElementById('ctSuccessReset');
    if (!form) return;

    const fields = {
      name:    { el: document.getElementById('ctName'),    err: document.getElementById('ctNameErr'),    validate: (v) => v.trim().length >= 2 ? '' : 'Please enter your full name (at least 2 characters).' },
      email:   { el: document.getElementById('ctEmail'),   err: document.getElementById('ctEmailErr'),   validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? '' : 'Please enter a valid email address.' },
      subject: { el: document.getElementById('ctSubject'), err: document.getElementById('ctSubjectErr'), validate: (v) => v ? '' : 'Please select a subject.' },
      message: { el: document.getElementById('ctMessage'), err: document.getElementById('ctMessageErr'), validate: (v) => v.trim().length >= 10 ? '' : 'Please enter at least 10 characters.' },
    };

    function validateField(key) {
      const f = fields[key];
      if (!f.el) return true;
      const msg = f.validate(f.el.value);
      if (f.err) f.err.textContent = msg;
      f.el.classList.toggle('is-error', !!msg);
      f.el.classList.toggle('is-valid', !msg && f.el.value.trim() !== '');
      return !msg;
    }

    /* Blur validation */
    Object.keys(fields).forEach((key) => {
      const f = fields[key];
      if (f.el) {
        f.el.addEventListener('blur', () => validateField(key));
        f.el.addEventListener('input', () => {
          if (f.el.classList.contains('is-error')) validateField(key);
        });
      }
    });

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      /* Honeypot check */
      const honey = form.querySelector('[name="_gotcha"]');
      if (honey && honey.value) return;

      let valid = true;
      Object.keys(fields).forEach((key) => { if (!validateField(key)) valid = false; });
      if (!valid) {
        const firstErr = form.querySelector('.is-error');
        if (firstErr) firstErr.focus();
        return;
      }

      const endpoint = form.getAttribute('data-contact-endpoint') || 'api/public/contact-submit.php';
      const formData = new FormData(form);

      submitBtn.classList.add('is-loading');
      submitBtn.disabled = true;
      if (errorBox) errorBox.hidden = true;

      fetch(endpoint, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
      })
        .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;

          if (!ok || !data.success) {
            const apiErrors = data.errors || {};
            Object.keys(apiErrors).forEach((key) => {
              if (fields[key] && fields[key].err) fields[key].err.textContent = apiErrors[key];
              if (fields[key] && fields[key].el) fields[key].el.classList.add('is-error');
            });
            const fileErr = document.getElementById('ctFileErr');
            if (apiErrors.attachment && fileErr) fileErr.textContent = apiErrors.attachment;
            if (errorBox) {
              const message = errorBox.querySelector('[data-contact-error-message]');
              if (message && data.message) message.textContent = data.message;
              errorBox.hidden = false;
            }
            return;
          }

          const successMessage = successBox ? successBox.querySelector('[data-contact-success-message]') : null;
          if (successMessage && data.message) successMessage.textContent = data.message;
          form.querySelectorAll('.ct-field-group, .ct-form-row--2col, .ct-form-footer, .ct-privacy-note').forEach((el) => { el.style.display = 'none'; });
          if (successBox) {
            successBox.hidden = false;
            successBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        })
        .catch(() => {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;
          if (errorBox) errorBox.hidden = false;
        });
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        form.reset();
        if (successBox) successBox.hidden = true;
        if (errorBox)   errorBox.hidden   = true;
        form.querySelectorAll('.ct-field-group, .ct-form-row--2col, .ct-form-footer, .ct-privacy-note').forEach((el) => { el.style.display = ''; });
        Object.values(fields).forEach((f) => {
          if (f.el)  { f.el.classList.remove('is-error','is-valid'); }
          if (f.err) { f.err.textContent = ''; }
        });
        const charCount = document.getElementById('ctCharCount');
        if (charCount) charCount.textContent = '0 / 1000';
        const fileLabel = document.getElementById('ctFileLabel');
        const fileName  = document.getElementById('ctFileName');
        if (fileLabel) fileLabel.classList.remove('has-file');
        if (fileName)  fileName.textContent = 'Choose file (PDF, JPG, PNG — max 5MB)';
      });
    }
  }

  /* ---------------------------------------------------------
     7. BACK TO TOP
     --------------------------------------------------------- */
  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => btn.classList.toggle('is-visible', window.scrollY > 500), { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ---------------------------------------------------------
     INIT
     --------------------------------------------------------- */
  function init() {
    initHeroEntrance();
    initFadeUp();
    initOfficeHours();
    initCharCounter();
    initFileInput();
    initForm();
    initBackToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
