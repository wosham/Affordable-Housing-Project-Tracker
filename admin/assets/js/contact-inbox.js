(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function esc(value) {
    var span = document.createElement('span');
    span.textContent = value == null ? '' : String(value);
    return span.innerHTML;
  }

  var modal = qs('[data-contact-modal]');
  var detail = qs('[data-contact-detail]');
  var assignSelect = qs('[data-contact-assign]');
  var statusSelect = qs('[data-contact-status]');
  var responseNote = qs('[data-contact-response-note]');
  var activeId = 0;

  function setError(message) {
    var node = qs('[data-contact-error]', modal);
    if (!node) return;
    node.textContent = message || '';
    node.hidden = !message;
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
    activeId = 0;
    setError('');
  }

  function renderMessage(message) {
    var attachment = message.attachmentUrl
      ? '<a class="contact-attachment-chip" href="' + esc(message.attachmentUrl) + '" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-paperclip" aria-hidden="true"></i> Open attachment</a>'
      : '<span class="text-muted">No attachment</span>';

    detail.innerHTML =
      '<article class="contact-detail-card">' +
        '<h3>' + esc(message.subject || 'No subject') + '</h3>' +
        '<div class="contact-detail-meta">' +
          '<span><i class="fa-solid fa-user" aria-hidden="true"></i> ' + esc(message.name) + '</span>' +
          '<span><i class="fa-solid fa-envelope" aria-hidden="true"></i> ' + esc(message.email) + '</span>' +
          (message.phone ? '<span><i class="fa-solid fa-phone" aria-hidden="true"></i> ' + esc(message.phone) + '</span>' : '') +
          '<span><i class="fa-solid fa-clock" aria-hidden="true"></i> ' + esc(message.createdAtFormatted) + '</span>' +
        '</div>' +
        '<div class="contact-detail-message">' + esc(message.body) + '</div>' +
        '<div>' + attachment + '</div>' +
        '<div class="contact-detail-meta">' +
          '<span>Status: ' + esc(message.status) + '</span>' +
          '<span>Assigned: ' + esc(message.assigneeName || 'Unassigned') + '</span>' +
          (message.ipAddress ? '<span>IP: ' + esc(message.ipAddress) + '</span>' : '') +
        '</div>' +
      '</article>';

    if (assignSelect) assignSelect.value = message.assignedTo || '';
    if (statusSelect) statusSelect.value = message.status === 'archived' ? 'restore' : 'read';
    if (responseNote) responseNote.value = message.responseNote || '';
  }

  function openMessage(id) {
    activeId = parseInt(id, 10) || 0;
    if (!activeId || !modal) return;

    detail.innerHTML = '<div class="contact-detail__loading">Loading message...</div>';
    setError('');
    modal.hidden = false;
    document.body.classList.add('modal-open');

    window.AHPTC.request('api/contact/get-message.php?id=' + encodeURIComponent(activeId), {
      method: 'GET'
    }).then(function (data) {
      renderMessage(data.message || {});
    }).catch(function (error) {
      setError(error && error.message ? error.message : 'Message could not be loaded.');
    });
  }

  function postStatus(action, note) {
    if (!activeId) return Promise.reject(new Error('No active message.'));
    return window.AHPTC.request('api/contact/update-status.php', {
      method: 'POST',
      body: {
        id: activeId,
        action: action,
        response_note: note || ''
      }
    });
  }

  function saveStatus() {
    var action = statusSelect ? statusSelect.value : 'read';
    var note = responseNote ? responseNote.value : '';
    setError('');

    postStatus(action, note).then(function () {
      window.location.reload();
    }).catch(function (error) {
      setError(error && error.message ? error.message : 'Status could not be saved.');
    });
  }

  function saveAssignment() {
    if (!activeId) return;
    setError('');

    window.AHPTC.request('api/contact/assign.php', {
      method: 'POST',
      body: {
        id: activeId,
        assigned_to: assignSelect ? assignSelect.value : ''
      }
    }).then(function () {
      window.location.reload();
    }).catch(function (error) {
      setError(error && error.message ? error.message : 'Assignment could not be saved.');
    });
  }

  function quickAction(button) {
    activeId = parseInt(button.getAttribute('data-id') || '0', 10);
    var action = button.getAttribute('data-contact-action') || 'read';
    if (!activeId) return;

    button.disabled = true;
    postStatus(action, '').then(function () {
      window.location.reload();
    }).catch(function () {
      button.disabled = false;
    });
  }

  function init() {
    qsa('[data-contact-open]').forEach(function (button) {
      button.addEventListener('click', function () {
        openMessage(button.getAttribute('data-id'));
      });
    });

    qsa('[data-contact-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        quickAction(button);
      });
    });

    qsa('[data-contact-close]').forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    var saveStatusButton = qs('[data-contact-save-status]');
    if (saveStatusButton) saveStatusButton.addEventListener('click', saveStatus);

    var saveAssignButton = qs('[data-contact-save-assign]');
    if (saveAssignButton) saveAssignButton.addEventListener('click', saveAssignment);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeModal();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
