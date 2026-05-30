/* =========================================================
   TRANS-NZOIA AHP - Projects Page JS
   Filters, search, sort, pagination and progress animation
   ========================================================= */
(function () {
  'use strict';

  const PAGE_SIZE = 6;

  let activeStatus = 'all';
  let activeCon = 'all';
  let activeSort = 'pct-desc';
  let searchQuery = '';
  let currentPage = 1;
  let cards = [];

  function labels(grid) {
    return {
      resultsPrefix: grid.dataset.resultsPrefix || 'Showing',
      projectSingle: grid.dataset.projectSingle || 'project',
      projectPlural: grid.dataset.projectPlural || 'projects',
      paginationShowing: grid.dataset.paginationShowing || 'Showing',
      paginationOf: grid.dataset.paginationOf || 'of'
    };
  }

  function getFiltered() {
    let list = cards.slice();

    if (activeStatus !== 'all') {
      list = list.filter(card => card.dataset.status === activeStatus);
    }
    if (activeCon !== 'all') {
      list = list.filter(card => card.dataset.constituency === activeCon);
    }
    if (searchQuery) {
      list = list.filter(card => (card.dataset.search || '').includes(searchQuery));
    }

    switch (activeSort) {
      case 'pct-asc':
        list.sort((a, b) => Number(a.dataset.pct || 0) - Number(b.dataset.pct || 0));
        break;
      case 'units-desc':
        list.sort((a, b) => Number(b.dataset.units || 0) - Number(a.dataset.units || 0));
        break;
      case 'name-asc':
        list.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || ''));
        break;
      case 'pct-desc':
      default:
        list.sort((a, b) => Number(b.dataset.pct || 0) - Number(a.dataset.pct || 0));
        break;
    }

    return list;
  }

  function animateProgressBars(scope) {
    const fills = scope.querySelectorAll('.proj-progress-fill');
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const fill = entry.target;
            fill.style.width = `${fill.getAttribute('data-target') || 0}%`;
            obs.unobserve(fill);
          }
        });
      }, { threshold: 0.3 });

      fills.forEach(fill => obs.observe(fill));
      return;
    }

    fills.forEach(fill => {
      fill.style.width = `${fill.getAttribute('data-target') || 0}%`;
    });
  }

  function renderPagination(total, grid) {
    const paginationEl = document.getElementById('projectsPagination');
    if (!paginationEl) return;

    const copy = labels(grid);
    const totalPages = Math.ceil(total / PAGE_SIZE);

    if (totalPages <= 1) {
      paginationEl.hidden = true;
      paginationEl.innerHTML = '';
      return;
    }

    paginationEl.hidden = false;
    const start = (currentPage - 1) * PAGE_SIZE + 1;
    const end = Math.min(currentPage * PAGE_SIZE, total);
    let html = `<span class="ppg-info">${copy.paginationShowing} ${start}-${end} ${copy.paginationOf} ${total} ${total === 1 ? copy.projectSingle : copy.projectPlural}</span>`;

    html += `<button class="ppg-btn ppg-prev" type="button" ${currentPage === 1 ? 'disabled' : ''} aria-label="Previous page">
      <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>`;

    for (let i = 1; i <= totalPages; i += 1) {
      if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
        html += `<button class="ppg-btn${i === currentPage ? ' is-active' : ''}" type="button" data-page="${i}" aria-label="Page ${i}" aria-current="${i === currentPage ? 'page' : 'false'}">${i}</button>`;
      } else if (i === currentPage - 2 || i === currentPage + 2) {
        html += '<span class="ppg-ellipsis" aria-hidden="true">&hellip;</span>';
      }
    }

    html += `<button class="ppg-btn ppg-next" type="button" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Next page">
      <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>`;

    paginationEl.innerHTML = html;

    paginationEl.querySelectorAll('[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        currentPage = parseInt(btn.getAttribute('data-page'), 10);
        render(false);
        scrollToListings();
      });
    });

    const prevBtn = paginationEl.querySelector('.ppg-prev');
    const nextBtn = paginationEl.querySelector('.ppg-next');

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage -= 1;
          render(false);
          scrollToListings();
        }
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage += 1;
          render(false);
          scrollToListings();
        }
      });
    }
  }

  function scrollToListings() {
    const section = document.querySelector('.projects-main');
    if (!section) return;
    window.scrollTo({ top: section.offsetTop - 90, behavior: 'smooth' });
  }

  function render(resetPage) {
    const grid = document.getElementById('projectsGrid');
    const emptyState = document.getElementById('projectsEmpty');
    const countEl = document.getElementById('resultsCount');
    const resetBtn = document.getElementById('resetFilters');

    if (!grid) return;
    if (resetPage !== false) currentPage = 1;

    const copy = labels(grid);
    const filtered = getFiltered();
    const total = filtered.length;
    const hasFilters = activeStatus !== 'all' || activeCon !== 'all' || searchQuery !== '';

    if (resetBtn) resetBtn.hidden = !hasFilters;
    if (countEl) {
      countEl.textContent = `${copy.resultsPrefix} ${total} ${total === 1 ? copy.projectSingle : copy.projectPlural}`;
    }

    cards.forEach(card => {
      card.hidden = true;
      card.classList.remove('is-visible');
    });

    if (total === 0) {
      renderPagination(0, grid);
      if (emptyState) emptyState.hidden = false;
      return;
    }

    if (emptyState) emptyState.hidden = true;

    const start = (currentPage - 1) * PAGE_SIZE;
    const pageCards = filtered.slice(start, start + PAGE_SIZE);
    pageCards.forEach((card, index) => {
      card.hidden = false;
      card.style.animationDelay = `${index * 55}ms`;
      grid.appendChild(card);
    });

    requestAnimationFrame(() => {
      pageCards.forEach(card => card.classList.add('is-visible'));
    });

    animateProgressBars(grid);
    renderPagination(total, grid);
  }

  function resetAll() {
    const searchInput = document.getElementById('projectSearch');
    const sortSelect = document.getElementById('projectSort');

    activeStatus = 'all';
    activeCon = 'all';
    searchQuery = '';
    activeSort = 'pct-desc';

    if (searchInput) searchInput.value = '';
    if (sortSelect) sortSelect.value = 'pct-desc';

    document.querySelectorAll('[data-filter-status]').forEach(btn => {
      btn.classList.toggle('is-active', btn.dataset.filterStatus === 'all');
    });
    document.querySelectorAll('[data-filter-con]').forEach(btn => {
      btn.classList.toggle('is-active', btn.dataset.filterCon === 'all');
    });

    render();
  }

  function init() {
    const grid = document.getElementById('projectsGrid');
    const emptyReset = document.getElementById('emptyReset');
    const resetBtn = document.getElementById('resetFilters');
    const searchInput = document.getElementById('projectSearch');
    const sortSelect = document.getElementById('projectSort');

    if (!grid) return;

    cards = Array.from(grid.querySelectorAll('.proj-card'));

    document.querySelectorAll('[data-filter-status]').forEach(btn => {
      btn.addEventListener('click', () => {
        activeStatus = btn.dataset.filterStatus || 'all';
        document.querySelectorAll('[data-filter-status]').forEach(item => item.classList.toggle('is-active', item === btn));
        render();
      });
    });

    document.querySelectorAll('[data-filter-con]').forEach(btn => {
      btn.addEventListener('click', () => {
        activeCon = btn.dataset.filterCon || 'all';
        document.querySelectorAll('[data-filter-con]').forEach(item => item.classList.toggle('is-active', item === btn));
        render();
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        searchQuery = searchInput.value.toLowerCase().trim();
        render();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        activeSort = sortSelect.value;
        render();
      });
    }

    if (resetBtn) resetBtn.addEventListener('click', resetAll);
    if (emptyReset) emptyReset.addEventListener('click', resetAll);

    const params = new URLSearchParams(window.location.search);
    const conParam = params.get('constituency');
    if (conParam) {
      const escapedCon = window.CSS && CSS.escape ? CSS.escape(conParam) : conParam.replace(/"/g, '\\"');
      const btn = document.querySelector(`[data-filter-con="${escapedCon}"]`);
      if (btn) {
        activeCon = conParam;
        document.querySelectorAll('[data-filter-con]').forEach(item => item.classList.remove('is-active'));
        btn.classList.add('is-active');
      }
    }

    render();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
