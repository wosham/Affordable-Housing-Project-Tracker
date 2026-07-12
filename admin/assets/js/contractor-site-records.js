(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function toast(message, type) {
    if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
      window.AHPTC.toast(message, type || 'success');
    }
  }

  function setStatus(form, message, state) {
    var node = qs('[data-csr-status]', form);
    if (!node) return;
    node.textContent = message || '';
    node.dataset.state = state || '';
  }

  function payload(form) {
    var body = {};
    new FormData(form).forEach(function (value, key) {
      body[key] = typeof value === 'string' ? value.trim() : value;
    });
    return body;
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
  }

  function statusBadgeClass(status) {
    var s = String(status || '').toLowerCase();
    if (['approved', 'accepted', 'resolved', 'closed', 'compliant', 'on-site', 'active', 'reviewed'].indexOf(s) >= 0) return 'badge--success';
    if (['rejected', 'queried', 'issue', 'expired', 'critical', 'high', 'suspended', 'terminated', 'flagged', 'returned', 'poor', 'maintenance'].indexOf(s) >= 0) return 'badge--danger';
    return 'badge--info';
  }

  function clearMedia(wrap) {
    if (!wrap) return;
    var path = qs('[data-cms-upload-target]', wrap);
    var mediaId = qs('[data-media-id-target], input[name="media_id"]', wrap);
    var name = qs('[data-cms-asset-name]', wrap);
    var preview = qs('[data-cms-asset-preview]', wrap);
    if (path) path.value = '';
    if (mediaId) mediaId.value = '';
    if (name) name.textContent = 'No file selected';
    if (preview) preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
  }

  function setMediaFromRecord(form, record) {
    var wraps = qsa('[data-csr-media]', form);
    if (!wraps.length) return;
    var path = record.filename || record.attachment_path || record.delivery_note_ref || '';
    var mediaId = record.media_id || record.evidence_media_id || '';
    wraps.forEach(function (wrap) {
      var pathInput = qs('[data-cms-upload-target]', wrap);
      var mediaIdInput = qs('[data-media-id-target], input[name="media_id"]', wrap);
      var name = qs('[data-cms-asset-name]', wrap);
      var preview = qs('[data-cms-asset-preview]', wrap);
      if (pathInput) pathInput.value = path || '';
      if (mediaIdInput) mediaIdInput.value = mediaId ? String(mediaId) : '';
      if (name) name.textContent = path ? (String(path).split('/').pop() || 'Selected file') : 'No file selected';
      if (preview) {
        var isImage = /\.(jpg|jpeg|png|webp|gif)$/i.test(path || '');
        if (isImage && path && String(path).indexOf('uploads/') === 0 && window.AHPTC && typeof window.AHPTC.baseUrl === 'function') {
          preview.innerHTML = '<img src="' + escapeHtml(window.AHPTC.baseUrl() + '/' + path) + '" alt="">';
        } else if (path) {
          preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
        } else {
          preview.innerHTML = '<span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>';
        }
      }
    });
  }

  function validate(form) {
    var missing = qsa('[required]', form).filter(function (field) {
      // Hidden media path fields still count as required when empty
      return !String(field.value || '').trim();
    });
    if (missing.length) {
      var focusable = missing[0];
      if (focusable && focusable.type === 'hidden') {
        var wrap = focusable.closest('[data-csr-media]');
        if (wrap) {
          var btn = qs('[data-media-picker-open]', wrap);
          if (btn) btn.focus();
        }
      } else if (focusable && typeof focusable.focus === 'function') {
        focusable.focus();
      }
      setStatus(form, 'Please complete all required fields.', 'error');
      return false;
    }
    var mediaWrap = qs('[data-csr-media][data-media-required="1"]', form);
    if (mediaWrap) {
      var path = qs('[data-cms-upload-target]', mediaWrap);
      if (!path || !String(path.value || '').trim()) {
        setStatus(form, 'Please choose or upload a supporting file.', 'error');
        return false;
      }
    }
    return true;
  }

  function initMediaControls(form) {
    qsa('[data-csr-media]', form).forEach(function (wrap) {
      var clearBtn = qs('[data-csr-media-clear]', wrap);
      if (clearBtn && clearBtn.dataset.ready !== '1') {
        clearBtn.dataset.ready = '1';
        clearBtn.addEventListener('click', function () {
          clearMedia(wrap);
        });
      }
    });
  }

  function resetCreateForm(form) {
    form.reset();
    var id = qs('[name="id"]', form);
    if (id) id.value = '';
    qsa('[data-csr-media]', form).forEach(clearMedia);
    setStatus(form, '', '');
  }

  function fillForm(form, record) {
    if (!form || !record) return;
    Object.keys(record).forEach(function (key) {
      if (key === 'media_id' || key === 'evidence_media_id') return;
      var field = qsa('[name]', form).find(function (input) { return input.name === key; });
      if (!field) return;
      var value = record[key] == null ? '' : String(record[key]);
      if (field.type === 'date') value = value.slice(0, 10);
      if (field.type === 'number' && value !== '') {
        // keep numeric string
      }
      if (field.maxLength > 0) value = value.slice(0, field.maxLength);
      field.value = value;
    });
    var id = qs('[name="id"]', form);
    if (id) id.value = record.id || '';
    setMediaFromRecord(form, record);
    setStatus(form, '', '');
  }

  function submitForm(form, onSuccess) {
    if (!window.AHPTC || typeof window.AHPTC.request !== 'function') {
      setStatus(form, 'Application client is not ready. Refresh and try again.', 'error');
      toast('Application client is not ready. Refresh and try again.', 'error');
      return Promise.reject(new Error('AHPTC missing'));
    }
    if (!validate(form)) {
      return Promise.reject(new Error('validation'));
    }
    setStatus(form, 'Saving record...', 'loading');
    return window.AHPTC.request(form.getAttribute('action'), {
      method: 'POST',
      body: payload(form)
    }).then(function (data) {
      setStatus(form, (data && data.message) || 'Record saved.', 'success');
      toast((data && data.message) || 'Record saved.', 'success');
      if (typeof onSuccess === 'function') onSuccess(data);
      return data;
    }).catch(function (error) {
      if (error && error.message === 'validation') throw error;
      var message = (error && error.message) || 'Record could not be saved.';
      setStatus(form, message, 'error');
      toast(message, 'error');
      throw error;
    });
  }

  function initCreateForm(form) {
    if (form.dataset.ready === '1') return;
    form.dataset.ready = '1';
    initMediaControls(form);
    var reset = qs('[data-csr-reset]', form);
    if (reset) {
      reset.addEventListener('click', function () {
        resetCreateForm(form);
      });
    }
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var button = event.submitter || qs('[type="submit"]', form);
      var oldText = button ? button.innerHTML : '';
      if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Saving';
      }
      submitForm(form, function () {
        window.setTimeout(function () { window.location.reload(); }, 700);
      }).catch(function () {
        // status already set
      }).finally(function () {
        if (button) {
          button.disabled = false;
          button.innerHTML = oldText;
        }
      });
    });
  }

  function fieldCard(label, value, wide) {
    if (!String(value || '').trim()) return '';
    return '<article class="' + (wide ? 'is-wide' : '') + '"><small>' + escapeHtml(label || '') +
      '</small><strong>' + escapeHtml(value || '—') + '</strong></article>';
  }

  function setOverlayOpen(overlay, open) {
    if (!overlay) return;
    overlay.hidden = !open;
    overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    var anyOpen = qsa('.csr-overlay').some(function (node) { return !node.hidden; });
    document.body.classList.toggle('contractor-modal-open', anyOpen);
  }

  function initModals() {
    var detailModal = qs('[data-csr-detail-modal]');
    var editModal = qs('[data-csr-edit-modal]');
    if (!window.AHPTC) return;

    var detailBody = detailModal ? qs('[data-csr-detail-body]', detailModal) : null;
    var detailTitle = detailModal ? qs('[data-csr-detail-title]', detailModal) : null;
    var detailSub = detailModal ? qs('[data-csr-detail-sub]', detailModal) : null;
    var detailEditBtn = detailModal ? qs('[data-csr-detail-edit]', detailModal) : null;
    var editForm = editModal ? qs('[data-csr-edit-form]', editModal) : null;
    var editSub = editModal ? qs('[data-csr-edit-sub]', editModal) : null;
    var editSaveBtn = editModal ? qs('[data-csr-edit-save]', editModal) : null;
    var lastEdit = null;
    var lastType = '';

    if (editForm) {
      initMediaControls(editForm);
    }

    function closeDetail() {
      setOverlayOpen(detailModal, false);
      if (detailEditBtn) detailEditBtn.hidden = true;
    }

    function closeEdit() {
      setOverlayOpen(editModal, false);
      if (editForm) {
        editForm.reset();
        var id = qs('[name="id"]', editForm);
        if (id) id.value = '';
        qsa('[data-csr-media]', editForm).forEach(clearMedia);
        setStatus(editForm, '', '');
      }
    }

    function openDetail() {
      closeEdit();
      setOverlayOpen(detailModal, true);
    }

    function openEdit() {
      closeDetail();
      setOverlayOpen(editModal, true);
    }

    function renderDetail(data) {
      var item = (data && data.item) || {};
      var fields = (data && data.fields) || [];
      var media = data && data.media ? data.media : null;
      lastEdit = data && data.edit ? data.edit : null;
      lastType = (data && data.type) || lastType;
      if (detailEditBtn) detailEditBtn.hidden = !lastEdit || !editForm;

      if (detailTitle) detailTitle.textContent = item.title || 'Record';
      if (detailSub) {
        detailSub.textContent = (item.project_name || 'Project') +
          (item.date ? ' · ' + item.date : '') +
          (item.secondary ? ' · ' + item.secondary : '');
      }

      var fieldHtml = fields.map(function (field) {
        return fieldCard(field.label, field.value, !!field.wide);
      }).join('');

      var mediaHtml = '';
      if (media && (media.url || media.path)) {
        var isImage = String(media.type || '').indexOf('image/') === 0 ||
          ['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(String(media.extension || '').toLowerCase()) >= 0;
        var thumb = isImage && media.url
          ? '<img src="' + escapeHtml(media.url) + '" alt="">'
          : '<span><i class="fa-solid ' + (String(media.extension || '').toLowerCase() === 'pdf' ? 'fa-file-pdf' : 'fa-file-lines') + '" aria-hidden="true"></i></span>';
        var link = media.url
          ? '<a href="' + escapeHtml(media.url) + '" target="_blank" rel="noopener">' + escapeHtml(media.title || 'Open file') + '</a>'
          : '<strong>' + escapeHtml(media.title || media.path || 'Support file') + '</strong>';
        mediaHtml = '<section class="csr-detail-block"><h3>Supporting file</h3><div class="csr-attach-row">' +
          '<div class="csr-attach-thumb">' + thumb + '</div><div>' + link +
          (media.path ? '<small>' + escapeHtml(media.path) + '</small>' : '') +
          '</div></div></section>';
      }

      if (detailBody) {
        detailBody.innerHTML =
          '<div class="csr-detail-chips">' +
            '<article><small>Status</small><strong><span class="badge ' + statusBadgeClass(item.status) + '">' +
              escapeHtml(String(item.status || '—').replace(/-/g, ' ')) +
            '</span></strong></article>' +
            '<article><small>Reference</small><strong>#' + escapeHtml(String(item.id || '—')) + '</strong></article>' +
            '<article><small>Date</small><strong>' + escapeHtml(item.date || '—') + '</strong></article>' +
            '<article><small>Project</small><strong>' + escapeHtml(item.project_name || '—') + '</strong></article>' +
          '</div>' +
          '<section class="csr-detail-block">' +
            '<h3>' + escapeHtml(item.title || 'Details') + '</h3>' +
            '<p class="csr-detail-note">' + escapeHtml(item.summary || 'No additional narrative provided.') + '</p>' +
          '</section>' +
          (fieldHtml
            ? '<section class="csr-detail-block"><h3>Record fields</h3><div class="csr-detail-grid">' + fieldHtml + '</div></section>'
            : '') +
          mediaHtml;
      }
    }

    function loadRecord(type, id, mode) {
      if (!type || !id) return;
      lastType = type;
      if (mode === 'edit') {
        if (!editForm) {
          toast('Edit form is not available on this page.', 'error');
          return;
        }
        openEdit();
        if (editSub) editSub.textContent = 'Loading record #' + id + '...';
        setStatus(editForm, 'Loading record...', 'loading');
      } else {
        openDetail();
        if (detailBody) {
          detailBody.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Loading record...</strong></div>';
        }
      }

      window.AHPTC.request(
        'api/contractor/site-record-detail.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id),
        { method: 'GET' }
      ).then(function (data) {
        lastEdit = data && data.edit ? data.edit : null;
        if (mode === 'edit') {
          if (!lastEdit) {
            setStatus(editForm, 'Record could not be opened for editing.', 'error');
            toast('Record could not be opened for editing.', 'error');
            return;
          }
          fillForm(editForm, lastEdit);
          if (editSub) {
            editSub.textContent = (data.item && data.item.project_name ? data.item.project_name + ' · ' : '') +
              'Editing record #' + (lastEdit.id || id);
          }
          setStatus(editForm, 'Update the fields below, then save changes.', 'loading');
          // Focus first visible field
          var first = qs('input:not([type="hidden"]), select, textarea', editForm);
          if (first && typeof first.focus === 'function') {
            window.setTimeout(function () { first.focus(); }, 50);
          }
          return;
        }
        renderDetail(data);
      }).catch(function (error) {
        var message = (error && error.message) || 'Record could not load.';
        if (mode === 'edit' && editForm) {
          setStatus(editForm, message, 'error');
        } else if (detailBody) {
          detailBody.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Record could not load</strong><span class="empty-state__text">' +
            escapeHtml(message) + '</span></div>';
        }
        toast(message, 'error');
      });
    }

    if (editSaveBtn && editForm) {
      editSaveBtn.addEventListener('click', function () {
        var button = editSaveBtn;
        var oldText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Saving';
        submitForm(editForm, function () {
          window.setTimeout(function () { window.location.reload(); }, 700);
        }).catch(function () {
          // handled
        }).finally(function () {
          button.disabled = false;
          button.innerHTML = oldText;
        });
      });
    }

    document.addEventListener('click', function (event) {
      var openBtn = event.target.closest('[data-csr-open]');
      if (openBtn) {
        event.preventDefault();
        event.stopPropagation();
        loadRecord(openBtn.getAttribute('data-csr-type'), openBtn.getAttribute('data-csr-open'), 'view');
        return;
      }

      var editBtnRow = event.target.closest('[data-csr-edit]');
      if (editBtnRow) {
        event.preventDefault();
        event.stopPropagation();
        loadRecord(editBtnRow.getAttribute('data-csr-type'), editBtnRow.getAttribute('data-csr-edit'), 'edit');
        return;
      }

      if (event.target.closest('[data-csr-detail-edit]')) {
        event.preventDefault();
        if (lastEdit && editForm) {
          openEdit();
          fillForm(editForm, lastEdit);
          if (editSub) {
            editSub.textContent = 'Editing record #' + (lastEdit.id || '');
          }
          setStatus(editForm, 'Update the fields below, then save changes.', 'loading');
        } else if (lastType && lastEdit && lastEdit.id) {
          loadRecord(lastType, lastEdit.id, 'edit');
        } else {
          toast('Record could not be opened for editing.', 'error');
        }
        return;
      }

      if (event.target.closest('[data-csr-detail-close]')) {
        closeDetail();
        return;
      }
      if (event.target.closest('[data-csr-edit-close]')) {
        closeEdit();
        return;
      }
      if (detailModal && event.target === detailModal) {
        closeDetail();
        return;
      }
      if (editModal && event.target === editModal) {
        closeEdit();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      if (editModal && !editModal.hidden) {
        closeEdit();
        return;
      }
      if (detailModal && !detailModal.hidden) {
        closeDetail();
      }
    });
  }

  function init() {
    qsa('[data-csr-create-form]').forEach(initCreateForm);
    // Back-compat: any remaining data-csr-form without create marker
    qsa('[data-csr-form]:not([data-csr-create-form]):not([data-csr-edit-form])').forEach(initCreateForm);
    initModals();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
