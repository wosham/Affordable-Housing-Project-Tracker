(function () {
  'use strict';

  function loadQuill() {
    if (window.Quill) return Promise.resolve(true);

    return new Promise(function (resolve) {
      var baseMeta = document.querySelector('meta[name="app-base-url"]');
      var base = baseMeta ? String(baseMeta.getAttribute('content') || '').replace(/\/$/, '') : '';

      var stylesheet = document.createElement('link');
      stylesheet.rel = 'stylesheet';
      stylesheet.href = base + '/admin/assets/vendor/quill/quill.snow.css';
      document.head.appendChild(stylesheet);

      var script = document.createElement('script');
      script.src = base + '/admin/assets/vendor/quill/quill.js';
      script.onload = function () { resolve(!!window.Quill); };
      script.onerror = function () { resolve(false); };
      document.head.appendChild(script);
    });
  }

  function initEditors() {
    loadQuill().then(function (available) {
      document.querySelectorAll('[data-faq-quill]').forEach(function (editor) {
        var textarea = editor.parentElement.querySelector('textarea[name="answer"]');
        if (!textarea) return;

        if (!available) {
          editor.hidden = true;
          textarea.classList.remove('is-enhanced');
          textarea.hidden = false;
          return;
        }

        textarea.classList.add('is-enhanced');
        var quill = new window.Quill(editor, {
          theme: 'snow',
          modules: {
            toolbar: [
              [{ header: [2, 3, 4, false] }],
              ['bold', 'italic', 'underline'],
              [{ list: 'ordered' }, { list: 'bullet' }],
              ['blockquote', 'link', 'clean']
            ]
          }
        });

        quill.on('text-change', function () {
          textarea.value = quill.root.innerHTML;
        });

        var form = textarea.closest('form');
        if (form) {
          form.addEventListener('submit', function () {
            textarea.value = quill.root.innerHTML;
          });
        }
      });
    });
  }

  function bindCategoryDelete() {
    document.querySelectorAll('[data-faq-delete-category]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        if (!window.confirm('Delete this FAQ category? Only empty categories can be deleted.')) {
          event.preventDefault();
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initEditors();
    bindCategoryDelete();
  });
})();
