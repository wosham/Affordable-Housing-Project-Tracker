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
      node.className = 'clerk-quality-form-status';
      return;
    }
    node.hidden = false;
    node.textContent = message;
    node.className = 'clerk-quality-form-status is-' + (kind || 'info');
  }

  function clearMedia(wrap) {
    if (!wrap) return;
    var path = qs('[data-cms-upload-target]', wrap);
    var mediaId = qs('[data-media-id-target]', wrap);
    var name = qs('[data-cms-asset-name]', wrap);
    var preview = qs('[data-cms-asset-preview]', wrap);
    if (path) path.value = '';
    if (mediaId) mediaId.value = '';
    if (name) name.textContent = 'No file selected';
    if (preview) {
      preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
    }
  }

  function setMedia(wrap, path, mediaId, label) {
    if (!wrap) return;
    var pathInput = qs('[data-cms-upload-target]', wrap);
    var mediaInput = qs('[data-media-id-target]', wrap);
    var name = qs('[data-cms-asset-name]', wrap);
    var preview = qs('[data-cms-asset-preview]', wrap);
    if (pathInput) pathInput.value = path || '';
    if (mediaInput) mediaInput.value = mediaId || '';
    if (name) name.textContent = label || (path ? String(path).split('/').pop() : 'No file selected');
    if (preview) {
      var isImage = /\.(jpg|jpeg|png|webp|gif)$/i.test(path || '');
      var base = window.AHPTC && typeof window.AHPTC.baseUrl === 'function' ? window.AHPTC.baseUrl() : '';
      if (isImage && path && String(path).indexOf('uploads/') === 0 && base) {
        preview.innerHTML = '<img src="' + escapeHtml(base + '/' + path) + '" alt="">';
      } else if (path) {
        preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
      } else {
        preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
      }
    }
  }

  function initMedia(form) {
    qsa('[data-cq-media]', form).forEach(function (wrap) {
      var clearBtn = qs('[data-cq-media-clear]', wrap);
      if (clearBtn && clearBtn.dataset.ready !== '1') {
        clearBtn.dataset.ready = '1';
        clearBtn.addEventListener('click', function () { clearMedia(wrap); });
      }
    });
  }

  function fillForm(form, record) {
    if (!form || !record) return;
    var idField = qs('[data-cq-edit-id]', form) || qs('[name="id"]', form);
    if (idField) idField.value = record.id || '';

    qsa('[name]', form).forEach(function (field) {
      if (!field.name || field.name === 'project_id') return;
      if (field.type === 'file' || field.type === 'button' || field.type === 'submit') return;
      if (field.name === 'id') {
        field.value = record.id || '';
        return;
      }
      var value = record[field.name];
      if (field.type === 'checkbox') {
        field.checked = value === 1 || value === '1' || value === true || value === 'true';
        return;
      }
      if (value == null || value === '') {
        if (field.tagName === 'SELECT') {
          // keep first option / default if empty
          if (field.options && field.options.length) {
            // try exact empty match then leave
          }
          field.value = field.value;
          if (value === '' || value == null) {
            // only clear if blank string stored
            if (value === '') field.value = '';
          }
          return;
        }
        field.value = '';
        return;
      }
      if (field.type === 'date') {
        field.value = String(value).slice(0, 10);
        return;
      }
      field.value = String(value);
      // force select match
      if (field.tagName === 'SELECT') {
        field.value = String(value);
      }
    });

    qsa('[data-cq-media]', form).forEach(function (wrap) {
      var pathInput = qs('[data-cms-upload-target]', wrap);
      var mediaInput = qs('[data-media-id-target]', wrap);
      if (!pathInput) return;
      var pathKey = pathInput.name;
      var mediaKey = mediaInput ? mediaInput.name : '';
      var path = record[pathKey] || record.filename || record.document_path || record.photo_path || record.attachment_path || '';
      var mediaId = (mediaKey && record[mediaKey]) || record.evidence_media_id || record.media_id || '';
      setMedia(wrap, path, mediaId, record.original_name || '');
    });
  }

  function resetForm(form) {
    if (!form) return;
    form.reset();
    var idField = qs('[data-cq-edit-id]', form) || qs('[name="id"]', form);
    if (idField) idField.value = '';
    qsa('[data-cq-media]', form).forEach(clearMedia);
    setStatus(form, '', '');
  }

  function validateMedia(form) {
    var wraps = qsa('[data-cq-media][data-media-required="1"]', form);
    for (var i = 0; i < wraps.length; i++) {
      var path = qs('[data-cms-upload-target]', wraps[i]);
      if (!path || !String(path.value || '').trim()) {
        var btn = qs('[data-media-picker-open]', wraps[i]);
        if (btn) btn.focus();
        return 'Please choose or upload the required evidence file.';
      }
    }
    return '';
  }

  function setOverlayOpen(overlay, open) {
    if (!overlay) return;
    overlay.hidden = !open;
    overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    var anyOpen = qsa('.cq-overlay').some(function (node) { return !node.hidden; })
      || (qs('[data-cq-lightbox]') && !qs('[data-cq-lightbox]').hidden);
    document.body.classList.toggle('cq-modal-open', !!anyOpen);
  }

  function statusBadgeClass(status) {
    status = String(status || '').toLowerCase();
    if (['passed', 'accepted', 'resolved', 'closed', 'clerk-endorsed', 'approved', 'certified'].indexOf(status) >= 0) return 'badge--success';
    if (['pending', 'submitted', 'open', 'draft', 'queried', 'minor', 'low'].indexOf(status) >= 0) return 'badge--warning';
    if (['failed', 'rejected', 'returned', 'flagged', 'critical', 'major', 'high', 'rework-required'].indexOf(status) >= 0) return 'badge--danger';
    return 'badge--info';
  }

  function fieldCard(label, value, wide) {
    return '<article class="cq-detail-field' + (wide ? ' is-wide' : '') + '"><small>' + escapeHtml(label) + '</small><strong>' + escapeHtml(value || '—') + '</strong></article>';
  }

  function submitForm(form, submitter) {
    var mediaError = validateMedia(form);
    if (mediaError) {
      flash(mediaError, false);
      setStatus(form, mediaError, 'error');
      return Promise.reject(new Error(mediaError));
    }

    var actionValue = submitter && submitter.name === 'ipc_action' ? submitter.value : '';
    var button = submitter || qs('button[type="submit"]', form);
    var old = button ? button.innerHTML : '';
    if (button) {
      button.disabled = true;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving';
    }

    var body = new FormData(form);
    if (actionValue) body.set('ipc_action', actionValue);

    return request(form.action, { method: 'POST', body: body }).then(function (data) {
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

  function initCreateForm(form) {
    initMedia(form);
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
    var type = pageForm ? (pageForm.dataset.qualityType || '') : '';
    var detailUrl = pageForm ? (pageForm.dataset.detailUrl || '') : '';
    if (!detailUrl || type === 'ipc') return;

    var viewModal = qs('[data-cq-view-modal]');
    var editModal = qs('[data-cq-edit-modal]');
    var lightbox = qs('[data-cq-lightbox]');
    var editForm = editModal ? qs('[data-cq-edit-form]', editModal) : null;
    var viewBody = viewModal ? qs('[data-cq-view-body]', viewModal) : null;
    var viewTitle = viewModal ? qs('[data-cq-view-title]', viewModal) : null;
    var viewSub = viewModal ? qs('[data-cq-view-sub]', viewModal) : null;
    var viewEditBtn = viewModal ? qs('[data-cq-view-edit]', viewModal) : null;
    var editSub = editModal ? qs('[data-cq-edit-sub]', editModal) : null;
    var editSaveBtn = editModal ? qs('[data-cq-edit-save]', editModal) : null;
    var lastRecord = null;
    var lastId = 0;

    if (editForm) initMedia(editForm);

    function closeView() {
      setOverlayOpen(viewModal, false);
    }

    function closeEdit() {
      setOverlayOpen(editModal, false);
      if (editForm) resetForm(editForm);
    }

    function openLightbox(src, title) {
      if (!lightbox || !src) return;
      var img = qs('[data-cq-lightbox-img]', lightbox);
      if (img) {
        img.src = src;
        img.alt = title || 'Evidence photo';
      }
      lightbox.hidden = false;
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.classList.add('cq-modal-open');
    }

    function closeLightbox() {
      if (!lightbox) return;
      var img = qs('[data-cq-lightbox-img]', lightbox);
      if (img) img.src = '';
      lightbox.hidden = true;
      lightbox.setAttribute('aria-hidden', 'true');
      var anyOpen = qsa('.cq-overlay').some(function (node) { return !node.hidden; });
      document.body.classList.toggle('cq-modal-open', anyOpen);
    }

    function renderView(data) {
      var view = (data && data.view) || {};
      lastRecord = data && data.record ? data.record : null;
      lastId = view.id || (lastRecord && lastRecord.id) || 0;

      if (viewTitle) viewTitle.textContent = view.title || 'Record';
      if (viewSub) {
        viewSub.textContent = (view.project_name || 'Project')
          + (view.date ? ' · ' + String(view.date).slice(0, 10) : '')
          + (view.status ? ' · ' + String(view.status).replace(/-/g, ' ') : '');
      }

      var chips = ''
        + '<div class="cq-detail-chips">'
        + '<article><small>Status</small><strong><span class="badge ' + statusBadgeClass(view.status) + '">' + escapeHtml(String(view.status || '—').replace(/-/g, ' ')) + '</span></strong></article>'
        + '<article><small>Reference</small><strong>#' + escapeHtml(String(view.id || '—')) + '</strong></article>'
        + '<article><small>Date</small><strong>' + escapeHtml(view.date ? String(view.date).slice(0, 10) : '—') + '</strong></article>'
        + '<article><small>Project</small><strong>' + escapeHtml(view.project_name || '—') + '</strong></article>'
        + '</div>';

      var fields = (view.fields || []).map(function (f) {
        return fieldCard(f.label, f.value, !!f.wide);
      }).join('');

      var mediaHtml = '';
      if (view.media && (view.media.url || view.media.path)) {
        var m = view.media;
        if (m.is_image && m.url) {
          mediaHtml = '<section class="cq-detail-block"><h3>Photo / evidence</h3>'
            + '<button type="button" class="cq-detail-photo" data-cq-open-photo data-src="' + escapeHtml(m.url) + '" data-title="' + escapeHtml(m.title || view.title || 'Photo') + '">'
            + '<img src="' + escapeHtml(m.url) + '" alt="' + escapeHtml(m.title || 'Photo') + '">'
            + '<span>Click to enlarge</span></button></section>';
        } else if (m.url) {
          mediaHtml = '<section class="cq-detail-block"><h3>Supporting file</h3>'
            + '<div class="cq-file-cell"><span class="cq-file-icon"><i class="fa-solid fa-file-lines"></i></span>'
            + '<div><a href="' + escapeHtml(m.url) + '" target="_blank" rel="noopener"><strong>' + escapeHtml(m.title || 'Open file') + '</strong></a>'
            + (m.path ? '<small>' + escapeHtml(m.path) + '</small>' : '') + '</div></div></section>';
        }
      }

      if (viewBody) {
        viewBody.innerHTML = chips
          + (fields ? '<section class="cq-detail-block"><h3>Details</h3><div class="cq-detail-grid">' + fields + '</div></section>' : '')
          + mediaHtml;
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
        setStatus(editForm, 'Loading record...', 'info');
        request(url, { method: 'GET' }).then(function (data) {
          if (!data.success || !data.record) throw new Error(data.message || 'Record could not be loaded.');
          lastRecord = data.record;
          lastId = data.record.id;
          fillForm(editForm, data.record);
          if (editSub) editSub.textContent = 'Editing record #' + id + ' · save to update.';
          setStatus(editForm, '', '');
        }).catch(function (error) {
          flash(error.message || 'Record could not be loaded.', false);
          setStatus(editForm, error.message || 'Load failed.', 'error');
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

      var photoBtn = event.target.closest && event.target.closest('[data-cq-open-photo]');
      if (photoBtn) {
        event.preventDefault();
        openLightbox(photoBtn.getAttribute('data-src'), photoBtn.getAttribute('data-title'));
        return;
      }

      if (event.target.closest && event.target.closest('[data-cq-view-close]')) {
        closeView();
        return;
      }
      if (event.target.closest && event.target.closest('[data-cq-edit-close]')) {
        closeEdit();
        return;
      }
      if (event.target.closest && event.target.closest('[data-cq-lightbox-close]')) {
        closeLightbox();
        return;
      }
      if (event.target === viewModal) closeView();
      if (event.target === editModal) closeEdit();
      if (event.target === lightbox) closeLightbox();
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
      if (lightbox && !lightbox.hidden) {
        closeLightbox();
        return;
      }
      if (editModal && !editModal.hidden) closeEdit();
      else if (viewModal && !viewModal.hidden) closeView();
    });
  }

  function init() {
    qsa('[data-clerk-quality-form]').forEach(function (form) {
      initCreateForm(form);
      initModals(form);
    });
    // Photo pages without create form still need lightbox (unlikely) — bind global photo open via initModals only when form present
    // Also bind photo open when only table exists with form:
    if (!qs('[data-clerk-quality-form]') && qs('[data-cq-open-photo]')) {
      document.addEventListener('click', function (event) {
        var photoBtn = event.target.closest && event.target.closest('[data-cq-open-photo]');
        if (!photoBtn) return;
        var lightbox = qs('[data-cq-lightbox]');
        if (!lightbox) return;
        var img = qs('[data-cq-lightbox-img]', lightbox);
        if (img) {
          img.src = photoBtn.getAttribute('data-src') || '';
          img.alt = photoBtn.getAttribute('data-title') || 'Photo';
        }
        lightbox.hidden = false;
        document.body.classList.add('cq-modal-open');
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
