(function () {
  const detailModal = document.querySelector('[data-progress-detail-modal]');
  const lightbox = document.querySelector('[data-progress-lightbox]');
  if (!detailModal && !lightbox) return;

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function statusClass(status) {
    status = String(status || 'submitted').toLowerCase();
    if (status === 'accepted' || status === 'approved') return 'badge--success';
    if (status === 'rejected' || status === 'returned') return 'badge--danger';
    return 'badge--info';
  }

  function openDetail(payload) {
    if (!detailModal) return;
    const set = function (sel, value) {
      const node = qs(sel, detailModal);
      if (node) node.textContent = value || '—';
    };

    set('[data-detail-title]', payload.milestone || 'Progress update');
    set('[data-detail-when]', payload.when || '');
    set('[data-detail-change]', (payload.old != null ? payload.old : '—') + '% → ' + (payload.new != null ? payload.new : '—') + '%');
    set('[data-detail-by]', payload.by || 'Project team');
    set('[data-detail-milestone]', payload.milestone || 'Not set');
    set('[data-detail-summary]', payload.summary || 'No work summary recorded.');
    set('[data-detail-note]', payload.note || 'No note recorded.');

    const statusBadge = qs('[data-detail-status-badge]', detailModal);
    if (statusBadge) {
      const label = payload.status_label || String(payload.status || 'submitted').replace(/-/g, ' ');
      statusBadge.textContent = label;
      statusBadge.className = 'badge ' + statusClass(payload.status);
    }

    const blockersWrap = qs('[data-detail-blockers-wrap]', detailModal);
    const weatherWrap = qs('[data-detail-weather-wrap]', detailModal);
    if (blockersWrap) {
      blockersWrap.hidden = !payload.blockers;
      set('[data-detail-blockers]', payload.blockers || '');
    }
    if (weatherWrap) {
      weatherWrap.hidden = !payload.weather;
      set('[data-detail-weather]', payload.weather || '');
    }

    const photoWrap = qs('[data-detail-photo-wrap]', detailModal);
    const photoImg = qs('[data-detail-photo-img]', detailModal);
    if (photoWrap && photoImg) {
      if (payload.photo) {
        photoWrap.hidden = false;
        photoImg.src = payload.photo;
        photoImg.alt = payload.photo_label || 'Site evidence';
        photoWrap.dataset.photoUrl = payload.photo;
        photoWrap.dataset.photoTitle = payload.photo_label || payload.milestone || 'Site evidence';
      } else {
        photoWrap.hidden = true;
        photoImg.src = '';
        delete photoWrap.dataset.photoUrl;
      }
    }

    detailModal.hidden = false;
    detailModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('contractor-modal-open');
    const body = qs('.contractor-progress-modal__body', detailModal);
    if (body) body.scrollTop = 0;
  }

  function closeDetail() {
    if (!detailModal) return;
    detailModal.hidden = true;
    detailModal.setAttribute('aria-hidden', 'true');
    if (!lightbox || lightbox.hidden) {
      document.body.classList.remove('contractor-modal-open');
    }
  }

  function openLightbox(url, title) {
    if (!lightbox || !url) return;
    const img = qs('[data-progress-lightbox-img]', lightbox);
    const cap = qs('[data-progress-lightbox-caption]', lightbox);
    if (img) {
      img.src = url;
      img.alt = title || 'Site photo evidence';
    }
    if (cap) cap.textContent = title || 'Site photo evidence';
    lightbox.hidden = false;
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.classList.add('contractor-modal-open');
  }

  function closeLightbox() {
    if (!lightbox) return;
    const img = qs('[data-progress-lightbox-img]', lightbox);
    if (img) img.src = '';
    lightbox.hidden = true;
    lightbox.setAttribute('aria-hidden', 'true');
    if (!detailModal || detailModal.hidden) {
      document.body.classList.remove('contractor-modal-open');
    }
  }

  document.addEventListener('click', function (event) {
    const openBtn = event.target.closest('[data-progress-open]');
    if (openBtn) {
      event.preventDefault();
      try {
        openDetail(JSON.parse(openBtn.getAttribute('data-progress-json') || '{}'));
      } catch (e) {
        if (window.AHPTC && window.AHPTC.toast) window.AHPTC.toast('Could not open progress details.', 'error');
      }
      return;
    }

    const photoBtn = event.target.closest('[data-progress-photo]');
    if (photoBtn) {
      event.preventDefault();
      openLightbox(photoBtn.getAttribute('data-photo-url') || '', photoBtn.getAttribute('data-photo-title') || 'Site photo');
      return;
    }

    const detailPhoto = event.target.closest('[data-detail-photo-open]');
    if (detailPhoto) {
      event.preventDefault();
      const wrap = event.target.closest('[data-detail-photo-wrap]');
      if (wrap && wrap.dataset.photoUrl) {
        openLightbox(wrap.dataset.photoUrl, wrap.dataset.photoTitle || 'Site evidence');
      }
      return;
    }

    if (event.target.closest('[data-progress-detail-close]') || event.target === detailModal) {
      closeDetail();
      return;
    }

    if (event.target.closest('[data-progress-lightbox-close]') || event.target === lightbox) {
      closeLightbox();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (lightbox && !lightbox.hidden) {
      closeLightbox();
      return;
    }
    if (detailModal && !detailModal.hidden) closeDetail();
  });
})();
