(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function number(value) {
    var parsed = parseFloat(String(value || '').replace(/,/g, ''));
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function money(value) {
    return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }
    return fetch(url, options).then(function (response) {
      return response.json();
    });
  }

  function setStatus(form, message, state) {
    var status = qs('[data-ipc-status]', form) || qs('[data-form-status]', form);
    if (!status) return;
    status.textContent = message || '';
    status.dataset.state = state || '';
  }

  function collectLines(form) {
    return qsa('[data-ipc-line]', form).map(function (row) {
      return {
        boq_item_id: qs('[name$="[boq_item_id]"]', row) ? qs('[name$="[boq_item_id]"]', row).value : '',
        description: qs('[name$="[description]"]', row) ? qs('[name$="[description]"]', row).value : '',
        qty_this_period: qs('[name$="[qty_this_period]"]', row) ? qs('[name$="[qty_this_period]"]', row).value : '',
        cumulative_qty: qs('[name$="[cumulative_qty]"]', row) ? qs('[name$="[cumulative_qty]"]', row).value : '',
        rate: qs('[name$="[rate]"]', row) ? qs('[name$="[rate]"]', row).value : '',
        amount: qs('[name$="[amount]"]', row) ? qs('[name$="[amount]"]', row).value : ''
      };
    }).filter(function (line) {
      return line.description || number(line.amount) > 0 || number(line.qty_this_period) > 0;
    });
  }

  function recalc(form) {
    var gross = 0;
    qsa('[data-ipc-line]', form).forEach(function (row) {
      var qty = number((qs('[name$="[qty_this_period]"]', row) || {}).value);
      var rate = number((qs('[name$="[rate]"]', row) || {}).value);
      var amountField = qs('[name$="[amount]"]', row);
      var amount = amountField && amountField.value !== '' ? number(amountField.value) : qty * rate;
      if (amountField && amountField.value === '') amountField.value = amount ? amount.toFixed(2) : '';
      gross += amount;
    });

    var grossField = qs('[name="gross_amount"]', form);
    var retentionField = qs('[name="retention_amount"]', form);
    var netField = qs('[name="net_amount"]', form);
    if (grossField && gross > 0) grossField.value = gross.toFixed(2);
    var retention = number(retentionField && retentionField.value);
    if (netField) netField.value = Math.max(0, number(grossField && grossField.value) - retention).toFixed(2);

    qsa('[data-ipc-total]', form).forEach(function (node) {
      node.textContent = money(number(grossField && grossField.value));
    });
    qsa('[data-ipc-net]', form).forEach(function (node) {
      node.textContent = money(number(netField && netField.value));
    });
  }

  function addLine(form) {
    var template = qs('template[data-ipc-line-template]', form);
    var list = qs('[data-ipc-lines]', form);
    if (!template || !list) return;
    var index = qsa('[data-ipc-line]', list).length;
    var html = template.innerHTML.replace(/__INDEX__/g, String(index));
    var wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    list.appendChild(wrapper.firstElementChild);
  }

  function payload(form) {
    var data = new FormData(form);
    var body = {};
    data.forEach(function (value, key) {
      if (key.indexOf('lines[') === 0) return;
      body[key] = value;
    });
    body.lines = collectLines(form);
    return body;
  }

  function initForm(form) {
    if (form.dataset.ipcReady === '1') return;
    form.dataset.ipcReady = '1';

    form.addEventListener('input', function (event) {
      if (event.target.closest('[data-ipc-line]') || ['gross_amount', 'retention_amount'].indexOf(event.target.name) !== -1) {
        recalc(form);
      }
    });

    form.addEventListener('click', function (event) {
      var add = event.target.closest('[data-ipc-add-line]');
      var remove = event.target.closest('[data-ipc-remove-line]');
      if (add) {
        event.preventDefault();
        addLine(form);
        recalc(form);
      }
      if (remove) {
        event.preventDefault();
        var row = remove.closest('[data-ipc-line]');
        if (row) row.remove();
        recalc(form);
      }
    });

    form.addEventListener('submit', function (event) {
      if (form.hasAttribute('data-ipc-native-submit')) return;
      event.preventDefault();
      var button = event.submitter || qs('[type="submit"]', form);
      if (button) button.disabled = true;
      setStatus(form, 'Submitting IPC...', 'loading');
      request(form.getAttribute('action') || 'api/ipcs/submit.php', {
        method: 'POST',
        body: payload(form)
      }).then(function (result) {
        if (!result.success) throw new Error(result.message || 'IPC could not be submitted.');
        setStatus(form, result.message || 'IPC submitted.', 'success');
        form.dispatchEvent(new CustomEvent('ipc:submitted', { detail: result, bubbles: true }));
      }).catch(function (error) {
        setStatus(form, error.message || 'IPC could not be submitted.', 'error');
      }).finally(function () {
        if (button) button.disabled = false;
      });
    });

    recalc(form);
  }

  function init() {
    qsa('[data-ipc-form]').forEach(initForm);
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, {
    initIPCForms: init
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
