(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function setState(row, message, ok) {
    var state = qs('[data-settings-state]', row);
    if (!state) return;
    state.textContent = message || '';
    state.classList.toggle('is-success', ok === true);
    state.classList.toggle('is-error', ok === false);
  }

  function inputValue(row) {
    var input = qs('[data-setting-input]', row);
    if (!input) return '';
    if (input.type === 'checkbox') return input.checked ? '1' : '0';
    return input.value;
  }

  function rememberValue(row) {
    row.setAttribute('data-setting-original-value', inputValue(row));
  }

  function isDirty(row) {
    return inputValue(row) !== (row.getAttribute('data-setting-original-value') || '');
  }

  function updateDirtyState(row) {
    if (isDirty(row)) {
      setState(row, 'Unsaved changes', null);
    } else {
      setState(row, '', null);
    }
  }

  function saveRow(row) {
    var key = row.getAttribute('data-setting-key') || '';
    var buttons = qsa('button', row);
    buttons.forEach(function (button) { button.disabled = true; });
    setState(row, 'Saving...', null);

    return window.AHPTC.request('api/settings/save.php', {
      method: 'POST',
      body: { key: key, value: inputValue(row) }
    }).then(function (data) {
      setState(row, data.message || 'Saved.', true);
      var input = qs('[data-setting-input]', row);
      if (input && input.hasAttribute('data-setting-sensitive')) {
        input.value = '';
        if (data.setting && data.setting.has_value) {
          input.setAttribute('placeholder', 'Saved value is hidden. Leave blank to keep it.');
        }
      }
      rememberValue(row);
      row.classList.toggle('is-custom', !!(data.setting && data.setting.is_custom));
      var badge = qs('[data-custom-badge]', row);
      if (badge) badge.hidden = !(data.setting && data.setting.is_custom);
    }).catch(function (error) {
      setState(row, (error.data && error.data.message) || error.message || 'Could not save.', false);
    }).finally(function () {
      buttons.forEach(function (button) { button.disabled = false; });
    });
  }

  function resetRow(row) {
    if (!window.confirm('Reset this setting to its default value?')) return;
    var key = row.getAttribute('data-setting-key') || '';
    setState(row, 'Resetting...', null);

    window.AHPTC.request('api/settings/reset.php', {
      method: 'POST',
      body: { key: key }
    }).then(function (data) {
      if (data.setting) {
        var input = qs('[data-setting-input]', row);
        if (input) {
          if (input.type === 'checkbox') input.checked = ['1', 'true', 'yes', 'on'].indexOf(String(data.setting.value).toLowerCase()) !== -1;
          else input.value = data.setting.value || '';
        }
      }
      rememberValue(row);
      row.classList.remove('is-custom');
      var badge = qs('[data-custom-badge]', row);
      if (badge) badge.hidden = true;
      setState(row, data.message || 'Reset.', true);
    }).catch(function (error) {
      setState(row, (error.data && error.data.message) || error.message || 'Could not reset.', false);
    });
  }

  function initTabs() {
    qsa('[data-settings-tab]').forEach(function (button) {
      button.addEventListener('click', function () {
        var target = button.getAttribute('data-settings-tab') || '';
        qsa('[data-settings-tab]').forEach(function (tab) {
          var active = tab === button;
          tab.classList.toggle('is-active', active);
          tab.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        qsa('[data-settings-panel]').forEach(function (panel) {
          panel.classList.toggle('is-active', panel.getAttribute('data-settings-panel') === target);
        });
      });
    });
  }

  function initActions() {
    document.addEventListener('click', function (event) {
      var save = event.target.closest && event.target.closest('[data-settings-save]');
      if (save) {
        var row = save.closest('[data-setting-row]');
        if (row) saveRow(row);
        return;
      }

      var reset = event.target.closest && event.target.closest('[data-settings-reset]');
      if (reset) {
        var resetRowEl = reset.closest('[data-setting-row]');
        if (resetRowEl) resetRow(resetRowEl);
        return;
      }

      var groupReset = event.target.closest && event.target.closest('[data-settings-reset-group]');
      if (groupReset) {
        var group = groupReset.getAttribute('data-settings-reset-group') || '';
        if (!window.confirm('Reset every visible setting in this group to default?')) return;
        groupReset.disabled = true;
        window.AHPTC.request('api/settings/reset.php', {
          method: 'POST',
          body: { group: group }
        }).then(function () {
          window.location.reload();
        }).catch(function (error) {
          window.alert((error.data && error.data.message) || error.message || 'Could not reset group.');
        }).finally(function () {
          groupReset.disabled = false;
        });
        return;
      }

      var saveVisible = event.target.closest && event.target.closest('[data-settings-save-visible]');
      if (saveVisible) {
        var rows = qsa('.settings-panel.is-active [data-setting-row]').filter(isDirty);
        if (!rows.length) {
          window.alert('No visible settings have unsaved changes.');
          return;
        }
        saveVisible.disabled = true;
        rows.reduce(function (promise, row) {
          return promise.then(function () { return saveRow(row); });
        }, Promise.resolve()).finally(function () {
          saveVisible.disabled = false;
        });
      }
    });
  }

  function initDirtyStates() {
    qsa('[data-setting-row]').forEach(function (row) {
      rememberValue(row);
      qsa('[data-setting-input]', row).forEach(function (input) {
        input.addEventListener('input', function () {
          updateDirtyState(row);
        });
        input.addEventListener('change', function () {
          updateDirtyState(row);
        });
      });
    });
  }

  function init() {
    if (!document.querySelector('.sa-settings-page')) return;
    initTabs();
    initActions();
    initDirtyStates();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
