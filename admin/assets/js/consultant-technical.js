(function () {
  const modal = document.querySelector('[data-technical-modal]');
  if (!modal || !window.AHPTC) return;

  const form = modal.querySelector('form');
  const title = modal.querySelector('[data-modal-title]');
  const item = modal.querySelector('[data-modal-item]');
  const closeButtons = modal.querySelectorAll('[data-modal-close]');

  function openModal(button) {
    form.reset();
    form.elements.type.value = button.dataset.type || '';
    form.elements.id.value = button.dataset.id || '';
    form.elements.action.value = button.dataset.action || '';
    title.textContent = button.dataset.title || 'Review item';
    item.textContent = button.dataset.item || '';
    modal.hidden = false;
    form.elements.note.focus();
  }

  function closeModal() {
    modal.hidden = true;
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-technical-action]');
    if (button) {
      event.preventDefault();
      openModal(button);
      return;
    }

    if (event.target === modal) {
      closeModal();
    }
  });

  closeButtons.forEach((button) => button.addEventListener('click', closeModal));

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const submit = form.querySelector('[type="submit"]');
    const payload = {
      type: form.elements.type.value,
      id: form.elements.id.value,
      action: form.elements.action.value,
      note: form.elements.note.value.trim(),
    };
    const needsNote = ['return', 'reject', 'flag'].includes(payload.action);
    if (needsNote && !payload.note) {
      window.AHPTC.toast('Add a review note before saving.', 'error');
      form.elements.note.focus();
      return;
    }

    submit.disabled = true;
    try {
      const response = await window.AHPTC.request('api/consultant/technical-action.php', {
        method: 'POST',
        body: payload,
      });
      window.AHPTC.toast(response.message || 'Review saved.', 'success');
      window.setTimeout(() => window.location.reload(), 650);
    } catch (error) {
      window.AHPTC.toast(error.message || 'Review could not be saved.', 'error');
    } finally {
      submit.disabled = false;
    }
  });
})();
