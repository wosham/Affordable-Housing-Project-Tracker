(function (window, document) {
  'use strict';

  var root = document.documentElement;

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

  function hasValues(values) {
    return Array.isArray(values) && values.reduce(function (sum, value) {
      return sum + Number(value || 0);
    }, 0) > 0;
  }

  function money(value) {
    return 'KES ' + Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
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
    var el = document.getElementById(id);
    if (!el || typeof window.Chart === 'undefined') {
      return null;
    }
    return el;
  }

  function make(id, config) {
    var el = canvas(id);
    if (!el) return null;
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
    }], {
      scales: {
        x: { ticks: { color: css('--admin-text-muted', '#64748b') }, grid: { color: css('--admin-border', '#dbe3ef') } },
        y: { min: 0, max: 100, ticks: { color: css('--admin-text-muted', '#64748b') }, grid: { color: css('--admin-border', '#dbe3ef') } }
      }
    });

    bar('paymentMonthsChart', (data.paymentMonths || {}).labels, [{
      label: 'Payments',
      data: (data.paymentMonths || {}).values || [],
      backgroundColor: colors[4]
    }], {
      plugins: { tooltip: { callbacks: { label: function (ctx) { return money(ctx.parsed.y); } } } }
    });

    bar('budgetBurnChart', (data.budgetBurn || {}).labels, [
      { label: 'Contract Sum', data: (data.budgetBurn || {}).contract || [], backgroundColor: colors[0] },
      { label: 'Paid', data: (data.budgetBurn || {}).paid || [], backgroundColor: colors[1] }
    ], {
      plugins: { tooltip: { callbacks: { label: function (ctx) { return ctx.dataset.label + ': ' + money(ctx.parsed.y); } } } }
    });

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

  window.AHPTCCharts = {
    palette: palette,
    doughnut: doughnut,
    bar: bar,
    line: line,
    money: money
  };

  document.addEventListener('DOMContentLoaded', bindAnalytics);
}(window, document));
