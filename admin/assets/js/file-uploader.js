(function () {
  'use strict';

  var defaults = {
    maxSize: 10 * 1024 * 1024,
    imageTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    documentTypes: [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]
  };

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function setStatus(root, text, state) {
    var status = qs('[data-upload-status]', root);
    if (!status) return;
    status.textContent = text || '';
    status.dataset.state = state || '';
  }

  function validate(file, mode, maxSize) {
    if (!file) return 'Choose a file first.';
    if (file.size > maxSize) return 'File is too large.';

    var allowed = mode === 'document' ? defaults.documentTypes : defaults.imageTypes.concat(defaults.documentTypes);
    if (mode === 'image') allowed = defaults.imageTypes;
    if (allowed.indexOf(file.type) === -1) return 'This file type is not allowed.';

    return '';
  }

  function preview(root, file) {
    var image = qs('[data-upload-preview]', root);
    var name = qs('[data-upload-name]', root);
    if (name) name.textContent = file ? file.name : '';
    if (!image || !file || !file.type || file.type.indexOf('image/') !== 0) return;

    var reader = new FileReader();
    reader.onload = function () {
      image.src = reader.result;
      image.hidden = false;
    };
    reader.readAsDataURL(file);
  }

  function initUploader(root) {
    var input = qs('input[type="file"]', root);
    if (!input) return;

    var mode = root.getAttribute('data-upload-mode') || (input.getAttribute('accept') === 'image/*' ? 'image' : 'any');
    var maxSize = parseInt(root.getAttribute('data-upload-max') || '', 10) || defaults.maxSize;

    function handleFile(file) {
      var error = validate(file, mode, maxSize);
      if (error) {
        input.value = '';
        setStatus(root, error, 'error');
        return;
      }

      preview(root, file);
      setStatus(root, 'Ready to upload.', 'ready');
    }

    input.addEventListener('change', function () {
      handleFile(input.files && input.files[0]);
    });

    root.addEventListener('dragover', function (event) {
      event.preventDefault();
      root.classList.add('is-dragging');
    });

    root.addEventListener('dragleave', function () {
      root.classList.remove('is-dragging');
    });

    root.addEventListener('drop', function (event) {
      event.preventDefault();
      root.classList.remove('is-dragging');
      var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
      if (!file) return;
      try {
        var transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
      } catch (error) {}
      handleFile(file);
    });
  }

  function init() {
    qsa('[data-file-uploader]').forEach(initUploader);
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, {
    initFileUploaders: init
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
