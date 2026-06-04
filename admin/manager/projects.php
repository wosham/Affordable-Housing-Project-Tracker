<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
    'progress' => Security::cleanString((string)($_GET['progress'] ?? '')),
    'risk' => Security::cleanString((string)($_GET['risk'] ?? '')),
    'coverage' => Security::cleanString((string)($_GET['coverage'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 12;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerProject::count($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$projects = array_map([ManagerProject::class, 'payload'], ManagerProject::list($userId, $role, $filters, $perPage, $offset));
$summary = ManagerProject::summary($userId, $role);
$constituencies = Database::fetchAll('SELECT id, name FROM constituencies ORDER BY name ASC');
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($projects), $total);

$pageTitle = 'My Projects';
$pageDescription = 'Assigned project monitoring, delivery risks and operational shortcuts.';
$adminRole = 'manager';
$csrfForm = 'manager_projects';
$contentClass = 'manager-projects-page';
$componentCss = ['manager-projects'];
$pageScripts = ['manager-projects'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Projects'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="manager-projects-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-building" aria-hidden="true"></i> Assigned portfolio</span>
    <h2>Project monitoring</h2>
    <p>Track assigned sites, delivery risk, team coverage, IPC signals and programme movement.</p>
  </div>
  <div class="manager-projects-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/assignments.php')) ?>"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Assignments</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/programme-of-works.php')) ?>"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/manager/messages.php')) ?>"><i class="fa-solid fa-comments" aria-hidden="true"></i> Message Team</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 manager-project-stats" aria-label="Project summary">
  <?php manager_project_stat('fa-building-circle-check', $summary['assigned_projects'], 'Assigned Projects', 'Projects under your care'); ?>
  <?php manager_project_stat('fa-circle-check', $summary['active_projects'], 'Active', 'Currently progressing'); ?>
  <?php manager_project_stat('fa-triangle-exclamation', $summary['delayed_projects'], 'Delayed', 'Past delivery date'); ?>
  <?php manager_project_stat('fa-chart-line', $summary['average_progress'] . '%', 'Average Progress', 'Across assigned sites'); ?>
  <?php manager_project_stat('fa-file-invoice-dollar', $summary['ipc_queue'], 'IPC Queue', 'Submitted/certified'); ?>
  <?php manager_project_stat('fa-bullseye', $summary['milestones_due'], 'Milestones Due', 'Next delivery window'); ?>
  <?php manager_project_stat('fa-clipboard-user', $summary['missing_clerks'], 'Missing Clerk', 'Coverage gaps'); ?>
  <?php manager_project_stat('fa-location-dot', $summary['missing_interns'], 'Missing Intern', 'Coverage gaps'); ?>
</section>

<section class="card manager-projects-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Assigned Projects</h2>
      <p class="card__subtitle">Filter by status, risk, team coverage and progress, then open a project for details.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> projects</span>
  </div>

  <form class="filter-bar manager-project-filter" method="get" action="<?= Security::e(Url::to('admin/manager/projects.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project, site, contractor..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (Project::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="constituency_id">Constituency</label><select class="form-select" id="constituency_id" name="constituency_id"><option value="">All constituencies</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= (int)($filters['constituency_id'] ?? 0) === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="progress">Progress</label><select class="form-select" id="progress" name="progress"><option value="">Any progress</option><?php foreach (['0-25', '26-50', '51-75', '76-100'] as $range): ?><option value="<?= Security::e($range) ?>" <?= (($filters['progress'] ?? '') === $range) ? 'selected' : '' ?>><?= Security::e($range) ?>%</option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="risk">Risk</label><select class="form-select" id="risk" name="risk"><option value="">Any risk</option><option value="attention" <?= (($filters['risk'] ?? '') === 'attention') ? 'selected' : '' ?>>Needs attention</option><option value="delayed" <?= (($filters['risk'] ?? '') === 'delayed') ? 'selected' : '' ?>>Delayed</option></select></div>
    <div class="filter-group"><label class="filter-label" for="coverage">Coverage</label><select class="form-select" id="coverage" name="coverage"><option value="">Any coverage</option><option value="missing-clerk" <?= (($filters['coverage'] ?? '') === 'missing-clerk') ? 'selected' : '' ?>>Missing clerk</option><option value="missing-intern" <?= (($filters['coverage'] ?? '') === 'missing-intern') ? 'selected' : '' ?>>Missing intern</option></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table manager-project-table">
      <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Next Milestone</th><th>Team Coverage</th><th>Signals</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($projects === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></span><strong class="empty-state__title">No assigned projects found</strong><span class="empty-state__text">Projects will appear here once your assignments are added.</span></div></td></tr>
<?php else: foreach ($projects as $project): ?>
        <tr>
          <td><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e(trim($project['constituency_name'] . ' / ' . ($project['ward_name'] ?: $project['location_label']), ' /')) ?></small></td>
          <td><span class="badge <?= Security::e(status_badge_class($project['status'])) ?>"><?= Security::e(status_label($project['status'])) ?></span><small><?= Security::e($project['risk_label']) ?> risk</small></td>
          <td><div class="manager-project-progress"><span style="width: <?= (int)$project['progress'] ?>%"></span></div><small><?= (int)$project['progress'] ?>% complete</small></td>
          <td><strong><?= Security::e($project['next_milestone'] ?: ($project['current_milestone'] ?: 'Not set')) ?></strong><small><?= Security::e(format_date($project['next_milestone_date'] ?: null)) ?></small></td>
          <td><strong><?= Security::e(format_number($project['clerk_count'])) ?> clerk / <?= Security::e(format_number($project['intern_count'])) ?> intern</strong><small><?= Security::e($project['contractor_name'] ?: 'No contractor') ?></small></td>
          <td><strong><?= Security::e(format_number($project['open_ipcs'])) ?> IPCs</strong><small><?= Security::e(format_number($project['overdue_tasks'])) ?> overdue tasks, <?= Security::e(format_number($project['present_today'])) ?> present today</small></td>
          <td>
            <div class="manager-project-actions">
              <button class="btn btn--icon btn--primary" type="button" data-project-detail="<?= (int)$project['id'] ?>" title="Open project monitor" aria-label="Open project monitor"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/manager/milestones.php?project_id=' . (int)$project['id'])) ?>" title="Milestones" aria-label="Milestones"><i class="fa-solid fa-bullseye" aria-hidden="true"></i></a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/manager/programme-of-works.php?project_id=' . (int)$project['id'])) ?>" title="Programme" aria-label="Programme"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i></a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/manager/boq.php?project_id=' . (int)$project['id'])) ?>" title="BOQ" aria-label="BOQ"><i class="fa-solid fa-list-check" aria-hidden="true"></i></a>
              <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/manager/assignments.php?project_id=' . (int)$project['id'])) ?>" title="Assignments" aria-label="Assignments"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></a>
            </div>
          </td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Manager project pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> projects</p>
    <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_projects_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_projects_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
  </nav>
<?php endif; ?>
</section>

<aside class="manager-project-drawer" data-project-drawer hidden>
  <div class="manager-project-drawer__panel">
    <div class="manager-project-drawer__header">
      <div><span class="sa-panel-label">Project monitor</span><h2 data-project-title>Project</h2><p data-project-meta></p></div>
      <button class="btn btn--icon btn--ghost" type="button" data-project-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="manager-project-drawer__body" data-project-detail-body>
      <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i></span><strong class="empty-state__title">Loading project</strong></div>
    </div>
  </div>
</aside>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_project_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function manager_projects_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null);
    return Url::to('admin/manager/projects.php?' . http_build_query($query));
}
