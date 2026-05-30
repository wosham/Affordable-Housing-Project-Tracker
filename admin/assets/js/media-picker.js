(function () {
  'use strict';

  var picker = null;
  var targetInput = null;
  var contextFolder = 'cms';
  var selected = null;
  var items = [];
  var folders = {};
  var activeFolder = 'cms';

  var folderIcons = {
    heroes: 'fa-panorama',
    backgrounds: 'fa-panorama',
    banners: 'fa-panorama',
    logos: 'fa-copyright',
    gallery: 'fa-images',
    projects: 'fa-building',
    news: 'fa-newspaper',
    profiles: 'fa-user-tie',
    leadership: 'fa-user-tie',
    partners: 'fa-handshake',
    'site-photos': 'fa-camera',
    publications: 'fa-file-lines',
    tenders: 'fa-file-signature',
    cms: 'fa-layer-group'
  };

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function build() {
    if (picker) return picker;
    picker = document.createElement('div');
    picker.className = 'sa-media-modal sa-media-picker';
    picker.hidden = true;
    picker.innerHTML =
      '<div class="sa-media-modal__backdrop" data-picker-close></div>' +
      '<section class="sa-media-modal__panel sa-media-picker__panel" role="dialog" aria-modal="true" aria-label="Choose media">' +
      '<header><div><span class="sa-panel-label"><i class="fa-solid fa-photo-film"></i> Media Picker</span><h3>Choose or upload image</h3><p>Select an existing asset or upload into the correct content collection.</p></div><button class="btn btn--icon btn--outline" type="button" data-picker-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button></header>' +
      '<div class="sa-media-picker__tabs"><button class="is-active" type="button" data-picker-tab="library">Choose from Library</button><button type="button" data-picker-tab="upload">Upload New</button></div>' +
      '<div class="sa-media-picker__body" data-picker-panel="library">' +
      '<div class="sa-media-picker__library">' +
      '<aside class="sa-media-picker__folders" data-picker-folders></aside>' +
      '<div class="sa-media-picker__workspace"><div class="sa-media-toolbar"><label class="sa-media-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-picker-search placeholder="Search images..."></label><button class="btn btn--outline" type="button" data-picker-refresh><i class="fa-solid fa-rotate"></i> Refresh</button></div>' +
      '<div class="sa-media-status" data-picker-status>Loading images...</div><div class="sa-media-grid" data-picker-grid></div></div>' +
      '</div>' +
      '</div>' +
      '<form class="sa-media-picker__body is-hidden" data-picker-panel="upload" data-picker-upload-form>' +
      '<input type="hidden" name="csrf_form" value="cms_editor"><input type="hidden" name="folder" data-picker-folder value="cms">' +
      '<div class="sa-media-drop"><input type="file" name="file" accept="image/*" required><i class="fa-solid fa-cloud-arrow-up"></i><strong>Upload into this collection</strong><span data-picker-folder-label>CMS Assets</span></div>' +
      '<div class="form-grid form-grid--2"><label class="form-field"><span class="form-label">Title</span><input class="form-input" name="title"></label><label class="form-field"><span class="form-label">Alt text</span><input class="form-input" name="alt_text"></label></div>' +
      '<footer><span data-picker-upload-state>Ready</span><button class="btn btn--primary" type="submit"><i class="fa-solid fa-upload"></i> Upload & Use</button></footer>' +
      '</form>' +
      '<footer><span data-picker-selected>No image selected</span><button class="btn btn--outline" type="button" data-picker-close>Cancel</button><button class="btn btn--primary" type="button" data-picker-use disabled><i class="fa-solid fa-check"></i> Use Selected</button></footer>' +
      '</section>';
    document.body.appendChild(picker);
    bindPicker();
    return picker;
  }

  function status(text) {
    var node = qs('[data-picker-status]', picker);
    if (node) node.textContent = text;
  }

  function renderFolders() {
    var rail = qs('[data-picker-folders]', picker);
    if (!rail) return;
    var folderList = Object.keys(folders).length ? folders : {
      heroes: 'Heroes',
      backgrounds: 'Backgrounds',
      banners: 'Banners',
      logos: 'Logos',
      projects: 'Projects',
      gallery: 'Gallery',
      news: 'News',
      cms: 'CMS Assets'
    };

    rail.innerHTML = '<strong>Collections</strong>' + Object.keys(folderList).map(function (key) {
      return '<button class="' + (activeFolder === key ? 'is-active' : '') + '" type="button" data-picker-folder-select="' + esc(key) + '">' +
        '<i class="fa-solid ' + esc(folderIcons[key] || 'fa-folder') + '"></i><span>' + esc(folderList[key]) + '</span></button>';
    }).join('');
  }

  function render() {
    var grid = qs('[data-picker-grid]', picker);
    if (!grid) return;
    if (!items.length) {
      grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><span class="empty-state__icon"><i class="fa-solid fa-image"></i></span><strong class="empty-state__title">No images found</strong><span class="empty-state__text">Upload a new image or sync the media library.</span></div>';
      return;
    }
    grid.innerHTML = items.map(function (item) {
      return '<article class="sa-media-card' + (selected && selected.id === item.id ? ' is-active' : '') + '" data-picker-media-id="' + item.id + '">' +
        '<div class="sa-media-thumb"><span class="sa-media-type">' + esc(item.extension || 'image') + '</span><img src="' + esc(item.url) + '" alt="' + esc(item.alt_text || item.title) + '" loading="lazy"></div>' +
        '<strong title="' + esc(item.title) + '">' + esc(item.title) + '</strong><small>' + esc(item.folder_label) + '</small></article>';
    }).join('');
  }

  function load() {
    var search = qs('[data-picker-search]', picker);
    var params = new URLSearchParams({ type: 'image', folder: activeFolder, q: search ? search.value : '', per_page: 48 });
    status('Loading images...');
    return window.AHPTC.request('api/media/list.php?' + params.toString()).then(function (data) {
      items = data.media || [];
      folders = data.folders || folders;
      renderFolders();
      if (!items.length && activeFolder) {
        return window.AHPTC.request('api/media/list.php?type=image&per_page=48').then(function (fallback) {
          items = fallback.media || [];
          folders = fallback.folders || folders;
          renderFolders();
          status('This collection is empty. Showing all images for quick selection.');
          render();
        });
      }
      status(items.length + ' images available');
      render();
    }).catch(function (error) {
      status(error.message || 'Could not load images');
    });
  }

  function open(button) {
    var wrap = button.closest('[data-cms-upload]');
    targetInput = wrap ? qs('[data-cms-upload-target]', wrap) : null;
    contextFolder = button.getAttribute('data-media-picker-folder') || (wrap && wrap.getAttribute('data-upload-folder')) || 'cms';
    activeFolder = contextFolder;
    selected = null;
    build().hidden = false;
    var folderInput = qs('[data-picker-folder]', picker);
    if (folderInput) folderInput.value = contextFolder;
    var label = qs('[data-picker-folder-label]', picker);
    if (label) label.textContent = 'Destination: ' + contextFolder.replace(/-/g, ' ');
    var use = qs('[data-picker-use]', picker);
    if (use) use.disabled = true;
    var selectedLabel = qs('[data-picker-selected]', picker);
    if (selectedLabel) selectedLabel.textContent = 'No image selected';
    load();
  }

  function close() {
    if (picker) picker.hidden = true;
  }

  function useSelected() {
    if (!selected || !targetInput) return;
    targetInput.value = selected.path;
    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
    close();
  }

  function bindPicker() {
    picker.addEventListener('click', function (event) {
      if (event.target.closest('[data-picker-close]')) close();

      var tab = event.target.closest('[data-picker-tab]');
      if (tab) {
        picker.querySelectorAll('[data-picker-tab]').forEach(function (button) {
          button.classList.toggle('is-active', button === tab);
        });
        picker.querySelectorAll('[data-picker-panel]').forEach(function (panel) {
          panel.classList.toggle('is-hidden', panel.getAttribute('data-picker-panel') !== tab.getAttribute('data-picker-tab'));
        });
      }

      if (event.target.closest('[data-picker-refresh]')) load();

      var folderButton = event.target.closest('[data-picker-folder-select]');
      if (folderButton) {
        activeFolder = folderButton.getAttribute('data-picker-folder-select') || contextFolder;
        var folderInput = qs('[data-picker-folder]', picker);
        if (folderInput) folderInput.value = activeFolder;
        selected = null;
        load();
      }

      var card = event.target.closest('[data-picker-media-id]');
      if (card) {
        selected = items.find(function (item) { return item.id === parseInt(card.getAttribute('data-picker-media-id'), 10); });
        var use = qs('[data-picker-use]', picker);
        if (use) use.disabled = !selected;
        var label = qs('[data-picker-selected]', picker);
        if (label) label.textContent = selected ? selected.title : 'No image selected';
        render();
      }

      if (event.target.closest('[data-picker-use]')) useSelected();
    });

    var search = qs('[data-picker-search]', picker);
    if (search) {
      var timer = null;
      search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(load, 250);
      });
    }

    var form = qs('[data-picker-upload-form]', picker);
    if (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var state = qs('[data-picker-upload-state]', picker);
        if (state) state.textContent = 'Uploading...';
        window.AHPTC.request('api/media/upload.php', { method: 'POST', body: new FormData(form) }).then(function (data) {
          selected = data.media;
          useSelected();
        }).catch(function (error) {
          if (state) state.textContent = error.message || 'Upload failed';
        });
      });
    }
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('[data-media-picker-open]');
    if (!button) return;
    event.preventDefault();
    open(button);
  });
})();
