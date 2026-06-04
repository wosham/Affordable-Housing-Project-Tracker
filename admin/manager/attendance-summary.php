<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$date = Security::cleanString((string)($_GET['date'] ?? date('Y-m-d')));
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
$filters = array_filter([
    'date' => $date,
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'gps' => Security::cleanString((string)($_GET['gps'] ?? '')),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

if (!in_array((string)($filters['role'] ?? ''), ManagerAttendance::ROLES, true)) {
    unset($filters['role']);
}
if (!in_array((string)($filters['status'] ?? ''), ManagerAttendance::STATUSES, true)) {
    unset($filters['status']);
}
if (!in_array((string)($filters['gps'] ?? ''), ['', 'flagged'], true)) {
    unset($filters['gps']);
}

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerAttendance::countRecords($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$projects = ManagerAttendance::projects($userId, $role);
$stats = ManagerAttendance::summary($userId, $role, $date, $filters);
$coverage = ManagerAttendance::projectCoverage($userId, $role, $date, $filters);
$records = ManagerAttendance::records($userId, $role, $filters, $perPage, $offset);
$exceptions = ManagerAttendance::exceptions($userId, $role, $date, $filters);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($records), $total);

$pageTitle = 'Attendance Summary';
$pageDescription = 'Monitor assigned site attendance, gateway coverage and GPS exceptions.';
$adminRole = 'manager';
$contentClass = 'manager-attendance-page';
$componentCss = ['manager-attendance'];
$pageScripts = ['manager-attendance'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Attendance Summary'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="manager-attendance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Site attendance</span>
    <h2>Attendance Summary</h2>
    <p>Monitor assigned site attendance, gateway coverage and GPS exceptions.</p>
  </div>
  <form class="manager-attendance-date" method="get">
    <label><span>Report date</span><input class="form-input" type="date" name="date" value="<?= Security::e($date) ?>"></label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Load Day</button>
  </form>
</section>

<section class="stat-grid stat-grid--4 manager-attendance-stats" aria-label="Attendance summary">
  <?php manager_attendance_stat('fa-building', $stats['assigned_projects'], 'Assigned Projects', 'Manager portfolio'); ?>
  <?php manager_attendance_stat('fa-door-open', $stats['open_gateways'], 'Open Gateways', format_number($stats['gateways_today']) . ' opened today'); ?>
  <?php manager_attendance_stat('fa-users', $stats['expected_people'], 'Expected Staff', 'Active assignments'); ?>
  <?php manager_attendance_stat('fa-user-check', $stats['present'], 'Present', format_number($stats['total_records']) . ' records'); ?>
  <?php manager_attendance_stat('fa-user-clock', $stats['missing_signins'], 'Missing Sign-ins', 'No record today'); ?>
  <?php manager_attendance_stat('fa-clock', $stats['late'], 'Late', 'After window'); ?>
  <?php manager_attendance_stat('fa-location-dot', $stats['geo_fail'] + $stats['outside_window'], 'GPS Flags', 'Fence or window'); ?>
  <?php manager_attendance_stat('fa-chart-simple', $stats['attendance_percent'] . '%', 'Attendance Rate', 'Assigned staff'); ?>
</section>

<section class="manager-attendance-layout">
  <div class="manager-attendance-main">
    <section class="card manager-attendance-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">Project Coverage</h2>
          <p class="card__subtitle">Gateway, sign-in and GPS coverage for assigned projects.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('api/attendance/daily-report.php?' . http_build_query($filters))) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-code" aria-hidden="true"></i> JSON Report</a>
      </div>
      <div class="table-wrap">
        <table class="data-table manager-attendance-table">
          <thead><tr><th>Project</th><th>Geo</th><th>Gateway</th><th>Expected</th><th>Signed</th><th>Missing</th><th>Rate</th><th>Flags</th><th>Actions</th></tr></thead>
          <tbody>
<?php if ($coverage === []): ?>
            <tr><td colspan="9"><div class="empty-state"><strong class="empty-state__title">No assigned attendance projects</strong><span class="empty-state__text">Project assignments will appear here once configured.</span></div></td></tr>
<?php else: foreach ($coverage as $row): $expected = (int)$row['expected_people']; $signed = (int)$row['signed_people']; $pct = $expected > 0 ? percentage(($signed / $expected) * 100) : 0; ?>
            <tr>
              <td><strong><?= Security::e($row['project_name']) ?></strong><small><?= Security::e($row['constituency_name'] ?: 'Assigned project') ?></small></td>
              <td><span class="badge <?= $row['geo_id'] ? Security::e(status_badge_class($row['geo_status'] ?: 'configured')) : 'badge--warning' ?>"><?= Security::e($row['geo_id'] ? status_label($row['geo_status'] ?: 'configured') : 'Missing') ?></span></td>
              <td><span class="badge <?= (int)($row['is_open'] ?? 0) === 1 ? 'badge--success' : ($row['gateway_id'] ? 'badge--neutral' : 'badge--warning') ?>"><?= Security::e((int)($row['is_open'] ?? 0) === 1 ? 'Open' : ($row['gateway_id'] ? 'Closed' : 'Not Opened')) ?></span><small><?= Security::e($row['opened_by_name'] ?: '-') ?></small></td>
              <td><?= Security::e(format_number($expected)) ?></td>
              <td><?= Security::e(format_number($signed)) ?></td>
              <td><?= Security::e(format_number(max(0, $expected - $signed))) ?></td>
              <td><div class="manager-attendance-meter"><span style="width: <?= (int)$pct ?>%"></span></div><small><?= Security::e(format_percentage($pct)) ?></small></td>
              <td><?= Security::e(format_number($row['gps_flags'] ?? 0)) ?></td>
              <td><button class="btn btn--icon btn--outline" type="button" data-attendance-project="<?= (int)$row['project_id'] ?>" data-attendance-date="<?= Security::e($date) ?>" title="View details" aria-label="View details"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></td>
            </tr>
<?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card manager-attendance-card">
      <div class="card__header">
        <div><h2 class="card__title">Attendance Records</h2><p class="card__subtitle">Filter daily sign-ins by project, role, status and GPS trust.</p></div>
        <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
      </div>
      <form class="filter-bar manager-attendance-filter" method="get">
        <input type="hidden" name="date" value="<?= Security::e($date) ?>">
        <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Name, email or project..."></div>
        <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">All roles</option><?php foreach (array_filter(ManagerAttendance::ROLES) as $option): ?><option value="<?= Security::e($option) ?>" <?= (($filters['role'] ?? '') === $option) ? 'selected' : '' ?>><?= Security::e(role_label($option)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (array_filter(ManagerAttendance::STATUSES) as $option): ?><option value="<?= Security::e($option) ?>" <?= (($filters['status'] ?? '') === $option) ? 'selected' : '' ?>><?= Security::e(status_label($option)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="gps">GPS</label><select class="form-select" id="gps" name="gps"><option value="">All</option><option value="flagged" <?= (($filters['gps'] ?? '') === 'flagged') ? 'selected' : '' ?>>Flagged only</option></select></div>
        <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/attendance-summary.php')) ?>">Reset</a></div>
      </form>
      <div class="table-wrap">
        <table class="data-table manager-attendance-table">
          <thead><tr><th>Staff</th><th>Role</th><th>Project</th><th>Sign-in</th><th>Gateway</th><th>GPS / Distance</th><th>Status</th><th>Review</th></tr></thead>
          <tbody>
<?php if ($records === []): ?>
            <tr><td colspan="8"><div class="empty-state"><strong class="empty-state__title">No attendance records found</strong><span class="empty-state__text">Records appear after gateways are opened and staff sign in.</span></div></td></tr>
<?php else: foreach ($records as $record): ?>
            <tr>
              <td><strong><?= Security::e($record['user_name']) ?></strong><small><?= Security::e($record['email']) ?></small></td>
              <td><?= Security::e(role_label($record['role_slug'])) ?></td>
              <td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['constituency_name'] ?: '-') ?></small></td>
              <td><?= Security::e($record['signin_time'] ?: '-') ?><small><?= Security::e(format_date($record['date'])) ?></small></td>
              <td><span class="badge <?= (int)($record['gateway_is_open'] ?? 0) === 1 ? 'badge--success' : 'badge--neutral' ?>"><?= Security::e((int)($record['gateway_is_open'] ?? 0) === 1 ? 'Open' : 'Closed') ?></span><small><?= Security::e(!empty($record['opened_at']) ? format_datetime($record['opened_at']) : '-') ?></small></td>
              <td><strong><?= Security::e($record['distance_from_site_m'] !== null ? format_number($record['distance_from_site_m'], 1) . 'm' : '-') ?></strong><small><?= Security::e($record['site_name'] ?: 'No site name') ?></small></td>
              <td><span class="badge <?= Security::e(status_badge_class($record['status'])) ?>"><?= Security::e(status_label($record['status'])) ?></span></td>
              <td><?= Security::e(status_label($record['review_status'] ?? 'pending')) ?></td>
            </tr>
<?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
<?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Attendance pagination">
        <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> records</p>
        <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_attendance_page_url(max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_attendance_page_url(min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
      </nav>
<?php endif; ?>
    </section>
  </div>

  <aside class="manager-attendance-side" aria-label="Attendance exceptions">
    <?php manager_attendance_exception_panel('Missing Sign-ins', 'fa-user-xmark', $exceptions['missing'], 'No missing assigned staff'); ?>
    <?php manager_attendance_exception_panel('GPS Exceptions', 'fa-location-dot', $exceptions['gps'], 'No GPS exceptions'); ?>
    <?php manager_attendance_exception_panel('No Gateway Opened', 'fa-door-closed', $exceptions['noGateway'], 'All assigned projects opened'); ?>
    <?php manager_attendance_exception_panel('Geo-fence Issues', 'fa-map-pin', $exceptions['geo'], 'No geo-fence issues'); ?>
  </aside>
</section>

<div class="manager-attendance-detail" data-attendance-detail hidden>
  <div class="manager-attendance-detail__panel">
    <div class="manager-attendance-detail__header">
      <div><span class="sa-panel-label">Project attendance</span><h2 data-attendance-title>Attendance details</h2><p data-attendance-summary></p></div>
      <button class="btn btn--icon btn--ghost" type="button" data-attendance-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="manager-attendance-detail__body">
      <div class="manager-attendance-detail-grid" data-attendance-metrics></div>
      <section><h3>Signed-in Staff</h3><div class="manager-attendance-mini-list" data-attendance-records></div></section>
      <section><h3>Exceptions</h3><div class="manager-attendance-mini-list" data-attendance-exceptions></div></section>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_attendance_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget"><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></article>
<?php
}

function manager_attendance_exception_panel(string $title, string $icon, array $rows, string $empty): void
{
?>
  <section class="card manager-attendance-panel">
    <div class="card__header"><div><h2 class="card__title"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i> <?= Security::e($title) ?></h2></div></div>
    <div class="manager-attendance-mini-list">
<?php if ($rows === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title"><?= Security::e($empty) ?></strong></div>
<?php else: foreach (array_slice($rows, 0, 5) as $row): ?>
      <span><strong><?= Security::e($row['project_name'] ?? $row['user_name'] ?? 'Record') ?></strong><small><?= Security::e($row['email'] ?? $row['constituency_name'] ?? status_label($row['status'] ?? $row['geo_status'] ?? 'needs-review')) ?></small></span>
<?php endforeach; endif; ?>
    </div>
  </section>
<?php
}

function manager_attendance_page_url(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;
    return Url::to('admin/manager/attendance-summary.php' . ($query ? '?' . http_build_query($query) : ''));
}
