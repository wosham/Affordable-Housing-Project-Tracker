/* Contact page interactions */
(function () {
  'use strict';

  const DEFAULT_FILE_LABEL = 'Choose file (PDF, JPG, PNG - max 5MB)';
  const MAX_FILE_BYTES = 5 * 1024 * 1024;
  const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        obs.unobserve(entry.target);
      });
    }, { threshold: 0.07, rootMargin: '0px 0px -40px 0px' });

    els.forEach((el) => obs.observe(el));
  }

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
      elems.forEach((el, index) => {
        setTimeout(() => {
          el.style.transition = 'opacity .6s ease, transform .6s ease';
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }, 120 + index * 110);
      });
    });
  }

  function parseTime(value, fallback) {
    const parts = String(value || fallback).split(':').map((part) => parseInt(part, 10));
    if (parts.length < 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) return parseTime(fallback, '08:00');
    return (parts[0] * 60) + parts[1];
  }

  function initOfficeHours() {
    const form = document.getElementById('contactForm');
    const weekdayEl = document.getElementById('ctWeekdayStatus');
    const saturdayEl = document.getElementById('ctSaturdayStatus');
    if (!weekdayEl) return;

    let hours = {};
    try {
      hours = JSON.parse(form ? form.getAttribute('data-office-hours') || '{}' : '{}');
    } catch (error) {
      hours = {};
    }

    const now = new Date();
    const day = now.getDay();
    const minutes = now.getHours() * 60 + now.getMinutes();
    const weekdayStart = parseTime(hours.weekdayStart, '08:00');
    const weekdayEnd = parseTime(hours.weekdayEnd, '17:00');
    const saturdayStart = parseTime(hours.saturdayStart, '09:00');
    const saturdayEnd = parseTime(hours.saturdayEnd, '13:00');

    if (day >= 1 && day <= 5) {
      const open = minutes >= weekdayStart && minutes < weekdayEnd;
      weekdayEl.textContent = open ? 'Open Now' : 'Closed Now';
      weekdayEl.className = 'ct-hours-status ' + (open ? 'ct-hours-open' : 'ct-hours-closed');
    } else {
      weekdayEl.textContent = '-';
      weekdayEl.className = 'ct-hours-status';
    }

    if (saturdayEl) {
      if (day === 6) {
        const open = minutes >= saturdayStart && minutes < saturdayEnd;
        saturdayEl.textContent = open ? 'Open Now' : 'Closed Now';
        saturdayEl.className = 'ct-hours-status ' + (open ? 'ct-hours-sat-open' : 'ct-hours-closed');
      } else {
        saturdayEl.textContent = '-';
        saturdayEl.className = 'ct-hours-status';
      }
    }
  }

  function initCharCounter() {
    const textarea = document.getElementById('ctMessage');
    const counter = document.getElementById('ctCharCount');
    if (!textarea || !counter) return;

    const update = () => {
      const len = textarea.value.length;
      counter.textContent = `${len} / 1000`;
      counter.className = 'ct-char-count';
      if (len > 900) counter.classList.add('is-warning');
      if (len >= 1000) {
        counter.classList.remove('is-warning');
        counter.classList.add('is-limit');
      }
    };

    textarea.addEventListener('input', update);
    update();
  }

  function normalizePhone(value) {
    const clean = String(value || '').replace(/[\s().-]+/g, '');
    if (/^07\d{8}$/.test(clean) || /^01\d{8}$/.test(clean)) return `+254${clean.slice(1)}`;
    if (/^254(7|1)\d{8}$/.test(clean)) return `+${clean}`;
    return clean;
  }

  function initFileInput() {
    const fileInput = document.getElementById('ctAttachment');
    const fileName = document.getElementById('ctFileName');
    const fileLabel = document.getElementById('ctFileLabel');
    const fileErr = document.getElementById('ctFileErr');
    if (!fileInput || !fileLabel) return;

    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      if (fileErr) fileErr.textContent = '';

      if (!file) {
        fileLabel.classList.remove('has-file');
        if (fileName) fileName.textContent = DEFAULT_FILE_LABEL;
        return;
      }

      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!ALLOWED_EXTENSIONS.includes(ext)) {
        if (fileErr) fileErr.textContent = 'Only PDF, JPG and PNG attachments are allowed.';
        fileInput.value = '';
        fileLabel.classList.remove('has-file');
        if (fileName) fileName.textContent = DEFAULT_FILE_LABEL;
        return;
      }

      if (file.size > MAX_FILE_BYTES) {
        if (fileErr) fileErr.textContent = 'File exceeds 5MB limit. Please choose a smaller file.';
        fileInput.value = '';
        fileLabel.classList.remove('has-file');
        if (fileName) fileName.textContent = DEFAULT_FILE_LABEL;
        return;
      }

      fileLabel.classList.add('has-file');
      if (fileName) fileName.textContent = `${file.name} (${Math.max(1, Math.round(file.size / 1024))} KB)`;
    });
  }

  function setFieldError(field, message) {
    if (!field || !field.el) return;
    if (field.err) field.err.textContent = message || '';
    field.el.classList.toggle('is-error', Boolean(message));
    field.el.classList.toggle('is-valid', !message && field.el.value.trim() !== '');
  }

  function initForm() {
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('ctSubmitBtn');
    const successBox = document.getElementById('ctFormSuccess');
    const errorBox = document.getElementById('ctFormErrorState');
    const resetBtn = document.getElementById('ctSuccessReset');
    if (!form || !submitBtn) return;

    const phoneEl = document.getElementById('ctPhone');
    const fields = {
      name: { el: document.getElementById('ctName'), err: document.getElementById('ctNameErr'), validate: (v) => v.trim().length >= 2 ? '' : 'Please enter your full name.' },
      email: { el: document.getElementById('ctEmail'), err: document.getElementById('ctEmailErr'), validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? '' : 'Please enter a valid email address.' },
      phone: { el: phoneEl, err: document.getElementById('ctPhoneErr'), validate: (v) => {
        const value = normalizePhone(v);
        return value === '' || /^\+254(7|1)\d{8}$/.test(value) ? '' : 'Please enter a valid Kenyan phone number.';
      } },
      subject: { el: document.getElementById('ctSubject'), err: document.getElementById('ctSubjectErr'), validate: (v) => v ? '' : 'Please select a subject.' },
      message: { el: document.getElementById('ctMessage'), err: document.getElementById('ctMessageErr'), validate: (v) => v.trim().length >= 10 ? '' : 'Please enter at least 10 characters.' },
    };

    const validateField = (key) => {
      const field = fields[key];
      if (!field || !field.el) return true;
      const message = field.validate(field.el.value);
      setFieldError(field, message);
      return !message;
    };

    Object.keys(fields).forEach((key) => {
      const field = fields[key];
      if (!field.el) return;
      field.el.addEventListener('blur', () => validateField(key));
      field.el.addEventListener('input', () => {
        if (field.el.classList.contains('is-error')) validateField(key);
      });
    });

    if (phoneEl) {
      phoneEl.addEventListener('blur', () => {
        const normalized = normalizePhone(phoneEl.value);
        if (/^\+254(7|1)\d{8}$/.test(normalized)) phoneEl.value = normalized;
      });
    }

    form.addEventListener('submit', (event) => {
      event.preventDefault();

      const honey = form.querySelector('[name="_gotcha"]');
      if (honey && honey.value) return;

      let valid = true;
      Object.keys(fields).forEach((key) => {
        if (!validateField(key)) valid = false;
      });

      if (!valid) {
        const firstErr = form.querySelector('.is-error');
        if (firstErr) firstErr.focus();
        return;
      }

      if (phoneEl && phoneEl.value.trim() !== '') {
        phoneEl.value = normalizePhone(phoneEl.value);
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
        headers: { Accept: 'application/json' },
      })
        .then((response) => response.json().catch(() => ({})).then((data) => ({ ok: response.ok, data, status: response.status })))
        .then(({ ok, data, status }) => {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;

          if (!ok || !data.success) {
            const apiErrors = data.errors || {};
            Object.keys(apiErrors).forEach((key) => {
              if (fields[key]) setFieldError(fields[key], apiErrors[key]);
            });
            if (apiErrors.attachment) {
              const fileErr = document.getElementById('ctFileErr');
              if (fileErr) fileErr.textContent = apiErrors.attachment;
            }
            if (errorBox) {
              const message = errorBox.querySelector('[data-contact-error-message]');
              if (message) {
                message.textContent = data.message || (status === 429 ? 'Please wait a few minutes before sending another message.' : 'Unable to send your message right now.');
              }
              errorBox.hidden = false;
            }
            return;
          }

          const successMessage = successBox ? successBox.querySelector('[data-contact-success-message]') : null;
          if (successMessage && data.message) successMessage.textContent = data.message;
          form.classList.add('is-submitted');
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
        form.classList.remove('is-submitted');
        if (successBox) successBox.hidden = true;
        if (errorBox) errorBox.hidden = true;
        Object.values(fields).forEach((field) => setFieldError(field, ''));
        if (phoneEl) phoneEl.classList.remove('is-error');
        const fileErr = document.getElementById('ctFileErr');
        if (fileErr) fileErr.textContent = '';
        const charCount = document.getElementById('ctCharCount');
        if (charCount) charCount.textContent = '0 / 1000';
        const fileLabel = document.getElementById('ctFileLabel');
        const fileName = document.getElementById('ctFileName');
        if (fileLabel) fileLabel.classList.remove('has-file');
        if (fileName) fileName.textContent = DEFAULT_FILE_LABEL;
      });
    }
  }

  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => btn.classList.toggle('is-visible', window.scrollY > 500), { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

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
