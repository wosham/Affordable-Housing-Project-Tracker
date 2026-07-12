<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$requestedProjectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectId = ContractorProject::defaultProjectId($userId, $role, $requestedProjectId);
$projects = ContractorProject::projects($userId, $role);
$project = $projectId > 0 ? ContractorProject::detail($projectId, $userId, $role) : null;
$summary = $project ? ContractorProject::summary($projectId, $userId, $role) : ContractorProject::summary(0, $userId, $role);
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => in_array((string)($_GET['status'] ?? ''), ['pending', 'not_started', 'in_progress', 'complete', 'completed', 'done', 'on_hold', 'delayed'], true) ? (string)$_GET['status'] : '',
    'critical' => Security::cleanInt($_GET['critical'] ?? 0),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total = $project ? ContractorProject::programmeCount($projectId, $userId, $role, $filters) : 0;
$pages = max(1, (int)ceil($total / $limit));
$tasks = $project ? ContractorProject::programmeList($projectId, $userId, $role, $filters, $limit, $offset) : [];
$focusItems = $project ? ContractorProject::programmeFocusItems($projectId, $userId, $role, 8) : [];
$dueSoon = contractor_programme_due_soon_count($projectId, $userId, $role);

$pageTitle = 'Programme of Works';
$pageDescription = 'View delivery activities, progress, dates and current programme signals.';
$adminRole = 'contractor';
$contentClass = 'contractor-phase-page';
$componentCss = ['contractor-phase'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Programme of Works'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-phase-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Delivery programme</span>
    <h2>Programme of Works</h2>
    <p>View planned activities, progress, dates and items that need attention on your assigned projects.</p>
  </div>
  <div class="contractor-phase-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/my-project.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> My Project</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/progress-update.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Progress Update</a>
  </div>
</section>

<?php if ($projects !== []): ?>
<form class="contractor-phase-selector card" method="get" action="<?= Security::e(Url::to('admin/contractor/programme-of-works.php')) ?>">
  <label for="project_id">Project</label>
  <select id="project_id" name="project_id" onchange="this.form.submit()">
    <?php foreach ($projects as $option): ?>
      <option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($option['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>

<?php if (!$project): ?>
  <div class="card empty-state">
    <strong class="empty-state__title">No assigned project found</strong>
    <span class="empty-state__text">Assigned project records will appear here once configured.</span>
  </div>
<?php else: ?>

<section class="contractor-phase-stats" aria-label="Programme summary">
  <?php contractor_phase_stat('fa-bars-progress', $summary['programme_total'], 'Tasks', 'Programme activities', programme_filter_url([])); ?>
  <?php contractor_phase_stat('fa-spinner', $summary['programme_in_progress'], 'In Progress', 'Active work items', programme_filter_url(['status' => 'in_progress'])); ?>
  <?php contractor_phase_stat('fa-circle-check', $summary['programme_completed'], 'Completed', 'Finished items', programme_filter_url(['status' => 'complete'])); ?>
  <?php contractor_phase_stat('fa-triangle-exclamation', $summary['programme_overdue'], 'Delayed', 'Needs attention', programme_filter_url(['status' => 'delayed'])); ?>
  <?php contractor_phase_stat('fa-chart-simple', $summary['programme_average'] . '%', 'Average Progress', 'Task completion', programme_filter_url([])); ?>
  <?php contractor_phase_stat('fa-calendar-day', $dueSoon, 'Due Soon', 'Next 14 days', programme_filter_url([])); ?>
</section>

<section class="contractor-phase-grid">
  <main class="card contractor-phase-panel">
    <div class="card__header">
      <div>
        <h2 class="card__title">Programme Register</h2>
        <p class="card__subtitle">Filter assigned project activities and track delivery dates.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="contractor-phase-filter" method="get" action="<?= Security::e(Url::to('admin/contractor/programme-of-works.php')) ?>">
      <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
      <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Task name or note..."></label>
      <label><span>Status</span>
        <select name="status">
          <option value="">All statuses</option>
          <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="not_started" <?= $filters['status'] === 'not_started' ? 'selected' : '' ?>>Not started</option>
          <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option>
          <option value="complete" <?= $filters['status'] === 'complete' ? 'selected' : '' ?>>Complete</option>
          <option value="on_hold" <?= $filters['status'] === 'on_hold' ? 'selected' : '' ?>>On hold</option>
          <option value="delayed" <?= $filters['status'] === 'delayed' ? 'selected' : '' ?>>Delayed</option>
        </select>
      </label>
      <label class="contractor-phase-check"><input type="checkbox" name="critical" value="1" <?= !empty($filters['critical']) ? 'checked' : '' ?>> <span>Priority items</span></label>
      <div class="contractor-phase-filter__actions">
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/programme-of-works.php' . ($projectId ? '?project_id=' . $projectId : ''))) ?>">Reset</a>
      </div>
    </form>

    <div class="contractor-phase-table-wrap">
      <table class="contractor-phase-table contractor-phase-table--programme">
        <thead>
          <tr>
            <th>Task</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Dates</th>
            <th>Signal</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($tasks === []): ?>
          <tr>
            <td colspan="5">
              <div class="empty-state empty-state--compact">
                <strong class="empty-state__title">No programme tasks found</strong>
                <span class="empty-state__text">Adjust filters or check the assigned project.</span>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($tasks as $task): ?>
          <?php
            $progress = percentage($task['pct_complete'] ?? 0);
            $end = $task['planned_end'] ?? $task['end_date'] ?? null;
            $delayed = $end && $end < date('Y-m-d') && !in_array((string)($task['status'] ?? ''), ['complete', 'completed', 'done', 'cancelled'], true);
          ?>
          <tr class="<?= $delayed ? 'is-delayed' : '' ?>">
            <td><strong><?= Security::e($task['task_name'] ?? '-') ?></strong><small><?= Security::e(safe_truncate((string)($task['notes'] ?? 'Programme activity'), 90)) ?></small></td>
            <td><span class="badge <?= Security::e(status_badge_class($task['status'] ?? 'pending')) ?>"><?= Security::e(status_label($task['status'] ?? 'pending')) ?></span></td>
            <td><div class="contractor-phase-progress"><span style="width: <?= (int)$progress ?>%"></span></div><small><?= (int)$progress ?>%</small></td>
            <td><?= Security::e(format_date($task['planned_start'] ?? $task['start_date'] ?? null)) ?> to <?= Security::e(format_date($end)) ?><small><?= Security::e(contractor_programme_date_signal($task)) ?></small></td>
            <td><strong><?= ((int)($task['critical_path'] ?? 0) === 1) ? 'Priority' : 'Normal' ?></strong><small><?= Security::e(contractor_programme_signal($task)) ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php contractor_phase_pagination($page, $pages, $total, $limit); ?>
  </main>

  <aside class="contractor-phase-side">
    <section class="card contractor-phase-panel">
      <h2>Current Focus</h2>
      <p class="contractor-phase-panel__intro">Open, overdue and priority tasks across this project (not limited to this page).</p>
      <div class="contractor-phase-list">
        <?php if ($focusItems === []): ?>
          <div class="empty-state empty-state--compact"><strong class="empty-state__title">No current focus items</strong></div>
        <?php else: foreach ($focusItems as $task): ?>
          <a class="contractor-phase-list-link" href="<?= Security::e(programme_filter_url(['q' => (string)$task['task_name']])) ?>">
            <strong><?= Security::e(safe_truncate((string)($task['task_name'] ?? ''), 54)) ?></strong>
            <small><?= (int)percentage($task['pct_complete'] ?? 0) ?>% / <?= Security::e(format_date($task['planned_end'] ?? $task['end_date'] ?? null)) ?></small>
          </a>
        <?php endforeach; endif; ?>
      </div>
      <?php if ((int)$summary['programme_overdue'] > 0): ?>
        <a class="btn btn--outline btn--sm" href="<?= Security::e(programme_filter_url(['status' => 'delayed'])) ?>">View delayed only</a>
      <?php endif; ?>
    </section>

    <section class="card contractor-phase-panel">
      <h2>Project Snapshot</h2>
      <div class="contractor-phase-metric-list">
        <a class="contractor-phase-metric-link" href="<?= Security::e(Url::to('admin/contractor/my-project.php?project_id=' . $projectId)) ?>">
          <strong><?= Security::e(safe_truncate($project['name'], 48)) ?></strong>
          <small><?= Security::e(trim(($project['constituency_name'] ?? '') . ' / ' . ($project['ward_name'] ?? ''), ' /')) ?> · Open project</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(Url::to('admin/contractor/progress-update.php?project_id=' . $projectId)) ?>">
          <strong><?= (int)$summary['programme_average'] ?>%</strong>
          <small>Average progress · Update progress</small>
        </a>
        <a class="contractor-phase-metric-link" href="<?= Security::e(programme_filter_url(['status' => 'delayed'])) ?>">
          <strong><?= format_number($summary['programme_overdue']) ?></strong>
          <small>Items needing attention</small>
        </a>
      </div>
    </section>
  </aside>
</section>

<?php endif; ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function programme_filter_url(array $extra): string
{
    $base = [
        'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
        'critical' => Security::cleanInt($_GET['critical'] ?? 0),
    ];
    $query = array_filter(array_merge($base, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/contractor/programme-of-works.php' . ($query ? '?' . http_build_query($query) : ''));
}

function contractor_programme_due_soon_count(int $projectId, int $userId, string $role): int
{
    if ($projectId <= 0 || !ContractorProject::canAccess($userId, $role, $projectId)) {
        return 0;
    }
    try {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total FROM programme_tasks
             WHERE project_id = ?
               AND COALESCE(status, '') NOT IN ('done','completed','complete','cancelled')
               AND COALESCE(planned_end, end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)",
            [$projectId]
        );
        return (int)($row['total'] ?? 0);
    } catch (Throwable) {
        return 0;
    }
}

function contractor_programme_signal(array $task): string
{
    $status = (string)($task['status'] ?? '');
    $end = strtotime((string)($task['planned_end'] ?? $task['end_date'] ?? ''));
    if (!in_array($status, ['complete', 'completed', 'done', 'cancelled'], true) && $end !== false && $end < strtotime(date('Y-m-d'))) {
        return 'Needs attention';
    }
    return ((int)($task['critical_path'] ?? 0) === 1) ? 'Priority activity' : 'On programme';
}

function contractor_programme_date_signal(array $task): string
{
    $status = (string)($task['status'] ?? '');
    $end = strtotime((string)($task['planned_end'] ?? $task['end_date'] ?? ''));
    if (in_array($status, ['complete', 'completed', 'done'], true)) {
        return 'Completed';
    }
    if ($end !== false && $end < strtotime(date('Y-m-d'))) {
        return 'Past target date';
    }
    return 'Target date';
}

function contractor_phase_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="contractor-phase-stat card"' . $href . '><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><small>' . Security::e($label) . '</small><em>' . Security::e($hint) . '</em></div></' . $tag . '>';
}

function contractor_phase_pagination(int $page, int $pages, int $total, int $limit): void
{
    if ($total <= 0) {
        return;
    }
    $from = min($total, (($page - 1) * $limit) + 1);
    $to = min($total, $page * $limit);
    $query = $_GET;
    echo '<div class="pagination"><span>Showing ' . format_number($from) . '-' . format_number($to) . ' of ' . format_number($total) . '</span><div>';
    $query['page'] = max(1, $page - 1);
    $prevDis = $page <= 1 ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $prevDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left"></i></a>';
    echo '<span class="btn btn--sm btn--primary">' . $page . ' / ' . $pages . '</span>';
    $query['page'] = min($pages, $page + 1);
    $nextDis = $page >= $pages ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $nextDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right"></i></a></div></div>';
}
