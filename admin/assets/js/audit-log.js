(function () {
  'use strict';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function prettyJson(value) {
    try {
      return JSON.stringify(value || {}, null, 2);
    } catch (error) {
      return '{}';
    }
  }

  function openModal() {
    var modal = qs('[data-audit-modal]');
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('audit-modal-open');
  }

  function closeModal() {
    var modal = qs('[data-audit-modal]');
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('audit-modal-open');
  }

  function renderEvent(event) {
    var details = prettyJson(event.details);
    var metadata = prettyJson(event.metadata);
    return '' +
      '<header class="audit-detail__head">' +
        '<div><span class="sa-panel-label"><i class="fa-solid fa-shield-halved"></i> Event #' + escapeHtml(event.id) + '</span>' +
        '<h2 id="auditModalTitle">' + escapeHtml(event.action_label || event.action || 'Audit event') + '</h2>' +
        '<p>' + escapeHtml(event.created_at || '') + ' · ' + escapeHtml(event.severity || 'info') + '</p></div>' +
      '</header>' +
      '<div class="audit-detail__grid">' +
        detailItem('Actor', event.actor_name, event.actor_email) +
        detailItem('Role', event.actor_role || 'system', 'User #' + (event.user_id || 0)) +
        detailItem('Module', event.module, 'Target #' + (event.target_id || 0)) +
        detailItem('IP Address', event.ip || '-', event.request_method || '') +
        detailItem('Route', event.route || '-', event.event_hash ? 'Hash ' + event.event_hash.substring(0, 16) : '') +
        detailItem('User Agent', event.user_agent || '-', '') +
      '</div>' +
      '<section class="audit-detail__json"><h3>Details JSON</h3><pre>' + escapeHtml(details) + '</pre></section>' +
      '<section class="audit-detail__json"><h3>Metadata JSON</h3><pre>' + escapeHtml(metadata) + '</pre></section>';
  }

  function detailItem(label, value, hint) {
    return '<div><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value || '-') + '</strong><small>' + escapeHtml(hint || '') + '</small></div>';
  }

  function loadDetail(id) {
    var body = qs('[data-audit-modal-body]');
    if (!body) return;
    body.innerHTML = '<div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-spinner fa-spin"></i></span><strong class="empty-state__title">Loading event</strong></div>';
    openModal();
    window.AHPTC.request('api/audit/detail.php?id=' + encodeURIComponent(id)).then(function (data) {
      body.innerHTML = renderEvent(data.event || {});
    }).catch(function (error) {
      body.innerHTML = '<div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-triangle-exclamation"></i></span><strong class="empty-state__title">Could not load event</strong><span class="empty-state__text">' + escapeHtml(error.message || 'Request failed') + '</span></div>';
    });
  }

  function init() {
    if (!document.querySelector('.sa-audit-page')) return;
    document.addEventListener('click', function (event) {
      var detail = event.target.closest && event.target.closest('[data-audit-detail]');
      if (detail) {
        loadDetail(detail.getAttribute('data-audit-detail'));
        return;
      }
      if (event.target.closest && event.target.closest('[data-audit-close]')) {
        closeModal();
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeModal();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
