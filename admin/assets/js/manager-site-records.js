(function () {
  'use strict';

  var api = window.AHPTC || null;
  if (!api || typeof api.request !== 'function') {
    console.error('AHPTC.request is required for manager site records.');
    return;
  }
  var request = api.request.bind(api);
  var csrfHeaders = {
    'X-CSRF-Form': (api.csrfForm && api.csrfForm()) || 'manager_site_records'
  };

  function field(form, name) {
    return form.querySelector('[data-field="' + name + '"]');
  }

  function setField(form, name, value) {
    var input = field(form, name);
    if (!input) return;
    input.value = value === null || value === undefined ? '' : value;
  }

  function setStatus(form, message, state) {
    var output = form.querySelector('[data-msr-status-text]');
    if (!output) return;
    output.textContent = message || '';
    output.dataset.state = state || '';
  }

  function loading(button, state) {
    if (!button) return;
    button.disabled = !!state;
    button.classList.toggle('is-loading', !!state);
  }

  function recordFromButton(button) {
    var data = {};
    if (!button || !button.attributes) return data;
    Array.prototype.forEach.call(button.attributes, function (attr) {
      var name = attr.name || '';
      if (name.indexOf('data-') !== 0) return;
      if (name === 'data-msr-open' || name === 'data-msr-status' || name === 'data-endpoint' || name === 'data-id' || name === 'data-status') return;
      var key = name.slice(5).replace(/-/g, '_');
      data[key] = attr.value;
    });
    return data;
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
  }

  function openModal(type, record) {
    var modal = document.querySelector('[data-msr-modal="' + type + '"]');
    if (!modal) return;
    var form = modal.querySelector('[data-msr-form]');
    var title = modal.querySelector('[data-msr-title]');
    var labels = {
      meeting: 'meeting minutes',
      incident: 'H&S incident',
      community: 'community engagement'
    };
    resetForm(form);
    if (record && record.id) {
      fillForm(form, record);
      if (title) title.textContent = 'Edit ' + (labels[type] || 'record');
    } else {
      var today = new Date().toISOString().slice(0, 10);
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
    var endpoint = form.getAttribute('data-endpoint');
    if (!endpoint) return;
    var body = new FormData(form);
    loading(button, true);
    setStatus(form, 'Saving record...', 'loading');
    try {
      var data = await request(endpoint, {
        method: 'POST',
        body: body,
        headers: csrfHeaders
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
    var endpoint = button.getAttribute('data-endpoint');
    var id = button.getAttribute('data-id');
    var status = button.getAttribute('data-status');
    if (!endpoint || !id || !status) return;
    if (!window.confirm('Update this record status?')) return;
    loading(button, true);
    var body = new FormData();
    body.append('id', id);
    body.append('status', status);
    try {
      var data = await request(endpoint, {
        method: 'POST',
        body: body,
        headers: csrfHeaders
      });
      if (!data.success) throw new Error(data.message || 'Status could not be updated.');
      window.location.reload();
    } catch (error) {
      window.alert(error.message || 'Status could not be updated.');
      loading(button, false);
    }
  }

  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-msr-open]');
    var closer = event.target.closest('[data-msr-close]');
    var statusButton = event.target.closest('[data-msr-status]');

    if (opener) {
      openModal(opener.getAttribute('data-msr-open'), recordFromButton(opener));
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
