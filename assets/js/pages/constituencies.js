/* =========================================================
   TRANS-NZOIA AHP — Constituencies Page JS
   Interactive SVG map + constituency cards
   ========================================================= */
(function () {
  'use strict';

  const cardsPanel  = document.getElementById('conCardsPanel');
  const chartEl     = document.getElementById('countyTotalsChart');
  const browseGrid  = document.getElementById('conBrowseGrid');
  const sortBar     = document.getElementById('conGridSort');

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  /* --------------------------------------------------
     Build a constituency card
     -------------------------------------------------- */
  function buildCard(c) {
    const statusClass = c.status === 'active' ? 'con-card-status--active' : 'con-card-status--planning';
    const statusLabel = c.status === 'active' ? 'Active' : 'Planning';
    const wardsList   = (c.wards || []).map(esc).join(', ');
    const link = esc(c.link || '#');

    return `
      <div class="con-card" data-id="${esc(c.id)}" tabindex="0" role="button" aria-label="Explore ${esc(c.name)} constituency">
        <div class="con-card-header">
          <div class="con-card-name-wrap">
            <span class="con-card-name">${esc(c.name)}</span>
            <span class="con-card-pop">${esc(c.population)} residents</span>
          </div>
          <span class="con-card-status ${statusClass}">${statusLabel}</span>
        </div>
        <div class="con-card-stats">
          <div class="con-card-stat">
            <span class="con-card-stat-val">${esc(c.projectCount)}</span>
            <span class="con-card-stat-lbl">Projects</span>
          </div>
          <div class="con-card-stat">
            <span class="con-card-stat-val">${Number(c.totalUnits || 0).toLocaleString()}</span>
            <span class="con-card-stat-lbl">Units</span>
          </div>
          <div class="con-card-stat">
            <span class="con-card-stat-val">${(c.wards || []).length}</span>
            <span class="con-card-stat-lbl">Wards</span>
          </div>
        </div>
        <div class="con-card-progress-wrap">
          <div class="con-card-progress-meta">
            <span class="con-card-progress-label">Avg. Completion</span>
            <span class="con-card-progress-pct">${esc(c.avgCompletion)}%</span>
          </div>
          <div class="con-card-progress-bar" role="progressbar" aria-valuenow="${esc(c.avgCompletion)}" aria-valuemin="0" aria-valuemax="100">
            <div class="con-card-progress-fill" data-target="${esc(c.avgCompletion)}"></div>
          </div>
        </div>
        <div class="con-card-footer">
          <span class="con-card-wards">${wardsList}</span>
          <a href="${link}" class="con-card-cta" aria-label="Explore ${esc(c.name)} constituency">
            Explore <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>`;
  }

  /* --------------------------------------------------
     Build browse grid card
     -------------------------------------------------- */
  function buildBrowseCard(c) {
    const statusClass = c.status === 'active' ? 'con-browse-status--active' : 'con-browse-status--planning';
    const statusLabel = c.status === 'active' ? 'Active' : 'Planning';
    const link = esc(c.link || '#');
    return `
      <div class="con-browse-card">
        <div class="con-browse-card-top">
          <div>
            <div class="con-browse-card-name">${esc(c.name)}</div>
            <div class="con-browse-card-pop">${esc(c.population)} residents</div>
          </div>
          <span class="con-browse-status ${statusClass}">${statusLabel}</span>
        </div>
        <div class="con-browse-stats">
          <div class="con-browse-stat">
            <span class="con-browse-stat-val">${esc(c.projectCount)}</span>
            <span class="con-browse-stat-lbl">Projects</span>
          </div>
          <div class="con-browse-stat">
            <span class="con-browse-stat-val">${Number(c.totalUnits || 0).toLocaleString()}</span>
            <span class="con-browse-stat-lbl">Units</span>
          </div>
          <div class="con-browse-stat">
            <span class="con-browse-stat-val">${(c.wards || []).length}</span>
            <span class="con-browse-stat-lbl">Wards</span>
          </div>
        </div>
        <div class="con-browse-progress">
          <div class="con-browse-progress-meta">
            <span>Avg. Completion</span>
            <span>${esc(c.avgCompletion)}%</span>
          </div>
          <div class="con-browse-bar-track">
            <div class="con-browse-bar-fill" data-target="${esc(c.avgCompletion)}"></div>
          </div>
        </div>
        <div class="con-browse-wards">${(c.wards || []).map(esc).join(' &bull; ')}</div>
        <a href="${link}" class="con-browse-cta">
          Explore ${esc(c.name)} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
      </div>`;
  }

  const ITEMS_PER_PAGE = 3;
  let currentSortKey = 'default';
  let currentPage    = 1;

  /* --------------------------------------------------
     Render browse grid with sort + pagination
     -------------------------------------------------- */
  function renderBrowseGrid(sortKey, page) {
    if (!browseGrid) return;
    currentSortKey = sortKey  || currentSortKey;
    currentPage    = page     || 1;

    let data = [...AHP_DATA.constituencies];
    if (currentSortKey === 'progress') data.sort((a, b) => b.avgCompletion - a.avgCompletion);
    else if (currentSortKey === 'units') data.sort((a, b) => b.totalUnits - a.totalUnits);
    else if (currentSortKey === 'alpha') data.sort((a, b) => a.name.localeCompare(b.name));

    const totalPages = Math.ceil(data.length / ITEMS_PER_PAGE);
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);

    const start   = (currentPage - 1) * ITEMS_PER_PAGE;
    const pageData = data.slice(start, start + ITEMS_PER_PAGE);

    browseGrid.innerHTML = pageData.map(buildBrowseCard).join('');
    animateBars(browseGrid);

    renderPagination(totalPages, data.length);
  }

  /* --------------------------------------------------
     Render pagination controls
     -------------------------------------------------- */
  function renderPagination(totalPages, totalItems) {
    let pg = document.getElementById('conPagination');
    if (!pg) {
      pg = document.createElement('div');
      pg.id = 'conPagination';
      pg.className = 'con-pagination';
      browseGrid.parentNode.insertBefore(pg, browseGrid.nextSibling);
    }

    const start = (currentPage - 1) * ITEMS_PER_PAGE + 1;
    const end   = Math.min(currentPage * ITEMS_PER_PAGE, totalItems);

    let html = `
      <button class="con-page-btn con-page-btn--nav${currentPage === 1 ? ' is-disabled' : ''}" data-pg="${currentPage - 1}"
        ${currentPage === 1 ? 'disabled' : ''} aria-label="Previous page">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev
      </button>`;

    for (let i = 1; i <= totalPages; i++) {
      html += `<button class="con-page-btn${i === currentPage ? ' is-active' : ''}" data-pg="${i}" aria-label="Page ${i}">${i}</button>`;
    }

    html += `
      <span class="con-page-info">Showing ${start}–${end} of ${totalItems}</span>
      <button class="con-page-btn con-page-btn--nav${currentPage === totalPages ? ' is-disabled' : ''}" data-pg="${currentPage + 1}"
        ${currentPage === totalPages ? 'disabled' : ''} aria-label="Next page">
        Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </button>`;

    pg.innerHTML = html;

    pg.querySelectorAll('.con-page-btn[data-pg]').forEach(btn => {
      btn.addEventListener('click', () => {
        const p = parseInt(btn.dataset.pg, 10);
        if (!isNaN(p)) {
          renderBrowseGrid(currentSortKey, p);
          browseGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  /* --------------------------------------------------
     Build county total comparison bars
     -------------------------------------------------- */
  function buildTotalsChart() {
    if (!chartEl) return;
    const rows = AHP_DATA.constituencies.map(c => `
      <div class="county-bar-item">
        <span class="county-bar-name">${c.name}</span>
        <div class="county-bar-track">
          <div class="county-bar-fill" data-target="${c.avgCompletion}"></div>
        </div>
        <span class="county-bar-pct">${c.avgCompletion}%</span>
      </div>`).join('');
    chartEl.innerHTML = `<div class="county-bar-row">${rows}</div>`;
  }

  /* --------------------------------------------------
     Animate progress bars via IntersectionObserver
     -------------------------------------------------- */
  function animateBars(container) {
    const fills = container.querySelectorAll('[data-target]');
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.width = entry.target.getAttribute('data-target') + '%';
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.3 });
      fills.forEach(f => obs.observe(f));
    } else {
      fills.forEach(f => f.style.width = f.getAttribute('data-target') + '%');
    }
  }

  /* --------------------------------------------------
     SVG map hover tooltip
     -------------------------------------------------- */
  function initMapTooltip() {
    const tooltip = document.createElement('div');
    tooltip.className = 'con-map-tooltip';
    document.body.appendChild(tooltip);

    const mapGroups = document.querySelectorAll('.con-path-group');
    mapGroups.forEach(g => {
      g.addEventListener('mouseenter', (e) => {
        const id  = g.dataset.id || '';
        const con = AHP_DATA.getConstituency ? AHP_DATA.getConstituency(id) : null;
        const name   = con ? con.name : (g.getAttribute('aria-label') || id);
        const units  = con ? con.totalUnits.toLocaleString() + ' units' : '';
        const pct    = con ? con.avgCompletion + '% complete' : '';
        tooltip.innerHTML = `<strong>${esc(name)}</strong>${units ? esc(units) + ' &mdash; ' + esc(pct) : ''}`;
        tooltip.classList.add('is-visible');
      });

      g.addEventListener('mousemove', (e) => {
        tooltip.style.left = (e.clientX + 14) + 'px';
        tooltip.style.top  = (e.clientY - 36) + 'px';
      });

      g.addEventListener('mouseleave', () => {
        tooltip.classList.remove('is-visible');
      });
    });
  }

  /* --------------------------------------------------
     Map + card cross-highlight
     -------------------------------------------------- */
  function initMapInteraction() {
    const mapGroups = document.querySelectorAll('.con-path-group');
    const cards     = () => document.querySelectorAll('.con-card');

    function selectConstituency(id) {
      /* Map */
      mapGroups.forEach(g => g.classList.toggle('is-selected', g.dataset.id === id));

      /* Cards */
      cards().forEach(c => {
        const isMatch = c.dataset.id === id;
        c.classList.toggle('is-highlighted', isMatch);
        if (isMatch) {
          c.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });
    }

    mapGroups.forEach(g => {
      g.addEventListener('click', () => selectConstituency(g.dataset.id));
      g.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          selectConstituency(g.dataset.id);
        }
      });
    });

    /* Hovering a card highlights the map too */
    document.addEventListener('mouseover', (e) => {
      const card = e.target.closest('.con-card');
      if (card) {
        mapGroups.forEach(g => g.classList.toggle('is-selected', g.dataset.id === card.dataset.id));
      }
    });

    /* Clicking a card navigates to constituency detail */
    document.addEventListener('click', (e) => {
      const card = e.target.closest('.con-card');
      if (card && !e.target.closest('.con-card-cta')) {
        const con = AHP_DATA.getConstituency(card.dataset.id);
        if (con) window.location.href = con.link;
      }
    });
  }

  /* --------------------------------------------------
     Init
     -------------------------------------------------- */
  function init() {
    if (!cardsPanel) return;

    /* Render cards */
    cardsPanel.innerHTML = AHP_DATA.constituencies.map(buildCard).join('');

    /* Render browse grid (default order) */
    renderBrowseGrid('default');

    /* Sort bar */
    if (sortBar) {
      sortBar.addEventListener('click', (e) => {
        const btn = e.target.closest('.con-sort-btn');
        if (!btn) return;
        sortBar.querySelectorAll('.con-sort-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        renderBrowseGrid(btn.dataset.sort, 1);
      });
    }

    /* Build comparison chart */
    buildTotalsChart();

    /* Animate bars */
    animateBars(cardsPanel);
    if (chartEl) animateBars(chartEl);

    /* Map interaction + tooltip */
    initMapInteraction();
    initMapTooltip();

    /* Check URL param — pre-highlight a constituency */
    const params = new URLSearchParams(window.location.search);
    const preId  = params.get('highlight');
    if (preId) {
      const mapG = document.querySelector(`.con-path-group[data-id="${preId}"]`);
      if (mapG) mapG.classList.add('is-selected');
      const card = document.querySelector(`.con-card[data-id="${preId}"]`);
      if (card) {
        card.classList.add('is-highlighted');
        setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 300);
      }
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
