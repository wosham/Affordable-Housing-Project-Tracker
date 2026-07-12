(function () {
  'use strict';

  var api = window.AHPTC || null;
  if (!api || typeof api.request !== 'function') {
    console.error('AHPTC.request is required for manager contract controls.');
    return;
  }
  var request = api.request.bind(api);
  var csrfHeaders = {
    'X-CSRF-Form': (api.csrfForm && api.csrfForm()) || 'manager_contract_controls'
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
    var output = form.querySelector('[data-mcc-status-text]');
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
      if (
        name === 'data-mcc-open' ||
        name === 'data-mcc-status' ||
        name === 'data-endpoint' ||
        name === 'data-id' ||
        name === 'data-status' ||
        name === 'data-ld-rate' ||
        name === 'data-ld-days'
      ) {
        return;
      }
      var key = name.slice(5).replace(/-/g, '_');
      data[key] = attr.value;
    });
    return data;
  }

  function updateLdTotal(form) {
    var total = form.querySelector('[data-ld-total]');
    if (!total) return;
    var rate = Number((form.querySelector('[data-ld-rate]') || {}).value || 0);
    var days = Number((form.querySelector('[data-ld-days]') || {}).value || 0);
    total.value = 'KES ' + (rate * days).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function openModal(type, record) {
    var modal = document.querySelector('[data-mcc-modal="' + type + '"]');
    if (!modal) return;
    var form = modal.querySelector('[data-mcc-form]');
    var title = modal.querySelector('[data-mcc-title]');
    var labels = { eot: 'EOT review', ld: 'LD record', subcontractor: 'subcontractor' };
    form.reset();
    form.querySelectorAll('[data-field]').forEach(function (input) {
      if (input.type === 'hidden') input.value = '';
    });
    setStatus(form, '', '');
    Object.keys(record || {}).forEach(function (key) {
      setField(form, key, record[key]);
    });
    if (title) title.textContent = (record && record.id ? 'Edit ' : 'New ') + (labels[type] || 'record');
    updateLdTotal(form);
    modal.hidden = false;
  }

  function closeModal(modal) {
    if (modal) modal.hidden = true;
  }

  async function saveForm(form, button) {
    loading(button, true);
    setStatus(form, 'Saving...', 'loading');
    try {
      var data = await request(form.getAttribute('data-endpoint'), {
        method: 'POST',
        body: new FormData(form),
        headers: csrfHeaders
      });
      if (!data.success) throw new Error(data.message || 'Record could not be saved.');
      setStatus(form, data.message || 'Saved.', 'success');
      window.setTimeout(function () {
        window.location.reload();
      }, 450);
    } catch (error) {
      setStatus(form, error.message || 'Record could not be saved.', 'error');
      loading(button, false);
    }
  }

  async function updateStatus(button) {
    if (!window.confirm('Update this record status?')) return;
    loading(button, true);
    var body = new FormData();
    body.append('id', button.getAttribute('data-id'));
    body.append('status', button.getAttribute('data-status'));
    try {
      var data = await request(button.getAttribute('data-endpoint'), {
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
    var opener = event.target.closest('[data-mcc-open]');
    var closer = event.target.closest('[data-mcc-close]');
    var status = event.target.closest('[data-mcc-status]');
    if (opener) openModal(opener.getAttribute('data-mcc-open'), recordFromButton(opener));
    if (closer) closeModal(closer.closest('[data-mcc-modal]'));
    if (status) updateStatus(status);
  });

  document.querySelectorAll('[data-mcc-modal]').forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeModal(modal);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('[data-mcc-modal]:not([hidden])').forEach(closeModal);
    }
  });

  document.querySelectorAll('[data-mcc-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      saveForm(form, event.submitter);
    });
    form.addEventListener('input', function () {
      updateLdTotal(form);
    });
  });
}());
