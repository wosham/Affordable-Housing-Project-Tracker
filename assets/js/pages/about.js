/* =========================================================
   TRANS-NZOIA AHP TRACKER — About Page JS
   ========================================================= */

(function () {
  'use strict';

  /* -------------------------------------------------------
     1. ANIMATED COUNTERS (stats strip)
     ------------------------------------------------------- */
  function initCounters() {
    const els = document.querySelectorAll('[data-count]');
    if (!els.length) return;

    const ease = (t) => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;

    const runCounter = (el) => {
      const target  = parseFloat(el.dataset.count);
      const suffix  = el.dataset.suffix  || '';
      const prefix  = el.dataset.prefix  || '';
      const dec     = el.dataset.decimals ? parseInt(el.dataset.decimals, 10) : 0;
      const dur     = 1800;
      let start     = null;

      const step = (ts) => {
        if (!start) start = ts;
        const prog  = Math.min((ts - start) / dur, 1);
        const eased = ease(prog);
        const val   = target * eased;
        el.textContent = prefix + (dec ? val.toFixed(dec) : Math.floor(val).toLocaleString()) + suffix;
        if (prog < 1) requestAnimationFrame(step);
        else el.textContent = prefix + (dec ? target.toFixed(dec) : target.toLocaleString()) + suffix;
      };
      requestAnimationFrame(step);
    };

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          runCounter(entry.target);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    els.forEach((el) => obs.observe(el));
  }

  /* -------------------------------------------------------
     2. FADE-UP ON SCROLL
     ------------------------------------------------------- */
  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          const delay = entry.target.dataset.delay ? parseInt(entry.target.dataset.delay, 10) : 0;
          setTimeout(() => entry.target.classList.add('is-visible'), delay);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach((el) => obs.observe(el));
  }

  /* -------------------------------------------------------
     3. TIMELINE SLIDE-IN
     ------------------------------------------------------- */
  function initTimeline() {
    const items = document.querySelectorAll('.ab-tl-item');
    if (!items.length) return;

    items.forEach((item) => {
      item.style.opacity = '0';
      item.style.transform = 'translateX(-20px)';
      item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const idx   = parseInt(entry.target.dataset.index || 0, 10);
          const delay = idx * 80;
          setTimeout(() => {
            entry.target.style.opacity   = '1';
            entry.target.style.transform = 'none';
          }, delay);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    items.forEach((item, i) => {
      item.dataset.index = i;
      obs.observe(item);
    });
  }

  /* -------------------------------------------------------
     4. FAQ ACCORDION
     ------------------------------------------------------- */
  function initFAQ() {
    const triggers = document.querySelectorAll('.ab-faq-trigger');
    if (!triggers.length) return;

    triggers.forEach((trigger) => {
      const bodyId = trigger.getAttribute('aria-controls');
      const body   = document.getElementById(bodyId);
      if (!body) return;

      body.style.maxHeight   = '0';
      body.style.overflow    = 'hidden';
      body.style.transition  = 'max-height 0.35s ease';

      trigger.addEventListener('click', () => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';

        triggers.forEach((t) => {
          const bid = t.getAttribute('aria-controls');
          const b   = document.getElementById(bid);
          if (!b) return;
          t.setAttribute('aria-expanded', 'false');
          b.style.maxHeight = '0';
        });

        if (!expanded) {
          trigger.setAttribute('aria-expanded', 'true');
          body.style.maxHeight = body.scrollHeight + 'px';
        }
      });

      trigger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          trigger.click();
        }
      });
    });
  }

  /* -------------------------------------------------------
     5. HERO KPI COUNTERS (static values, just animate up)
     ------------------------------------------------------- */
  function initHeroKPIs() {
    const kpis = document.querySelectorAll('.ab-kpi-val[data-count]');
    if (!kpis.length) return;

    const ease = (t) => 1 - Math.pow(1 - t, 3);
    const dur  = 1500;

    const runKPI = (el) => {
      const target  = parseFloat(el.dataset.count);
      const suffix  = el.dataset.suffix  || '';
      const prefix  = el.dataset.prefix  || '';
      let start     = null;

      const step = (ts) => {
        if (!start) start = ts;
        const prog  = Math.min((ts - start) / dur, 1);
        const val   = target * ease(prog);
        el.textContent = prefix + Math.floor(val).toLocaleString() + suffix;
        if (prog < 1) requestAnimationFrame(step);
        else el.textContent = prefix + target.toLocaleString() + suffix;
      };
      requestAnimationFrame(step);
    };

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          runKPI(entry.target);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    kpis.forEach((el) => obs.observe(el));
  }

  /* -------------------------------------------------------
     6. PILLAR CARDS STAGGER
     ------------------------------------------------------- */
  function initPillarsStagger() {
    const cards = document.querySelectorAll('.ab-pillar-card');
    if (!cards.length) return;

    cards.forEach((card, i) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(24px)';
      card.style.transition = `opacity 0.45s ease ${i * 80}ms, transform 0.45s ease ${i * 80}ms`;
    });

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity   = '1';
          entry.target.style.transform = 'none';
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    cards.forEach((card) => obs.observe(card));
  }

  /* -------------------------------------------------------
     7. PARTNER CARDS STAGGER
     ------------------------------------------------------- */
  function initPartnersStagger() {
    const cards = document.querySelectorAll('.ab-partner-card');
    if (!cards.length) return;

    cards.forEach((card, i) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = `opacity 0.4s ease ${i * 60}ms, transform 0.4s ease ${i * 60}ms`;
    });

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity   = '1';
          entry.target.style.transform = 'none';
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    cards.forEach((card) => obs.observe(card));
  }

  /* -------------------------------------------------------
     8. LEGAL CARDS STAGGER
     ------------------------------------------------------- */
  function initLegalStagger() {
    const cards = document.querySelectorAll('.ab-legal-card');
    if (!cards.length) return;

    cards.forEach((card, i) => {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = `opacity 0.45s ease ${i * 80}ms, transform 0.45s ease ${i * 80}ms`;
    });

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity   = '1';
          entry.target.style.transform = 'none';
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    cards.forEach((card) => obs.observe(card));
  }

  /* -------------------------------------------------------
     9. SNAPSHOT CARD ENTRANCE
     ------------------------------------------------------- */
  function initSnapshot() {
    const snap = document.querySelector('.ab-snapshot');
    if (!snap) return;
    snap.style.opacity   = '0';
    snap.style.transform = 'translateX(20px)';
    snap.style.transition = 'opacity 0.6s ease 0.2s, transform 0.6s ease 0.2s';

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          snap.style.opacity   = '1';
          snap.style.transform = 'none';
          obs.unobserve(snap);
        }
      });
    }, { threshold: 0.2 });
    obs.observe(snap);
  }

  /* -------------------------------------------------------
     10. TICKER (global — handled by global.js but ensure
         it plays on this page too)
     ------------------------------------------------------- */
  function initTicker() {
    const track = document.querySelector('.ticker-track');
    if (!track || track.children.length === 0) return;
    const clone = track.innerHTML;
    track.innerHTML += clone;
  }

  /* -------------------------------------------------------
     INIT
     ------------------------------------------------------- */
  function init() {
    initHeroKPIs();
    initCounters();
    initFadeUp();
    initTimeline();
    initFAQ();
    initPillarsStagger();
    initPartnersStagger();
    initLegalStagger();
    initSnapshot();
    initTicker();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
