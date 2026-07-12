/* =========================================================
   TRANS-NZOIA AHP - Projects Page JS
   Database-backed filters, search, sorting and pagination
   ========================================================= */
(function () {
  'use strict';

  const PAGE_SIZE = 6;
  const state = {
    status: 'all',
    constituency: 'all',
    category: 'all',
    sort: 'pct-desc',
    search: '',
    page: 1,
    loading: false,
    apiEnabled: true,
  };

  let cards = [];
  let searchTimer = null;

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

  function labels(grid) {
    return {
      resultsPrefix: grid.dataset.resultsPrefix || 'Showing',
      projectSingle: grid.dataset.projectSingle || 'project',
      projectPlural: grid.dataset.projectPlural || 'projects',
      unitsLabel: grid.dataset.unitsLabel || 'Units',
      completeLabel: grid.dataset.completeLabel || 'Complete',
      viewLabel: grid.dataset.viewLabel || 'View Project',
      paginationShowing: grid.dataset.paginationShowing || 'Showing',
      paginationOf: grid.dataset.paginationOf || 'of',
    };
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

  function formatNumber(value) {
    return Number(value || 0).toLocaleString();
  }

  function formatQuarter(value) {
    const raw = String(value || '').trim();
    if (!raw || raw === '0000-00-00') return 'TBD';
    const date = new Date(raw + 'T00:00:00');
    if (Number.isNaN(date.getTime())) return raw;
    return `Q${Math.ceil((date.getMonth() + 1) / 3)} ${date.getFullYear()}`;
  }

  function projectUrl(project) {
    return `project-detail.php?id=${encodeURIComponent(project.slug || project.id || '')}`;
  }

  function statusClass(project) {
    return project.public_status === 'active' ? 'active' : 'planning';
  }

  function cardHtml(project, index) {
    const copy = labels($('#projectsGrid') || document.documentElement);
    const pct = Math.max(0, Math.min(100, Number(project.pct_complete || 0)));
    const image = String(project.hero_image || '').trim();
    const name = project.name || 'Project';
    const location = project.ward_name || project.location_label || '';
    const milestone = project.current_milestone || 'Project details pending';
    const contractor = project.contractor_name || '';
    const status = statusClass(project);
    const outputLabel = project.output_label || copy.unitsLabel;

    return `
      <article class="proj-card is-visible"
               role="listitem"
               style="animation-delay:${(index % 6) * 55}ms"
               data-status="${escapeHtml(status)}"
               data-constituency="${escapeHtml(project.constituency_slug || '')}"
               data-category="${escapeHtml(project.category_slug || '')}"
               data-name="${escapeHtml(String(name).toLowerCase())}"
               data-units="${Number(project.units || 0)}"
               data-pct="${pct}"
               data-search="${escapeHtml([name, project.constituency_name, location, contractor, milestone].join(' ').toLowerCase())}">
        <div class="proj-card-img">
          ${image
            ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(name)}" loading="lazy" decoding="async" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
               <div class="proj-card-img-placeholder" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></div>`
            : '<div class="proj-card-img-placeholder"><i class="fa-solid fa-building" aria-hidden="true"></i></div>'}
          <span class="proj-status-badge proj-status-badge--${escapeHtml(status)}">${escapeHtml(project.status_label || status)}</span>
          <span class="proj-con-tag">${escapeHtml(project.constituency_name || '')}</span>
        </div>
        <div class="proj-card-body">
          <div class="proj-card-meta">
            <span class="proj-category">${escapeHtml(project.category_name || 'Project')}</span>
            <span class="proj-ward">${escapeHtml(location)}</span>
          </div>
          <h3 class="proj-card-title">${escapeHtml(name)}</h3>
          <div class="proj-card-stats">
            <div class="proj-stat">
              <span class="proj-stat-val">${formatNumber(project.units)}</span>
              <span class="proj-stat-lbl">${escapeHtml(outputLabel)}</span>
            </div>
            <div class="proj-stat">
              <span class="proj-stat-val">${pct}%</span>
              <span class="proj-stat-lbl">${escapeHtml(copy.completeLabel)}</span>
            </div>
            <div class="proj-stat proj-stat--delivery">
              <span class="proj-stat-val proj-stat-val--date">${escapeHtml(delivery)}</span>
              <span class="proj-stat-lbl">Est. Delivery</span>
            </div>
          </div>
          <div class="proj-progress" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" aria-label="${pct}% complete">
            <div class="proj-progress-fill" data-target="${pct}" style="width:${pct}%"></div>
          </div>
          <div class="proj-milestone">
            <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
            <span>${escapeHtml(milestone)}</span>
          </div>
          <div class="proj-card-footer">
            <span class="proj-contractor" title="${escapeHtml(contractor)}">${escapeHtml(contractor)}</span>
            <a href="${escapeHtml(projectUrl(project))}" class="proj-cta" aria-label="View details for ${escapeHtml(name)}">
              ${escapeHtml(copy.viewLabel)} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </article>`;
  }

  function currentParams() {
    const params = new URLSearchParams();
    if (state.search) params.set('search', state.search);
    if (state.status !== 'all') params.set('status', state.status);
    if (state.constituency !== 'all') params.set('constituency', state.constituency);
    if (state.category !== 'all') params.set('category', state.category);
    if (state.sort !== 'pct-desc') params.set('sort', state.sort);
    params.set('page', String(state.page));
    params.set('limit', String(PAGE_SIZE));
    return params;
  }

  function syncUrl() {
    const params = currentParams();
    params.delete('limit');
    if (state.page === 1) params.delete('page');
    const query = params.toString();
    window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}`);
  }

  function setLoading(isLoading) {
    const grid = $('#projectsGrid');
    const section = $('.projects-main');
    state.loading = isLoading;
    if (grid) grid.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    if (section) section.classList.toggle('is-loading', isLoading);
  }

  function setError(message = '') {
    const grid = $('#projectsGrid');
    if (!grid || !grid.parentNode) return;

    let errorEl = $('#projectsError');
    if (!errorEl) {
      errorEl = document.createElement('div');
      errorEl.id = 'projectsError';
      errorEl.className = 'ui-error projects-error';
      errorEl.hidden = true;
      grid.parentNode.insertBefore(errorEl, grid);
    }

    if (!message) {
      errorEl.hidden = true;
      errorEl.replaceChildren();
      return;
    }

    const wrap = document.createElement('div');
    const icon = document.createElement('i');
    const title = document.createElement('h3');
    const text = document.createElement('p');
    icon.className = 'fa-solid fa-triangle-exclamation';
    icon.setAttribute('aria-hidden', 'true');
    title.textContent = 'Live project filters are temporarily unavailable.';
    text.textContent = message;
    wrap.append(icon, title, text);
    errorEl.replaceChildren(wrap);
    errorEl.hidden = false;
  }

  function updateStats(stats = {}) {
    const totalProjects = $('#statTotalProjects');
    const totalUnits = $('#statTotalUnits');
    const activeProjects = $('#statActiveProjects');
    const constituencies = $('#statConstituencies');

    if (totalProjects && stats.total_projects !== undefined) totalProjects.textContent = formatNumber(stats.total_projects);
    if (totalUnits && stats.total_units !== undefined) totalUnits.textContent = formatNumber(stats.total_units);
    if (activeProjects && stats.active_projects !== undefined) activeProjects.textContent = formatNumber(stats.active_projects);
    if (constituencies && stats.constituencies !== undefined) constituencies.textContent = formatNumber(stats.constituencies);
  }

  async function fetchProjects() {
    const grid = $('#projectsGrid');
    if (!grid || !grid.dataset.apiUrl || !window.TNAH?.request) {
      state.apiEnabled = false;
      renderDomFallback();
      return;
    }

    setLoading(true);
    try {
      const response = await window.TNAH.request(`${grid.dataset.apiUrl}?${currentParams().toString()}`, {
        method: 'GET',
      });
      if (!response || response.success !== true) {
        throw new Error(response?.message || 'Unable to load projects.');
      }
      setError('');
      renderApiResults(response);
      syncUrl();
    } catch (error) {
      state.apiEnabled = false;
      setError('Showing the project records already loaded on this page.');
      renderDomFallback();
      if (window.TNAH?.toast) {
        window.TNAH.toast('Project filters are using the current page data.', 'info');
      }
    } finally {
      setLoading(false);
    }
  }

  function renderApiResults(response) {
    const grid = $('#projectsGrid');
    const emptyState = $('#projectsEmpty');
    if (!grid) return;

    const envelope = response.data && !Array.isArray(response.data) ? response.data : {};
    const data = Array.isArray(envelope.items) ? envelope.items : (Array.isArray(response.data) ? response.data : []);
    grid.innerHTML = data.map(cardHtml).join('');
    cards = $$('.proj-card', grid);

    if (emptyState) emptyState.hidden = data.length > 0;
    updateCount(envelope.pagination?.total || response.pagination?.total || data.length);
    updateStats(envelope.stats || response.stats || {});
    renderPagination(envelope.pagination || response.pagination || { page: 1, total_pages: 1, total: data.length }, grid, true);
  }

  function getFilteredDomCards() {
    let list = cards.slice();

    if (state.status !== 'all') list = list.filter((card) => card.dataset.status === state.status);
    if (state.constituency !== 'all') list = list.filter((card) => card.dataset.constituency === state.constituency);
    if (state.category !== 'all') list = list.filter((card) => card.dataset.category === state.category);
    if (state.search) list = list.filter((card) => (card.dataset.search || '').includes(state.search));

    switch (state.sort) {
      case 'pct-asc':
        list.sort((a, b) => Number(a.dataset.pct || 0) - Number(b.dataset.pct || 0));
        break;
      case 'units-desc':
        list.sort((a, b) => Number(b.dataset.units || 0) - Number(a.dataset.units || 0));
        break;
      case 'name-asc':
        list.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || ''));
        break;
      default:
        list.sort((a, b) => Number(b.dataset.pct || 0) - Number(a.dataset.pct || 0));
        break;
    }

    return list;
  }

  function renderDomFallback(resetPage = false) {
    const grid = $('#projectsGrid');
    const emptyState = $('#projectsEmpty');
    if (!grid) return;
    if (resetPage) state.page = 1;

    const filtered = getFilteredDomCards();
    const total = filtered.length;
    cards.forEach((card) => {
      card.hidden = true;
      card.classList.remove('is-visible');
    });

    updateCount(total);
    if (emptyState) emptyState.hidden = total > 0;

    const start = (state.page - 1) * PAGE_SIZE;
    filtered.slice(start, start + PAGE_SIZE).forEach((card, index) => {
      card.hidden = false;
      card.style.animationDelay = `${index * 55}ms`;
      grid.appendChild(card);
      requestAnimationFrame(() => card.classList.add('is-visible'));
    });

    renderPagination({
      page: state.page,
      total,
      total_pages: Math.max(1, Math.ceil(total / PAGE_SIZE)),
    }, grid, false);
  }

  function updateCount(total) {
    const grid = $('#projectsGrid');
    const countEl = $('#resultsCount');
    const resetBtn = $('#resetFilters');
    if (!grid) return;
    const copy = labels(grid);
    const hasFilters = state.status !== 'all' || state.constituency !== 'all' || state.category !== 'all' || state.search !== '';

    if (countEl) {
      countEl.textContent = `${copy.resultsPrefix} ${total} ${total === 1 ? copy.projectSingle : copy.projectPlural}`;
    }
    if (resetBtn) resetBtn.hidden = !hasFilters;
  }

  function renderPagination(pagination, grid, useApi) {
    const paginationEl = $('#projectsPagination');
    if (!paginationEl) return;

    const total = Number(pagination.total || 0);
    const totalPages = Number(pagination.total_pages || 1);
    const page = Number(pagination.page || state.page || 1);
    const copy = labels(grid);

    if (totalPages <= 1) {
      paginationEl.hidden = true;
      paginationEl.innerHTML = '';
      return;
    }

    paginationEl.hidden = false;
    const start = (page - 1) * PAGE_SIZE + 1;
    const end = Math.min(page * PAGE_SIZE, total);
    let html = `<span class="ppg-info">${copy.paginationShowing} ${start}-${end} ${copy.paginationOf} ${total} ${total === 1 ? copy.projectSingle : copy.projectPlural}</span>`;
    html += `<button class="ppg-btn ppg-prev" type="button" ${page === 1 ? 'disabled' : ''} aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>`;

    for (let i = 1; i <= totalPages; i += 1) {
      if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
        html += `<button class="ppg-btn${i === page ? ' is-active' : ''}" type="button" data-page="${i}" aria-label="Page ${i}" aria-current="${i === page ? 'page' : 'false'}">${i}</button>`;
      } else if (i === page - 2 || i === page + 2) {
        html += '<span class="ppg-ellipsis" aria-hidden="true">&hellip;</span>';
      }
    }

    html += `<button class="ppg-btn ppg-next" type="button" ${page === totalPages ? 'disabled' : ''} aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>`;
    paginationEl.innerHTML = html;

    $$('[data-page]', paginationEl).forEach((button) => {
      button.addEventListener('click', () => {
        state.page = Number(button.dataset.page || 1);
        useApi ? fetchProjects() : renderDomFallback(false);
        scrollToListings();
      });
    });

    $('.ppg-prev', paginationEl)?.addEventListener('click', () => {
      if (state.page > 1) {
        state.page -= 1;
        useApi ? fetchProjects() : renderDomFallback(false);
        scrollToListings();
      }
    });

    $('.ppg-next', paginationEl)?.addEventListener('click', () => {
      if (state.page < totalPages) {
        state.page += 1;
        useApi ? fetchProjects() : renderDomFallback(false);
        scrollToListings();
      }
    });
  }

  function scrollToListings() {
    const section = $('.projects-main');
    if (!section) return;
    window.scrollTo({ top: section.offsetTop - 90, behavior: 'smooth' });
  }

  function runSearch(value) {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      state.search = String(value || '').toLowerCase().trim();
      state.page = 1;
      state.apiEnabled ? fetchProjects() : renderDomFallback(true);
    }, 220);
  }

  function setActive(selector, dataKey, value) {
    $$(selector).forEach((button) => {
      button.classList.toggle('is-active', button.dataset[dataKey] === value);
    });
  }

  function resetAll() {
    const searchInput = $('#projectSearch');
    const sortSelect = $('#projectSort');
    state.status = 'all';
    state.constituency = 'all';
    state.category = 'all';
    state.search = '';
    state.sort = 'pct-desc';
    state.page = 1;

    if (searchInput) searchInput.value = '';
    if (sortSelect) sortSelect.value = 'pct-desc';
    setActive('[data-filter-status]', 'filterStatus', 'all');
    setActive('[data-filter-con]', 'filterCon', 'all');
    setActive('[data-filter-category]', 'filterCategory', 'all');
    state.apiEnabled ? fetchProjects() : renderDomFallback(true);
  }

  function hydrateFromUrl() {
    const params = new URLSearchParams(window.location.search);
    state.search = String(params.get('search') || '').toLowerCase().trim();
    state.status = params.get('status') || 'all';
    state.constituency = params.get('constituency') || 'all';
    state.category = params.get('category') || 'all';
    state.sort = params.get('sort') || 'pct-desc';
    state.page = Math.max(1, Number(params.get('page') || 1));

    const searchInput = $('#projectSearch');
    const sortSelect = $('#projectSort');
    if (searchInput) searchInput.value = state.search;
    if (sortSelect) sortSelect.value = state.sort;
    setActive('[data-filter-status]', 'filterStatus', state.status);
    setActive('[data-filter-con]', 'filterCon', state.constituency);
    setActive('[data-filter-category]', 'filterCategory', state.category);
  }

  function bindControls() {
    $$('[data-filter-status]').forEach((button) => {
      button.addEventListener('click', () => {
        state.status = button.dataset.filterStatus || 'all';
        state.page = 1;
        setActive('[data-filter-status]', 'filterStatus', state.status);
        state.apiEnabled ? fetchProjects() : renderDomFallback(true);
      });
    });

    $$('[data-filter-con]').forEach((button) => {
      button.addEventListener('click', () => {
        state.constituency = button.dataset.filterCon || 'all';
        state.page = 1;
        setActive('[data-filter-con]', 'filterCon', state.constituency);
        state.apiEnabled ? fetchProjects() : renderDomFallback(true);
      });
    });

    $$('[data-filter-category]').forEach((button) => {
      button.addEventListener('click', () => {
        state.category = button.dataset.filterCategory || 'all';
        state.page = 1;
        setActive('[data-filter-category]', 'filterCategory', state.category);
        state.apiEnabled ? fetchProjects() : renderDomFallback(true);
      });
    });

    $('#projectSearch')?.addEventListener('input', (event) => runSearch(event.target.value));
    $('#projectSort')?.addEventListener('change', (event) => {
      state.sort = event.target.value;
      state.page = 1;
      state.apiEnabled ? fetchProjects() : renderDomFallback(true);
    });
    $('#resetFilters')?.addEventListener('click', resetAll);
    $('#emptyReset')?.addEventListener('click', resetAll);
  }

  function init() {
    const grid = $('#projectsGrid');
    if (!grid) return;
    cards = $$('.proj-card', grid);
    hydrateFromUrl();
    bindControls();
    fetchProjects();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
