/* =========================================================
   TRANS-NZOIA AHP - Constituency Detail Page JS
   Progress animation for server-rendered constituency data
   ========================================================= */
(function () {
  'use strict';

  function animateFills() {
    const fills = document.querySelectorAll('[data-target]');

    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const fill = entry.target;
          fill.style.width = `${fill.getAttribute('data-target') || 0}%`;
          obs.unobserve(fill);
        });
      }, { threshold: 0.3 });

      fills.forEach(fill => obs.observe(fill));
      return;
    }

    fills.forEach(fill => {
      fill.style.width = `${fill.getAttribute('data-target') || 0}%`;
    });
  }

  document.addEventListener('DOMContentLoaded', animateFills);
})();
