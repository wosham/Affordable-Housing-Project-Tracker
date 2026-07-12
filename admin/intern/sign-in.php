<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$userId = (int)Auth::id();
$requestedType = Security::cleanString((string)($_GET['target_type'] ?? 'project'));
$requestedId = Security::cleanInt($_GET['target_id'] ?? ($_GET['project_id'] ?? 0));
$defaultTarget = InternAttendance::defaultTarget($userId, $requestedType, $requestedId);
$targetType = (string)$defaultTarget['type'];
$targetId = (int)$defaultTarget['id'];
$projects = InternAttendance::attendanceTargets($userId);
$today = InternAttendance::todayTargetStatus($userId, $targetType, $targetId);

$pageTitle = 'Attendance Sign In';
$pageDescription = 'Record your daily site attendance using the open attendance gateway.';
$adminRole = 'intern';
$csrfForm = 'attendance_signin';
$contentClass = 'intern-page intern-signin-page';
$pageScripts = ['attendance-signin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'Sign In'],
];

$activeInternHub = 'sign-in';
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<?php $policy = $today['policy'] ?? AttendancePolicy::windowPayload(); ?>
<section class="intern-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Attendance sign-in</span>
    <h2>Site Attendance</h2>
    <p>One sign-in per person per day for yourself only. Policy window (County Director): <strong><?= Security::e($policy['open_time'] ?? '07:00') ?>–<?= Security::e($policy['close_time'] ?? '08:30') ?></strong>, expected <strong><?= Security::e($policy['expected_time'] ?? '08:00') ?></strong>, late after expected until close. Mon–Fri.</p>
  </div>
  <div class="intern-hero__actions"><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/dashboard.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/intern-hub.php'; ?>

<section class="card clerk-policy-banner" style="margin-bottom:1rem">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Today&apos;s policy</span>
    <strong><?= Security::e($policy['label'] ?? 'Attendance policy') ?></strong>
    <small><?= !empty($policy['is_within_window']) ? 'Window is open now.' : (!empty($policy['is_before_open']) ? 'Before open time.' : (!empty($policy['is_after_close']) ? 'Window closed for today.' : 'Not an active attendance day.')) ?></small>
  </div>
</section>

<section class="intern-signin-layout">
  <div class="card intern-card intern-signin-card <?= $today['is_open'] ? 'is-open' : 'is-closed' ?>">
    <div class="intern-signin-card__status">
      <span class="intern-gps-dot" data-gps-dot></span>
      <span data-gps-label><?= Security::e($today['status_label']) ?></span>
    </div>
    <h2><?= Security::e($today['project']['name'] ?? 'No assigned attendance location') ?></h2>
    <p><?= Security::e($today['is_open'] ? 'Gateway is open. Confirm your location before signing in.' : 'Attendance gateway is currently closed for this location.') ?></p>

    <form class="intern-signin-form" action="<?= Security::e(Url::to('api/attendance/sign-in.php')) ?>" method="post" data-attendance-signin data-gateway-open="<?= $today['is_open'] ? '1' : '0' ?>" data-already-signed="<?= $today['already_signed'] ? '1' : '0' ?>">
      <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token('attendance_signin')) ?>">
      <label class="form-field"><span class="form-label">Attendance location</span><select class="form-select" name="target" <?= count($projects) <= 1 ? 'disabled' : '' ?> onchange="var p=this.value.split(':'); this.form.target_type.value=p[0]||'project'; this.form.target_id.value=p[1]||'0'; this.form.method='get'; this.form.action='<?= Security::e(Url::to('admin/intern/sign-in.php')) ?>'; this.form.submit();"><?php foreach ($projects as $project): ?><?php $optionType = (string)($project['target_type'] ?? 'project'); $optionId = (int)($project['target_id'] ?? $project['id']); ?><option value="<?= Security::e($optionType . ':' . $optionId) ?>" <?= $optionType === $targetType && $optionId === $targetId ? 'selected' : '' ?>><?= Security::e(($optionType === 'work_location' ? 'Office - ' : 'Project - ') . $project['name']) ?></option><?php endforeach; ?></select></label>
      <input type="hidden" name="target_type" value="<?= Security::e($targetType) ?>">
      <input type="hidden" name="target_id" value="<?= (int)$targetId ?>">
      <div class="intern-location-grid">
        <div><span>Distance</span><strong data-distance-label>-</strong></div>
        <div><span>Accuracy</span><strong data-accuracy-label>-</strong></div>
        <div><span>Status</span><strong data-result-label><?= Security::e($today['status_label']) ?></strong></div>
      </div>
      <p class="intern-form-status" data-attendance-status></p>
      <button class="btn btn--primary intern-signin-button" type="submit" <?= (!$today['is_open'] || $today['already_signed'] || $targetId <= 0) ? 'disabled' : '' ?>><i class="fa-solid fa-location-crosshairs"></i> <?= $today['already_signed'] ? 'Already Signed In' : 'Sign In With Location' ?></button>
    </form>
  </div>

  <aside class="card intern-card intern-signin-side">
    <h2>Today</h2>
    <dl>
      <div><dt>Gateway</dt><dd><?= Security::e($today['is_open'] ? 'Open' : 'Closed') ?></dd></div>
      <div><dt>Closes</dt><dd><?= Security::e(format_datetime($today['gateway']['closes_at'] ?? null)) ?></dd></div>
      <div><dt>Location radius</dt><dd><?= Security::e(isset($today['project']['radius_meters']) ? format_number((float)$today['project']['radius_meters']) . ' m' : '-') ?></dd></div>
      <div><dt>Geo status</dt><dd><?= Security::e(status_label($today['project']['geo_status'] ?? 'not configured')) ?></dd></div>
    </dl>
  </aside>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
