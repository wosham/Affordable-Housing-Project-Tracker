(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function initPortalAnnouncements(root) {
    if (!root || !window.AHPTC || typeof window.AHPTC.request !== 'function') {
      return;
    }

    var list = qs('[data-portal-announcement-list]', root);
    var csrfForm = root.getAttribute('data-csrf-form') || window.AHPTC.csrfForm();

    root.addEventListener('click', function (event) {
      var button = event.target.closest('[data-dismiss-announcement]');
      if (!button || !root.contains(button)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      var id = Number(button.getAttribute('data-dismiss-announcement') || 0);
      if (!id) {
        return;
      }

      var item = button.closest('[data-announcement-item]');
      button.disabled = true;

      window.AHPTC.request('api/announcements/dismiss.php', {
        method: 'POST',
        body: {
          announcement_id: id,
          csrf_form: csrfForm
        },
        headers: {
          'X-CSRF-Form': csrfForm
        }
      }).then(function () {
        if (item) {
          item.classList.add('is-dismissing');
          window.setTimeout(function () {
            item.remove();
            if (list && !list.querySelector('[data-announcement-item]')) {
              root.innerHTML = ''
                + '<div class="card__header"><div>'
                + '<h2 class="card__title">Staff Announcements</h2>'
                + '<p class="card__subtitle">Role-targeted notices from the County Director.</p>'
                + '</div></div>'
                + '<div class="empty-state empty-state--compact" data-portal-announcements-empty>'
                + '<span class="empty-state__icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span>'
                + '<strong class="empty-state__title">No active staff announcements</strong>'
                + '<span class="empty-state__text">Published role notices will appear here.</span>'
                + '</div>';
            }
          }, 180);
        }
      }).catch(function (error) {
        button.disabled = false;
        window.alert((error && error.message) || 'Announcement could not be dismissed.');
      });
    });
  }

  function boot() {
    document.querySelectorAll('[data-portal-announcements]').forEach(initPortalAnnouncements);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
}());
