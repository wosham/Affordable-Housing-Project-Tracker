(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function flash(message, ok) {
    if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
      window.AHPTC.toast(message, ok ? 'success' : 'error');
      return;
    }
    if (window.AHPTC && window.AHPTC.flash) {
      window.AHPTC.flash(message, ok ? 'success' : 'danger');
      return;
    }
    window.alert(message);
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }
    return fetch(url, options).then(function (response) { return response.json(); });
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function setStatus(form, message, kind) {
    var node = qs('[data-form-status]', form);
    if (!node) return;
    if (!message) {
      node.hidden = true;
      node.textContent = '';
      node.className = 'clerk-record-form-status';
      return;
    }
    node.hidden = false;
    node.textContent = message;
    node.className = 'clerk-record-form-status is-' + (kind || 'info');
  }

  function updateLabourTotal(form) {
    var total = qsa('[data-labour-count]', form).reduce(function (sum, input) {
      return sum + Math.max(0, parseInt(input.value || '0', 10) || 0);
    }, 0);
    var node = qs('[data-labour-total]', form);
    if (node) node.textContent = String(total);
  }

  function bindLabour(form) {
    qsa('[data-labour-count]', form).forEach(function (input) {
      input.addEventListener('input', function () { updateLabourTotal(form); });
    });
    updateLabourTotal(form);
  }

  function fillFromDataset(form, select) {
    if (!select || !select.selectedOptions || !select.selectedOptions[0]) return;
    var opt = select.selectedOptions[0];
    if (!opt.value) return;
    var map = {
      material: 'material',
      supplier: 'supplier',
      'delivery-date': 'delivery_date',
      quantity: 'quantity',
      unit: 'unit',
      'delivery-note': 'delivery_note_no',
      'verified-qty': 'verified_quantity',
      'equipment-type': 'equipment_type',
      registration: 'registration',
      owner: 'owner',
      'date-on-site': 'date_on_site',
      condition: 'condition',
      status: 'status',
      'check-status': 'check_status'
    };
    Object.keys(map).forEach(function (dataKey) {
      var attr = 'data-' + dataKey;
      if (!opt.hasAttribute(attr)) return;
      var field = qs('[name="' + map[dataKey] + '"]', form);
      if (field) field.value = opt.getAttribute(attr) || '';
    });
  }

  function bindPickers(form) {
    var materialPicker = qs('[data-material-picker]', form);
    if (materialPicker) {
      materialPicker.addEventListener('change', function () {
        if (!materialPicker.value) return;
        fillFromDataset(form, materialPicker);
      });
    }
    var equipmentPicker = qs('[data-equipment-picker]', form);
    if (equipmentPicker) {
      equipmentPicker.addEventListener('change', function () {
        if (!equipmentPicker.value) return;
        fillFromDataset(form, equipmentPicker);
      });
    }
  }

  function fillForm(form, record) {
    if (!form || !record) return;
    var idField = qs('[data-cr-edit-id]', form) || qs('[name="id"]', form);
    if (idField && idField.tagName !== 'SELECT') {
      idField.value = record.id || '';
    } else if (idField && idField.tagName === 'SELECT') {
      idField.value = String(record.id || '');
    }

    qsa('[name]', form).forEach(function (field) {
      if (!field.name || field.name === 'project_id') return;
      if (field.type === 'button' || field.type === 'submit') return;
      if (field.name === 'id' && field.tagName === 'SELECT') {
        field.value = String(record.id || '');
        return;
      }
      if (field.name === 'id') {
        field.value = record.id || '';
        return;
      }
      var value = record[field.name];
      if (field.type === 'checkbox') {
        field.checked = value === 1 || value === '1' || value === true;
        return;
      }
      if (value == null) {
        if (field.tagName !== 'SELECT') field.value = '';
        return;
      }
      if (field.type === 'date') {
        field.value = String(value).slice(0, 10);
        return;
      }
      field.value = String(value);
    });
    updateLabourTotal(form);
  }

  function resetForm(form) {
    if (!form) return;
    form.reset();
    var idField = qs('[data-cr-edit-id]', form);
    if (idField) idField.value = '';
    var picker = qs('[data-material-picker], [data-equipment-picker]', form);
    if (picker) picker.value = '';
    updateLabourTotal(form);
    setStatus(form, '', '');
  }

  function submitForm(form, submitter) {
    var button = submitter || qs('button[type="submit"]', form) || qs('[data-cr-edit-save]');
    var old = button ? button.innerHTML : '';
    if (button) {
      button.disabled = true;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving';
    }
    return request(form.action, { method: 'POST', body: new FormData(form) }).then(function (data) {
      if (!data.success) throw new Error(data.message || 'Record could not be saved.');
      flash(data.message || 'Record saved successfully.', true);
      setStatus(form, data.message || 'Saved.', 'success');
      window.setTimeout(function () { window.location.reload(); }, 400);
      return data;
    }).catch(function (error) {
      flash(error.message || 'Record could not be saved.', false);
      setStatus(form, error.message || 'Record could not be saved.', 'error');
      throw error;
    }).finally(function () {
      if (button) {
        button.disabled = false;
        button.innerHTML = old;
      }
    });
  }

  function setOverlayOpen(overlay, open) {
    if (!overlay) return;
    overlay.hidden = !open;
    overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    var anyOpen = qsa('.cr-overlay').some(function (node) { return !node.hidden; });
    document.body.classList.toggle('cr-modal-open', anyOpen);
  }

  function statusBadgeClass(status) {
    status = String(status || '').toLowerCase();
    if (['verified', 'accepted', 'present', 'submitted', 'reviewed', 'passed'].indexOf(status) >= 0) return 'badge--success';
    if (['pending', 'none', 'minor', 'draft', 'on-site', 'good'].indexOf(status) >= 0) return 'badge--warning';
    if (['queried', 'rejected', 'missing', 'severe', 'poor', 'failed'].indexOf(status) >= 0) return 'badge--danger';
    return 'badge--info';
  }

  function fieldCard(label, value, wide) {
    return '<article class="cr-detail-field' + (wide ? ' is-wide' : '') + '"><small>' + escapeHtml(label) + '</small><strong>' + escapeHtml(value || '—') + '</strong></article>';
  }

  function initCreateForm(form) {
    bindLabour(form);
    bindPickers(form);
    var resetBtn = qs('[data-form-reset]', form);
    if (resetBtn) {
      resetBtn.addEventListener('click', function () { resetForm(form); });
    }
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitForm(form, event.submitter).catch(function () {});
    });
  }

  function initModals(pageForm) {
    var type = pageForm ? (pageForm.dataset.recordType || '') : '';
    var detailUrl = pageForm ? (pageForm.dataset.detailUrl || '') : '';
    if (!detailUrl || !type) return;

    var viewModal = qs('[data-cr-view-modal]');
    var editModal = qs('[data-cr-edit-modal]');
    var editForm = editModal ? qs('[data-cr-edit-form]', editModal) : null;
    var viewBody = viewModal ? qs('[data-cr-view-body]', viewModal) : null;
    var viewTitle = viewModal ? qs('[data-cr-view-title]', viewModal) : null;
    var viewSub = viewModal ? qs('[data-cr-view-sub]', viewModal) : null;
    var viewEditBtn = viewModal ? qs('[data-cr-view-edit]', viewModal) : null;
    var editSub = editModal ? qs('[data-cr-edit-sub]', editModal) : null;
    var editSaveBtn = editModal ? qs('[data-cr-edit-save]', editModal) : null;
    var lastId = 0;

    if (editForm) {
      bindLabour(editForm);
      bindPickers(editForm);
    }

    function closeView() { setOverlayOpen(viewModal, false); }
    function closeEdit() {
      setOverlayOpen(editModal, false);
      if (editForm) resetForm(editForm);
    }

    function renderView(data) {
      var view = (data && data.view) || {};
      lastId = view.id || 0;
      if (viewTitle) viewTitle.textContent = view.title || 'Record';
      if (viewSub) {
        viewSub.textContent = (view.project_name || 'Project')
          + (view.date ? ' · ' + String(view.date).slice(0, 10) : '')
          + (view.status ? ' · ' + String(view.status).replace(/-/g, ' ') : '');
      }
      var chips = '<div class="cr-detail-chips">'
        + '<article><small>Status</small><strong><span class="badge ' + statusBadgeClass(view.status) + '">' + escapeHtml(String(view.status || '—').replace(/-/g, ' ')) + '</span></strong></article>'
        + '<article><small>Reference</small><strong>#' + escapeHtml(String(view.id || '—')) + '</strong></article>'
        + '<article><small>Date</small><strong>' + escapeHtml(view.date ? String(view.date).slice(0, 10) : '—') + '</strong></article>'
        + '<article><small>Project</small><strong>' + escapeHtml(view.project_name || '—') + '</strong></article>'
        + '</div>';
      var fields = (view.fields || []).map(function (f) {
        return fieldCard(f.label, f.value, !!f.wide);
      }).join('');
      if (viewBody) {
        viewBody.innerHTML = chips
          + (fields ? '<section class="cr-detail-block"><h3>Details</h3><div class="cr-detail-grid">' + fields + '</div></section>' : '');
      }
    }

    function loadRecord(id, mode) {
      if (!id) return;
      var url = detailUrl + (detailUrl.indexOf('?') >= 0 ? '&' : '?') + 'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id);

      if (mode === 'edit') {
        if (!editForm) {
          flash('Edit form is not available.', false);
          return;
        }
        closeView();
        setOverlayOpen(editModal, true);
        if (editSub) editSub.textContent = 'Loading record #' + id + '...';
        setStatus(editForm, 'Loading…', 'info');
        request(url, { method: 'GET' }).then(function (data) {
          if (!data.success || !data.record) throw new Error(data.message || 'Record could not be loaded.');
          lastId = data.record.id;
          fillForm(editForm, data.record);
          if (editSub) editSub.textContent = 'Editing record #' + id;
          setStatus(editForm, '', '');
        }).catch(function (error) {
          flash(error.message || 'Record could not be loaded.', false);
          setStatus(editForm, error.message || 'Load failed', 'error');
        });
        return;
      }

      closeEdit();
      setOverlayOpen(viewModal, true);
      if (viewBody) {
        viewBody.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Loading…</strong></div>';
      }
      request(url, { method: 'GET' }).then(function (data) {
        if (!data.success) throw new Error(data.message || 'Record could not be loaded.');
        renderView(data);
      }).catch(function (error) {
        flash(error.message || 'Record could not be loaded.', false);
        if (viewBody) {
          viewBody.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">' + escapeHtml(error.message || 'Load failed') + '</strong></div>';
        }
      });
    }

    document.addEventListener('click', function (event) {
      var viewBtn = event.target.closest && event.target.closest('[data-view-record]');
      if (viewBtn) {
        event.preventDefault();
        loadRecord(viewBtn.getAttribute('data-id'), 'view');
        return;
      }
      var editBtn = event.target.closest && event.target.closest('[data-edit-record]');
      if (editBtn) {
        event.preventDefault();
        loadRecord(editBtn.getAttribute('data-id'), 'edit');
        return;
      }
      if (event.target.closest && event.target.closest('[data-cr-view-close]')) {
        closeView();
        return;
      }
      if (event.target.closest && event.target.closest('[data-cr-edit-close]')) {
        closeEdit();
        return;
      }
      if (event.target === viewModal) closeView();
      if (event.target === editModal) closeEdit();
    });

    if (viewEditBtn) {
      viewEditBtn.addEventListener('click', function () {
        if (lastId) loadRecord(lastId, 'edit');
      });
    }
    if (editSaveBtn && editForm) {
      editSaveBtn.addEventListener('click', function () {
        submitForm(editForm, editSaveBtn).catch(function () {});
      });
      editForm.addEventListener('submit', function (event) {
        event.preventDefault();
        submitForm(editForm, editSaveBtn).catch(function () {});
      });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      if (editModal && !editModal.hidden) closeEdit();
      else if (viewModal && !viewModal.hidden) closeView();
    });
  }

  function init() {
    qsa('[data-clerk-record-form]').forEach(function (form) {
      initCreateForm(form);
      initModals(form);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
