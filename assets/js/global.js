/**
 * TRANS-NZOIA AFFORDABLE HOUSING TRACKER
 * Global JavaScript — Phase 1
 */

(function () {
  'use strict';

  /* ========================================================
     1. Utilities
     ======================================================== */
  function debounce(fn, delay = 100) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  function throttle(fn, limit = 100) {
    let inThrottle;
    return function (...args) {
      if (!inThrottle) {
        fn.apply(this, args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  }

  function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return Number(num).toLocaleString('en-KE');
  }

  function formatDate(dateStr) {
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('en-KE', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  /* ========================================================
     1. Custom Cursor — dot follows instantly, ring lerps behind
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

    const hoverSel = 'a, button, [role="button"], input, select, textarea, label, .map-constituency, .ptc, .news-card, .proj-card, .pfb-chip, .ppg-btn';
    document.addEventListener('mouseover', (e) => {
      if (e.target.closest(hoverSel)) document.body.classList.add('cursor-hover');
    });
    document.addEventListener('mouseout', (e) => {
      if (e.target.closest(hoverSel)) document.body.classList.remove('cursor-hover');
    });
  }

  /* ========================================================
     2. Mobile Navigation
     ======================================================== */
  function initMobileNav() {
    const toggle = document.querySelector('.navbar-toggle');
    const mobileMenu = document.querySelector('.navbar-mobile');
    const backdrop = document.querySelector('.navbar-mobile-backdrop');
    if (!toggle || !mobileMenu) return;

    function openMenu() {
      toggle.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      mobileMenu.classList.add('is-open');
      mobileMenu.setAttribute('aria-hidden', 'false');
      if (backdrop) backdrop.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
      // Focus first link for accessibility
      const firstLink = mobileMenu.querySelector('a, button');
      if (firstLink) firstLink.focus();
    }

    function closeMenu() {
      toggle.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      mobileMenu.classList.remove('is-open');
      mobileMenu.setAttribute('aria-hidden', 'true');
      if (backdrop) backdrop.classList.remove('is-visible');
      document.body.style.overflow = '';
      toggle.focus();
    }

    toggle.addEventListener('click', () => {
      if (mobileMenu.classList.contains('is-open')) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', closeMenu);
    }

    // Also wire the in-panel close button
    const closeBtn = document.getElementById('mob-close-btn');
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mobileMenu.classList.contains('is-open')) {
        closeMenu();
      }
    });

    // Close on link click (but not the close button itself)
    mobileMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMenu);
    });

    // Auto-close when viewport becomes desktop width
    const mediaQuery = window.matchMedia('(min-width: 1024px)');
    function handleResize(e) {
      if (e.matches && mobileMenu.classList.contains('is-open')) closeMenu();
    }
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', handleResize);
    } else {
      mediaQuery.addListener(handleResize);
    }
  }

  /* ========================================================
     3. Sticky Header — scrolled style + hide-on-scroll-down
     ======================================================== */
  function initStickyHeader() {
    const navbar  = document.querySelector('.navbar');
    const topbar  = document.getElementById('siteTopbar'); // may be null on other pages
    if (!navbar) return;

    const SCROLL_THRESHOLD = 80;  // px before hide activates
    const SCROLL_DELTA     = 6;   // px hysteresis to avoid jitter
    let lastY = window.scrollY;

    function checkScroll() {
      const y = window.scrollY;

      /* --- scrolled style (background) ---- */
      if (y > 10) {
        navbar.classList.add('is-scrolled');
      } else {
        navbar.classList.remove('is-scrolled');
      }

      /* --- hide / reveal on scroll direction ---- */
      if (topbar) {
        if (y < SCROLL_THRESHOLD) {
          topbar.classList.remove('is-hidden');
        } else if (y > lastY + SCROLL_DELTA) {
          topbar.classList.add('is-hidden');       // scrolling down
        } else if (y < lastY - SCROLL_DELTA) {
          topbar.classList.remove('is-hidden');    // scrolling up
        }
      }

      lastY = y;
    }

    window.addEventListener('scroll', throttle(checkScroll, 40), { passive: true });
    checkScroll();
  }

  /* ========================================================
     4. Active Navigation Highlighting
     ======================================================== */
  function highlightActiveNav() {
    const currentPath = window.location.pathname;
    const pageName = currentPath.split('/').pop() || 'index.html';

    document.querySelectorAll('.navbar-link').forEach((link) => {
      const href = link.getAttribute('href');
      if (!href) return;
      const linkPage = href.split('/').pop();

      // Exact match or index default
      if (
        linkPage === pageName ||
        (pageName === '' && linkPage === 'index.html') ||
        (pageName === 'index.html' && linkPage === 'index.html')
      ) {
        link.classList.add('is-active');
      } else {
        link.classList.remove('is-active');
      }
    });
  }

  /* ========================================================
     5. Smooth Scroll
     ======================================================== */
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (!targetId || targetId === '#') return;

        const target = document.querySelector(targetId);
        if (!target) return;

        e.preventDefault();

        const navbar = document.querySelector('.navbar');
        const offset = navbar ? navbar.offsetHeight + 16 : 80;

        const top = target.getBoundingClientRect().top + window.scrollY - offset;

        window.scrollTo({
          top: top,
          behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
      });
    });
  }

  /* ========================================================
     6. Back to Top
     ======================================================== */
  function initBackToTop() {
    const btn = document.querySelector('.back-to-top');
    if (!btn) return;

    const showThreshold = 300;

    function toggleVisibility() {
      if (window.scrollY > showThreshold) {
        btn.classList.add('is-visible');
      } else {
        btn.classList.remove('is-visible');
      }
    }

    window.addEventListener('scroll', throttle(toggleVisibility, 100));
    toggleVisibility();

    btn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
      });
    });
  }

  /* ========================================================
     7. Scroll Reveal (IntersectionObserver)
     ======================================================== */
  function initScrollReveal() {
    if (prefersReducedMotion()) return;

    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.15,
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    document
      .querySelectorAll('.fade-up, .fade-in, .scale-in, .stagger-children')
      .forEach((el) => observer.observe(el));
  }

  /* ========================================================
     8. Dropdown Menus
     ======================================================== */
  function initDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach((trigger) => {
      const dropdownId = trigger.getAttribute('data-dropdown');
      const menu = document.getElementById(dropdownId);
      if (!menu) return;

      let isOpen = false;

      function open() {
        isOpen = true;
        menu.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        menu.setAttribute('aria-hidden', 'false');
      }

      function close() {
        isOpen = false;
        menu.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
      }

      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (isOpen) close();
        else open();
      });

      // Close on outside click
      document.addEventListener('click', (e) => {
        if (isOpen && !trigger.contains(e.target) && !menu.contains(e.target)) {
          close();
        }
      });

      // Keyboard navigation
      trigger.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowDown' && !isOpen) {
          e.preventDefault();
          open();
          const firstItem = menu.querySelector('a, button');
          if (firstItem) firstItem.focus();
        }
      });
    });
  }

  /* ========================================================
     9. Search Toggle
     ======================================================== */
  function initSearchToggle() {
    const searchToggle = document.querySelector('[data-search-toggle]');
    const searchBar = document.querySelector('[data-search-bar]');
    if (!searchToggle || !searchBar) return;

    searchToggle.addEventListener('click', () => {
      const isHidden = searchBar.getAttribute('aria-hidden') === 'true';
      if (isHidden) {
        searchBar.classList.add('is-open');
        searchBar.setAttribute('aria-hidden', 'false');
        const input = searchBar.querySelector('input');
        if (input) setTimeout(() => input.focus(), 100);
      } else {
        searchBar.classList.remove('is-open');
        searchBar.setAttribute('aria-hidden', 'true');
      }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && searchBar.classList.contains('is-open')) {
        searchBar.classList.remove('is-open');
        searchBar.setAttribute('aria-hidden', 'true');
        searchToggle.focus();
      }
    });
  }

  /* ========================================================
     10. Counter Animation
     ======================================================== */
  function animateCounter(el, target, duration = 2000) {
    if (prefersReducedMotion()) {
      el.textContent = formatNumber(target);
      return;
    }

    const startTime = performance.now();
    const startValue = 0;

    function easeOutExpo(t) {
      return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    }

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = easeOutExpo(progress);
      const current = Math.floor(startValue + (target - startValue) * eased);
      el.textContent = formatNumber(current);

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }

    requestAnimationFrame(update);
  }

  /* Expose for page scripts */
  window.TNAH = window.TNAH || {};
  window.TNAH.utils = {
    debounce,
    throttle,
    formatNumber,
    formatDate,
    prefersReducedMotion,
    animateCounter,
  };

  /* ========================================================
     11. Initialize Everything on DOM Ready
     ======================================================== */
  function init() {
    initCursor();
    initMobileNav();
    initStickyHeader();
    highlightActiveNav();
    initSmoothScroll();
    initBackToTop();
    initScrollReveal();
    initDropdowns();
    initSearchToggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
