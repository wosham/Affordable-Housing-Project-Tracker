/* =========================================================
   TRANS-NZOIA AHP TRACKER - Gallery Page JS
   ========================================================= */
(function () {
  'use strict';

  const SITE_DATA = window.GALLERY_SITE_DATA || {};

  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    els.forEach((el) => observer.observe(el));
  }

  function initHeroEntrance() {
    const elements = [
      document.querySelector('.gl-hero-eyebrow'),
      document.querySelector('.gl-hero-title'),
      document.querySelector('.gl-hero-sub'),
      document.querySelector('.gl-hero-kpi-strip')
    ].filter(Boolean);

    elements.forEach((el) => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(28px)';
    });

    requestAnimationFrame(() => {
      elements.forEach((el, index) => {
        setTimeout(() => {
          el.style.transition = 'opacity 0.65s ease, transform 0.65s ease';
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }, 150 + index * 120);
      });
    });
  }

  function initHighlights() {
    const track = document.getElementById('hlTrack');
    const dotsWrap = document.getElementById('hlDots');
    const btnPrev = document.getElementById('hlPrev');
    const btnNext = document.getElementById('hlNext');
    if (!track || !dotsWrap || !btnPrev || !btnNext) return;

    const slides = track.querySelectorAll('.gl-hl-slide');
    const total = slides.length;
    let current = 0;
    let autoTimer = null;

    slides.forEach((_, index) => {
      const dot = document.createElement('button');
      dot.className = 'gl-hl-dot' + (index === 0 ? ' is-active' : '');
      dot.type = 'button';
      dot.setAttribute('role', 'tab');
      dot.setAttribute('aria-label', 'Go to highlight ' + (index + 1));
      dot.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
      dot.addEventListener('click', () => goTo(index));
      dotsWrap.appendChild(dot);
    });

    function updateDots() {
      dotsWrap.querySelectorAll('.gl-hl-dot').forEach((dot, index) => {
        dot.classList.toggle('is-active', index === current);
        dot.setAttribute('aria-selected', index === current ? 'true' : 'false');
      });
    }

    function goTo(index) {
      current = Math.max(0, Math.min(total - 1, index));
      track.style.transform = 'translateX(calc(-' + (current * 100) + '% - ' + (current * 1.5) + 'rem))';
      updateDots();
      btnPrev.disabled = current === 0;
      btnNext.disabled = current === total - 1;
    }

    function startAuto() {
      stopAuto();
      if (total > 1) {
        autoTimer = setInterval(() => goTo(current < total - 1 ? current + 1 : 0), 5500);
      }
    }

    function stopAuto() {
      if (autoTimer) clearInterval(autoTimer);
      autoTimer = null;
    }

    btnPrev.addEventListener('click', () => { stopAuto(); goTo(current - 1); startAuto(); });
    btnNext.addEventListener('click', () => { stopAuto(); goTo(current + 1); startAuto(); });
    const nav = track.closest('.gl-hl-nav');
    if (nav) {
      nav.addEventListener('mouseenter', stopAuto);
      nav.addEventListener('mouseleave', startAuto);
    }

    let touchStartX = 0;
    track.addEventListener('touchstart', (event) => { touchStartX = event.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', (event) => {
      const diff = touchStartX - event.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        stopAuto();
        goTo(current + (diff > 0 ? 1 : -1));
        startAuto();
      }
    }, { passive: true });

    goTo(0);
    startAuto();
  }

  function initPhotoGrid() {
    const grid = document.getElementById('photoGrid');
    const countEl = document.getElementById('filterCount');
    const noResults = document.getElementById('noResults');
    const resetBtn = document.getElementById('resetFilters');
    if (!grid) return;

    let activeCat = 'all';
    let activeYear = 'all';
    const items = Array.from(grid.querySelectorAll('.gl-photo-item'));
    const chips = document.querySelectorAll('.gl-chip');

    function applyFilters(animate) {
      let visible = 0;
      items.forEach((item) => {
        const catMatch = activeCat === 'all' || item.dataset.cat === activeCat;
        const yearMatch = activeYear === 'all' || item.dataset.year === activeYear;
        const show = catMatch && yearMatch;
        item.classList.toggle('is-hidden', !show);
        if (show) {
          visible++;
          if (animate) {
            item.classList.remove('is-entering');
            void item.offsetWidth;
            item.classList.add('is-entering');
          }
        }
      });

      if (countEl) countEl.innerHTML = 'Showing <strong>' + visible + '</strong> photos';
      if (noResults) noResults.hidden = visible > 0;
    }

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        const filter = chip.dataset.filter;
        const value = chip.dataset.val;
        const group = chip.closest('.gl-filter-chips');
        if (group) group.querySelectorAll('.gl-chip').forEach((btn) => btn.classList.remove('is-active'));
        chip.classList.add('is-active');
        if (filter === 'cat') activeCat = value;
        if (filter === 'year') activeYear = value;
        applyFilters(true);
      });
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        activeCat = 'all';
        activeYear = 'all';
        chips.forEach((chip) => chip.classList.toggle('is-active', chip.dataset.val === 'all'));
        applyFilters(true);
      });
    }

    applyFilters(false);
  }

  function initLightbox() {
    const lb = document.getElementById('glLightbox');
    const backdrop = document.getElementById('glLbBackdrop');
    const closeBtn = document.getElementById('glLbClose');
    const prevBtn = document.getElementById('glLbPrev');
    const nextBtn = document.getElementById('glLbNext');
    const lbImg = document.getElementById('glLbImg');
    const lbBadge = document.getElementById('glLbBadge');
    const lbCap = document.getElementById('glLbCaption');
    const lbDate = document.getElementById('glLbDate');
    const lbCount = document.getElementById('glLbCounter');
    const grid = document.getElementById('photoGrid');
    if (!lb || !grid) return;

    let visibleItems = [];
    let currentIndex = 0;

    function getVisible() {
      return Array.from(grid.querySelectorAll('.gl-photo-item:not(.is-hidden)'));
    }

    function loadSlide(index) {
      const item = visibleItems[index];
      if (!item) return;
      const img = item.querySelector('img');
      const badge = item.querySelector('.gl-photo-badge');
      const date = item.querySelector('.gl-photo-date');

      lbImg.classList.add('is-loading');
      lbImg.src = item.dataset.fullSrc || (img ? img.src : '');
      lbImg.alt = img ? img.alt : '';
      lbImg.onload = () => lbImg.classList.remove('is-loading');
      lbBadge.textContent = badge ? badge.textContent : '';
      lbBadge.className = 'gl-lb-badge';
      if (badge) lbBadge.className += ' ' + badge.className.replace('gl-photo-badge', '').trim();
      lbCap.textContent = img ? img.alt : '';
      lbDate.textContent = date ? date.textContent : '';
      lbCount.textContent = (index + 1) + ' / ' + visibleItems.length;
      prevBtn.disabled = index === 0;
      nextBtn.disabled = index === visibleItems.length - 1;
    }

    function openAt(index) {
      visibleItems = getVisible();
      currentIndex = index;
      lb.classList.add('is-open');
      lb.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      loadSlide(currentIndex);
      setTimeout(() => closeBtn.focus(), 100);
    }

    function close() {
      lb.classList.remove('is-open');
      lb.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    function prev() { if (currentIndex > 0) { currentIndex--; loadSlide(currentIndex); } }
    function next() { if (currentIndex < visibleItems.length - 1) { currentIndex++; loadSlide(currentIndex); } }

    grid.addEventListener('click', (event) => {
      const item = event.target.closest('.gl-photo-item');
      if (!item) return;
      visibleItems = getVisible();
      const index = visibleItems.indexOf(item);
      if (index >= 0) openAt(index);
    });

    document.addEventListener('click', (event) => {
      const mini = event.target.closest('.gl-site-mini-item');
      if (!mini) return;
      const src = mini.dataset.src;
      if (!src) return;
      document.querySelectorAll('.gl-chip[data-val="all"]').forEach((chip) => chip.click());
      setTimeout(() => {
        visibleItems = getVisible();
        const index = visibleItems.findIndex((item) => item.dataset.fullSrc === src);
        if (index >= 0) openAt(index);
      }, 50);
    });

    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);
    prevBtn.addEventListener('click', prev);
    nextBtn.addEventListener('click', next);
    document.addEventListener('keydown', (event) => {
      if (!lb.classList.contains('is-open')) return;
      if (event.key === 'Escape') close();
      if (event.key === 'ArrowLeft') prev();
      if (event.key === 'ArrowRight') next();
    });
  }

  function initSiteTabs() {
    const tabs = document.querySelectorAll('.gl-site-tab');
    const miniGrid = document.getElementById('siteMiniGrid');
    const stat1 = document.getElementById('siteStat1');
    const stat2 = document.getElementById('siteStat2');
    const stat3 = document.getElementById('siteStat3');
    const fill = document.getElementById('siteProgressFill');
    const link = document.getElementById('siteDetailsLink');
    if (!tabs.length || !miniGrid) return;

    function activateSite(siteKey) {
      const data = SITE_DATA[siteKey];
      if (!data) return;
      tabs.forEach((tab) => {
        tab.classList.toggle('is-active', tab.dataset.site === siteKey);
        tab.setAttribute('aria-selected', tab.dataset.site === siteKey ? 'true' : 'false');
      });
      if (stat1) stat1.textContent = data.units || 0;
      if (stat2) stat2.textContent = (data.pct || 0) + '%';
      if (stat3) stat3.textContent = data.photos || 0;
      if (fill) fill.style.width = (data.pct || 0) + '%';
      if (link && data.link) link.href = data.link;
      miniGrid.innerHTML = (data.images || []).map((src) => (
        '<button class="gl-site-mini-item" type="button" data-src="' + src + '" aria-label="View site photo">' +
          '<img src="' + src + '" alt="Site photo" loading="lazy">' +
        '</button>'
      )).join('');
    }

    tabs.forEach((tab) => tab.addEventListener('click', () => activateSite(tab.dataset.site)));
    const first = tabs[0] ? tabs[0].dataset.site : Object.keys(SITE_DATA)[0];
    if (first) activateSite(first);
  }

  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => {
      btn.classList.toggle('is-visible', window.scrollY > 500);
    }, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  function init() {
    initHeroEntrance();
    initFadeUp();
    initHighlights();
    initPhotoGrid();
    initLightbox();
    initSiteTabs();
    initBackToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
