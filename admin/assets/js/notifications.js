(function () {
  'use strict';

  var POLL_MS = 30000;

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function apiRequest(url, options) {
    if (window.AHPTC && typeof window.AHPTC.request === 'function') {
      return window.AHPTC.request(url, options || {});
    }

    return fetch(url, options || {}).then(function (response) {
      return response.json();
    });
  }

  function updateBadge(root, unread) {
    var badge = qs('[data-notification-count]', root);
    var markAll = qs('[data-notification-mark-all]', root);
    unread = Number(unread || 0);
    if (badge) {
      badge.textContent = String(Math.min(unread, 99));
      badge.hidden = unread <= 0;
      badge.classList.toggle('is-empty', unread <= 0);
      badge.setAttribute('aria-label', unread + ' unread notifications');
    }
    if (markAll) markAll.disabled = unread <= 0;
  }

  function renderEmpty(list) {
    list.innerHTML = '<div class="notification-empty" data-notification-empty><i class="fa-solid fa-bell-slash" aria-hidden="true"></i><span>No notifications yet</span></div>';
  }

  function renderItems(root, items) {
    var list = qs('[data-notification-list]', root);
    if (!list) return;
    if (!items || !items.length) {
      renderEmpty(list);
      return;
    }

    list.innerHTML = items.map(function (item) {
      var classes = 'notification-item' + (item.isUnread ? ' is-unread' : '') + (item.priority === 'urgent' ? ' is-urgent' : '');
      var body = item.bodyShort ? '<small>' + escapeHtml(item.bodyShort) + '</small>' : '';
      var urgent = item.priority === 'urgent' ? '<span class="notification-priority">Urgent</span>' : '';
      return '<a class="' + classes + '" href="' + escapeHtml(item.link || '#') + '" data-notification-id="' + Number(item.id || 0) + '" data-notification-priority="' + escapeHtml(item.priority || 'normal') + '">' +
        '<span class="notification-item-icon"><i class="fa-solid ' + escapeHtml(item.icon || 'fa-bell') + '" aria-hidden="true"></i></span>' +
        '<span class="notification-item-copy"><strong>' + escapeHtml(item.title || 'Notification') + '</strong>' + body + '<em>' + escapeHtml(item.timeAgo || '') + '</em></span>' +
        urgent +
      '</a>';
    }).join('');
  }

  function fetchNotifications(root) {
    return apiRequest('api/notifications/fetch.php?limit=10', { method: 'GET' }).then(function (data) {
      if (!data || data.success === false) return;
      updateBadge(root, data.unread || 0);
      renderItems(root, data.items || []);
    }).catch(function () {
      // Keep header resilient if the API is unavailable.
    });
  }

  function markRead(root, id) {
    if (!id) return Promise.resolve();
    return apiRequest('api/notifications/mark-read.php', {
      method: 'POST',
      body: { id: id }
    }).then(function (data) {
      if (data && data.success !== false) updateBadge(root, data.unread || 0);
    }).catch(function () {});
  }

  function markAll(root) {
    return apiRequest('api/notifications/mark-all-read.php', {
      method: 'POST',
      body: {}
    }).then(function (data) {
      if (data && data.success !== false) {
        updateBadge(root, 0);
        qsa('.notification-item.is-unread', root).forEach(function (item) {
          item.classList.remove('is-unread');
        });
      }
    }).catch(function () {});
  }

  function initOne(root) {
    if (!root || root.dataset.notificationsReady === '1') return;
    root.dataset.notificationsReady = '1';

    var toggle = qs('[data-notification-toggle]', root);
    var markAllButton = qs('[data-notification-mark-all]', root);
    var list = qs('[data-notification-list]', root);

    if (toggle) {
      toggle.addEventListener('click', function () {
        window.setTimeout(function () {
          if (root.classList.contains('is-open')) fetchNotifications(root);
        }, 0);
      });
    }

    if (markAllButton) {
      markAllButton.addEventListener('click', function () {
        if (markAllButton.disabled) return;
        markAllButton.disabled = true;
        markAll(root).finally(function () {
          markAllButton.disabled = false;
          updateBadge(root, 0);
        });
      });
    }

    if (list) {
      list.addEventListener('click', function (event) {
        var item = event.target.closest && event.target.closest('[data-notification-id]');
        if (!item) return;
        var id = Number(item.getAttribute('data-notification-id') || 0);
        if (item.classList.contains('is-unread')) {
          item.classList.remove('is-unread');
          markRead(root, id);
        }
      });
    }

    fetchNotifications(root);
    window.setInterval(function () {
      fetchNotifications(root);
    }, POLL_MS);
  }

  function init() {
    qsa('[data-notifications]').forEach(initOne);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
