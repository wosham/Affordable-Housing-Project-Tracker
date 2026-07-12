<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('clerk');

$userId = (int)Auth::id();
$today = date('Y-m-d');
$projects = ClerkAttendance::projects($userId);
$projectId = ClerkAttendance::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$policy = AttendancePolicy::windowPayload();
$summary = ClerkAttendance::summary($userId, $today);
$gateways = ClerkAttendance::gatewayRows($userId, $today);
$records = $projectId > 0 ? ClerkAttendance::records($userId, $today, $projectId, [], 8) : [];
$expected = $projectId > 0 ? ClerkAttendance::expectedPeople($userId, $projectId, $today) : [];
$portalAnnouncements = Announcement::activeForRole('clerk', 5, $userId);

$pageTitle = 'Clerk Dashboard';
$pageDescription = 'Clerk overview for attendance, site records and daily field controls.';
$adminRole = 'clerk';
$contentClass = 'clerk-dashboard-page';
$csrfForm = 'default';
$componentCss = ['attendance'];
$pageScripts = [];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Clerk of Works', 'url' => Url::to('admin/clerk/dashboard.php')],
    ['label' => 'Dashboard'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="clerk-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i> Clerk overview</span>
    <h2>Welcome, <?= Security::e(current_user_name()) ?></h2>
    <p><?= Security::e(date('l, d M Y')) ?>. Monitor site attendance, daily records and field exceptions from one place.</p>
  </div>
  <div class="clerk-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/attendance-live.php')) ?>"><i class="fa-solid fa-eye"></i> Live Attendance</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/clerk/attendance-gateway.php')) ?>"><i class="fa-solid fa-door-open"></i> Attendance Gateway</a>
  </div>
</section>

<section class="card clerk-policy-banner" aria-label="County Director attendance policy">
  <div class="clerk-policy-banner__copy">
    <span class="sa-panel-label"><i class="fa-solid fa-sliders" aria-hidden="true"></i> County Director policy</span>
    <strong class="clerk-policy-banner__title"><?= Security::e($policy['label']) ?></strong>
    <p class="clerk-policy-banner__status">
      <?= !empty($policy['is_within_window']) ? 'Window is open now — confirm site open on each active site.' : (!empty($policy['is_before_open']) ? 'Before open time.' : (!empty($policy['is_after_close']) ? 'Closed for today.' : 'Not an active attendance day (Mon–Fri).')) ?>
      Clerks confirm site open only; times are set by County Director.
    </p>
  </div>
  <div class="clerk-policy-chips">
    <article><small>Opens</small><strong><?= Security::e($policy['open_time']) ?></strong></article>
    <article><small>Expected</small><strong><?= Security::e($policy['expected_time']) ?></strong></article>
    <article><small>Late after</small><strong><?= Security::e($policy['late_after']) ?></strong></article>
    <article><small>Closes</small><strong><?= Security::e($policy['close_time']) ?></strong></article>
  </div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/dashboard-announcements.php'; ?>

<section class="clerk-stats" aria-label="Attendance summary">
  <?php clerk_stat('fa-building-circle-check', $summary['assigned_projects'], 'Assigned Projects', 'Active site coverage'); ?>
  <?php clerk_stat('fa-door-open', $summary['open_gateways'], 'Open Windows', 'Policy window accepting sign-ins'); ?>
  <?php clerk_stat('fa-user-check', $summary['signed_in'], 'Signed In', 'Interns + clerks today'); ?>
  <?php clerk_stat('fa-user-clock', $summary['missing'], 'Not Signed In', 'Expected but not recorded'); ?>
  <?php clerk_stat('fa-location-crosshairs', $summary['flagged'], 'Location Flags', 'Needs review'); ?>
  <?php clerk_stat('fa-chart-simple', $summary['attendance_percent'] . '%', 'Coverage', 'Attendance completion'); ?>
</section>

<section class="clerk-layout">
  <main class="card clerk-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title">Today&apos;s gateway status</h2>
        <p class="card__subtitle">Policy window and confirm status for your assigned projects.</p>
      </div>
      <span class="badge badge--info"><?= count($gateways) ?> sites</span>
    </div>
    <div class="clerk-project-list">
      <?php if ($gateways === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No assigned sites</strong><span class="empty-state__text">Assigned projects will appear here.</span></div>
      <?php else: foreach ($gateways as $row): ?>
        <?php
          $open = !empty($row['is_effectively_open']);
          $confirmed = !empty($row['clerk_confirmed']);
          $statusLabel = $open ? ($confirmed ? 'Open · confirmed' : 'Open · confirm') : 'Closed';
          $closeLabel = $row['policy_close_time'] ?: ($row['closes_at'] ? date('H:i', strtotime((string)$row['closes_at'])) : $policy['close_time']);
        ?>
        <article class="clerk-project-row">
          <span class="clerk-project-row__status <?= $open ? 'is-open' : 'is-closed' ?>"><i class="fa-solid <?= $open ? 'fa-door-open' : 'fa-door-closed' ?>"></i></span>
          <div>
            <strong><?= Security::e($row['project_name']) ?></strong>
            <small><?= Security::e(trim(($row['constituency_name'] ?? '') . ' / ' . ($row['ward_name'] ?? ''), ' /') ?: 'Project site') ?></small>
          </div>
          <div><strong><?= (int)$row['signed_in'] ?></strong><small>Signed in</small></div>
          <div><strong><?= Security::e($statusLabel) ?></strong><small>Closes <?= Security::e((string)$closeLabel) ?></small></div>
          <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/clerk/attendance-gateway.php?project_id=' . (int)$row['project_id'])) ?>">Manage</a>
        </article>
      <?php endforeach; endif; ?>
    </div>
  </main>

  <aside class="card clerk-panel">
    <h2>Quick Actions</h2>
    <div class="clerk-actions">
      <a href="<?= Security::e(Url::to('admin/clerk/attendance-gateway.php')) ?>"><i class="fa-solid fa-door-open"></i><span>Confirm site open</span></a>
      <a href="<?= Security::e(Url::to('admin/clerk/attendance-live.php')) ?>"><i class="fa-solid fa-eye"></i><span>View live list</span></a>
      <a href="<?= Security::e(Url::to('admin/clerk/daily-diary.php')) ?>"><i class="fa-solid fa-book"></i><span>Daily diary</span></a>
      <a href="<?= Security::e(Url::to('admin/clerk/weather-log.php')) ?>"><i class="fa-solid fa-cloud-sun"></i><span>Weather log</span></a>
      <a href="<?= Security::e(Url::to('admin/clerk/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Message site interns</span></a>
      <a href="<?= Security::e(Url::to('admin/clerk/hs-incidents.php')) ?>"><i class="fa-solid fa-triangle-exclamation"></i><span>Report incident</span></a>
    </div>
  </aside>
</section>

<section class="clerk-layout clerk-layout--balanced">
  <main class="card clerk-panel">
    <div class="card__header">
      <div><h2 class="card__title">Recent Sign-ins</h2><p class="card__subtitle">Latest attendance activity for the selected site.</p></div>
      <?php if ($projectId): ?><a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/clerk/attendance-live.php?project_id=' . $projectId)) ?>">View all</a><?php endif; ?>
    </div>
    <div class="clerk-table-wrap">
      <table class="clerk-table">
        <thead><tr><th>Name</th><th>Role</th><th>Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php if ($records === []): ?>
          <tr><td colspan="4"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No sign-ins yet</strong><span class="empty-state__text">Live attendance will appear after users sign in.</span></div></td></tr>
        <?php else: foreach ($records as $record): ?>
          <tr>
            <td><strong><?= Security::e($record['user_name'] ?? '-') ?></strong><small><?= Security::e($record['email'] ?? '') ?></small></td>
            <td><?= Security::e(role_label((string)($record['role_slug'] ?? ''))) ?></td>
            <td><?= Security::e($record['signin_time'] ? date('H:i', strtotime((string)$record['signin_time'])) : '-') ?></td>
            <td><span class="badge <?= Security::e(status_badge_class((string)($record['status'] ?? ''))) ?>"><?= Security::e(status_label((string)($record['status'] ?? '-'))) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <aside class="card clerk-panel">
    <h2>Expected Today</h2>
    <div class="clerk-people-list">
      <?php if ($expected === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">No assigned personnel</strong><span class="empty-state__text">Interns and clerks will appear once assigned.</span></div>
      <?php else: foreach (array_slice($expected, 0, 10) as $person): ?>
        <span><strong><?= Security::e($person['user_name']) ?></strong><small><?= Security::e(role_label((string)$person['role_slug'])) ?> / <?= $person['attendance_id'] ? 'Signed in' : 'Pending' ?></small></span>
      <?php endforeach; endif; ?>
    </div>
  </aside>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function clerk_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="clerk-stat card"><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></article>';
}
