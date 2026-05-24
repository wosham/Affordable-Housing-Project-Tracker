/* =========================================================
   TRANS-NZOIA AHP — Projects Page JS
   Filters, search, sort, pagination, card render, progress animation
   ========================================================= */
(function () {
  'use strict';

  const PAGE_SIZE = 6;

  let activeStatus = 'all';
  let activeCon    = 'all';
  let activeSort   = 'pct-desc';
  let searchQuery  = '';
  let currentPage  = 1;

  /* --------------------------------------------------
     Card HTML builder
     -------------------------------------------------- */
  function buildCard(p, delay) {
    const statusClass = p.status === 'active' ? 'proj-status-badge--active' : 'proj-status-badge--planning';
    const statusLabel = p.status === 'active' ? 'Active' : 'Planning';
    const imgHtml = p.images && p.images.length
      ? `<img src="${p.images[0]}" alt="${p.name}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
        + `<div class="proj-card-img-placeholder" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></div>`
      : `<div class="proj-card-img-placeholder"><i class="fa-solid fa-building" aria-hidden="true"></i></div>`;

    return `
      <article class="proj-card" role="listitem" style="animation-delay:${delay}ms">
        <div class="proj-card-img">
          ${imgHtml}
          <span class="proj-status-badge ${statusClass}" aria-label="Status: ${statusLabel}">${statusLabel}</span>
          <span class="proj-con-tag">${p.constituencyName}</span>
        </div>
        <div class="proj-card-body">
          <div class="proj-card-meta">
            <span class="proj-ward">${p.ward}</span>
          </div>
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
          <div class="proj-progress" role="progressbar" aria-valuenow="${p.pct}" aria-valuemin="0" aria-valuemax="100" aria-label="${p.pct}% complete">
            <div class="proj-progress-fill" data-target="${p.pct}"></div>
          </div>
          <div class="proj-milestone">
            <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
            <span>${p.milestone}</span>
          </div>
          <div class="proj-card-footer">
            <span class="proj-contractor" title="${p.contractor}">${p.contractor}</span>
            <a href="${p.link}" class="proj-cta" aria-label="View details for ${p.name}">
              View Project <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </article>`;
  }

  /* --------------------------------------------------
     Filter + sort logic
     -------------------------------------------------- */
  function getFiltered() {
    let list = [...AHP_DATA.projects];

    if (activeStatus !== 'all') list = list.filter(p => p.status === activeStatus);
    if (activeCon    !== 'all') list = list.filter(p => p.constituency === activeCon);
    if (searchQuery)            list = list.filter(p =>
      p.name.toLowerCase().includes(searchQuery) ||
      p.constituencyName.toLowerCase().includes(searchQuery) ||
      p.ward.toLowerCase().includes(searchQuery) ||
      p.contractor.toLowerCase().includes(searchQuery)
    );

    switch (activeSort) {
      case 'pct-desc':   list.sort((a,b) => b.pct   - a.pct);   break;
      case 'pct-asc':    list.sort((a,b) => a.pct   - b.pct);   break;
      case 'units-desc': list.sort((a,b) => b.units - a.units); break;
      case 'name-asc':   list.sort((a,b) => a.name.localeCompare(b.name)); break;
    }
    return list;
  }

  /* --------------------------------------------------
     Pagination renderer
     -------------------------------------------------- */
  function renderPagination(total) {
    const paginationEl = document.getElementById('projectsPagination');
    if (!paginationEl) return;

    const totalPages = Math.ceil(total / PAGE_SIZE);

    if (totalPages <= 1) {
      paginationEl.hidden = true;
      return;
    }

    paginationEl.hidden = false;

    const start = (currentPage - 1) * PAGE_SIZE + 1;
    const end   = Math.min(currentPage * PAGE_SIZE, total);

    let html = `<span class="ppg-info">Showing ${start}–${end} of ${total} projects</span>`;

    /* Prev button */
    html += `<button class="ppg-btn ppg-prev" ${currentPage === 1 ? 'disabled' : ''} aria-label="Previous page">
      <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>`;

    /* Page number buttons */
    for (let i = 1; i <= totalPages; i++) {
      if (
        i === 1 || i === totalPages ||
        (i >= currentPage - 1 && i <= currentPage + 1)
      ) {
        html += `<button class="ppg-btn${i === currentPage ? ' is-active' : ''}" data-page="${i}" aria-label="Page ${i}" aria-current="${i === currentPage ? 'page' : 'false'}">${i}</button>`;
      } else if (i === currentPage - 2 || i === currentPage + 2) {
        html += `<span class="ppg-ellipsis" aria-hidden="true">&hellip;</span>`;
      }
    }

    /* Next button */
    html += `<button class="ppg-btn ppg-next" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Next page">
      <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>`;

    paginationEl.innerHTML = html;

    /* Wire pagination buttons */
    paginationEl.querySelectorAll('[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        currentPage = parseInt(btn.getAttribute('data-page'), 10);
        render(false);
        window.scrollTo({ top: document.querySelector('.projects-main').offsetTop - 90, behavior: 'smooth' });
      });
    });

    const prevBtn = paginationEl.querySelector('.ppg-prev');
    const nextBtn = paginationEl.querySelector('.ppg-next');

    if (prevBtn) prevBtn.addEventListener('click', () => {
      if (currentPage > 1) { currentPage--; render(false); window.scrollTo({ top: document.querySelector('.projects-main').offsetTop - 90, behavior: 'smooth' }); }
    });
    if (nextBtn) nextBtn.addEventListener('click', () => {
      if (currentPage < totalPages) { currentPage++; render(false); window.scrollTo({ top: document.querySelector('.projects-main').offsetTop - 90, behavior: 'smooth' }); }
    });
  }

  /* --------------------------------------------------
     Progress bar animation
     -------------------------------------------------- */
  function animateProgressBars() {
    const grid  = document.getElementById('projectsGrid');
    if (!grid) return;
    const fills = grid.querySelectorAll('.proj-progress-fill');
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const fill = entry.target;
            fill.style.width = fill.getAttribute('data-target') + '%';
            obs.unobserve(fill);
          }
        });
      }, { threshold: 0.3 });
      fills.forEach(f => obs.observe(f));
    } else {
      fills.forEach(f => f.style.width = f.getAttribute('data-target') + '%');
    }
  }

  /* --------------------------------------------------
     Main render
     resetPage: whether to go back to page 1 (true on filter change)
     -------------------------------------------------- */
  function render(resetPage) {
    const grid       = document.getElementById('projectsGrid');
    const emptyState = document.getElementById('projectsEmpty');
    const countEl    = document.getElementById('resultsCount');
    const resetBtn   = document.getElementById('resetFilters');

    if (!grid) return;

    if (resetPage !== false) currentPage = 1;

    const filtered   = getFiltered();
    const total      = filtered.length;
    const hasFilters = activeStatus !== 'all' || activeCon !== 'all' || searchQuery;

    if (resetBtn) resetBtn.hidden = !hasFilters;
    if (countEl)  countEl.textContent = `Showing ${total} project${total !== 1 ? 's' : ''}`;

    if (total === 0) {
      grid.innerHTML = '';
      renderPagination(0);
      if (emptyState) emptyState.hidden = false;
      return;
    }

    if (emptyState) emptyState.hidden = true;

    /* Slice for current page */
    const start = (currentPage - 1) * PAGE_SIZE;
    const page  = filtered.slice(start, start + PAGE_SIZE);

    grid.innerHTML = page.map((p, i) => buildCard(p, i * 55)).join('');

    /* Animate cards in */
    requestAnimationFrame(() => {
      grid.querySelectorAll('.proj-card').forEach(card => card.classList.add('is-visible'));
    });

    animateProgressBars();
    renderPagination(total);
  }

  /* --------------------------------------------------
     Reset all filters
     -------------------------------------------------- */
  function resetAll() {
    const searchInput = document.getElementById('projectSearch');
    const sortSelect  = document.getElementById('projectSort');

    activeStatus = 'all';
    activeCon    = 'all';
    searchQuery  = '';
    activeSort   = 'pct-desc';

    if (searchInput) searchInput.value = '';
    if (sortSelect)  sortSelect.value  = 'pct-desc';

    document.querySelectorAll('[data-filter-status]').forEach(btn => {
      btn.classList.toggle('is-active', btn.dataset.filterStatus === 'all');
    });
    document.querySelectorAll('[data-filter-con]').forEach(btn => {
      btn.classList.toggle('is-active', btn.dataset.filterCon === 'all');
    });

    render();
  }

  /* --------------------------------------------------
     Event wiring + init
     -------------------------------------------------- */
  function init() {
    const grid        = document.getElementById('projectsGrid');
    const emptyReset  = document.getElementById('emptyReset');
    const resetBtn    = document.getElementById('resetFilters');
    const searchInput = document.getElementById('projectSearch');
    const sortSelect  = document.getElementById('projectSort');

    if (!grid) return;

    /* Status filter chips */
    document.querySelectorAll('[data-filter-status]').forEach(btn => {
      btn.addEventListener('click', () => {
        activeStatus = btn.dataset.filterStatus;
        document.querySelectorAll('[data-filter-status]').forEach(b => b.classList.toggle('is-active', b === btn));
        render();
      });
    });

    /* Constituency filter chips */
    document.querySelectorAll('[data-filter-con]').forEach(btn => {
      btn.addEventListener('click', () => {
        activeCon = btn.dataset.filterCon;
        document.querySelectorAll('[data-filter-con]').forEach(b => b.classList.toggle('is-active', b === btn));
        render();
      });
    });

    /* Search */
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        searchQuery = searchInput.value.toLowerCase().trim();
        render();
      });
    }

    /* Sort */
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        activeSort = sortSelect.value;
        render();
      });
    }

    /* Reset buttons */
    if (resetBtn)   resetBtn.addEventListener('click', resetAll);
    if (emptyReset) emptyReset.addEventListener('click', resetAll);

    /* URL param pre-filter */
    const params   = new URLSearchParams(window.location.search);
    const conParam = params.get('constituency');
    if (conParam) {
      activeCon = conParam;
      const btn = document.querySelector(`[data-filter-con="${conParam}"]`);
      if (btn) {
        document.querySelectorAll('[data-filter-con]').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
      }
    }

    render();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
