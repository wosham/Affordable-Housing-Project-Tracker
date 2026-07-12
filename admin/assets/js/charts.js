(function (window, document) {
  'use strict';

  var root = document.documentElement;
  var chartState = new WeakMap();
  var DASHBOARD_POLL_MS = 20000;

  function css(name, fallback) {
    var value = getComputedStyle(root).getPropertyValue(name).trim();
    return value || fallback;
  }

  function palette() {
    return [
      css('--admin-primary', '#163300'),
      css('--admin-lime', '#a6ef27'),
      css('--admin-info', '#2563eb'),
      css('--admin-warning', '#f59e0b'),
      css('--admin-success', '#16a34a'),
      css('--admin-danger', '#dc2626'),
      css('--admin-text-muted', '#64748b')
    ];
  }

  function qs(selector, rootEl) {
    return (rootEl || document).querySelector(selector);
  }

  function qsa(selector, rootEl) {
    return Array.prototype.slice.call((rootEl || document).querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function hasValues(values) {
    return Array.isArray(values) && values.reduce(function (sum, value) {
      return sum + Number(value || 0);
    }, 0) > 0;
  }

  function compactNumber(value) {
    var num = Number(value || 0);
    var abs = Math.abs(num);
    if (abs >= 1000000000) return (num / 1000000000).toFixed(abs >= 10000000000 ? 0 : 1).replace(/\.0$/, '') + 'B';
    if (abs >= 1000000) return (num / 1000000).toFixed(abs >= 10000000 ? 0 : 1).replace(/\.0$/, '') + 'M';
    if (abs >= 1000) return (num / 1000).toFixed(abs >= 10000 ? 0 : 1).replace(/\.0$/, '') + 'K';
    return String(Math.round(num));
  }

  function money(value) {
    return 'KES ' + Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
  }

  function compactMoney(value) {
    return 'KES ' + compactNumber(value);
  }

  function formatValue(value, type) {
    if (type === 'money') return money(value);
    if (type === 'percent') return Number(value || 0).toFixed(0) + '%';
    return Number(value || 0).toLocaleString();
  }

  function shortLabel(value, max) {
    var text = String(value || '');
    max = max || 13;
    return text.length > max ? text.slice(0, Math.max(1, max - 1)) + '...' : text;
  }

  function wrapText(ctx, text, x, y, maxWidth, lineHeight, maxLines) {
    var words = String(text || '').split(/\s+/);
    var line = '';
    var lines = [];
    words.forEach(function (word) {
      var next = line ? line + ' ' + word : word;
      if (ctx.measureText(next).width > maxWidth && line) {
        lines.push(line);
        line = word;
      } else {
        line = next;
      }
    });
    if (line) lines.push(line);
    lines.slice(0, maxLines || 2).forEach(function (item, index) {
      var output = item;
      if (index === (maxLines || 2) - 1 && lines.length > (maxLines || 2)) {
        output = shortLabel(output, Math.max(8, Math.floor(maxWidth / 7)));
      }
      ctx.fillText(output, x, y + index * lineHeight);
    });
  }

  function baseOptions(extra) {
    var gridColor = css('--admin-border', '#dbe3ef');
    var textColor = css('--admin-text-muted', '#64748b');
    return Object.assign({
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
        },
        tooltip: {
          callbacks: {}
        }
      },
      scales: {
        x: { ticks: { color: textColor }, grid: { color: gridColor } },
        y: { ticks: { color: textColor }, grid: { color: gridColor }, beginAtZero: true }
      }
    }, extra || {});
  }

  function canvas(id) {
    return document.getElementById(id);
  }

  function canvasSize(el) {
    var box = el.getBoundingClientRect();
    var width = Math.max(280, Math.floor(box.width || el.clientWidth || 360));
    var height = Math.max(220, Math.floor(box.height || el.clientHeight || 260));
    var ratio = window.devicePixelRatio || 1;
    el.width = width * ratio;
    el.height = height * ratio;
    var ctx = el.getContext('2d');
    if (!ctx) return null;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);
    return { ctx: ctx, width: width, height: height };
  }

  function chartWrap(el) {
    return el.closest('.sa-chart-wrap, .chart-container') || el.parentElement;
  }

  function renderLegend(el, items) {
    var wrap = chartWrap(el);
    if (!wrap) return;
    var legend = qs('[data-chart-legend]', wrap);
    if (!legend) {
      legend = document.createElement('div');
      legend.className = 'sa-chart-legend';
      legend.setAttribute('data-chart-legend', '1');
      wrap.appendChild(legend);
    }

    legend.innerHTML = items.slice(0, 8).map(function (item) {
      return '<span class="sa-chart-legend__item" title="' + escapeHtml(item.label) + '">' +
        '<i style="background:' + escapeHtml(item.color) + '"></i>' +
        '<strong>' + escapeHtml(shortLabel(item.label, 24)) + '</strong>' +
        (item.value !== undefined ? '<em>' + escapeHtml(item.displayValue || String(item.value)) + '</em>' : '') +
      '</span>';
    }).join('');
  }

  function tooltipEl() {
    var el = qs('[data-chart-tooltip]');
    if (!el) {
      el = document.createElement('div');
      el.className = 'sa-chart-tooltip';
      el.setAttribute('data-chart-tooltip', '1');
      el.hidden = true;
      document.body.appendChild(el);
    }
    return el;
  }

  function hideTooltip() {
    var el = tooltipEl();
    el.hidden = true;
  }

  function showTooltip(event, point) {
    var el = tooltipEl();
    el.innerHTML = '<strong>' + escapeHtml(point.title) + '</strong>' +
      '<span>' + escapeHtml(point.series || 'Value') + ': ' + escapeHtml(point.valueLabel) + '</span>' +
      (point.shareLabel ? '<small>' + escapeHtml(point.shareLabel) + '</small>' : '');
    el.hidden = false;
    var x = Math.min(window.innerWidth - el.offsetWidth - 12, event.clientX + 14);
    var y = Math.min(window.innerHeight - el.offsetHeight - 12, event.clientY + 14);
    el.style.left = Math.max(12, x) + 'px';
    el.style.top = Math.max(12, y) + 'px';
  }

  function bindHover(el) {
    if (el.dataset.chartHoverReady === '1') return;
    el.dataset.chartHoverReady = '1';
    el.addEventListener('mousemove', function (event) {
      var state = chartState.get(el);
      if (!state || !state.points || !state.points.length) return hideTooltip();
      var rect = el.getBoundingClientRect();
      var x = event.clientX - rect.left;
      var y = event.clientY - rect.top;
      var match = null;
      var distance = Infinity;
      state.points.forEach(function (point) {
        var dx = x - point.x;
        var dy = y - point.y;
        var d = Math.sqrt(dx * dx + dy * dy);
        if ((point.hit && point.hit(x, y)) || d < distance) {
          match = point;
          distance = d;
        }
      });
      if (match && (distance <= (match.radius || 36) || (match.hit && match.hit(x, y)))) showTooltip(event, match);
      else hideTooltip();
    });
    el.addEventListener('mouseleave', hideTooltip);
  }

  function fallbackDoughnut(el, config) {
    var size = canvasSize(el);
    if (!size) return null;
    var ctx = size.ctx;
    var labels = ((config.data || {}).labels || []);
    var values = (((config.data || {}).datasets || [])[0] || {}).data || [];
    var colors = ((((config.data || {}).datasets || [])[0] || {}).backgroundColor || palette());
    var valueType = (config.options || {}).valueType || 'number';
    var total = values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
    var cx = size.width / 2;
    var cy = Math.max(94, size.height * 0.42);
    var radius = Math.min(size.width, size.height) * 0.29;
    var start = -Math.PI / 2;
    var points = [];

    values.forEach(function (value, index) {
      var slice = total > 0 ? (Number(value || 0) / total) * Math.PI * 2 : 0;
      var end = start + slice;
      var segmentStart = start;
      var segmentEnd = end;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, radius, start, end);
      ctx.closePath();
      ctx.fillStyle = colors[index % colors.length];
      ctx.fill();
      points.push({
        x: cx + Math.cos(start + slice / 2) * radius * 0.72,
        y: cy + Math.sin(start + slice / 2) * radius * 0.72,
        radius: radius,
        title: labels[index] || 'Item',
        series: 'Total',
        valueLabel: formatValue(value, valueType),
        shareLabel: total > 0 ? ((Number(value || 0) / total) * 100).toFixed(1).replace(/\.0$/, '') + '% of total' : '',
        hit: function (mx, my) {
          var dx = mx - cx;
          var dy = my - cy;
          var angle = Math.atan2(dy, dx);
          if (angle < -Math.PI / 2) angle += Math.PI * 2;
          var adjustedStart = segmentStart < -Math.PI / 2 ? segmentStart + Math.PI * 2 : segmentStart;
          var adjustedEnd = segmentEnd < -Math.PI / 2 ? segmentEnd + Math.PI * 2 : segmentEnd;
          return Math.sqrt(dx * dx + dy * dy) <= radius && angle >= adjustedStart && angle <= adjustedEnd;
        }
      });
      start = end;
    });

    ctx.beginPath();
    ctx.arc(cx, cy, radius * 0.62, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();
    ctx.fillStyle = css('--admin-text', '#162033');
    ctx.font = '800 20px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(compactNumber(total), cx, cy);
    ctx.font = '700 11px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    ctx.fillStyle = css('--admin-text-muted', '#64748b');
    ctx.fillText('total', cx, cy + 22);

    renderLegend(el, labels.map(function (label, index) {
      return { label: label, color: colors[index % colors.length], value: values[index], displayValue: formatValue(values[index], valueType) };
    }));
    chartState.set(el, { points: points });
    bindHover(el);
    return el;
  }

  function fallbackSeries(el, config) {
    var size = canvasSize(el);
    if (!size) return null;
    var ctx = size.ctx;
    var labels = ((config.data || {}).labels || []);
    var datasets = ((config.data || {}).datasets || []);
    var colors = palette();
    var valueType = (config.options || {}).valueType || 'number';
    var left = valueType === 'money' ? 76 : 54;
    var right = 18;
    var top = 28;
    var bottom = labels.length > 4 ? 64 : 48;
    var chartWidth = size.width - left - right;
    var chartHeight = size.height - top - bottom;
    var allValues = [];
    var points = [];

    datasets.forEach(function (set) {
      allValues = allValues.concat((set.data || []).map(function (value) { return Number(value || 0); }));
    });

    var max = Math.max.apply(Math, allValues.concat([1]));
    var groupWidth = chartWidth / Math.max(labels.length, 1);
    var barWidth = Math.max(7, Math.min(32, (groupWidth - 14) / Math.max(datasets.length, 1)));

    ctx.strokeStyle = css('--admin-border', '#dbe3ef');
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(left, top);
    ctx.lineTo(left, top + chartHeight);
    ctx.lineTo(left + chartWidth, top + chartHeight);
    ctx.stroke();

    ctx.fillStyle = css('--admin-text-muted', '#64748b');
    ctx.font = '700 11px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    [0, .5, 1].forEach(function (tick) {
      var y = top + chartHeight - chartHeight * tick;
      var value = max * tick;
      ctx.strokeStyle = tick === 0 ? css('--admin-border-strong', '#cbd5e1') : css('--admin-border', '#dbe3ef');
      ctx.beginPath();
      ctx.moveTo(left, y);
      ctx.lineTo(left + chartWidth, y);
      ctx.stroke();
      ctx.fillStyle = css('--admin-text-muted', '#64748b');
      ctx.fillText(valueType === 'money' ? compactMoney(value) : compactNumber(value), left - 8, y);
    });

    datasets.forEach(function (set, setIndex) {
      var color = set.backgroundColor || set.borderColor || colors[setIndex % colors.length];
      ctx.fillStyle = color;
      (set.data || []).forEach(function (value, index) {
        var numeric = Number(value || 0);
        var height = (numeric / max) * Math.max(1, chartHeight - 8);
        var x = left + index * groupWidth + Math.max(6, (groupWidth - (barWidth * datasets.length + 4 * (datasets.length - 1))) / 2) + setIndex * (barWidth + 4);
        var y = top + chartHeight - height;
        ctx.fillRect(x, y, barWidth, Math.max(2, height));
        points.push({
          x: x + barWidth / 2,
          y: y,
          radius: Math.max(18, groupWidth / 2),
          title: labels[index] || 'Item',
          series: set.label || 'Value',
          valueLabel: formatValue(numeric, valueType),
          hit: function (mx, my) {
            return mx >= x - 4 && mx <= x + barWidth + 4 && my >= y - 12 && my <= top + chartHeight + 8;
          }
        });
      });
    });

    ctx.fillStyle = css('--admin-text-muted', '#64748b');
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    ctx.font = '700 10px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    labels.slice(0, 10).forEach(function (label, index) {
      var x = left + index * groupWidth + groupWidth / 2;
      wrapText(ctx, shortLabel(label, groupWidth < 70 ? 8 : 14), x, top + chartHeight + 12, Math.max(44, groupWidth - 6), 12, 2);
    });

    renderLegend(el, datasets.map(function (set, index) {
      return { label: set.label || 'Series', color: set.backgroundColor || set.borderColor || colors[index % colors.length] };
    }));
    chartState.set(el, { points: points });
    bindHover(el);
    return el;
  }

  function fallback(id, config) {
    var el = canvas(id);
    if (!el || !el.getContext) return null;
    if (config.type === 'doughnut') return fallbackDoughnut(el, config);
    return fallbackSeries(el, config);
  }

  function make(id, config) {
    var el = canvas(id);
    if (!el) return null;
    if (typeof window.Chart === 'undefined') {
      return fallback(id, config);
    }
    return new window.Chart(el, config);
  }

  function doughnut(id, dataset, options) {
    if (!dataset || !hasValues(dataset.values)) return null;
    return make(id, {
      type: 'doughnut',
      data: {
        labels: dataset.labels || [],
        datasets: [{ data: dataset.values || [], backgroundColor: palette(), borderWidth: 0 }]
      },
      options: baseOptions(Object.assign({
        cutout: '68%',
        scales: {}
      }, options || {}))
    });
  }

  function bar(id, labels, datasets, options) {
    if (!Array.isArray(datasets) || !datasets.some(function (set) { return hasValues(set.data); })) return null;
    return make(id, {
      type: 'bar',
      data: { labels: labels || [], datasets: datasets },
      options: baseOptions(options)
    });
  }

  function line(id, labels, datasets, options) {
    if (!Array.isArray(datasets) || !datasets.some(function (set) { return hasValues(set.data); })) return null;
    return make(id, {
      type: 'line',
      data: { labels: labels || [], datasets: datasets },
      options: baseOptions(options)
    });
  }

  function bindAnalytics() {
    var data = window.AHPTC_ANALYTICS || {};
    var colors = palette();

    doughnut('projectStatusChart', data.projectStatus);
    doughnut('ipcPipelineChart', data.ipcPipeline);
    doughnut('attendanceRoleChart', data.attendanceRole);
    doughnut('newsStatusChart', data.newsStatus);
    doughnut('contactStatusChart', data.contactStatus);
    doughnut('galleryStatusChart', data.galleryStatus);

    bar('constituencyUnitsChart', (data.constituencyUnits || {}).labels, [{
      label: 'Units',
      data: (data.constituencyUnits || {}).values || [],
      backgroundColor: colors[0]
    }]);

    bar('completionChart', (data.completion || {}).labels, [{
      label: 'Completion %',
      data: (data.completion || {}).values || [],
      backgroundColor: colors[1]
    }], { valueType: 'percent' });

    bar('paymentMonthsChart', (data.paymentMonths || {}).labels, [{
      label: 'Payments',
      data: (data.paymentMonths || {}).values || [],
      backgroundColor: colors[4]
    }], { valueType: 'money' });

    bar('budgetBurnChart', (data.budgetBurn || {}).labels, [
      { label: 'Contract Sum', data: (data.budgetBurn || {}).contract || [], backgroundColor: colors[0] },
      { label: 'Paid', data: (data.budgetBurn || {}).paid || [], backgroundColor: colors[1] }
    ], { valueType: 'money' });

    line('attendanceDailyChart', (data.attendanceDaily || {}).labels, [
      { label: 'Sign-ins', data: (data.attendanceDaily || {}).total || [], borderColor: colors[4], backgroundColor: 'rgba(22, 163, 74, .16)', fill: true, tension: .35 },
      { label: 'GPS flags', data: (data.attendanceDaily || {}).geoFail || [], borderColor: colors[5], backgroundColor: 'rgba(220, 38, 38, .12)', fill: true, tension: .35 }
    ]);

    bar('attendanceProjectChart', (data.attendanceProject || {}).labels, [{
      label: 'Sign-ins',
      data: (data.attendanceProject || {}).values || [],
      backgroundColor: colors[2]
    }]);

    line('subscribersDailyChart', (data.subscribersDaily || {}).labels, [{
      label: 'Subscribers',
      data: (data.subscribersDaily || {}).values || [],
      borderColor: colors[0],
      backgroundColor: 'rgba(22, 51, 0, .14)',
      fill: true,
      tension: .35
    }]);
  }

  function bindDashboard() {
    var data = window.AHPTC_DASHBOARD_CHARTS || {};
    var colors = palette();

    doughnut('projectStatusChart', data.projectStatus);
    doughnut('ipcPipelineChart', data.ipcPipeline);

    bar('unitsChart', (data.unitsByConstituency || {}).labels, [{
      label: 'Units',
      data: (data.unitsByConstituency || {}).values || [],
      backgroundColor: colors[0]
    }]);

    bar('attendanceProjectChart', (data.attendanceByProject || {}).labels, [{
      label: 'Signed in today',
      data: (data.attendanceByProject || {}).values || [],
      backgroundColor: colors[4]
    }]);

    bar('budgetBurnChart', (data.budgetBurn || {}).labels, [
      { label: 'Contract Sum', data: (data.budgetBurn || {}).contract || [], backgroundColor: colors[0] },
      { label: 'Paid', data: (data.budgetBurn || {}).paid || [], backgroundColor: colors[1] }
    ], { valueType: 'money' });
  }

  function renderDashboardContacts(data) {
    var count = Number((data || {}).unreadContacts || 0);
    var stat = qs('[data-dashboard-unread-contacts]');
    var trend = qs('[data-dashboard-contact-trend]');
    var card = qs('[data-dashboard-contact-inbox]');
    if (stat) stat.textContent = count.toLocaleString();
    if (trend) trend.textContent = count > 0 ? 'Citizen inbox attention' : 'Inbox is clear';
    if (!card) return;

    var list = qs('[data-dashboard-contact-list]', card);
    var empty = qs('[data-dashboard-contact-empty]', card);
    var contacts = (data && data.contacts) || [];

    if (!contacts.length) {
      if (list) list.remove();
      if (!empty) {
        empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.setAttribute('data-dashboard-contact-empty', '1');
        empty.innerHTML = '<span class="empty-state__icon"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span><h3 class="empty-state__title">Inbox is clear</h3><p class="empty-state__text">Unread submissions will appear here.</p>';
        card.appendChild(empty);
      }
      return;
    }

    if (empty) empty.remove();
    if (!list) {
      list = document.createElement('div');
      list.className = 'sa-list';
      list.setAttribute('data-dashboard-contact-list', '1');
      card.appendChild(list);
    }

    list.innerHTML = contacts.map(function (contact) {
      return '<a class="sa-list-item" href="' + escapeHtml(contact.url || '#') + '">' +
        '<span><strong>' + escapeHtml(contact.name) + '</strong><small>' + escapeHtml(contact.subject || 'No subject') + '</small></span>' +
        '<time>' + escapeHtml(contact.timeAgo || '') + '</time>' +
      '</a>';
    }).join('');
  }

  function bindDashboardLive() {
    if (!qs('.sa-dashboard')) return;
    if (!window.AHPTC || typeof window.AHPTC.request !== 'function') return;

    function refresh() {
      window.AHPTC.request('api/superadmin/dashboard-summary.php', { method: 'GET' }).then(function (data) {
        if (!data || data.success === false) return;
        renderDashboardContacts(data);
      }).catch(function () {});
    }

    refresh();
    window.setInterval(refresh, DASHBOARD_POLL_MS);
  }

  window.AHPTCCharts = {
    palette: palette,
    doughnut: doughnut,
    bar: bar,
    line: line,
    money: money,
    compactMoney: compactMoney,
    bindDashboard: bindDashboard,
    bindAnalytics: bindAnalytics
  };

  document.addEventListener('DOMContentLoaded', function () {
    bindDashboard();
    bindAnalytics();
    bindDashboardLive();
  });
}(window, document));
