/* =========================================================
   TRANS-NZOIA AHP TRACKER — Gallery Page JS
   ========================================================= */

(function () {
  'use strict';

  /* ---------------------------------------------------------
     SITE DATA — constituency panel info
     --------------------------------------------------------- */
  const SITE_DATA = {
    kiminini: { units: 400, pct: 60, photos: 7,  seeds: ['gl1','gl2','gl4','gl8','gl9','gl13','gl15'] },
    saboti:   { units: 300, pct: 42, photos: 4,  seeds: ['gl3','gl10','gl14','gl20'] },
    kwanza:   { units: 250, pct: 30, photos: 3,  seeds: ['gl5','gl7','gl16'] },
    'tn-east':{ units: 280, pct: 22, photos: 4,  seeds: ['gl11','gl17','gl18','gl19'] },
    'tn-west':{ units: 200, pct: 35, photos: 2,  seeds: ['gl6','gl12'] },
  };

  /* ---------------------------------------------------------
     1. FADE-UP SCROLL OBSERVER
     --------------------------------------------------------- */
  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
    );
    els.forEach((el) => observer.observe(el));
  }

  /* ---------------------------------------------------------
     2. HERO ENTRANCE
     --------------------------------------------------------- */
  function initHeroEntrance() {
    const elements = [
      document.querySelector('.gl-hero-eyebrow'),
      document.querySelector('.gl-hero-title'),
      document.querySelector('.gl-hero-sub'),
      document.querySelector('.gl-hero-kpi-strip'),
    ].filter(Boolean);

    elements.forEach((el) => {
      el.style.opacity   = '0';
      el.style.transform = 'translateY(28px)';
    });

    requestAnimationFrame(() => {
      elements.forEach((el, i) => {
        setTimeout(() => {
          el.style.transition = 'opacity 0.65s ease, transform 0.65s ease';
          el.style.opacity    = '1';
          el.style.transform  = 'translateY(0)';
        }, 150 + i * 120);
      });
    });
  }

  /* ---------------------------------------------------------
     3. HIGHLIGHTS CAROUSEL
     --------------------------------------------------------- */
  function initHighlights() {
    const track    = document.getElementById('hlTrack');
    const dotsWrap = document.getElementById('hlDots');
    const btnPrev  = document.getElementById('hlPrev');
    const btnNext  = document.getElementById('hlNext');
    if (!track || !dotsWrap || !btnPrev || !btnNext) return;

    const slides = track.querySelectorAll('.gl-hl-slide');
    const total  = slides.length;
    let   current = 0;
    let   autoTimer = null;

    /* Build dot indicators */
    slides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.className   = 'gl-hl-dot' + (i === 0 ? ' is-active' : '');
      dot.setAttribute('role', 'tab');
      dot.setAttribute('aria-label', `Go to highlight ${i + 1}`);
      dot.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
      dot.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(dot);
    });

    function updateDots() {
      const dots = dotsWrap.querySelectorAll('.gl-hl-dot');
      dots.forEach((d, i) => {
        d.classList.toggle('is-active', i === current);
        d.setAttribute('aria-selected', i === current ? 'true' : 'false');
      });
    }

    function updateButtons() {
      btnPrev.disabled = current === 0;
      btnNext.disabled = current === total - 1;
    }

    function goTo(index) {
      current = Math.max(0, Math.min(total - 1, index));
      track.style.transform = `translateX(calc(-${current * 100}% - ${current * 1.5}rem))`;
      updateDots();
      updateButtons();
    }

    function startAuto() {
      stopAuto();
      autoTimer = setInterval(() => {
        goTo(current < total - 1 ? current + 1 : 0);
      }, 5500);
    }

    function stopAuto() {
      if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    }

    btnPrev.addEventListener('click', () => { stopAuto(); goTo(current - 1); startAuto(); });
    btnNext.addEventListener('click', () => { stopAuto(); goTo(current + 1); startAuto(); });

    /* Pause on hover */
    track.closest('.gl-hl-nav').addEventListener('mouseenter', stopAuto);
    track.closest('.gl-hl-nav').addEventListener('mouseleave', startAuto);

    /* Touch / swipe */
    let touchStartX = 0;
    track.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', (e) => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) { stopAuto(); goTo(current + (diff > 0 ? 1 : -1)); startAuto(); }
    }, { passive: true });

    goTo(0);
    startAuto();
  }

  /* ---------------------------------------------------------
     4. PHOTO GRID — dual filter (category + year)
     --------------------------------------------------------- */
  function initPhotoGrid() {
    const grid       = document.getElementById('photoGrid');
    const countEl    = document.getElementById('filterCount');
    const noResults  = document.getElementById('noResults');
    const resetBtn   = document.getElementById('resetFilters');
    if (!grid) return;

    let activeCat  = 'all';
    let activeYear = 'all';

    const items    = Array.from(grid.querySelectorAll('.gl-photo-item'));
    const chipSets = document.querySelectorAll('.gl-chip');

    function applyFilters(animate) {
      let visible = 0;

      items.forEach((item) => {
        const catMatch  = activeCat  === 'all' || item.dataset.cat  === activeCat;
        const yearMatch = activeYear === 'all' || item.dataset.year === activeYear;
        const show = catMatch && yearMatch;

        if (show) {
          item.classList.remove('is-hidden');
          if (animate) {
            item.classList.remove('is-entering');
            void item.offsetWidth;
            item.classList.add('is-entering');
          }
          visible++;
        } else {
          item.classList.add('is-hidden');
          item.classList.remove('is-entering');
        }
      });

      if (countEl) countEl.innerHTML = `Showing <strong>${visible}</strong> photo${visible !== 1 ? 's' : ''}`;
      if (noResults) noResults.hidden = visible > 0;
    }

    chipSets.forEach((chip) => {
      chip.addEventListener('click', () => {
        const filter = chip.dataset.filter;
        const val    = chip.dataset.val;

        /* Update active chip in group */
        chip.closest('.gl-filter-chips').querySelectorAll('.gl-chip').forEach((c) => c.classList.remove('is-active'));
        chip.classList.add('is-active');

        if (filter === 'cat')  activeCat  = val;
        if (filter === 'year') activeYear = val;

        applyFilters(true);
      });
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        activeCat  = 'all';
        activeYear = 'all';
        chipSets.forEach((c) => {
          c.classList.toggle('is-active', c.dataset.val === 'all');
        });
        applyFilters(true);
      });
    }

    /* Initial count */
    applyFilters(false);
  }

  /* ---------------------------------------------------------
     5. LIGHTBOX
     --------------------------------------------------------- */
  function initLightbox() {
    const lb       = document.getElementById('glLightbox');
    const backdrop = document.getElementById('glLbBackdrop');
    const closeBtn = document.getElementById('glLbClose');
    const prevBtn  = document.getElementById('glLbPrev');
    const nextBtn  = document.getElementById('glLbNext');
    const lbImg    = document.getElementById('glLbImg');
    const lbBadge  = document.getElementById('glLbBadge');
    const lbCap    = document.getElementById('glLbCaption');
    const lbDate   = document.getElementById('glLbDate');
    const lbCount  = document.getElementById('glLbCounter');
    if (!lb) return;

    const grid    = document.getElementById('photoGrid');
    let   visibleItems = [];
    let   currentIndex = 0;

    function getVisible() {
      return Array.from(grid.querySelectorAll('.gl-photo-item:not(.is-hidden)'));
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

    function loadSlide(i) {
      const item  = visibleItems[i];
      if (!item) return;

      const img   = item.querySelector('img');
      const badge = item.querySelector('.gl-photo-badge');
      const date  = item.querySelector('.gl-photo-date');

      lbImg.classList.add('is-loading');
      lbImg.src  = img ? img.src.replace('/600/420', '/1200/840') : '';
      lbImg.alt  = img ? img.alt : '';
      lbImg.onload = () => lbImg.classList.remove('is-loading');

      lbBadge.textContent = badge ? badge.textContent : '';
      lbBadge.className   = 'gl-lb-badge';
      if (badge) lbBadge.className += ' ' + badge.className.replace('gl-photo-badge', '').trim();
      lbCap.textContent   = img ? img.alt : '';
      lbDate.textContent  = date ? date.textContent : '';
      lbCount.textContent = `${i + 1} / ${visibleItems.length}`;

      prevBtn.disabled = i === 0;
      nextBtn.disabled = i === visibleItems.length - 1;
    }

    function prev() { if (currentIndex > 0) { currentIndex--; loadSlide(currentIndex); } }
    function next() { if (currentIndex < visibleItems.length - 1) { currentIndex++; loadSlide(currentIndex); } }

    /* Open on photo click */
    document.getElementById('photoGrid').addEventListener('click', (e) => {
      const item = e.target.closest('.gl-photo-item');
      if (!item) return;
      visibleItems = getVisible();
      const index = visibleItems.indexOf(item);
      if (index >= 0) openAt(index);
    });

    /* Also open from site mini grid */
    document.addEventListener('click', (e) => {
      const mini = e.target.closest('.gl-site-mini-item');
      if (!mini) return;
      const seed = mini.dataset.seed;
      if (!seed) return;
      /* Find corresponding main grid item */
      visibleItems = Array.from(grid.querySelectorAll('.gl-photo-item'));
      const match = visibleItems.find(it => {
        const img = it.querySelector('img');
        return img && img.src.includes(seed);
      });
      if (match) {
        visibleItems = getVisible();
        const idx = visibleItems.indexOf(match);
        if (idx >= 0) openAt(idx);
        else {
          /* Reset filters and open */
          document.querySelectorAll('.gl-chip[data-val="all"]').forEach(c => c.click());
          setTimeout(() => {
            visibleItems = getVisible();
            const idx2 = visibleItems.findIndex(it => {
              const img = it.querySelector('img');
              return img && img.src.includes(seed);
            });
            if (idx2 >= 0) openAt(idx2);
          }, 50);
        }
      }
    });

    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);
    prevBtn.addEventListener('click', prev);
    nextBtn.addEventListener('click', next);

    document.addEventListener('keydown', (e) => {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape')      close();
      if (e.key === 'ArrowLeft')   prev();
      if (e.key === 'ArrowRight')  next();
    });
  }

  /* ---------------------------------------------------------
     6. SITE TABS
     --------------------------------------------------------- */
  function initSiteTabs() {
    const tabs       = document.querySelectorAll('.gl-site-tab');
    const miniGrid   = document.getElementById('siteMiniGrid');
    const stat1      = document.getElementById('siteStat1');
    const stat2      = document.getElementById('siteStat2');
    const stat3      = document.getElementById('siteStat3');
    const fill       = document.getElementById('siteProgressFill');
    if (!tabs.length || !miniGrid) return;

    function activateSite(siteKey) {
      const d = SITE_DATA[siteKey];
      if (!d) return;

      /* Update tabs */
      tabs.forEach((t) => {
        t.classList.toggle('is-active', t.dataset.site === siteKey);
        t.setAttribute('aria-selected', t.dataset.site === siteKey ? 'true' : 'false');
      });

      /* Update stats */
      if (stat1) stat1.textContent = d.units;
      if (stat2) stat2.textContent = d.pct + '%';
      if (stat3) stat3.textContent = d.photos;
      if (fill)  fill.style.width  = d.pct + '%';

      /* Render mini grid */
      miniGrid.innerHTML = d.seeds.map(seed => `
        <button class="gl-site-mini-item" data-seed="${seed}" aria-label="View photo from ${siteKey}">
          <img src="https://picsum.photos/seed/${seed}/240/240" alt="Site photo" loading="lazy">
        </button>
      `).join('');
    }

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => activateSite(tab.dataset.site));
    });

    /* Init with first tab */
    activateSite('kiminini');
  }

  /* ---------------------------------------------------------
     7. VIDEO CARDS — play button interaction
     --------------------------------------------------------- */
  function initVideoCards() {
    const cards = document.querySelectorAll('.gl-video-card');
    cards.forEach((card) => {
      const playBtn = card.querySelector('.gl-video-play-btn');
      if (!playBtn) return;
      playBtn.addEventListener('click', () => {
        /* Placeholder: show a toast or alert since no real video URL */
        const title = card.querySelector('.gl-video-title');
        if (title) {
          const msg = document.createElement('div');
          msg.style.cssText = [
            'position:fixed', 'bottom:2rem', 'left:50%',
            'transform:translateX(-50%)',
            'background:var(--primary-600,#1e4d00)',
            'color:var(--lime,#9fe870)',
            'padding:0.75rem 1.5rem',
            'border-radius:100px',
            'font-size:0.85rem',
            'font-weight:600',
            'z-index:9999',
            'box-shadow:0 8px 24px rgba(0,0,0,0.25)',
            'pointer-events:none',
            'opacity:0',
            'transition:opacity 0.3s'
          ].join(';');
          msg.textContent = 'Video coming soon: ' + title.textContent;
          document.body.appendChild(msg);
          requestAnimationFrame(() => { msg.style.opacity = '1'; });
          setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 350);
          }, 3000);
        }
      });
    });
  }

  /* ---------------------------------------------------------
     8. BACK TO TOP
     --------------------------------------------------------- */
  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => {
      btn.classList.toggle('is-visible', window.scrollY > 500);
    }, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ---------------------------------------------------------
     INIT
     --------------------------------------------------------- */
  function init() {
    initHeroEntrance();
    initFadeUp();
    initHighlights();
    initPhotoGrid();
    initLightbox();
    initSiteTabs();
    initVideoCards();
    initBackToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
