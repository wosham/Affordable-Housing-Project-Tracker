(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function request(url, options) {
    if (window.AHPTC && window.AHPTC.request) {
      return window.AHPTC.request(url, options);
    }
    return fetch(url, options).then(function (response) { return response.json(); });
  }

  function projectId(root) {
    var field = qs('[name="project_id"]', root);
    return field ? field.value : root.getAttribute('data-project-id') || '';
  }

  function gatewayDate(root) {
    var field = qs('[name="date"]', root);
    return field ? field.value : root.getAttribute('data-gateway-date') || '';
  }

  function setStatus(root, message, state) {
    var node = qs('[data-gateway-status]', root);
    if (!node) return;
    node.textContent = message || '';
    node.dataset.state = state || '';
    node.classList.toggle('is-open', state === 'open' || state === 'success');
    node.classList.toggle('is-closed', state === 'closed' || state === 'error');
  }

  function statusLabel(status) {
    return String(status || '-').replace(/[-_]+/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
  }

  function roleLabel(role) {
    return statusLabel(role || 'staff');
  }

  function time(value) {
    if (!value) return '-';
    var parts = String(value).split(/[ T]/);
    return (parts[1] || parts[0] || '-').slice(0, 5);
  }

  function badgeClass(status) {
    if (status === 'present' || status === 'accepted') return 'badge--success';
    if (status === 'late' || status === 'pending') return 'badge--warning';
    if (status === 'geo-fail' || status === 'outside-window' || status === 'flagged') return 'badge--danger';
    return 'badge--muted';
  }

  function statusUrl(root) {
    var params = new URLSearchParams();
    if (projectId(root)) params.set('project_id', projectId(root));
    if (gatewayDate(root)) params.set('date', gatewayDate(root));
    var base = root.getAttribute('data-status-url') || 'api/attendance/gateway-status.php';
    return base + (base.indexOf('?') === -1 ? '?' : '&') + params.toString();
  }

  function renderSummary(data) {
    var root = qs('[data-live-summary]');
    if (!root || !data.summary) return;
    var stats = [
      ['fa-user-check', data.summary.signed_in, 'Signed In', 'Today'],
      ['fa-circle-check', data.summary.present, 'Present', 'Verified by location/time'],
      ['fa-user-clock', data.summary.late, 'Late', 'After expected time'],
      ['fa-location-crosshairs', data.summary.flagged, 'Flags', 'Needs review'],
      ['fa-user-minus', data.summary.missing, 'Pending', 'No sign-in yet'],
      ['fa-chart-simple', String(data.summary.attendance_percent || 0) + '%', 'Coverage', 'Completion']
    ];
    root.innerHTML = stats.map(function (item) {
      return '<article class="clerk-stat card"><span><i class="fa-solid ' + esc(item[0]) + '"></i></span><div><strong>' + esc(item[1]) + '</strong><small>' + esc(item[2]) + '</small><em>' + esc(item[3]) + '</em></div></article>';
    }).join('');
  }

  function renderRecords(data) {
    var body = qs('[data-live-records]');
    var count = qs('[data-live-count]');
    if (!body) return;
    var records = data.records || [];
    if (count) count.textContent = records.length + ' records';
    if (!records.length) {
      body.innerHTML = '<tr><td colspan="6"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No attendance records found</strong><span class="empty-state__text">Records will appear when assigned personnel sign in.</span></div></td></tr>';
      return;
    }
    body.innerHTML = records.map(function (record) {
      var location = record.distance_from_site_m == null ? '-' : Number(record.distance_from_site_m).toFixed(1) + ' m';
      var accuracy = record.accuracy_meters == null ? '' : 'Accuracy ' + Number(record.accuracy_meters).toFixed(1) + ' m';
      return '<tr>' +
        '<td><strong>' + esc(record.user_name || '-') + '</strong><small>' + esc(record.email || '') + '</small></td>' +
        '<td><strong>' + esc(record.project_name || '-') + '</strong><small>' + esc([record.constituency_name, record.ward_name].filter(Boolean).join(' / ')) + '</small></td>' +
        '<td>' + esc(roleLabel(record.role_slug)) + '</td>' +
        '<td>' + esc(time(record.signin_time)) + '</td>' +
        '<td><strong>' + esc(location) + '</strong><small>' + esc(accuracy) + '</small></td>' +
        '<td><span class="badge ' + esc(badgeClass(record.status)) + '">' + esc(statusLabel(record.status)) + '</span><small>' + esc(statusLabel(record.review_status || 'pending')) + '</small></td>' +
        '</tr>';
    }).join('');
  }

  function renderExpected(data) {
    var node = qs('[data-expected-list]');
    if (!node || !data.expected) return;
    var pending = data.expected.filter(function (person) { return !person.attendance_id; });
    if (node.tagName === 'TBODY') {
      if (!data.expected.length) {
        node.innerHTML = '<tr><td colspan="4"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No assigned personnel</strong><span class="empty-state__text">Assigned team members will appear here.</span></div></td></tr>';
        return;
      }
      node.innerHTML = data.expected.map(function (person) {
        return '<tr>' +
          '<td><strong>' + esc(person.user_name || '-') + '</strong></td>' +
          '<td>' + esc(roleLabel(person.role_slug)) + '</td>' +
          '<td><small>' + esc(person.email || '') + '</small><small>' + esc(person.phone || '') + '</small></td>' +
          '<td><span class="badge ' + esc(person.attendance_id ? 'badge--success' : 'badge--warning') + '">' + esc(person.attendance_id ? 'Signed in' : 'Pending') + '</span></td>' +
          '</tr>';
      }).join('');
      return;
    }
    if (!pending.length) {
      node.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No pending people</strong><span class="empty-state__text">Everyone assigned to this site has a record.</span></div>';
      return;
    }
    node.innerHTML = pending.map(function (person) {
      return '<span><strong>' + esc(person.user_name) + '</strong><small>' + esc(roleLabel(person.role_slug)) + ' / ' + esc(person.email || '') + '</small></span>';
    }).join('');
  }

  function refresh(root) {
    return request(statusUrl(root)).then(function (data) {
      if (!data.success) throw new Error(data.message || 'Gateway status could not be loaded.');
      var msg = data.is_open
        ? (data.clerk_confirmed ? 'Policy window open · site confirmed by clerk.' : 'Policy window open · confirm site open when ready.')
        : 'Attendance gateway is closed.';
      if (data.policy && data.policy.is_before_open) msg = 'Before open (' + (data.policy.open_time || '') + ').';
      if (data.policy && data.policy.is_after_close) msg = 'Closed for today after ' + (data.policy.close_time || '') + '.';
      setStatus(root, msg, data.is_open ? 'open' : 'closed');
      var policyLabel = qs('[data-policy-label]');
      if (policyLabel && data.policy && data.policy.label) policyLabel.textContent = data.policy.label;
      renderSummary(data);
      renderRecords(data);
      renderExpected(data);
      return data;
    }).catch(function (error) {
      setStatus(root, error.message || 'Gateway status could not be loaded.', 'error');
    });
  }

  function submitAction(root, endpoint, button, confirmText) {
    if (confirmText && !window.confirm(confirmText)) return;
    var form = qs('[data-gateway-form]', document) || qs('form', root);
    var data = new FormData(form || undefined);
    if (!data.get('project_id') && projectId(root)) data.set('project_id', projectId(root));
    if (!data.get('date') && gatewayDate(root)) data.set('date', gatewayDate(root));
    // Clerks never send custom close times — policy owns the window.
    data.delete('closes_at');

    var old = button ? button.innerHTML : '';
    if (button) {
      button.disabled = true;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Working';
    }
    setStatus(root, 'Updating attendance gateway...', 'loading');
    request(endpoint, { method: 'POST', body: data }).then(function (data) {
      if (!data.success) throw new Error(data.message || 'Gateway update failed.');
      setStatus(root, data.message || 'Gateway updated.', 'success');
      if (window.AHPTC && window.AHPTC.toast) window.AHPTC.toast(data.message || 'Gateway updated.', 'success');
      window.setTimeout(function () { window.location.reload(); }, 600);
    }).catch(function (error) {
      setStatus(root, error.message || 'Gateway update failed.', 'error');
      if (window.AHPTC && window.AHPTC.toast) window.AHPTC.toast(error.message || 'Gateway update failed.', 'error');
    }).finally(function () {
      if (button) {
        button.disabled = false;
        button.innerHTML = old;
      }
    });
  }

  function initClerkSelfSignIn() {
    var form = qs('[data-clerk-self-signin]');
    if (!form || form.dataset.ready === '1') return;
    form.dataset.ready = '1';
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var status = qs('[data-clerk-signin-status]', form);
      var button = qs('[type="submit"]', form);
      var old = button ? button.innerHTML : '';
      if (!navigator.geolocation) {
        if (status) status.textContent = 'GPS is required for sign-in.';
        return;
      }
      if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating';
      }
      if (status) status.textContent = 'Getting your location...';
      navigator.geolocation.getCurrentPosition(function (pos) {
        var body = {
          target_type: (qs('[name="target_type"]', form) || {}).value || 'project',
          target_id: (qs('[name="target_id"]', form) || {}).value || '',
          project_id: (qs('[name="project_id"]', form) || {}).value || '',
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
          accuracy_meters: pos.coords.accuracy
        };
        if (button) button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in';
        // Temporarily switch CSRF form for sign-in endpoint
        var meta = document.querySelector('meta[name="csrf-form"]');
        var previous = meta ? meta.getAttribute('content') : '';
        if (meta) meta.setAttribute('content', form.getAttribute('data-csrf-form') || 'attendance_signin');
        request(form.getAttribute('action') || 'api/attendance/sign-in.php', { method: 'POST', body: body }).then(function (data) {
          if (!data.success && !data.already_signed && !data.attendance) throw new Error(data.message || 'Sign-in failed.');
          if (status) status.textContent = data.message || 'Signed in.';
          if (window.AHPTC && window.AHPTC.toast) window.AHPTC.toast(data.message || 'Signed in.', 'success');
          window.setTimeout(function () { window.location.reload(); }, 700);
        }).catch(function (error) {
          if (status) status.textContent = error.message || 'Sign-in failed.';
          if (window.AHPTC && window.AHPTC.toast) window.AHPTC.toast(error.message || 'Sign-in failed.', 'error');
        }).finally(function () {
          if (meta) meta.setAttribute('content', previous || 'attendance_gateway');
          if (button) {
            button.disabled = false;
            button.innerHTML = old;
          }
        });
      }, function () {
        if (status) status.textContent = 'Could not read GPS location. Allow location access and try again.';
        if (button) {
          button.disabled = false;
          button.innerHTML = old;
        }
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
  }

  function initGateway(root) {
    if (root.dataset.gatewayReady === '1') return;
    root.dataset.gatewayReady = '1';
    qsa('[data-gateway-open]').forEach(function (button) {
      button.addEventListener('click', function () {
        submitAction(root, button.getAttribute('data-gateway-open') || 'api/attendance/gateway-open.php', button, 'Confirm that this site is open and manned for attendance today?');
      });
    });
    qsa('[data-gateway-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        submitAction(root, button.getAttribute('data-gateway-close') || 'api/attendance/gateway-close.php', button, 'Emergency close attendance for this site? Interns will not be able to sign in until policy re-opens tomorrow.');
      });
    });
    qsa('[data-gateway-refresh], [data-live-refresh]').forEach(function (button) {
      button.addEventListener('click', function () { refresh(root); });
    });
    refresh(root);
  }

  function init() {
    qsa('[data-attendance-gateway], [data-attendance-live]').forEach(function (root) {
      initGateway(root);
      if (root.hasAttribute('data-attendance-live')) {
        window.setInterval(function () { refresh(root); }, 30000);
      }
    });
    initClerkSelfSignIn();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
