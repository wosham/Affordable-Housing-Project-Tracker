/**
 * TRANS-NZOIA AHP TRACKER â€” Homepage JavaScript
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

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  /* ========================================================
     1. CUSTOM CURSOR
        â€” dot follows mouse instantly
        â€” ring lerps behind with a smooth lag
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
        â€” click / keyboard Enter|Space on .map-constituency
        â€” renders a detail panel with progress bar animation
        â€” back button returns to overview panel
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

    const CONSTITUENCY_DATA = window.TNAH?.data?.model
      ? window.TNAH.data.model('map_projects', {})
      : {};

    function buildProjectCard(p) {
      const isActive = p.status === 'active' || p.status === 'completed';
      const statusLabel = p.statusLabel || (isActive ? 'Active' : 'Planning');
      const pct = Math.max(0, Math.min(100, Number(p.pct || 0)));
      const badgeStyle = isActive
        ? 'background:rgba(34,197,94,0.12);color:#15803d;border:1px solid rgba(34,197,94,0.25)'
        : 'background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.25)';
      return `
        <div class="mpd-project-card">
          <div class="mpd-pc-header">
            <span class="mpd-pc-name">${escapeHtml(p.name || 'Project')}</span>
            <span class="mpd-pc-badge" style="${badgeStyle}">${escapeHtml(statusLabel)}</span>
          </div>
          <div class="mpd-pc-rows">
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-location-dot"></i> Ward</span><span class="mpd-pc-val">${escapeHtml(p.ward || 'TBD')}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-building"></i> ${escapeHtml(p.outputLabel || 'Units')}</span><span class="mpd-pc-val">${Number(p.units || 0).toLocaleString()}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-hammer"></i> Contractor</span><span class="mpd-pc-val">${escapeHtml(p.contractor || 'TBD')}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-coins"></i> Funding</span><span class="mpd-pc-val">${escapeHtml(p.funding || 'TBD')}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-building-columns"></i> Lead Agency</span><span class="mpd-pc-val">${escapeHtml(p.leadAgency || 'TBD')}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-regular fa-calendar"></i> Start Date</span><span class="mpd-pc-val">${escapeHtml(p.startDate || 'TBD')}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-flag-checkered"></i> Est. Delivery</span><span class="mpd-pc-val">${escapeHtml(p.estDelivery || 'TBD')}</span></div>
            <div class="mpd-pc-row"><span class="mpd-pc-lbl"><i class="fa-solid fa-person-digging"></i> Site Engineer</span><span class="mpd-pc-val">${escapeHtml(p.siteEngineer || 'TBD')}</span></div>
          </div>
          <div class="mpd-pc-progress-wrap">
            <div class="mpd-progress-meta"><span>Completion</span><span>${pct}%</span></div>
            <div class="mpd-progress-bar"><div class="mpd-progress-fill mpd-progress-fill-anim" style="width:0%" data-target="${pct}"></div></div>
          </div>
          <p class="mpd-pc-milestone"><i class="fa-solid fa-circle-dot" style="color:var(--lime-muted)"></i> ${escapeHtml(p.milestone || 'Project update pending')}</p>
        </div>`;
    }

    function showDetail(g) {
      const id   = g.getAttribute('data-id')   || '';
      const name = g.getAttribute('data-name') || '';
      const link = g.getAttribute('data-link') || 'constituencies.php';
      const data = CONSTITUENCY_DATA[id];
      const projects = Array.isArray(data) ? data : (data && Array.isArray(data.projects) ? data.projects : []);

      if (!projects.length) {
        panelDetail.innerHTML = `
        <div class=\"mpd-header\">
          <button class=\"mpd-back\" id=\"mpd-back-btn\" aria-label=\"Back to constituency overview\">
            <i class=\"fa-solid fa-arrow-left\" aria-hidden=\"true\"></i>
          </button>
          <span class=\"mpd-name\">${escapeHtml(name)}</span>
          <span class=\"mpd-proj-count\">0 projects</span>
        </div>
        <div class=\"mpd-project-card\">
          <div class=\"mpd-pc-header\"><span class=\"mpd-pc-name\">No active tracked projects yet</span></div>
          <p class=\"mpd-pc-milestone\"><i class=\"fa-solid fa-circle-info\" style=\"color:var(--lime-muted)\"></i> This constituency is included in county coverage and will show project data once a site is published.</p>
        </div>
        <div class=\"mpd-cta\"><a href=\"${escapeHtml(link)}\" class=\"btn btn-primary\"><i class=\"fa-solid fa-eye\" aria-hidden=\"true\"></i> View ${escapeHtml(name)}</a></div>`;
        panelDefault.style.display = 'none';
        panelDetail.classList.add('is-visible');
        const emptyBackBtn = document.getElementById('mpd-back-btn');
        if (emptyBackBtn) { emptyBackBtn.addEventListener('click', showDefault); emptyBackBtn.focus(); }
        return;
      }

      /* Build tab strip (only when multiple projects) */
      const tabStrip = projects.length > 1
        ? `<div class="mpd-tabs" role="tablist" aria-label="${escapeHtml(name)} projects">
            ${projects.map((p, i) => `
              <button class="mpd-tab-btn${i === 0 ? ' is-active' : ''}"
                role="tab" aria-selected="${i === 0}" aria-controls="mpd-tab-panel-${i}"
                data-tab="${i}">
                ${escapeHtml(String(p.name || 'Project').split(' ').slice(0, 3).join(' '))}&hellip;
              </button>`).join('')}
           </div>`
        : '';

      /* Build tab panels â€” each project is a panel */
      const tabPanels = projects.map((p, i) => `
        <div class="mpd-tab-panel${i === 0 ? ' is-active' : ''}" id="mpd-tab-panel-${i}" role="tabpanel">
          ${buildProjectCard(p)}
        </div>`).join('');

      panelDetail.innerHTML = `
        <div class="mpd-header">
          <button class="mpd-back" id="mpd-back-btn" aria-label="Back to constituency overview">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </button>
          <span class="mpd-name">${escapeHtml(name)}</span>
          <span class="mpd-proj-count">${projects.length} project${projects.length !== 1 ? 's' : ''}</span>
        </div>
        ${tabStrip}
        <div class="mpd-tab-panels">${tabPanels}</div>
        <div class="mpd-cta">
          <a href="${escapeHtml(link)}" class="btn btn-primary">
            <i class="fa-solid fa-eye" aria-hidden="true"></i> All ${escapeHtml(name)} Projects
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
        Skips anything inside .tracker-hero â€” handled by initHeroEntrance
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
