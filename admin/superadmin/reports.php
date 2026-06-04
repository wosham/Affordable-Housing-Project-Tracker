<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$pageTitle = 'Reports';
$pageDescription = 'Generate progress, financial, attendance, project and public content reports.';
$adminRole = 'superadmin';
$componentCss = ['cards', 'tables', 'reports'];
$pageScripts = ['report-builder'];
$contentClass = 'sa-reports-page';
$csrfForm = 'reports';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Reports'],
];

$types = ReportBuilder::typesForRole('superadmin');
$projects = ReportBuilder::projectsForFilter();
$constituencies = ReportBuilder::constituenciesForFilter();
$recentRuns = ReportBuilder::recentRuns(8);
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
?>

<section class="reports-hero card">
  <div>
    <span class="eyebrow"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Reports</span>
    <h2>Reports centre</h2>
    <p>Generate leadership-ready progress, financial, attendance, project register and public content reports from live programme data.</p>
  </div>
  <div class="reports-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/analytics.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Analytics</a>
    <button class="btn btn--primary" type="button" data-report-generate><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Generate Preview</button>
  </div>
</section>

<section class="reports-layout">
  <div class="reports-builder card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Report Builder</h2>
        <p class="card__subtitle">Choose the report scope, filters and output format.</p>
      </div>
    </div>

    <form class="report-form" data-report-form action="<?= Security::e(Url::to('api/reports/generate.php')) ?>" method="post">
      <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token($csrfForm)) ?>">
      <input type="hidden" name="type" value="executive_summary" data-report-type>

      <div class="report-type-grid" role="radiogroup" aria-label="Report type">
<?php foreach ($types as $key => $type): ?>
        <button class="report-type<?= $key === 'executive_summary' ? ' is-active' : '' ?>" type="button" data-report-type-choice="<?= Security::e($key) ?>" aria-pressed="<?= $key === 'executive_summary' ? 'true' : 'false' ?>">
          <span><i class="fa-solid <?= Security::e($type['icon']) ?>" aria-hidden="true"></i></span>
          <strong><?= Security::e($type['label']) ?></strong>
          <small><?= Security::e($type['description']) ?></small>
        </button>
<?php endforeach; ?>
      </div>

      <div class="report-filter-grid">
        <label class="form-field">
          <span class="form-label">Date from</span>
          <input class="form-input" type="date" name="date_from" value="<?= Security::e($defaultFrom) ?>">
        </label>
        <label class="form-field">
          <span class="form-label">Date to</span>
          <input class="form-input" type="date" name="date_to" value="<?= Security::e($defaultTo) ?>">
        </label>
        <label class="form-field">
          <span class="form-label">Project</span>
          <select class="form-select" name="project_id">
            <option value="">All projects</option>
<?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="form-field">
          <span class="form-label">Constituency</span>
          <select class="form-select" name="constituency_id">
            <option value="">All constituencies</option>
<?php foreach ($constituencies as $constituency): ?>
            <option value="<?= (int)$constituency['id'] ?>"><?= Security::e($constituency['name']) ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="form-field">
          <span class="form-label">Status</span>
          <select class="form-select" name="status">
            <option value="">Any status</option>
            <option value="planning">Planning</option>
            <option value="active">Active</option>
            <option value="stalled">Stalled</option>
            <option value="completed">Completed</option>
            <option value="geo-fail">GPS failed</option>
            <option value="present">Present</option>
          </select>
        </label>
        <label class="form-field">
          <span class="form-label">Output</span>
          <select class="form-select" name="format" data-report-format>
            <option value="json">Preview</option>
            <option value="csv">CSV export</option>
            <option value="html">Printable HTML</option>
          </select>
        </label>
      </div>

      <div class="report-actions">
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-play" aria-hidden="true"></i> Generate</button>
        <button class="btn btn--outline" type="button" data-report-download="csv"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> CSV</button>
        <button class="btn btn--outline" type="button" data-report-download="html"><i class="fa-solid fa-print" aria-hidden="true"></i> Print View</button>
        <button class="btn btn--ghost" type="reset"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</button>
      </div>
    </form>
  </div>

  <aside class="reports-recent card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Recent Runs</h2>
        <p class="card__subtitle">Latest generated reports.</p>
      </div>
    </div>
<?php if ($recentRuns === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><strong class="empty-state__title">No reports generated yet</strong><span class="empty-state__text">Generated reports will appear here after the first run.</span></div>
<?php else: ?>
    <div class="report-run-list">
<?php foreach ($recentRuns as $run): ?>
      <div class="report-run">
        <span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>
        <div>
          <strong><?= Security::e(ReportBuilder::TYPES[$run['report_type']]['label'] ?? status_label($run['report_type'])) ?></strong>
          <small><?= Security::e(strtoupper((string)$run['format'])) ?> by <?= Security::e($run['user_name']) ?> · <?= Security::e(time_ago($run['created_at'])) ?></small>
        </div>
        <em><?= Security::e(format_number($run['row_count'])) ?></em>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </aside>
</section>

<section class="card report-preview-card" data-report-preview-card>
  <div class="card__header">
    <div>
      <h2 class="card__title">Report Preview</h2>
      <p class="card__subtitle">Preview output appears here before export or print.</p>
    </div>
    <span class="badge badge--lime" data-report-count>Ready</span>
  </div>
  <div class="report-preview" data-report-preview>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i></span><strong class="empty-state__title">Choose a report and generate preview</strong><span class="empty-state__text">The builder will show summary metrics and report tables here.</span></div>
  </div>
</section>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>
