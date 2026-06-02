(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function refresh(button) {
    if (!window.AHPTC) return;
    button.disabled = true;
    window.AHPTC.request('api/system-health/refresh.php', {
      method: 'POST',
      body: {}
    }).then(function () {
      window.location.reload();
    }).catch(function (error) {
      window.alert((error.data && error.data.message) || error.message || 'Health refresh failed.');
    }).finally(function () {
      button.disabled = false;
    });
  }

  function loadLog(button) {
    var file = button.getAttribute('data-log-file') || 'error.log';
    var tail = qs('[data-log-tail]');
    if (!tail) return;
    qsa('[data-log-file]').forEach(function (tab) {
      tab.classList.toggle('is-active', tab === button);
    });
    tail.textContent = 'Loading...';
    window.AHPTC.request('api/system-health/log-tail.php?file=' + encodeURIComponent(file) + '&lines=40').then(function (data) {
      var lines = data.log && data.log.lines ? data.log.lines : [];
      tail.textContent = lines.length ? lines.join('\n') : 'No log entries found.';
    }).catch(function (error) {
      tail.textContent = error.message || 'Could not load log.';
    });
  }

  function copySummary() {
    var node = qs('#healthSummaryJson');
    if (!node) return;
    var parsed = {};
    try { parsed = JSON.parse(node.textContent || '{}'); } catch (error) {}
    var text = [
      'AHPTC System Health',
      'Status: ' + (parsed.status || 'unknown'),
      'Score: ' + (parsed.score || 0),
      'Checked: ' + (parsed.checked_at || ''),
      'Critical: ' + ((parsed.counts && parsed.counts.critical) || 0),
      'Warnings: ' + ((parsed.counts && parsed.counts.warning) || 0)
    ].join('\n');
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        window.alert('Health summary copied.');
      });
    } else {
      window.prompt('Copy health summary:', text);
    }
  }

  function init() {
    if (!document.querySelector('.sa-health-page')) return;
    var refreshButton = qs('[data-health-refresh]');
    if (refreshButton) refreshButton.addEventListener('click', function () { refresh(refreshButton); });
    var copyButton = qs('[data-health-copy]');
    if (copyButton) copyButton.addEventListener('click', copySummary);
    qsa('[data-log-file]').forEach(function (button) {
      button.addEventListener('click', function () { loadLog(button); });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
