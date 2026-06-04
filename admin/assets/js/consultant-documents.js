(function () {
  const app = document.querySelector('[data-documents-app]');
  if (!app) {
    return;
  }

  const modal = document.querySelector('[data-doc-modal]');
  const form = modal ? modal.querySelector('form') : null;
  const title = modal ? modal.querySelector('[data-modal-title]') : null;
  const item = modal ? modal.querySelector('[data-modal-item]') : null;
  const note = modal ? modal.querySelector('textarea[name="note"]') : null;
  const endpoint = app.dataset.endpoint || '';

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }

  function openModal(button) {
    if (!modal || !form) {
      return;
    }
    form.type.value = button.dataset.type || '';
    form.id.value = button.dataset.id || '';
    form.action.value = button.dataset.action || '';
    if (title) {
      title.textContent = button.dataset.title || button.getAttribute('aria-label') || 'Review';
    }
    if (item) {
      item.textContent = button.dataset.item || '';
    }
    if (note) {
      note.value = '';
      note.required = ['return', 'flag', 'close'].includes(form.action.value);
      note.placeholder = note.required ? 'Add a clear note for the project team.' : 'Optional review note.';
    }
    modal.hidden = false;
    setTimeout(() => note && note.focus(), 40);
  }

  function closeModal() {
    if (modal) {
      modal.hidden = true;
    }
  }

  function toast(message, isError) {
    const node = document.createElement('div');
    node.className = 'doc-toast' + (isError ? ' is-error' : '');
    node.textContent = message;
    document.body.appendChild(node);
    window.setTimeout(() => node.remove(), 3600);
  }

  async function submitAction(event) {
    event.preventDefault();
    if (!form || !endpoint) {
      return;
    }

    const button = form.querySelector('button[type="submit"]');
    const oldText = button ? button.innerHTML : '';
    if (button) {
      button.disabled = true;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Saving';
    }

    try {
      const payload = {
        type: form.type.value,
        id: form.id.value,
        action: form.action.value,
        note: form.note.value,
      };
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': csrfToken(),
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || data.success === false) {
        throw new Error(data.message || 'Review could not be saved.');
      }
      toast(data.message || 'Review saved.', false);
      closeModal();
      window.setTimeout(() => window.location.reload(), 650);
    } catch (error) {
      toast(error.message || 'Review could not be saved.', true);
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = oldText;
      }
    }
  }

  document.addEventListener('click', function (event) {
    const actionButton = event.target.closest('[data-doc-action]');
    if (actionButton) {
      openModal(actionButton);
      return;
    }

    const copyButton = event.target.closest('[data-copy-link]');
    if (copyButton) {
      const value = copyButton.dataset.copyLink || '';
      if (value && navigator.clipboard) {
        navigator.clipboard.writeText(value).then(() => toast('Document link copied.', false));
      }
      return;
    }

    if (event.target.closest('[data-print-page]')) {
      window.print();
      return;
    }

    if (event.target.matches('[data-modal-close]') || event.target.closest('[data-modal-close]')) {
      closeModal();
      return;
    }

    if (modal && event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  });

  if (form) {
    form.addEventListener('submit', submitAction);
  }
})();
