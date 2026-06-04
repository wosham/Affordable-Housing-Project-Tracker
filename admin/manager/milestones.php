<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'priority' => Security::cleanString((string)($_GET['priority'] ?? '')),
    'view' => Security::cleanString((string)($_GET['view'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerMilestone::count($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$milestones = array_map([ManagerMilestone::class, 'payload'], ManagerMilestone::list($userId, $role, $filters, $perPage, $offset));
$summary = ManagerMilestone::summary($userId, $role);
$projects = ManagerMilestone::projects($userId, $role);
$insights = ManagerMilestone::insights($userId, $role);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($milestones), $total);

$pageTitle = 'Milestones';
$pageDescription = 'Track delivery targets, upcoming deadlines and completed site milestones.';
$adminRole = 'manager';
$csrfForm = 'manager_milestones';
$contentClass = 'manager-milestones-page';
$componentCss = ['manager-milestones'];
$pageScripts = ['manager-milestones'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Milestones'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="manager-milestone-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Delivery targets</span>
    <h2>Milestones</h2>
    <p>Track delivery targets, upcoming deadlines and completed site milestones.</p>
  </div>
  <div class="manager-milestone-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/programme-of-works.php')) ?>"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme</a>
    <button class="btn btn--primary" type="button" data-milestone-open-create><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Milestone</button>
  </div>
</section>

<section class="stat-grid stat-grid--4 manager-milestone-stats" aria-label="Milestone summary">
  <?php manager_milestone_stat('fa-layer-group', $summary['total'], 'Total Milestones', 'Across assigned projects'); ?>
  <?php manager_milestone_stat('fa-location-crosshairs', $summary['current'], 'Current', 'Active delivery targets'); ?>
  <?php manager_milestone_stat('fa-circle-check', $summary['completed'], 'Completed', 'Closed milestones'); ?>
  <?php manager_milestone_stat('fa-triangle-exclamation', $summary['overdue'], 'Overdue', 'Needs attention'); ?>
  <?php manager_milestone_stat('fa-clock', $summary['due_soon'], 'Due Soon', 'Next delivery window'); ?>
  <?php manager_milestone_stat('fa-fire', $summary['critical'], 'Critical', 'Priority targets'); ?>
  <?php manager_milestone_stat('fa-chart-simple', $summary['average_progress'] . '%', 'Average Progress', 'Milestone progress'); ?>
  <?php manager_milestone_stat('fa-circle-question', $summary['without_current'], 'No Current Target', 'Projects to review'); ?>
</section>

<section class="manager-milestone-layout">
  <div class="manager-milestone-main">
    <section class="card manager-milestone-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">Milestone Tracker</h2>
          <p class="card__subtitle">Filter targets, update status and keep delivery dates current.</p>
        </div>
        <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> milestones</span>
      </div>

      <form class="filter-bar manager-milestone-filter" method="get" action="<?= Security::e(Url::to('admin/manager/milestones.php')) ?>">
        <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project or milestone..."></div>
        <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerMilestone::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority"><option value="">All priorities</option><?php foreach (ManagerMilestone::PRIORITIES as $priority): ?><option value="<?= Security::e($priority) ?>" <?= (($filters['priority'] ?? '') === $priority) ? 'selected' : '' ?>><?= Security::e(status_label($priority)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="view">View</label><select class="form-select" id="view" name="view"><option value="">All targets</option><option value="overdue" <?= (($filters['view'] ?? '') === 'overdue') ? 'selected' : '' ?>>Overdue</option><option value="due-soon" <?= (($filters['view'] ?? '') === 'due-soon') ? 'selected' : '' ?>>Due soon</option><option value="completed" <?= (($filters['view'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option></select></div>
        <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
        <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
        <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/milestones.php')) ?>">Reset</a></div>
      </form>

      <div class="table-wrap">
        <table class="data-table manager-milestone-table">
          <thead><tr><th>Milestone</th><th>Project</th><th>Status</th><th>Target</th><th>Progress</th><th>Updated</th><th>Actions</th></tr></thead>
          <tbody>
<?php if ($milestones === []): ?>
            <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-bullseye" aria-hidden="true"></i></span><strong class="empty-state__title">No milestones found</strong><span class="empty-state__text">Add a milestone or adjust your filters.</span></div></td></tr>
<?php else: foreach ($milestones as $milestone): ?>
            <tr data-milestone-row="<?= (int)$milestone['id'] ?>">
              <td>
                <strong><?= Security::e($milestone['label']) ?></strong>
                <small><?= Security::e($milestone['description'] ?: $milestone['notes'] ?: 'No description added') ?></small>
              </td>
              <td><strong><?= Security::e($milestone['project_name']) ?></strong><small><?= Security::e(trim($milestone['constituency_name'] . ' / ' . $milestone['ward_name'], ' /')) ?></small></td>
              <td>
                <span class="manager-milestone-status manager-milestone-status--<?= Security::e($milestone['status']) ?>"><?= Security::e($milestone['status_label']) ?></span>
                <small class="manager-milestone-priority manager-milestone-priority--<?= Security::e($milestone['priority']) ?>"><?= Security::e($milestone['priority_label']) ?></small>
              </td>
              <td>
                <strong class="<?= $milestone['is_overdue'] ? 'is-overdue' : ($milestone['is_due_soon'] ? 'is-due-soon' : '') ?>"><?= Security::e($milestone['time_label']) ?></strong>
                <small><?= Security::e($milestone['target_label'] ?: 'No target date') ?></small>
              </td>
              <td><div class="manager-milestone-progress"><span style="width: <?= (int)$milestone['progress'] ?>%"></span></div><small><?= (int)$milestone['progress'] ?>% complete</small></td>
              <td><strong><?= Security::e($milestone['updated_by_name'] ?: 'System') ?></strong><small><?= Security::e($milestone['updated_label'] ?: '-') ?></small></td>
              <td>
                <div class="manager-milestone-actions">
                  <button class="btn btn--icon btn--outline" type="button" data-milestone-edit
                    data-id="<?= (int)$milestone['id'] ?>"
                    data-project-id="<?= (int)$milestone['project_id'] ?>"
                    data-label="<?= Security::e($milestone['label']) ?>"
                    data-description="<?= Security::e($milestone['description']) ?>"
                    data-status="<?= Security::e($milestone['status']) ?>"
                    data-priority="<?= Security::e($milestone['priority']) ?>"
                    data-target-date="<?= Security::e($milestone['target_date']) ?>"
                    data-actual-date="<?= Security::e($milestone['actual_date']) ?>"
                    data-progress="<?= (int)$milestone['progress'] ?>"
                    data-sequence="<?= (int)$milestone['sequence'] ?>"
                    data-notes="<?= Security::e($milestone['notes']) ?>"
                    title="Edit milestone" aria-label="Edit milestone"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
                  <?php if ($milestone['status'] !== 'current'): ?><button class="btn btn--icon btn--primary" type="button" data-milestone-action="current" data-milestone-id="<?= (int)$milestone['id'] ?>" title="Mark current" aria-label="Mark current"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></button><?php endif; ?>
                  <?php if ($milestone['status'] !== 'done'): ?><button class="btn btn--icon btn--success" type="button" data-milestone-action="done" data-milestone-id="<?= (int)$milestone['id'] ?>" title="Mark done" aria-label="Mark done"><i class="fa-solid fa-check" aria-hidden="true"></i></button><?php endif; ?>
                  <?php if ($milestone['status'] !== 'pending'): ?><button class="btn btn--icon btn--outline" type="button" data-milestone-action="pending" data-milestone-id="<?= (int)$milestone['id'] ?>" title="Move to pending" aria-label="Move to pending"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button><?php endif; ?>
                </div>
              </td>
            </tr>
<?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

<?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Milestone pagination">
        <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> milestones</p>
        <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_milestone_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_milestone_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
      </nav>
<?php endif; ?>
    </section>

    <section class="card manager-milestone-followup">
      <div class="card__header"><div><h2 class="card__title">No Current Target</h2><p class="card__subtitle">Projects needing a current milestone.</p></div></div>
      <div class="manager-milestone-project-grid">
<?php if ($insights['withoutCurrent'] === []): ?>
        <div class="empty-state empty-state--compact"><strong class="empty-state__title">All projects have a current target</strong></div>
<?php else: foreach ($insights['withoutCurrent'] as $project): ?>
        <div class="manager-milestone-project-card"><span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency_name'] ?: $project['ward_name'] ?: status_label($project['status'])) ?></small></span><button class="btn btn--icon btn--outline" type="button" data-milestone-open-create data-project-id="<?= (int)$project['id'] ?>" title="Add milestone"><i class="fa-solid fa-plus" aria-hidden="true"></i></button></div>
<?php endforeach; endif; ?>
      </div>
    </section>
  </div>

  <aside class="manager-milestone-side" aria-label="Milestone insights">
    <?php manager_milestone_panel('Overdue', 'fa-triangle-exclamation', $insights['overdue'], 'No overdue milestones'); ?>
    <?php manager_milestone_panel('Due Soon', 'fa-clock', $insights['dueSoon'], 'No upcoming deadlines'); ?>
    <?php manager_milestone_panel('Recently Completed', 'fa-circle-check', $insights['recent'], 'No completed milestones yet'); ?>
  </aside>
</section>

<div class="manager-milestone-modal" data-milestone-modal hidden>
  <form class="manager-milestone-modal__panel" data-milestone-form>
    <div class="manager-milestone-modal__header">
      <div><span class="sa-panel-label">Milestone details</span><h2 data-milestone-modal-title>Add milestone</h2></div>
      <button class="btn btn--icon btn--ghost" type="button" data-milestone-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <input type="hidden" name="milestone_id" data-field="milestone_id">
    <div class="manager-milestone-modal__body">
      <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" data-field="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Label</span><input class="form-input" name="label" data-field="label" maxlength="200" required></label>
      <label class="form-field form-field--wide"><span class="form-label">Description</span><textarea class="form-textarea" name="description" data-field="description" rows="3"></textarea></label>
      <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status" data-field="status"><?php foreach (ManagerMilestone::STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Priority</span><select class="form-select" name="priority" data-field="priority"><?php foreach (ManagerMilestone::PRIORITIES as $priority): ?><option value="<?= Security::e($priority) ?>"><?= Security::e(status_label($priority)) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Target date</span><input class="form-input" type="date" name="target_date" data-field="target_date"></label>
      <label class="form-field"><span class="form-label">Actual date</span><input class="form-input" type="date" name="actual_date" data-field="actual_date"></label>
      <label class="form-field"><span class="form-label">Progress %</span><input class="form-input" type="number" min="0" max="100" name="progress_percent" data-field="progress_percent" value="0"></label>
      <label class="form-field"><span class="form-label">Sequence</span><input class="form-input" type="number" min="0" name="sequence" data-field="sequence" value="0"></label>
      <label class="form-field form-field--wide"><span class="form-label">Update note</span><textarea class="form-textarea" name="notes" data-field="notes" rows="3"></textarea></label>
      <p class="manager-milestone-form-status" data-milestone-form-status></p>
    </div>
    <div class="manager-milestone-modal__footer">
      <button class="btn btn--outline" type="button" data-milestone-close>Cancel</button>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Milestone</button>
    </div>
  </form>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_milestone_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function manager_milestone_panel(string $title, string $icon, array $items, string $empty): void
{
?>
  <section class="card manager-milestone-panel">
    <div class="card__header"><div><h2 class="card__title"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i> <?= Security::e($title) ?></h2></div></div>
    <div class="manager-milestone-mini-list">
<?php if ($items === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title"><?= Security::e($empty) ?></strong></div>
<?php else: foreach ($items as $item): ?>
      <div class="manager-milestone-mini-item"><span><strong><?= Security::e($item['label']) ?></strong><small><?= Security::e($item['project_name'] . ' / ' . $item['time_label']) ?></small></span><span class="manager-milestone-status manager-milestone-status--<?= Security::e($item['status']) ?>"><?= Security::e($item['status_label']) ?></span></div>
<?php endforeach; endif; ?>
    </div>
  </section>
<?php
}

function manager_milestone_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null);
    return Url::to('admin/manager/milestones.php?' . http_build_query($query));
}
