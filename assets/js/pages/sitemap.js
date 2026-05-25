/* =========================================================
   TRANS-NZOIA AHP TRACKER — Sitemap Page JS
   ========================================================= */

(function () {
  'use strict';

  /* ---------------------------------------------------------
     1. FADE-UP SCROLL OBSERVER
     --------------------------------------------------------- */
  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const obs = new IntersectionObserver(
      (entries) => entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); obs.unobserve(e.target); }
      }),
      { threshold: 0.06, rootMargin: '0px 0px -40px 0px' }
    );
    els.forEach((el) => obs.observe(el));
  }

  /* ---------------------------------------------------------
     2. HERO ENTRANCE
     --------------------------------------------------------- */
  function initHeroEntrance() {
    const elems = [
      document.querySelector('.sm-hero-eyebrow'),
      document.querySelector('.sm-hero-title'),
      document.querySelector('.sm-hero-sub'),
      document.querySelector('.sm-search-wrap'),
      document.querySelector('.sm-hero-stats'),
    ].filter(Boolean);

    elems.forEach((el) => { el.style.opacity = '0'; el.style.transform = 'translateY(20px)'; });
    requestAnimationFrame(() => {
      elems.forEach((el, i) => {
        setTimeout(() => {
          el.style.transition = 'opacity .55s ease, transform .55s ease';
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }, 100 + i * 100);
      });
    });
  }

  /* ---------------------------------------------------------
     3. LIVE SEARCH — filters cards + table rows
     --------------------------------------------------------- */
  function initSearch() {
    const input     = document.getElementById('smSearch');
    const clearBtn  = document.getElementById('smSearchClear');
    const noResults = document.getElementById('smNoResults');
    const noResQ    = document.getElementById('smNoResultsQuery');
    const clearLink = document.getElementById('smClearSearch');
    if (!input) return;

    /* Card items */
    const cardItems = Array.from(document.querySelectorAll('.sm-card-links > li'));
    const cards     = Array.from(document.querySelectorAll('.sm-card'));
    /* Table rows */
    const tableRows = Array.from(document.querySelectorAll('#smTableBody tr'));

    function doSearch(query) {
      query = query.trim();
      clearBtn.hidden = !query;

      if (!query) {
        cardItems.forEach((li) => li.classList.remove('is-hidden'));
        cards.forEach((c) => c.classList.remove('is-hidden'));
        tableRows.forEach((tr) => tr.classList.remove('is-hidden'));
        if (noResults) noResults.hidden = true;
        return;
      }

      const q = query.toLowerCase();
      let anyCard = false;

      /* Filter card list items */
      cardItems.forEach((li) => {
        const page = (li.dataset.page || '').toLowerCase();
        const desc = (li.dataset.desc || '').toLowerCase();
        const match = page.includes(q) || desc.includes(q);
        li.classList.toggle('is-hidden', !match);
      });

      /* Hide cards that have no visible items */
      cards.forEach((card) => {
        const visible = card.querySelectorAll('.sm-card-links > li:not(.is-hidden)').length;
        card.classList.toggle('is-hidden', visible === 0);
        if (visible > 0) anyCard = true;
      });

      /* Filter table rows */
      tableRows.forEach((tr) => {
        const page    = (tr.dataset.page    || '').toLowerCase();
        const section = (tr.dataset.section || '').toLowerCase();
        const status  = (tr.dataset.status  || '').toLowerCase();
        const match   = page.includes(q) || section.includes(q) || status.includes(q);
        tr.classList.toggle('is-hidden', !match);
      });

      if (noResults) {
        noResults.hidden = anyCard;
        if (!anyCard && noResQ) noResQ.textContent = `"${query}"`;
      }
    }

    input.addEventListener('input', () => doSearch(input.value));
    clearBtn.addEventListener('click', () => { input.value = ''; doSearch(''); input.focus(); });
    if (clearLink) {
      clearLink.addEventListener('click', () => {
        input.value = '';
        doSearch('');
        input.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  }

  /* ---------------------------------------------------------
     4. TABLE SORT
     --------------------------------------------------------- */
  function initTableSort() {
    const headers = document.querySelectorAll('.sm-th.sortable');
    const tbody   = document.getElementById('smTableBody');
    if (!headers.length || !tbody) return;

    let lastCol = -1;
    let asc     = true;

    headers.forEach((th) => {
      th.addEventListener('click', () => {
        const col = parseInt(th.dataset.col, 10);

        /* Toggle direction */
        if (lastCol === col) {
          asc = !asc;
        } else {
          asc = true;
          lastCol = col;
        }

        /* Update ARIA */
        headers.forEach((h) => h.setAttribute('aria-sort', 'none'));
        th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');

        /* Sort rows */
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
          const aCell = a.cells[col] ? a.cells[col].textContent.trim().toLowerCase() : '';
          const bCell = b.cells[col] ? b.cells[col].textContent.trim().toLowerCase() : '';
          return asc ? aCell.localeCompare(bCell) : bCell.localeCompare(aCell);
        });
        rows.forEach((row) => tbody.appendChild(row));
      });
    });
  }

  /* ---------------------------------------------------------
     5. XML COPY TO CLIPBOARD
     --------------------------------------------------------- */
  function initXmlCopy() {
    const copyBtn  = document.getElementById('smCopyXml');
    const codeEl   = document.getElementById('smXmlContent');
    const toast    = document.getElementById('smCopyToast');
    if (!copyBtn || !codeEl) return;

    let toastTimer = null;

    copyBtn.addEventListener('click', async () => {
      const text = codeEl.textContent || codeEl.innerText;
      try {
        await navigator.clipboard.writeText(text);
      } catch (_) {
        /* Fallback for older browsers */
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
      }

      if (toast) {
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 2800);
      }

      /* Button feedback */
      const icon = copyBtn.querySelector('i');
      if (icon) { icon.className = 'fa-solid fa-circle-check'; }
      setTimeout(() => { if (icon) icon.className = 'fa-solid fa-copy'; }, 2000);
    });
  }

  /* ---------------------------------------------------------
     6. BACK TO TOP
     --------------------------------------------------------- */
  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => btn.classList.toggle('is-visible', window.scrollY > 500), { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ---------------------------------------------------------
     INIT
     --------------------------------------------------------- */
  function init() {
    initHeroEntrance();
    initFadeUp();
    initSearch();
    initTableSort();
    initXmlCopy();
    initBackToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
