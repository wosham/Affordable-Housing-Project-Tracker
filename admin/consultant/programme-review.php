<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => trim((string)($_GET['status'] ?? '')),
    'delay' => trim((string)($_GET['delay'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$projects = ConsultantTechnicalReview::projects($userId, $role);
$summary = ConsultantTechnicalReview::programmeSummary($userId, $role, $filters);
$items = ConsultantTechnicalReview::programmeTasks($userId, $role, $filters, $limit, $offset);
$total = ConsultantTechnicalReview::programmeCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));
$signals = ConsultantTechnicalReview::programmeSignalItems($userId, $role, $filters, 8);

$pageTitle = 'Programme Review';
$pageDescription = 'Review programme movement, delayed tasks and critical project activities.';
$adminRole = 'consultant';
$csrfForm = 'consultant_technical';
$contentClass = 'consultant-technical-page';
$componentCss = ['consultant-technical'];
$pageScripts = ['consultant-technical'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Programme Review'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="technical-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i> Programme review</span>
    <h2>Programme Review</h2>
    <p>Review task progress, delivery dates, critical activities and items requiring project team correction on your assigned projects only.</p>
  </div>
  <div class="technical-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/boq-review.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> BOQ Review</a>
  </div>
</section>

<section class="technical-stats" aria-label="Programme summary">
  <?php technical_stat('fa-bars-progress', $summary['total'], 'Tasks', 'Assigned projects', programme_filter_url([])); ?>
  <?php technical_stat('fa-hourglass-half', $summary['pending'], 'Pending Review', 'Awaiting action', programme_filter_url(['status' => 'pending'])); ?>
  <?php technical_stat('fa-circle-check', $summary['approved'], 'Accepted', 'Reviewed tasks', programme_filter_url(['status' => 'approved'])); ?>
  <?php technical_stat('fa-triangle-exclamation', $summary['delayed_tasks'], 'Delayed', 'Past planned finish', programme_filter_url(['delay' => 'delayed'])); ?>
  <?php technical_stat('fa-route', $summary['critical'], 'Critical Path', 'Critical activities', programme_filter_url(['delay' => 'critical'])); ?>
</section>

<section class="technical-layout">
  <article class="technical-card card">
    <div class="technical-card__head">
      <div>
        <h2>Task review register</h2>
        <p>Filter programme records and record consultant review decisions.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="technical-filter-grid" method="get" action="<?= Security::e(Url::to('admin/consultant/programme-review.php')) ?>">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Task or project..."></label>
      <label>Project
        <select name="project_id">
          <option value="0">All assigned projects</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Review
        <select name="status">
          <option value="">All review statuses</option>
          <?php foreach (['pending', 'approved', 'needs-revision', 'flagged'] as $status): ?>
            <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Signal
        <select name="delay">
          <option value="">Any signal</option>
          <option value="delayed" <?= $filters['delay'] === 'delayed' ? 'selected' : '' ?>>Delayed</option>
          <option value="critical" <?= $filters['delay'] === 'critical' ? 'selected' : '' ?>>Critical path</option>
        </select>
      </label>
      <label>From <input type="date" name="from" value="<?= Security::e($filters['from']) ?>"></label>
      <label>To <input type="date" name="to" value="<?= Security::e($filters['to']) ?>"></label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/programme-review.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table technical-table">
        <thead><tr><th>Task</th><th>Project</th><th>Progress</th><th>Dates</th><th>Review</th><th>Updated</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($items === []): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <strong class="empty-state__title">No programme tasks found</strong>
                <span class="empty-state__text">Adjust filters or wait for programme data on your assigned projects.</span>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <?php
            $plannedEnd = $item['planned_end'] ?: $item['end_date'];
            $delayed = $plannedEnd && $plannedEnd < date('Y-m-d') && !in_array((string)$item['status'], ['complete', 'cancelled'], true);
          ?>
          <tr class="<?= $delayed ? 'is-delayed' : '' ?>">
            <td>
              <span class="technical-primary"><?= Security::e($item['task_name']) ?></span>
              <span class="technical-secondary"><?= Security::e($item['notes'] ? safe_truncate($item['notes'], 100) : 'No note added') ?></span>
            </td>
            <td>
              <span class="technical-primary"><?= Security::e($item['project_name']) ?></span>
              <span class="technical-secondary"><?= Security::e($item['assignee_name'] ?: 'Unassigned') ?></span>
            </td>
            <td class="technical-progress">
              <span><?= percentage($item['pct_complete']) ?>%</span>
              <div class="technical-progress__bar"><span style="width: <?= percentage($item['pct_complete']) ?>%"></span></div>
              <span class="technical-secondary"><?= Security::e(status_label($item['status'])) ?><?= (int)$item['critical_path'] === 1 ? ' · Critical' : '' ?></span>
            </td>
            <td>
              <span><?= Security::e(format_date($item['planned_start'] ?: $item['start_date'])) ?> to <?= Security::e(format_date($plannedEnd)) ?></span>
              <span class="technical-secondary<?= $delayed ? ' text-danger' : '' ?>"><?= $delayed ? 'Past planned finish' : ((int)$item['critical_path'] === 1 ? 'Critical path' : 'On schedule') ?></span>
            </td>
            <td>
              <span class="badge <?= Security::e(ConsultantTechnicalReview::statusClass((string)($item['consultant_review_status'] ?? 'pending'))) ?>"><?= Security::e(status_label($item['consultant_review_status'] ?? 'pending')) ?></span>
              <span class="technical-secondary"><?= Security::e($item['consultant_review_note'] ? safe_truncate($item['consultant_review_note'], 80) : '') ?></span>
            </td>
            <td>
              <span><?= Security::e($item['reviewed_by_name'] ?: '-') ?></span>
              <span class="technical-secondary"><?= Security::e(format_datetime($item['consultant_reviewed_at'] ?? null)) ?></span>
            </td>
            <td>
              <div class="technical-row-actions">
                <?php technical_action('programme', (int)$item['id'], 'review', 'Accept', 'fa-check', $item['task_name'] . ' - ' . $item['project_name']); ?>
                <?php technical_action('programme', (int)$item['id'], 'return', 'Return', 'fa-rotate-left', $item['task_name'] . ' - ' . $item['project_name']); ?>
                <?php technical_action('programme', (int)$item['id'], 'flag', 'Flag', 'fa-flag', $item['task_name'] . ' - ' . $item['project_name']); ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php technical_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="technical-card card">
    <h2>Portfolio signals</h2>
    <p>Delayed, critical-path and pending-review tasks across your projects (not limited to this page).</p>
    <div class="technical-side-list">
      <?php if ($signals === []): ?>
        <div class="empty-state empty-state--compact">
          <strong class="empty-state__title">No programme signals</strong>
          <span class="empty-state__text">Delayed or critical tasks will appear here.</span>
        </div>
      <?php else: foreach ($signals as $item): ?>
        <?php
          $end = $item['planned_end'] ?: $item['end_date'];
          $isDelayed = $end && $end < date('Y-m-d') && !in_array((string)$item['status'], ['complete', 'cancelled'], true);
          $chip = $isDelayed ? 'Delayed' : ((int)$item['critical_path'] === 1 ? 'Critical' : status_label($item['consultant_review_status'] ?? 'pending'));
        ?>
        <a class="technical-side-item technical-side-item--link" href="<?= Security::e(Url::to('admin/consultant/programme-review.php?project_id=' . (int)$item['project_id'] . '&q=' . rawurlencode((string)$item['task_name']))) ?>">
          <strong><?= Security::e($item['task_name']) ?></strong>
          <span><?= Security::e($item['project_name']) ?> · <?= percentage($item['pct_complete']) ?>% · <?= Security::e($chip) ?></span>
        </a>
      <?php endforeach; endif; ?>
    </div>
    <?php if ((int)$summary['delayed_tasks'] > 0): ?>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(programme_filter_url(['delay' => 'delayed'])) ?>">View delayed only</a>
    <?php endif; ?>
  </aside>
</section>

<?php technical_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function programme_filter_url(array $extra): string
{
    $query = array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/consultant/programme-review.php' . ($query ? '?' . http_build_query($query) : ''));
}

function technical_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="technical-stat card"' . $href . '><span class="technical-stat__icon"><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e(is_numeric($value) ? format_number($value) : (string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function technical_action(string $type, int $id, string $action, string $label, string $icon, string $item): void
{
    echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-technical-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label . ' task') . '" data-item="' . Security::e($item) . '"><i class="fa-solid ' . Security::e($icon) . '"></i><span class="sr-only">' . Security::e($label) . '</span></button>';
}

function technical_pagination(int $page, int $pages, int $total, int $limit): void
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

function technical_modal(): void
{
    ?>
    <div class="technical-modal" data-technical-modal hidden>
      <div class="technical-modal__panel">
        <div class="technical-modal__head">
          <div>
            <strong data-modal-title>Review item</strong>
            <span class="technical-secondary" data-modal-item></span>
          </div>
          <button class="btn btn--sm btn--ghost" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form>
          <input type="hidden" name="type"><input type="hidden" name="id"><input type="hidden" name="action">
          <div class="technical-modal__body">
            <label>Review note <textarea name="note" rows="5" placeholder="Add a clear note for the project team."></textarea></label>
          </div>
          <div class="technical-modal__foot">
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save review</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
