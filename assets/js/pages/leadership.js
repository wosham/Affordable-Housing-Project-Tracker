/* =========================================================
   TRANS-NZOIA AHP TRACKER — Leadership Page JS
   ========================================================= */

(function () {
  'use strict';

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
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    els.forEach((el) => observer.observe(el));
  }

  /* ---------------------------------------------------------
     2. ORG CHART — cascade reveal + click popovers
     --------------------------------------------------------- */
  function initOrgChart() {
    const chart = document.getElementById('orgChart');
    if (!chart) return;

    /* Cascade reveal on scroll */
    const chartObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            chartObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08 }
    );
    chartObserver.observe(chart);

    /* Popover toggle on click / Enter / Space */
    const nodes = chart.querySelectorAll('.ld-org-node[data-popover]');

    function closeAll(except) {
      nodes.forEach((n) => {
        if (n !== except) n.classList.remove('popover-open');
      });
    }

    nodes.forEach((node) => {
      node.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = node.classList.contains('popover-open');
        closeAll(null);
        if (!isOpen) node.classList.add('popover-open');
      });

      node.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const isOpen = node.classList.contains('popover-open');
          closeAll(null);
          if (!isOpen) node.classList.add('popover-open');
        }
        if (e.key === 'Escape') {
          node.classList.remove('popover-open');
        }
      });
    });

    /* Close popovers when clicking outside */
    document.addEventListener('click', () => closeAll(null));
  }

  /* ---------------------------------------------------------
     3. NATIONAL LEADERSHIP CARDS CAROUSEL
     --------------------------------------------------------- */
  function initNationalCarousel() {
    const track    = document.getElementById('nationalTrack');
    const dotsWrap = document.getElementById('natDots');
    const prevBtn  = document.getElementById('natPrev');
    const nextBtn  = document.getElementById('natNext');

    if (!track || !dotsWrap) return;

    const cards        = Array.from(track.querySelectorAll('.ld-national-card'));
    const totalCards   = cards.length;
    let current        = 0;
    let visibleCount   = getVisibleCount();
    let maxIndex       = Math.max(0, totalCards - visibleCount);

    function getVisibleCount() {
      if (window.innerWidth >= 1100) return 3;
      if (window.innerWidth >= 768)  return 2;
      return 1;
    }

    function getCardWidthPct() {
      if (window.innerWidth >= 1100) return 33.333;
      if (window.innerWidth >= 768)  return 50;
      return 100;
    }

    /* Build dots */
    function buildDots() {
      dotsWrap.innerHTML = '';
      const dotCount = maxIndex + 1;
      for (let i = 0; i <= maxIndex; i++) {
        const btn = document.createElement('button');
        btn.className = 'ld-nat-dot' + (i === current ? ' is-active' : '');
        btn.setAttribute('role', 'tab');
        btn.setAttribute('aria-label', `Leadership card group ${i + 1} of ${dotCount}`);
        btn.addEventListener('click', () => goTo(i));
        dotsWrap.appendChild(btn);
      }
    }

    function updateDots() {
      const dots = dotsWrap.querySelectorAll('.ld-nat-dot');
      dots.forEach((d, i) => d.classList.toggle('is-active', i === current));
    }

    function goTo(index) {
      current = Math.max(0, Math.min(index, maxIndex));
      const pct = current * getCardWidthPct();
      track.style.transform = `translateX(-${pct}%)`;
      updateDots();
    }

    prevBtn && prevBtn.addEventListener('click', () => goTo(current - 1));
    nextBtn && nextBtn.addEventListener('click', () => goTo(current + 1));

    /* Keyboard navigation */
    [prevBtn, nextBtn].forEach((btn) => {
      btn && btn.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') goTo(current - 1);
        if (e.key === 'ArrowRight') goTo(current + 1);
      });
    });

    /* Touch swipe */
    let touchStartX = 0;
    track.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', (e) => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) diff > 0 ? goTo(current + 1) : goTo(current - 1);
    });

    /* Recalculate on resize */
    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        visibleCount = getVisibleCount();
        maxIndex     = Math.max(0, totalCards - visibleCount);
        current      = Math.min(current, maxIndex);
        buildDots();
        goTo(current);
      }, 150);
    });

    buildDots();
    goTo(0);
  }

  /* ---------------------------------------------------------
     4. QUOTE CAROUSEL — auto-rotating
     --------------------------------------------------------- */
  function initQuoteCarousel() {
    const track    = document.getElementById('quotesTrack');
    const dotsWrap = document.getElementById('quotesDots');
    const prevBtn  = document.getElementById('quotesPrev');
    const nextBtn  = document.getElementById('quotesNext');
    const carousel = document.getElementById('quotesCarousel');

    if (!track || !dotsWrap) return;

    const slides     = Array.from(track.querySelectorAll('.ld-quote-slide'));
    const total      = slides.length;
    let current      = 0;
    let autoTimer    = null;
    const AUTO_DELAY = 6000;

    /* Build dots */
    function buildDots() {
      dotsWrap.innerHTML = '';
      slides.forEach((_, i) => {
        const btn = document.createElement('button');
        btn.className = 'ld-quote-dot' + (i === 0 ? ' is-active' : '');
        btn.setAttribute('role', 'tab');
        btn.setAttribute('aria-label', `Quote ${i + 1} of ${total}`);
        btn.addEventListener('click', () => { goTo(i); resetAuto(); });
        dotsWrap.appendChild(btn);
      });
    }

    function updateDots() {
      const dots = dotsWrap.querySelectorAll('.ld-quote-dot');
      dots.forEach((d, i) => d.classList.toggle('is-active', i === current));
    }

    function goTo(index) {
      current = ((index % total) + total) % total;
      track.style.transform = `translateX(-${current * 100}%)`;
      updateDots();

      /* Update aria live */
      slides.forEach((s, i) => {
        s.setAttribute('aria-hidden', i !== current ? 'true' : 'false');
      });
    }

    function startAuto() {
      autoTimer = setInterval(() => goTo(current + 1), AUTO_DELAY);
    }

    function resetAuto() {
      clearInterval(autoTimer);
      startAuto();
    }

    prevBtn && prevBtn.addEventListener('click', () => { goTo(current - 1); resetAuto(); });
    nextBtn && nextBtn.addEventListener('click', () => { goTo(current + 1); resetAuto(); });

    /* Keyboard */
    [prevBtn, nextBtn].forEach((btn) => {
      btn && btn.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') { goTo(current - 1); resetAuto(); }
        if (e.key === 'ArrowRight') { goTo(current + 1); resetAuto(); }
      });
    });

    /* Touch swipe */
    let touchStartX = 0;
    track.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', (e) => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) {
        diff > 0 ? goTo(current + 1) : goTo(current - 1);
        resetAuto();
      }
    });

    /* Pause on hover/focus */
    if (carousel) {
      carousel.addEventListener('mouseenter', () => clearInterval(autoTimer));
      carousel.addEventListener('mouseleave', () => startAuto());
      carousel.addEventListener('focusin',    () => clearInterval(autoTimer));
      carousel.addEventListener('focusout',   () => startAuto());
    }

    buildDots();
    goTo(0);
    startAuto();
  }

  /* ---------------------------------------------------------
     5. CONTRACTOR PROGRESS BAR ANIMATION
     --------------------------------------------------------- */
  function initProgressBars() {
    const fills = document.querySelectorAll('.ld-contractor-prog-fill');
    if (!fills.length) return;

    /* Initially set width to 0 so animation can play in */
    fills.forEach((fill) => {
      const target = fill.style.getPropertyValue('--prog') || '0%';
      fill.dataset.target = target;
      fill.style.setProperty('--prog', '0%');
    });

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const fill = entry.target;
            setTimeout(() => {
              fill.style.setProperty('--prog', fill.dataset.target);
            }, 200);
            observer.unobserve(fill);
          }
        });
      },
      { threshold: 0.3 }
    );

    fills.forEach((fill) => observer.observe(fill));
  }

  /* ---------------------------------------------------------
     6. ORG CHART INFO BTN — keyboard focus opens popover
     --------------------------------------------------------- */
  function initInfoBtns() {
    const infoBtns = document.querySelectorAll('.ld-org-info-btn');
    infoBtns.forEach((btn) => {
      btn.setAttribute('tabindex', '0');
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const node = btn.closest('.ld-org-node');
        if (!node) return;
        const isOpen = node.classList.contains('popover-open');
        document.querySelectorAll('.ld-org-node').forEach((n) => n.classList.remove('popover-open'));
        if (!isOpen) node.classList.add('popover-open');
      });
    });
  }

  /* ---------------------------------------------------------
     7. SPOTLIGHT AVATAR — entrance animation
     --------------------------------------------------------- */
  function initSpotlight() {
    const spotlight = document.querySelector('.ld-spotlight');
    if (!spotlight) return;

    const avatar = spotlight.querySelector('.ld-spotlight-avatar');
    const content = spotlight.querySelector('.ld-spotlight-content');
    const visual = spotlight.querySelector('.ld-spotlight-visual');

    if (avatar) { avatar.style.opacity = '0'; avatar.style.transform = 'scale(0.85)'; }
    if (visual)  { visual.style.opacity = '0'; visual.style.transform = 'translateX(-24px)'; }
    if (content) { content.style.opacity = '0'; content.style.transform = 'translateX(24px)'; }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            if (visual) {
              setTimeout(() => {
                visual.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                visual.style.opacity = '1';
                visual.style.transform = 'translateX(0)';
              }, 100);
            }
            if (avatar) {
              setTimeout(() => {
                avatar.style.transition = 'opacity 0.5s ease, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
                avatar.style.opacity = '1';
                avatar.style.transform = 'scale(1)';
              }, 200);
            }
            if (content) {
              setTimeout(() => {
                content.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                content.style.opacity = '1';
                content.style.transform = 'translateX(0)';
              }, 300);
            }
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );

    observer.observe(spotlight);
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

    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------------------------------------------------------
     9. HERO ENTRANCE
     --------------------------------------------------------- */
  function initHeroEntrance() {
    const body    = document.querySelector('.ld-hero-body');
    const pills   = document.querySelector('.ld-hero-pills');
    const eyebrow = document.querySelector('.ld-hero-eyebrow');
    const title   = document.querySelector('.ld-hero-title');
    const sub     = document.querySelector('.ld-hero-sub');

    const elements = [eyebrow, title, sub, pills].filter(Boolean);

    elements.forEach((el) => {
      el.style.opacity    = '0';
      el.style.transform  = 'translateY(28px)';
    });

    requestAnimationFrame(() => {
      elements.forEach((el, i) => {
        setTimeout(() => {
          el.style.transition = 'opacity 0.65s ease, transform 0.65s ease';
          el.style.opacity    = '1';
          el.style.transform  = 'translateY(0)';
        }, 150 + i * 110);
      });
    });
  }

  /* ---------------------------------------------------------
     10. PARTNER CARDS — stagger on scroll
     --------------------------------------------------------- */
  function initPartnerStagger() {
    const cards = document.querySelectorAll('.ld-partner-card');
    if (!cards.length) return;

    cards.forEach((card, i) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(20px)';
    });

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const cards2 = entry.target.querySelectorAll('.ld-partner-card');
            cards2.forEach((card, i) => {
              setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity   = '1';
                card.style.transform = 'translateY(0)';
              }, i * 80);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1 }
    );

    const grid = document.querySelector('.ld-partners-grid');
    if (grid) observer.observe(grid);
  }

  /* ---------------------------------------------------------
     11. CONTRACTOR CARDS — stagger on scroll
     --------------------------------------------------------- */
  function initContractorStagger() {
    const grid = document.getElementById('contractorsGrid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.ld-contractor-card');
    cards.forEach((card) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(24px)';
    });

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            cards.forEach((card, i) => {
              setTimeout(() => {
                card.style.transition = 'opacity 0.45s ease, transform 0.45s ease, box-shadow var(--transition-base), border-color var(--transition-base)';
                card.style.opacity   = '1';
                card.style.transform = 'translateY(0)';
              }, i * 60);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.05 }
    );

    observer.observe(grid);
  }

  /* ---------------------------------------------------------
     INIT
     --------------------------------------------------------- */
  function init() {
    initHeroEntrance();
    initFadeUp();
    initOrgChart();
    initNationalCarousel();
    initQuoteCarousel();
    initProgressBars();
    initInfoBtns();
    initSpotlight();
    initBackToTop();
    initPartnerStagger();
    initContractorStagger();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
