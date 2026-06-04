(function () {
  'use strict';

  const modal = document.querySelector('[data-milestone-modal]');
  const form = document.querySelector('[data-milestone-form]');
  const title = document.querySelector('[data-milestone-modal-title]');
  const statusText = document.querySelector('[data-milestone-form-status]');
  const request = window.AHPTC && window.AHPTC.request ? window.AHPTC.request : null;

  if (!modal || !form || !request) return;

  function field(name) {
    return form.querySelector('[data-field="' + name + '"]');
  }

  function setField(name, value) {
    const input = field(name);
    if (input) input.value = value || '';
  }

  function setStatus(message, state) {
    if (!statusText) return;
    statusText.textContent = message || '';
    statusText.dataset.state = state || '';
  }

  function openModal(mode, data) {
    form.reset();
    setStatus('', '');
    title.textContent = mode === 'edit' ? 'Edit milestone' : 'Add milestone';
    setField('milestone_id', data.id || '');
    setField('project_id', data.projectId || '');
    setField('label', data.label || '');
    setField('description', data.description || '');
    setField('status', data.status || 'pending');
    setField('priority', data.priority || 'normal');
    setField('target_date', data.targetDate || '');
    setField('actual_date', data.actualDate || '');
    setField('progress_percent', data.progress || '0');
    setField('sequence', data.sequence || '0');
    setField('notes', data.notes || '');
    modal.hidden = false;
    const first = field('project_id');
    if (first) first.focus();
  }

  function closeModal() {
    modal.hidden = true;
  }

  function buttonLoading(button, loading) {
    if (!button) return;
    button.disabled = loading;
    button.classList.toggle('is-loading', loading);
  }

  async function save(payload, button) {
    buttonLoading(button, true);
    setStatus('Saving milestone...', 'loading');
    try {
      const data = await request('api/projects/update-milestone.php', {
        method: 'POST',
        body: payload
      });
      if (!data.success) throw new Error(data.message || 'Milestone could not be saved.');
      setStatus(data.message || 'Milestone saved.', 'success');
      window.setTimeout(() => window.location.reload(), 450);
    } catch (error) {
      setStatus(error.message || 'Milestone could not be saved.', 'error');
      buttonLoading(button, false);
    }
  }

  document.addEventListener('click', function (event) {
    const create = event.target.closest('[data-milestone-open-create]');
    const edit = event.target.closest('[data-milestone-edit]');
    const close = event.target.closest('[data-milestone-close]');
    const action = event.target.closest('[data-milestone-action]');

    if (create) {
      openModal('create', { projectId: create.getAttribute('data-project-id') || '' });
    }

    if (edit) {
      openModal('edit', {
        id: edit.getAttribute('data-id'),
        projectId: edit.getAttribute('data-project-id'),
        label: edit.getAttribute('data-label'),
        description: edit.getAttribute('data-description'),
        status: edit.getAttribute('data-status'),
        priority: edit.getAttribute('data-priority'),
        targetDate: edit.getAttribute('data-target-date'),
        actualDate: edit.getAttribute('data-actual-date'),
        progress: edit.getAttribute('data-progress'),
        sequence: edit.getAttribute('data-sequence'),
        notes: edit.getAttribute('data-notes')
      });
    }

    if (close || event.target === modal) {
      closeModal();
    }

    if (action) {
      const nextStatus = action.getAttribute('data-milestone-action');
      const milestoneId = action.getAttribute('data-milestone-id');
      const message = nextStatus === 'done'
        ? 'Mark this milestone as completed?'
        : 'Update this milestone status?';
      if (!window.confirm(message)) return;
      save({ milestone_id: milestoneId, status: nextStatus }, action);
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    const button = event.submitter || form.querySelector('[type="submit"]');
    const data = Object.fromEntries(new FormData(form).entries());
    save(data, button);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });
}());
