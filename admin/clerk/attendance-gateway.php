<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('clerk');

$userId = (int)Auth::id();
$today = date('Y-m-d');
$projects = ClerkAttendance::projects($userId);
$projectId = ClerkAttendance::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$project = $projectId ? ClerkAttendance::project($userId, $projectId) : null;
$policy = AttendancePolicy::windowPayload();
if ($projectId > 0) {
    AttendancePolicy::ensureProjectWindow($projectId, $today);
}
$gateway = $projectId ? AttendanceGateway::forProjectDate($projectId, $today) : null;
$summary = $projectId ? ClerkAttendance::summary($userId, $today, $projectId) : ClerkAttendance::summary($userId, $today);
$expected = $projectId ? ClerkAttendance::expectedPeople($userId, $projectId, $today) : [];
$isOpen = AttendancePolicy::isEffectivelyOpen($gateway);
$clerkConfirmed = $gateway && !empty($gateway['clerk_confirmed_at']);
$selfSigned = AttendanceRecord::todayForAnyTarget($userId);

$pageTitle = 'Attendance Gateway';
$pageDescription = 'Confirm site open within County Director policy window and monitor sign-ins.';
$adminRole = 'clerk';
$contentClass = 'clerk-attendance-page';
$csrfForm = 'attendance_gateway';
$componentCss = ['attendance'];
$pageScripts = ['attendance-gateway'];
// dashboard-clerk.css loads via adminRole; attendance.css adds policy banners
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Clerk of Works', 'url' => Url::to('admin/clerk/dashboard.php')],
    ['label' => 'Attendance Gateway'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="clerk-hero card" data-attendance-gateway data-status-url="<?= Security::e(Url::to('api/attendance/gateway-status.php')) ?>" data-project-id="<?= (int)$projectId ?>" data-gateway-date="<?= Security::e($today) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-door-open" aria-hidden="true"></i> Attendance control</span>
    <h2>Attendance Gateway</h2>
    <p>County Director sets the sign-in window. The system auto-opens on schedule so early arrivals are not blocked. You confirm site open — you cannot change times.</p>
    <span class="clerk-live-status <?= $isOpen ? 'is-open' : 'is-closed' ?>" data-gateway-status>
      <?= $isOpen ? 'Policy window is open — sign-ins accepted.' : ($policy['is_before_open'] ? 'Before open (' . Security::e($policy['open_time']) . ').' : ($policy['is_after_close'] ? 'Closed for today after ' . Security::e($policy['close_time']) . '.' : 'Attendance not active today.')) ?>
    </span>
  </div>
  <div class="clerk-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/attendance-live.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-eye"></i> Live Attendance</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/clerk/messages.php')) ?>"><i class="fa-solid fa-comments"></i> Message site interns</a>
    <button class="btn btn--outline" type="button" data-gateway-refresh><i class="fa-solid fa-rotate"></i> Refresh</button>
  </div>
</section>

<section class="card clerk-policy-banner" aria-label="County Director attendance policy">
  <div class="clerk-policy-banner__copy">
    <span class="sa-panel-label"><i class="fa-solid fa-sliders" aria-hidden="true"></i> County Director policy</span>
    <strong class="clerk-policy-banner__title" data-policy-label><?= Security::e($policy['label']) ?></strong>
    <p class="clerk-policy-banner__status">Weekdays only · Africa/Nairobi · Auto-open <?= $policy['auto_open'] ? 'enabled' : 'disabled' ?>. Clerks confirm site open only; times cannot be changed here.</p>
  </div>
  <div class="clerk-policy-chips">
    <article><small>Opens</small><strong><?= Security::e($policy['open_time']) ?></strong></article>
    <article><small>Expected</small><strong><?= Security::e($policy['expected_time']) ?></strong></article>
    <article><small>Late after</small><strong><?= Security::e($policy['late_after']) ?></strong></article>
    <article><small>Closes</small><strong><?= Security::e($policy['close_time']) ?></strong></article>
  </div>
</section>

<?php if ($projects === []): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Attendance controls appear once a project is assigned to you.</span></div>
<?php else: ?>

<form class="card clerk-selector" method="get">
  <label for="project_id">Project</label>
  <select id="project_id" name="project_id" onchange="this.form.submit()">
    <?php foreach ($projects as $option): ?>
      <option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<section class="clerk-stats">
  <?php clerk_gate_stat('fa-door-open', $isOpen ? 'Open' : 'Closed', 'Policy window', $isOpen ? 'Accepting sign-ins' : 'Not accepting'); ?>
  <?php clerk_gate_stat('fa-user-check', $clerkConfirmed ? 'Yes' : 'No', 'Clerk confirmed', $clerkConfirmed ? 'Site open acknowledged' : 'Confirm site open below'); ?>
  <?php clerk_gate_stat('fa-users', $summary['signed_in'], 'Signed In', 'Today'); ?>
  <?php clerk_gate_stat('fa-user-clock', $summary['missing'], 'Pending', 'Expected people'); ?>
  <?php clerk_gate_stat('fa-location-crosshairs', $summary['flagged'], 'Location Flags', 'Needs review'); ?>
  <?php clerk_gate_stat('fa-clock', $policy['close_time'], 'Close time', 'Set by County Director'); ?>
</section>

<?php if (!$clerkConfirmed && $isOpen): ?>
  <div class="card clerk-warning-banner" role="status">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    <div>
      <strong>Confirm site open</strong>
      <span>Attendance is already open under policy so interns can sign in. Confirm that the site is manned and ready. If you do not confirm, County Director may be notified after the escalation period.</span>
    </div>
  </div>
<?php endif; ?>

<section class="clerk-layout">
  <main class="card clerk-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title">Confirm site open</h2>
        <p class="card__subtitle"><?= Security::e($project['name'] ?? 'Assigned project') ?> · times are read-only</p>
      </div>
      <span class="badge <?= $clerkConfirmed ? 'badge--success' : ($isOpen ? 'badge--warning' : 'badge--muted') ?>">
        <?= $clerkConfirmed ? 'Confirmed' : ($isOpen ? 'Open — unconfirmed' : 'Closed') ?>
      </span>
    </div>

    <form class="clerk-gateway-form" data-gateway-form>
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <input type="hidden" name="date" value="<?= Security::e($today) ?>">
      <label><span>Attendance date</span><input type="date" value="<?= Security::e($today) ?>" disabled></label>
      <label><span>Policy close time</span><input type="time" value="<?= Security::e($policy['close_time']) ?>" disabled title="Set by County Director"></label>
      <label class="is-wide"><span>Clerk note (optional)</span><textarea name="notes" rows="3" placeholder="Site ready, weather, or access notes for today."><?= Security::e((string)($gateway['notes'] ?? '')) ?></textarea></label>
      <div class="clerk-gateway-form__actions">
        <button class="btn btn--primary" type="button" data-gateway-open="<?= Security::e(Url::to('api/attendance/gateway-open.php')) ?>" <?= (!$isOpen && !$policy['is_within_window']) ? 'disabled' : '' ?>>
          <i class="fa-solid fa-clipboard-check"></i> Confirm site open
        </button>
        <button class="btn btn--danger" type="button" data-gateway-close="<?= Security::e(Url::to('api/attendance/gateway-close.php')) ?>" title="Emergency early close only">
          <i class="fa-solid fa-door-closed"></i> Emergency close
        </button>
      </div>
      <p class="clerk-form-help">You cannot change open/close times. Emergency close stops sign-ins early with a note; policy times still apply tomorrow.</p>
    </form>
  </main>

  <aside class="card clerk-panel">
    <h2>Your sign-in (once per day)</h2>
    <?php if ($selfSigned): ?>
      <div class="clerk-self-signed">
        <span class="badge badge--success">Already signed in</span>
        <p>You signed in today at <strong><?= Security::e(date('H:i', strtotime((string)($selfSigned['signin_time'] ?? 'now')))) ?></strong>. One sign-in per person per day. You cannot sign for someone else.</p>
      </div>
    <?php else: ?>
      <p class="clerk-form-help">Clerks may sign in once per day for themselves only (same GPS rules as interns).</p>
      <form class="clerk-self-signin" action="<?= Security::e(Url::to('api/attendance/sign-in.php')) ?>" method="post" data-clerk-self-signin data-csrf-form="attendance_signin">
        <input type="hidden" name="target_type" value="project">
        <input type="hidden" name="target_id" value="<?= (int)$projectId ?>">
        <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
        <button class="btn btn--primary" type="submit" <?= !$isOpen || $projectId <= 0 ? 'disabled' : '' ?>>
          <i class="fa-solid fa-location-crosshairs"></i> Sign in with location
        </button>
        <p class="clerk-form-help" data-clerk-signin-status></p>
      </form>
    <?php endif; ?>
    <div class="clerk-context" style="margin-top:1rem">
      <span><strong><?= Security::e($project['name'] ?? '-') ?></strong><small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /') ?: 'Project site') ?></small></span>
      <span><strong><?= Security::e(status_label((string)($project['status'] ?? '-'))) ?></strong><small>Project status</small></span>
      <span><strong><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</strong><small>Progress</small></span>
      <span><strong><?= Security::e(status_label((string)($project['geo_status'] ?? 'missing'))) ?></strong><small>Location setup</small></span>
    </div>
  </aside>
</section>

<section class="card clerk-panel">
  <div class="card__header">
    <div><h2 class="card__title">Expected Personnel</h2><p class="card__subtitle">People assigned to this site today (one sign-in each).</p></div>
    <span class="badge badge--info"><?= count($expected) ?> people</span>
  </div>
  <div class="clerk-table-wrap">
    <table class="clerk-table">
      <thead><tr><th>Name</th><th>Role</th><th>Contact</th><th>Status</th></tr></thead>
      <tbody data-expected-list>
      <?php if ($expected === []): ?>
        <tr><td colspan="4"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No assigned personnel</strong><span class="empty-state__text">Assigned team members will appear here.</span></div></td></tr>
      <?php else: foreach ($expected as $person): ?>
        <tr>
          <td><strong><?= Security::e($person['user_name']) ?></strong></td>
          <td><?= Security::e(role_label((string)$person['role_slug'])) ?></td>
          <td><small><?= Security::e($person['email'] ?? '') ?></small><small><?= Security::e($person['phone'] ?? '') ?></small></td>
          <td><span class="badge <?= $person['attendance_id'] ? 'badge--success' : 'badge--warning' ?>"><?= $person['attendance_id'] ? 'Signed in' : 'Pending' ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php endif; ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function clerk_gate_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="clerk-stat card"><span><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></article>';
}
