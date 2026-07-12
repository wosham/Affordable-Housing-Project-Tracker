(function () {
  'use strict';

  const modal = document.querySelector('[data-boq-modal]');
  const form = document.querySelector('[data-boq-form]');
  const title = document.querySelector('[data-boq-title]');
  const subtitle = document.querySelector('[data-boq-subtitle]');
  const current = document.querySelector('[data-boq-current]');
  const usage = document.querySelector('[data-boq-usage]');
  const history = document.querySelector('[data-boq-history]');
  const warning = document.querySelector('[data-boq-warning]');
  const statusText = document.querySelector('[data-boq-status]');
  const api = window.AHPTC || null;
  if (!api || typeof api.request !== 'function') {
    console.error('AHPTC.request is required for BOQ review.');
    return;
  }
  const request = api.request.bind(api);
  const csrfHeaders = {
    'X-CSRF-Form': (api.csrfForm && api.csrfForm()) || 'manager_boq'
  };

  if (!modal || !form) return;

  function field(name) {
    return form.querySelector('[data-field="' + name + '"]');
  }

  function setField(name, value) {
    const input = field(name);
    if (input) input.value = value === null || value === undefined ? '' : value;
  }

  function setStatus(message, state) {
    if (!statusText) return;
    statusText.textContent = message || '';
    statusText.dataset.state = state || '';
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function money(value) {
    return 'KES ' + Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function qty(value) {
    return Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 3
    });
  }

  function closeModal() {
    modal.hidden = true;
  }

  function openModal() {
    modal.hidden = false;
  }

  function loading(button, state) {
    if (!button) return;
    button.disabled = state;
    button.classList.toggle('is-loading', state);
  }

  function renderCurrent(item) {
    if (!current) return;
    current.innerHTML = [
      ['Contract Qty', qty(item.quantity) + ' ' + escapeHtml(item.unit || '')],
      ['Contract Value', money(item.amount)],
      ['Certified Value', money(item.certified_value)],
      ['Paid Value', money(item.paid_value)]
    ].map(function (pair) {
      return '<span><small>' + pair[0] + '</small><strong>' + pair[1] + '</strong></span>';
    }).join('');
  }

  function renderUsage(rows) {
    if (!usage) return;
    if (!rows || !rows.length) {
      usage.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No IPC usage yet</strong></div>';
      return;
    }

    usage.innerHTML = rows.map(function (row) {
      return '<div class="manager-boq-mini-list__item"><span><strong>' +
        escapeHtml(row.ipc_no || ('IPC #' + row.ipc_id)) +
        '</strong><small>' + escapeHtml(row.status_label || row.status || '') +
        ' / ' + escapeHtml(row.submitted_at || '-') +
        '</small></span><em>' + escapeHtml(qty(row.certified_qty)) + '</em></div>';
    }).join('');
  }

  function renderHistory(rows) {
    if (!history) return;
    if (!rows || !rows.length) {
      history.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No review history yet</strong></div>';
      return;
    }

    history.innerHTML = rows.map(function (row) {
      const note = row.note ? '<small>' + escapeHtml(row.note) + '</small>' : '';
      return '<div class="manager-boq-mini-list__item"><span><strong>' +
        escapeHtml(row.user || 'System') +
        '</strong><small>' + escapeHtml(row.created_at || '-') +
        ' / ' + escapeHtml(row.old_review_status || '-') + ' to ' + escapeHtml(row.new_review_status || '-') +
        '</small>' + note + '</span><em>' + escapeHtml(qty(row.new_certified_qty)) + '</em></div>';
    }).join('');
  }

  async function loadItem(id, button) {
    loading(button, true);
    form.reset();
    setStatus('Loading BOQ item...', 'loading');
    if (warning) {
      warning.hidden = true;
      warning.innerHTML = '';
    }
    setField('force', '0');

    try {
      const data = await request('api/manager/boq-detail.php?id=' + encodeURIComponent(id));
      if (!data.success || !data.item) throw new Error(data.message || 'BOQ item could not be loaded.');
      const item = data.item;
      title.textContent = item.item_no ? item.item_no + ' - ' + item.description : item.description;
      subtitle.textContent = (item.project_name || '') + (item.section ? ' / ' + item.section : '');
      setField('id', item.id);
      setField('certified_qty', item.certified_qty);
      setField('paid_qty', item.paid_qty);
      setField('review_status', item.review_status || 'pending');
      setField('risk_status', item.risk_status || 'normal');
      setField('manager_note', item.manager_note || '');
      renderCurrent(item);
      renderUsage(data.usage || []);
      renderHistory(data.history || []);
      setStatus('', '');
      openModal();
    } catch (error) {
      setStatus(error.message || 'BOQ item could not be loaded.', 'error');
      window.alert(error.message || 'BOQ item could not be loaded.');
    } finally {
      loading(button, false);
    }
  }

  async function save(button) {
    const payload = new FormData(form);
    loading(button, true);
    setStatus('Saving BOQ review...', 'loading');

    try {
      const data = await request('api/boq/update-item.php', {
        method: 'POST',
        body: payload,
        headers: csrfHeaders
      });
      if (!data.success) throw new Error(data.message || 'BOQ review could not be saved.');
      setStatus(data.message || 'BOQ review saved.', 'success');
      window.setTimeout(function () {
        window.location.reload();
      }, 450);
    } catch (error) {
      const data = error.data || {};
      if (data.requires_force && data.warnings && warning) {
        setField('force', '1');
        warning.hidden = false;
        warning.innerHTML = '<strong>Confirm this update</strong><ul>' + data.warnings.map(function (item) {
          return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul><small>Press Save Review again to confirm.</small>';
      }
      setStatus(error.message || 'BOQ review could not be saved.', 'error');
      loading(button, false);
    }
  }

  document.addEventListener('click', function (event) {
    const opener = event.target.closest('[data-boq-review]');
    const closer = event.target.closest('[data-boq-close]');

    if (opener) {
      loadItem(opener.getAttribute('data-id'), opener);
    }
    if (closer) {
      closeModal();
    }
  });

  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeModal();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    save(event.submitter);
  });
}());
