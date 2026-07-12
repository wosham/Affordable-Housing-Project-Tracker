(function () {
  'use strict';

  var state = {
    page: 1,
    perPage: 48,
    folder: '',
    type: '',
    q: '',
    view: 'grid',
    items: [],
    selected: null
  };

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function status(text) {
    var node = qs('[data-media-status]');
    if (node) node.textContent = text;
  }

  function iconFor(item) {
    if (item.type_group === 'pdf') return 'fa-file-pdf';
    if (item.type_group === 'video') return 'fa-file-video';
    if (item.type_group === 'document') return 'fa-file-lines';
    return 'fa-image';
  }

  function thumb(item) {
    var badge = '<span class="sa-media-type">' + esc(item.extension || item.type_group) + '</span>';
    var used = item.usage_count > 0 ? '<span class="sa-media-used">Used</span>' : '';
    if (item.type_group === 'image') {
      return '<div class="sa-media-thumb">' + badge + '<img src="' + esc(item.url) + '" alt="' + esc(item.alt_text || item.title) + '" loading="lazy">' + used + '</div>';
    }
    if (item.type_group === 'video') {
      return '<div class="sa-media-thumb">' + badge + '<video src="' + esc(item.url) + '" muted></video>' + used + '</div>';
    }
    return '<div class="sa-media-thumb">' + badge + '<i class="fa-solid ' + iconFor(item) + '"></i>' + used + '</div>';
  }

  function renderGrid() {
    var grid = qs('[data-media-grid]');
    if (!grid) return;
    grid.classList.toggle('is-list', state.view === 'list');

    if (!state.items.length) {
      grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><span class="empty-state__icon"><i class="fa-solid fa-photo-film"></i></span><strong class="empty-state__title">No media found</strong><span class="empty-state__text">Upload files or sync existing uploads to populate this collection.</span></div>';
      return;
    }

    grid.innerHTML = state.items.map(function (item) {
      return '<article class="sa-media-card' + (state.selected && state.selected.id === item.id ? ' is-active' : '') + '" data-media-id="' + item.id + '">' +
        thumb(item) +
        '<strong title="' + esc(item.title) + '">' + esc(item.title) + '</strong>' +
        '<small>' + esc(item.folder_label) + ' / ' + esc(item.size_label) + '</small>' +
        '</article>';
    }).join('');
  }

  function renderPagination(pagination) {
    var node = qs('[data-media-pagination]');
    if (!node) return;
    var page = pagination.page || 1;
    var total = pagination.total_pages || 1;
    node.innerHTML = '<span class="pagination__meta">Page ' + page + ' of ' + total + ' / ' + (pagination.total || 0) + ' files</span>' +
      '<div class="pagination__links">' +
      '<button class="pagination__link" type="button" data-page="' + Math.max(1, page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-left"></i></button>' +
      '<button class="pagination__link" type="button" data-page="' + Math.min(total, page + 1) + '"' + (page >= total ? ' disabled' : '') + '><i class="fa-solid fa-chevron-right"></i></button>' +
      '</div>';
  }

  function renderInspector(item) {
    var node = qs('[data-media-inspector]');
    if (!node) return;
    if (!item) {
      node.innerHTML = '<div class="sa-media-inspector__empty"><i class="fa-solid fa-arrow-pointer"></i><strong>Select an asset</strong><span>Preview details, copy paths, edit metadata and review usage.</span></div>';
      return;
    }

    var preview = item.type_group === 'image'
      ? '<img src="' + esc(item.url) + '" alt="' + esc(item.alt_text || item.title) + '">'
      : item.type_group === 'video'
        ? '<video src="' + esc(item.url) + '" controls></video>'
        : '<div><i class="fa-solid ' + iconFor(item) + '"></i></div>';

    node.innerHTML = '<div class="sa-media-detail">' +
      '<div class="sa-media-detail__preview">' + preview + '</div>' +
      '<div><h3>' + esc(item.title) + '</h3><p class="text-muted">' + esc(item.path) + '</p></div>' +
      '<div class="sa-media-actions">' +
      '<button class="btn btn--outline btn--sm" type="button" data-copy-path="' + esc(item.path) + '"><i class="fa-solid fa-copy"></i> Copy Path</button>' +
      '<a class="btn btn--outline btn--sm" href="' + esc(item.url) + '" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open</a>' +
      '<button class="btn btn--danger btn--sm" type="button" data-delete-media="' + item.id + '"><i class="fa-solid fa-box-archive"></i> Archive</button>' +
      '</div>' +
      '<dl>' +
      '<div><dt>Type</dt><dd>' + esc(item.type) + '</dd></div>' +
      '<div><dt>Folder</dt><dd>' + esc(item.folder_label) + '</dd></div>' +
      '<div><dt>Size</dt><dd>' + esc(item.size_label) + '</dd></div>' +
      '<div><dt>Dimensions</dt><dd>' + esc(item.width && item.height ? item.width + ' x ' + item.height : 'n/a') + '</dd></div>' +
      '<div><dt>Uploaded</dt><dd>' + esc(item.created_label) + '</dd></div>' +
      '<div><dt>Usage</dt><dd>' + esc(item.usage_count || 0) + ' references</dd></div>' +
      '</dl>' +
      '<form data-media-update-form>' +
      '<input type="hidden" name="csrf_form" value="media_library"><input type="hidden" name="id" value="' + item.id + '">' +
      '<label class="form-field"><span class="form-label">Title</span><input class="form-input" name="title" value="' + esc(item.title) + '"></label>' +
      '<label class="form-field"><span class="form-label">Alt text</span><input class="form-input" name="alt_text" value="' + esc(item.alt_text) + '"></label>' +
      '<label class="form-field"><span class="form-label">Caption</span><textarea class="form-textarea" name="caption" rows="3">' + esc(item.caption) + '</textarea></label>' +
      '<input type="hidden" name="folder" value="' + esc(item.folder) + '">' +
      '<button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Metadata</button>' +
      '</form>' +
      '</div>';
  }

  function load() {
    var params = new URLSearchParams({
      page: state.page,
      per_page: state.perPage,
      folder: state.folder,
      type: state.type,
      q: state.q
    });
    status('Loading media...');
    return window.AHPTC.request('api/media/list.php?' + params.toString()).then(function (data) {
      state.items = data.media || [];
      status((data.pagination.total || 0) + ' assets found');
      renderGrid();
      renderPagination(data.pagination || {});
      if (state.selected) {
        var fresh = state.items.find(function (item) { return item.id === state.selected.id; });
        state.selected = fresh || null;
        renderInspector(state.selected);
      }
    }).catch(function (error) {
      status(error.message || 'Could not load media');
    });
  }

  function select(id) {
    var item = state.items.find(function (media) { return media.id === id; });
    state.selected = item || null;
    renderGrid();
    renderInspector(state.selected);
  }

  function bindLibrary() {
    if (!qs('[data-media-library]')) return;

    qsa('[data-media-folder]').forEach(function (button) {
      button.addEventListener('click', function () {
        qsa('[data-media-folder]').forEach(function (btn) { btn.classList.remove('is-active'); });
        button.classList.add('is-active');
        state.folder = button.getAttribute('data-media-folder') || '';
        state.page = 1;
        load();
      });
    });

    qsa('[data-media-type]').forEach(function (button) {
      button.addEventListener('click', function () {
        qsa('[data-media-type]').forEach(function (btn) { btn.classList.remove('is-active'); });
        button.classList.add('is-active');
        state.type = button.getAttribute('data-media-type') || '';
        state.page = 1;
        load();
      });
    });

    qsa('[data-media-view]').forEach(function (button) {
      button.addEventListener('click', function () {
        qsa('[data-media-view]').forEach(function (btn) { btn.classList.remove('is-active'); });
        button.classList.add('is-active');
        state.view = button.getAttribute('data-media-view') || 'grid';
        renderGrid();
      });
    });

    var search = qs('[data-media-search]');
    if (search) {
      var timer = null;
      search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          state.q = search.value || '';
          state.page = 1;
          load();
        }, 250);
      });
    }

    document.addEventListener('click', function (event) {
      var card = event.target.closest && event.target.closest('[data-media-id]');
      if (card) select(parseInt(card.getAttribute('data-media-id'), 10));

      var page = event.target.closest && event.target.closest('[data-page]');
      if (page && !page.disabled) {
        state.page = parseInt(page.getAttribute('data-page'), 10) || 1;
        load();
      }

      var copy = event.target.closest && event.target.closest('[data-copy-path]');
      if (copy) {
        navigator.clipboard.writeText(copy.getAttribute('data-copy-path') || '');
        status('Path copied');
      }

      var del = event.target.closest && event.target.closest('[data-delete-media]');
      if (del && window.confirm('Archive this media item? It will be hidden from future pickers but the file will remain on disk.')) {
        var body = new FormData();
        body.append('id', del.getAttribute('data-delete-media'));
        body.append('csrf_form', 'media_library');
        window.AHPTC.request('api/media/delete.php', { method: 'POST', body: body }).then(function () {
          state.selected = null;
          renderInspector(null);
          load();
        }).catch(function (error) { status(error.message || 'Delete failed'); });
      }
    });

    document.addEventListener('submit', function (event) {
      var form = event.target.closest && event.target.closest('[data-media-update-form]');
      if (!form) return;
      event.preventDefault();
      window.AHPTC.request('api/media/update.php', { method: 'POST', body: new FormData(form) }).then(function () {
        status('Metadata saved');
        load();
      }).catch(function (error) { status(error.message || 'Save failed'); });
    });

    load();
  }

  function bindUploadModal() {
    var modal = qs('[data-media-upload-modal]');
    if (!modal) return;
    var open = function () { modal.hidden = false; };
    var close = function () { modal.hidden = true; };
    qsa('[data-media-upload-open]').forEach(function (button) { button.addEventListener('click', open); });
    qsa('[data-media-modal-close]', modal).forEach(function (button) { button.addEventListener('click', close); });

    var form = qs('[data-media-upload-form]', modal);
    var uploadState = qs('[data-media-upload-state]', modal);
    if (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (uploadState) uploadState.textContent = 'Uploading...';
        window.AHPTC.request('api/media/upload.php', { method: 'POST', body: new FormData(form) }).then(function () {
          if (uploadState) uploadState.textContent = 'Uploaded';
          form.reset();
          close();
          load();
        }).catch(function (error) {
          if (uploadState) uploadState.textContent = error.message || 'Upload failed';
        });
      });
    }
  }

  function bindSync() {
    qsa('[data-media-sync]').forEach(function (button) {
      button.addEventListener('click', function () {
        var body = new FormData();
        body.append('csrf_form', 'media_library');
        button.disabled = true;
        status('Scanning uploads folders...');
        window.AHPTC.request('api/media/sync.php', { method: 'POST', body: body }).then(function (data) {
          var result = data.result || {};
          status('Synced ' + (result.inserted || 0) + ' new files, skipped ' + (result.skipped || 0));
          load();
        }).catch(function (error) {
          status(error.message || 'Sync failed');
        }).finally(function () {
          button.disabled = false;
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindLibrary();
    bindUploadModal();
    bindSync();
  });
})();
