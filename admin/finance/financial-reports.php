<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$clean = FinanceReports::cleanInput($_GET);
$report = FinanceReports::generate($clean['type'], $clean['filters']);
$stats = FinancePayment::dashboard();
$projects = FinanceBudget::projects();
$filterSupport = $report['filter_support'] ?? FinanceReports::filterSupport($clean['type']);
$typeLabels = FinanceReports::typeLabels();

// Log intentional generate (query present), not every bare page open.
$shouldLogPreview = $_GET !== [] && Auth::id();
if ($shouldLogPreview) {
    FinanceReports::recordRun((int)Auth::id(), $clean['type'], 'preview', $clean['filters'], (int)$report['row_count']);
}
$runs = FinanceReports::recentRuns(8);

$queryBase = array_merge($clean['filters'], ['type' => $clean['type']]);
$printUrl = Url::to('api/finance/generate-report.php?' . http_build_query(array_merge($queryBase, ['format' => 'html', 'print' => 1])));
$viewUrl = Url::to('api/finance/generate-report.php?' . http_build_query(array_merge($queryBase, ['format' => 'html'])));
$csvUrl = Url::to('api/finance/generate-report.php?' . http_build_query(array_merge($queryBase, ['format' => 'csv'])));

$pageTitle = 'Financial Reports';
$pageDescription = 'Generate printable finance summaries and CSV exports.';
$adminRole = 'finance';
$csrfForm = 'finance_reports';
$contentClass = 'finance-payments-page finance-report-page';
$componentCss = ['finance-payments', 'finance-reports'];
$pageScripts = ['finance-payments'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Financial Reports'],
];
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="finance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Finance reports</span>
    <h2>Financial Reports</h2>
    <p>Build payment, budget, retention, damages and monthly finance summaries. Print professionally or export CSV.</p>
  </div>
  <div class="finance-hero__actions">
    <a class="btn btn--primary" target="_blank" rel="noopener" href="<?= Security::e($printUrl) ?>"><i class="fa-solid fa-print" aria-hidden="true"></i> Print / PDF</a>
    <a class="btn btn--outline" target="_blank" rel="noopener" href="<?= Security::e($viewUrl) ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i> Printable view</a>
    <a class="btn btn--outline" href="<?= Security::e($csvUrl) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
  </div>
</section>

<section class="finance-stat-grid finance-stat-grid--3" aria-label="Finance report snapshot">
  <a class="finance-stat card finance-stat--link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>">
    <span class="finance-stat__icon"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(format_money($stats['approved_value'])) ?></strong><em>Awaiting Payment</em><small><?= Security::e(format_number($stats['approved_count'])) ?> IPCs</small></span>
  </a>
  <a class="finance-stat card finance-stat--link" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>">
    <span class="finance-stat__icon"><i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(format_money($stats['paid_value'])) ?></strong><em>Paid to Date</em><small><?= Security::e(format_number($stats['payment_count'])) ?> payments</small></span>
  </a>
  <a class="finance-stat card finance-stat--link" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>">
    <span class="finance-stat__icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(format_money($stats['retention_held_value'])) ?></strong><em>Retention Held</em><small>Open balances</small></span>
  </a>
</section>

<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Process Payment</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-column"></i><span>Budget</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>"><i class="fa-solid fa-lock"></i><span>Retention</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Damages</span></a>
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>Reports</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
</nav>

<section class="card finance-card finance-card--full finance-report-builder">
  <div class="card__header">
    <div>
      <h2 class="card__title">Report Builder</h2>
      <p class="card__subtitle">Choose a report type, apply filters, then print or export. Primary output is a professional printable page (Save as PDF from the browser).</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($report['row_count'])) ?> rows</span>
  </div>

  <form class="finance-filter finance-report-filter" method="get" action="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>">
    <label class="filter-group" for="type">
      <span class="filter-label">Report</span>
      <select class="form-select" id="type" name="type">
<?php foreach ($typeLabels as $type => $label): ?>
        <option value="<?= Security::e($type) ?>" <?= $clean['type'] === $type ? 'selected' : '' ?>><?= Security::e($label) ?></option>
<?php endforeach; ?>
      </select>
    </label>
    <label class="filter-group" for="project_id">
      <span class="filter-label">Project</span>
      <select class="form-select" id="project_id" name="project_id">
        <option value="">All projects</option>
<?php foreach ($projects as $project): ?>
        <option value="<?= (int)$project['id'] ?>" <?= (int)($clean['filters']['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
<?php endforeach; ?>
      </select>
    </label>
    <label class="filter-group" for="q">
      <span class="filter-label">Search</span>
      <input class="form-input" id="q" type="search" name="q" value="<?= Security::e($clean['filters']['q'] ?? '') ?>" placeholder="Project, reference...">
    </label>
    <label class="filter-group" for="date_from">
      <span class="filter-label">From</span>
      <input class="form-input" id="date_from" type="date" name="date_from" value="<?= Security::e($clean['filters']['date_from'] ?? '') ?>" <?= empty($filterSupport['dates']) ? 'disabled' : '' ?>>
    </label>
    <label class="filter-group" for="date_to">
      <span class="filter-label">To</span>
      <input class="form-input" id="date_to" type="date" name="date_to" value="<?= Security::e($clean['filters']['date_to'] ?? '') ?>" <?= empty($filterSupport['dates']) ? 'disabled' : '' ?>>
    </label>
    <div class="filter-actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Generate</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>">Reset</a>
    </div>
  </form>

  <p class="finance-report-hint">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <?= Security::e((string)($filterSupport['date_hint'] ?? 'Apply filters then generate.')) ?>
    Use <strong>Print / PDF</strong> for a letterhead document suitable for signing or filing. CSV remains available for spreadsheet analysis.
  </p>

  <div class="finance-report-preview" data-finance-report-preview>
    <div class="finance-report-preview__head">
      <div>
        <h3><?= Security::e($report['title']) ?></h3>
        <p>
          Generated by <?= Security::e($report['generated_by']) ?>
          on <?= Security::e($report['generated_at_label'] ?? format_datetime($report['generated_at'])) ?>
          · <?= Security::e(format_number($report['row_count'])) ?> records
        </p>
<?php if (!empty($report['filter_labels'])): ?>
        <ul class="finance-report-preview__filters">
<?php foreach ($report['filter_labels'] as $label => $value): ?>
          <li><span><?= Security::e((string)$label) ?></span> <?= Security::e((string)$value) ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>
      <div class="finance-report-preview__actions">
        <button class="btn btn--outline btn--sm" type="button" onclick="window.print()"><i class="fa-solid fa-print" aria-hidden="true"></i> Print page</button>
        <a class="btn btn--primary btn--sm" target="_blank" rel="noopener" href="<?= Security::e($printUrl) ?>"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Open print sheet</a>
      </div>
    </div>

<?php if (!empty($report['summary_cards'])): ?>
    <div class="finance-report-summary" aria-label="Report totals">
<?php foreach ($report['summary_cards'] as $card): ?>
      <article class="finance-report-summary__item">
        <em><?= Security::e((string)($card['label'] ?? '')) ?></em>
        <strong><?= Security::e((string)($card['value'] ?? '')) ?></strong>
      </article>
<?php endforeach; ?>
    </div>
<?php endif; ?>

    <div class="table-wrap finance-table-wrap">
      <table class="data-table finance-table finance-table--stack finance-report-table">
        <thead>
          <tr>
<?php foreach ($report['columns'] as $key => $label): ?>
            <th class="<?= finance_report_is_money_key((string)$key) ? 'is-money' : '' ?>"><?= Security::e((string)$label) ?></th>
<?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
<?php if ($report['rows'] === []): ?>
          <tr>
            <td colspan="<?= max(1, count($report['columns'])) ?>">
              <div class="empty-state">
                <strong class="empty-state__title">No report records found</strong>
                <span class="empty-state__text">Adjust filters or choose another report type.</span>
              </div>
            </td>
          </tr>
<?php else: foreach ($report['rows'] as $row): ?>
          <tr>
<?php foreach (array_keys($report['columns']) as $key): ?>
            <td class="<?= finance_report_is_money_key((string)$key) ? 'is-money' : '' ?>" data-label="<?= Security::e((string)($report['columns'][$key] ?? $key)) ?>"><?= Security::e(FinanceReports::cell($row, (string)$key)) ?></td>
<?php endforeach; ?>
          </tr>
<?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="card finance-card finance-card--full">
  <div class="card__header">
    <div>
      <h2 class="card__title">Recent Report Runs</h2>
      <p class="card__subtitle">Latest preview, print and CSV activity from finance users.</p>
    </div>
  </div>
  <div class="finance-mini-list finance-mini-list--grid">
<?php if ($runs === []): ?>
    <div class="empty-state empty-state--compact"><strong class="empty-state__title">No reports generated yet</strong></div>
<?php else: foreach ($runs as $run): ?>
    <article class="finance-mini-item">
      <strong><?= Security::e($run['type_label'] ?? FinanceReports::title((string)($run['type_key'] ?? ''))) ?></strong>
      <span><?= Security::e($run['format_label'] ?? ($run['format'] ?? 'report')) ?> · <?= Security::e(format_number((int)$run['row_count'])) ?> rows · <?= Security::e($run['user_name'] ?: 'Finance') ?></span>
      <em><?= Security::e(time_ago($run['created_at'] ?? null)) ?></em>
    </article>
<?php endforeach; endif; ?>
  </div>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function finance_report_is_money_key(string $key): bool
{
    return (bool)preg_match('/(amount|value|sum|held|released|balance|rate|ld|retention|gross|net|paid|unpaid)/i', $key);
}
