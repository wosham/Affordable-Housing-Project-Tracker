(function () {
  const modal = document.querySelector('[data-contract-modal]');
  if (!modal || !window.AHPTC) return;

  const form = modal.querySelector('form');
  const title = modal.querySelector('[data-modal-title]');
  const item = modal.querySelector('[data-modal-item]');
  const typeInput = form.elements.type;
  const eotFields = modal.querySelectorAll('[data-eot-field]');
  const variationFields = modal.querySelectorAll('[data-variation-field]');

  function showFields(type) {
    eotFields.forEach((field) => { field.hidden = type !== 'eot'; });
    variationFields.forEach((field) => { field.hidden = type !== 'variation'; });
  }

  function openModal(button) {
    form.reset();
    typeInput.value = button.dataset.type || '';
    form.elements.id.value = button.dataset.id || '';
    form.elements.action.value = button.dataset.action || '';
    form.elements.recommended_days.value = button.dataset.days || '';
    form.elements.recommended_amount.value = button.dataset.amount || '';
    form.elements.time_impact_days.value = button.dataset.time || '';
    title.textContent = button.dataset.title || 'Review contract item';
    item.textContent = button.dataset.item || '';
    showFields(typeInput.value);
    modal.hidden = false;
    const first = typeInput.value === 'eot' ? form.elements.recommended_days : form.elements.recommended_amount;
    if (first && form.elements.action.value === 'recommend') first.focus();
    else form.elements.note.focus();
  }

  function closeModal() {
    modal.hidden = true;
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-contract-action]');
    if (button) {
      event.preventDefault();
      openModal(button);
      return;
    }
    if (event.target === modal) closeModal();
  });

  modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const action = form.elements.action.value;
    const note = form.elements.note.value.trim();
    if (['return', 'reject', 'flag'].includes(action) && !note) {
      window.AHPTC.toast('Add a review note before saving.', 'error');
      form.elements.note.focus();
      return;
    }

    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const payload = {
        type: form.elements.type.value,
        id: form.elements.id.value,
        action,
        note,
        recommended_days: form.elements.recommended_days.value,
        delay_category: form.elements.delay_category.value,
        recommended_amount: form.elements.recommended_amount.value,
        time_impact_days: form.elements.time_impact_days.value,
        cost_impact_status: form.elements.cost_impact_status.value,
        documents_checked: form.elements.documents_checked.checked ? 1 : 0,
      };
      const response = await window.AHPTC.request('api/consultant/contract-action.php', { method: 'POST', body: payload });
      window.AHPTC.toast(response.message || 'Review saved.', 'success');
      window.setTimeout(() => window.location.reload(), 650);
    } catch (error) {
      window.AHPTC.toast(error.message || 'Review could not be saved.', 'error');
    } finally {
      submit.disabled = false;
    }
  });
})();
