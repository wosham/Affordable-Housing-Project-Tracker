<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$userId = (int)Auth::id();
$projectId = InternAttendance::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$projects = InternAttendance::projects($userId);
$today = InternAttendance::todayStatus($userId, $projectId);
$summary = InternAttendance::summary($userId, date('Y-m'), $projectId);
$history = InternAttendance::history($userId, ['month' => date('Y-m'), 'project_id' => $projectId], 6);
$workSummary = $projectId > 0 ? InternProjectWork::summary($userId, $projectId) : [];
$portalAnnouncements = Announcement::activeForRole('intern', 5, (int)(Auth::id() ?? 0));
$siteName = (string)($today['project']['name'] ?? ($projects[0]['name'] ?? 'No assigned site'));

$pageTitle = 'Intern Dashboard';
$pageDescription = 'Intern overview for attendance, assigned site and daily activity.';
$adminRole = 'intern';
$csrfForm = 'attendance_signin';
$contentClass = 'intern-page intern-dashboard-page';
$pageScripts = ['attendance-signin'];
$activeInternHub = 'dashboard';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern'],
    ['label' => 'Dashboard'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="intern-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i> Intern overview</span>
    <h2>Welcome, <?= Security::e(Auth::name()) ?></h2>
    <p><?= Security::e(format_date(date('Y-m-d'))) ?>. Your assigned site: <strong><?= Security::e($siteName) ?></strong>. Track attendance and site work from one place.</p>
  </div>
  <div class="intern-hero__actions">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/intern/sign-in.php')) ?>"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sign In</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/my-attendance.php')) ?>"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> My Attendance</a>
  </div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/dashboard-announcements.php'; ?>
<?php include __DIR__ . '/../../app/partials/admin/intern-hub.php'; ?>

<section class="intern-stat-grid" aria-label="Intern attendance summary">
  <article class="intern-stat card">
    <span class="intern-stat__icon"><i class="fa-solid fa-door-open" aria-hidden="true"></i></span>
    <span><strong><?= Security::e($today['is_open'] ? 'Open' : 'Closed') ?></strong><em>Gateway</em><small><?= Security::e((string)($today['gateway']['closes_at'] ?? 'No active window')) ?></small></span>
  </article>
  <article class="intern-stat card">
    <span class="intern-stat__icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span>
    <span><strong><?= Security::e($today['status_label']) ?></strong><em>Today</em><small><?= Security::e($siteName) ?></small></span>
  </article>
  <article class="intern-stat card">
    <span class="intern-stat__icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(format_number($summary['signed_days'])) ?></strong><em>Signed Days</em><small><?= Security::e($summary['attendance_percent'] . '% attendance') ?></small></span>
  </article>
  <article class="intern-stat card">
    <span class="intern-stat__icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(format_number($summary['late'])) ?></strong><em>Late</em><small>This month</small></span>
  </article>
  <article class="intern-stat card">
    <span class="intern-stat__icon"><i class="fa-solid fa-note-sticky" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(format_number($workSummary['entries_total'] ?? 0)) ?></strong><em>Site Notes</em><small><?= Security::e(format_number($workSummary['photos_total'] ?? 0)) ?> photos</small></span>
  </article>
</section>

<section class="intern-grid">
  <div class="intern-main">
    <section class="card intern-card intern-signin-panel">
      <div class="intern-signin-panel__copy">
        <span class="badge <?= Security::e($today['status_badge']) ?>"><?= Security::e($today['status_label']) ?></span>
        <h2><?= Security::e($siteName) ?></h2>
        <p><?= Security::e(trim(($today['project']['constituency_name'] ?? '') . ' / ' . ($today['project']['ward_name'] ?? ''), ' /') ?: 'Assigned site') ?></p>
      </div>
<?php if ($today['already_signed']): ?>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/my-attendance.php')) ?>"><i class="fa-solid fa-check" aria-hidden="true"></i> View Record</a>
<?php elseif ($today['is_open'] && $projectId > 0): ?>
      <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/intern/sign-in.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sign In Now</a>
<?php else: ?>
      <button class="btn btn--outline" type="button" disabled><i class="fa-solid fa-lock" aria-hidden="true"></i> Gateway Closed</button>
<?php endif; ?>
    </section>

    <section class="card intern-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">Recent Attendance</h2>
          <p class="card__subtitle">Your latest sign-in records for this month.</p>
        </div>
        <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/intern/my-attendance.php')) ?>">View all</a>
      </div>
      <div class="table-wrap intern-table-wrap">
        <table class="data-table intern-table">
          <thead>
            <tr><th>Date</th><th>Project</th><th>Time</th><th>Status</th><th>Distance</th></tr>
          </thead>
          <tbody>
<?php if ($history === []): ?>
            <tr><td colspan="5"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No attendance records yet</strong></div></td></tr>
<?php else: foreach ($history as $row): ?>
            <tr>
              <td data-label="Date"><?= Security::e(format_date($row['date'])) ?></td>
              <td data-label="Project"><strong><?= Security::e($row['project_name'] ?? '-') ?></strong></td>
              <td data-label="Time"><?= Security::e($row['signin_label']) ?></td>
              <td data-label="Status"><span class="badge <?= Security::e($row['status_badge']) ?>"><?= Security::e($row['status_label']) ?></span></td>
              <td data-label="Distance"><?= Security::e($row['distance_label']) ?></td>
            </tr>
<?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <aside class="intern-side">
    <section class="card intern-card">
      <div class="card__header"><div><h2 class="card__title">Quick Actions</h2><p class="card__subtitle">Common intern tools.</p></div></div>
      <div class="intern-action-list">
        <a href="<?= Security::e(Url::to('admin/intern/sign-in.php')) ?>"><i class="fa-solid fa-location-dot"></i> Attendance sign-in</a>
        <a href="<?= Security::e(Url::to('admin/intern/my-attendance.php')) ?>"><i class="fa-solid fa-calendar-check"></i> My attendance</a>
        <a href="<?= Security::e(Url::to('admin/intern/my-project.php')) ?>"><i class="fa-solid fa-building"></i> My project</a>
        <a href="<?= Security::e(Url::to('admin/intern/site-data-entry.php')) ?>"><i class="fa-solid fa-pen-to-square"></i> Site data entry</a>
        <a href="<?= Security::e(Url::to('admin/intern/upload-photos.php')) ?>"><i class="fa-solid fa-camera"></i> Upload photos</a>
        <a href="<?= Security::e(Url::to('admin/intern/messages.php')) ?>"><i class="fa-solid fa-comments"></i> Messages</a>
      </div>
    </section>
<?php if (count($projects) > 1): ?>
    <section class="card intern-card">
      <div class="card__header"><div><h2 class="card__title">Site notice</h2></div></div>
      <p class="intern-muted">You currently have more than one active site in history. Policy is <strong>one site per intern</strong> — contact Superadmin if your assignment looks wrong.</p>
    </section>
<?php endif; ?>
  </aside>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
