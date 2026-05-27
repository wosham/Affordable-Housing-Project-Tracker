(function () {
  'use strict';

  var unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function meta(name, fallback) {
    var node = qs('meta[name="' + name + '"]');
    return node ? node.getAttribute('content') || fallback || '' : fallback || '';
  }

  function baseUrl() {
    return meta('app-base-url', '').replace(/\/$/, '');
  }

  function csrfToken() {
    return meta('csrf-token', '');
  }

  function csrfTokenName() {
    return meta('csrf-token-name', '_csrf_token');
  }

  function toUrl(url) {
    if (/^(https?:)?\/\//i.test(url) || url.charAt(0) === '/') {
      return url;
    }
    return baseUrl() + '/' + url.replace(/^\/+/, '');
  }

  function request(url, options) {
    options = options || {};
    var method = (options.method || 'GET').toUpperCase();
    var headers = Object.assign({ Accept: 'application/json' }, options.headers || {});
    var body = options.body;
    var isFormData = typeof FormData !== 'undefined' && body instanceof FormData;

    if (unsafeMethods.indexOf(method) !== -1) {
      headers['X-CSRF-Token'] = csrfToken();
    }

    if (body && typeof body === 'object' && !isFormData) {
      headers['Content-Type'] = headers['Content-Type'] || 'application/json';
      body = JSON.stringify(body);
    }

    return fetch(toUrl(url), Object.assign({}, options, {
      method: method,
      headers: headers,
      body: body
    })).then(function (response) {
      return response.text().then(function (text) {
        var data = {};
        if (text) {
          try {
            data = JSON.parse(text);
          } catch (error) {
            data = { success: false, message: text };
          }
        }

        if (!response.ok) {
          var err = new Error(data.message || 'Request failed.');
          err.status = response.status;
          err.data = data;
          throw err;
        }

        return data;
      });
    });
  }

  function closeSidebar() {
    var sidebar = qs('#adminSidebar');
    var toggle = qs('#adminSidebarToggle');
    if (!sidebar) return;
    sidebar.classList.remove('is-open');
    document.body.classList.remove('sidebar-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  function openSidebar() {
    var sidebar = qs('#adminSidebar');
    var toggle = qs('#adminSidebarToggle');
    if (!sidebar) return;
    sidebar.classList.add('is-open');
    document.body.classList.add('sidebar-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
  }

  function toggleSidebar() {
    var sidebar = qs('#adminSidebar');
    if (!sidebar) return;
    if (window.matchMedia('(max-width: 768px)').matches) {
      if (sidebar.classList.contains('is-open')) closeSidebar();
      else openSidebar();
      return;
    }

    var collapsed = !document.body.classList.contains('sidebar-collapsed');
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    try {
      localStorage.setItem('ahptc_sidebar_collapsed', collapsed ? '1' : '0');
    } catch (error) {}

    var toggle = qs('#adminSidebarToggle');
    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  }

  function initSidebar() {
    var toggle = qs('#adminSidebarToggle');
    var sidebar = qs('#adminSidebar');
    if (!toggle || !sidebar) return;

    try {
      if (localStorage.getItem('ahptc_sidebar_collapsed') === '1' && !window.matchMedia('(max-width: 768px)').matches) {
        document.body.classList.add('sidebar-collapsed');
        toggle.setAttribute('aria-expanded', 'false');
      }
    } catch (error) {}

    toggle.addEventListener('click', function () {
      toggleSidebar();
    });

    qsa('[data-sidebar-close]', sidebar).forEach(function (button) {
      button.addEventListener('click', function () {
        closeSidebar();
      });
    });

    qsa('[data-sidebar-group]', sidebar).forEach(function (group) {
      var button = qs('[data-sidebar-group-toggle]', group);
      var groupId = group.getAttribute('data-sidebar-group-id') || '';
      var hasActive = group.classList.contains('has-active');
      if (!button) return;

      try {
        var stored = localStorage.getItem('ahptc_sidebar_group_' + groupId);
        if (stored === '1') group.classList.add('is-open');
        if (stored === '0' && !hasActive) group.classList.remove('is-open');
      } catch (error) {}

      if (hasActive) group.classList.add('is-open');
      button.setAttribute('aria-expanded', group.classList.contains('is-open') ? 'true' : 'false');

      button.addEventListener('click', function () {
        var willOpen = !group.classList.contains('is-open');
        group.classList.toggle('is-open', willOpen);
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        try {
          localStorage.setItem('ahptc_sidebar_group_' + groupId, willOpen ? '1' : '0');
        } catch (error) {}
      });
    });

    qsa('.sidebar-link', sidebar).forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 768px)').matches) closeSidebar();
      });
    });

    document.addEventListener('click', function (event) {
      if (!document.body.classList.contains('sidebar-open')) return;
      if (sidebar.contains(event.target) || toggle.contains(event.target)) return;
      closeSidebar();
    });
  }

  function closeDropdowns(except) {
    qsa('.admin-user-menu.is-open, .admin-notifications.is-open').forEach(function (dropdown) {
      if (except && dropdown === except) return;
      dropdown.classList.remove('is-open');
      var button = qs('[aria-expanded]', dropdown);
      if (button) button.setAttribute('aria-expanded', 'false');
    });
  }

  function initDropdowns() {
    qsa('[data-admin-dropdown], [data-notifications]').forEach(function (dropdown) {
      var toggle = qs('[data-admin-dropdown-toggle], [data-notification-toggle]', dropdown);
      if (!toggle) return;

      toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        var willOpen = !dropdown.classList.contains('is-open');
        closeDropdowns(dropdown);
        dropdown.classList.toggle('is-open', willOpen);
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function (event) {
      var inside = event.target.closest && event.target.closest('[data-admin-dropdown], [data-notifications]');
      if (!inside) closeDropdowns();
    });
  }

  function initAlertDismiss() {
    qsa('[data-alert-dismiss]').forEach(function (button) {
      button.addEventListener('click', function () {
        var alert = button.closest('[data-alert]');
        if (alert) alert.remove();
      });
    });
  }

  function initConfirmActions() {
    document.addEventListener('click', function (event) {
      var target = event.target.closest && event.target.closest('[data-confirm]');
      if (!target) return;
      var message = target.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        event.preventDefault();
        event.stopPropagation();
      }
    });

    document.addEventListener('submit', function (event) {
      var submitter = event.submitter;
      if (!submitter || !submitter.hasAttribute('data-confirm')) return;
      var message = submitter.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  }

  function initNotifications() {
    qsa('[data-notification-mark-all]').forEach(function (button) {
      button.addEventListener('click', function () {
        if (button.disabled) return;
        button.disabled = true;

        request('api/notifications/mark-all-read.php', {
          method: 'POST',
          body: {}
        }).catch(function () {
          // Notification APIs are wired in a later phase; keep the shell resilient.
        }).finally(function () {
          qsa('.notification-item.is-unread').forEach(function (item) {
            item.classList.remove('is-unread');
          });
          qsa('[data-notification-count]').forEach(function (count) {
            count.textContent = '0';
            count.hidden = true;
            count.classList.add('is-empty');
          });
        });
      });
    });
  }

  function initKeyboard() {
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      closeSidebar();
      closeDropdowns();
    });
  }

  function init() {
    document.body.classList.add('js-ready');
    initSidebar();
    initDropdowns();
    initAlertDismiss();
    initConfirmActions();
    initNotifications();
    initKeyboard();
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, {
    baseUrl: baseUrl,
    csrfToken: csrfToken,
    csrfTokenName: csrfTokenName,
    request: request,
    openSidebar: openSidebar,
    closeSidebar: closeSidebar,
    toggleSidebar: toggleSidebar,
    closeDropdowns: closeDropdowns
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
