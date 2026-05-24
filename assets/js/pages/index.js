/**
 * TRANS-NZOIA AHP TRACKER — Homepage JavaScript
 * Phase 2 Rebuild: Modern Project Tracker UI
 */

(function () {
  'use strict';

  /* ========================================================
     UTILITY: Animate a numeric counter with optional suffix
     ======================================================== */
  function runCounter(el, target, duration) {
    const suffix = el.getAttribute('data-suffix') || '';
    const start  = performance.now();
    function ease(t) { return 1 - Math.pow(1 - t, 3); }
    function frame(now) {
      const progress = Math.min((now - start) / duration, 1);
      const current  = Math.round(ease(progress) * target);
      el.textContent = (current >= 1000 ? current.toLocaleString() : current) + suffix;
      if (progress < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  /* ========================================================
     1. CUSTOM CURSOR
        — dot follows mouse instantly
        — ring lerps behind with a smooth lag
     ======================================================== */
  function initCursor() {
    const dot  = document.getElementById('cursorDot');
    const ring = document.getElementById('cursorRing');
    if (!dot || !ring) return;
    if (!window.matchMedia('(hover: hover)').matches) return;

    let mouseX = 0, mouseY = 0;
    let ringX  = 0, ringY  = 0;

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      dot.style.left = mouseX + 'px';
      dot.style.top  = mouseY + 'px';
    });

    (function lerpRing() {
      ringX += (mouseX - ringX) * 0.12;
      ringY += (mouseY - ringY) * 0.12;
      ring.style.left = ringX + 'px';
      ring.style.top  = ringY + 'px';
      requestAnimationFrame(lerpRing);
    })();

    const hoverSel = 'a, button, [role="button"], input, select, textarea, label, .map-constituency, .ptc, .news-card, .ecitizen-btn-main, .ecitizen-btn-ghost';
    document.addEventListener('mouseover', (e) => {
      if (e.target.closest(hoverSel)) document.body.classList.add('cursor-hover');
    });
    document.addEventListener('mouseout', (e) => {
      if (e.target.closest(hoverSel)) document.body.classList.remove('cursor-hover');
    });
  }

  /* ========================================================
     2. HERO ENTRANCE ANIMATION
        Staggers .tracker-hero-inner children + .hero-snapshot
     ======================================================== */
  function initHeroEntrance() {
    const inner = document.querySelector('.tracker-hero-inner');
    if (!inner) return;
    const items = Array.from(inner.children);
    items.forEach((el, i) => {
      el.style.opacity    = '0';
      el.style.transform  = 'translateY(28px)';
      el.style.transition = `opacity 0.72s ease ${i * 0.14}s, transform 0.72s ease ${i * 0.14}s`;
    });
    requestAnimationFrame(() => requestAnimationFrame(() => {
      items.forEach((el) => {
        el.style.opacity   = '1';
        el.style.transform = 'translateY(0)';
      });
    }));
  }

  /* ========================================================
     3. HERO BACKGROUND PARALLAX
     ======================================================== */
  function initHeroParallax() {
    const bg = document.querySelector('.tracker-hero-bg img');
    if (!bg) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          const rate = Math.min(window.scrollY * 0.12, 120);
          bg.style.transform = `translateY(${rate}px) scale(1.04)`;
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  /* ========================================================
     4. HERO KPI COUNTERS
     ======================================================== */
  function initKPICounters() {
    const els = document.querySelectorAll('.hero-kpi-number[data-count]');
    if (!els.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const target = parseInt(entry.target.getAttribute('data-count'), 10);
        if (!isNaN(target)) runCounter(entry.target, target, 1800);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.4 });
    els.forEach((el) => observer.observe(el));
  }

  /* ========================================================
     5. PROJECT CARD PROGRESS BARS
     ======================================================== */
  function initProgressBars() {
    const fills = document.querySelectorAll('.ptc-progress-fill[data-progress]');
    if (!fills.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const pct = parseFloat(entry.target.getAttribute('data-progress'));
        if (!isNaN(pct)) requestAnimationFrame(() => { entry.target.style.width = pct + '%'; });
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.3 });
    fills.forEach((f) => observer.observe(f));
  }

  /* ========================================================
     6. INTERACTIVE SVG MAP
        — click / keyboard Enter|Space on .map-constituency
        — renders a detail panel with progress bar animation
        — back button returns to overview panel
     ======================================================== */
  function initMap() {
    const constituencies = document.querySelectorAll('.map-constituency');
    const panelDefault   = document.getElementById('mapPanelDefault');
    const panelDetail    = document.getElementById('mapPanelDetail');
    if (!constituencies.length || !panelDefault || !panelDetail) return;

    let selected = null;

    function showDefault() {
      panelDetail.innerHTML = '';
      panelDetail.classList.remove('is-visible');
      panelDefault.style.display = '';
      if (selected) { selected.classList.remove('is-selected'); selected = null; }
    }

    const CONSTITUENCY_DATA = {
      saboti: {
        projects: [
          {
            name: 'Maili Tatu Affordable Housing Estate',
            ward: 'Matisi Ward',
            units: 1040,
            pct: 60,
            status: 'active',
            contractor: 'Jabavu Developers Ltd',
            funding: 'National AHP Fund',
            leadAgency: 'State Dept. of Housing',
            siteEngineer: 'Eng. S. K. Kariuki',
            startDate: 'Mar 2024',
            estDelivery: 'Q4 2026',
            milestone: 'Roofing works — Q3 2026',
          },
          {
            name: 'Kitale Town Infill Units',
            ward: 'Kitale Central',
            units: 80,
            pct: 55,
            status: 'active',
            contractor: 'BuildRight Construction Co.',
            funding: 'County Development Fund',
            leadAgency: 'Trans-Nzoia County Housing Dept.',
            siteEngineer: 'Eng. P. Wafula',
            startDate: 'Jun 2024',
            estDelivery: 'Q1 2027',
            milestone: 'Internal finishing',
          },
        ],
      },
      cherangany: {
        projects: [
          {
            name: 'Matunda AHP Estate',
            ward: 'Matunda / Sinyerere',
            units: 200,
            pct: 35,
            status: 'active',
            contractor: 'Afri-Build Kenya Ltd',
            funding: 'National AHP Fund',
            leadAgency: 'State Dept. of Housing',
            siteEngineer: 'Eng. J. M. Otieno',
            startDate: 'Jul 2024',
            estDelivery: 'Q2 2027',
            milestone: 'Ground-floor columns cast',
          },
        ],
      },
      endebess: {
        projects: [
          {
            name: 'Suam Border Post Estate',
            ward: 'Endebess Ward',
            units: 150,
            pct: 12,
            status: 'active',
            contractor: 'Frontier Housing Ltd',
            funding: 'National AHP Fund + NEMA',
            leadAgency: 'State Dept. of Housing',
            siteEngineer: 'Eng. A. Chesire',
            startDate: 'Jan 2025',
            estDelivery: 'Q3 2027',
            milestone: 'Site mobilisation in progress',
          },
          {
            name: 'Endebess Township Units',
            ward: 'Endebess Ward',
            units: 60,
            pct: 8,
            status: 'active',
            contractor: 'Trans-Nzoia Housing Corp.',
            funding: 'County Capital Budget',
            leadAgency: 'Trans-Nzoia County Housing Dept.',
            siteEngineer: 'Eng. B. K. Rotich',
            startDate: 'Mar 2025',
            estDelivery: 'Q4 2027',
            milestone: 'Foundation works',
          },
        ],
      },
      kiminini: {
        projects: [
          {
            name: 'Kiminini AHP Phase 1',
            ward: 'Kiminini Ward',
            units: 80,
            pct: 5,
            status: 'planning',
            contractor: 'TBD — Tender in Preparation',
            funding: 'National AHP Fund',
            leadAgency: 'State Dept. of Housing',
            siteEngineer: 'TBD',
            startDate: 'TBD — Q3 2026 target',
            estDelivery: 'Q2 2028',
            milestone: 'Environmental Assessment underway',
          },
          {
            name: 'Waitaluk Estate',
            ward: 'Waitaluk Ward',
            units: 40,
            pct: 5,
            status: 'planning',
            contractor: 'TBD',
            funding: 'County Development Fund',
            leadAgency: 'Trans-Nzoia County Housing Dept.',
            siteEngineer: 'TBD',
            startDate: 'TBD',
            estDelivery: 'TBD',
            milestone: 'Land acquisition in progress',
          },
        ],
      },
      kwanza: {
        projects: [
          {
            name: 'Kwanza Township Housing',
            ward: 'Kwanza Ward',
            units: 80,
            pct: 8,
            status: 'planning',
            contractor: 'TBD — Procurement stage',
            funding: 'National AHP Fund',
            leadAgency: 'State Dept. of Housing',
            siteEngineer: 'TBD',
            startDate: 'TBD — Q4 2026 target',
            estDelivery: 'Q1 2028',
            milestone: 'Design review in progress',
          },
        ],
      },
    };

    function buildProjectCard(p) {
      const isActive = p.status === 'active';
      const badgeStyle = isActive
        ? 'background:rgba(34,197,94,0.12);color:#15803d;border:1px solid rgba(34,197,94,0.25)'
        : 'background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.25)';
      return `
        <div class="mpd-project-card">
          <div class="mpd-pc-header">
            <span class="mpd-pc-name">${p.name}</span>
            <span class="mpd-pc-badge" style="${badgeStyle}">${isActive ? 'Active' : 'Planning'}</span>
          </div>
          <div class="mpd-pc-rows">
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-location-dot"></i> Ward</span><span class="mpd-pc-val">${p.ward}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-building"></i> Units</span><span class="mpd-pc-val">${p.units.toLocaleString()}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-hammer"></i> Contractor</span><span class="mpd-pc-val">${p.contractor}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-coins"></i> Funding</span><span class="mpd-pc-val">${p.funding}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-building-columns"></i> Lead Agency</span><span class="mpd-pc-val">${p.leadAgency}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-regular fa-calendar"></i> Start Date</span><span class="mpd-pc-val">${p.startDate}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-flag-checkered"></i> Est. Delivery</span><span class="mpd-pc-val">${p.estDelivery}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-person-digging"></i> Site Engineer</span><span class="mpd-pc-val">${p.siteEngineer}</span></div>
          </div>
          <div class="mpd-pc-progress-wrap">
            <div class="mpd-progress-meta"><span>Completion</span><span>${p.pct}%</span></div>
            <div class="mpd-progress-bar"><div class="mpd-progress-fill mpd-progress-fill-anim" style="width:0%" data-target="${p.pct}"></div></div>
          </div>
          <p class="mpd-pc-milestone"><i class="fa-solid fa-circle-dot" style="color:var(--lime-muted)"></i> ${p.milestone}</p>
        </div>`;
    }

    function showDetail(g) {
      const id   = g.getAttribute('data-id')   || '';
      const name = g.getAttribute('data-name') || '';
      const link = g.getAttribute('data-link') || 'constituencies.html';
      const data = CONSTITUENCY_DATA[id];
      const projects = data ? data.projects : [];

      /* Build tab strip (only when multiple projects) */
      const tabStrip = projects.length > 1
        ? `<div class="mpd-tabs" role="tablist" aria-label="${name} projects">
            ${projects.map((p, i) => `
              <button class="mpd-tab-btn${i === 0 ? ' is-active' : ''}"
                role="tab" aria-selected="${i === 0}" aria-controls="mpd-tab-panel-${i}"
                data-tab="${i}">
                ${p.name.split(' ').slice(0, 3).join(' ')}&hellip;
              </button>`).join('')}
           </div>`
        : '';

      /* Build tab panels — each project is a panel */
      const tabPanels = projects.map((p, i) => `
        <div class="mpd-tab-panel${i === 0 ? ' is-active' : ''}" id="mpd-tab-panel-${i}" role="tabpanel">
          ${buildProjectCard(p)}
        </div>`).join('');

      panelDetail.innerHTML = `
        <div class="mpd-header">
          <button class="mpd-back" id="mpd-back-btn" aria-label="Back to constituency overview">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </button>
          <span class="mpd-name">${name}</span>
          <span class="mpd-proj-count">${projects.length} project${projects.length !== 1 ? 's' : ''}</span>
        </div>
        ${tabStrip}
        <div class="mpd-tab-panels">${tabPanels}</div>
        <div class="mpd-cta">
          <a href="${link}" class="btn btn-primary">
            <i class="fa-solid fa-eye" aria-hidden="true"></i> All ${name} Projects
          </a>
        </div>`;

      panelDefault.style.display = 'none';
      panelDetail.classList.add('is-visible');

      /* Animate progress bars in the initially visible tab */
      requestAnimationFrame(() => requestAnimationFrame(() => {
        const activePanel = panelDetail.querySelector('.mpd-tab-panel.is-active, .mpd-project-card');
        if (activePanel) {
          activePanel.querySelectorAll('.mpd-progress-fill-anim').forEach((fill) => {
            const target = parseFloat(fill.getAttribute('data-target'));
            if (!isNaN(target)) fill.style.width = target + '%';
          });
        }
      }));

      /* Wire tab buttons */
      panelDetail.querySelectorAll('.mpd-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          const idx = parseInt(btn.getAttribute('data-tab'), 10);
          panelDetail.querySelectorAll('.mpd-tab-btn').forEach((b) => {
            b.classList.toggle('is-active', b === btn);
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
          });
          panelDetail.querySelectorAll('.mpd-tab-panel').forEach((p, i) => {
            const show = i === idx;
            p.classList.toggle('is-active', show);
            if (show) {
              requestAnimationFrame(() => requestAnimationFrame(() => {
                p.querySelectorAll('.mpd-progress-fill-anim').forEach((fill) => {
                  fill.style.width = '0%';
                  requestAnimationFrame(() => {
                    const target = parseFloat(fill.getAttribute('data-target'));
                    if (!isNaN(target)) fill.style.width = target + '%';
                  });
                });
              }));
            }
          });
        });
      });

      const backBtn = document.getElementById('mpd-back-btn');
      if (backBtn) {
        backBtn.addEventListener('click', showDefault);
        backBtn.focus();
      }
    }

    constituencies.forEach((g) => {
      function activate() {
        if (selected) selected.classList.remove('is-selected');
        selected = g;
        g.classList.add('is-selected');
        showDetail(g);
      }
      g.addEventListener('click', activate);
      g.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
      });
    });
  }

  /* ========================================================
     7. SECTION FADE-UP (generic .fade-up elements)
        Skips anything inside .tracker-hero — handled by initHeroEntrance
     ======================================================== */
  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.style.opacity   = '1';
        entry.target.style.transform = 'translateY(0)';
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12 });
    els.forEach((el) => {
      if (el.closest('.tracker-hero')) return;
      el.style.opacity   = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.65s ease, transform 0.65s ease';
      observer.observe(el);
    });
  }

  /* ========================================================
     8. NEWS CARD STAGGER (asymmetric layout)
     ======================================================== */
  function initNewsStagger() {
    const wrap = document.querySelector('.news-asymmetric');
    if (!wrap) return;
    const cards = Array.from(wrap.querySelectorAll('.news-card'));
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        cards.forEach((card, i) => {
          setTimeout(() => {
            card.style.opacity   = '1';
            card.style.transform = 'translateY(0)';
          }, i * 110);
        });
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.1 });
    cards.forEach((card) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    observer.observe(wrap);
  }

  /* ========================================================
     INIT
     ======================================================== */
  function init() {
    initCursor();
    initHeroEntrance();
    initHeroParallax();
    initKPICounters();
    initProgressBars();
    initMap();
    initFadeUp();
    initNewsStagger();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
