<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('manager');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$summary = ManagerDashboard::summary($userId, $role);
$projects = ManagerDashboard::projects($userId, $role, 8);
$milestones = ManagerDashboard::milestoneAlerts($userId, $role, 8);
$ipcs = ManagerDashboard::ipcQueue($userId, $role, 6);
$programme = ManagerDashboard::programmeSnapshot($userId, $role);
$risks = ManagerDashboard::riskFeed($userId, $role, 8);

$pageTitle = 'Manager Dashboard';
$pageDescription = 'Operational overview for assigned projects, milestones, IPCs and site signals.';
$adminRole = 'manager';
$contentClass = 'manager-dashboard-page';
$componentCss = [];
$pageScripts = [];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager'],
    ['label' => 'Dashboard'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="manager-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Manager overview</span>
    <h2>Welcome, <?= Security::e(current_user_name()) ?></h2>
    <p><?= Security::e(date('l, d F Y')) ?>. Track project movement, site risks, IPC actions and team communication from one place.</p>
  </div>
  <div class="manager-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/programme-of-works.php')) ?>"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/manager/messages.php')) ?>"><i class="fa-solid fa-comments" aria-hidden="true"></i> Message Team</a>
  </div>
</section>

<section class="manager-stat-grid" aria-label="Manager dashboard summary">
  <?php manager_stat('fa-building-circle-check', $summary['assigned_projects'], 'Assigned Projects', 'Project portfolio', 'admin/manager/projects.php'); ?>
  <?php manager_stat('fa-chart-line', $summary['average_progress'] . '%', 'Average Progress', 'Across assigned works', 'admin/manager/projects.php'); ?>
  <?php manager_stat('fa-triangle-exclamation', $summary['delayed_projects'], 'Delayed Projects', 'Past delivery date', 'admin/manager/projects.php'); ?>
  <?php manager_stat('fa-file-invoice-dollar', $summary['ipc_queue'], 'IPC Queue', 'Needs review/action', 'admin/manager/ipc-queue.php'); ?>
  <?php manager_stat('fa-bullseye', $summary['milestones_due'], 'Due Soon', 'Milestones next window', 'admin/manager/milestones.php'); ?>
  <?php manager_stat('fa-bars-progress', $summary['programme_overdue'], 'Overdue Tasks', 'Programme slippage', 'admin/manager/programme-of-works.php'); ?>
  <?php manager_stat('fa-calendar-check', $summary['attendance_today'], 'Present Today', 'Site attendance', 'admin/manager/attendance-summary.php'); ?>
  <?php manager_stat('fa-bell', $summary['unread_notifications'], 'Unread Alerts', 'Notifications waiting', 'admin/manager/messages.php'); ?>
</section>

<section class="manager-grid manager-grid--main">
  <article class="card manager-projects-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Assigned Projects</h2>
        <p class="card__subtitle">Progress, delivery dates and next milestones.</p>
      </div>
      <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>">View all</a>
    </div>
    <div class="table-wrap">
      <table class="data-table manager-project-table">
        <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Next Milestone</th><th>Delivery</th></tr></thead>
        <tbody>
<?php if ($projects === []): ?>
          <tr><td colspan="5"><div class="empty-state"><strong class="empty-state__title">No assigned projects yet</strong><span class="empty-state__text">Project assignments will appear here once configured.</span></div></td></tr>
<?php else: ?>
<?php foreach ($projects as $project): ?>
          <tr>
            <td><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?></small></td>
            <td><span class="badge <?= Security::e(status_badge_class($project['status'])) ?>"><?= Security::e(status_label($project['status'])) ?></span></td>
            <td><div class="manager-progress"><span style="width: <?= percentage($project['pct_complete'] ?? 0) ?>%"></span></div><small><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</small></td>
            <td><strong><?= Security::e($project['next_milestone'] ?: 'Not set') ?></strong><small><?= Security::e(format_date($project['next_milestone_date'] ?? null)) ?></small></td>
            <td><?= Security::e(format_date($project['est_delivery'] ?? null)) ?><small><?= Security::e(format_money($project['contract_sum'] ?? 0)) ?></small></td>
          </tr>
<?php endforeach; ?>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <aside class="manager-side-stack">
    <article class="card manager-programme-card">
      <div class="card__header"><div><h2 class="card__title">Programme Snapshot</h2><p class="card__subtitle">Task health across assigned projects.</p></div></div>
      <div class="manager-programme-ring">
        <strong><?= (int)$programme['avg_progress'] ?>%</strong>
        <span>average task progress</span>
      </div>
      <div class="manager-mini-grid">
        <span><strong><?= format_number($programme['total']) ?></strong>Total</span>
        <span><strong><?= format_number($programme['in_progress']) ?></strong>In progress</span>
        <span><strong><?= format_number($programme['completed']) ?></strong>Completed</span>
        <span><strong><?= format_number($programme['overdue']) ?></strong>Overdue</span>
      </div>
    </article>

    <article class="card manager-quick-card">
      <div class="card__header"><div><h2 class="card__title">Quick Actions</h2><p class="card__subtitle">Common manager workflows.</p></div></div>
      <div class="manager-action-list">
        <a href="<?= Security::e(Url::to('admin/manager/ipc-queue.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Review IPC queue</a>
        <a href="<?= Security::e(Url::to('admin/manager/boq.php')) ?>"><i class="fa-solid fa-list-check"></i> Review BOQ</a>
        <a href="<?= Security::e(Url::to('admin/manager/milestones.php')) ?>"><i class="fa-solid fa-bullseye"></i> Update milestones</a>
        <a href="<?= Security::e(Url::to('admin/manager/attendance-summary.php')) ?>"><i class="fa-solid fa-calendar-days"></i> View attendance</a>
        <a href="<?= Security::e(Url::to('admin/manager/hs-incidents.php')) ?>"><i class="fa-solid fa-triangle-exclamation"></i> Report HS incident</a>
      </div>
    </article>
  </aside>
</section>

<section class="manager-grid manager-grid--three">
  <article class="card">
    <div class="card__header"><div><h2 class="card__title">IPC Queue</h2><p class="card__subtitle">Track incoming claims and endorse certified IPCs.</p></div></div>
    <div class="manager-list">
<?php if ($ipcs === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No IPCs waiting</strong></div>
<?php else: foreach ($ipcs as $ipc): ?>
      <a class="manager-list-item" href="<?= Security::e(Url::to('admin/manager/ipc-queue.php')) ?>">
        <span><strong><?= Security::e($ipc['project_name']) ?></strong><small>IPC #<?= (int)$ipc['ipc_number'] ?> by <?= Security::e(trim($ipc['contractor_name']) ?: 'Contractor') ?></small></span>
        <span><em><?= Security::e(format_money($ipc['net_amount'] ?? 0)) ?></em><small><?= Security::e(status_label($ipc['status'])) ?></small></span>
      </a>
<?php endforeach; endif; ?>
    </div>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Milestone Alerts</h2><p class="card__subtitle">Due soon and overdue targets.</p></div></div>
    <div class="manager-list">
<?php if ($milestones === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No milestone alerts</strong></div>
<?php else: foreach ($milestones as $milestone): ?>
      <a class="manager-list-item" href="<?= Security::e(Url::to('admin/manager/milestones.php')) ?>">
        <span><strong><?= Security::e($milestone['label']) ?></strong><small><?= Security::e($milestone['project_name']) ?></small></span>
        <span><em><?= Security::e(format_date($milestone['target_date'] ?? null)) ?></em><small><?= Security::e(status_label($milestone['status'])) ?></small></span>
      </a>
<?php endforeach; endif; ?>
    </div>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Risk Feed</h2><p class="card__subtitle">Recent site and workflow signals.</p></div></div>
    <div class="manager-list">
<?php if ($risks === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No recent risks</strong></div>
<?php else: foreach ($risks as $risk): ?>
      <span class="manager-list-item">
        <span><strong><?= Security::e(safe_truncate($risk['title'] ?? '', 64)) ?></strong><small><?= Security::e($risk['item_type']) ?> · <?= Security::e($risk['project_name']) ?></small></span>
        <span><em><?= Security::e(status_label($risk['status'] ?? '')) ?></em><small><?= Security::e(time_ago($risk['created_at'] ?? null)) ?></small></span>
      </span>
<?php endforeach; endif; ?>
    </div>
  </article>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_stat(string $icon, mixed $value, string $label, string $hint, string $path): void
{
?>
  <a class="manager-stat card" href="<?= Security::e(Url::to($path)) ?>">
    <span class="manager-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="manager-stat__copy"><strong><?= Security::e((string)$value) ?></strong><span><?= Security::e($label) ?></span><small><?= Security::e($hint) ?></small></span>
  </a>
<?php
}
