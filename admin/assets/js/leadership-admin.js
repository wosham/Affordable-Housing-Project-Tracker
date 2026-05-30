(function () {
  'use strict';

  function slug(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function bindSlugs() {
    document.querySelectorAll('[data-slug-source]').forEach(function (input) {
      var sourceName = input.getAttribute('data-slug-source');
      var form = input.closest('form');
      var source = form ? form.querySelector('[name="' + sourceName + '"]') : null;
      if (!source) return;

      source.addEventListener('input', function () {
        if (input.value.trim() !== '') return;
        input.placeholder = slug(source.value) || 'auto-generated if empty';
      });
    });
  }

  function bindAssetPreview() {
    document.querySelectorAll('[data-cms-upload]').forEach(function (wrap) {
      var target = wrap.querySelector('[data-cms-upload-target]');
      if (!target) return;

      function update() {
        var path = target.value || '';
        var preview = wrap.querySelector('[data-cms-asset-preview]');
        var name = wrap.querySelector('[data-cms-asset-name]');
        wrap.classList.toggle('has-asset', path.trim() !== '');
        if (name) name.textContent = path ? path.split('/').pop() : 'No image selected';
        if (preview) {
          preview.innerHTML = path
            ? '<img src="' + window.AHPTC.baseUrl() + '/' + path.replace(/^\/+/, '') + '" alt="">'
            : '<span><i class="fa-solid fa-image" aria-hidden="true"></i></span>';
        }
      }

      target.addEventListener('input', update);
      update();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindSlugs();
    bindAssetPreview();
  });
})();
