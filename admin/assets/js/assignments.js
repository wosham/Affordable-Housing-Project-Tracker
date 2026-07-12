(function () {
  'use strict';

  const api = window.AHPTC || null;
  if (!api || typeof api.request !== 'function') {
    console.error('AHPTC.request is required for manager assignments.');
    return;
  }
  const request = api.request.bind(api);
  const csrfHeaders = {
    'X-CSRF-Form': (api.csrfForm && api.csrfForm()) || 'assignments'
  };

  const modal = document.querySelector('[data-assignment-modal]');
  const bulkModal = document.querySelector('[data-assignment-bulk-modal]');
  const form = document.querySelector('[data-assignment-form]');
  const bulkForm = document.querySelector('[data-assignment-bulk-form]');

  function notify(message, type) {
    type = type || 'success';
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
    [modal, bulkModal].forEach(function (target) {
      if (!target) return;
      target.hidden = true;
      target.classList.remove('is-open');
    });
  }

  function formJson(formEl) {
    const data = {};
    const fd = new FormData(formEl);
    fd.forEach(function (value, key) {
      if (key.endsWith('[]')) {
        const clean = key.slice(0, -2);
        data[clean] = data[clean] || [];
        data[clean].push(value);
      } else {
        data[key] = value;
      }
    });
    data.is_primary = formEl.querySelector('[name="is_primary"]') && formEl.querySelector('[name="is_primary"]').checked ? 1 : 0;
    return data;
  }

  function setBusy(button, busy) {
    if (!button) return;
    button.disabled = !!busy;
    button.classList.toggle('is-loading', !!busy);
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  async function loadUsers(projectId, role, select, selectedId) {
    selectedId = selectedId || '';
    if (!select || !projectId) {
      if (select) select.innerHTML = '<option value="">Select project first</option>';
      return;
    }
    select.innerHTML = '<option value="">Loading users...</option>';
    try {
      const params = new URLSearchParams({ project_id: projectId });
      if (role) params.set('role', role);
      const json = await request('api/assignments/available-users.php?' + params.toString());
      if (!json.success) {
        select.innerHTML = '<option value="">No users available</option>';
        return;
      }
      const options = ['<option value="">Choose user</option>'].concat((json.users || []).map(function (user) {
        const selected = String(user.id) === String(selectedId) ? ' selected' : '';
        return '<option value="' + user.id + '"' + selected + '>' + escapeHtml(user.name) + ' - ' + escapeHtml(user.roleLabel) + ' (' + escapeHtml(user.email) + ')</option>';
      }));
      select.innerHTML = options.join('');
    } catch (error) {
      select.innerHTML = '<option value="">Could not load users</option>';
      notify(error.message || 'Could not load users.', 'error');
    }
  }

  async function loadBulkUsers() {
    const project = bulkForm && bulkForm.querySelector('[data-bulk-project]') ? bulkForm.querySelector('[data-bulk-project]').value : '';
    const role = bulkForm && bulkForm.querySelector('[data-bulk-role]') ? bulkForm.querySelector('[data-bulk-role]').value : '';
    const list = bulkForm ? bulkForm.querySelector('[data-bulk-users]') : null;
    if (!list || !project) {
      if (list) list.innerHTML = '<span class="text-muted">Choose a project to load users.</span>';
      return;
    }
    list.innerHTML = '<span class="text-muted">Loading users...</span>';
    try {
      const params = new URLSearchParams({ project_id: project });
      if (role) params.set('role', role);
      const json = await request('api/assignments/available-users.php?' + params.toString());
      const users = json.users || [];
      if (!json.success || users.length === 0) {
        list.innerHTML = '<span class="text-muted">No available users for this selection.</span>';
        return;
      }
      list.innerHTML = users.map(function (user) {
        return ''
          + '<label class="assignment-user-option">'
          + '<input type="checkbox" name="user_ids[]" value="' + user.id + '">'
          + '<span><strong>' + escapeHtml(user.name) + '</strong><span>' + escapeHtml(user.roleLabel) + ' - ' + escapeHtml(user.email) + '</span></span>'
          + '</label>';
      }).join('');
    } catch (error) {
      list.innerHTML = '<span class="text-muted">Could not load users.</span>';
      notify(error.message || 'Could not load users.', 'error');
    }
  }

  document.addEventListener('click', async function (event) {
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
      form.reset();
      form.querySelector('[data-assignment-title]').textContent = 'Edit assignment';
      form.querySelector('[name="id"]').value = editBtn.getAttribute('data-id') || '';
      form.querySelector('[name="project_id"]').value = editBtn.getAttribute('data-project-id') || '';
      form.querySelector('[name="role_filter"]').value = editBtn.getAttribute('data-role-slug') || '';
      form.querySelector('[name="assignment_type"]').value = editBtn.getAttribute('data-assignment-type') || 'site';
      form.querySelector('[name="scope"]').value = editBtn.getAttribute('data-scope') || 'general';
      form.querySelector('[name="status"]').value = editBtn.getAttribute('data-status') || 'active';
      form.querySelector('[name="start_date"]').value = editBtn.getAttribute('data-start-date') || '';
      form.querySelector('[name="end_date"]').value = editBtn.getAttribute('data-end-date') || '';
      form.querySelector('[name="notes"]').value = editBtn.getAttribute('data-notes') || '';
      form.querySelector('[name="is_primary"]').checked = editBtn.getAttribute('data-is-primary') === '1';
      const userId = editBtn.getAttribute('data-user-id') || '';
      const userName = editBtn.getAttribute('data-user-name') || 'Assigned user';
      const roleName = editBtn.getAttribute('data-role-name') || '';
      const email = editBtn.getAttribute('data-email') || '';
      const userSelect = form.querySelector('[data-assignment-user]');
      userSelect.innerHTML = '<option value="' + escapeHtml(userId) + '" selected>'
        + escapeHtml(userName) + ' - ' + escapeHtml(roleName) + ' (' + escapeHtml(email) + ')</option>';
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
          body: { id: id, action: action },
          headers: csrfHeaders
        });
        if (!json.success) throw new Error(json.message || 'Assignment status could not be changed.');
        notify(json.message || 'Assignment updated.');
        window.location.reload();
      } catch (error) {
        notify(error.message || 'Assignment status could not be changed.', 'error');
      } finally {
        setBusy(statusBtn, false);
      }
    }

    if (event.target.closest('[data-assignment-close]') || event.target.classList.contains('assignment-modal')) {
      closeModals();
    }
  });

  if (form) {
    const projectSelect = form.querySelector('[data-assignment-project]');
    const roleSelect = form.querySelector('[data-assignment-role]');
    const userSelect = form.querySelector('[data-assignment-user]');
    if (projectSelect) {
      projectSelect.addEventListener('change', function () {
        if (form.querySelector('[name="id"]').value) return;
        loadUsers(projectSelect.value, roleSelect ? roleSelect.value : '', userSelect);
      });
    }
    if (roleSelect) {
      roleSelect.addEventListener('change', function () {
        if (form.querySelector('[name="id"]').value) return;
        loadUsers(projectSelect ? projectSelect.value : '', roleSelect.value, userSelect);
      });
    }
    form.addEventListener('submit', async function (event) {
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
          headers: csrfHeaders
        });
        if (!json.success) throw new Error(json.message || 'Assignment could not be saved.');
        notify(json.message || 'Assignment saved.');
        window.location.reload();
      } catch (error) {
        notify(error.message || 'Assignment could not be saved.', 'error');
      } finally {
        setBusy(button, false);
      }
    });
  }

  if (bulkForm) {
    const bulkProject = bulkForm.querySelector('[data-bulk-project]');
    const bulkRole = bulkForm.querySelector('[data-bulk-role]');
    if (bulkProject) bulkProject.addEventListener('change', loadBulkUsers);
    if (bulkRole) bulkRole.addEventListener('change', loadBulkUsers);
    bulkForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      const button = bulkForm.querySelector('[type="submit"]');
      const data = formJson(bulkForm);
      data.user_ids = Array.prototype.slice.call(bulkForm.querySelectorAll('[name="user_ids[]"]:checked')).map(function (input) {
        return input.value;
      });
      if (!data.user_ids.length) {
        notify('Select at least one user for bulk assignment.', 'error');
        return;
      }
      setBusy(button, true);
      try {
        const json = await request('api/assignments/bulk-assign.php', {
          method: 'POST',
          body: data,
          headers: csrfHeaders
        });
        if (!json.success) throw new Error(json.message || 'Bulk assignment failed.');
        notify(json.message || 'Bulk assignment complete.');
        window.location.reload();
      } catch (error) {
        notify(error.message || 'Bulk assignment failed.', 'error');
      } finally {
        setBusy(button, false);
      }
    });
  }
}());
