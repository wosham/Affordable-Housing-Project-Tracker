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

    if (!constituency || !ward) return;

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
      matches.forEach((item) => ward.appendChild(option(item.name, item.id)));

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

  function assetUrl(path) {
    const value = String(path || '').trim();
    if (!value) return '';
    if (/^(https?:)?\/\//i.test(value) || value.charAt(0) === '/') return value;
    const base = window.AHPTC && typeof window.AHPTC.baseUrl === 'function' ? window.AHPTC.baseUrl().replace(/\/$/, '') : '';
    return base ? `${base}/${value.replace(/^\/+/, '')}` : value;
  }

  function filename(path) {
    const value = String(path || '').split('?')[0].split('#')[0].replace(/\\/g, '/');
    return value.split('/').filter(Boolean).pop() || 'Selected media';
  }

  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    })[char]);
  }

  function mediaPaths(value) {
    return Array.from(new Set(String(value || '').split(/[\r\n,]+/).map((item) => item.trim()).filter(Boolean)));
  }

  function writeMediaPaths(input, paths) {
    input.value = paths.join('\n');
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function initProjectMedia(form) {
    const heroInput = form.querySelector('[data-project-hero-input]');
    const heroPicker = form.querySelector('[data-project-hero-picker]');
    const heroPreview = heroPicker ? heroPicker.querySelector('[data-cms-asset-preview]') : null;
    const heroName = heroPicker ? heroPicker.querySelector('[data-cms-asset-name]') : null;
    const heroPath = heroPicker ? heroPicker.querySelector('[data-project-hero-path]') : null;
    const heroClear = heroPicker ? heroPicker.querySelector('[data-project-clear-hero]') : null;
    const galleryInput = form.querySelector('[data-project-gallery-input]');
    const galleryPicker = form.querySelector('[data-project-gallery-picker]');
    const galleryGrid = form.querySelector('[data-project-gallery-grid]');
    const galleryName = galleryPicker ? galleryPicker.querySelector('[data-cms-asset-name]') : null;

    function renderHero() {
      if (!heroInput || !heroPicker || !heroPreview) return;
      const path = heroInput.value.trim();
      heroPicker.classList.toggle('has-media', !!path);
      if (heroName) heroName.textContent = path ? filename(path) : 'No hero selected';
      if (heroPath) heroPath.textContent = path || 'Choose an approved media asset for the public hero.';
      heroPreview.innerHTML = path
        ? `<img src="${esc(assetUrl(path))}" alt="">`
        : '<span><i class="fa-solid fa-image" aria-hidden="true"></i></span>';
    }

    function renderGallery() {
      if (!galleryInput || !galleryGrid || !galleryPicker) return;
      const paths = mediaPaths(galleryInput.value);
      galleryPicker.classList.toggle('has-media', paths.length > 0);
      if (galleryName) galleryName.textContent = paths.length ? `${paths.length} image${paths.length === 1 ? '' : 's'} selected` : 'No gallery images selected';

      if (!paths.length) {
        galleryGrid.innerHTML = '<div class="sa-project-gallery-empty"><i class="fa-solid fa-image" aria-hidden="true"></i><strong>No gallery media selected</strong><span>Use the media library picker to attach project images.</span></div>';
        return;
      }

      galleryGrid.innerHTML = paths.map((path) => (
        `<article class="sa-project-gallery-item" data-gallery-path="${esc(path)}">` +
          `<img src="${esc(assetUrl(path))}" alt="">` +
          `<div><strong>${esc(filename(path))}</strong><small>${esc(path)}</small></div>` +
          `<button class="btn btn--icon btn--outline" type="button" data-project-remove-gallery="${esc(path)}" aria-label="Remove gallery image"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>` +
        '</article>'
      )).join('');
    }

    if (heroInput) {
      heroInput.addEventListener('input', renderHero);
      renderHero();
    }

    if (heroClear && heroInput) {
      heroClear.addEventListener('click', () => {
        heroInput.value = '';
        heroInput.dispatchEvent(new Event('input', { bubbles: true }));
      });
    }

    if (galleryInput) {
      galleryInput.addEventListener('input', renderGallery);
      renderGallery();
    }

    if (galleryGrid && galleryInput) {
      galleryGrid.addEventListener('click', (event) => {
        const button = event.target.closest('[data-project-remove-gallery]');
        if (!button) return;
        const removePath = button.getAttribute('data-project-remove-gallery') || '';
        writeMediaPaths(galleryInput, mediaPaths(galleryInput.value).filter((path) => path !== removePath));
      });
    }
  }

  function initProjectPreview(form) {
    const media = form.querySelector('[data-preview-media]');
    const progressInput = form.querySelector('[name="pct_complete"]');
    const progressRange = form.querySelector('[data-project-progress-range]');
    const heroPath = form.querySelector('[name="hero_image"]');

    function updateProgress(value) {
      const progress = Math.max(0, Math.min(100, parseInt(value || '0', 10) || 0));
      if (progressInput) progressInput.value = progress;
      if (progressRange) progressRange.value = progress;
      setText(form, '[data-preview-progress]', `${progress}%`);
      const bar = form.querySelector('[data-preview-progress-bar]');
      if (bar) bar.style.width = `${progress}%`;
    }

    function setPreviewImage(path) {
      if (!media) return;
      const src = assetUrl(path);
      media.innerHTML = src ? `<img src="${esc(src)}" alt="">` : '<span><i class="fa-solid fa-building" aria-hidden="true"></i></span>';
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

    if (heroPath) {
      heroPath.addEventListener('input', () => setPreviewImage(heroPath.value));
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
      initProjectMedia(form);
      initProjectPreview(form);
    });
  });
}());