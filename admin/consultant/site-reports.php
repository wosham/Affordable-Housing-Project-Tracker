<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => trim((string)($_GET['status'] ?? '')),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$projects = ConsultantDocumentCentre::projects($userId, $role);
$summary = ConsultantDocumentCentre::siteReportSummary($userId, $role, $filters);
$reports = ConsultantDocumentCentre::siteReports($userId, $role, $filters, $limit, $offset);
$total = ConsultantDocumentCentre::siteReportCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));
$attention = ConsultantDocumentCentre::siteReportAttentionItems($userId, $role, $filters, 8);

$pageTitle = 'Site Reports';
$pageDescription = 'Review site diaries, field issues, next-day plans and consultant report actions.';
$adminRole = 'consultant';
$contentClass = 'consultant-documents-page doc-page';
$componentCss = ['consultant-documents'];
$pageScripts = ['consultant-documents'];
$csrfForm = 'consultant_reports';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Site Reports'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="doc-hero card" data-documents-app data-endpoint="<?= Security::e(Url::to('api/consultant/document-action.php')) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Site reporting</span>
    <h2>Site Reports</h2>
    <p>Review daily site records, field issues, next-day plans and project reporting signals on assigned projects only.</p>
  </div>
  <div class="doc-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/documents.php')) ?>"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> Documents</a>
    <button class="btn btn--outline" type="button" data-print-page><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
  </div>
</section>

<section class="doc-stats" aria-label="Site report summary">
  <?php doc_stat('fa-clipboard-list', $summary['total'], 'Reports', 'Assigned portfolio', site_filter_url([])); ?>
  <?php doc_stat('fa-calendar-week', $summary['this_week'], 'This Week', 'Recent records', site_filter_url([])); ?>
  <?php doc_stat('fa-hourglass-half', $summary['pending'], 'Pending', 'Needs review', site_filter_url(['status' => 'pending'])); ?>
  <?php doc_stat('fa-flag', $summary['flagged'], 'Flagged', 'Needs attention', site_filter_url(['status' => 'flagged'])); ?>
  <?php doc_stat('fa-circle-check', $summary['reviewed'], 'Reviewed', 'Closed or checked', site_filter_url(['status' => 'reviewed'])); ?>
  <?php doc_stat('fa-building', $summary['projects'], 'Projects', 'With reports', site_filter_url([])); ?>
</section>

<section class="doc-layout">
  <article class="doc-card card">
    <div class="doc-card__head">
      <div>
        <h2>Site report register</h2>
        <p>Filter reports, review daily records and return issues with clear action notes.</p>
      </div>
      <span class="badge badge--info"><?= format_number($total) ?> records</span>
    </div>

    <form class="doc-filter-grid doc-filter-grid--reports" method="get" action="<?= Security::e(Url::to('admin/consultant/site-reports.php')) ?>">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Report, project or issue..."></label>
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
          <option value="">All statuses</option>
          <?php foreach (ConsultantDocumentCentre::REVIEW_STATUSES as $status): ?>
            <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>From <input type="date" name="from" value="<?= Security::e($filters['from']) ?>"></label>
      <label>To <input type="date" name="to" value="<?= Security::e($filters['to']) ?>"></label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/site-reports.php')) ?>">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table doc-table">
        <thead>
          <tr><th>Report</th><th>Project</th><th>Work and Issues</th><th>Prepared By</th><th>Review</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if ($reports === []): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <strong class="empty-state__title">No site reports found</strong>
                  <span class="empty-state__text">Adjust filters or wait for daily diaries on your assigned projects.</span>
                </div>
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($reports as $report): ?>
            <?php $reportTitle = trim((string)($report['report_title'] ?? '')) ?: 'Daily site report'; ?>
            <tr>
              <td>
                <span class="doc-primary"><?= Security::e($reportTitle) ?></span>
                <span class="doc-secondary"><?= Security::e(format_date($report['diary_date'] ?? null)) ?></span>
                <span class="doc-secondary"><?= Security::e($report['weather_summary'] ?? '') ?></span>
              </td>
              <td>
                <span class="doc-primary"><?= Security::e($report['project_name']) ?></span>
                <span class="doc-secondary"><?= Security::e($report['constituency_name'] ?? '-') ?></span>
              </td>
              <td>
                <span class="doc-primary"><?= Security::e(safe_truncate($report['work_done'] ?? 'No work summary added.', 92)) ?></span>
                <span class="doc-secondary"><?= Security::e(safe_truncate($report['issues_raised'] ?? 'No site issues recorded.', 82)) ?></span>
                <span class="doc-secondary"><?= Security::e(safe_truncate($report['next_day_plan'] ?? 'No next-day plan added.', 82)) ?></span>
              </td>
              <td>
                <span><?= Security::e(trim((string)$report['recorded_by_name']) ?: 'System') ?></span>
                <span class="doc-secondary"><?= Security::e(format_datetime($report['created_at'] ?? null)) ?></span>
              </td>
              <td>
                <span class="badge <?= Security::e(ConsultantDocumentCentre::statusClass($report['consultant_review_status'] ?? 'pending')) ?>"><?= Security::e(status_label($report['consultant_review_status'] ?? 'pending')) ?></span>
                <span class="doc-secondary doc-note-preview"><?= Security::e(safe_truncate($report['consultant_review_note'] ?? '', 80)) ?></span>
              </td>
              <td>
                <div class="doc-row-actions">
                  <?php doc_action('site_report', (int)$report['id'], 'review', 'Mark reviewed', 'fa-check', $reportTitle . ' - ' . $report['project_name']); ?>
                  <?php doc_action('site_report', (int)$report['id'], 'return', 'Return report', 'fa-rotate-left', $reportTitle . ' - ' . $report['project_name']); ?>
                  <?php doc_action('site_report', (int)$report['id'], 'flag', 'Flag report', 'fa-flag', $reportTitle . ' - ' . $report['project_name']); ?>
                  <?php doc_action('site_report', (int)$report['id'], 'close', 'Close report', 'fa-lock', $reportTitle . ' - ' . $report['project_name']); ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php doc_pagination($page, $pages, $total, $limit); ?>
  </article>

  <aside class="doc-card card">
    <h2>Report focus</h2>
    <p>Open reporting items across your portfolio (not limited to this page).</p>
    <div class="doc-side-list">
      <?php if ($attention === []): ?>
        <div class="empty-state empty-state--compact">
          <strong class="empty-state__title">No open report reviews</strong>
          <span class="empty-state__text">Pending or flagged diaries will appear here.</span>
        </div>
      <?php else: foreach ($attention as $report): ?>
        <a class="doc-side-item doc-side-item--link" href="<?= Security::e(Url::to('admin/consultant/site-reports.php?project_id=' . (int)$report['project_id'] . '&status=' . rawurlencode((string)($report['consultant_review_status'] ?? 'pending')))) ?>">
          <strong><?= Security::e(trim((string)($report['report_title'] ?? '')) ?: 'Daily site report') ?></strong>
          <span><?= Security::e($report['project_name']) ?> · <?= Security::e(format_date($report['diary_date'] ?? null)) ?> · <?= Security::e(status_label($report['consultant_review_status'] ?? 'pending')) ?></span>
        </a>
      <?php endforeach; endif; ?>
    </div>
    <?php if ((int)$summary['pending'] > 0): ?>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(site_filter_url(['status' => 'pending'])) ?>">View pending only</a>
    <?php endif; ?>
  </aside>
</section>

<?php doc_modal(); ?>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function site_filter_url(array $extra): string
{
    $query = array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/consultant/site-reports.php' . ($query ? '?' . http_build_query($query) : ''));
}

function doc_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="doc-stat card"' . $href . '><span class="doc-stat__icon"><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e(is_numeric($value) ? format_number($value) : (string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function doc_action(string $type, int $id, string $action, string $label, string $icon, string $item): void
{
    echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-doc-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label) . '" data-item="' . Security::e($item) . '"><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i><span class="sr-only">' . Security::e($label) . '</span></button>';
}

function doc_pagination(int $page, int $pages, int $total, int $limit): void
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
    echo '<a class="btn btn--sm btn--outline' . $prevDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>';
    echo '<span class="btn btn--sm btn--primary">' . format_number($page) . ' / ' . format_number($pages) . '</span>';
    $query['page'] = min($pages, $page + 1);
    $nextDis = $page >= $pages ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $nextDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div></div>';
}

function doc_modal(): void
{
    ?>
    <div class="doc-modal" data-doc-modal hidden>
      <div class="doc-modal__panel" role="dialog" aria-modal="true" aria-labelledby="docModalTitle">
        <div class="doc-modal__head">
          <div>
            <strong id="docModalTitle" data-modal-title>Review</strong>
            <span class="doc-secondary" data-modal-item></span>
          </div>
          <button class="btn btn--sm btn--ghost" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <form>
          <input type="hidden" name="type">
          <input type="hidden" name="id">
          <input type="hidden" name="action">
          <div class="doc-modal__body">
            <label>Review note <textarea name="note" placeholder="Add a clear note for the project team."></textarea></label>
          </div>
          <div class="doc-modal__foot">
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Review</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
