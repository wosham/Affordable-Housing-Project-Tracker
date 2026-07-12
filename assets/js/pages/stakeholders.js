/* =========================================================
   TRANS-NZOIA AHP TRACKER - Stakeholders Page JS
   ========================================================= */

(function () {
  'use strict';

  function readPayload() {
    const script = document.getElementById('stakeholders-page-data');
    if (!script) return { groups: [] };
    try {
      const parsed = JSON.parse(script.textContent || '{}');
      return parsed && typeof parsed === 'object' ? parsed : { groups: [] };
    } catch (error) {
      return { groups: [] };
    }
  }

  const PAGE_DATA = readPayload();
  const GROUP_DATA = new Map((PAGE_DATA.groups || []).map((group) => [String(group.slug || ''), group]));

  function el(tagName, className, text) {
    const node = document.createElement(tagName);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = String(text);
    return node;
  }

  function icon(className) {
    const node = document.createElement('i');
    node.className = `fa-solid ${className || 'fa-handshake'}`;
    node.setAttribute('aria-hidden', 'true');
    return node;
  }

  function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length || !('IntersectionObserver' in window)) {
      els.forEach((item) => item.classList.add('is-visible'));
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
    );

    els.forEach((item) => observer.observe(item));
  }

  function initHeroEntrance() {
    const elements = [
      document.querySelector('.sk-hero-eyebrow'),
      document.querySelector('.sk-hero-title'),
      document.querySelector('.sk-hero-sub'),
      document.querySelector('.sk-hero-kpi-strip'),
    ].filter(Boolean);

    elements.forEach((item) => {
      item.style.opacity = '0';
      item.style.transform = 'translateY(28px)';
    });

    requestAnimationFrame(() => {
      elements.forEach((item, index) => {
        setTimeout(() => {
          item.style.transition = 'opacity 0.65s ease, transform 0.65s ease';
          item.style.opacity = '1';
          item.style.transform = 'translateY(0)';
        }, 150 + index * 110);
      });
    });
  }

  function renderDetail(detailWrap, group) {
    if (!group) return;

    detailWrap.replaceChildren();
    detailWrap.classList.add('is-updating');
    setTimeout(() => detailWrap.classList.remove('is-updating'), 350);

    const content = el('div', 'sk-map-detail-content');
    const iconBox = el('div', 'sk-map-detail-icon');
    iconBox.appendChild(icon(group.icon));

    const body = el('div', 'sk-map-detail-body');
    body.appendChild(el('p', 'sk-map-detail-name', group.name || 'Stakeholder group'));
    body.appendChild(el('p', 'sk-map-detail-sub', group.summary || group.count_label || ''));
    body.appendChild(el('p', 'sk-map-detail-desc', group.description || 'Details will be published after confirmation.'));

    const entities = el('ul', 'sk-map-detail-entities');
    const records = Array.isArray(group.entities) ? group.entities : [];
    if (records.length) {
      records.forEach((record) => {
        const item = el('li', '', record.role ? `${record.label} - ${record.role}` : record.label);
        entities.appendChild(item);
      });
    } else {
      entities.appendChild(el('li', '', 'Records will appear here after publication.'));
    }
    body.appendChild(entities);

    content.appendChild(iconBox);
    content.appendChild(body);
    detailWrap.appendChild(content);
  }

  function renderPlaceholder(detailWrap) {
    const placeholder = el('div', 'sk-map-detail-placeholder');
    placeholder.appendChild(icon('fa-hand-pointer'));
    placeholder.appendChild(el('p', '', 'Select a stakeholder category to view its public role and records.'));
    detailWrap.replaceChildren(placeholder);
  }

  function initEcosystemMap() {
    const map = document.getElementById('stakeholderMap');
    const detailWrap = document.getElementById('mapDetailInner');
    if (!map || !detailWrap) return;

    const nodes = map.querySelectorAll('.sk-map-node');
    const lines = map.querySelectorAll('.sk-conn-line');
    let active = null;

    function activateNode(groupKey) {
      active = active === groupKey ? null : groupKey;

      nodes.forEach((node) => node.classList.toggle('is-active', node.dataset.cat === active));
      lines.forEach((line) => {
        if (!active) {
          line.classList.remove('is-active', 'is-dimmed');
        } else if (line.dataset.cat === active) {
          line.classList.add('is-active');
          line.classList.remove('is-dimmed');
        } else {
          line.classList.add('is-dimmed');
          line.classList.remove('is-active');
        }
      });

      if (active) {
        renderDetail(detailWrap, GROUP_DATA.get(active));
        const detail = document.getElementById('mapDetail');
        if (detail && window.innerWidth < 900) {
          setTimeout(() => detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 60);
        }
      } else {
        renderPlaceholder(detailWrap);
      }
    }

    nodes.forEach((node) => {
      node.addEventListener('click', () => activateNode(node.dataset.cat));
      node.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          activateNode(node.dataset.cat);
        }
      });
    });
  }

  function initCategoryCards() {
    document.querySelectorAll('.sk-cat-card').forEach((card) => {
      const flipBtn = card.querySelector('.sk-cat-flip-btn');
      const flipBackBtn = card.querySelector('.sk-cat-flip-back-btn');
      if (flipBtn) flipBtn.addEventListener('click', () => card.classList.add('is-flipped'));
      if (flipBackBtn) flipBackBtn.addEventListener('click', () => card.classList.remove('is-flipped'));
    });
  }

  function initAccordion() {
    const triggers = document.querySelectorAll('.sk-acc-trigger');
    if (!triggers.length) return;

    triggers.forEach((trigger) => {
      const panel = document.getElementById(trigger.getAttribute('aria-controls'));
      if (!panel) return;

      if (trigger.getAttribute('aria-expanded') === 'false') {
        panel.style.height = '0';
        panel.style.overflow = 'hidden';
      }

      trigger.addEventListener('click', () => {
        const isOpen = trigger.getAttribute('aria-expanded') === 'true';

        triggers.forEach((other) => {
          if (other === trigger) return;
          const otherPanel = document.getElementById(other.getAttribute('aria-controls'));
          if (!otherPanel) return;
          other.setAttribute('aria-expanded', 'false');
          otherPanel.style.height = '0';
          otherPanel.setAttribute('hidden', '');
        });

        if (isOpen) {
          panel.style.height = `${panel.scrollHeight}px`;
          requestAnimationFrame(() => {
            panel.style.height = '0';
          });
          trigger.setAttribute('aria-expanded', 'false');
          setTimeout(() => panel.setAttribute('hidden', ''), 350);
        } else {
          panel.removeAttribute('hidden');
          panel.style.height = '0';
          requestAnimationFrame(() => {
            panel.style.height = `${panel.scrollHeight}px`;
          });
          trigger.setAttribute('aria-expanded', 'true');
          setTimeout(() => {
            panel.style.height = 'auto';
          }, 380);
        }
      });
    });
  }

  function initTimeline() {
    const items = document.querySelectorAll('.sk-tl-item');
    const wrap = document.querySelector('.sk-timeline-scroll-wrap');
    if (!items.length || !wrap) return;

    items.forEach((item) => {
      item.style.opacity = '0';
      item.style.transform = 'translateY(16px)';
    });

    if (!('IntersectionObserver' in window)) {
      items.forEach((item) => {
        item.style.opacity = '1';
        item.style.transform = 'translateY(0)';
      });
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            items.forEach((item, index) => {
              setTimeout(() => {
                item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
              }, index * 70);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.05 }
    );
    observer.observe(wrap);
  }

  function initStagger(selector, itemSelector, offset) {
    const root = document.querySelector(selector);
    if (!root) return;

    const items = root.querySelectorAll(itemSelector);
    items.forEach((item) => {
      item.style.opacity = '0';
      item.style.transform = `translateY(${offset}px)`;
    });

    if (!('IntersectionObserver' in window)) {
      items.forEach((item) => {
        item.style.opacity = '1';
        item.style.transform = 'translateY(0)';
      });
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            items.forEach((item, index) => {
              setTimeout(() => {
                item.style.transition = 'opacity 0.5s ease, transform 0.5s ease, box-shadow 0.25s, border-color 0.25s';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
              }, index * 80);
            });
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.06 }
    );
    observer.observe(root);
  }

  function initTimelineArrows() {
    const wrap = document.querySelector('.sk-timeline-scroll-wrap');
    const prev = document.getElementById('tlPrev');
    const next = document.getElementById('tlNext');
    if (!wrap || !prev || !next) return;

    const step = 460;

    function updateButtons() {
      prev.disabled = wrap.scrollLeft <= 0;
      next.disabled = wrap.scrollLeft >= wrap.scrollWidth - wrap.clientWidth - 2;
    }

    prev.addEventListener('click', () => {
      wrap.scrollTo({ left: Math.max(0, wrap.scrollLeft - step), behavior: 'smooth' });
    });
    next.addEventListener('click', () => {
      wrap.scrollTo({ left: Math.min(wrap.scrollWidth - wrap.clientWidth, wrap.scrollLeft + step), behavior: 'smooth' });
    });
    wrap.addEventListener('scroll', updateButtons, { passive: true });
    updateButtons();
  }

  function init() {
    initHeroEntrance();
    initFadeUp();
    initEcosystemMap();
    initCategoryCards();
    initAccordion();
    initTimeline();
    initStagger('#voicesGrid', '.sk-voice-card', 20);
    initStagger('.sk-partners-row', '.sk-partner-tile', 14);
    initStagger('.sk-engage-grid', '.sk-engage-card', 24);
    initTimelineArrows();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
