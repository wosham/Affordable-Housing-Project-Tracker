(function () {
  const modal = document.querySelector('[data-ipc-detail-modal]');
  if (!modal || !window.AHPTC) return;

  const body = modal.querySelector('[data-ipc-detail-body]');
  const title = modal.querySelector('[data-ipc-detail-title]');
  const subtitle = modal.querySelector('[data-ipc-detail-sub]');

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('contractor-modal-open');
  }

  function openModal() {
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('contractor-modal-open');
  }

  function money(value) {
    const n = Number(value || 0);
    return 'KES ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
  }

  function renderDetail(data) {
    const ipc = data.ipc || {};
    const lines = data.lines || [];
    const payment = data.payment || null;
    const workflow = data.workflow || {};
    const step = Number(workflow.current_step || 0);

    if (title) title.textContent = 'IPC #' + (ipc.ipc_number || '—');
    if (subtitle) {
      subtitle.textContent = (ipc.project_name || 'Project') + ' · ' + (ipc.period_from || '—') + ' to ' + (ipc.period_to || '—');
    }

    const flowDots = [1, 2, 3, 4, 5, 6].map(function (i) {
      return '<span class="' + (i <= step ? 'is-done' : '') + '"></span>';
    }).join('');

    const lineRows = lines.length
      ? lines.map(function (line) {
          return '<tr>' +
            '<td><strong>' + escapeHtml(line.item_no || '—') + '</strong><small>' + escapeHtml(line.section || '') + '</small></td>' +
            '<td>' + escapeHtml(line.description || '—') + '<small>' + escapeHtml(line.unit || '') + '</small></td>' +
            '<td>' + Number(line.qty_this_period || 0).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + '</td>' +
            '<td>' + Number(line.cumulative_qty || 0).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + '</td>' +
            '<td>' + money(line.rate || 0) + '</td>' +
            '<td><strong>' + money(line.amount || 0) + '</strong></td>' +
          '</tr>';
        }).join('')
      : '<tr><td colspan="6"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No claim lines</strong></div></td></tr>';

    const paymentHtml = payment
      ? '<section class="contractor-ipc-detail-block">' +
          '<h3>Payment</h3>' +
          '<div class="contractor-ipc-detail-grid">' +
            '<span><small>Paid amount</small><strong>' + money(payment.amount || 0) + '</strong></span>' +
            '<span><small>Payment date</small><strong>' + escapeHtml(payment.payment_date || '—') + '</strong></span>' +
            '<span><small>Reference</small><strong>' + escapeHtml(payment.reference_no || '—') + '</strong></span>' +
            '<span><small>Bank / method</small><strong>' + escapeHtml((payment.bank || '—') + ' / ' + (payment.payment_method || '—')) + '</strong></span>' +
          '</div>' +
        '</section>'
      : (String(ipc.status || '') === 'approved'
        ? '<section class="contractor-ipc-detail-block"><h3>Payment</h3><p class="contractor-ipc-detail-note">Approved and awaiting finance payment.</p></section>'
        : (String(ipc.status || '') === 'paid'
          ? '<section class="contractor-ipc-detail-block"><h3>Payment</h3><p class="contractor-ipc-detail-note">Marked paid. Payment schedule details are shown when recorded by finance.</p></section>'
          : ''));

    const paymentLink = String(ipc.status || '') === 'paid' || payment
      ? '<a class="btn btn--outline btn--sm" href="' + escapeHtml((window.AHPTC.baseUrl ? window.AHPTC.baseUrl() + '/' : '') + 'admin/contractor/payment-history.php?project_id=' + encodeURIComponent(ipc.project_id || '') + '&status=paid') + '">Open payment history</a>'
      : '';

    body.innerHTML =
      '<div class="contractor-ipc-detail-chips">' +
        '<article><small>Status</small><strong><span class="badge badge--info">' + escapeHtml(String(ipc.status || 'submitted').replace(/-/g, ' ')) + '</span></strong></article>' +
        '<article><small>Gross</small><strong>' + money(ipc.gross_amount || 0) + '</strong></article>' +
        '<article><small>Retention</small><strong>' + money(ipc.retention_amount || 0) + '</strong></article>' +
        '<article><small>Net payable</small><strong>' + money(ipc.net_amount || 0) + '</strong></article>' +
      '</div>' +
      '<section class="contractor-ipc-detail-block">' +
        '<h3>Workflow</h3>' +
        '<div class="contractor-ipc-mini-flow" style="--step:' + step + '">' + flowDots + '</div>' +
        '<p class="contractor-ipc-detail-note">Reference: ' + escapeHtml(ipc.contractor_reference || 'Not set') +
          ' · Submitted ' + escapeHtml(ipc.submitted_at || '—') + '</p>' +
      '</section>' +
      '<section class="contractor-ipc-detail-block">' +
        '<div class="contractor-ipc-detail-block__head"><h3>Claim lines</h3>' + paymentLink + '</div>' +
        '<div class="contractor-ipc-table-wrap"><table class="contractor-ipc-table contractor-ipc-table--detail"><thead><tr><th>Item</th><th>Description</th><th>This period</th><th>Cumulative</th><th>Rate</th><th>Amount</th></tr></thead><tbody>' +
        lineRows +
        '</tbody></table></div>' +
      '</section>' +
      paymentHtml;
  }

  async function loadDetail(id) {
    if (!id) return;
    openModal();
    if (body) body.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Loading claim...</strong></div>';
    try {
      const data = await window.AHPTC.request('api/contractor/ipc-detail.php?id=' + encodeURIComponent(id), { method: 'GET' });
      renderDetail(data);
    } catch (error) {
      if (body) {
        body.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Claim could not load</strong><span class="empty-state__text">' +
          escapeHtml(error.message || 'Try again.') + '</span></div>';
      }
      if (window.AHPTC.toast) window.AHPTC.toast(error.message || 'Claim could not load.', 'error');
    }
  }

  document.addEventListener('click', function (event) {
    const openBtn = event.target.closest('[data-ipc-open]');
    if (openBtn) {
      event.preventDefault();
      loadDetail(openBtn.getAttribute('data-ipc-open'));
      return;
    }
    if (event.target.closest('[data-ipc-detail-close]') || event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
  });
})();
