(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function cellText(row, index) {
    var cell = row.children[index];
    return cell ? cell.textContent.trim() : '';
  }

  function filterRows(table, term) {
    var rows = qsa('tbody tr', table);
    var needle = term.trim().toLowerCase();
    var visible = 0;
    rows.forEach(function (row) {
      if (row.querySelector('.empty-state') && rows.length === 1) {
        row.hidden = false;
        return;
      }
      var haystack = row.textContent.toLowerCase();
      var hide = needle !== '' && haystack.indexOf(needle) === -1;
      row.hidden = hide;
      if (!hide) visible += 1;
    });
    return visible;
  }

  function sortRows(table, index, direction) {
    var tbody = qs('tbody', table);
    if (!tbody) return;

    var rows = qsa('tr', tbody).filter(function (row) {
      return !row.querySelector('.empty-state');
    });

    rows.sort(function (a, b) {
      var av = cellText(a, index);
      var bv = cellText(b, index);
      var an = parseFloat(av.replace(/[^0-9.-]/g, ''));
      var bn = parseFloat(bv.replace(/[^0-9.-]/g, ''));

      if (!Number.isNaN(an) && !Number.isNaN(bn) && /[0-9]/.test(av) && /[0-9]/.test(bv)) {
        return direction === 'desc' ? bn - an : an - bn;
      }

      return direction === 'desc' ? bv.localeCompare(av) : av.localeCompare(bv);
    });

    rows.forEach(function (row) {
      tbody.appendChild(row);
    });
  }

  function exportCsv(table, filename) {
    var rows = qsa('tr', table).filter(function (row) {
      return !row.hidden;
    }).map(function (row) {
      return qsa('th,td', row).map(function (cell) {
        return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
      }).join(',');
    });

    var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = filename || 'table-export.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  }

  function initTable(wrapper) {
    var table = qs('table', wrapper) || (wrapper.tagName === 'TABLE' ? wrapper : null);
    if (!table) return;

    var scope = wrapper.closest('[data-table-scope]') || wrapper.parentElement || document;
    var search = qs('[data-table-search]', scope);
    var selectAll = qs('[data-table-select-all]', wrapper) || qs('[data-table-select-all]', table);
    var exportButton = qs('[data-table-export]', scope);
    var countNode = qs('[data-table-count]', scope);

    function refreshCount() {
      if (!countNode) return;
      var total = qsa('tbody tr', table).filter(function (row) {
        return !row.querySelector('.empty-state');
      }).length;
      var visible = qsa('tbody tr', table).filter(function (row) {
        return !row.hidden && !row.querySelector('.empty-state');
      }).length;
      countNode.textContent = visible + (total !== visible ? ' / ' + total : '');
    }

    if (search) {
      search.addEventListener('input', function () {
        filterRows(table, search.value);
        refreshCount();
      });
    }

    qsa('th[data-sort]', table).forEach(function (header, index) {
      header.tabIndex = 0;
      header.setAttribute('role', 'button');
      header.setAttribute('title', 'Sort column');
      function toggleSort() {
        var current = header.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
        qsa('th[data-sort]', table).forEach(function (th) {
          th.removeAttribute('data-sort-dir');
        });
        header.setAttribute('data-sort-dir', current);
        // Prefer explicit column index when provided
        var col = parseInt(header.getAttribute('data-sort') || '', 10);
        sortRows(table, Number.isNaN(col) ? index : col, current);
      }
      header.addEventListener('click', toggleSort);
      header.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          toggleSort();
        }
      });
    });

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        qsa('tbody input[type="checkbox"]', table).forEach(function (box) {
          if (!box.disabled && !box.closest('tr').hidden) box.checked = selectAll.checked;
        });
      });
    }

    if (exportButton) {
      exportButton.addEventListener('click', function () {
        exportCsv(table, exportButton.getAttribute('data-table-export') || 'export.csv');
        if (window.AHPTC && typeof window.AHPTC.toast === 'function') {
          window.AHPTC.toast('Table exported to CSV.', 'success');
        }
      });
    }

    refreshCount();
  }

  function addStackLabels(table) {
    var headers = qsa('thead th', table).map(function (header) {
      return header.textContent.trim();
    });

    if (!headers.length) return;

    qsa('tbody tr', table).forEach(function (row) {
      qsa('td', row).forEach(function (cell, index) {
        if (!cell.hasAttribute('data-label') && headers[index]) {
          cell.setAttribute('data-label', headers[index]);
        }
      });
    });
  }

  function init() {
    var tables = [];
    qsa('[data-table], table.data-table').forEach(function (source) {
      var table = source.tagName === 'TABLE' ? source : qs('table', source);
      if (!table || tables.indexOf(table) !== -1 || table.dataset.tableInitialised === 'true') return;
      tables.push(table);
      table.dataset.tableInitialised = 'true';
      addStackLabels(table);
      initTable(table);
    });
  }

  window.AHPTC = Object.assign(window.AHPTC || {}, {
    initDataTables: init,
    exportTableCsv: exportCsv
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
