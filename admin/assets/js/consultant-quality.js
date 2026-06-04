(function () {
  const modal = document.querySelector('[data-quality-modal]');
  if (!modal || !window.AHPTC) return;
  const form = modal.querySelector('form');
  const title = modal.querySelector('[data-modal-title]');
  const item = modal.querySelector('[data-modal-item]');
  function openModal(button) {
    form.reset();
    form.elements.type.value = button.dataset.type || '';
    form.elements.id.value = button.dataset.id || '';
    form.elements.action.value = button.dataset.action || '';
    title.textContent = button.dataset.title || 'Review quality record';
    item.textContent = button.dataset.item || '';
    modal.hidden = false;
    form.elements.note.focus();
  }
  function closeModal() { modal.hidden = true; }
  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-quality-action]');
    if (button) { event.preventDefault(); openModal(button); return; }
    if (event.target === modal) closeModal();
  });
  modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const action = form.elements.action.value;
    const note = form.elements.note.value.trim();
    if (['return', 'flag', 'close', 'reopen'].includes(action) && !note) {
      window.AHPTC.toast('Add a review note before saving.', 'error');
      form.elements.note.focus();
      return;
    }
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const response = await window.AHPTC.request('api/consultant/quality-action.php', {
        method: 'POST',
        body: {
          type: form.elements.type.value,
          id: form.elements.id.value,
          action,
          note,
          severity: form.elements.severity.value,
          documents_checked: form.elements.documents_checked.checked ? 1 : 0,
        },
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
