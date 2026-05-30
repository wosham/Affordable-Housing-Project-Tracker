(function () {
  'use strict';

  function slug(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function bindSlugs() {
    document.querySelectorAll('[data-slug-source]').forEach(function (input) {
      var sourceName = input.getAttribute('data-slug-source');
      var form = input.closest('form');
      var source = form ? form.querySelector('[name="' + sourceName + '"]') : null;
      if (!source) return;

      source.addEventListener('input', function () {
        if (input.value.trim() !== '') return;
        input.placeholder = slug(source.value) || 'auto-generated if empty';
      });
    });
  }

  function bindAssetPreview() {
    document.querySelectorAll('[data-cms-upload]').forEach(function (wrap) {
      var target = wrap.querySelector('[data-cms-upload-target]');
      if (!target) return;

      function update() {
        var path = target.value || '';
        var preview = wrap.querySelector('[data-cms-asset-preview]');
        var name = wrap.querySelector('[data-cms-asset-name]');
        wrap.classList.toggle('has-asset', path.trim() !== '');
        if (name) name.textContent = path ? path.split('/').pop() : 'No image selected';
        if (preview) {
          preview.innerHTML = path
            ? '<img src="' + window.AHPTC.baseUrl() + '/' + path.replace(/^\/+/, '') + '" alt="">'
            : '<span><i class="fa-solid fa-image" aria-hidden="true"></i></span>';
        }
      }

      target.addEventListener('input', update);
      update();
    });
  }

  function rowsFor(container) {
    if (container.tagName === 'TABLE') {
      return Array.prototype.slice.call(container.querySelectorAll('tbody tr'));
    }

    return Array.prototype.slice.call(container.querySelectorAll('[data-table-row]'));
  }

  function paginate(container) {
    var rows = rowsFor(container);
    var perPage = 10;
    if (rows.length <= perPage) return;

    var pages = Math.ceil(rows.length / perPage);
    var current = 1;
    var pager = document.createElement('div');
    pager.className = 'sa-stakeholder-pagination';
    var label = document.createElement('span');
    var buttons = document.createElement('div');
    buttons.className = 'sa-stakeholder-pagination__buttons';
    pager.appendChild(label);
    pager.appendChild(buttons);

    function render() {
      rows.forEach(function (row, index) {
        var page = Math.floor(index / perPage) + 1;
        row.hidden = page !== current;
      });
      label.textContent = 'Showing ' + (((current - 1) * perPage) + 1) + '-' + Math.min(current * perPage, rows.length) + ' of ' + rows.length;
      buttons.innerHTML = '';
      for (var page = 1; page <= pages; page += 1) {
        var button = document.createElement('button');
        button.type = 'button';
        button.textContent = String(page);
        button.className = page === current ? 'is-active' : '';
        button.addEventListener('click', (function (targetPage) {
          return function () {
            current = targetPage;
            render();
          };
        })(page));
        buttons.appendChild(button);
      }
    }

    container.insertAdjacentElement('afterend', pager);
    render();
  }

  function bindPagination() {
    document.querySelectorAll('[data-stakeholder-table]').forEach(paginate);
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindSlugs();
    bindAssetPreview();
    bindPagination();
  });
})();
