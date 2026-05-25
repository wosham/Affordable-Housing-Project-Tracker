/* =========================================================
   TRANS-NZOIA AHP â€” Constituency Detail Page JS
   Loads constituency data from URL ?id= and renders page
   ========================================================= */
(function () {
  'use strict';

  /* --------------------------------------------------
     Reuse project card builder (same as projects.js)
     -------------------------------------------------- */
  function buildProjCard(p) {
    const statusClass = p.status === 'active' ? 'proj-status-badge--active' : 'proj-status-badge--planning';
    const statusLabel = p.status === 'active' ? 'Active' : 'Planning';
    const imgHtml = p.images && p.images.length
      ? `<img src="${p.images[0]}" alt="${p.name}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
        + `<div class="proj-card-img-placeholder" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></div>`
      : `<div class="proj-card-img-placeholder"><i class="fa-solid fa-building" aria-hidden="true"></i></div>`;

    return `
      <article class="proj-card">
        <div class="proj-card-img">
          ${imgHtml}
          <span class="proj-status-badge ${statusClass}">${statusLabel}</span>
          <span class="proj-con-tag">${p.ward}</span>
        </div>
        <div class="proj-card-body">
          <div class="proj-card-meta"><span class="proj-ward">${p.ward}</span></div>
          <h3 class="proj-card-title">${p.name}</h3>
          <div class="proj-card-stats">
            <div class="proj-stat">
              <span class="proj-stat-val">${p.units.toLocaleString()}</span>
              <span class="proj-stat-lbl">Units</span>
            </div>
            <div class="proj-stat">
              <span class="proj-stat-val">${p.pct}%</span>
              <span class="proj-stat-lbl">Complete</span>
            </div>
          </div>
          <div class="proj-progress" role="progressbar" aria-valuenow="${p.pct}" aria-valuemin="0" aria-valuemax="100">
            <div class="proj-progress-fill" data-target="${p.pct}"></div>
          </div>
          <div class="proj-milestone">
            <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
            <span>${p.milestone}</span>
          </div>
          <div class="proj-card-footer">
            <span class="proj-contractor" title="${p.contractor}">${p.contractor}</span>
            <a href="${p.link}" class="proj-cta">View Project <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </div>
        </div>
      </article>`;
  }

  /* --------------------------------------------------
     Animate progress fills (any [data-target] fill)
     -------------------------------------------------- */
  function animateFills(container) {
    const fills = container.querySelectorAll('[data-target]');
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.style.width = e.target.getAttribute('data-target') + '%';
            obs.unobserve(e.target);
          }
        });
      }, { threshold: 0.3 });
      fills.forEach(f => obs.observe(f));
    } else {
      fills.forEach(f => f.style.width = f.getAttribute('data-target') + '%');
    }
  }

  /* --------------------------------------------------
     Show not-found state
     -------------------------------------------------- */
  function showNotFound() {
    document.getElementById('cdHero').hidden      = true;
    document.getElementById('cdNotFound').hidden  = false;
    const hide = ['cd-projects-section','cd-facts-section','cdProgressSection',
                  'cd-related-section','cdWardStrip'];
    hide.forEach(sel => {
      const el = document.getElementById(sel) || document.querySelector('.' + sel);
      if (el) el.hidden = true;
    });
    document.title = '404 Not Found | Trans-Nzoia AHP Tracker';
  }

  /* --------------------------------------------------
     Render full page from constituency data
     -------------------------------------------------- */
  function renderPage(con) {
    const projects = AHP_DATA.getProjectsByConstituency(con.id);

    /* Page title & meta */
    document.title = `${con.name} Constituency | Trans-Nzoia AHP Tracker`;
    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) metaDesc.setAttribute('content', con.description);

    /* Breadcrumb */
    const breadCurrent = document.getElementById('cdBreadcrumbCurrent');
    if (breadCurrent) breadCurrent.textContent = con.name;

    /* Hero background image */
    const heroBg = document.getElementById('cdHeroBg');
    if (heroBg && con.heroImage) {
      const img = document.createElement('img');
      img.src    = con.heroImage;
      img.alt    = '';
      img.loading = 'eager';
      img.onerror = () => img.remove();
      heroBg.insertBefore(img, heroBg.firstChild);
    }

    /* Hero body */
    const statusLabel = con.status === 'active' ? 'Active Construction' : 'Planning Stage';
    const heroBody = document.getElementById('cdHeroBody');
    if (heroBody) {
      heroBody.innerHTML = `
        <div class="cd-hero-eyebrow">
          <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
          Trans-Nzoia County &mdash; ${statusLabel}
        </div>
        <h1 class="cd-hero-title">${con.name}</h1>
        <p class="cd-hero-desc">${con.description}</p>`;
    }

    /* KPI cards */
    const totalUnits = projects.reduce((s, p) => s + p.units, 0);
    const avgPct     = projects.length
      ? Math.round(projects.reduce((s, p) => s + p.pct, 0) / projects.length)
      : 0;
    const kpiData = [
      { icon: 'fa-building-columns', val: projects.length, lbl: 'Projects' },
      { icon: 'fa-house-chimney',    val: totalUnits.toLocaleString(), lbl: 'Units Planned' },
      { icon: 'fa-users',            val: con.population, lbl: 'Population' },
      { icon: 'fa-chart-simple',     val: avgPct + '%', lbl: 'Avg. Completion' },
    ];
    const kpisEl = document.getElementById('cdHeroKpis');
    if (kpisEl) {
      kpisEl.innerHTML = kpiData.map(k => `
        <div class="cd-kpi-card">
          <i class="fa-solid ${k.icon} cd-kpi-icon" aria-hidden="true"></i>
          <span class="cd-kpi-val">${k.val}</span>
          <span class="cd-kpi-lbl">${k.lbl}</span>
        </div>`).join('');
    }

    /* Projects grid */
    const projTitle = document.getElementById('cdProjectsTitle');
    if (projTitle) projTitle.textContent = `${con.name} Projects`;

    const viewAll = document.getElementById('cdViewAll');
    if (viewAll) viewAll.href = `projects.php?constituency=${con.id}`;

    const projGrid = document.getElementById('cdProjectsGrid');
    if (projGrid) {
      projGrid.innerHTML = projects.length
        ? projects.map(buildProjCard).join('')
        : `<p style="color:var(--neutral-400);font-family:var(--font-heading)">No projects listed yet for this constituency.</p>`;
      animateFills(projGrid);
    }

    /* Constituency facts */
    const factsData = [
      { icon: 'fa-map-pin',       label: 'County',       value: 'Trans-Nzoia County, Kenya' },
      { icon: 'fa-users',         label: 'Population',   value: con.population },
      { icon: 'fa-map',           label: 'Wards',        value: con.wards.join(', ') },
      { icon: 'fa-person-shelter',label: 'Lead Agency',  value: 'State Dept. of Housing' },
      { icon: 'fa-coins',         label: 'Funding',      value: 'National AHP Fund + County Budget' },
      { icon: 'fa-building-columns', label: 'Programme', value: 'National Affordable Housing Programme' },
    ];
    const factsGrid = document.getElementById('cdFactsGrid');
    if (factsGrid) {
      factsGrid.innerHTML = factsData.map(f => `
        <div class="cd-fact-card">
          <i class="fa-solid ${f.icon} cd-fact-icon" aria-hidden="true"></i>
          <span class="cd-fact-label">${f.label}</span>
          <span class="cd-fact-value">${f.value}</span>
        </div>`).join('');
    }

    /* Mini map â€” highlight selected constituency */
    document.querySelectorAll('.cdm-path-group').forEach(g => {
      g.classList.toggle('is-selected', g.dataset.id === con.id);
    });

    /* Ward pills strip */
    const wardStrip = document.getElementById('cdWardStrip');
    if (wardStrip) {
      const innerDiv = wardStrip.querySelector('.container');
      if (innerDiv) {
        innerDiv.innerHTML = `
          <div class="cd-ward-strip-inner">
            <span class="cd-ward-strip-label"><i class="fa-solid fa-map-pin" aria-hidden="true"></i> Wards</span>
            ${con.wards.map(w => `<span class="cd-ward-pill">${w}</span>`).join('')}
          </div>`;
      }
    }

    /* Progress section */
    const progSection = document.getElementById('cdProgressSection');
    if (progSection) {
      const totalUnitsP = projects.reduce((s, p) => s + p.units, 0);
      const avgPctP     = projects.length
        ? Math.round(projects.reduce((s, p) => s + p.pct, 0) / projects.length)
        : 0;
      const innerDiv = progSection.querySelector('.container');
      if (innerDiv) {
        innerDiv.innerHTML = `
          <div class="cd-prog-inner">
            <div class="cd-prog-left">
              <div class="cd-prog-label">Progress Overview</div>
              <h2 class="cd-prog-title">${con.name} Construction Progress</h2>
              <p class="cd-prog-sub">
                Across <strong>${projects.length} active project${projects.length !== 1 ? 's' : ''}</strong>, ${con.name} Constituency
                has delivered <strong>${totalUnitsP.toLocaleString()} units</strong> under the national AHP programme.
                Work is progressing across ${con.wards.length} wards.
              </p>
            </div>
            <div class="cd-prog-right">
              <div>
                <div class="cd-prog-pct-display">${avgPctP}%</div>
                <div class="cd-prog-pct-lbl">Average Completion</div>
              </div>
              <div class="cd-prog-bar-wrap" role="progressbar" aria-valuenow="${avgPctP}" aria-valuemin="0" aria-valuemax="100">
                <div class="cd-prog-bar-fill" data-target="${avgPctP}"></div>
              </div>
              <div class="cd-prog-stats-row">
                <div class="cd-prog-stat">
                  <span class="cd-prog-stat-val">${totalUnitsP.toLocaleString()}</span>
                  <span class="cd-prog-stat-lbl">Total Units</span>
                </div>
                <div class="cd-prog-stat">
                  <span class="cd-prog-stat-val">${projects.length}</span>
                  <span class="cd-prog-stat-lbl">Projects</span>
                </div>
                <div class="cd-prog-stat">
                  <span class="cd-prog-stat-val">${con.wards.length}</span>
                  <span class="cd-prog-stat-lbl">Wards</span>
                </div>
              </div>
            </div>
          </div>`;
        animateFills(innerDiv);
      }
    }

    /* Related constituencies */
    const relatedGrid = document.getElementById('cdRelatedGrid');
    if (relatedGrid) {
      const others = AHP_DATA.constituencies.filter(c => c.id !== con.id);
      relatedGrid.innerHTML = others.map(c => {
        const relProjects = AHP_DATA.getProjectsByConstituency(c.id);
        const relUnits    = relProjects.reduce((s, p) => s + p.units, 0);
        const statusCls   = c.status === 'active' ? 'con-browse-status--active' : 'con-browse-status--planning';
        const statusLbl   = c.status === 'active' ? 'Active' : 'Planning';
        return `
          <a href="constituency-detail.php?id=${c.id}" class="cd-related-card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
              <div>
                <div class="cd-related-name">${c.name}</div>
                <div class="cd-related-pop">${c.population}</div>
              </div>
              <span class="con-browse-status ${statusCls}" style="font-size:10px;padding:2px 10px">${statusLbl}</span>
            </div>
            <div class="cd-related-stats">
              <div class="cd-related-stat">
                <span class="cd-related-stat-val">${relProjects.length}</span>
                <span class="cd-related-stat-lbl">Projects</span>
              </div>
              <div class="cd-related-stat">
                <span class="cd-related-stat-val">${relUnits.toLocaleString()}</span>
                <span class="cd-related-stat-lbl">Units</span>
              </div>
              <div class="cd-related-stat">
                <span class="cd-related-stat-val">${c.wards.length}</span>
                <span class="cd-related-stat-lbl">Wards</span>
              </div>
            </div>
            <div class="cd-related-cta">Explore ${c.name} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div>
          </a>`;
      }).join('');
    }
  }

  /* --------------------------------------------------
     Init
     -------------------------------------------------- */
  function init() {
    const params = new URLSearchParams(window.location.search);
    const id     = params.get('id');

    if (!id) { showNotFound(); return; }

    const con = AHP_DATA.getConstituency(id);
    if (!con) { showNotFound(); return; }

    renderPage(con);
  }

  document.addEventListener('DOMContentLoaded', init);
})();
