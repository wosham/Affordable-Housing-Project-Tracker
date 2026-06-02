(function () {
  'use strict';

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function updateSubscriber(button) {
    var id = button.getAttribute('data-id');
    var action = button.getAttribute('data-subscriber-action');
    if (!id || !action) return;

    var label = action === 'unsubscribe' ? 'Unsubscribe this address?' : 'Reactivate this address?';
    if (!window.confirm(label)) return;

    button.disabled = true;
    window.AHPTC.request('api/subscribers/update-status.php', {
      method: 'POST',
      body: { id: id, action: action }
    }).then(function () {
      window.location.reload();
    }).catch(function (error) {
      button.disabled = false;
      window.alert(error && error.message ? error.message : 'Subscriber could not be updated.');
    });
  }

  function init() {
    qsa('[data-subscriber-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        updateSubscriber(button);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
