(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function money(value) {
    var amount = Number(value || 0);
    return 'KES ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function toast(message, ok) {
    if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
      window.AHPTC.toast(message, ok ? 'success' : 'error');
      return;
    }
    if (window.AHPTC && window.AHPTC.flash) {
      window.AHPTC.flash(message, ok ? 'success' : 'danger');
    }
  }

  function setStatus(node, message, type) {
    if (!node) return;
    node.textContent = message || '';
    node.classList.remove('is-error', 'is-success');
    if (type) node.classList.add(type);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function clearMedia(wrap) {
    if (!wrap) return;
    var path = qs('[data-cms-upload-target]', wrap);
    var name = qs('[data-cms-asset-name]', wrap);
    var preview = qs('[data-cms-asset-preview]', wrap);
    if (path) path.value = '';
    if (name) name.textContent = 'No file selected';
    if (preview) preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
  }

  function renderSelected(opt) {
    var body = qs('[data-selected-body]');
    if (!body || !opt) return;
    body.innerHTML =
      '<div class="finance-selected">' +
        '<strong>IPC #' + escapeHtml(opt.getAttribute('data-ipc-number') || '') + '</strong>' +
        '<span>' + escapeHtml(opt.getAttribute('data-project') || '') + '</span>' +
        '<span>' + escapeHtml(opt.getAttribute('data-contractor') || '') + '</span>' +
        '<dl>' +
          '<div><dt>Gross</dt><dd>' + money(opt.getAttribute('data-gross')) + '</dd></div>' +
          '<div><dt>Retention</dt><dd>' + money(opt.getAttribute('data-retention')) + '</dd></div>' +
          '<div><dt>Net payable</dt><dd>' + money(opt.getAttribute('data-net')) + '</dd></div>' +
          '<div><dt>Approved</dt><dd>' + escapeHtml(opt.getAttribute('data-approved') || '-') + '</dd></div>' +
        '</dl>' +
      '</div>';
  }

  function initPaymentForm() {
    var form = qs('[data-finance-payment-form]');
    if (!form || !window.AHPTC) return;
    var select = qs('[data-ipc-select]', form);
    var amount = qs('[name="amount"]', form);
    var status = qs('[data-finance-form-status]', form);
    var submit = qs('button[type="submit"]', form);
    var mediaWrap = qs('[data-finance-media]', form);

    if (mediaWrap) {
      var clearBtn = qs('[data-finance-media-clear]', mediaWrap);
      if (clearBtn) {
        clearBtn.addEventListener('click', function () { clearMedia(mediaWrap); });
      }
    }

    function syncSelected() {
      if (!select) return;
      var selected = select.options[select.selectedIndex];
      if (!selected) return;
      var net = selected.getAttribute('data-net') || '';
      if (amount && net) amount.value = net;
      renderSelected(selected);
    }

    if (select) {
      select.addEventListener('change', syncSelected);
      syncSelected();
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var review = qs('[name="confirm_review"]', form);
      if (review && !review.checked) {
        setStatus(status, 'Confirm you have checked the payable amount, reference and payment date.', 'is-error');
        toast('Please confirm the payment review checkbox.', false);
        return;
      }

      setStatus(status, 'Saving payment...', '');
      if (submit) submit.disabled = true;

      var payload = {};
      new FormData(form).forEach(function (value, key) {
        payload[key] = value;
      });
      payload.confirm_payment = '1';
      if (review && review.checked) payload.confirm_review = '1';

      window.AHPTC.request('api/finance/process-payment.php', {
        method: 'POST',
        body: payload
      }).then(function (response) {
        setStatus(status, response.message || 'Payment processed successfully.', 'is-success');
        toast(response.message || 'Payment processed successfully.', true);
        window.setTimeout(function () {
          window.location.href = window.AHPTC.baseUrl() + '/admin/finance/approved-ipcs.php';
        }, 850);
      }).catch(function (error) {
        setStatus(status, error.message || 'Payment could not be processed.', 'is-error');
        toast(error.message || 'Payment could not be processed.', false);
      }).finally(function () {
        if (submit) submit.disabled = false;
      });
    });
  }

  function initDetailDrawer() {
    var drawer = qs('[data-finance-detail]');
    if (!drawer || !window.AHPTC) return;
    var title = qs('[data-detail-title]', drawer);
    var summary = qs('[data-detail-summary]', drawer);
    var body = qs('[data-detail-body]', drawer);
    var processLink = qs('[data-detail-process]', drawer);

    function close() {
      drawer.hidden = true;
      document.body.classList.remove('finance-drawer-open');
    }

    function open() {
      drawer.hidden = false;
      document.body.classList.add('finance-drawer-open');
    }

    function metric(label, value) {
      return '<div class="finance-detail-metric"><span>' + escapeHtml(label) + '</span><strong>' + value + '</strong></div>';
    }

    qsa('[data-detail-close]', drawer).forEach(function (button) {
      button.addEventListener('click', close);
    });

    drawer.addEventListener('click', function (event) {
      if (event.target === drawer) close();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !drawer.hidden) close();
    });

    qsa('[data-payment-detail]').forEach(function (button) {
      button.addEventListener('click', function () {
        var id = button.getAttribute('data-payment-detail');
        open();
        if (title) title.textContent = 'Loading IPC...';
        if (summary) summary.textContent = '';
        if (body) body.innerHTML = '';
        if (processLink) {
          processLink.hidden = true;
          processLink.href = '#';
        }

        window.AHPTC.request('api/finance/payment-detail.php?ipc_id=' + encodeURIComponent(id))
          .then(function (response) {
            var ipc = response.ipc || {};
            if (title) title.textContent = 'IPC #' + (ipc.ipc_number || id);
            if (summary) summary.textContent = (ipc.project_name || 'Project') + ' / ' + (ipc.contractor_name || 'Contractor');

            var payments = ipc.payments || [];
            var payHtml = '';
            if (payments.length) {
              payHtml = '<section class="finance-detail-block"><h3>Payment history</h3><div class="finance-payment-history">' +
                payments.map(function (p) {
                  return '<article class="finance-payment-history__item">' +
                    '<strong>' + escapeHtml(p.reference_no || 'Payment') + '</strong>' +
                    '<span>' + escapeHtml(p.payment_date || '-') + ' · ' + escapeHtml(p.payment_method || '') + '</span>' +
                    '<em>' + escapeHtml(p.amount_label || money(p.amount)) + '</em>' +
                    '<small>' + escapeHtml(p.processed_by_name || '') + (p.voucher_no ? ' · ' + escapeHtml(p.voucher_no) : '') + '</small>' +
                  '</article>';
                }).join('') + '</div></section>';
            } else {
              payHtml = '<section class="finance-detail-block"><h3>Payment history</h3><div class="empty-state empty-state--compact"><strong class="empty-state__title">No payments recorded yet</strong></div></section>';
            }

            if (body) {
              body.innerHTML = [
                metric('Gross amount', escapeHtml(ipc.gross_label || money(ipc.gross_amount))),
                metric('Retention', escapeHtml(ipc.retention_label || money(ipc.retention_amount))),
                metric('Net / outstanding', escapeHtml(ipc.outstanding_label || money(ipc.outstanding_amount))),
                metric('Approved date', escapeHtml(ipc.approved_at_label || ipc.approved_at || '-')),
                metric('Approved by', escapeHtml(ipc.approved_by_name || '-')),
                metric('Status', escapeHtml(String(ipc.status || '-').replace(/-/g, ' '))),
                metric('Payment records', String(payments.length))
              ].join('') + payHtml;
            }

            if (processLink && ipc.status === 'approved' && Number(ipc.outstanding_amount || 0) > 0) {
              processLink.hidden = false;
              processLink.href = ipc.process_url || (window.AHPTC.baseUrl() + '/admin/finance/process-payment.php?ipc_id=' + encodeURIComponent(id));
            }
          }).catch(function (error) {
            if (title) title.textContent = 'IPC details';
            if (body) {
              body.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">' + escapeHtml(error.message || 'Details could not be loaded.') + '</strong></div>';
            }
            toast(error.message || 'Details could not be loaded.', false);
          });
      });
    });
  }

  function init() {
    initPaymentForm();
    initDetailDrawer();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
