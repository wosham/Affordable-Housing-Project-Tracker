(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function setStatus(root, message, state) {
    var status = qs('[data-intern-work-status]', root);
    if (!status) return;
    status.textContent = message || '';
    status.dataset.state = state || '';
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }

    return fetch(url, options).then(function (response) {
      return response.json().catch(function () {
        return { success: false, message: 'The server response could not be read.' };
      }).then(function (payload) {
        if (!response.ok && payload && payload.success !== true) {
          throw new Error(payload.message || 'Request failed.');
        }
        return payload;
      });
    });
  }

  function setButton(button, loading, fallback) {
    if (!button) return;
    button.disabled = !!loading;
    if (loading) {
      button.dataset.originalText = button.dataset.originalText || button.innerHTML;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    } else if (fallback || button.dataset.originalText) {
      button.innerHTML = fallback || button.dataset.originalText;
    }
  }

  function initEntryForm(form) {
    var buttons = qsa('[data-save-mode]', form);
    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var status = qs('[data-entry-status]', form);
        if (status) status.value = button.dataset.saveMode || 'submitted';
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var submitter = event.submitter || buttons[buttons.length - 1];
      setButton(submitter, true);
      setStatus(form, 'Saving site note...', 'loading');

      request(form.action, { method: 'POST', body: new FormData(form) }).then(function (payload) {
        if (!payload.success) throw new Error(payload.message || 'Site note could not be saved.');
        setStatus(form, payload.message || 'Site note saved.', 'success');
        if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
          window.AHPTC.toast(payload.message || 'Site note saved.', 'success');
        }
        window.setTimeout(function () {
          window.location.reload();
        }, 650);
      }).catch(function (error) {
        setStatus(form, error.message || 'Site note could not be saved.', 'error');
        if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
          window.AHPTC.toast(error.message || 'Site note could not be saved.', 'error');
        }
        setButton(submitter, false);
      });
    });
  }

  function initPhotoForm(form) {
    var zone = qs('[data-upload-zone]', form);
    var input = qs('[data-photo-file]', form);
    var picker = qs('[data-photo-picker]', form);
    var name = qs('[data-photo-file-name]', form);

    if (picker && input) {
      picker.addEventListener('click', function () {
        input.click();
      });
    }

    if (input && name) {
      input.addEventListener('change', function () {
        name.textContent = input.files && input.files[0] ? input.files[0].name : 'Choose or drop a site photo';
      });
    }

    if (zone && input) {
      ['dragenter', 'dragover'].forEach(function (eventName) {
        zone.addEventListener(eventName, function (event) {
          event.preventDefault();
          zone.classList.add('is-dragging');
        });
      });
      ['dragleave', 'drop'].forEach(function (eventName) {
        zone.addEventListener(eventName, function (event) {
          event.preventDefault();
          zone.classList.remove('is-dragging');
        });
      });
      zone.addEventListener('drop', function (event) {
        if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
          input.files = event.dataTransfer.files;
          if (name) name.textContent = event.dataTransfer.files[0].name;
        }
      });
    }

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        var lat = qs('[data-photo-latitude]', form);
        var lng = qs('[data-photo-longitude]', form);
        if (lat) lat.value = position.coords.latitude;
        if (lng) lng.value = position.coords.longitude;
      }, function () {}, { enableHighAccuracy: true, timeout: 9000, maximumAge: 60000 });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var submitter = event.submitter || qs('[type="submit"]', form);
      setButton(submitter, true, submitter ? submitter.dataset.originalText : '');
      setStatus(form, 'Uploading site photo...', 'loading');

      request(form.action, { method: 'POST', body: new FormData(form) }).then(function (payload) {
        if (!payload.success) throw new Error(payload.message || 'Photo could not be uploaded.');
        setStatus(form, payload.message || 'Site photo uploaded.', 'success');
        if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
          window.AHPTC.toast(payload.message || 'Site photo uploaded.', 'success');
        }
        window.setTimeout(function () {
          window.location.reload();
        }, 650);
      }).catch(function (error) {
        setStatus(form, error.message || 'Photo could not be uploaded.', 'error');
        if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
          window.AHPTC.toast(error.message || 'Photo could not be uploaded.', 'error');
        }
        setButton(submitter, false);
      });
    });
  }

  function initDeletes() {
    qsa('[data-photo-delete]').forEach(function (button) {
      button.addEventListener('click', function () {
        if (!window.confirm('Remove this photo from your gallery?')) return;
        var data = new FormData();
        data.set('photo_id', button.dataset.photoDelete || '');
        var tokenName = document.querySelector('meta[name="csrf-token-name"]');
        var token = document.querySelector('meta[name="csrf-token"]');
        if (tokenName && token) data.set(tokenName.content, token.content);

        request('../../api/intern/photo-delete.php', { method: 'POST', body: data }).then(function (payload) {
          if (!payload.success) throw new Error(payload.message || 'Photo could not be removed.');
          var card = button.closest('[data-photo-card]');
          if (card) card.remove();
        }).catch(function (error) {
          window.alert(error.message || 'Photo could not be removed.');
        });
      });
    });
  }

  function init() {
    qsa('[data-intern-entry-form]').forEach(initEntryForm);
    qsa('[data-intern-photo-form]').forEach(initPhotoForm);
    initDeletes();
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, { initInternWork: init });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
