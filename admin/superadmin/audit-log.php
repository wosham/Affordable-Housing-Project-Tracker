<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'user_id' => Security::cleanInt($_GET['user_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'action' => Security::cleanString((string)($_GET['action'] ?? '')),
    'module' => Security::cleanString((string)($_GET['module'] ?? '')),
    'severity' => Security::cleanString((string)($_GET['severity'] ?? '')),
    'target_id' => Security::cleanInt($_GET['target_id'] ?? 0),
    'ip' => Security::cleanString((string)($_GET['ip'] ?? '')),
    'quick' => Security::cleanString((string)($_GET['quick'] ?? '')),
    'date_from' => audit_date($_GET['date_from'] ?? ''),
    'date_to' => audit_date($_GET['date_to'] ?? ''),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);

$perPage = max(10, min(100, Security::cleanInt($_GET['per_page'] ?? 25)));
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = AuditLog::countItems($filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$logs = array_map([AuditLog::class, 'payload'], AuditLog::items($filters, $perPage, $offset));
$summary = AuditLog::summary($filters);
$actions = AuditLog::actionOptions();
$modules = AuditLog::moduleOptions();
$users = AuditLog::userOptions();
$roles = Database::fetchAll('SELECT slug, name FROM roles ORDER BY name ASC');
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($logs), $total);
$exportUrl = Url::to('api/audit/export.php?' . http_build_query($filters));

$pageTitle = 'Audit Log';
$pageDescription = 'Search login, edit, approval, delete and security activity across the admin system.';
$adminRole = 'superadmin';
$contentClass = 'sa-audit-page';
$componentCss = ['audit-log'];
$pageScripts = ['audit-log'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Audit Log'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="audit-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i> System trace</span>
    <h2>Audit log explorer</h2>
    <p>Search every login, edit, approval, rejection, delete, upload, settings change and workflow event recorded by the portal.</p>
  </div>
  <div class="audit-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/settings.php')) ?>"><i class="fa-solid fa-gear" aria-hidden="true"></i> Settings</a>
    <a class="btn btn--primary" href="<?= Security::e($exportUrl) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 audit-stats" aria-label="Audit summary">
  <?php audit_stat('fa-database', $summary['total'] ?? 0, 'Total Events', 'Matching filters'); ?>
  <?php audit_stat('fa-calendar-day', $summary['today'] ?? 0, 'Today', 'Events today'); ?>
  <?php audit_stat('fa-user-lock', $summary['failed_logins'] ?? 0, 'Failed Logins', 'Auth warnings'); ?>
  <?php audit_stat('fa-triangle-exclamation', $summary['critical'] ?? 0, 'Critical', 'High attention'); ?>
  <?php audit_stat('fa-trash', $summary['destructive'] ?? 0, 'Delete Events', 'Destructive actions'); ?>
  <?php audit_stat('fa-stamp', $summary['approvals'] ?? 0, 'Approvals', 'Approve/reject flow'); ?>
  <?php audit_stat('fa-users', $summary['unique_actors'] ?? 0, 'Actors', 'Distinct users'); ?>
  <?php audit_stat('fa-network-wired', $summary['unique_ips'] ?? 0, 'IP Addresses', 'Distinct sources'); ?>
</section>

<section class="audit-quick card" aria-label="Quick audit filters">
  <?php foreach (['failed-login' => ['fa-user-lock', 'Failed logins'], 'destructive' => ['fa-trash', 'Deletes'], 'approvals' => ['fa-stamp', 'Approvals'], 'critical' => ['fa-triangle-exclamation', 'Critical']] as $quick => [$icon, $label]): ?>
    <a class="audit-chip<?= (($filters['quick'] ?? '') === $quick) ? ' is-active' : '' ?>" href="<?= Security::e(audit_url(array_merge($filters, ['quick' => $quick, 'page' => null]))) ?>"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i> <?= Security::e($label) ?></a>
  <?php endforeach; ?>
  <a class="audit-chip" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset all</a>
</section>

<section class="card audit-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Event Registry</h2>
      <p class="card__subtitle">Filter by actor, role, action, module, date, target, IP or details payload.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="filter-bar audit-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>">
    <div class="filter-group audit-filter__search"><label class="filter-label" for="q">Search</label><input class="form-input" id="q" name="q" type="search" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Actor, IP, action, module, details..."></div>
    <div class="filter-group"><label class="filter-label" for="user_id">User</label><select class="form-select" id="user_id" name="user_id"><option value="">All users</option><?php foreach ($users as $user): ?><option value="<?= (int)$user['id'] ?>" <?= (int)($filters['user_id'] ?? 0) === (int)$user['id'] ? 'selected' : '' ?>><?= Security::e(trim((string)$user['name']) ?: $user['email']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">All roles</option><?php foreach ($roles as $role): ?><option value="<?= Security::e($role['slug']) ?>" <?= (($filters['role'] ?? '') === $role['slug']) ? 'selected' : '' ?>><?= Security::e($role['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="action">Action</label><select class="form-select" id="action" name="action"><option value="">All actions</option><?php foreach ($actions as $action): ?><option value="<?= Security::e($action['action']) ?>" <?= (($filters['action'] ?? '') === $action['action']) ? 'selected' : '' ?>><?= Security::e(status_label($action['action'])) ?> (<?= (int)$action['total'] ?>)</option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="module">Module</label><select class="form-select" id="module" name="module"><option value="">All modules</option><?php foreach ($modules as $module): ?><option value="<?= Security::e($module['module']) ?>" <?= (($filters['module'] ?? '') === $module['module']) ? 'selected' : '' ?>><?= Security::e(status_label($module['module'])) ?> (<?= (int)$module['total'] ?>)</option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="severity">Severity</label><select class="form-select" id="severity" name="severity"><option value="">All severities</option><?php foreach (AuditLog::severityOptions() as $severity): ?><option value="<?= Security::e($severity) ?>" <?= (($filters['severity'] ?? '') === $severity) ? 'selected' : '' ?>><?= Security::e(status_label($severity)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="target_id">Target ID</label><input class="form-input" id="target_id" name="target_id" type="number" min="1" value="<?= Security::e((string)($filters['target_id'] ?? '')) ?>"></div>
    <div class="filter-group"><label class="filter-label" for="ip">IP</label><input class="form-input" id="ip" name="ip" value="<?= Security::e($filters['ip'] ?? '') ?>" placeholder="192.168..."></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" id="date_from" name="date_from" type="date" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" id="date_to" name="date_to" type="date" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="per_page">Rows</label><select class="form-select" id="per_page" name="per_page"><?php foreach ([25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table audit-table">
      <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Module / Target</th><th>IP / Route</th><th>Details</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($logs === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span><strong class="empty-state__title">No audit events found</strong><span class="empty-state__text">Change the filters or wait for system activity.</span></div></td></tr>
<?php else: ?>
<?php foreach ($logs as $log): ?>
        <tr data-audit-row data-audit-id="<?= (int)$log['id'] ?>">
          <td><strong><?= Security::e(format_datetime($log['created_at'])) ?></strong><small><?= Security::e(time_ago($log['created_at'])) ?></small></td>
          <td><strong><?= Security::e($log['actor_name']) ?></strong><small><?= Security::e(($log['actor_email'] ?: 'No email') . ' · ' . role_label($log['actor_role'] ?: 'system')) ?></small></td>
          <td><span class="badge <?= Security::e(audit_severity_badge($log['severity'])) ?>"><?= Security::e(status_label($log['severity'])) ?></span><small><?= Security::e(status_label($log['action'])) ?></small></td>
          <td><strong><?= Security::e(status_label($log['module'])) ?></strong><small>Target #<?= Security::e((string)$log['target_id']) ?></small></td>
          <td><strong><?= Security::e($log['ip'] ?: '-') ?></strong><small><?= Security::e(safe_truncate($log['route'] ?: '-', 42)) ?></small></td>
          <td><span><?= Security::e(safe_truncate($log['summary'], 86)) ?></span></td>
          <td class="table-actions">
            <button class="btn btn--icon btn--outline" type="button" data-audit-detail="<?= (int)$log['id'] ?>" title="View details" aria-label="View details"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            <a class="btn btn--icon btn--outline" href="<?= Security::e(audit_url(array_merge($filters, ['user_id' => $log['user_id'] ?: null, 'page' => null]))) ?>" title="Filter actor"><i class="fa-solid fa-user" aria-hidden="true"></i></a>
            <a class="btn btn--icon btn--outline" href="<?= Security::e(audit_url(array_merge($filters, ['ip' => $log['ip'] ?: null, 'page' => null]))) ?>" title="Filter IP"><i class="fa-solid fa-network-wired" aria-hidden="true"></i></a>
          </td>
        </tr>
<?php endforeach; ?>
<?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="Audit pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> events</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(audit_url(array_merge($filters, ['page' => max(1, $page - 1), 'per_page' => $perPage]))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(audit_url(array_merge($filters, ['page' => min($totalPages, $page + 1), 'per_page' => $perPage]))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<div class="audit-modal" data-audit-modal hidden>
  <div class="audit-modal__panel" role="dialog" aria-modal="true" aria-labelledby="auditModalTitle">
    <button class="btn btn--icon btn--outline audit-modal__close" type="button" data-audit-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    <div data-audit-modal-body><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i></span><strong class="empty-state__title">Loading event</strong></div></div>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function audit_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function audit_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget"><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number((float)$value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></article>
<?php
}

function audit_url(array $filters): string
{
    $query = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);
    return Url::to('admin/superadmin/audit-log.php' . ($query ? '?' . http_build_query($query) : ''));
}

function audit_severity_badge(string $severity): string
{
    return match ($severity) {
        'critical' => 'badge--danger',
        'warning' => 'badge--warning',
        default => 'badge--info',
    };
}
