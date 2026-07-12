(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function setStatus(form, message, state) {
    var node = qs('[data-submission-status]', form);
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

  function validate(form) {
    var missing = qsa('[required]', form).filter(function (field) {
      return !String(field.value || '').trim();
    });
    if (missing.length) {
      missing[0].focus();
      setStatus(form, 'Please complete all required fields.', 'error');
      return false;
    }
    return true;
  }

  function toast(message, type) {
    if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
      window.AHPTC.toast(message, type || 'success');
    }
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
  }

  function statusBadgeClass(status) {
    var s = String(status || '').toLowerCase();
    if (['approved', 'granted', 'partially-granted', 'answered', 'closed', 'paid'].indexOf(s) >= 0) return 'badge--success';
    if (['rejected', 'resubmit'].indexOf(s) >= 0) return 'badge--danger';
    if (['pending', 'under-review', 'open', 'submitted'].indexOf(s) >= 0) return 'badge--info';
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

  function initMediaControls(form) {
    qsa('[data-submission-media]', form).forEach(function (wrap) {
      var clearBtn = qs('[data-submission-media-clear]', wrap);
      if (clearBtn && clearBtn.dataset.ready !== '1') {
        clearBtn.dataset.ready = '1';
        clearBtn.addEventListener('click', function () {
          clearMedia(wrap);
        });
      }
    });
  }

  function initForm(form) {
    if (form.dataset.ready === '1') return;
    form.dataset.ready = '1';
    initMediaControls(form);
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!window.AHPTC || typeof window.AHPTC.request !== 'function') {
        setStatus(form, 'Application client is not ready. Refresh and try again.', 'error');
        return;
      }
      if (!validate(form)) return;

      var button = event.submitter || qs('[type="submit"]', form);
      var oldText = button ? button.innerHTML : '';
      if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Submitting';
      }
      setStatus(form, 'Submitting record...', 'loading');

      window.AHPTC.request(form.getAttribute('action'), {
        method: 'POST',
        body: payload(form)
      }).then(function (data) {
        setStatus(form, (data && data.message) || 'Submission saved.', 'success');
        toast((data && data.message) || 'Submission saved.', 'success');
        window.setTimeout(function () {
          window.location.reload();
        }, 700);
      }).catch(function (error) {
        var message = (error && error.message) || 'Submission could not be saved.';
        setStatus(form, message, 'error');
        toast(message, 'error');
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

  function initDetailModal() {
    var modal = qs('[data-submission-detail-modal]');
    if (!modal || !window.AHPTC) return;

    var body = qs('[data-submission-detail-body]', modal);
    var title = qs('[data-submission-detail-title]', modal);
    var subtitle = qs('[data-submission-detail-sub]', modal);

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

    function renderDetail(data) {
      var item = (data && data.item) || {};
      var attachments = (data && data.attachments) || [];
      if (title) title.textContent = item.reference || 'Submission';
      if (subtitle) {
        subtitle.textContent = (item.project_name || 'Project') +
          (item.submitted_at ? ' · Submitted ' + item.submitted_at : '');
      }

      var wideLabels = ['Question', 'Response', 'Description', 'Reason', 'Impact summary', 'Specification', 'Notes', 'Supporting evidence', 'Document reference'];
      var fields = (item.fields || []).map(function (field) {
        var label = String(field.label || '');
        var wide = wideLabels.some(function (entry) {
          return label.toLowerCase().indexOf(entry.toLowerCase()) >= 0;
        }) || String(field.value || '').length > 80;
        return fieldCard(label, field.value, wide);
      }).join('');

      var attachHtml = attachments.length
        ? '<ul class="contractor-submission-attach-list">' + attachments.map(function (row) {
            var isImage = String(row.media_type || '').indexOf('image/') === 0 ||
              ['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(String(row.extension || '').toLowerCase()) >= 0;
            var thumb = isImage && row.url
              ? '<img src="' + escapeHtml(row.url) + '" alt="">'
              : '<span><i class="fa-solid ' + (String(row.extension || '').toLowerCase() === 'pdf' ? 'fa-file-pdf' : 'fa-file-lines') + '" aria-hidden="true"></i></span>';
            var link = row.url
              ? '<a href="' + escapeHtml(row.url) + '" target="_blank" rel="noopener">' + escapeHtml(row.title || 'Support record') + '</a>'
              : '<strong>' + escapeHtml(row.title || 'Support record') + '</strong>';
            return '<li><div class="contractor-submission-attach-thumb">' + thumb + '</div><div>' + link +
              (row.path ? '<small>' + escapeHtml(row.path) + '</small>' : '') +
              '</div></li>';
          }).join('') + '</ul>'
        : '<p class="contractor-submission-detail-note">No support records attached.</p>';

      if (body) {
        body.innerHTML =
          '<div class="contractor-submission-detail-chips">' +
            '<article><small>Status</small><strong><span class="badge ' + statusBadgeClass(item.status) + '">' +
              escapeHtml(String(item.status || 'pending').replace(/-/g, ' ')) +
            '</span></strong></article>' +
            '<article><small>Impact</small><strong>' + escapeHtml(item.impact || '—') + '</strong></article>' +
            '<article><small>Reference</small><strong>' + escapeHtml(item.reference || '—') + '</strong></article>' +
            '<article><small>Attachments</small><strong>' + escapeHtml(String(item.attachment_count || attachments.length || 0)) + '</strong></article>' +
          '</div>' +
          '<section class="contractor-submission-detail-block">' +
            '<h3>' + escapeHtml(item.title || 'Details') + '</h3>' +
            '<p class="contractor-submission-detail-note">' + escapeHtml(item.description || 'No additional narrative provided.') + '</p>' +
          '</section>' +
          (fields
            ? '<section class="contractor-submission-detail-block"><h3>Record fields</h3><div class="contractor-submission-detail-grid">' + fields + '</div></section>'
            : '') +
          '<section class="contractor-submission-detail-block"><h3>Supporting records</h3>' + attachHtml + '</section>';
      }
    }

    function loadDetail(type, id) {
      if (!type || !id) return;
      openModal();
      if (body) {
        body.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Loading record...</strong></div>';
      }
      window.AHPTC.request(
        'api/contractor/submission-detail.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id),
        { method: 'GET' }
      ).then(function (data) {
        renderDetail(data);
      }).catch(function (error) {
        if (body) {
          body.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">Record could not load</strong><span class="empty-state__text">' +
            escapeHtml((error && error.message) || 'Try again.') + '</span></div>';
        }
        toast((error && error.message) || 'Record could not load.', 'error');
      });
    }

    document.addEventListener('click', function (event) {
      var openBtn = event.target.closest('[data-submission-open]');
      if (openBtn) {
        event.preventDefault();
        loadDetail(openBtn.getAttribute('data-submission-type'), openBtn.getAttribute('data-submission-open'));
        return;
      }
      if (event.target.closest('[data-submission-detail-close]') || event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
  }

  function init() {
    qsa('[data-contractor-submission]').forEach(initForm);
    initDetailModal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
