(function () {
  'use strict';

  var quillReady = null;

  function loadQuill() {
    if (window.Quill) return Promise.resolve();
    if (quillReady) return quillReady;

    quillReady = new Promise(function (resolve) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
      document.head.appendChild(link);

      var script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
      script.onload = resolve;
      script.onerror = resolve;
      document.head.appendChild(script);
    });

    return quillReady;
  }

  function request(url, payload) {
    return window.AHPTC.request(url, {
      method: 'POST',
      body: payload
    });
  }

  function state(text, ok) {
    document.querySelectorAll('[data-cms-save-state]').forEach(function (node) {
      node.textContent = text;
      node.style.color = ok === false ? 'var(--admin-danger)' : 'var(--admin-text-muted)';
    });
  }

  function initQuillEditors() {
    return loadQuill().then(function () {
      document.querySelectorAll('[data-quill-editor]').forEach(function (editor) {
        var hidden = editor.parentElement.querySelector('[data-cms-field="body"]');
        if (!hidden) return;

        if (!window.Quill) {
          editor.classList.add('is-hidden');
          hidden.classList.remove('is-hidden');
          return;
        }

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
          hidden.value = quill.root.innerHTML;
          state('Unsaved changes');
        });
      });
    });
  }

  function sectionPayload(section) {
    var content = {};
    section.querySelectorAll('[data-cms-field]').forEach(function (field) {
      content[field.getAttribute('data-cms-field')] = field.value || '';
    });

    var page = document.querySelector('[data-cms-page-id]');
    return {
      page_id: page ? page.getAttribute('data-cms-page-id') : '',
      section_id: section.getAttribute('data-section-id'),
      section_key: section.getAttribute('data-section-key'),
      content: content
    };
  }

  function bindPageForm() {
    var form = document.querySelector('[data-cms-page-form]');
    if (!form) return;

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var payload = {};
      new FormData(form).forEach(function (value, key) {
        payload[key] = value;
      });

      state('Saving page...');
      request('api/cms/save-page.php', payload).then(function () {
        state('Page settings saved', true);
      }).catch(function (error) {
        state(error.message || 'Page save failed', false);
      });
    });
  }

  function bindSections() {
    document.querySelectorAll('[data-cms-section]').forEach(function (section) {
      section.addEventListener('input', function () {
        state('Unsaved changes');
      });

      var save = section.querySelector('[data-cms-save-section]');
      if (save) {
        save.addEventListener('click', function () {
          save.disabled = true;
          save.textContent = 'Saving...';
          request('api/cms/save-section.php', sectionPayload(section)).then(function () {
            save.textContent = 'Saved';
            state('Section saved', true);
            setTimeout(function () {
              save.disabled = false;
              save.innerHTML = '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Section';
            }, 900);
          }).catch(function (error) {
            save.disabled = false;
            save.innerHTML = '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Section';
            state(error.message || 'Section save failed', false);
          });
        });
      }

      var toggle = section.querySelector('[data-cms-toggle-section]');
      if (toggle) {
        toggle.addEventListener('click', function () {
          request('api/cms/toggle-section.php', {
            section_id: section.getAttribute('data-section-id')
          }).then(function (data) {
            toggle.textContent = data.is_visible ? 'Hide' : 'Show';
            state(data.message || 'Visibility updated', true);
          }).catch(function (error) {
            state(error.message || 'Visibility update failed', false);
          });
        });
      }
    });
  }

  function bindSettings() {
    document.querySelectorAll('[data-cms-setting-form]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = {};
        new FormData(form).forEach(function (value, key) {
          payload[key] = value;
        });

        request('api/cms/save-setting.php', payload).then(function () {
          state('Setting saved', true);
        }).catch(function (error) {
          state(error.message || 'Setting save failed', false);
        });
      });
    });
  }

  function bindUploads() {
    document.querySelectorAll('[data-cms-upload]').forEach(function (wrap) {
      var input = wrap.querySelector('[data-cms-upload-input]');
      var target = wrap.querySelector('[data-cms-upload-target]');
      var folder = wrap.getAttribute('data-upload-folder') || 'cms';
      if (!input || !target) return;

      input.addEventListener('change', function () {
        if (!input.files || !input.files[0]) return;

        var body = new FormData();
        body.append('file', input.files[0]);
        body.append('folder', folder);
        body.append('csrf_form', 'cms_editor');

        wrap.classList.add('is-uploading');
        state('Uploading image...');

        window.AHPTC.request('api/media/upload.php', {
          method: 'POST',
          body: body
        }).then(function (data) {
          target.value = data.media && data.media.path ? data.media.path : '';
          target.dispatchEvent(new Event('input', { bubbles: true }));
          wrap.classList.remove('is-uploading');
          state('Image uploaded', true);
        }).catch(function (error) {
          wrap.classList.remove('is-uploading');
          state(error.message || 'Image upload failed', false);
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initQuillEditors();
    bindPageForm();
    bindSections();
    bindSettings();
    bindUploads();
  });
})();
