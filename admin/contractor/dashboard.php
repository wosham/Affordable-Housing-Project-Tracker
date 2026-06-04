<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$summary = ContractorDashboard::summary($userId, $role);
$projects = ContractorDashboard::projects($userId, $role, 8);
$ipcs = ContractorDashboard::ipcSnapshot($userId, $role, 6);
$programme = ContractorDashboard::programmeSnapshot($userId, $role);
$site = ContractorDashboard::siteSnapshot($userId, $role);
$technical = ContractorDashboard::technicalSnapshot($userId, $role);
$commercial = ContractorDashboard::commercialSnapshot($userId, $role);
$activity = ContractorDashboard::recentActivity($userId, $role, 8);

$pageTitle = 'Contractor Dashboard';
$pageDescription = 'Contractor overview for assigned projects, claims, programme, submissions and site records.';
$adminRole = 'contractor';
$contentClass = 'contractor-dashboard-page';
$componentCss = [];
$pageScripts = [];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor'],
    ['label' => 'Dashboard'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Contractor overview</span>
    <h2>Welcome, <?= Security::e(current_user_name()) ?></h2>
    <p><?= Security::e(date('l, d F Y')) ?>. Track progress, claims, submissions, site records and project communication from one place.</p>
  </div>
  <div class="contractor-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> My Project</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/progress-update.php')) ?>"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Progress Update</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php')) ?>"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Submit IPC</a>
  </div>
</section>

<section class="contractor-stat-grid" aria-label="Contractor dashboard summary">
  <?php contractor_stat('fa-building-circle-check', $summary['projects'], 'Assigned Projects', 'Project portfolio', 'admin/contractor/my-project.php'); ?>
  <?php contractor_stat('fa-chart-line', $summary['average_progress'] . '%', 'Average Progress', 'Across assigned works', 'admin/contractor/progress-update.php'); ?>
  <?php contractor_stat('fa-file-invoice-dollar', $summary['active_ipcs'], 'Active IPCs', 'Claims in workflow', 'admin/contractor/ipc-history.php'); ?>
  <?php contractor_stat('fa-money-check-dollar', format_money($summary['approved_unpaid_value']), 'Approved Unpaid', 'Awaiting payment', 'admin/contractor/payment-history.php'); ?>
  <?php contractor_stat('fa-coins', format_money($summary['paid_value']), 'Paid Value', 'Payment history', 'admin/contractor/payment-history.php'); ?>
  <?php contractor_stat('fa-list-check', format_money($summary['boq_certified_value']), 'Certified BOQ', 'Measured value', 'admin/contractor/boq.php'); ?>
  <?php contractor_stat('fa-bars-progress', $summary['pending_tasks'], 'Open Tasks', 'Programme movement', 'admin/contractor/programme-of-works.php'); ?>
  <?php contractor_stat('fa-bell', $summary['unread_notifications'], 'Unread Alerts', 'Notifications waiting', 'admin/contractor/messages.php'); ?>
</section>

<section class="contractor-grid contractor-grid--main">
  <article class="card contractor-projects-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Assigned Projects</h2>
        <p class="card__subtitle">Progress, milestone targets, delivery dates and project contacts.</p>
      </div>
      <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php')) ?>">View all</a>
    </div>
    <div class="table-wrap">
      <table class="data-table contractor-project-table">
        <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Next Milestone</th><th>Delivery</th><th>Consultant</th></tr></thead>
        <tbody>
<?php if ($projects === []): ?>
          <tr><td colspan="6"><div class="empty-state"><strong class="empty-state__title">No assigned projects yet</strong><span class="empty-state__text">Assigned project records will appear here once configured.</span></div></td></tr>
<?php else: ?>
<?php foreach ($projects as $project): ?>
          <tr>
            <td><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?></small></td>
            <td><span class="badge <?= Security::e(status_badge_class($project['status'])) ?>"><?= Security::e(status_label($project['status'])) ?></span></td>
            <td><div class="contractor-progress"><span style="width: <?= percentage($project['pct_complete'] ?? 0) ?>%"></span></div><small><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</small></td>
            <td><strong><?= Security::e($project['next_milestone'] ?: 'Not set') ?></strong><small><?= Security::e(format_date($project['next_milestone_date'] ?? null)) ?></small></td>
            <td><?= Security::e(format_date($project['est_delivery'] ?? null)) ?><small><?= Security::e(format_money($project['contract_sum'] ?? 0)) ?></small></td>
            <td><strong><?= Security::e(trim((string)($project['consultant_name'] ?? '')) ?: 'Not assigned') ?></strong><small><?= Security::e($project['consultant_email'] ?? '') ?></small></td>
          </tr>
<?php endforeach; ?>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <aside class="contractor-side-stack">
    <article class="card contractor-programme-card">
      <div class="card__header"><div><h2 class="card__title">Programme Snapshot</h2><p class="card__subtitle">Task movement across assigned projects.</p></div></div>
      <div class="contractor-programme-ring">
        <strong><?= (int)$programme['avg_progress'] ?>%</strong>
        <span>average task progress</span>
      </div>
      <div class="contractor-mini-grid">
        <span><strong><?= format_number($programme['total']) ?></strong>Total</span>
        <span><strong><?= format_number($programme['in_progress']) ?></strong>In progress</span>
        <span><strong><?= format_number($programme['completed']) ?></strong>Completed</span>
        <span><strong><?= format_number($programme['overdue']) ?></strong>Overdue</span>
      </div>
    </article>

    <article class="card contractor-quick-card">
      <div class="card__header"><div><h2 class="card__title">Quick Actions</h2><p class="card__subtitle">Common contractor workflows.</p></div></div>
      <div class="contractor-action-list">
        <a href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php')) ?>"><i class="fa-solid fa-file-circle-plus"></i> Submit IPC</a>
        <a href="<?= Security::e(Url::to('admin/contractor/progress-update.php')) ?>"><i class="fa-solid fa-chart-simple"></i> Update progress</a>
        <a href="<?= Security::e(Url::to('admin/contractor/material-approval-submit.php')) ?>"><i class="fa-solid fa-cubes"></i> Submit material</a>
        <a href="<?= Security::e(Url::to('admin/contractor/rfis.php')) ?>"><i class="fa-solid fa-circle-question"></i> Raise RFI</a>
        <a href="<?= Security::e(Url::to('admin/contractor/messages.php')) ?>"><i class="fa-solid fa-comments"></i> Message team</a>
      </div>
    </article>
  </aside>
</section>

<section class="contractor-grid contractor-grid--three">
  <article class="card">
    <div class="card__header"><div><h2 class="card__title">IPC Status</h2><p class="card__subtitle">Recent claims and payment workflow.</p></div></div>
    <div class="contractor-list">
<?php if ($ipcs === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No IPC records yet</strong></div>
<?php else: foreach ($ipcs as $ipc): ?>
      <a class="contractor-list-item" href="<?= Security::e(Url::to('admin/contractor/ipc-history.php')) ?>">
        <span><strong>IPC #<?= (int)$ipc['ipc_number'] ?></strong><small><?= Security::e($ipc['project_name']) ?> / <?= Security::e(format_date($ipc['period_from'] ?? null)) ?> to <?= Security::e(format_date($ipc['period_to'] ?? null)) ?></small></span>
        <span><em><?= Security::e(format_money($ipc['net_amount'] ?? 0)) ?></em><small><?= Security::e(status_label($ipc['status'])) ?></small></span>
      </a>
<?php endforeach; endif; ?>
    </div>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Site Records</h2><p class="card__subtitle">Recent labour, equipment, delivery and safety signals.</p></div></div>
    <div class="contractor-mini-grid contractor-mini-grid--stacked">
      <a href="<?= Security::e(Url::to('admin/contractor/labour-register.php')) ?>"><strong><?= format_number($site['labour']) ?></strong>Labour records</a>
      <a href="<?= Security::e(Url::to('admin/contractor/equipment-register.php')) ?>"><strong><?= format_number($site['equipment']) ?></strong>Equipment on site</a>
      <a href="<?= Security::e(Url::to('admin/contractor/material-deliveries.php')) ?>"><strong><?= format_number($site['deliveries']) ?></strong>Recent deliveries</a>
      <a href="<?= Security::e(Url::to('admin/contractor/hs-incidents.php')) ?>"><strong><?= format_number($site['incidents']) ?></strong>Open incidents</a>
    </div>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Submissions</h2><p class="card__subtitle">Technical submissions awaiting action.</p></div></div>
    <div class="contractor-mini-grid contractor-mini-grid--stacked">
      <a href="<?= Security::e(Url::to('admin/contractor/material-approval-submit.php')) ?>"><strong><?= format_number($technical['materials']) ?></strong>Materials pending</a>
      <a href="<?= Security::e(Url::to('admin/contractor/shop-drawing-submit.php')) ?>"><strong><?= format_number($technical['drawings']) ?></strong>Drawings pending</a>
      <a href="<?= Security::e(Url::to('admin/contractor/rfis.php')) ?>"><strong><?= format_number($technical['rfis']) ?></strong>Open RFIs</a>
      <a href="<?= Security::e(Url::to('admin/contractor/documents.php')) ?>"><strong><?= format_number($technical['documents']) ?></strong>Documents</a>
    </div>
  </article>
</section>

<section class="contractor-grid contractor-grid--two">
  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Commercial Controls</h2><p class="card__subtitle">Claims, payment and contract request signals.</p></div></div>
    <div class="contractor-commercial-grid">
      <a href="<?= Security::e(Url::to('admin/contractor/eot-request.php')) ?>"><span>EOT requests</span><strong><?= format_number($commercial['eots']) ?></strong></a>
      <a href="<?= Security::e(Url::to('admin/contractor/variation-request.php')) ?>"><span>Variations</span><strong><?= format_number($commercial['variations']) ?></strong></a>
      <a href="<?= Security::e(Url::to('admin/contractor/payment-history.php')) ?>"><span>Approved unpaid</span><strong><?= Security::e(format_money($commercial['approved_unpaid'])) ?></strong></a>
      <a href="<?= Security::e(Url::to('admin/contractor/payment-history.php')) ?>"><span>Payments received</span><strong><?= Security::e(format_money($commercial['payments'])) ?></strong></a>
    </div>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Recent Activity</h2><p class="card__subtitle">Latest claim and submission movement.</p></div></div>
    <div class="contractor-list">
<?php if ($activity === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">No recent activity</strong></div>
<?php else: foreach ($activity as $item): ?>
      <span class="contractor-list-item">
        <span><strong><?= Security::e(safe_truncate($item['title'] ?? '', 64)) ?></strong><small><?= Security::e($item['item_type']) ?> / <?= Security::e($item['project_name']) ?></small></span>
        <span><em><?= Security::e(status_label($item['status'] ?? '')) ?></em><small><?= Security::e(time_ago($item['created_at'] ?? null)) ?></small></span>
      </span>
<?php endforeach; endif; ?>
    </div>
  </article>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function contractor_stat(string $icon, mixed $value, string $label, string $hint, string $path): void
{
?>
  <a class="contractor-stat card" href="<?= Security::e(Url::to($path)) ?>">
    <span class="contractor-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="contractor-stat__copy"><strong><?= Security::e((string)$value) ?></strong><span><?= Security::e($label) ?></span><small><?= Security::e($hint) ?></small></span>
  </a>
<?php
}
