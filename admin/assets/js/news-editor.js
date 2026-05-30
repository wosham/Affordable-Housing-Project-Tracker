/* News editor rich text and slug helpers */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-news-editor]');
    if (!form) return;

    var titleInput = form.querySelector('[data-title-source]');
    var slugInput = form.querySelector('[data-slug-target]');
    var bodyInput = form.querySelector('[data-quill-body]');
    var editorNode = document.getElementById('newsQuillEditor');
    var quill = null;

    function slugify(value) {
      return String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    }

    if (titleInput && slugInput) {
      titleInput.addEventListener('input', function () {
        if (!slugInput.value.trim()) {
          slugInput.value = slugify(titleInput.value);
        }
      });
    }

    if (window.Quill && editorNode) {
      quill = new window.Quill(editorNode, {
        theme: 'snow',
        modules: {
          toolbar: '#newsQuillToolbar'
        }
      });
    } else if (editorNode) {
      editorNode.setAttribute('contenteditable', 'true');
      editorNode.classList.add('is-fallback-editor');
    }

    form.addEventListener('submit', function () {
      if (bodyInput && quill) {
        bodyInput.value = quill.root.innerHTML;
      } else if (bodyInput && editorNode) {
        bodyInput.value = editorNode.innerHTML;
      }
    });
  });
}());
