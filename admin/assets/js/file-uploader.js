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
    if (file.size > maxSize) {
      var mb = Math.round(maxSize / (1024 * 1024));
      return 'File is too large (max ' + mb + ' MB).';
    }

    var allowed = mode === 'document' ? defaults.documentTypes : defaults.imageTypes.concat(defaults.documentTypes);
    if (mode === 'image') allowed = defaults.imageTypes;

    // Some browsers leave type empty for known extensions — allow by extension fallback.
    if (file.type && allowed.indexOf(file.type) === -1) {
      var name = (file.name || '').toLowerCase();
      var okExt = /\.(jpe?g|png|webp|gif|pdf|docx?|xlsx?)$/i.test(name);
      if (!okExt) return 'This file type is not allowed.';
    }

    return '';
  }

  function preview(root, file) {
    var image = qs('[data-upload-preview]', root);
    var name = qs('[data-upload-name]', root);
    if (name) name.textContent = file ? file.name : 'No file selected';
    if (!image) return;

    if (!file || !file.type || file.type.indexOf('image/') !== 0) {
      image.removeAttribute('src');
      image.hidden = true;
      return;
    }

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
    var picker = qs('[data-upload-picker]', root);

    function handleFile(file) {
      var error = validate(file, mode, maxSize);
      if (error) {
        input.value = '';
        preview(root, null);
        setStatus(root, error, 'error');
        return;
      }

      preview(root, file);
      setStatus(root, 'Ready to upload.', 'ready');
    }

    if (picker) {
      picker.addEventListener('click', function () {
        input.click();
      });
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
