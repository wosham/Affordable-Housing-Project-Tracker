(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  var actionModal = qs('#approvalActionModal');
  var detailModal = qs('#approvalDetailModal');
  var backdrop = qs('[data-approval-backdrop]');
  var activeModal = null;

  function openModal(modal) {
    if (!modal) return;
    activeModal = modal;
    if (backdrop) backdrop.classList.add('is-open');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (backdrop) backdrop.classList.remove('is-open');
    qsa('.sa-approval-modal.is-open').forEach(function (modal) {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    });
    activeModal = null;
  }

  function openAction(button) {
    if (!actionModal) return;
    var form = qs('[data-approval-form]', actionModal);
    var title = qs('[data-action-title]', actionModal);
    var summary = qs('[data-action-summary]', actionModal);
    var endpoint = qs('[data-action-endpoint]', actionModal);
    var field = qs('[data-action-entity-field]', actionModal);
    var id = qs('[data-action-entity-id]', actionModal);
    var comment = qs('[data-action-comment]', actionModal);
    var label = qs('[data-comment-label]', actionModal);
    var submit = qs('[data-action-submit]', actionModal);
    var daysField = qs('[data-granted-days-field]', actionModal);
    var daysInput = qs('[data-granted-days-input]', actionModal);
    var requiresReason = button.getAttribute('data-requires-reason') === '1';
    var grantedDays = button.getAttribute('data-granted-days') || '';

    if (form) form.reset();
    if (title) title.textContent = button.getAttribute('data-title') || 'Record decision';
    if (summary) summary.textContent = button.getAttribute('data-summary') || 'Confirm this decision.';
    if (endpoint) endpoint.value = button.getAttribute('data-endpoint') || '';
    if (field) field.value = button.getAttribute('data-entity-field') || '';
    if (id) id.value = button.getAttribute('data-entity-id') || '';
    if (comment) {
      comment.required = requiresReason;
      comment.placeholder = requiresReason ? 'A clear rejection reason is required.' : 'Optional decision note.';
    }
    if (label) label.textContent = requiresReason ? 'Rejection reason' : 'Decision note';
    if (submit) {
      submit.classList.toggle('btn--danger', requiresReason);
      submit.classList.toggle('btn--primary', !requiresReason);
      submit.textContent = requiresReason ? 'Reject' : 'Approve';
    }
    if (daysField && daysInput) {
      var showDays = grantedDays !== '';
      daysField.hidden = !showDays;
      daysInput.disabled = !showDays;
      daysInput.value = grantedDays;
      daysInput.max = grantedDays || '';
    }

    openModal(actionModal);
  }

  function openDetail(button) {
    if (!detailModal) return;
    var selector = button.getAttribute('data-detail-open');
    var template = selector ? qs(selector) : null;
    var target = qs('[data-detail-content]', detailModal);
    if (!template || !target) return;
    target.innerHTML = template.innerHTML;
    openModal(detailModal);
  }

  function submitAction(form) {
    var endpoint = qs('[data-action-endpoint]', form);
    var entityField = qs('[data-action-entity-field]', form);
    var entityId = qs('[data-action-entity-id]', form);
    var comment = qs('[data-action-comment]', form);
    var days = qs('[data-granted-days-input]', form);
    var submit = qs('[data-action-submit]', form);
    var payload = {};

    payload[entityField.value] = entityId.value;
    if (comment && comment.value.trim() !== '') {
      payload.comment = comment.value.trim();
      payload.reason = comment.value.trim();
    }
    if (days && !days.disabled && days.value) {
      payload.granted_days = days.value;
    }

    if (submit) submit.disabled = true;
    window.AHPTC.request(endpoint.value, {
      method: 'POST',
      body: payload
    }).then(function () {
      window.location.reload();
    }).catch(function (error) {
      alert((error && error.data && error.data.message) || error.message || 'Decision could not be saved.');
    }).finally(function () {
      if (submit) submit.disabled = false;
    });
  }

  document.addEventListener('click', function (event) {
    var action = event.target.closest && event.target.closest('[data-approval-action]');
    if (action) {
      openAction(action);
      return;
    }

    var detail = event.target.closest && event.target.closest('[data-detail-open]');
    if (detail) {
      openDetail(detail);
      return;
    }

    if ((event.target.closest && event.target.closest('[data-modal-close]')) || event.target === backdrop) {
      closeModal();
    }
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest && event.target.closest('[data-approval-form]');
    if (!form) return;
    event.preventDefault();
    submitAction(form);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && activeModal) closeModal();
  });
}());
