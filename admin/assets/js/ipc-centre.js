(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function request(url, options) {
    if (window.AHPTC && typeof window.AHPTC.request === 'function') {
      return window.AHPTC.request(url, options);
    }

    return fetch(url, options || {}).then(function (response) { return response.json(); });
  }

  function openModal(button) {
    var modal = qs('#ipcDecisionModal');
    var backdrop = qs('[data-ipc-backdrop]');
    if (!modal) return;

    var mode = button.getAttribute('data-ipc-action') || '';
    var isReject = mode === 'reject';

    qs('[data-ipc-modal-title]', modal).textContent = (isReject ? 'Reject ' : 'Approve ') + (button.getAttribute('data-ipc-title') || 'IPC');
    qs('[data-ipc-modal-summary]', modal).textContent = button.getAttribute('data-ipc-summary') || 'Confirm this IPC decision.';
    qs('[data-ipc-modal-id]', modal).value = button.getAttribute('data-ipc-id') || '';
    qs('[data-ipc-modal-mode]', modal).value = mode;
    qs('[data-ipc-comment-label]', modal).textContent = isReject ? 'Rejection reason' : 'Approval note';
    qs('[data-ipc-comment]', modal).value = '';
    qs('[data-ipc-comment]', modal).required = isReject;
    var error = qs('[data-ipc-error]', modal);
    if (error) {
      error.hidden = true;
      error.textContent = '';
    }

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    if (backdrop) backdrop.classList.add('is-open');
  }

  function closeModal() {
    var modal = qs('#ipcDecisionModal');
    var backdrop = qs('[data-ipc-backdrop]');
    if (modal) {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    }
    if (backdrop) backdrop.classList.remove('is-open');
  }

  function submitDecision(event) {
    event.preventDefault();
    var form = event.currentTarget;
    var mode = qs('[data-ipc-modal-mode]', form).value;
    var id = qs('[data-ipc-modal-id]', form).value;
    var comment = qs('[data-ipc-comment]', form).value.trim();
    var error = qs('[data-ipc-error]', form);
    var submit = qs('[data-ipc-submit]', form);

    if (mode === 'reject' && !comment) {
      if (error) {
        error.hidden = false;
        error.textContent = 'A rejection reason is required.';
      }
      return;
    }

    if (submit) submit.disabled = true;

    request(mode === 'approve' ? 'api/ipcs/approve.php' : 'api/ipcs/reject.php', {
      method: 'POST',
      body: mode === 'approve' ? { ipc_id: id, comment: comment } : { ipc_id: id, reason: comment }
    }).then(function (data) {
      if (!data || data.success === false || data.status === 'error') {
        throw new Error((data && data.message) || 'Decision could not be saved.');
      }
      window.location.reload();
    }).catch(function (err) {
      if (error) {
        error.hidden = false;
        error.textContent = err.message || 'Decision could not be saved.';
      }
    }).finally(function () {
      if (submit) submit.disabled = false;
    });
  }

  function init() {
    qsa('[data-ipc-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        openModal(button);
      });
    });

    qsa('[data-ipc-modal-close], [data-ipc-backdrop]').forEach(function (node) {
      node.addEventListener('click', closeModal);
    });

    var form = qs('[data-ipc-decision-form]');
    if (form) form.addEventListener('submit', submitDecision);

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
