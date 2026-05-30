(function () {
  'use strict';

  var quillPromise = null;

  function loadQuill() {
    if (window.Quill) return Promise.resolve(true);
    if (quillPromise) return quillPromise;

    quillPromise = new Promise(function (resolve) {
      var stylesheet = document.createElement('link');
      stylesheet.rel = 'stylesheet';
      stylesheet.href = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
      document.head.appendChild(stylesheet);

      var script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
      script.onload = function () { resolve(!!window.Quill); };
      script.onerror = function () { resolve(false); };
      document.head.appendChild(script);
    });

    return quillPromise;
  }

  function initEditors() {
    loadQuill().then(function (available) {
      document.querySelectorAll('[data-faq-quill]').forEach(function (editor) {
        var textarea = editor.parentElement.querySelector('textarea[name="answer"]');
        if (!textarea) return;

        if (!available) {
          editor.hidden = true;
          textarea.classList.remove('is-enhanced');
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
