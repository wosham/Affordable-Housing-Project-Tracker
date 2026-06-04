(function () {
  const api = window.AHPTC || {};
  const request = api.request || ((url, options) => fetch(url, options));

  const modal = document.querySelector('[data-assignment-modal]');
  const bulkModal = document.querySelector('[data-assignment-bulk-modal]');
  const form = document.querySelector('[data-assignment-form]');
  const bulkForm = document.querySelector('[data-assignment-bulk-form]');

  function notify(message, type = 'success') {
    if (api.toast) {
      api.toast(message, type);
      return;
    }
    window.alert(message);
  }

  function openModal(target) {
    if (!target) return;
    target.hidden = false;
    target.classList.add('is-open');
  }

  function closeModals() {
    [modal, bulkModal].forEach((target) => {
      if (!target) return;
      target.hidden = true;
      target.classList.remove('is-open');
    });
  }

  function formJson(formEl) {
    const data = {};
    const fd = new FormData(formEl);
    fd.forEach((value, key) => {
      if (key.endsWith('[]')) {
        const clean = key.slice(0, -2);
        data[clean] = data[clean] || [];
        data[clean].push(value);
      } else {
        data[key] = value;
      }
    });
    data.is_primary = formEl.querySelector('[name="is_primary"]')?.checked ? 1 : 0;
    return data;
  }

  function setBusy(button, busy) {
    if (!button) return;
    button.disabled = busy;
    button.classList.toggle('is-loading', busy);
  }

  async function loadUsers(projectId, role, select, selectedId = '') {
    if (!select || !projectId) {
      if (select) select.innerHTML = '<option value="">Select project first</option>';
      return;
    }
    select.innerHTML = '<option value="">Loading users...</option>';
    const params = new URLSearchParams({ project_id: projectId });
    if (role) params.set('role', role);
    const json = await request(`api/assignments/available-users.php?${params.toString()}`);
    if (!json.success) {
      select.innerHTML = '<option value="">No users available</option>';
      return;
    }
    const options = ['<option value="">Choose user</option>'].concat((json.users || []).map((user) => {
      const selected = String(user.id) === String(selectedId) ? ' selected' : '';
      return `<option value="${user.id}"${selected}>${escapeHtml(user.name)} - ${escapeHtml(user.roleLabel)} (${escapeHtml(user.email)})</option>`;
    }));
    select.innerHTML = options.join('');
  }

  async function loadBulkUsers() {
    const project = bulkForm?.querySelector('[data-bulk-project]')?.value || '';
    const role = bulkForm?.querySelector('[data-bulk-role]')?.value || '';
    const list = bulkForm?.querySelector('[data-bulk-users]');
    if (!list || !project) {
      if (list) list.innerHTML = '<span class="text-muted">Choose a project to load users.</span>';
      return;
    }
    list.innerHTML = '<span class="text-muted">Loading users...</span>';
    const params = new URLSearchParams({ project_id: project });
    if (role) params.set('role', role);
    const json = await request(`api/assignments/available-users.php?${params.toString()}`);
    const users = json.users || [];
    if (!json.success || users.length === 0) {
      list.innerHTML = '<span class="text-muted">No available users for this selection.</span>';
      return;
    }
    list.innerHTML = users.map((user) => `
      <label class="assignment-user-option">
        <input type="checkbox" name="user_ids[]" value="${user.id}">
        <span><strong>${escapeHtml(user.name)}</strong><span>${escapeHtml(user.roleLabel)} - ${escapeHtml(user.email)}</span></span>
      </label>
    `).join('');
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
  }

  document.addEventListener('click', async (event) => {
    const createBtn = event.target.closest('[data-assignment-create]');
    if (createBtn && form) {
      form.reset();
      form.querySelector('[name="id"]').value = '';
      form.querySelector('[data-assignment-title]').textContent = 'New assignment';
      form.querySelector('[name="user_id"]').disabled = false;
      form.querySelector('[data-assignment-user]').innerHTML = '<option value="">Select project first</option>';
      openModal(modal);
    }

    const bulkBtn = event.target.closest('[data-assignment-bulk]');
    if (bulkBtn && bulkForm) {
      bulkForm.reset();
      const list = bulkForm.querySelector('[data-bulk-users]');
      if (list) list.innerHTML = '<span class="text-muted">Choose a project to load users.</span>';
      openModal(bulkModal);
    }

    const editBtn = event.target.closest('[data-assignment-edit]');
    if (editBtn && form) {
      const item = JSON.parse(editBtn.getAttribute('data-assignment-edit') || '{}');
      form.reset();
      form.querySelector('[data-assignment-title]').textContent = 'Edit assignment';
      form.querySelector('[name="id"]').value = item.id || '';
      form.querySelector('[name="project_id"]').value = item.project_id || '';
      form.querySelector('[name="role_filter"]').value = item.role_slug || '';
      form.querySelector('[name="assignment_type"]').value = item.assignment_type || 'site';
      form.querySelector('[name="scope"]').value = item.scope || 'general';
      form.querySelector('[name="status"]').value = item.status || 'active';
      form.querySelector('[name="start_date"]').value = item.start_date || '';
      form.querySelector('[name="end_date"]').value = item.end_date || '';
      form.querySelector('[name="notes"]').value = item.notes || '';
      form.querySelector('[name="is_primary"]').checked = !!item.is_primary;
      const userSelect = form.querySelector('[data-assignment-user]');
      userSelect.innerHTML = `<option value="${item.user_id}" selected>${escapeHtml(item.user_name)} - ${escapeHtml(item.role_name)} (${escapeHtml(item.email)})</option>`;
      form.querySelector('[name="user_id"]').disabled = true;
      openModal(modal);
    }

    const statusBtn = event.target.closest('[data-assignment-status]');
    if (statusBtn) {
      const action = statusBtn.getAttribute('data-assignment-status');
      const id = statusBtn.getAttribute('data-id');
      if (!window.confirm(action === 'reactivate' ? 'Reactivate this assignment?' : 'Revoke this assignment?')) return;
      setBusy(statusBtn, true);
      try {
        const json = await request('api/assignments/remove.php', {
          method: 'POST',
          body: { id, action },
        });
        if (!json.success) throw new Error(json.message || 'Assignment status could not be changed.');
        notify(json.message || 'Assignment updated.');
        window.location.reload();
      } catch (error) {
        notify(error.message, 'error');
      } finally {
        setBusy(statusBtn, false);
      }
    }

    if (event.target.closest('[data-assignment-close]') || event.target.classList.contains('assignment-modal')) {
      closeModals();
    }
  });

  form?.querySelector('[data-assignment-project]')?.addEventListener('change', () => {
    loadUsers(form.querySelector('[data-assignment-project]').value, form.querySelector('[data-assignment-role]').value, form.querySelector('[data-assignment-user]'));
  });
  form?.querySelector('[data-assignment-role]')?.addEventListener('change', () => {
    loadUsers(form.querySelector('[data-assignment-project]').value, form.querySelector('[data-assignment-role]').value, form.querySelector('[data-assignment-user]'));
  });
  bulkForm?.querySelector('[data-bulk-project]')?.addEventListener('change', loadBulkUsers);
  bulkForm?.querySelector('[data-bulk-role]')?.addEventListener('change', loadBulkUsers);

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    const data = formJson(form);
    const isEdit = !!data.id;
    if (isEdit) delete data.user_id;
    setBusy(button, true);
    try {
      const json = await request(isEdit ? 'api/assignments/update.php' : 'api/assignments/create.php', {
        method: 'POST',
        body: data,
      });
      if (!json.success) throw new Error(json.message || 'Assignment could not be saved.');
      notify(json.message || 'Assignment saved.');
      window.location.reload();
    } catch (error) {
      notify(error.message, 'error');
    } finally {
      setBusy(button, false);
    }
  });

  bulkForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = bulkForm.querySelector('[type="submit"]');
    const data = formJson(bulkForm);
    data.user_ids = Array.from(bulkForm.querySelectorAll('[name="user_ids[]"]:checked')).map((input) => input.value);
    setBusy(button, true);
    try {
      const json = await request('api/assignments/bulk-assign.php', {
        method: 'POST',
        body: data,
      });
      if (!json.success) throw new Error(json.message || 'Bulk assignment failed.');
      notify(json.message || 'Bulk assignment complete.');
      window.location.reload();
    } catch (error) {
      notify(error.message, 'error');
    } finally {
      setBusy(button, false);
    }
  });
})();
