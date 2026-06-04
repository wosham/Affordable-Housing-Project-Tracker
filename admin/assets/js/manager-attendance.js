(function () {
  'use strict';

  const panel = document.querySelector('[data-attendance-detail]');
  const request = window.AHPTC && window.AHPTC.request ? window.AHPTC.request : null;
  if (!panel || !request) return;

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function number(value) {
    return Number(value || 0).toLocaleString();
  }

  function closePanel() {
    panel.hidden = true;
  }

  function metric(label, value) {
    return '<span><small>' + escapeHtml(label) + '</small><strong>' + escapeHtml(value) + '</strong></span>';
  }

  function renderRecords(records) {
    const node = qs('[data-attendance-records]', panel);
    if (!node) return;
    if (!records || !records.length) {
      node.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No signed-in staff</strong></div>';
      return;
    }

    node.innerHTML = records.map(function (record) {
      return '<span class="manager-attendance-mini-item"><strong>' + escapeHtml(record.user_name || 'Staff') +
        '</strong><small>' + escapeHtml(record.role_name || record.role_slug || '') +
        ' / ' + escapeHtml(record.signin_time || '-') +
        ' / ' + escapeHtml(record.status || '') + '</small></span>';
    }).join('');
  }

  function renderExceptions(data) {
    const node = qs('[data-attendance-exceptions]', panel);
    if (!node) return;
    const items = []
      .concat(data.exceptions && data.exceptions.missing ? data.exceptions.missing : [])
      .concat(data.exceptions && data.exceptions.gps ? data.exceptions.gps : [])
      .concat(data.exceptions && data.exceptions.gateways ? data.exceptions.gateways : [])
      .slice(0, 12);

    if (!items.length) {
      node.innerHTML = '<div class="empty-state empty-state--compact"><strong class="empty-state__title">No exceptions for this project</strong></div>';
      return;
    }

    node.innerHTML = items.map(function (item) {
      return '<span class="manager-attendance-mini-item"><strong>' + escapeHtml(item.user_name || item.project_name || 'Record') +
        '</strong><small>' + escapeHtml(item.email || item.status || item.opened_by_name || item.constituency_name || 'Needs review') +
        '</small></span>';
    }).join('');
  }

  async function openProject(button) {
    const projectId = button.getAttribute('data-attendance-project') || '';
    const date = button.getAttribute('data-attendance-date') || '';
    button.disabled = true;

    try {
      const url = 'api/attendance/daily-report.php?project_id=' + encodeURIComponent(projectId) + '&date=' + encodeURIComponent(date) + '&limit=100';
      const data = await request(url);
      if (!data.success) throw new Error(data.message || 'Attendance details could not be loaded.');
      const project = (data.project_summaries || [])[0] || {};
      const totals = data.totals || {};
      qs('[data-attendance-title]', panel).textContent = project.project_name || 'Project attendance';
      qs('[data-attendance-summary]', panel).textContent = date + ' / ' + (project.constituency_name || 'Assigned project');
      qs('[data-attendance-metrics]', panel).innerHTML = [
        metric('Expected', number(project.expected_people || totals.expected_people)),
        metric('Signed in', number(project.signed_people || totals.total_records)),
        metric('Missing', number(Math.max(0, Number(project.expected_people || 0) - Number(project.signed_people || 0)))),
        metric('GPS flags', number(project.gps_flags || (totals.geo_fail || 0) + (totals.outside_window || 0))),
        metric('Late', number(project.late || totals.late)),
        metric('Gateways', number((data.gateways || []).length)),
        metric('Open', number(totals.open_gateways)),
        metric('Rate', number(totals.attendance_percent) + '%')
      ].join('');
      renderRecords(data.records || []);
      renderExceptions(data);
      panel.hidden = false;
    } catch (error) {
      window.alert(error.message || 'Attendance details could not be loaded.');
    } finally {
      button.disabled = false;
    }
  }

  document.addEventListener('click', function (event) {
    const opener = event.target.closest('[data-attendance-project]');
    const close = event.target.closest('[data-attendance-close]');
    if (opener) openProject(opener);
    if (close) closePanel();
  });

  panel.addEventListener('click', function (event) {
    if (event.target === panel) closePanel();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closePanel();
  });
}());
