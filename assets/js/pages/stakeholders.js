/* =========================================================
   TRANS-NZOIA AHP TRACKER — Stakeholders Page JS
   ========================================================= */

(function () {
  'use strict';

  /* ---------------------------------------------------------
     ECOSYSTEM MAP DATA
     --------------------------------------------------------- */
  const MAP_DATA = {
    national: {
      name: 'National Government',
      sub: 'Policy · Funding · National Mandate',
      icon: 'fa-building-columns',
      color: '#1e4d00',
      light: '#edfbd6',
      desc: 'The apex leadership setting policy, allocating funds and providing the legal mandate for the Affordable Housing Programme across all 47 counties.',
      entities: [
        { label: 'H.E. The President of Kenya', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Cabinet Secretary — Housing', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Principal Secretary — Housing', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Affordable Housing Board (AHB)', borderColor: '#1e4d00', bg: '#edfbd6' },
      ]
    },
    county: {
      name: 'County Government',
      sub: 'Land · Facilitation · Local Governance',
      icon: 'fa-map-location-dot',
      color: '#276600',
      light: '#edfbd6',
      desc: 'Trans-Nzoia County Government facilitates programme delivery by providing public land, local permits and community engagement through ward administrators.',
      entities: [
        { label: 'H.E. Governor — Trans-Nzoia', borderColor: '#276600', bg: '#edfbd6' },
        { label: 'CEC Member — Lands & Housing', borderColor: '#276600', bg: '#edfbd6' },
        { label: 'AHB Field Office — Dir. Moses Owuor', borderColor: '#276600', bg: '#edfbd6' },
      ]
    },
    contractors: {
      name: 'Implementing Contractors',
      sub: 'Construction · NCA Registration · Site Delivery',
      icon: 'fa-hard-hat',
      color: '#1e4d00',
      light: '#edfbd6',
      desc: '8 NCA-registered construction firms awarded tenders to deliver housing units across the five Trans-Nzoia constituencies. All firms comply with OSHA 2007.',
      entities: [
        { label: 'Kiunga Civil Engineering Ltd', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Baraka Construction & Supplies', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Nile Basin Builders Ltd', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Savanna Homes Ltd', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: '+ 4 more active firms', borderColor: '#1e4d00', bg: '#edfbd6' },
      ]
    },
    financial: {
      name: 'Financial Partners',
      sub: 'Housing Levy · Mortgages · Beneficiary Payments',
      icon: 'fa-landmark',
      color: '#276600',
      light: '#edfbd6',
      desc: 'Government and private institutions mobilising the 1.5% Housing Levy, mortgage capital through KMRC, and beneficiary registration through Boma Yangu.',
      entities: [
        { label: 'State Dept. Housing (Treasury)', borderColor: '#276600', bg: '#edfbd6' },
        { label: 'Kenya Mortgage Refinance Co. (KMRC)', borderColor: '#276600', bg: '#edfbd6' },
        { label: 'Boma Yangu — Beneficiary Portal', borderColor: '#276600', bg: '#edfbd6' },
        { label: 'KCB Bank Kenya Ltd', borderColor: '#276600', bg: '#edfbd6' },
      ]
    },
    oversight: {
      name: 'Oversight & Regulation',
      sub: 'Environment · Quality · Audit · Statistics',
      icon: 'fa-scale-balanced',
      color: '#3d8c00',
      light: '#edfbd6',
      desc: 'Four independent statutory bodies ensuring environmental compliance, construction quality standards, financial accountability and transparent impact data.',
      entities: [
        { label: 'NEMA — Environmental Oversight', borderColor: '#3d8c00', bg: '#edfbd6' },
        { label: 'NCA — Construction Quality', borderColor: '#3d8c00', bg: '#edfbd6' },
        { label: 'KNBS — Impact Statistics', borderColor: '#3d8c00', bg: '#edfbd6' },
        { label: 'Office of the Auditor General', borderColor: '#3d8c00', bg: '#edfbd6' },
      ]
    },
    community: {
      name: 'Community & Beneficiaries',
      sub: 'Residents · Ward Reps · Women Groups · PWDs',
      icon: 'fa-people-group',
      color: '#1e4d00',
      light: '#edfbd6',
      desc: 'Over 5,000 target households — including registered Boma Yangu applicants, ward development committees, women housing networks and PWD groups.',
      entities: [
        { label: 'Registered Boma Yangu Applicants', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Ward Development Committees', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Trans-Nzoia Women Housing Network', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'Youth Cohort Beneficiaries', borderColor: '#1e4d00', bg: '#edfbd6' },
        { label: 'PWD Priority Housing Applicants', borderColor: '#1e4d00', bg: '#edfbd6' },
      ]
    }
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
      { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
    );
    els.forEach((el) => observer.observe(el));
  }

  /* ---------------------------------------------------------
     2. HERO ENTRANCE
     --------------------------------------------------------- */
  function initHeroEntrance() {
    const elements = [
      document.querySelector('.sk-hero-eyebrow'),
      document.querySelector('.sk-hero-title'),
      document.querySelector('.sk-hero-sub'),
      document.querySelector('.sk-hero-kpi-strip'),
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
        }, 150 + i * 110);
      });
    });
  }

  /* ---------------------------------------------------------
     3. ECOSYSTEM MAP — node clicks + detail panel
     --------------------------------------------------------- */
  function initEcosystemMap() {
    const map        = document.getElementById('stakeholderMap');
    const detailWrap = document.getElementById('mapDetailInner');
    if (!map || !detailWrap) return;

    const nodes = map.querySelectorAll('.sk-map-node');
    const lines = map.querySelectorAll('.sk-conn-line');
    let active  = null;

    function renderDetail(catKey) {
      const d = MAP_DATA[catKey];
      if (!d) return;

      const entitiesHTML = d.entities
        .map(e => `<li style="color:${e.borderColor};background:${e.bg};border-color:${e.borderColor}">${e.label}</li>`)
        .join('');

      detailWrap.classList.add('is-updating');
      setTimeout(() => detailWrap.classList.remove('is-updating'), 350);

      detailWrap.innerHTML = `
        <div class="sk-map-detail-content">
          <div class="sk-map-detail-icon" style="background:${d.light};color:${d.color}">
            <i class="fa-solid ${d.icon}"></i>
          </div>
          <div class="sk-map-detail-body">
            <p class="sk-map-detail-name">${d.name}</p>
            <p class="sk-map-detail-sub" style="color:${d.color}">${d.sub}</p>
            <p class="sk-map-detail-desc">${d.desc}</p>
            <ul class="sk-map-detail-entities">${entitiesHTML}</ul>
          </div>
        </div>
      `;
    }

    function activateNode(catKey) {
      active = active === catKey ? null : catKey;

      /* Update node states */
      nodes.forEach((n) => n.classList.toggle('is-active', n.dataset.cat === active));

      /* Update line states */
      lines.forEach((l) => {
        if (!active) {
          l.classList.remove('is-active', 'is-dimmed');
        } else if (l.dataset.cat === active) {
          l.classList.add('is-active');
          l.classList.remove('is-dimmed');
        } else {
          l.classList.add('is-dimmed');
          l.classList.remove('is-active');
        }
      });

      /* Render detail panel */
      if (active) {
        renderDetail(active);
        /* Scroll detail panel into view on narrow screens where it's below the map */
        const detail = document.getElementById('mapDetail');
        if (detail && window.innerWidth < 900) {
          setTimeout(() => detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 60);
        }
      } else {
        detailWrap.innerHTML = `
          <div class="sk-map-detail-placeholder">
            <i class="fa-solid fa-hand-pointer"></i>
            <p>Click any stakeholder category above to explore its role in the programme.</p>
          </div>
        `;
      }
    }

    nodes.forEach((node) => {
      node.addEventListener('click', () => activateNode(node.dataset.cat));
      node.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activateNode(node.dataset.cat);
        }
      });
    });
  }

  /* ---------------------------------------------------------
     4. CATEGORIES GRID — flip cards
     --------------------------------------------------------- */
  function initCategoryCards() {
    const cards = document.querySelectorAll('.sk-cat-card');
    cards.forEach((card) => {
      const flipBtn     = card.querySelector('.sk-cat-flip-btn');
      const flipBackBtn = card.querySelector('.sk-cat-flip-back-btn');

      flipBtn && flipBtn.addEventListener('click', () => {
        card.classList.add('is-flipped');
      });
      flipBackBtn && flipBackBtn.addEventListener('click', () => {
        card.classList.remove('is-flipped');
      });
    });
  }

  /* ---------------------------------------------------------
     5. ACCORDION — animated height transitions
     --------------------------------------------------------- */
  function initAccordion() {
    const triggers = document.querySelectorAll('.sk-acc-trigger');
    if (!triggers.length) return;

    triggers.forEach((trigger) => {
      const panelId = trigger.getAttribute('aria-controls');
      const panel   = document.getElementById(panelId);
      if (!panel) return;

      /* Initialise: set height to 0 on hidden panels */
      if (trigger.getAttribute('aria-expanded') === 'false') {
        panel.style.height = '0';
        panel.style.overflow = 'hidden';
      }

      trigger.addEventListener('click', () => {
        const isOpen = trigger.getAttribute('aria-expanded') === 'true';

        /* Close all others */
        triggers.forEach((t) => {
          if (t !== trigger) {
            const p = document.getElementById(t.getAttribute('aria-controls'));
            if (!p) return;
            t.setAttribute('aria-expanded', 'false');
            p.style.height = '0';
            p.setAttribute('hidden', '');
          }
        });

        /* Toggle this one */
        if (isOpen) {
          panel.style.height = panel.scrollHeight + 'px';
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              panel.style.height = '0';
            });
          });
          trigger.setAttribute('aria-expanded', 'false');
          setTimeout(() => panel.setAttribute('hidden', ''), 350);
        } else {
          panel.removeAttribute('hidden');
          panel.style.height = '0';
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              panel.style.height = panel.scrollHeight + 'px';
            });
          });
          trigger.setAttribute('aria-expanded', 'true');
          setTimeout(() => { panel.style.height = 'auto'; }, 380);
        }
      });
    });
  }

  /* ---------------------------------------------------------
     6. TIMELINE — category filter buttons (optional filter bar)
        Plus entrance animation for timeline items
     --------------------------------------------------------- */
  function initTimeline() {
    const items = document.querySelectorAll('.sk-tl-item');
    if (!items.length) return;

    items.forEach((item, i) => {
      item.style.opacity   = '0';
      item.style.transform = 'translateY(16px)';
    });

    const wrap = document.querySelector('.sk-timeline-scroll-wrap');
    if (!wrap) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            items.forEach((item, i) => {
              setTimeout(() => {
                item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                item.style.opacity   = '1';
                item.style.transform = 'translateY(0)';
              }, i * 70);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.05 }
    );

    observer.observe(wrap);
  }

  /* ---------------------------------------------------------
     7. VOICE CARDS — stagger reveal
     --------------------------------------------------------- */
  function initVoiceCards() {
    const grid = document.getElementById('voicesGrid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.sk-voice-card');
    cards.forEach((card) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(20px)';
    });

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            cards.forEach((card, i) => {
              setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease, box-shadow 0.25s, border-color 0.25s';
                card.style.opacity   = '1';
                card.style.transform = 'translateY(0)';
              }, i * 90);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08 }
    );

    observer.observe(grid);
  }

  /* ---------------------------------------------------------
     8. PARTNER TILES — stagger reveal
     --------------------------------------------------------- */
  function initPartnerTiles() {
    const row = document.querySelector('.sk-partners-row');
    if (!row) return;

    const tiles = row.querySelectorAll('.sk-partner-tile');
    tiles.forEach((tile) => {
      tile.style.opacity   = '0';
      tile.style.transform = 'translateY(14px)';
    });

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            tiles.forEach((tile, i) => {
              setTimeout(() => {
                tile.style.transition = 'opacity 0.4s ease, transform 0.4s ease, background 0.25s, border-color 0.25s';
                tile.style.opacity   = '1';
                tile.style.transform = 'translateY(0)';
              }, i * 55);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.05 }
    );

    observer.observe(row);
  }

  /* ---------------------------------------------------------
     9. ENGAGE CARDS — stagger reveal
     --------------------------------------------------------- */
  function initEngageCards() {
    const grid = document.querySelector('.sk-engage-grid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.sk-engage-card');
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
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease, box-shadow 0.25s';
                card.style.opacity   = '1';
                card.style.transform = 'translateY(0)';
              }, i * 80);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.06 }
    );

    observer.observe(grid);
  }

  /* ---------------------------------------------------------
     10. TIMELINE ARROWS — prev/next with eased scroll
     --------------------------------------------------------- */
  function initTimelineArrows() {
    const wrap = document.querySelector('.sk-timeline-scroll-wrap');
    const prev = document.getElementById('tlPrev');
    const next = document.getElementById('tlNext');
    if (!wrap || !prev || !next) return;

    const STEP = 460;

    function updateButtons() {
      prev.disabled = wrap.scrollLeft <= 0;
      next.disabled = wrap.scrollLeft >= wrap.scrollWidth - wrap.clientWidth - 2;
    }

    function easeInOutCubic(t) {
      return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function smoothScroll(target) {
      const start    = wrap.scrollLeft;
      const distance = target - start;
      const duration = 560;
      let   startTime = null;

      function step(ts) {
        if (!startTime) startTime = ts;
        const elapsed  = ts - startTime;
        const progress = Math.min(elapsed / duration, 1);
        wrap.scrollLeft = start + distance * easeInOutCubic(progress);
        if (progress < 1) requestAnimationFrame(step);
        else updateButtons();
      }
      requestAnimationFrame(step);
    }

    prev.addEventListener('click', () => {
      smoothScroll(Math.max(0, wrap.scrollLeft - STEP));
    });
    next.addEventListener('click', () => {
      smoothScroll(Math.min(wrap.scrollWidth - wrap.clientWidth, wrap.scrollLeft + STEP));
    });

    wrap.addEventListener('scroll', updateButtons, { passive: true });
    updateButtons();
  }

  /* ---------------------------------------------------------
     11. BACK TO TOP
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
     INIT
     --------------------------------------------------------- */
  function init() {
    initHeroEntrance();
    initFadeUp();
    initEcosystemMap();
    initCategoryCards();
    initAccordion();
    initTimeline();
    initVoiceCards();
    initPartnerTiles();
    initEngageCards();
    initTimelineArrows();
    initBackToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
