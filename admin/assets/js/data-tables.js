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
    rows.forEach(function (row) {
      var haystack = row.textContent.toLowerCase();
      row.hidden = needle !== '' && haystack.indexOf(needle) === -1;
    });
  }

  function sortRows(table, index, direction) {
    var tbody = qs('tbody', table);
    if (!tbody) return;

    var rows = qsa('tr', tbody);
    rows.sort(function (a, b) {
      var av = cellText(a, index);
      var bv = cellText(b, index);
      var an = parseFloat(av.replace(/[^0-9.-]/g, ''));
      var bn = parseFloat(bv.replace(/[^0-9.-]/g, ''));

      if (!Number.isNaN(an) && !Number.isNaN(bn)) {
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
    var table = qs('table', wrapper) || wrapper;
    var search = qs('[data-table-search]', wrapper.parentElement || document);
    var selectAll = qs('[data-table-select-all]', wrapper);
    var exportButton = qs('[data-table-export]', wrapper.parentElement || document);

    if (search) {
      search.addEventListener('input', function () {
        filterRows(table, search.value);
      });
    }

    qsa('th[data-sort]', table).forEach(function (header, index) {
      header.tabIndex = 0;
      header.setAttribute('role', 'button');
      header.addEventListener('click', function () {
        var current = header.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
        qsa('th[data-sort]', table).forEach(function (th) {
          th.removeAttribute('data-sort-dir');
        });
        header.setAttribute('data-sort-dir', current);
        sortRows(table, index, current);
      });
    });

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        qsa('tbody input[type="checkbox"]', table).forEach(function (box) {
          if (!box.disabled) box.checked = selectAll.checked;
        });
      });
    }

    if (exportButton) {
      exportButton.addEventListener('click', function () {
        exportCsv(table, exportButton.getAttribute('data-table-export') || 'export.csv');
      });
    }
  }

  function init() {
    qsa('[data-table]').forEach(initTable);
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
