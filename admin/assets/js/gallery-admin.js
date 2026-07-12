(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function currentType() {
    var checked = qs('input[name="media_type"]:checked');
    return checked ? checked.value : 'image';
  }

  function syncMediaMode() {
    var type = currentType();
    qsa('[data-gallery-media-field]').forEach(function (field) {
      var name = field.getAttribute('data-gallery-media-field');
      var show = type === 'image'
        ? name === 'primary_image_ids'
        : name === 'video_media_ids' || name === 'thumbnail_media_id';
      field.hidden = !show;
    });
  }

  function clearMedia(button) {
    var wrap = button.closest('[data-cms-upload]');
    if (!wrap) return;
    var input = qs('[data-cms-upload-target]', wrap);
    var name = qs('[data-cms-asset-name]', wrap);
    var preview = qs('[data-cms-asset-preview]', wrap);
    if (input) input.value = '';
    if (name) name.textContent = 'No asset selected';
    if (preview) {
      var isVideo = (wrap.getAttribute('data-media-kind') || '') === 'video';
      preview.innerHTML = '<span><i class="fa-solid ' + (isVideo ? 'fa-video' : 'fa-image') + '" aria-hidden="true"></i></span>';
    }
  }

  function removeSelected(button) {
    var id = button.getAttribute('data-gallery-selected-remove');
    var field = button.closest('[data-gallery-media-field]');
    if (!field || !id) return;
    var input = qs('[data-cms-upload-target]', field);
    var listItem = button.closest('[data-selected-id]');
    if (input) {
      var ids = String(input.value || '').split(/[\s,]+/).filter(Boolean).filter(function (value) {
        return value !== id;
      });
      input.value = ids.join(',');
      input.dispatchEvent(new Event('input', { bubbles: true }));
      var name = qs('[data-cms-asset-name]', field);
      if (name) name.textContent = ids.length ? ids.length + ' assets selected' : 'No asset selected';
    }
    if (listItem) listItem.remove();
    var preview = qs('[data-cms-asset-preview]', field);
    var nextImage = qs('[data-selected-list] img', field);
    if (preview && nextImage) {
      preview.innerHTML = '<img src="' + nextImage.getAttribute('src') + '" alt="">';
    } else if (preview && input && !input.value) {
      var isVideo = (field.getAttribute('data-gallery-media-field') || '').indexOf('video') >= 0;
      preview.innerHTML = '<span><i class="fa-solid ' + (isVideo ? 'fa-video' : 'fa-image') + '" aria-hidden="true"></i></span>';
    }
  }

  document.addEventListener('change', function (event) {
    if (event.target && event.target.matches('input[name="media_type"]')) {
      syncMediaMode();
    }
  });

  document.addEventListener('click', function (event) {
    var clear = event.target.closest && event.target.closest('[data-gallery-media-clear]');
    if (clear) {
      event.preventDefault();
      clearMedia(clear);
      var field = clear.closest('[data-gallery-media-field]');
      var list = field && qs('[data-selected-list]', field);
      if (list) list.innerHTML = '';
      return;
    }

    var remove = event.target.closest && event.target.closest('[data-gallery-selected-remove]');
    if (remove) {
      event.preventDefault();
      removeSelected(remove);
    }
  });

  syncMediaMode();
})();
