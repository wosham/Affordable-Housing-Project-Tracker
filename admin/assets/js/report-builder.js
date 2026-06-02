(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function formPayload(form, format) {
    var data = {};
    new FormData(form).forEach(function (value, key) {
      if (key.charAt(0) !== '_' || key === window.AHPTC.csrfTokenName()) {
        data[key] = value;
      }
    });
    if (format) data.format = format;
    return data;
  }

  function queryUrl(form, format) {
    var payload = formPayload(form, format);
    var params = new URLSearchParams();
    Object.keys(payload).forEach(function (key) {
      if (key.charAt(0) === '_') return;
      if (payload[key] !== '') params.set(key, payload[key]);
    });
    return toUrl(form.getAttribute('action')) + '?' + params.toString();
  }

  function toUrl(url) {
    if (/^(https?:)?\/\//i.test(url) || url.charAt(0) === '/') {
      return url;
    }
    return window.AHPTC.baseUrl().replace(/\/$/, '') + '/' + url.replace(/^\/+/, '');
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function label(value) {
    return String(value || '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
  }

  function renderSummary(summary) {
    var keys = Object.keys(summary || {});
    if (!keys.length) return '';
    return '<div class="report-preview-summary">' + keys.map(function (key) {
      return '<span><strong>' + escapeHtml(displayValue(key, summary[key])) + '</strong><small>' + escapeHtml(label(key)) + '</small></span>';
    }).join('') + '</div>';
  }

  function renderRows(rows) {
    if (!rows || !rows.length) {
      return '<div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-table" aria-hidden="true"></i></span><strong class="empty-state__title">No records</strong><span class="empty-state__text">This section has no matching data.</span></div>';
    }

    var headers = Object.keys(rows[0]);
    return '<div class="table-wrap"><table class="data-table"><thead><tr>' +
      headers.map(function (header) { return '<th>' + escapeHtml(label(header)) + '</th>'; }).join('') +
      '</tr></thead><tbody>' +
      rows.slice(0, 60).map(function (row) {
        return '<tr>' + headers.map(function (header) {
          return '<td>' + escapeHtml(displayValue(header, row[header])) + '</td>';
        }).join('') + '</tr>';
      }).join('') +
      '</tbody></table></div>' +
      (rows.length > 60 ? '<p class="report-preview-note">Showing first 60 rows. Export CSV for the full report.</p>' : '');
  }

  function renderReport(report) {
    var html = '<div class="report-preview-head"><div><h3>' + escapeHtml(report.title) + '</h3><p>' + escapeHtml(report.description || '') + '</p></div><span>' + escapeHtml(report.generated_at) + '</span></div>';
    html += renderSummary(report.summary || {});
    (report.sections || []).forEach(function (section) {
      html += '<section class="report-preview-section"><h4>' + escapeHtml(section.title || 'Section') + '</h4>' + renderRows(section.rows || []) + '</section>';
    });
    return html;
  }

  function displayValue(key, value) {
    if (value === null || value === undefined || value === '') return '-';
    var lower = String(key || '').toLowerCase();
    var numeric = Number(value);
    if (!isNaN(numeric) && (lower.indexOf('amount') !== -1 || lower.indexOf('value') !== -1 || lower.indexOf('sum') !== -1 || lower.indexOf('paid') !== -1 || lower.indexOf('retention') !== -1 || lower.indexOf('gross') !== -1 || lower.indexOf('net') !== -1 || lower.indexOf('contract') !== -1)) {
      return 'KES ' + numeric.toLocaleString(undefined, { maximumFractionDigits: 0 });
    }
    if (!isNaN(numeric) && (lower.indexOf('pct') !== -1 || lower.indexOf('completion') !== -1 || lower.indexOf('progress') !== -1)) {
      return Math.max(0, Math.min(100, numeric)).toLocaleString(undefined, { maximumFractionDigits: 1 }) + '%';
    }
    return value;
  }

  function setLoading(form, loading) {
    qsa('button', form).forEach(function (button) {
      button.disabled = loading;
    });
  }

  function initTypeChoices(form) {
    var input = qs('[data-report-type]', form);
    qsa('[data-report-type-choice]').forEach(function (button) {
      button.addEventListener('click', function () {
        qsa('[data-report-type-choice]').forEach(function (item) {
          item.classList.remove('is-active');
          item.setAttribute('aria-pressed', 'false');
        });
        button.classList.add('is-active');
        button.setAttribute('aria-pressed', 'true');
        input.value = button.getAttribute('data-report-type-choice');
      });
    });
  }

  function init() {
    var form = qs('[data-report-form]');
    var preview = qs('[data-report-preview]');
    var count = qs('[data-report-count]');
    if (!form || !preview) return;

    initTypeChoices(form);

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      setLoading(form, true);
      preview.innerHTML = '<div class="report-loading"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Generating report...</span></div>';

      window.AHPTC.request(form.getAttribute('action'), {
        method: 'POST',
        body: formPayload(form, 'json')
      }).then(function (response) {
        var report = response.report || {};
        preview.innerHTML = renderReport(report);
        if (count) count.textContent = (report.row_count || 0) + ' rows';
      }).catch(function (error) {
        preview.innerHTML = '<div class="alert alert--danger"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><div class="alert__content">' + escapeHtml(error.message || 'Report could not be generated.') + '</div></div>';
        if (count) count.textContent = 'Error';
      }).finally(function () {
        setLoading(form, false);
      });
    });

    qsa('[data-report-download]').forEach(function (button) {
      button.addEventListener('click', function () {
        window.open(queryUrl(form, button.getAttribute('data-report-download')), '_blank', 'noopener');
      });
    });

    form.addEventListener('reset', function () {
      window.setTimeout(function () {
        qsa('[data-report-type-choice]').forEach(function (item, index) {
          item.classList.toggle('is-active', index === 0);
          item.setAttribute('aria-pressed', index === 0 ? 'true' : 'false');
        });
        qs('[data-report-type]', form).value = 'executive_summary';
        if (count) count.textContent = 'Ready';
      }, 0);
    });

    var heroButton = qs('[data-report-generate]');
    if (heroButton) {
      heroButton.addEventListener('click', function () {
        form.requestSubmit();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
