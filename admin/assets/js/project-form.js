(function () {
  'use strict';

  function option(label, value) {
    const item = document.createElement('option');
    item.value = value || '';
    item.textContent = label;
    return item;
  }

  function initProjectLocationForm(form) {
    const constituency = form.querySelector('[data-project-constituency]');
    const ward = form.querySelector('[data-project-ward]');

    if (!constituency || !ward) {
      return;
    }

    const allWards = Array.from(ward.querySelectorAll('option[data-constituency-id]')).map((item) => ({
      id: item.value,
      name: item.textContent,
      constituencyId: item.getAttribute('data-constituency-id') || '',
    }));

    function renderWards() {
      const constituencyId = constituency.value || '';
      const selectedWard = ward.getAttribute('data-selected-ward') || ward.value || '';
      const matches = allWards.filter((item) => item.constituencyId === constituencyId);

      ward.innerHTML = '';

      if (!constituencyId) {
        ward.appendChild(option('Choose constituency first', ''));
        ward.value = '';
        ward.disabled = true;
        ward.classList.add('is-muted');
        return;
      }

      ward.disabled = false;
      ward.classList.remove('is-muted');

      if (matches.length === 0) {
        ward.appendChild(option('No wards configured', ''));
        ward.value = '';
        return;
      }

      ward.appendChild(option('Choose ward', ''));
      matches.forEach((item) => {
        ward.appendChild(option(item.name, item.id));
      });

      if (matches.some((item) => item.id === selectedWard)) {
        ward.value = selectedWard;
      } else {
        ward.value = '';
        ward.setAttribute('data-selected-ward', '');
      }
    }

    constituency.addEventListener('change', () => {
      ward.setAttribute('data-selected-ward', '');
      renderWards();
    });

    ward.addEventListener('change', () => {
      ward.setAttribute('data-selected-ward', ward.value || '');
    });

    renderWards();
  }

  function text(form, name) {
    const field = form.querySelector(`[name="${name}"]`);
    if (!field) return '';
    if (field.tagName === 'SELECT') {
      return field.options[field.selectedIndex] ? field.options[field.selectedIndex].textContent.trim() : '';
    }
    return (field.value || '').trim();
  }

  function setText(form, selector, value) {
    const target = form.querySelector(selector);
    if (target) target.textContent = value;
  }

  function initProjectPreview(form) {
    const media = form.querySelector('[data-preview-media]');
    const progressInput = form.querySelector('[name="pct_complete"]');
    const progressRange = form.querySelector('[data-project-progress-range]');
    const heroUpload = form.querySelector('[name="hero_upload"]');

    function updateProgress(value) {
      const progress = Math.max(0, Math.min(100, parseInt(value || '0', 10) || 0));
      if (progressInput) progressInput.value = progress;
      if (progressRange) progressRange.value = progress;
      setText(form, '[data-preview-progress]', `${progress}%`);
      const bar = form.querySelector('[data-preview-progress-bar]');
      if (bar) bar.style.width = `${progress}%`;
    }

    function updatePreview() {
      const constituency = text(form, 'constituency_id');
      const ward = text(form, 'ward_id');
      const location = text(form, 'location_label') || [ward, constituency].filter(Boolean).join(', ');
      const status = text(form, 'status') || 'Planning';

      setText(form, '[data-preview-name]', text(form, 'name') || 'Untitled project');
      setText(form, '[data-preview-location]', location || 'Choose constituency and ward');
      setText(form, '[data-preview-status]', status);
      setText(form, '[data-preview-units]', text(form, 'units') || '0');
      setText(form, '[data-preview-contractor]', text(form, 'contractor_name') || 'Not assigned');
      setText(form, '[data-preview-funding]', text(form, 'funding_source') || 'Not set');
      updateProgress(progressInput ? progressInput.value : 0);
    }

    if (progressInput && progressRange) {
      progressInput.addEventListener('input', () => updateProgress(progressInput.value));
      progressRange.addEventListener('input', () => updateProgress(progressRange.value));
    }

    if (heroUpload && media) {
      heroUpload.addEventListener('change', () => {
        const file = heroUpload.files && heroUpload.files[0] ? heroUpload.files[0] : null;
        if (!file || !file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        media.innerHTML = '';
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        img.onload = () => URL.revokeObjectURL(url);
        media.appendChild(img);
      });
    }

    ['input', 'change'].forEach((eventName) => {
      form.addEventListener(eventName, (event) => {
        if (event.target && event.target.matches('input, select, textarea')) {
          updatePreview();
        }
      });
    });

    updatePreview();
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.sa-project-form').forEach((form) => {
      initProjectLocationForm(form);
      initProjectPreview(form);
    });
  });
})();
