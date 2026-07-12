<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$userId = (int)Auth::id();
$filters = InternAttendance::filters($_GET);
$defaultTarget = InternAttendance::defaultTarget($userId, (string)($filters['target_type'] ?? 'project'), (int)($filters['target_id'] ?? ($filters['project_id'] ?? 0)));
$targetType = (string)$defaultTarget['type'];
$targetId = (int)$defaultTarget['id'];
$filters['target_type'] = $targetType;
$filters['target_id'] = $targetId;
$projects = InternAttendance::attendanceTargets($userId);
$summary = InternAttendance::summary($userId, $filters['month'] ?? date('Y-m'), 0, $targetType, $targetId);
$records = InternAttendance::history($userId, $filters, 80);
$calendar = InternAttendance::calendar($userId, $filters['month'] ?? date('Y-m'), 0, $targetType, $targetId);

$pageTitle = 'My Attendance';
$pageDescription = 'Personal attendance history and monthly attendance summary.';
$adminRole = 'intern';
$csrfForm = 'attendance_signin';
$contentClass = 'intern-page intern-attendance-page';
$pageScripts = ['attendance-signin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'My Attendance'],
];

$activeInternHub = 'attendance';
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>
<section class="intern-hero card"><div><span class="sa-panel-label"><i class="fa-solid fa-calendar-check"></i> Attendance history</span><h2>My Attendance</h2><p>Review your monthly sign-ins, status and site location checks.</p></div><div class="intern-hero__actions"><a class="btn btn--primary" href="<?= Security::e(Url::to('admin/intern/sign-in.php')) ?>">Sign In</a></div></section>
<?php include __DIR__ . '/../../app/partials/admin/intern-hub.php'; ?>
<section class="intern-stat-grid"><?php intern_att_stat('fa-user-check', $summary['present'], 'Present', 'This month'); ?><?php intern_att_stat('fa-clock', $summary['late'], 'Late', 'This month'); ?><?php intern_att_stat('fa-location-crosshairs', $summary['flagged'], 'Flags', 'Needs review'); ?><?php intern_att_stat('fa-calendar-xmark', $summary['missed'], 'Missed', 'Working days'); ?><?php intern_att_stat('fa-chart-simple', $summary['attendance_percent'] . '%', 'Attendance Rate', $summary['signed_days'] . ' signed days'); ?></section>
<section class="intern-grid">
  <div class="intern-main">
    <section class="card intern-card">
      <div class="card__header"><div><h2 class="card__title">Attendance Register</h2><p class="card__subtitle">Filter by month, project and status.</p></div></div>
      <form class="intern-filter" method="get"><label><span>Month</span><input class="form-input" type="month" name="month" value="<?= Security::e($filters['month'] ?? date('Y-m')) ?>"></label><label><span>Location</span><select class="form-select" name="target" onchange="var p=this.value.split(':'); this.form.target_type.value=p[0]||'project'; this.form.target_id.value=p[1]||'0';"><?php foreach ($projects as $project): ?><?php $optionType = (string)($project['target_type'] ?? 'project'); $optionId = (int)($project['target_id'] ?? $project['id']); ?><option value="<?= Security::e($optionType . ':' . $optionId) ?>" <?= $optionType === $targetType && $optionId === $targetId ? 'selected' : '' ?>><?= Security::e(($optionType === 'work_location' ? 'Office - ' : 'Project - ') . $project['name']) ?></option><?php endforeach; ?></select><input type="hidden" name="target_type" value="<?= Security::e($targetType) ?>"><input type="hidden" name="target_id" value="<?= (int)$targetId ?>"></label><label><span>Status</span><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['present','late','geo-fail','outside-window'] as $status): ?><option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(InternAttendance::attendanceLabel($status)) ?></option><?php endforeach; ?></select></label><div><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/my-attendance.php')) ?>">Reset</a></div></form>
      <div class="table-wrap intern-table-wrap"><table class="data-table intern-table"><thead><tr><th>Date</th><th>Location</th><th>Time</th><th>Status</th><th>Review</th><th>Distance</th><th>Accuracy</th></tr></thead><tbody><?php if ($records === []): ?><tr><td colspan="7"><div class="empty-state"><strong class="empty-state__title">No attendance records found</strong></div></td></tr><?php else: foreach ($records as $row): ?><tr><td><?= Security::e(format_date($row['date'])) ?></td><td><strong><?= Security::e($row['project_name']) ?></strong><small><?= Security::e($row['constituency_name'] ?: ($row['location_type'] ?? '')) ?></small></td><td><?= Security::e($row['signin_label']) ?></td><td><span class="badge <?= Security::e($row['status_badge']) ?>"><?= Security::e($row['status_label']) ?></span></td><td><?= Security::e(status_label($row['review_status'] ?? 'pending')) ?></td><td><?= Security::e($row['distance_label']) ?></td><td><?= Security::e($row['accuracy_label']) ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
    </section>
  </div>
  <aside class="intern-side"><section class="card intern-card"><div class="card__header"><div><h2 class="card__title">Month View</h2><p class="card__subtitle"><?= Security::e($summary['month']) ?></p></div></div><div class="intern-calendar"><?php foreach ($calendar as $day): ?><div class="intern-calendar__day <?= $day['is_today'] ? 'is-today' : '' ?> <?= $day['is_future'] ? 'is-future' : '' ?> <?= $day['record'] ? 'has-record' : '' ?>"><strong><?= Security::e($day['day']) ?></strong><span><?= Security::e($day['record'] ? InternAttendance::attendanceLabel($day['record']['status']) : ($day['is_weekend'] ? 'Weekend' : '-')) ?></span></div><?php endforeach; ?></div></section></aside>
</section>
<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
<?php function intern_att_stat(string $icon, mixed $value, string $label, string $hint): void { ?><article class="intern-stat card"><span class="intern-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>"></i></span><span><strong><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><em><?= Security::e($label) ?></em><small><?= Security::e($hint) ?></small></span></article><?php } ?>
