/* =========================================================
   Trans-Nzoia AHP - Constituencies page
   ========================================================= */
(function () {
  'use strict';

  const cardsPanel = document.getElementById('conCardsPanel');
  const chartEl = document.getElementById('countyTotalsChart');
  const browseGrid = document.getElementById('conBrowseGrid');
  const sortBar = document.getElementById('conGridSort');
  const pageData = window.TNAH && window.TNAH.data ? window.TNAH.data.page() : {};
  const models = pageData.models || {};
  const labels = Object.assign({
    active: 'Active',
    planning: 'Planning',
    residents: 'residents',
    projects: 'Projects',
    units: 'Outputs',
    wards: 'Wards',
    completion: 'Avg. Completion',
    explore: 'Explore',
    previous: 'Prev',
    next: 'Next',
    showing: 'Showing',
    of: 'of'
  }, models.labels || {});

  const constituencies = Array.isArray(models.constituencies)
    ? models.constituencies.map(normalizeConstituency).filter(item => item.id)
    : [];

  const ITEMS_PER_PAGE = 3;
  let currentSortKey = 'default';
  let currentPage = 1;

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizeNumber(value) {
    const number = Number(value || 0);
    return Number.isFinite(number) ? number : 0;
  }

  function normalizeConstituency(item) {
    const slug = String(item.slug || item.id || '').trim();
    const population = item.population || (item.population_raw ? `~${Number(item.population_raw).toLocaleString()}` : '-');
    return {
      id: slug,
      slug,
      name: String(item.name || ''),
      wards: Array.isArray(item.wards) ? item.wards : [],
      population,
      totalUnits: normalizeNumber(item.totalUnits != null ? item.totalUnits : item.total_units),
      avgCompletion: Math.max(0, Math.min(100, normalizeNumber(item.avgCompletion != null ? item.avgCompletion : item.avg_completion))),
      projectCount: normalizeNumber(item.projectCount != null ? item.projectCount : item.project_count),
      status: String(item.status || 'planning') === 'active' ? 'active' : 'planning',
      link: String(item.link || `constituency-detail.php?id=${encodeURIComponent(slug)}`)
    };
  }

  function renderEmptyState() {
    const emptyHtml = `
      <div class="ui-empty con-empty-state">
        <div>
          <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
          <h3>Constituency data is not available yet.</h3>
          <p>Published constituency records will appear here after they are connected in the CMS.</p>
        </div>
      </div>`;

    if (cardsPanel) cardsPanel.innerHTML = emptyHtml;
    if (browseGrid) browseGrid.innerHTML = emptyHtml;
    if (chartEl) chartEl.innerHTML = '<div class="ui-empty con-empty-chart"><div><i class="fa-solid fa-chart-simple" aria-hidden="true"></i><p>No constituency progress data is available.</p></div></div>';
  }

  function getConstituency(id) {
    return constituencies.find(item => item.id === id || item.slug === id) || null;
  }

  function statusLabel(c) {
    return c.status === 'active' ? labels.active : labels.planning;
  }

  function statusClass(prefix, c) {
    return `${prefix}--${c.status === 'active' ? 'active' : 'planning'}`;
  }

  function buildCard(c) {
    const wardsList = c.wards.length ? c.wards.map(ward => `<span class="con-card-ward-chip">${esc(ward)}</span>`).join('') : '<span class="con-card-ward-chip con-card-ward-chip--empty">-</span>';
    const link = esc(c.link);

    return `
      <article class="con-card" data-id="${esc(c.id)}" tabindex="0" aria-label="${esc(labels.explore)} ${esc(c.name)}">
        <div class="con-card-header">
          <div class="con-card-name-wrap">
            <h3 class="con-card-name">${esc(c.name)}</h3>
            <span class="con-card-pop">${esc(c.population)} ${esc(labels.residents)}</span>
          </div>
          <span class="con-card-status ${statusClass('con-card-status', c)}">${esc(statusLabel(c))}</span>
        </div>
        <div class="con-card-stats">
          ${statBlock(c.projectCount, labels.projects)}
          ${statBlock(c.totalUnits.toLocaleString(), labels.units)}
          ${statBlock(c.wards.length, labels.wards)}
        </div>
        <div class="con-card-progress-wrap">
          <div class="con-card-progress-meta">
            <span class="con-card-progress-label">${esc(labels.completion)}</span>
            <span class="con-card-progress-pct">${esc(c.avgCompletion)}%</span>
          </div>
          <div class="con-card-progress-bar" role="progressbar" aria-valuenow="${esc(c.avgCompletion)}" aria-valuemin="0" aria-valuemax="100">
            <div class="con-card-progress-fill" data-target="${esc(c.avgCompletion)}"></div>
          </div>
        </div>
        <div class="con-card-footer">
          <div class="con-card-wards" aria-label="Wards">${wardsList}</div>
          <a href="${link}" class="con-card-cta" aria-label="${esc(labels.explore)} ${esc(c.name)}">
            ${esc(labels.explore)} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </article>`;
  }

  function statBlock(value, label) {
    return `
      <div class="con-card-stat">
        <span class="con-card-stat-val">${esc(value)}</span>
        <span class="con-card-stat-lbl">${esc(label)}</span>
      </div>`;
  }

  function buildBrowseCard(c) {
    const link = esc(c.link);
    const wardText = c.wards.length ? c.wards.map(ward => `<span class="con-browse-wards-chip">${esc(ward)}</span>`).join('') : '<span class="con-browse-wards-chip">-</span>';

    return `
      <article class="con-browse-card">
        <div class="con-browse-card-top">
          <div>
            <h3 class="con-browse-card-name">${esc(c.name)}</h3>
            <div class="con-browse-card-pop">${esc(c.population)} ${esc(labels.residents)}</div>
          </div>
          <span class="con-browse-status ${statusClass('con-browse-status', c)}">${esc(statusLabel(c))}</span>
        </div>
        <div class="con-browse-stats">
          ${browseStat(c.projectCount, labels.projects)}
          ${browseStat(c.totalUnits.toLocaleString(), labels.units)}
          ${browseStat(c.wards.length, labels.wards)}
        </div>
        <div class="con-browse-progress">
          <div class="con-browse-progress-meta">
            <span>${esc(labels.completion)}</span>
            <span>${esc(c.avgCompletion)}%</span>
          </div>
          <div class="con-browse-bar-track">
            <div class="con-browse-bar-fill" data-target="${esc(c.avgCompletion)}"></div>
          </div>
        </div>
        <div class="con-browse-wards" aria-label="Wards">${wardText}</div>
        <a href="${link}" class="con-browse-cta">
          ${esc(labels.explore)} ${esc(c.name)} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
      </article>`;
  }

  function browseStat(value, label) {
    return `
      <div class="con-browse-stat">
        <span class="con-browse-stat-val">${esc(value)}</span>
        <span class="con-browse-stat-lbl">${esc(label)}</span>
      </div>`;
  }

  function sortedData() {
    const data = [...constituencies];
    if (currentSortKey === 'progress') data.sort((a, b) => b.avgCompletion - a.avgCompletion);
    else if (currentSortKey === 'units') data.sort((a, b) => b.totalUnits - a.totalUnits);
    else if (currentSortKey === 'alpha') data.sort((a, b) => a.name.localeCompare(b.name));
    return data;
  }

  function renderBrowseGrid(sortKey, page) {
    if (!browseGrid) return;
    currentSortKey = sortKey || currentSortKey;
    currentPage = page || 1;

    const data = sortedData();
    const totalPages = Math.max(1, Math.ceil(data.length / ITEMS_PER_PAGE));
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);
    const start = (currentPage - 1) * ITEMS_PER_PAGE;

    browseGrid.innerHTML = data.slice(start, start + ITEMS_PER_PAGE).map(buildBrowseCard).join('');
    animateBars(browseGrid);
    renderPagination(totalPages, data.length);
  }

  function renderPagination(totalPages, totalItems) {
    if (!browseGrid || !browseGrid.parentNode) return;

    let pg = document.getElementById('conPagination');
    if (!pg) {
      pg = document.createElement('div');
      pg.id = 'conPagination';
      pg.className = 'con-pagination';
      browseGrid.parentNode.insertBefore(pg, browseGrid.nextSibling);
    }

    const start = totalItems === 0 ? 0 : (currentPage - 1) * ITEMS_PER_PAGE + 1;
    const end = Math.min(currentPage * ITEMS_PER_PAGE, totalItems);
    const buttons = [];

    buttons.push(pageButton(currentPage - 1, labels.previous, 'left', currentPage === 1));
    for (let i = 1; i <= totalPages; i += 1) {
      buttons.push(`<button class="con-page-btn${i === currentPage ? ' is-active' : ''}" data-pg="${i}" aria-label="Page ${i}">${i}</button>`);
    }
    buttons.push(`<span class="con-page-info">${esc(labels.showing)} ${start}-${end} ${esc(labels.of)} ${totalItems}</span>`);
    buttons.push(pageButton(currentPage + 1, labels.next, 'right', currentPage === totalPages));

    pg.innerHTML = buttons.join('');
    pg.querySelectorAll('.con-page-btn[data-pg]').forEach(btn => {
      btn.addEventListener('click', () => {
        const p = parseInt(btn.dataset.pg || '', 10);
        if (Number.isNaN(p)) return;
        renderBrowseGrid(currentSortKey, p);
        browseGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  function pageButton(page, label, icon, disabled) {
    const left = icon === 'left' ? '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i> ' : '';
    const right = icon === 'right' ? ' <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>' : '';
    return `
      <button class="con-page-btn con-page-btn--nav${disabled ? ' is-disabled' : ''}" data-pg="${page}" ${disabled ? 'disabled' : ''} aria-label="${esc(label)}">
        ${left}${esc(label)}${right}
      </button>`;
  }

  function buildTotalsChart() {
    if (!chartEl) return;
    chartEl.innerHTML = `<div class="county-bar-row">${constituencies.map(c => `
      <div class="county-bar-item">
        <span class="county-bar-name">${esc(c.name)}</span>
        <div class="county-bar-track">
          <div class="county-bar-fill" data-target="${esc(c.avgCompletion)}"></div>
        </div>
        <span class="county-bar-pct">${esc(c.avgCompletion)}%</span>
      </div>`).join('')}</div>`;
  }

  function animateBars(container) {
    if (!container) return;
    const fills = container.querySelectorAll('[data-target]');
    const setWidth = fill => {
      fill.style.width = `${Math.max(0, Math.min(100, normalizeNumber(fill.getAttribute('data-target'))))}%`;
    };

    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          setWidth(entry.target);
          obs.unobserve(entry.target);
        });
      }, { threshold: 0.3 });
      fills.forEach(fill => obs.observe(fill));
      return;
    }

    fills.forEach(setWidth);
  }

  function initMapTooltip() {
    const mapGroups = document.querySelectorAll('.con-path-group');
    if (!mapGroups.length) return;

    const tooltip = document.createElement('div');
    tooltip.className = 'con-map-tooltip';
    document.body.appendChild(tooltip);

    mapGroups.forEach(group => {
      group.addEventListener('mouseenter', () => {
        const con = getConstituency(group.dataset.id || '');
        if (!con) return;
        tooltip.innerHTML = `<strong>${esc(con.name)}</strong>${esc(con.totalUnits.toLocaleString())} ${esc(labels.units)} - ${esc(con.avgCompletion)}%`;
        tooltip.classList.add('is-visible');
      });

      group.addEventListener('mousemove', event => {
        tooltip.style.left = `${event.clientX + 14}px`;
        tooltip.style.top = `${event.clientY - 36}px`;
      });

      group.addEventListener('mouseleave', () => {
        tooltip.classList.remove('is-visible');
      });
    });
  }

  function selectConstituency(id, shouldScroll) {
    const mapGroups = document.querySelectorAll('.con-path-group');
    mapGroups.forEach(group => {
      const selected = group.dataset.id === id;
      group.classList.toggle('is-selected', selected);
      group.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });

    document.querySelectorAll('.con-card').forEach(card => {
      const selected = card.dataset.id === id;
      card.classList.toggle('is-highlighted', selected);
      if (selected && shouldScroll) {
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  }

  function initMapInteraction() {
    document.querySelectorAll('.con-path-group').forEach(group => {
      group.setAttribute('aria-pressed', 'false');
      group.addEventListener('click', () => selectConstituency(group.dataset.id || '', true));
      group.addEventListener('keydown', event => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        selectConstituency(group.dataset.id || '', true);
      });
    });

    document.addEventListener('mouseover', event => {
      const card = event.target.closest('.con-card');
      if (card) selectConstituency(card.dataset.id || '', false);
    });

    document.addEventListener('click', event => {
      const card = event.target.closest('.con-card');
      if (!card || event.target.closest('a')) return;
      const con = getConstituency(card.dataset.id || '');
      if (con && con.link) window.location.href = con.link;
    });
  }

  function initSort() {
    if (!sortBar) return;
    sortBar.addEventListener('click', event => {
      const btn = event.target.closest('.con-sort-btn');
      if (!btn) return;
      sortBar.querySelectorAll('.con-sort-btn').forEach(item => item.classList.remove('is-active'));
      btn.classList.add('is-active');
      renderBrowseGrid(btn.dataset.sort || 'default', 1);
    });
  }

  function initHighlightFromUrl() {
    const preId = new URLSearchParams(window.location.search).get('highlight');
    if (!preId) return;
    setTimeout(() => selectConstituency(preId, true), 250);
  }

  function init() {
    if (!cardsPanel) return;
    if (!constituencies.length) {
      renderEmptyState();
      return;
    }

    cardsPanel.innerHTML = constituencies.map(buildCard).join('');
    renderBrowseGrid('default', 1);
    buildTotalsChart();
    animateBars(cardsPanel);
    animateBars(chartEl);
    initSort();
    initMapInteraction();
    initMapTooltip();
    initHighlightFromUrl();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
