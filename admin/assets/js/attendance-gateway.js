(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }
    return fetch(url, options).then(function (response) {
      return response.json();
    });
  }

  function setStatus(root, message, state) {
    var node = qs('[data-gateway-status]', root);
    if (!node) return;
    node.textContent = message || '';
    node.dataset.state = state || '';
  }

  function projectId(root) {
    var field = qs('[name="project_id"]', root);
    return field ? field.value : root.getAttribute('data-project-id') || '';
  }

  function refresh(root) {
    var id = projectId(root);
    var url = 'api/attendance/gateway-status.php' + (id ? '?project_id=' + encodeURIComponent(id) : '');

    return request(url).then(function (result) {
      if (!result.success) throw new Error(result.message || 'Gateway status could not be loaded.');
      var label = result.is_open ? 'Attendance gateway is open.' : 'Attendance gateway is closed.';
      setStatus(root, label, result.is_open ? 'open' : 'closed');
      root.dispatchEvent(new CustomEvent('attendance:gateway-status', { detail: result, bubbles: true }));
      return result;
    }).catch(function (error) {
      setStatus(root, error.message || 'Gateway status could not be loaded.', 'error');
    });
  }

  function submitAction(root, endpoint, button) {
    var form = qs('form', root);
    var data = new FormData(form || undefined);
    if (!data.get('project_id') && projectId(root)) data.set('project_id', projectId(root));

    if (button) button.disabled = true;
    setStatus(root, 'Updating attendance gateway...', 'loading');

    request(endpoint, { method: 'POST', body: data }).then(function (result) {
      if (!result.success) throw new Error(result.message || 'Gateway update failed.');
      setStatus(root, result.message || 'Gateway updated.', 'success');
      return refresh(root);
    }).catch(function (error) {
      setStatus(root, error.message || 'Gateway update failed.', 'error');
    }).finally(function () {
      if (button) button.disabled = false;
    });
  }

  function initGateway(root) {
    if (root.dataset.gatewayReady === '1') return;
    root.dataset.gatewayReady = '1';

    qsa('[data-gateway-open]', root).forEach(function (button) {
      button.addEventListener('click', function () {
        submitAction(root, button.getAttribute('data-gateway-open') || 'api/attendance/gateway-open.php', button);
      });
    });

    qsa('[data-gateway-close]', root).forEach(function (button) {
      button.addEventListener('click', function () {
        submitAction(root, button.getAttribute('data-gateway-close') || 'api/attendance/gateway-close.php', button);
      });
    });

    qsa('[data-gateway-refresh]', root).forEach(function (button) {
      button.addEventListener('click', function () {
        refresh(root);
      });
    });

    refresh(root);
  }

  function init() {
    qsa('[data-attendance-gateway]').forEach(initGateway);
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, {
    initAttendanceGateway: init
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
