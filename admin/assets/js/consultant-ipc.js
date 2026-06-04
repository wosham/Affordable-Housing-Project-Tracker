(function () {
  'use strict';

  var admin = window.AHPTC || {};

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function request(url, body) {
    if (admin.request) {
      return admin.request(url, { method: 'POST', body: body });
    }

    return fetch(toUrl(url), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (response) {
      return response.json();
    });
  }

  function toUrl(url) {
    if (/^(https?:)?\/\//i.test(url) || url.charAt(0) === '/') return url;
    if (admin.baseUrl) return admin.baseUrl() + '/' + url.replace(/^\/+/, '');
    return '../../' + url.replace(/^\/+/, '');
  }

  function notify(message, type) {
    if (admin.toast) {
      admin.toast(message, type || 'info');
      return;
    }
    window.alert(message);
  }

  function setBusy(button, busy) {
    if (!button) return;
    button.disabled = !!busy;
    button.classList.toggle('is-loading', !!busy);
  }

  function openReject(button) {
    var modal = qs('[data-consultant-ipc-modal]');
    if (!modal) return;
    var id = button.getAttribute('data-ipc-id') || '';
    var label = button.getAttribute('data-ipc-label') || 'this IPC';
    var input = qs('[data-consultant-ipc-reject-id]', modal);
    var copy = qs('[data-consultant-ipc-reject-label]', modal);
    var reason = qs('textarea[name="reason"]', modal);
    if (input) input.value = id;
    if (copy) copy.textContent = 'Record why ' + label + ' is being returned.';
    if (reason) reason.value = '';
    modal.hidden = false;
    document.body.classList.add('modal-open');
    if (reason) reason.focus();
  }

  function closeReject() {
    var modal = qs('[data-consultant-ipc-modal]');
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  }

  qsa('[data-consultant-ipc-reject]').forEach(function (button) {
    button.addEventListener('click', function () {
      openReject(button);
    });
  });

  qsa('[data-consultant-ipc-close]').forEach(function (button) {
    button.addEventListener('click', closeReject);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeReject();
  });

  var modal = qs('[data-consultant-ipc-modal]');
  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeReject();
    });
  }

  var rejectForm = qs('[data-consultant-ipc-reject-form]');
  if (rejectForm) {
    rejectForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var submit = qs('button[type="submit"]', rejectForm);
      var ipcId = qs('[data-consultant-ipc-reject-id]', rejectForm);
      var reason = qs('textarea[name="reason"]', rejectForm);
      var body = {
        ipc_id: ipcId ? ipcId.value : '',
        reason: reason ? reason.value.trim() : ''
      };
      if (!body.reason) {
        notify('A rejection reason is required.', 'error');
        return;
      }

      setBusy(submit, true);
      request('api/ipcs/reject.php', body).then(function (data) {
        notify(data.message || 'IPC rejected successfully.', 'success');
        window.setTimeout(function () {
          window.location.href = (admin.baseUrl ? admin.baseUrl() + '/admin/consultant/ipc-inbox.php' : 'ipc-inbox.php');
        }, 600);
      }).catch(function (error) {
        notify(error.message || 'Rejection could not be completed.', 'error');
      }).finally(function () {
        setBusy(submit, false);
      });
    });
  }

  var certifyForm = qs('[data-consultant-ipc-certify-form]');
  if (certifyForm) {
    certifyForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var submit = qs('button[type="submit"]', certifyForm);
      var checked = qsa('input[name="checklist[]"]:checked', certifyForm);
      var required = qsa('input[name="checklist[]"]', certifyForm);
      if (checked.length < required.length) {
        notify('Complete the certification checklist first.', 'error');
        return;
      }

      if (!window.confirm('Certify this IPC and send it to the manager?')) return;

      var ipcId = qs('input[name="ipc_id"]', certifyForm);
      var comment = qs('textarea[name="comment"]', certifyForm);
      setBusy(submit, true);
      request('api/ipcs/certify.php', {
        ipc_id: ipcId ? ipcId.value : '',
        comment: comment ? comment.value.trim() : '',
        checklist: checked.map(function (item) { return item.value; })
      }).then(function (data) {
        notify(data.message || 'IPC certified successfully.', 'success');
        window.setTimeout(function () {
          window.location.href = (admin.baseUrl ? admin.baseUrl() + '/admin/consultant/ipc-inbox.php' : 'ipc-inbox.php');
        }, 700);
      }).catch(function (error) {
        notify(error.message || 'Certification could not be completed.', 'error');
      }).finally(function () {
        setBusy(submit, false);
      });
    });
  }

  qsa('[data-consultant-ipc-print]').forEach(function (button) {
    button.addEventListener('click', function () {
      window.print();
    });
  });
})();
