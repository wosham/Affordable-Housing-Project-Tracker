(function () {
  const drawer = document.querySelector('[data-project-drawer]');
  const titleEl = document.querySelector('[data-project-title]');
  const metaEl = document.querySelector('[data-project-meta]');
  const bodyEl = document.querySelector('[data-project-detail-body]');
  const request = window.AHPTC && window.AHPTC.request ? window.AHPTC.request : null;

  if (!drawer || !request) return;

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
  }

  function openDrawer() {
    drawer.hidden = false;
  }

  function closeDrawer() {
    drawer.hidden = true;
  }

  function list(items, empty, render) {
    if (!items || items.length === 0) {
      return `<div class="empty-state empty-state--compact"><strong class="empty-state__title">${escapeHtml(empty)}</strong></div>`;
    }
    return `<div class="manager-project-list">${items.map(render).join('')}</div>`;
  }

  function renderDetail(detail) {
    const project = detail.project;
    titleEl.textContent = project.name || 'Project';
    metaEl.textContent = `${project.constituency_name || 'No constituency'} / ${project.ward_name || project.location_label || 'No ward'} / ${project.risk_label || 'Normal'} risk`;

    bodyEl.innerHTML = `
      <div class="manager-project-detail-grid">
        <div class="manager-project-detail-card"><strong>${project.progress}%</strong><span>Progress</span></div>
        <div class="manager-project-detail-card"><strong>${escapeHtml(detail.boq.contractValue)}</strong><span>BOQ value</span></div>
        <div class="manager-project-detail-card"><strong>${detail.attendance.present}</strong><span>Present today</span></div>
        <div class="manager-project-detail-card"><strong>${detail.programme.overdue}</strong><span>Overdue tasks</span></div>
        <div class="manager-project-detail-card"><strong>${project.open_ipcs}</strong><span>Open IPCs</span></div>
        <div class="manager-project-detail-card"><strong>${project.clerk_count}/${project.intern_count}</strong><span>Clerks / interns</span></div>
      </div>

      <section class="manager-project-section">
        <h3>Team Coverage</h3>
        ${list(detail.team, 'No assigned team yet', (item) => `
          <div class="manager-project-list__item"><span><strong>${escapeHtml(item.user_name)}</strong><small>${escapeHtml(item.email)}</small></span><span>${escapeHtml(item.role_name)}</span></div>
        `)}
      </section>

      <section class="manager-project-section">
        <h3>Milestones</h3>
        ${list(detail.milestones, 'No milestones found', (item) => `
          <div class="manager-project-list__item"><span><strong>${escapeHtml(item.label)}</strong><small>${escapeHtml(item.targetLabel)}</small></span><span>${escapeHtml(item.statusLabel)}</span></div>
        `)}
      </section>

      <section class="manager-project-section">
        <h3>IPC Queue</h3>
        ${list(detail.ipcs, 'No IPCs found', (item) => `
          <div class="manager-project-list__item"><span><strong>IPC #${item.number}</strong><small>${escapeHtml(item.submittedAt)}</small></span><span>${escapeHtml(item.amount)} / ${escapeHtml(item.statusLabel)}</span></div>
        `)}
      </section>

      <section class="manager-project-section">
        <h3>Risk Feed</h3>
        ${list(detail.risks, 'No recent risks', (item) => `
          <div class="manager-project-list__item"><span><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.type)} / ${escapeHtml(item.createdAt)}</small></span><span>${escapeHtml(item.status)}</span></div>
        `)}
      </section>

      <section class="manager-project-section">
        <h3>Manager Notes</h3>
        ${list(detail.notes, 'No manager notes yet', (item) => `
          <div class="manager-project-list__item"><span><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.manager)} / ${escapeHtml(item.createdAt)}</small></span><span>${escapeHtml(item.severity)}</span></div>
        `)}
        <form class="manager-project-note-form" data-project-note-form>
          <input type="hidden" name="project_id" value="${project.id}">
          <label class="form-field"><span class="form-label">Type</span><select class="form-select" name="note_type"><option value="monitoring">Monitoring</option><option value="risk">Risk</option><option value="delivery">Delivery</option><option value="attendance">Attendance</option><option value="finance">Finance</option></select></label>
          <label class="form-field"><span class="form-label">Severity</span><select class="form-select" name="severity"><option value="normal">Normal</option><option value="warning">Warning</option><option value="critical">Critical</option></select></label>
          <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><option value="open">Open</option><option value="watching">Watching</option><option value="closed">Closed</option></select></label>
          <label class="form-field form-field--wide"><span class="form-label">Title</span><input class="form-input" name="title" required maxlength="180"></label>
          <label class="form-field form-field--wide"><span class="form-label">Note</span><textarea class="form-textarea" name="body" rows="3"></textarea></label>
          <div class="form-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Note</button></div>
        </form>
      </section>
    `;
  }

  async function loadDetail(projectId) {
    openDrawer();
    titleEl.textContent = 'Loading project';
    metaEl.textContent = '';
    bodyEl.innerHTML = '<div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i></span><strong class="empty-state__title">Loading project</strong></div>';
    try {
      const data = await request('api/manager/project-detail.php?project_id=' + encodeURIComponent(projectId));
      if (!data.success) throw new Error(data.message || 'Project could not be loaded.');
      renderDetail(data.detail);
    } catch (error) {
      bodyEl.innerHTML = `<div class="empty-state"><strong class="empty-state__title">${escapeHtml(error.message || 'Project could not be loaded.')}</strong></div>`;
    }
  }

  document.addEventListener('click', (event) => {
    const detailButton = event.target.closest('[data-project-detail]');
    if (detailButton) {
      loadDetail(detailButton.getAttribute('data-project-detail'));
    }
    if (event.target.closest('[data-project-close]') || event.target === drawer) {
      closeDrawer();
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-project-note-form]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    button.disabled = true;
    const payload = Object.fromEntries(new FormData(form).entries());
    try {
      const data = await request('api/manager/project-note.php', { method: 'POST', body: payload });
      if (!data.success) throw new Error(data.message || 'Note could not be saved.');
      loadDetail(payload.project_id);
    } catch (error) {
      window.alert(error.message || 'Note could not be saved.');
      button.disabled = false;
    }
  });
})();
