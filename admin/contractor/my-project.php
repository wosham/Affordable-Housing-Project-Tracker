<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('contractor'));

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorProject::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorProject::projects($userId, $role);
$project = $projectId > 0 ? ContractorProject::detail($projectId, $userId, $role) : null;
$summary = $project ? ContractorProject::summary($projectId, $userId, $role) : ContractorProject::summary(0, $userId, $role);
$history = $project ? ContractorProject::progressHistory($projectId, $userId, $role, 8) : [];
$tasks = $project ? ContractorProject::programmeTasks($projectId, $userId, $role, 7) : [];
$ipcs = $project ? ContractorProject::latestIpcs($projectId, $userId, $role, 5) : [];

$pageTitle = 'My Project';
$pageDescription = 'Contractor project overview, progress, claims and site records.';
$adminRole = 'contractor';
$contentClass = 'contractor-project-page';
$componentCss = ['contractor-project'];
$pageScripts = ['contractor-progress'];
$csrfForm = 'contractor_project';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'My Project'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-project-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-building" aria-hidden="true"></i> Project workspace</span>
    <h2><?= Security::e($project['name'] ?? 'My Project') ?></h2>
    <p>Track progress, programme, claims, submissions and project records in one place.</p>
  </div>
  <div class="contractor-project-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/progress-update.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Update Progress</a>
  </div>
</section>

<?php if ($projects !== []): ?>
<form class="contractor-project-selector card" method="get">
  <label for="project_id">Project</label>
  <select id="project_id" name="project_id" onchange="this.form.submit()">
    <?php foreach ($projects as $option): ?>
      <option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>

<?php if (!$project): ?>
  <div class="card empty-state"><strong class="empty-state__title">No assigned project found</strong><span class="empty-state__text">Assigned project records will appear here once configured.</span></div>
<?php else: ?>

<section class="contractor-project-grid contractor-project-grid--overview">
  <article class="card contractor-project-profile">
    <div class="contractor-project-progress-ring" style="--progress: <?= (int)percentage($project['pct_complete'] ?? 0) ?>">
      <strong><?= (int)percentage($project['pct_complete'] ?? 0) ?>%</strong>
      <span>complete</span>
    </div>
    <div>
      <h3><?= Security::e($project['name']) ?></h3>
      <p><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?></p>
      <div class="contractor-project-meta">
        <span><strong>Status</strong><?= Security::e(status_label($project['status'] ?? '')) ?></span>
        <span><strong>Contract Sum</strong><?= Security::e(format_money($project['contract_sum'] ?? 0)) ?></span>
        <span><strong>Start Date</strong><?= Security::e(format_date($project['start_date'] ?? null)) ?></span>
        <span><strong>Delivery</strong><?= Security::e(format_date($project['est_delivery'] ?? null)) ?></span>
        <span><strong>Current Milestone</strong><?= Security::e($project['current_milestone'] ?: 'Not set') ?></span>
        <span><strong>Consultant</strong><?= Security::e(trim((string)($project['consultant_name'] ?? '')) ?: 'Not assigned') ?></span>
      </div>
    </div>
  </article>

  <aside class="card contractor-project-contact">
    <h3>Project Contacts</h3>
    <div class="contractor-contact-list">
      <span><strong>Consultant</strong><?= Security::e(trim((string)($project['consultant_name'] ?? '')) ?: 'Not assigned') ?><small><?= Security::e($project['consultant_email'] ?? '') ?></small></span>
      <span><strong>Contractor</strong><?= Security::e(trim((string)($project['contractor_name'] ?? '')) ?: current_user_name()) ?><small><?= Security::e($project['contractor_email'] ?? '') ?></small></span>
    </div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/messages.php')) ?>"><i class="fa-solid fa-comments" aria-hidden="true"></i> Message Team</a>
  </aside>
</section>

<section class="contractor-project-stats" aria-label="Project signals">
  <?php contractor_project_stat('fa-list-check', $summary['boq_items'], 'BOQ Items', 'Project quantities'); ?>
  <?php contractor_project_stat('fa-circle-check', format_money($summary['boq_certified']), 'Certified BOQ', 'Measured value'); ?>
  <?php contractor_project_stat('fa-file-invoice-dollar', $summary['ipc_active'], 'Active IPCs', 'Claims in workflow'); ?>
  <?php contractor_project_stat('fa-money-check-dollar', format_money($summary['ipc_approved_unpaid']), 'Approved Unpaid', 'Awaiting payment'); ?>
  <?php contractor_project_stat('fa-bars-progress', $summary['programme_total'], 'Programme Tasks', 'Delivery activities'); ?>
  <?php contractor_project_stat('fa-triangle-exclamation', $summary['programme_overdue'], 'Overdue Tasks', 'Needs attention'); ?>
</section>

<section class="contractor-project-grid contractor-project-grid--main">
  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Programme Focus</h2><p class="card__subtitle">Upcoming and open delivery tasks.</p></div></div>
    <div class="contractor-project-list">
      <?php if ($tasks === []): ?><div class="empty-state empty-state--compact"><strong class="empty-state__title">No programme tasks found</strong></div><?php endif; ?>
      <?php foreach ($tasks as $task): ?>
        <span class="contractor-project-list-item">
          <span><strong><?= Security::e($task['task_name']) ?></strong><small><?= Security::e(status_label($task['status'])) ?> / <?= Security::e(format_date($task['planned_end'] ?? $task['end_date'] ?? null)) ?></small></span>
          <span><em><?= (int)percentage($task['pct_complete'] ?? 0) ?>%</em><small><?= ((int)($task['critical_path'] ?? 0) === 1) ? 'Critical path' : 'Programme task' ?></small></span>
        </span>
      <?php endforeach; ?>
    </div>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">IPC Trail</h2><p class="card__subtitle">Recent claims and payment movement.</p></div></div>
    <div class="contractor-project-list">
      <?php if ($ipcs === []): ?><div class="empty-state empty-state--compact"><strong class="empty-state__title">No IPC records yet</strong></div><?php endif; ?>
      <?php foreach ($ipcs as $ipc): ?>
        <a class="contractor-project-list-item" href="<?= Security::e(Url::to('admin/contractor/ipc-history.php')) ?>">
          <span><strong>IPC #<?= (int)$ipc['ipc_number'] ?></strong><small><?= Security::e(format_date($ipc['period_from'] ?? null)) ?> to <?= Security::e(format_date($ipc['period_to'] ?? null)) ?></small></span>
          <span><em><?= Security::e(format_money($ipc['net_amount'] ?? 0)) ?></em><small><?= Security::e(status_label($ipc['status'])) ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<section class="contractor-project-grid contractor-project-grid--main">
  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Progress History</h2><p class="card__subtitle">Submitted progress updates and site notes.</p></div></div>
    <div class="contractor-timeline">
      <?php if ($history === []): ?><div class="empty-state empty-state--compact"><strong class="empty-state__title">No progress updates yet</strong></div><?php endif; ?>
      <?php foreach ($history as $item): ?>
        <article class="contractor-timeline-item">
          <strong><?= (int)$item['old_progress'] ?>% to <?= (int)$item['new_progress'] ?>%</strong>
          <span><?= Security::e($item['current_milestone'] ?: 'Progress update') ?> / <?= Security::e(format_datetime($item['created_at'] ?? null)) ?></span>
          <p><?= Security::e(safe_truncate($item['work_summary'] ?: ($item['note'] ?? ''), 150)) ?></p>
          <?php if (!empty($item['photo_path'])): ?><a href="<?= Security::e(Url::asset($item['photo_path'])) ?>" target="_blank" rel="noopener">View photo</a><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </article>

  <aside class="card">
    <div class="card__header"><div><h2 class="card__title">Open Submissions</h2><p class="card__subtitle">Items waiting for a response.</p></div></div>
    <div class="contractor-submission-grid">
      <a href="<?= Security::e(Url::to('admin/contractor/rfis.php')) ?>"><strong><?= format_number($summary['rfis']) ?></strong>RFIs</a>
      <a href="<?= Security::e(Url::to('admin/contractor/material-approval-submit.php')) ?>"><strong><?= format_number($summary['materials']) ?></strong>Materials</a>
      <a href="<?= Security::e(Url::to('admin/contractor/shop-drawing-submit.php')) ?>"><strong><?= format_number($summary['drawings']) ?></strong>Drawings</a>
      <a href="<?= Security::e(Url::to('admin/contractor/eot-request.php')) ?>"><strong><?= format_number($summary['eots']) ?></strong>EOTs</a>
      <a href="<?= Security::e(Url::to('admin/contractor/variation-request.php')) ?>"><strong><?= format_number($summary['variations']) ?></strong>Variations</a>
    </div>
  </aside>
</section>

<?php endif; ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function contractor_project_stat(string $icon, mixed $value, string $label, string $hint): void
{
    echo '<article class="contractor-project-stat card"><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></article>';
}
