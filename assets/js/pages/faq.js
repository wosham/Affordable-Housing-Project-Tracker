/* =========================================================
   TRANS-NZOIA AHP TRACKER — FAQ Page JS
   ========================================================= */

(function () {
  'use strict';

  /* ---------------------------------------------------------
     1. FADE-UP SCROLL OBSERVER
     --------------------------------------------------------- */
  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.06, rootMargin: '0px 0px -40px 0px' }
    );
    els.forEach((el) => observer.observe(el));
  }

  /* ---------------------------------------------------------
     2. HERO ENTRANCE
     --------------------------------------------------------- */
  function initHeroEntrance() {
    const elements = [
      document.querySelector('.fq-hero-eyebrow'),
      document.querySelector('.fq-hero-title'),
      document.querySelector('.fq-hero-sub'),
      document.querySelector('.fq-search-wrap'),
      document.querySelector('.fq-hero-kpi-strip'),
    ].filter(Boolean);

    elements.forEach((el) => {
      el.style.opacity   = '0';
      el.style.transform = 'translateY(24px)';
    });

    requestAnimationFrame(() => {
      elements.forEach((el, i) => {
        setTimeout(() => {
          el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
          el.style.opacity    = '1';
          el.style.transform  = 'translateY(0)';
        }, 120 + i * 110);
      });
    });
  }

  /* ---------------------------------------------------------
     3. ACCORDION
     --------------------------------------------------------- */
  function initAccordion() {
    const triggers = document.querySelectorAll('.fq-trigger');
    if (!triggers.length) return;

    function openItem(trigger) {
      const panel = document.getElementById(trigger.getAttribute('aria-controls'));
      if (!panel) return;
      trigger.setAttribute('aria-expanded', 'true');
      panel.style.height = panel.scrollHeight + 'px';
    }

    function closeItem(trigger) {
      const panel = document.getElementById(trigger.getAttribute('aria-controls'));
      if (!panel) return;
      trigger.setAttribute('aria-expanded', 'false');
      panel.style.height = '0';
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener('click', () => {
        const isOpen = trigger.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
          closeItem(trigger);
          return;
        }

        /* Close all other open items in the same accordion */
        const accordion = trigger.closest('.fq-accordion');
        if (accordion) {
          accordion.querySelectorAll('.fq-trigger[aria-expanded="true"]').forEach((t) => {
            if (t !== trigger) closeItem(t);
          });
        }

        openItem(trigger);
      });

      /* Keyboard: Space or Enter open/close */
      trigger.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          trigger.click();
        }
      });
    });
  }

  /* ---------------------------------------------------------
     4. LIVE SEARCH
     --------------------------------------------------------- */
  function initSearch() {
    const input      = document.getElementById('faqSearch');
    const clearBtn   = document.getElementById('faqSearchClear');
    const noResults  = document.getElementById('fqNoResults');
    const noResultsQ = document.getElementById('fqNoResultsQuery');
    const clearSearch = document.getElementById('fqClearSearch');
    if (!input) return;

    const allItems    = Array.from(document.querySelectorAll('.fq-item'));
    const allGroups   = Array.from(document.querySelectorAll('.fq-group'));
    const allTriggers = Array.from(document.querySelectorAll('.fq-trigger'));

    /* Store original text for restoration */
    allTriggers.forEach((t) => {
      const span = t.querySelector('.fq-q-text');
      if (span) t._originalText = span.textContent;
    });

    function escapeRegex(str) {
      return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlightText(text, query) {
      if (!query) return text;
      const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
      return text.replace(regex, '<mark>$1</mark>');
    }

    function doSearch(query) {
      query = query.trim();
      clearBtn.hidden = !query;

      if (!query) {
        /* Restore everything */
        allItems.forEach((item) => {
          item.classList.remove('is-hidden');
          const span = item.querySelector('.fq-q-text');
          const trigger = item.querySelector('.fq-trigger');
          if (span && trigger) span.innerHTML = trigger._originalText || span.textContent;
        });
        allGroups.forEach((g) => g.classList.remove('is-hidden'));
        if (noResults) noResults.hidden = true;
        return;
      }

      const queryLower = query.toLowerCase();
      let anyVisible   = false;

      allItems.forEach((item) => {
        const trigger  = item.querySelector('.fq-trigger');
        const qText    = trigger ? (trigger._originalText || '') : '';
        const panel    = item.querySelector('.fq-panel-inner');
        const bodyText = panel ? panel.textContent : '';
        const matches  = qText.toLowerCase().includes(queryLower) || bodyText.toLowerCase().includes(queryLower);

        if (matches) {
          item.classList.remove('is-hidden');
          anyVisible = true;
          /* Highlight in question text */
          const span = trigger ? trigger.querySelector('.fq-q-text') : null;
          if (span) span.innerHTML = highlightText(qText, query);
        } else {
          item.classList.add('is-hidden');
          /* Remove highlight */
          const span = trigger ? trigger.querySelector('.fq-q-text') : null;
          if (span) span.innerHTML = qText;
        }
      });

      /* Hide groups with no visible items */
      allGroups.forEach((group) => {
        const visibleInGroup = group.querySelectorAll('.fq-item:not(.is-hidden)').length;
        group.classList.toggle('is-hidden', visibleInGroup === 0);
      });

      if (noResults) {
        noResults.hidden = anyVisible;
        if (!anyVisible && noResultsQ) noResultsQ.textContent = `"${query}"`;
      }
    }

    input.addEventListener('input', () => doSearch(input.value));
    clearBtn.addEventListener('click', () => {
      input.value = '';
      doSearch('');
      input.focus();
    });
    if (clearSearch) {
      clearSearch.addEventListener('click', () => {
        input.value = '';
        doSearch('');
        input.focus();
        window.scrollTo({ top: input.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
      });
    }
  }

  /* ---------------------------------------------------------
     5. CATEGORY TABS — filter groups
     --------------------------------------------------------- */
  function initCategoryTabs() {
    const tabs    = document.querySelectorAll('.fq-cat-tab');
    const groups  = document.querySelectorAll('.fq-group');
    if (!tabs.length) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const cat = tab.dataset.cat;

        /* Update tab states */
        tabs.forEach((t) => {
          t.classList.toggle('is-active', t === tab);
          t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
        });

        /* Show/hide groups */
        groups.forEach((group) => {
          const show = cat === 'all' || group.dataset.group === cat;
          group.classList.toggle('is-hidden', !show);
        });

        /* Scroll to first visible group */
        const firstVisible = Array.from(groups).find((g) => !g.classList.contains('is-hidden'));
        if (firstVisible && cat !== 'all') {
          setTimeout(() => {
            firstVisible.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }, 50);
        }
      });
    });
  }

  /* ---------------------------------------------------------
     6. POPULAR PILLS — jump to question + open it
     --------------------------------------------------------- */
  function initPopularPills() {
    const pills = document.querySelectorAll('.fq-pill');
    pills.forEach((pill) => {
      pill.addEventListener('click', () => {
        const targetId = pill.dataset.target;
        if (!targetId) return;
        const item = document.getElementById(targetId);
        if (!item) return;

        /* Make sure parent group is visible */
        const group = item.closest('.fq-group');
        if (group) {
          document.querySelectorAll('.fq-group').forEach((g) => {
            g.classList.toggle('is-hidden', g !== group);
          });
          /* Set "All" tab active — we are jumping to a specific item */
          document.querySelectorAll('.fq-cat-tab').forEach((t) => {
            const match = group.dataset.group === t.dataset.cat;
            t.classList.toggle('is-active', match);
            t.setAttribute('aria-selected', match ? 'true' : 'false');
          });
        }

        /* Open the accordion item */
        const trigger = item.querySelector('.fq-trigger');
        if (trigger && trigger.getAttribute('aria-expanded') !== 'true') {
          trigger.click();
        }

        /* Scroll to item */
        setTimeout(() => {
          item.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);

        /* Pulse active pill */
        pills.forEach((p) => p.classList.remove('is-active'));
        pill.classList.add('is-active');
      });
    });
  }

  /* ---------------------------------------------------------
     7. HELPFUL FEEDBACK BUTTONS
     --------------------------------------------------------- */
  function initHelpfulButtons() {
    const helpfulWraps = document.querySelectorAll('.fq-helpful');
    helpfulWraps.forEach((wrap) => {
      const buttons = wrap.querySelectorAll('.fq-helpful-btn');
      buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
          /* Prevent double-voting */
          if (wrap.dataset.voted) return;
          wrap.dataset.voted = btn.dataset.vote;

          /* Style the clicked button */
          btn.classList.add(btn.dataset.vote === 'yes' ? 'is-voted-yes' : 'is-voted-no');

          /* Replace buttons with a thank-you message */
          setTimeout(() => {
            const thanks = document.createElement('span');
            thanks.className = 'fq-helpful-thanks';
            thanks.textContent = btn.dataset.vote === 'yes'
              ? 'Glad that helped!'
              : 'Thanks for letting us know.';
            /* Replace buttons, keep label span */
            buttons.forEach((b) => b.remove());
            wrap.appendChild(thanks);
          }, 400);
        });
      });
    });
  }

  /* ---------------------------------------------------------
     8. BACK TO TOP
     --------------------------------------------------------- */
  function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    window.addEventListener('scroll', () => {
      btn.classList.toggle('is-visible', window.scrollY > 500);
    }, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ---------------------------------------------------------
     9. URL HASH — open a specific question on page load
     --------------------------------------------------------- */
  function initHashOpen() {
    const hash = window.location.hash.slice(1);
    if (!hash) return;
    const item = document.getElementById(hash);
    if (!item || !item.classList.contains('fq-item')) return;

    setTimeout(() => {
      const trigger = item.querySelector('.fq-trigger');
      if (trigger) trigger.click();
      item.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 400);
  }

  /* ---------------------------------------------------------
     INIT
     --------------------------------------------------------- */
  function init() {
    initHeroEntrance();
    initFadeUp();
    initAccordion();
    initSearch();
    initCategoryTabs();
    initPopularPills();
    initHelpfulButtons();
    initBackToTop();
    initHashOpen();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
