(function () {
  const page = document.querySelector('[data-progress-page]');
  const form = document.querySelector('[data-progress-form]');
  if (!page || !form || !window.AHPTC) {
    return;
  }

  const endpoint = page.dataset.endpoint || 'api/projects/update-progress.php';
  const mediaListEndpoint = page.dataset.mediaListEndpoint || 'api/media/list.php';
  const mediaUploadEndpoint = page.dataset.mediaUploadEndpoint || 'api/media/upload.php';
  const myProjectUrl = page.dataset.myProjectUrl || 'admin/contractor/my-project.php';
  const number = form.querySelector('[data-progress-number]');
  const range = form.querySelector('[data-progress-range]');
  const photo = form.querySelector('[data-progress-photo]');
  const uploadLabel = form.querySelector('[data-upload-label]');
  const evidencePicker = form.querySelector('[data-evidence-picker]');
  const evidenceModal = document.querySelector('[data-evidence-modal]');
  const evidenceGrid = document.querySelector('[data-evidence-grid]');
  const evidenceStatus = document.querySelector('[data-evidence-status]');
  const evidenceSearch = document.querySelector('[data-evidence-search]');
  const mediaIdInput = form.querySelector('[data-progress-media-id]');
  const preview = form.querySelector('[data-evidence-preview]');
  const previewImage = form.querySelector('[data-evidence-preview-image]');
  const previewTitle = form.querySelector('[data-evidence-preview-title]');
  const warning = form.querySelector('[data-progress-warning]');
  const original = parseInt(form.dataset.originalProgress || (number ? number.defaultValue || number.value : '0') || '0', 10) || 0;
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
  const maxBytes = 10 * 1024 * 1024;

  function toast(message, type) {
    if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
      window.AHPTC.toast(message, type || 'success');
      return;
    }
    const node = document.createElement('div');
    node.className = 'contractor-toast' + (type === 'error' ? ' is-error' : '');
    node.textContent = message;
    document.body.appendChild(node);
    window.setTimeout(() => node.remove(), 3600);
  }

  function setProgress(value, source) {
    value = Math.max(0, Math.min(100, parseInt(value || '0', 10) || 0));
    if (number && source !== number) number.value = value;
    if (range && source !== range) range.value = value;
    if (warning) {
      const delta = value - original;
      if (delta < 0) {
        warning.hidden = false;
        warning.textContent = 'Progress is lower than the current record. Add a clear note explaining the correction.';
      } else if (delta >= 20) {
        warning.hidden = false;
        warning.textContent = 'This is a large progress change. Make sure the work summary and photo clearly support it.';
      } else {
        warning.hidden = true;
        warning.textContent = '';
      }
    }
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function setEvidenceStatus(message) {
    if (evidenceStatus) evidenceStatus.textContent = message || '';
  }

  function setSelectedEvidence(item) {
    if (mediaIdInput) mediaIdInput.value = item && item.id ? item.id : '';
    if (photo && item && item.id) photo.value = '';
    if (preview && previewImage && previewTitle && uploadLabel && item) {
      preview.hidden = false;
      previewImage.src = item.url || '';
      previewImage.alt = item.title || 'Selected site evidence';
      previewTitle.textContent = item.title || item.original_name || 'Selected site evidence';
      uploadLabel.textContent = (item.size_label ? item.size_label + ' / ' : '') + (item.created_label || 'Media library');
      if (evidencePicker) evidencePicker.classList.add('has-file');
    } else if (preview && uploadLabel) {
      preview.hidden = true;
      if (previewImage) previewImage.src = '';
      if (previewTitle) previewTitle.textContent = 'Selected evidence';
      uploadLabel.textContent = 'JPG, PNG or WebP evidence up to the configured limit.';
      if (evidencePicker) evidencePicker.classList.remove('has-file');
    }
  }

  function renderEvidence(items) {
    if (!evidenceGrid) return;
    if (!items.length) {
      evidenceGrid.innerHTML = '<div class="empty-state empty-state--compact" style="grid-column:1/-1"><strong class="empty-state__title">No site evidence found</strong><span class="empty-state__text">Upload a new photo or adjust your search.</span></div>';
      return;
    }
    evidenceGrid.innerHTML = items.map(function (item) {
      return '<button class="contractor-media-card" type="button" data-evidence-id="' + item.id + '" data-evidence-payload="' + escapeHtml(JSON.stringify(item)) + '">' +
        '<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.alt_text || item.title || 'Site evidence') + '" loading="lazy">' +
        '<strong title="' + escapeHtml(item.title || item.original_name) + '">' + escapeHtml(item.title || item.original_name || 'Site evidence') + '</strong>' +
        '<small>' + escapeHtml((item.size_label || '') + (item.created_label ? ' / ' + item.created_label : '')) + '</small>' +
      '</button>';
    }).join('');
  }

  async function loadEvidence() {
    if (!evidenceGrid) return;
    const params = new URLSearchParams({
      folder: 'site-photos',
      type: 'image',
      q: evidenceSearch ? evidenceSearch.value || '' : '',
      per_page: '60'
    });
    evidenceGrid.innerHTML = '<div class="empty-state empty-state--compact" style="grid-column:1/-1"><strong class="empty-state__title">Loading media...</strong></div>';
    setEvidenceStatus('Loading your site evidence...');
    try {
      const data = await window.AHPTC.request(mediaListEndpoint + '?' + params.toString(), { method: 'GET' });
      renderEvidence(data.media || []);
      setEvidenceStatus(((data.pagination && data.pagination.total) || 0) + ' site evidence file(s)');
    } catch (error) {
      evidenceGrid.innerHTML = '<div class="empty-state empty-state--compact" style="grid-column:1/-1"><strong class="empty-state__title">Media could not load</strong><span class="empty-state__text">' + escapeHtml(error.message || 'Try again.') + '</span></div>';
      setEvidenceStatus(error.message || 'Could not load media.');
    }
  }

  function openEvidenceModal() {
    if (!evidenceModal) return;
    evidenceModal.hidden = false;
    evidenceModal.setAttribute('aria-hidden', 'false');
    loadEvidence();
  }

  function closeEvidenceModal() {
    if (!evidenceModal) return;
    evidenceModal.hidden = true;
    evidenceModal.setAttribute('aria-hidden', 'true');
  }

  async function uploadEvidence(file) {
    if (!file) return;
    const body = new FormData();
    body.append('csrf_form', (window.AHPTC.csrfForm && window.AHPTC.csrfForm()) || 'contractor_progress');
    body.append('folder', 'site-photos');
    body.append('title', file.name.replace(/\.[^.]+$/, ''));
    body.append('alt_text', file.name.replace(/\.[^.]+$/, ''));
    body.append('file', file);
    setEvidenceStatus('Uploading evidence...');
    try {
      const data = await window.AHPTC.request(mediaUploadEndpoint, { method: 'POST', body: body });
      setSelectedEvidence(data.media || null);
      if (photo) photo.value = '';
      toast(data.message || 'Evidence uploaded.', 'success');
      closeEvidenceModal();
      loadEvidence();
    } catch (error) {
      toast(error.message || 'Evidence upload failed.', 'error');
      setEvidenceStatus(error.message || 'Upload failed.');
    }
  }

  const projectJump = form.querySelector('[data-project-jump]');
  if (projectJump) {
    projectJump.addEventListener('change', function () {
      if (projectJump.value) window.location.href = projectJump.value;
    });
  }

  if (number) number.addEventListener('input', function () { setProgress(number.value, number); });
  if (range) range.addEventListener('input', function () { setProgress(range.value, range); });

  if (photo && uploadLabel) {
    photo.addEventListener('change', function () {
      const file = photo.files && photo.files[0] ? photo.files[0] : null;
      if (!file) {
        uploadLabel.textContent = 'JPG, PNG or WebP evidence up to the configured limit.';
        return;
      }
      if (!allowedTypes.includes(file.type)) {
        photo.value = '';
        toast('Use a JPG, PNG or WebP site photo.', 'error');
        return;
      }
      if (file.size > maxBytes) {
        photo.value = '';
        toast('Site photo must be 10MB or less.', 'error');
        return;
      }
      uploadEvidence(file);
    });
  }

  document.querySelectorAll('[data-evidence-library-open]').forEach(function (button) {
    button.addEventListener('click', openEvidenceModal);
  });
  document.querySelectorAll('[data-evidence-upload-trigger], [data-evidence-modal-upload]').forEach(function (button) {
    button.addEventListener('click', function () { if (photo) photo.click(); });
  });
  document.querySelectorAll('[data-evidence-modal-close]').forEach(function (button) {
    button.addEventListener('click', closeEvidenceModal);
  });
  document.querySelectorAll('[data-evidence-clear]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (photo) photo.value = '';
      setSelectedEvidence(null);
    });
  });

  if (evidenceModal) {
    evidenceModal.addEventListener('click', function (event) {
      if (event.target === evidenceModal) closeEvidenceModal();
      const card = event.target.closest && event.target.closest('[data-evidence-payload]');
      if (card) {
        try {
          setSelectedEvidence(JSON.parse(card.getAttribute('data-evidence-payload') || '{}'));
          closeEvidenceModal();
        } catch (error) {
          toast('Selected evidence could not be read.', 'error');
        }
      }
    });
  }

  if (evidenceSearch) {
    let searchTimer = null;
    evidenceSearch.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadEvidence, 250);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && evidenceModal && !evidenceModal.hidden) closeEvidenceModal();
  });

  form.addEventListener('reset', function () {
    window.setTimeout(function () {
      setProgress(original, null);
      setSelectedEvidence(null);
    }, 0);
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    const oldText = submit ? submit.innerHTML : '';
    if (submit) {
      submit.disabled = true;
      submit.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Submitting';
    }

    try {
      const body = new FormData(form);
      const value = parseInt(body.get('pct_complete') || '0', 10) || 0;
      const workSummary = String(body.get('work_summary') || '').trim();
      const note = String(body.get('note') || '').trim();
      const file = photo && photo.files ? photo.files[0] : null;
      const selectedMediaId = mediaIdInput ? String(mediaIdInput.value || '').trim() : '';
      if (!workSummary || !note) {
        throw new Error('Work summary and progress note are required.');
      }
      if (!file && !selectedMediaId) {
        throw new Error('A site photo is required for this progress update.');
      }
      if (Math.abs(value - original) >= 20 && workSummary.length < 40) {
        throw new Error('For a large progress change, add a fuller work summary.');
      }
      // Prefer media library id once uploaded; avoid double-sending file
      if (selectedMediaId && file) {
        body.delete('progress_photo');
      }
      const data = await window.AHPTC.request(endpoint, { method: 'POST', body: body });
      toast(data.message || 'Progress update submitted.', 'success');
      const projectId = body.get('project_id') || '';
      const sep = String(myProjectUrl).indexOf('?') >= 0 ? '&' : '?';
      const target = myProjectUrl + sep + 'project_id=' + encodeURIComponent(projectId);
      window.setTimeout(function () {
        window.location.href = target;
      }, 800);
    } catch (error) {
      toast(error.message || 'Progress update could not be submitted.', 'error');
    } finally {
      if (submit) {
        submit.disabled = false;
        submit.innerHTML = oldText;
      }
    }
  });
})();
