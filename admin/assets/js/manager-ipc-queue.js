(function () {
  'use strict';

  const decisionModal = document.querySelector('[data-ipc-modal]');
  const decisionForm = document.querySelector('[data-ipc-form]');
  const detailPanel = document.querySelector('[data-ipc-detail-panel]');
  const request = window.AHPTC && window.AHPTC.request ? window.AHPTC.request : null;

  if (!request) return;

  function qs(selector, root) {
    return (root || document).querySelector(selector);
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

  function field(name) {
    return decisionForm ? decisionForm.querySelector('[data-field="' + name + '"]') : null;
  }

  function setField(name, value) {
    const input = field(name);
    if (input) input.value = value || '';
  }

  function setStatus(message, state) {
    const node = qs('[data-ipc-status]', decisionForm);
    if (!node) return;
    node.textContent = message || '';
    node.dataset.state = state || '';
  }

  function loading(button, state) {
    if (!button) return;
    button.disabled = state;
    button.classList.toggle('is-loading', state);
  }

  function closeDecision() {
    if (decisionModal) decisionModal.hidden = true;
  }

  function openDecision(button) {
    if (!decisionModal || !decisionForm) return;
    const mode = button.getAttribute('data-ipc-decision') || '';
    const isReject = mode === 'reject';
    decisionForm.reset();
    setStatus('', '');
    setField('ipc_id', button.getAttribute('data-id') || '');
    setField('mode', mode);
    qs('[data-ipc-modal-title]', decisionModal).textContent = (isReject ? 'Reject ' : 'Endorse ') + (button.getAttribute('data-title') || 'IPC');
    qs('[data-ipc-modal-summary]', decisionModal).textContent = button.getAttribute('data-summary') || 'Confirm this IPC decision.';
    qs('[data-ipc-comment-label]', decisionModal).textContent = isReject ? 'Rejection reason' : 'Endorsement note';
    field('comment').required = isReject;
    decisionModal.hidden = false;
    field('comment').focus();
  }

  function closeDetail() {
    if (detailPanel) detailPanel.hidden = true;
  }

  function renderMetrics(ipc) {
    const node = qs('[data-detail-metrics]', detailPanel);
    if (!node) return;
    const metrics = [
      ['Status', ipc.status_label || ipc.status],
      ['Gross', money(ipc.gross_amount)],
      ['Retention', money(ipc.retention_amount)],
      ['Net Payable', money(ipc.net_amount)],
      ['Period', ipc.period_label],
      ['Submitted', ipc.submitted_label],
      ['Contractor', ipc.contractor_name],
      ['Project', ipc.project_name]
    ];
    node.innerHTML = metrics.map(function (pair) {
      return '<span><small>' + escapeHtml(pair[0]) + '</small><strong>' + escapeHtml(pair[1] || '-') + '</strong></span>';
    }).join('');
  }

  function renderLines(lines) {
    const node = qs('[data-detail-lines]', detailPanel);
    if (!node) return;
    if (!lines || !lines.length) {
      node.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No line items recorded</strong></div>';
      return;
    }
    node.innerHTML = lines.map(function (line) {
      return '<div class="manager-ipc-line"><span><strong>' +
        escapeHtml((line.item_code ? line.item_code + ' - ' : '') + line.description) +
        '</strong><small>This period: ' + escapeHtml(qty(line.qty_this_period)) +
        ' / Cumulative: ' + escapeHtml(qty(line.cumulative_qty)) +
        ' / Rate: ' + escapeHtml(money(line.rate)) +
        '</small></span><em>' + escapeHtml(money(line.amount)) + '</em></div>';
    }).join('');
  }

  function renderTimeline(rows) {
    const node = qs('[data-detail-timeline]', detailPanel);
    if (!node) return;
    if (!rows || !rows.length) {
      node.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No workflow actions yet</strong></div>';
      return;
    }
    node.innerHTML = rows.map(function (row) {
      return '<div class="manager-ipc-time"><span><strong>' +
        escapeHtml(row.action_label || row.action) +
        '</strong><small>' + escapeHtml(row.actor_name || 'Staff') +
        (row.actor_role ? ' / ' + escapeHtml(row.actor_role) : '') +
        ' / ' + escapeHtml(row.actioned_at || '-') +
        '</small>' + (row.comments ? '<p>' + escapeHtml(row.comments) + '</p>' : '') +
        '</span><em>Step ' + escapeHtml(row.step || '-') + '</em></div>';
    }).join('');
  }

  function renderWarnings(warnings) {
    const wrap = qs('[data-detail-warnings-wrap]', detailPanel);
    const node = qs('[data-detail-warnings]', detailPanel);
    if (!wrap || !node) return;
    if (!warnings || !warnings.length) {
      wrap.hidden = true;
      node.innerHTML = '';
      return;
    }
    wrap.hidden = false;
    node.innerHTML = warnings.map(function (warning) {
      return '<li>' + escapeHtml(warning) + '</li>';
    }).join('');
  }

  async function openDetail(id, button) {
    if (!detailPanel) return;
    loading(button, true);
    try {
      const data = await request('api/manager/ipc-detail.php?id=' + encodeURIComponent(id));
      if (!data.success || !data.ipc) throw new Error(data.message || 'IPC details could not be loaded.');
      const ipc = data.ipc;
      qs('[data-detail-title]', detailPanel).textContent = 'IPC #' + (ipc.ipc_number || ipc.id);
      qs('[data-detail-summary]', detailPanel).textContent = (ipc.project_name || '') + ' / ' + money(ipc.net_amount);
      renderMetrics(ipc);
      renderLines(data.lines || []);
      renderTimeline(data.timeline || []);
      renderWarnings(ipc.warnings || []);
      detailPanel.hidden = false;
    } catch (error) {
      window.alert(error.message || 'IPC details could not be loaded.');
    } finally {
      loading(button, false);
    }
  }

  async function submitDecision(event) {
    event.preventDefault();
    const mode = field('mode').value;
    const comment = field('comment').value.trim();
    const submit = qs('[data-ipc-submit]', decisionForm);

    if (mode === 'reject' && !comment) {
      setStatus('A rejection reason is required.', 'error');
      return;
    }

    loading(submit, true);
    setStatus(mode === 'reject' ? 'Rejecting IPC...' : 'Endorsing IPC...', 'loading');

    try {
      const endpoint = mode === 'reject' ? 'api/ipcs/reject.php' : 'api/ipcs/endorse.php';
      const payload = mode === 'reject'
        ? { ipc_id: field('ipc_id').value, reason: comment }
        : { ipc_id: field('ipc_id').value, comment: comment };
      const data = await request(endpoint, { method: 'POST', body: payload });
      if (!data.success) throw new Error(data.message || 'IPC decision could not be saved.');
      setStatus(data.message || 'IPC decision saved.', 'success');
      window.setTimeout(function () {
        window.location.reload();
      }, 450);
    } catch (error) {
      setStatus(error.message || 'IPC decision could not be saved.', 'error');
      loading(submit, false);
    }
  }

  document.addEventListener('click', function (event) {
    const decision = event.target.closest('[data-ipc-decision]');
    const detail = event.target.closest('[data-ipc-detail]');
    const close = event.target.closest('[data-ipc-close]');
    const detailClose = event.target.closest('[data-detail-close]');

    if (decision) openDecision(decision);
    if (detail) openDetail(detail.getAttribute('data-id'), detail);
    if (close) closeDecision();
    if (detailClose) closeDetail();
  });

  if (decisionModal) {
    decisionModal.addEventListener('click', function (event) {
      if (event.target === decisionModal) closeDecision();
    });
  }

  if (detailPanel) {
    detailPanel.addEventListener('click', function (event) {
      if (event.target === detailPanel) closeDetail();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    closeDecision();
    closeDetail();
  });

  if (decisionForm) decisionForm.addEventListener('submit', submitDecision);
}());
