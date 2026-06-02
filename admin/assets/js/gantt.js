(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  var modal = qs('[data-programme-modal]');
  var form = qs('[data-programme-form]');

  function field(name) {
    return qs('[data-programme-field="' + name + '"]', form);
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
    setAlert('[data-programme-warning]', '');
    setAlert('[data-programme-error]', '');
  }

  function openModal(button) {
    if (!modal || !form) return;

    field('id').value = button.getAttribute('data-id') || '';
    field('task_name').value = button.getAttribute('data-name') || '';
    field('planned_start').value = button.getAttribute('data-planned-start') || '';
    field('planned_end').value = button.getAttribute('data-planned-end') || '';
    field('start_date').value = button.getAttribute('data-start-date') || '';
    field('end_date').value = button.getAttribute('data-end-date') || '';
    field('pct_complete').value = button.getAttribute('data-progress') || '0';
    field('status').value = button.getAttribute('data-status') || 'pending';
    field('assigned_to').value = button.getAttribute('data-assigned') || '';
    field('depends_on_task_id').value = button.getAttribute('data-dependency') || '';
    field('notes').value = button.getAttribute('data-notes') || '';
    field('critical_path').checked = button.getAttribute('data-critical') === '1';

    qsa('option', field('depends_on_task_id')).forEach(function (option) {
      option.disabled = option.value && option.value === field('id').value;
    });

    setAlert('[data-programme-warning]', '');
    setAlert('[data-programme-error]', '');
    modal.hidden = false;
    document.body.classList.add('modal-open');
    field('task_name').focus();
  }

  function validationWarnings() {
    var warnings = [];
    var plannedStart = field('planned_start').value;
    var plannedEnd = field('planned_end').value;
    var actualStart = field('start_date').value;
    var actualEnd = field('end_date').value;
    var progress = parseInt(field('pct_complete').value || '0', 10);

    if (plannedStart && plannedEnd && plannedEnd < plannedStart) warnings.push('Planned end is before planned start.');
    if (actualStart && actualEnd && actualEnd < actualStart) warnings.push('Actual end is before actual start.');
    if (progress < 0 || progress > 100) warnings.push('Progress must be between 0 and 100.');
    if (progress === 100 && field('status').value !== 'complete') warnings.push('A 100% task will be saved as complete.');
    return warnings;
  }

  function updateWarnings() {
    setAlert('[data-programme-warning]', validationWarnings().join(' '));
  }

  function setSaving(isSaving) {
    var submit = qs('button[type="submit"]', form);
    if (!submit) return;
    submit.disabled = isSaving;
    submit.classList.toggle('is-loading', isSaving);
  }

  function submitForm(event) {
    event.preventDefault();
    setAlert('[data-programme-error]', '');
    updateWarnings();

    var hardErrors = validationWarnings().filter(function (warning) {
      return warning.indexOf('before') !== -1 || warning.indexOf('between') !== -1;
    });
    if (hardErrors.length) {
      setAlert('[data-programme-error]', hardErrors.join(' '));
      return;
    }

    setSaving(true);
    window.AHPTC.request('api/programme/update-task.php', {
      method: 'POST',
      body: new FormData(form)
    }).then(function () {
      window.location.reload();
    }).catch(function (error) {
      setAlert('[data-programme-error]', error && error.message ? error.message : 'Programme task could not be saved.');
    }).finally(function () {
      setSaving(false);
    });
  }

  function init() {
    qsa('[data-programme-edit]').forEach(function (button) {
      button.addEventListener('click', function () {
        openModal(button);
      });
    });

    qsa('[data-programme-close]').forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    if (form) {
      form.addEventListener('submit', submitForm);
      ['planned_start', 'planned_end', 'start_date', 'end_date', 'pct_complete', 'status'].forEach(function (name) {
        var input = field(name);
        if (input) input.addEventListener('input', updateWarnings);
        if (input) input.addEventListener('change', updateWarnings);
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
