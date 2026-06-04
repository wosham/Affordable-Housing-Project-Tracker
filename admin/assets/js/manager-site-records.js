(function () {
  'use strict';

  const request = window.AHPTC && window.AHPTC.request ? window.AHPTC.request : null;
  if (!request) return;

  function closest(selector, event) {
    return event.target && event.target.closest ? event.target.closest(selector) : null;
  }

  function field(form, name) {
    return form.querySelector('[data-field="' + name + '"]');
  }

  function setField(form, name, value) {
    const input = field(form, name);
    if (!input) return;
    input.value = value === null || value === undefined ? '' : value;
  }

  function setStatus(form, message, state) {
    const output = form.querySelector('[data-msr-status-text]');
    if (!output) return;
    output.textContent = message || '';
    output.dataset.state = state || '';
  }

  function loading(button, state) {
    if (!button) return;
    button.disabled = state;
    button.classList.toggle('is-loading', state);
  }

  function parseRecord(button) {
    const raw = button.getAttribute('data-record');
    if (!raw) return {};
    try {
      return JSON.parse(raw);
    } catch (error) {
      return {};
    }
  }

  function resetForm(form) {
    form.reset();
    form.querySelectorAll('[data-field]').forEach(function (input) {
      if (input.type === 'hidden') input.value = '';
    });
    setStatus(form, '', '');
  }

  function fillForm(form, record) {
    Object.keys(record || {}).forEach(function (key) {
      setField(form, key, record[key]);
    });
    ['attendees_text', 'action_items_text'].forEach(function (key) {
      if (record[key] !== undefined) setField(form, key, record[key]);
    });
  }

  function openModal(type, record) {
    const modal = document.querySelector('[data-msr-modal="' + type + '"]');
    if (!modal) return;
    const form = modal.querySelector('[data-msr-form]');
    const title = modal.querySelector('[data-msr-title]');
    const labels = {
      meeting: 'meeting minutes',
      incident: 'H&S incident',
      community: 'community engagement'
    };
    resetForm(form);
    if (record && record.id) {
      fillForm(form, record);
      if (title) title.textContent = 'Edit ' + (labels[type] || 'record');
    } else {
      const today = new Date().toISOString().slice(0, 10);
      ['meeting_date', 'incident_date', 'log_date'].forEach(function (key) {
        setField(form, key, today);
      });
      if (title) title.textContent = 'New ' + (labels[type] || 'record');
    }
    modal.hidden = false;
  }

  function closeModal(modal) {
    if (modal) modal.hidden = true;
  }

  async function saveForm(form, button) {
    const endpoint = form.getAttribute('data-endpoint');
    if (!endpoint) return;
    const body = new FormData(form);
    loading(button, true);
    setStatus(form, 'Saving record...', 'loading');
    try {
      const data = await request(endpoint, {
        method: 'POST',
        body: body
      });
      if (!data.success) throw new Error(data.message || 'Record could not be saved.');
      setStatus(form, data.message || 'Record saved.', 'success');
      window.setTimeout(function () {
        window.location.reload();
      }, 450);
    } catch (error) {
      setStatus(form, error.message || 'Record could not be saved.', 'error');
      loading(button, false);
    }
  }

  async function updateStatus(button) {
    const endpoint = button.getAttribute('data-endpoint');
    const id = button.getAttribute('data-id');
    const status = button.getAttribute('data-status');
    if (!endpoint || !id || !status) return;
    if (!window.confirm('Update this record status?')) return;
    loading(button, true);
    const body = new FormData();
    body.append('id', id);
    body.append('status', status);
    try {
      const data = await request(endpoint, {
        method: 'POST',
        body: body
      });
      if (!data.success) throw new Error(data.message || 'Status could not be updated.');
      window.location.reload();
    } catch (error) {
      window.alert(error.message || 'Status could not be updated.');
      loading(button, false);
    }
  }

  document.addEventListener('click', function (event) {
    const opener = closest('[data-msr-open]', event);
    const closer = closest('[data-msr-close]', event);
    const statusButton = closest('[data-msr-status]', event);

    if (opener) {
      openModal(opener.getAttribute('data-msr-open'), parseRecord(opener));
    }
    if (closer) {
      closeModal(closer.closest('[data-msr-modal]'));
    }
    if (statusButton) {
      updateStatus(statusButton);
    }
  });

  document.querySelectorAll('[data-msr-modal]').forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeModal(modal);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-msr-modal]:not([hidden])').forEach(closeModal);
  });

  document.querySelectorAll('[data-msr-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      saveForm(form, event.submitter);
    });
  });
}());
