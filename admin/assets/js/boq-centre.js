(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  var modal = qs('[data-boq-modal]');
  var form = qs('[data-boq-form]');
  var activeQuantity = 0;

  function field(name) {
    return qs('[data-boq-field="' + name + '"]', form);
  }

  function setAlert(selector, message) {
    var node = qs(selector, modal);
    if (!node) return;
    node.textContent = message || '';
    node.hidden = !message;
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
    setAlert('[data-boq-warning]', '');
    setAlert('[data-boq-error]', '');
  }

  function openModal(button) {
    if (!modal || !form) return;

    activeQuantity = parseFloat(button.getAttribute('data-quantity') || '0') || 0;
    field('id').value = button.getAttribute('data-id') || '';
    field('certified_qty').value = button.getAttribute('data-certified') || '0';
    field('paid_qty').value = button.getAttribute('data-paid') || '0';
    field('status').value = button.getAttribute('data-status') || 'active';
    field('notes').value = button.getAttribute('data-notes') || '';
    var force = qs('input[name="force"]', form);
    if (force) force.checked = false;

    qs('[data-boq-item-no]', modal).textContent = button.getAttribute('data-item-no') || 'BOQ item';
    qs('[data-boq-description]', modal).textContent = button.getAttribute('data-description') || '';
    qs('[data-boq-contract]', modal).textContent = 'Contract quantity: ' + activeQuantity.toLocaleString(undefined, { maximumFractionDigits: 3 });

    setAlert('[data-boq-warning]', '');
    setAlert('[data-boq-error]', '');
    modal.hidden = false;
    document.body.classList.add('modal-open');
    field('certified_qty').focus();
  }

  function localWarnings() {
    var certified = parseFloat(field('certified_qty').value || '0') || 0;
    var paid = parseFloat(field('paid_qty').value || '0') || 0;
    var warnings = [];

    if (certified > activeQuantity) warnings.push('Certified quantity exceeds the contract quantity.');
    if (paid > certified) warnings.push('Paid quantity exceeds the certified quantity.');
    if (paid > activeQuantity) warnings.push('Paid quantity exceeds the contract quantity.');
    return warnings;
  }

  function updateWarnings() {
    var warnings = localWarnings();
    setAlert('[data-boq-warning]', warnings.join(' '));
  }

  function setSaving(isSaving) {
    var submit = qs('button[type="submit"]', form);
    if (!submit) return;
    submit.disabled = isSaving;
    submit.classList.toggle('is-loading', isSaving);
  }

  function submitForm(event) {
    event.preventDefault();
    setAlert('[data-boq-error]', '');
    updateWarnings();

    var warnings = localWarnings();
    var force = qs('input[name="force"]', form);
    if (warnings.length && force && !force.checked) {
      setAlert('[data-boq-error]', 'Review the warning and tick the confirmation checkbox before saving.');
      return;
    }

    setSaving(true);
    var data = new FormData(form);

    window.AHPTC.request('api/boq/update-item.php', {
      method: 'POST',
      body: data
    }).then(function () {
      window.location.reload();
    }).catch(function (error) {
      var message = error && error.message ? error.message : 'BOQ item could not be saved.';
      if (error && error.data && Array.isArray(error.data.warnings)) {
        setAlert('[data-boq-warning]', error.data.warnings.join(' '));
      }
      setAlert('[data-boq-error]', message);
    }).finally(function () {
      setSaving(false);
    });
  }

  function init() {
    qsa('[data-boq-edit]').forEach(function (button) {
      button.addEventListener('click', function () {
        openModal(button);
      });
    });

    qsa('[data-boq-close]').forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    if (form) {
      form.addEventListener('submit', submitForm);
      ['certified_qty', 'paid_qty'].forEach(function (name) {
        var input = field(name);
        if (input) input.addEventListener('input', updateWarnings);
      });
    }

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
