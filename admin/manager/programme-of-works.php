<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('manager');

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerProgramme::projects($userId, $role);
$defaultProjectId = 0;
foreach ($projects as $projectOption) {
    if ((int)($projectOption['task_count'] ?? 0) > 0) {
        $defaultProjectId = (int)$projectOption['id'];
        break;
    }
}
if ($defaultProjectId === 0 && isset($projects[0])) {
    $defaultProjectId = (int)$projects[0]['id'];
}

$filters = [
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'assigned_to' => Security::cleanInt($_GET['assigned_to'] ?? 0),
    'delay' => Security::cleanString((string)($_GET['delay'] ?? '')),
    'date_from' => ProgrammeTask::normaliseDate($_GET['date_from'] ?? ''),
    'date_to' => ProgrammeTask::normaliseDate($_GET['date_to'] ?? ''),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

if (!empty($filters['project_id']) && !ManagerProgramme::canAccessProject($userId, $role, (int)$filters['project_id'])) {
    Response::abort(403, 'You do not have access to this project programme.');
}

$perPage = 30;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalTasks = !empty($filters['project_id']) ? ProgrammeTask::countItems($filters) : 0;
$totalPages = max(1, (int)ceil($totalTasks / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$tasks = !empty($filters['project_id']) ? array_map([ProgrammeTask::class, 'payload'], ProgrammeTask::items($filters, $perPage, $offset)) : [];
$summary = !empty($filters['project_id']) ? ProgrammeTask::summary($filters) : [];
$timeline = ProgrammeTask::timeline($tasks);
$assignees = ProgrammeTask::assignees((int)($filters['project_id'] ?? 0) ?: null);
$assignableUsers = !empty($filters['project_id']) ? ManagerProgramme::assignableUsers($userId, $role, (int)$filters['project_id']) : [];
$dependencies = !empty($filters['project_id']) ? ProgrammeTask::dependenciesForProject((int)$filters['project_id']) : [];
$selectedProject = null;
foreach ($projects as $projectOption) {
    if ((int)$projectOption['id'] === (int)($filters['project_id'] ?? 0)) {
        $selectedProject = $projectOption;
        break;
    }
}

$timelineStartTs = strtotime($timeline['start']);
$timelineDays = max(1, (int)$timeline['days']);
$avgProgress = percentage($summary['avg_progress'] ?? 0);
$durationDays = ProgrammeTask::durationDays($summary['timeline_start'] ?? null, $summary['timeline_end'] ?? null);
$dueSoon = !empty($filters['project_id']) ? ManagerProgramme::dueSoonCount($filters) : 0;
$showingFrom = $totalTasks > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($tasks), $totalTasks);

$pageTitle = 'Programme of Works';
$pageDescription = 'Track planned dates, actual dates, completion and delivery delays for assigned projects.';
$adminRole = 'manager';
$csrfForm = 'manager_programme';
$contentClass = 'manager-programme-page';
$componentCss = ['gantt', 'manager-programme'];
$pageScripts = ['gantt', 'manager-programme'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Programme of Works'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="programme-hero card manager-programme-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme control</span>
    <h2>Programme of Works</h2>
    <p>Track planned dates, actual dates, completion and delivery delays for your assigned projects only.</p>
  </div>
  <div class="programme-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/milestones.php')) ?>"><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Milestones</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/boq.php' . (!empty($filters['project_id']) ? '?project_id=' . (int)$filters['project_id'] : ''))) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> BOQ</a>
  </div>
</section>

<section class="card programme-project-strip manager-programme-project-strip">
  <form class="programme-project-form" method="get" action="<?= Security::e(Url::to('admin/manager/programme-of-works.php')) ?>">
    <label class="filter-group programme-project-picker" for="project_id">
      <span class="filter-label">Project</span>
      <select class="form-select" id="project_id" name="project_id" data-programme-project-picker>
<?php if ($projects === []): ?>
        <option value="">No assigned projects</option>
<?php else: foreach ($projects as $projectOption): ?>
        <option value="<?= (int)$projectOption['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$projectOption['id'] ? 'selected' : '' ?>>
          <?= Security::e($projectOption['name']) ?> - <?= Security::e(format_number($projectOption['task_count'])) ?> tasks
        </option>
<?php endforeach; endif; ?>
      </select>
    </label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Open</button>
  </form>
<?php if ($selectedProject): ?>
  <div class="programme-project-meta">
    <strong><?= Security::e($selectedProject['name']) ?></strong>
    <span><?= Security::e($selectedProject['constituency_name'] ?: 'No constituency') ?></span>
    <span><?= Security::e(format_percentage($selectedProject['avg_progress'] ?? 0)) ?> average progress</span>
  </div>
<?php endif; ?>
</section>

<section class="stat-grid stat-grid--4 programme-stats" aria-label="Programme summary">
  <?php manager_programme_stat('fa-list-check', $summary['total_tasks'] ?? 0, 'Total Tasks', 'Programme activities', false, manager_programme_page_url(array_diff_key($filters, ['status' => true, 'delay' => true, 'page' => true]), 1)); ?>
  <?php manager_programme_stat('fa-spinner', $summary['in_progress'] ?? 0, 'In Progress', 'Active delivery tasks', false, manager_programme_page_url(array_merge($filters, ['status' => 'in_progress', 'page' => 1]), 1)); ?>
  <?php manager_programme_stat('fa-circle-check', $summary['complete'] ?? 0, 'Completed', 'Finished activities', false, manager_programme_page_url(array_merge($filters, ['status' => 'complete', 'page' => 1]), 1)); ?>
  <?php manager_programme_stat('fa-triangle-exclamation', $summary['delayed_tasks'] ?? 0, 'Delayed', 'Past planned finish', false, manager_programme_page_url(array_merge($filters, ['delay' => 'delayed', 'page' => 1]), 1)); ?>
  <?php manager_programme_stat('fa-route', $summary['critical'] ?? 0, 'Critical Path', 'Priority activities'); ?>
  <?php manager_programme_stat('fa-clock', $dueSoon, 'Due Soon', 'Next seven days', false, manager_programme_page_url(array_merge($filters, ['delay' => 'due', 'page' => 1]), 1)); ?>
  <?php manager_programme_stat('fa-chart-simple', $avgProgress, 'Average Progress', 'Across listed tasks', true); ?>
  <?php manager_programme_stat('fa-pause', $summary['on_hold'] ?? 0, 'On Hold', 'Paused activities', false, manager_programme_page_url(array_merge($filters, ['status' => 'on_hold', 'page' => 1]), 1)); ?>
</section>

<section class="card programme-board">
  <div class="card__header">
    <div>
      <h2 class="card__title">Gantt Timeline</h2>
      <p class="card__subtitle">Planned bars sit above actual bars. The vertical marker shows today.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalTasks)) ?> tasks</span>
  </div>

  <form class="filter-bar programme-filter" method="get" action="<?= Security::e(Url::to('admin/manager/programme-of-works.php')) ?>">
    <input type="hidden" name="project_id" value="<?= Security::e((string)($filters['project_id'] ?? '')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Task or project..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ProgrammeTask::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="assigned_to">Assigned</label><select class="form-select" id="assigned_to" name="assigned_to"><option value="">Anyone</option><?php foreach ($assignees as $assignee): ?><option value="<?= (int)$assignee['id'] ?>" <?= (int)($filters['assigned_to'] ?? 0) === (int)$assignee['id'] ? 'selected' : '' ?>><?= Security::e($assignee['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="delay">State</label><select class="form-select" id="delay" name="delay"><option value="">Any state</option><?php foreach (ProgrammeTask::delayOptions() as $delay): ?><option value="<?= Security::e($delay) ?>" <?= (($filters['delay'] ?? '') === $delay) ? 'selected' : '' ?>><?= Security::e(status_label($delay)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/programme-of-works.php?project_id=' . (int)($filters['project_id'] ?? 0))) ?>">Reset</a></div>
  </form>

  <div class="gantt-wrap" data-gantt-board data-start="<?= Security::e($timeline['start']) ?>" data-days="<?= (int)$timelineDays ?>">
    <div class="gantt-scale" style="--gantt-days: <?= (int)$timelineDays ?>;">
      <span><?= Security::e(format_date($timeline['start'])) ?></span>
      <span><?= Security::e(format_date(date('Y-m-d'))) ?></span>
      <span><?= Security::e(format_date($timeline['end'])) ?></span>
      <i class="gantt-today" style="left: <?= Security::e((string)manager_programme_gantt_offset(date('Y-m-d'), $timelineStartTs, $timelineDays)) ?>%;"></i>
    </div>
<?php if ($tasks === []): ?>
    <div class="empty-state programme-empty"><span class="empty-state__icon"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i></span><strong class="empty-state__title">No programme tasks found</strong><span class="empty-state__text">Programme tasks will appear here once created for this project.</span></div>
<?php else: ?>
    <div class="gantt-list">
<?php foreach ($tasks as $task): ?>
<?php
    $plannedStart = $task['planned_start'] ?: $task['start_date'];
    $plannedEnd = $task['planned_end'] ?: $task['end_date'];
    $actualStart = $task['start_date'] ?: $plannedStart;
    $actualEnd = $task['end_date'] ?: $plannedEnd;
    [$plannedLeft, $plannedWidth] = manager_programme_gantt_bar($plannedStart, $plannedEnd, $timelineStartTs, $timelineDays);
    [$actualLeft, $actualWidth] = manager_programme_gantt_bar($actualStart, $actualEnd, $timelineStartTs, $timelineDays);
?>
      <article class="gantt-row gantt-row--<?= Security::e($task['delay_state']) ?><?= $task['critical_path'] ? ' is-critical' : '' ?>">
        <div class="gantt-task"><strong><?= Security::e($task['task_name']) ?></strong><small><?= Security::e($task['assignee_name'] ?: 'Unassigned') ?><?= $task['dependency_name'] ? ' / after ' . Security::e($task['dependency_name']) : '' ?></small></div>
        <div class="gantt-bars"><span class="gantt-bar gantt-bar--planned" style="left: <?= Security::e((string)$plannedLeft) ?>%; width: <?= Security::e((string)$plannedWidth) ?>%;"><i><?= Security::e(format_percentage($task['pct_complete'])) ?></i></span><span class="gantt-bar gantt-bar--actual" style="left: <?= Security::e((string)$actualLeft) ?>%; width: <?= Security::e((string)$actualWidth) ?>%;"></span></div>
      </article>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
</section>

<section class="card programme-table-card">
  <div class="card__header"><div><h2 class="card__title">Task Register</h2><p class="card__subtitle">Update progress, dates, assignment, dependency and task state.</p></div></div>
  <div class="table-wrap">
    <table class="data-table programme-table">
      <thead><tr><th>Task</th><th>Planned</th><th>Actual</th><th>Progress</th><th>Status</th><th>Dependency</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($tasks === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span><strong class="empty-state__title">No task rows</strong><span class="empty-state__text">No programme tasks match the selected filters.</span></div></td></tr>
<?php else: foreach ($tasks as $task): ?>
        <tr>
          <td><strong><?= Security::e($task['task_name']) ?></strong><small><?= Security::e($task['project_name']) ?><?= $task['critical_path'] ? ' / Critical path' : '' ?></small></td>
          <td><?= Security::e(format_date($task['planned_start'])) ?><small>to <?= Security::e(format_date($task['planned_end'])) ?></small></td>
          <td><?= Security::e(format_date($task['start_date'])) ?><small>to <?= Security::e(format_date($task['end_date'])) ?></small></td>
          <td><div class="programme-progress"><span style="width: <?= (int)$task['pct_complete'] ?>%;"></span></div><small><?= Security::e(format_percentage($task['pct_complete'])) ?></small></td>
          <td><span class="badge <?= Security::e(status_badge_class($task['is_delayed'] ? 'overdue' : $task['status'])) ?>"><?= Security::e($task['is_delayed'] ? 'Delayed' : status_label($task['status'])) ?></span></td>
          <td><?= $task['dependency_name'] ? Security::e($task['dependency_name']) : '<span class="text-muted">None</span>' ?></td>
          <td><div class="manager-table-actions"><button class="btn btn--icon btn--primary" type="button" data-programme-edit data-id="<?= (int)$task['id'] ?>" data-name="<?= Security::e($task['task_name']) ?>" data-planned-start="<?= Security::e($task['planned_start'] ?? '') ?>" data-planned-end="<?= Security::e($task['planned_end'] ?? '') ?>" data-start-date="<?= Security::e($task['start_date'] ?? '') ?>" data-end-date="<?= Security::e($task['end_date'] ?? '') ?>" data-progress="<?= (int)$task['pct_complete'] ?>" data-status="<?= Security::e($task['status']) ?>" data-assigned="<?= Security::e((string)($task['assigned_to'] ?? '')) ?>" data-dependency="<?= Security::e((string)($task['depends_on_task_id'] ?? '')) ?>" data-critical="<?= (int)$task['critical_path'] ?>" data-notes="<?= Security::e($task['notes'] ?? '') ?>" title="Update task" aria-label="Update task"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button></div></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <nav class="pagination" aria-label="Programme pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalTasks)) ?> tasks (<?= (int)$perPage ?> per page)</p>
    <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_programme_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number($totalPages)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_programme_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
  </nav>
</section>

<div class="modal" data-programme-modal hidden>
  <div class="modal__backdrop" data-programme-close></div>
  <div class="modal__dialog programme-modal" role="dialog" aria-modal="true" aria-labelledby="programmeModalTitle">
    <div class="modal__header"><h2 class="modal__title" id="programmeModalTitle">Update programme task</h2><button class="modal__close" type="button" data-programme-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
    <form data-programme-form>
      <input type="hidden" name="id" data-programme-field="id">
      <div class="modal__body">
        <label class="form-field"><span>Task name</span><input class="form-input" type="text" name="task_name" maxlength="200" required data-programme-field="task_name"></label>
        <div class="form-grid form-grid--2">
          <label class="form-field"><span>Planned start</span><input class="form-input" type="date" name="planned_start" data-programme-field="planned_start"></label>
          <label class="form-field"><span>Planned end</span><input class="form-input" type="date" name="planned_end" data-programme-field="planned_end"></label>
          <label class="form-field"><span>Actual start</span><input class="form-input" type="date" name="start_date" data-programme-field="start_date"></label>
          <label class="form-field"><span>Actual end</span><input class="form-input" type="date" name="end_date" data-programme-field="end_date"></label>
          <label class="form-field"><span>Progress</span><input class="form-input" type="number" name="pct_complete" min="0" max="100" step="1" data-programme-field="pct_complete"></label>
          <label class="form-field"><span>Status</span><select class="form-select" name="status" data-programme-field="status"><?php foreach (ProgrammeTask::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span>Assigned to</span><select class="form-select" name="assigned_to" data-programme-field="assigned_to"><option value="">Unassigned</option><?php foreach ($assignableUsers as $user): ?><option value="<?= (int)$user['id'] ?>"><?= Security::e($user['name']) ?><?= $user['role'] ? ' - ' . Security::e(role_label($user['role'])) : '' ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span>Depends on</span><select class="form-select" name="depends_on_task_id" data-programme-field="depends_on_task_id"><option value="">No dependency</option><?php foreach ($dependencies as $dependency): ?><option value="<?= (int)$dependency['id'] ?>"><?= Security::e($dependency['task_name']) ?></option><?php endforeach; ?></select></label>
        </div>
        <label class="checkbox-card"><input type="checkbox" name="critical_path" value="1" data-programme-field="critical_path"> <span>Mark as critical path</span></label>
        <label class="form-field"><span>Notes</span><textarea class="form-textarea" name="notes" rows="3" data-programme-field="notes"></textarea></label>
        <div class="alert alert--warning" data-programme-warning hidden></div>
        <div class="alert alert--danger" data-programme-error hidden></div>
      </div>
      <div class="modal__footer"><button class="btn btn--outline" type="button" data-programme-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Task</button></div>
    </form>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_programme_stat(string $icon, mixed $value, string $label, string $trend, bool $percent = false, ?string $href = null): void
{
    $display = $percent ? format_percentage($value) : format_number($value);
    $tag = $href ? 'a' : 'article';
    $attr = $href ? ' href="' . Security::e($href) . '"' : '';
?>
  <<?= $tag ?> class="stat-widget<?= $href ? ' stat-widget--link' : '' ?>"<?= $attr ?>><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e($display) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></<?= $tag ?>>
<?php
}

function manager_programme_gantt_bar(?string $start, ?string $end, int $timelineStartTs, int $timelineDays): array
{
    if (!$start || !$end || strtotime($start) === false || strtotime($end) === false) {
        return [0, 0];
    }
    $left = manager_programme_gantt_offset($start, $timelineStartTs, $timelineDays);
    $right = manager_programme_gantt_offset($end, $timelineStartTs, $timelineDays);
    return [$left, max(2, $right - $left)];
}

function manager_programme_gantt_offset(string $date, int $timelineStartTs, int $timelineDays): int
{
    $dateTs = strtotime($date);
    if ($dateTs === false) {
        return 0;
    }
    $days = (int)floor(($dateTs - $timelineStartTs) / 86400);
    return percentage(($days / max(1, $timelineDays)) * 100);
}

function manager_programme_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
    return Url::to('admin/manager/programme-of-works.php?' . http_build_query($query));
}
