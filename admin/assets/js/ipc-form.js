(function () {
  'use strict';

  const RETENTION_RATE = 0.05;

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function number(value) {
    const parsed = parseFloat(String(value || '').replace(/,/g, ''));
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function money(value) {
    return number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }

  function setStatus(form, message, state) {
    const status = qs('[data-ipc-status]', form) || qs('[data-form-status]', form);
    if (!status) return;
    status.textContent = message || '';
    status.dataset.state = state || '';
  }

  function linePayload(row) {
    const qtyInput = qs('[name$="[qty_this_period]"]', row);
    const qty = number(qtyInput && qtyInput.value);
    const rate = number(row.dataset.rate);
    const remaining = number(row.dataset.remaining);
    const hiddenId = qs('[name$="[boq_item_id]"]', row);
    const hiddenDescription = qs('[name$="[description]"]', row);
    return {
      boq_item_id: hiddenId ? hiddenId.value : '',
      description: hiddenDescription ? hiddenDescription.value : '',
      qty_this_period: qty,
      remaining: remaining,
      rate: rate,
      amount: qty * rate
    };
  }

  function collectLines(form) {
    return qsa('[data-ipc-line]', form).map(linePayload).filter(function (line) {
      return line.boq_item_id && line.qty_this_period > 0;
    });
  }

  function recalc(form) {
    let gross = 0;
    let warnings = 0;
    qsa('[data-ipc-line]', form).forEach(function (row) {
      const line = linePayload(row);
      const amount = line.qty_this_period * line.rate;
      const amountNode = qs('[data-ipc-line-amount]', row);
      const remainingNode = qs('[data-ipc-remaining]', row);
      row.classList.toggle('is-warning', line.qty_this_period > line.remaining && line.qty_this_period > 0);
      if (line.qty_this_period > line.remaining && line.qty_this_period > 0) warnings += 1;
      if (amountNode) amountNode.textContent = 'KES ' + money(amount);
      if (remainingNode) remainingNode.textContent = Math.max(0, line.remaining - line.qty_this_period).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 });
      gross += amount;
    });

    const retention = gross * RETENTION_RATE;
    const net = Math.max(0, gross - retention);
    qsa('[data-ipc-total]', form).forEach(function (node) { node.textContent = money(gross); });
    qsa('[data-ipc-retention]', form).forEach(function (node) { node.textContent = money(retention); });
    qsa('[data-ipc-net]', form).forEach(function (node) { node.textContent = money(net); });

    if (warnings > 0) {
      setStatus(form, warnings + ' line(s) exceed remaining BOQ quantity.', 'error');
    } else if (collectLines(form).length > 0) {
      setStatus(form, 'Claim totals are ready for review.', 'success');
    } else {
      setStatus(form, '', '');
    }
  }

  function payload(form) {
    const data = new FormData(form);
    const body = {};
    data.forEach(function (value, key) {
      if (key.indexOf('lines[') === 0 || key === 'project_selector') return;
      body[key] = value;
    });
    body.declaration_accepted = data.get('declaration_accepted') === '1' ? '1' : '0';
    body.lines = collectLines(form).map(function (line) {
      return {
        boq_item_id: line.boq_item_id,
        qty_this_period: line.qty_this_period
      };
    });
    return body;
  }

  function initForm(form) {
    if (form.dataset.ipcReady === '1') return;
    form.dataset.ipcReady = '1';

    const projectJump = qs('[data-project-jump]', form);
    if (projectJump) {
      projectJump.addEventListener('change', function () {
        if (projectJump.value) window.location.href = projectJump.value;
      });
    }

    form.addEventListener('input', function (event) {
      if (event.target.closest('[data-ipc-line]')) {
        recalc(form);
      }
    });

    form.addEventListener('change', function (event) {
      if (event.target.closest('[data-ipc-line]')) {
        recalc(form);
      }
    });

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      const button = event.submitter || qs('[type="submit"]', form);
      const oldText = button ? button.innerHTML : '';
      const body = payload(form);
      if (!body.period_from || !body.period_to) {
        setStatus(form, 'Choose the IPC claim period.', 'error');
        return;
      }
      if (body.declaration_accepted !== '1') {
        setStatus(form, 'Accept the contractor declaration before submitting.', 'error');
        return;
      }
      if (!body.lines.length) {
        setStatus(form, 'Enter at least one BOQ quantity for this claim.', 'error');
        return;
      }

      try {
        if (button) {
          button.disabled = true;
          button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Submitting';
        }
        setStatus(form, 'Submitting IPC...', 'loading');
        const response = await fetch(form.getAttribute('action') || 'api/ipcs/submit.php', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken()
          },
          body: JSON.stringify(body)
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.success === false) {
          throw new Error(result.message || 'IPC could not be submitted.');
        }
        setStatus(form, result.message || 'IPC submitted.', 'success');
        window.setTimeout(function () {
          window.location.href = result.redirect || 'ipc-history.php';
        }, 800);
      } catch (error) {
        setStatus(form, error.message || 'IPC could not be submitted.', 'error');
      } finally {
        if (button) {
          button.disabled = false;
          button.innerHTML = oldText;
        }
      }
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
