/* =========================================================
   TRANS-NZOIA AHP - Project Detail Page JS
   Enhances server-rendered project data.
   ========================================================= */
(function () {
  'use strict';

  function animateProgressBars() {
    const fills = document.querySelectorAll('[data-target]');
    if (!fills.length) return;

    const reveal = (fill) => {
      const target = Math.max(0, Math.min(100, Number(fill.getAttribute('data-target')) || 0));
      fill.style.width = target + '%';
    };

    if (!('IntersectionObserver' in window)) {
      fills.forEach(reveal);
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        reveal(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.35 });

    fills.forEach((fill) => observer.observe(fill));
  }

  function initGalleryLightbox() {
    const items = Array.from(document.querySelectorAll('[data-gallery-index]'));
    if (!items.length) return;

    const images = items.map((item) => ({
      src: item.getAttribute('data-gallery-src') || '',
      alt: item.getAttribute('data-gallery-alt') || 'Project site photo'
    })).filter((image) => image.src !== '');

    if (!images.length) return;

    let current = 0;
    let activeTrigger = null;
    const lightbox = document.createElement('div');
    lightbox.className = 'pd-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Photo lightbox');
    lightbox.innerHTML = [
      '<button class="pd-lb-close" type="button" aria-label="Close lightbox"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>',
      '<button class="pd-lb-prev" type="button" aria-label="Previous photo"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>',
      '<button class="pd-lb-next" type="button" aria-label="Next photo"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>',
      '<div class="pd-lb-img-wrap"><img class="pd-lb-img" src="" alt=""></div>',
      '<p class="pd-lb-caption"></p>'
    ].join('');
    document.body.appendChild(lightbox);

    const img = lightbox.querySelector('.pd-lb-img');
    const caption = lightbox.querySelector('.pd-lb-caption');
    const closeBtn = lightbox.querySelector('.pd-lb-close');
    const prevBtn = lightbox.querySelector('.pd-lb-prev');
    const nextBtn = lightbox.querySelector('.pd-lb-next');

    if (images.length <= 1) {
      prevBtn.hidden = true;
      nextBtn.hidden = true;
    }

    function show(index, trigger) {
      if (trigger) activeTrigger = trigger;
      current = (index + images.length) % images.length;
      img.src = images[current].src;
      img.alt = images[current].alt;
      caption.textContent = 'Photo ' + (current + 1) + ' of ' + images.length;
      lightbox.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      closeBtn.focus();
    }

    function close() {
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
      if (activeTrigger && typeof activeTrigger.focus === 'function') {
        activeTrigger.focus();
      }
    }

    items.forEach((item, index) => {
      item.addEventListener('click', () => show(index, item));
    });

    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', () => show(current - 1));
    nextBtn.addEventListener('click', () => show(current + 1));
    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox) close();
    });

    document.addEventListener('keydown', (event) => {
      if (!lightbox.classList.contains('is-open')) return;
      if (event.key === 'Escape') close();
      if (event.key === 'ArrowLeft' && images.length > 1) show(current - 1);
      if (event.key === 'ArrowRight' && images.length > 1) show(current + 1);
      if (event.key === 'Tab') {
        const focusable = Array.from(lightbox.querySelectorAll('button:not([hidden])'));
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    animateProgressBars();
    initGalleryLightbox();
  });
})();
