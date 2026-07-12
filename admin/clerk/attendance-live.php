<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('clerk');

$userId = (int)Auth::id();
$date = Security::cleanString((string)($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$projects = ClerkAttendance::projects($userId);
$projectId = ClerkAttendance::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'role' => in_array((string)($_GET['role'] ?? ''), ['clerk', 'intern'], true) ? (string)$_GET['role'] : '',
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
];
if ($projectId > 0) {
    AttendancePolicy::ensureProjectWindow($projectId, $date);
}
$policy = AttendancePolicy::windowPayload();
$summary = $projectId ? ClerkAttendance::summary($userId, $date, $projectId) : ClerkAttendance::summary($userId, $date);
$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$total = ClerkAttendance::countRecords($userId, $date, $projectId, $filters);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$records = ClerkAttendance::records($userId, $date, $projectId, $filters, $perPage, ($page - 1) * $perPage);
$expected = $projectId ? ClerkAttendance::expectedPeople($userId, $projectId, $date) : [];
$gateway = $projectId ? AttendanceGateway::forProjectDate($projectId, $date) : null;
$isOpen = AttendancePolicy::isEffectivelyOpen($gateway);

$pageTitle = 'Live Attendance';
$pageDescription = 'Live attendance monitoring for assigned clerk sites.';
$adminRole = 'clerk';
$contentClass = 'clerk-attendance-page';
$csrfForm = 'attendance_gateway';
$componentCss = ['attendance'];
$pageScripts = ['attendance-gateway'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Clerk of Works', 'url' => Url::to('admin/clerk/dashboard.php')],
    ['label' => 'Live Attendance'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="clerk-hero card" data-attendance-live data-status-url="<?= Security::e(Url::to('api/attendance/gateway-status.php')) ?>" data-project-id="<?= (int)$projectId ?>" data-gateway-date="<?= Security::e($date) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-eye" aria-hidden="true"></i> Live attendance</span>
    <h2>Live Attendance</h2>
    <p>Monitor sign-ins, location flags and pending personnel for your assigned site.</p>
    <span class="clerk-live-status <?= $isOpen ? 'is-open' : 'is-closed' ?>" data-gateway-status>
      <?= $isOpen ? 'Policy window open — accepting sign-ins.' : 'Attendance window closed.' ?>
    </span>
  </div>
  <div class="clerk-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/attendance-gateway.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-door-open"></i> Gateway</a>
    <button class="btn btn--outline" type="button" data-live-refresh><i class="fa-solid fa-rotate"></i> Refresh</button>
  </div>
</section>

<section class="card clerk-policy-banner" aria-label="County Director attendance policy">
  <div class="clerk-policy-banner__copy">
    <span class="sa-panel-label"><i class="fa-solid fa-sliders" aria-hidden="true"></i> County Director policy</span>
    <strong class="clerk-policy-banner__title" data-policy-label><?= Security::e($policy['label']) ?></strong>
    <p class="clerk-policy-banner__status">One sign-in per person per day · interns and clerks only. Times are set by the County Director.</p>
  </div>
  <div class="clerk-policy-chips">
    <article><small>Opens</small><strong><?= Security::e($policy['open_time']) ?></strong></article>
    <article><small>Expected</small><strong><?= Security::e($policy['expected_time']) ?></strong></article>
    <article><small>Closes</small><strong><?= Security::e($policy['close_time']) ?></strong></article>
    <article><small>Coverage</small><strong><?= (int)$summary['attendance_percent'] ?>%</strong></article>
  </div>
</section>

<section class="clerk-stats" data-live-summary aria-label="Live attendance summary">
  <?php clerk_live_stat('fa-user-check', $summary['signed_in'], 'Signed In', 'Today'); ?>
  <?php clerk_live_stat('fa-circle-check', $summary['present'], 'Present', 'On time'); ?>
  <?php clerk_live_stat('fa-user-clock', $summary['late'], 'Late', 'After expected time'); ?>
  <?php clerk_live_stat('fa-location-crosshairs', $summary['flagged'], 'Flags', 'Needs review'); ?>
  <?php clerk_live_stat('fa-user-minus', $summary['missing'], 'Pending', 'No sign-in yet'); ?>
  <?php clerk_live_stat('fa-chart-simple', $summary['attendance_percent'] . '%', 'Coverage', 'Completion'); ?>
</section>

<?php if ($projects === []): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Live attendance appears once a project is assigned to you.</span></div>
<?php else: ?>

<form class="card clerk-filter" method="get">
  <label><span>Project</span><select name="project_id"><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)$project['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
  <label><span>Date</span><input type="date" name="date" value="<?= Security::e($date) ?>"></label>
  <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Name, email or project..."></label>
  <label><span>Role</span><select name="role"><option value="">All roles</option><?php foreach (['clerk', 'intern'] as $role): ?><option value="<?= Security::e($role) ?>" <?= $filters['role'] === $role ? 'selected' : '' ?>><?= Security::e(role_label($role)) ?></option><?php endforeach; ?></select></label>
  <label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach (['present', 'late', 'geo-fail', 'outside-window', 'absent'] as $status): ?><option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
  <div class="clerk-filter__actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/attendance-live.php')) ?>">Reset</a></div>
</form>

<section class="clerk-layout">
  <main class="card clerk-panel">
    <div class="card__header">
      <div><h2 class="card__title">Sign-in Register</h2><p class="card__subtitle">Auto-refreshes while the page is open.</p></div>
      <span class="badge badge--info" data-live-count><?= format_number($total) ?> records</span>
    </div>
    <div class="clerk-table-wrap">
      <table class="clerk-table">
        <thead><tr><th>Name</th><th>Project</th><th>Role</th><th>Time</th><th>Location</th><th>Status</th></tr></thead>
        <tbody data-live-records>
        <?php if ($records === []): ?>
          <tr><td colspan="6"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No attendance records found</strong><span class="empty-state__text">Records will appear when assigned personnel sign in.</span></div></td></tr>
        <?php else: foreach ($records as $record): ?>
          <?php clerk_live_row($record); ?>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($total > 0): ?>
      <?php
        $from = min($total, (($page - 1) * $perPage) + 1);
        $to = min($total, $page * $perPage);
        $query = $_GET;
      ?>
      <div class="pagination">
        <span>Showing <?= format_number($from) ?>-<?= format_number($to) ?> of <?= format_number($total) ?></span>
        <div>
          <?php $query['page'] = max(1, $page - 1); ?>
          <a class="btn btn--sm btn--outline<?= $page <= 1 ? ' is-disabled' : '' ?>" href="?<?= Security::e(http_build_query($query)) ?>"><i class="fa-solid fa-chevron-left"></i></a>
          <span class="btn btn--sm btn--primary"><?= $page ?> / <?= $pages ?></span>
          <?php $query['page'] = min($pages, $page + 1); ?>
          <a class="btn btn--sm btn--outline<?= $page >= $pages ? ' is-disabled' : '' ?>" href="?<?= Security::e(http_build_query($query)) ?>"><i class="fa-solid fa-chevron-right"></i></a>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <aside class="card clerk-panel">
    <h2>Pending Sign-ins</h2>
    <div class="clerk-people-list" data-expected-list>
      <?php
      $pending = array_values(array_filter($expected, static fn (array $person): bool => empty($person['attendance_id'])));
      ?>
      <?php if ($pending === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No pending people</strong><span class="empty-state__text">Everyone assigned to this site has a record.</span></div>
      <?php else: foreach ($pending as $person): ?>
        <span><strong><?= Security::e($person['user_name']) ?></strong><small><?= Security::e(role_label((string)$person['role_slug'])) ?> / <?= Security::e($person['email'] ?? '') ?></small></span>
      <?php endforeach; endif; ?>
    </div>
  </aside>
</section>

<?php endif; ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function clerk_live_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="clerk-stat card"><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></article>';
}

function clerk_live_row(array $record): void
{
    $location = isset($record['distance_from_site_m']) && $record['distance_from_site_m'] !== null
        ? number_format((float)$record['distance_from_site_m'], 1) . ' m'
        : '-';
    $accuracy = isset($record['accuracy_meters']) && $record['accuracy_meters'] !== null
        ? 'Accuracy ' . number_format((float)$record['accuracy_meters'], 0) . ' m'
        : '';
    echo '<tr>';
    echo '<td><strong>' . Security::e($record['user_name'] ?? '-') . '</strong><small>' . Security::e($record['email'] ?? '') . '</small></td>';
    echo '<td><strong>' . Security::e($record['project_name'] ?? '-') . '</strong><small>' . Security::e(trim(($record['constituency_name'] ?? '') . ' / ' . ($record['ward_name'] ?? ''), ' /')) . '</small></td>';
    echo '<td>' . Security::e(role_label((string)($record['role_slug'] ?? ''))) . '</td>';
    echo '<td>' . Security::e(!empty($record['signin_time']) ? date('H:i', strtotime((string)$record['signin_time'])) : '-') . '</td>';
    echo '<td><strong>' . Security::e($location) . '</strong><small>' . Security::e($accuracy) . '</small></td>';
    echo '<td><span class="badge ' . Security::e(status_badge_class((string)($record['status'] ?? ''))) . '">' . Security::e(status_label((string)($record['status'] ?? '-'))) . '</span><small>' . Security::e(status_label((string)($record['review_status'] ?? 'pending'))) . '</small></td>';
    echo '</tr>';
}
