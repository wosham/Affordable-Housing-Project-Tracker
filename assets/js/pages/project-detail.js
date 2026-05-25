/* =========================================================
   TRANS-NZOIA AHP â€” Project Detail Page JS
   Loads a single project from URL ?id= and renders page
   ========================================================= */
(function () {
  'use strict';

  /* --------------------------------------------------
     Not-found state
     -------------------------------------------------- */
  function showNotFound() {
    const hero = document.getElementById('pdHero');
    const body = document.getElementById('pdBody');
    const nf   = document.getElementById('pdNotFound');
    if (hero) hero.hidden = true;
    if (body) body.hidden = true;
    if (nf)   nf.hidden   = false;
    document.title = '404 Not Found | Trans-Nzoia AHP Tracker';
  }

  /* --------------------------------------------------
     Build hero section
     -------------------------------------------------- */
  function buildHero(p) {
    const heroBg = document.getElementById('pdHeroBg');
    if (heroBg && p.images && p.images.length) {
      const img   = document.createElement('img');
      img.src     = p.images[0];
      img.alt     = '';
      img.loading = 'eager';
      img.onerror = () => img.remove();
      heroBg.insertBefore(img, heroBg.firstChild);
    }

    const bc = document.getElementById('pdBreadcrumbCurrent');
    if (bc) bc.textContent = p.name;

    const statusBadge = p.status === 'active'
      ? '<span class="pd-badge pd-badge--active"><i class="fa-solid fa-circle-dot" aria-hidden="true"></i> Active</span>'
      : '<span class="pd-badge pd-badge--planning"><i class="fa-solid fa-clock" aria-hidden="true"></i> Planning</span>';

    const heroContent = document.getElementById('pdHeroContent');
    if (heroContent) {
      heroContent.innerHTML = `
        <div class="pd-hero-badges">
          ${statusBadge}
          <span class="pd-badge pd-badge--con">
            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
            ${p.constituencyName}
          </span>
          ${p.startDate ? `<span class="pd-badge pd-badge--date"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Started ${p.startDate}</span>` : ''}
          ${p.estDelivery ? `<span class="pd-badge pd-badge--date"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i> Est. Delivery ${p.estDelivery}</span>` : ''}
        </div>
        <h1 class="pd-hero-title">${p.name}</h1>
        <div class="pd-hero-meta">
          <span class="pd-hero-meta-item">
            <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
            ${p.ward}, ${p.constituencyName}
          </span>
          <span class="pd-hero-meta-item">
            <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
            ${p.milestone}
          </span>
          <span class="pd-hero-meta-item">
            <i class="fa-solid fa-person-digging" aria-hidden="true"></i>
            ${p.contractor}
          </span>
        </div>
        <div class="pd-hero-pct-wrap">
          <div class="pd-hero-pct">
            <span class="pd-hero-pct-num">${p.pct}<span class="pd-hero-pct-sign">%</span></span>
            <span class="pd-hero-pct-lbl">Construction Complete</span>
          </div>
          <div class="pd-hero-pct-bar" aria-hidden="true">
            <div class="pd-hero-pct-bar-fill" data-target="${p.pct}" style="width:0%"></div>
          </div>
        </div>`;

      /* Animate hero bar */
      setTimeout(() => {
        const fill = heroContent.querySelector('.pd-hero-pct-bar-fill');
        if (fill) fill.style.width = fill.getAttribute('data-target') + '%';
      }, 400);
    }
  }

  /* --------------------------------------------------
     Build 6-fact strip
     -------------------------------------------------- */
  function buildFactsStrip(p) {
    const factsData = [
      { icon: 'fa-house-chimney',   lbl: 'Units Planned',  val: p.units.toLocaleString() },
      { icon: 'fa-map-pin',         lbl: 'Ward',           val: p.ward },
      { icon: 'fa-person-digging',  lbl: 'Contractor',     val: p.contractor },
      { icon: 'fa-coins',           lbl: 'Funding Source', val: p.funding || 'â€”' },
      { icon: 'fa-calendar-plus',   lbl: 'Start Date',     val: p.startDate || 'â€”' },
      { icon: 'fa-flag-checkered',  lbl: 'Est. Delivery',  val: p.estDelivery || 'â€”' },
    ];
    const el = document.getElementById('pdFactsStrip');
    if (el) {
      el.innerHTML = factsData.map(f => `
        <div class="pd-fact">
          <div class="pd-fact-icon-wrap" aria-hidden="true">
            <i class="fa-solid ${f.icon}"></i>
          </div>
          <span class="pd-fact-lbl">${f.lbl}</span>
          <span class="pd-fact-val">${f.val}</span>
        </div>`).join('');
    }
  }

  /* --------------------------------------------------
     Build description
     -------------------------------------------------- */
  function buildDescription(p) {
    const el = document.getElementById('pdDescription');
    if (!el) return;
    el.innerHTML = `
      <p class="pd-section-label">About This Project</p>
      <h2 class="pd-section-title">Project Overview</h2>
      <p class="pd-description-text">${p.description}</p>
      <div class="pd-desc-meta-strip">
        ${p.leadAgency ? `<div class="pd-desc-meta-item"><i class="fa-solid fa-building-government" aria-hidden="true"></i><span><strong>Lead Agency</strong>${p.leadAgency}</span></div>` : ''}
        ${p.siteEngineer && p.siteEngineer !== 'TBD' ? `<div class="pd-desc-meta-item"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i><span><strong>Site Engineer</strong>${p.siteEngineer}</span></div>` : ''}
        ${p.funding ? `<div class="pd-desc-meta-item"><i class="fa-solid fa-coins" aria-hidden="true"></i><span><strong>Funding</strong>${p.funding}</span></div>` : ''}
      </div>
      <div class="pd-current-milestone">
        <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
        <span><strong>Current Activity:</strong> ${p.milestone}</span>
      </div>`;
  }

  /* --------------------------------------------------
     Build construction progress
     -------------------------------------------------- */
  function buildProgress(p) {
    const el = document.getElementById('pdProgressSection');
    if (!el) return;

    el.innerHTML = `
      <p class="pd-section-label">Construction Progress</p>
      <h2 class="pd-section-title">Live Progress Tracker</h2>
      <div class="pd-progress-bar-wrap">
        <div class="pd-progress-meta">
          <span class="pd-progress-lbl">Overall Completion</span>
          <span class="pd-progress-pct">${p.pct}%</span>
        </div>
        <div class="pd-progress-track" role="progressbar" aria-valuenow="${p.pct}" aria-valuemin="0" aria-valuemax="100" aria-label="${p.pct}% construction complete">
          <div class="pd-progress-fill" id="pdProgressFill" data-target="${p.pct}"></div>
        </div>
        <p class="pd-progress-note"><i class="fa-regular fa-clock" aria-hidden="true"></i> Data updated regularly by the Trans-Nzoia County Housing Department.</p>
      </div>
      <div class="pd-mini-stats">
        <div class="pd-mini-stat">
          <span class="pd-mini-stat-val">${p.units.toLocaleString()}</span>
          <span class="pd-mini-stat-lbl">Units Planned</span>
        </div>
        <div class="pd-mini-stat">
          <span class="pd-mini-stat-val">${Math.round(p.units * p.pct / 100).toLocaleString()}</span>
          <span class="pd-mini-stat-lbl">Units In Progress</span>
        </div>
        <div class="pd-mini-stat">
          <span class="pd-mini-stat-val">${p.estDelivery || 'â€”'}</span>
          <span class="pd-mini-stat-lbl">Target Delivery</span>
        </div>
      </div>`;

    const fill = document.getElementById('pdProgressFill');
    if (!fill) return;
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            fill.style.width = fill.getAttribute('data-target') + '%';
            obs.unobserve(fill);
          }
        });
      }, { threshold: 0.4 });
      obs.observe(fill);
    } else {
      fill.style.width = p.pct + '%';
    }
  }

  /* --------------------------------------------------
     Build milestone timeline (stepper)
     Uses m.label (correct data field) not m.title
     -------------------------------------------------- */
  function buildTimeline(p) {
    const el = document.getElementById('pdTimelineSection');
    if (!el) return;

    const milestones = p.milestones && p.milestones.length ? p.milestones : [
      { label: 'Site Acquisition',    date: 'Jan 2024', done: true },
      { label: 'Foundation Works',    date: 'Mar 2024', done: p.pct >= 20 },
      { label: 'Structural Frame',    date: 'Jul 2024',  done: p.pct >= 45, current: p.pct >= 20 && p.pct < 45 },
      { label: 'Finishing & MEP',     date: 'Dec 2024', done: p.pct >= 75, current: p.pct >= 45 && p.pct < 75 },
      { label: 'Unit Handover',       date: 'Q1 2026',  done: p.pct >= 100, current: p.pct >= 75 && p.pct < 100 },
    ];

    const itemsHtml = milestones.map(m => {
      let cls = '';
      if (m.done)    cls = 'is-done';
      if (m.current) cls = 'is-current';
      const icon = m.done
        ? '<i class="fa-solid fa-check" aria-hidden="true"></i>'
        : m.current
          ? '<i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>'
          : '';
      const badge = m.current ? '<span class="pd-tl-badge">CURRENT</span>' : '';
      return `
        <div class="pd-tl-item ${cls}">
          <div class="pd-tl-spine" aria-hidden="true">
            <div class="pd-tl-dot">${icon}</div>
          </div>
          <div class="pd-tl-content">
            <span class="pd-tl-date">${m.date}</span>
            <p class="pd-tl-label">${m.label} ${badge}</p>
          </div>
        </div>`;
    }).join('');

    el.innerHTML = `
      <p class="pd-section-label">Key Milestones</p>
      <h2 class="pd-section-title">Construction Timeline</h2>
      <div class="pd-timeline">${itemsHtml}</div>`;
  }

  /* --------------------------------------------------
     Build photo gallery: featured + static 2-col grid
     -------------------------------------------------- */
  function buildGallery(p) {
    const el = document.getElementById('pdGallerySection');
    if (!el) return;

    const images = p.images || [];
    if (!images.length) {
      el.innerHTML = `
        <p class="pd-section-label">Site Photography</p>
        <h2 class="pd-section-title">Photo Gallery</h2>
        <div class="pd-gallery-empty">
          <i class="fa-solid fa-camera-slash" aria-hidden="true"></i>
          <p>Site photography will be added as construction progresses.</p>
        </div>`;
      return;
    }

    const [featured, ...rest] = images;

    const gridHtml = rest.length ? `
      <div class="pd-gallery-grid">
        ${rest.map((src, i) => `
          <button class="pd-gallery-item" data-index="${i + 1}" aria-label="View photo ${i + 2}">
            <img src="${src}" alt="${p.name} â€” site photo ${i + 2}" loading="lazy"
                 onerror="this.closest('.pd-gallery-item').style.display='none'">
            <div class="pd-gallery-item-overlay">
              <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>
            </div>
          </button>`).join('')}
      </div>` : '';

    el.innerHTML = `
      <p class="pd-section-label">Site Photography</p>
      <h2 class="pd-section-title">Photo Gallery</h2>
      <div class="pd-gallery">
        <button class="pd-gallery-featured" data-index="0" aria-label="View featured photo">
          <img src="${featured}" alt="${p.name} â€” featured site photo" loading="lazy"
               onerror="this.closest('.pd-gallery-featured').style.display='none'">
          <div class="pd-gallery-item-overlay">
            <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>
          </div>
        </button>
        ${gridHtml}
      </div>`;

    initLightbox(el, images, p.name);
  }

  /* --------------------------------------------------
     Lightbox
     -------------------------------------------------- */
  function initLightbox(galleryEl, images, name) {
    let current = 0;

    const lb = document.createElement('div');
    lb.className = 'pd-lightbox';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-label', 'Photo lightbox');
    lb.innerHTML = `
      <button class="pd-lb-close" aria-label="Close lightbox"><i class="fa-solid fa-xmark"></i></button>
      <button class="pd-lb-prev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
      <button class="pd-lb-next" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
      <div class="pd-lb-img-wrap">
        <img class="pd-lb-img" src="" alt="">
      </div>
      <p class="pd-lb-caption"></p>`;
    document.body.appendChild(lb);

    const lbImg     = lb.querySelector('.pd-lb-img');
    const lbCaption = lb.querySelector('.pd-lb-caption');

    function show(idx) {
      current = (idx + images.length) % images.length;
      lbImg.src         = images[current];
      lbImg.alt         = `${name} â€” photo ${current + 1}`;
      lbCaption.textContent = `Photo ${current + 1} of ${images.length}`;
      lb.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      lb.querySelector('.pd-lb-close').focus();
    }

    function close() {
      lb.classList.remove('is-open');
      document.body.style.overflow = '';
    }

    galleryEl.querySelectorAll('[data-index]').forEach(btn => {
      btn.addEventListener('click', () => show(parseInt(btn.getAttribute('data-index'), 10)));
    });

    lb.querySelector('.pd-lb-close').addEventListener('click', close);
    lb.querySelector('.pd-lb-prev').addEventListener('click', () => show(current - 1));
    lb.querySelector('.pd-lb-next').addEventListener('click', () => show(current + 1));
    lb.addEventListener('click', (e) => { if (e.target === lb) close(); });

    document.addEventListener('keydown', (e) => {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape')     close();
      if (e.key === 'ArrowLeft')  show(current - 1);
      if (e.key === 'ArrowRight') show(current + 1);
    });
  }

  /* --------------------------------------------------
     Build sidebar
     -------------------------------------------------- */
  function buildSidebar(p) {
    const el = document.getElementById('pdSidebar');
    if (!el) return;

    const conLink  = `constituency-detail.php?id=${p.constituency}`;
    const projLink = `projects.php?constituency=${p.constituency}`;
    const statusBadge = p.status === 'active'
      ? '<span class="pd-sb-status pd-sb-status--active">Active Construction</span>'
      : '<span class="pd-sb-status pd-sb-status--planning">Planning Stage</span>';

    const infoRows = [
      { lbl: 'Constituency', val: `<a href="${conLink}" class="pd-sb-link">${p.constituencyName}</a>` },
      { lbl: 'Ward',         val: p.ward },
      { lbl: 'Status',       val: statusBadge, raw: true },
      { lbl: 'Target Units', val: p.units.toLocaleString() },
      { lbl: 'Start Date',   val: p.startDate || 'â€”' },
      { lbl: 'Est. Delivery', val: p.estDelivery || 'â€”' },
      {
        lbl: 'Completion', val: `
          <span class="pd-sb-pct">${p.pct}%</span>
          <div class="pd-sb-mini-bar"><div class="pd-sb-mini-fill" style="width:${p.pct}%"></div></div>`,
        raw: true
      },
    ];

    const infoHtml = infoRows.map((r, i) => `
      <div class="pd-sidebar-row${i % 2 === 1 ? ' is-alt' : ''}">
        <span class="pd-sidebar-row-lbl">${r.lbl}</span>
        <span class="pd-sidebar-row-val">${r.val}</span>
      </div>`).join('');

    el.innerHTML = `
      <div class="pd-sidebar-card pd-sb-contractor">
        <div class="pd-sb-con-avatar" aria-hidden="true">
          <i class="fa-solid fa-helmet-safety"></i>
        </div>
        <div class="pd-sb-con-info">
          <p class="pd-sidebar-card-title">Contractor</p>
          <p class="pd-contractor-name">${p.contractor}</p>
          <span class="pd-contractor-role-badge">Principal Contractor</span>
        </div>
        ${p.leadAgency ? `<div class="pd-sb-agency"><i class="fa-solid fa-building-government" aria-hidden="true"></i><span>${p.leadAgency}</span></div>` : ''}
        ${p.siteEngineer && p.siteEngineer !== 'TBD' ? `<div class="pd-sb-agency"><i class="fa-solid fa-user-tie" aria-hidden="true"></i><span>Site Eng: ${p.siteEngineer}</span></div>` : ''}
      </div>

      <div class="pd-sidebar-card">
        <p class="pd-sidebar-card-title">Project Info</p>
        <div class="pd-sidebar-list">${infoHtml}</div>
      </div>

      <div class="pd-sidebar-card pd-apply-card">
        <div class="pd-apply-icon-wrap" aria-hidden="true">
          <i class="fa-solid fa-house-chimney-user"></i>
        </div>
        <p class="pd-apply-title">Interested in a Unit?</p>
        <p class="pd-apply-sub">Register on the national Boma Yangu portal to apply for affordable housing in Trans-Nzoia County.</p>
        <a href="https://app.bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="pd-apply-btn">
          <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
          Apply on Boma Yangu
        </a>
      </div>

      <div class="pd-sidebar-card">
        <p class="pd-sidebar-card-title">Related</p>
        <div class="pd-related-links">
          <a href="${conLink}" class="pd-related-link">
            <div class="pd-related-icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></div>
            <span>${p.constituencyName} Constituency</span>
            <i class="fa-solid fa-chevron-right pd-related-arrow" aria-hidden="true"></i>
          </a>
          <a href="${projLink}" class="pd-related-link">
            <div class="pd-related-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></div>
            <span>All ${p.constituencyName} Projects</span>
            <i class="fa-solid fa-chevron-right pd-related-arrow" aria-hidden="true"></i>
          </a>
          <a href="projects.php" class="pd-related-link">
            <div class="pd-related-icon"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></div>
            <span>Back to All Projects</span>
            <i class="fa-solid fa-chevron-right pd-related-arrow" aria-hidden="true"></i>
          </a>
        </div>
      </div>`;
  }

  /* --------------------------------------------------
     Init
     -------------------------------------------------- */
  function init() {
    const params = new URLSearchParams(window.location.search);
    const id     = params.get('id');

    if (!id) { showNotFound(); return; }

    const p = AHP_DATA.getProject(id);
    if (!p) { showNotFound(); return; }

    document.title = `${p.name} | Trans-Nzoia AHP Tracker`;
    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) metaDesc.setAttribute('content', p.description);
    const ogTitle  = document.querySelector('meta[property="og:title"]');
    if (ogTitle) ogTitle.setAttribute('content', `${p.name} â€” Trans-Nzoia AHP`);
    if (p.images && p.images.length) {
      const ogImg = document.querySelector('meta[property="og:image"]');
      if (ogImg) ogImg.setAttribute('content', p.images[0]);
    }

    buildHero(p);
    buildFactsStrip(p);
    buildDescription(p);
    buildProgress(p);
    buildTimeline(p);
    buildGallery(p);
    buildSidebar(p);
  }

  document.addEventListener('DOMContentLoaded', init);
})();
