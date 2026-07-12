<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$filters = FinanceBudget::filters($_GET);
$perPage = 20;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = FinanceBudget::projectCount($filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$rows = FinanceBudget::projectRows($filters, $perPage, $offset);
$stats = FinanceBudget::summary($filters);
$projects = FinanceBudget::projects();
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($rows), $total);

$pageTitle = 'Budget Tracker';
$pageDescription = 'Track contract value, payments, approved unpaid IPCs, retention and project balances.';
$adminRole = 'finance';
$csrfForm = 'finance_reports';
$contentClass = 'finance-payments-page finance-budget-page';
$componentCss = ['finance-payments', 'finance-reports'];
$pageScripts = ['finance-payments'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Budget Tracker'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="finance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Budget control</span>
    <h2>Budget Tracker</h2>
    <p>Compare contract values, payments, approved balances, retained amounts and financial signals.</p>
  </div>
  <div class="finance-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('api/finance/generate-report.php?type=budget_summary&format=csv&' . http_build_query($filters))) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
  </div>
</section>

<section class="finance-stat-grid" aria-label="Budget summary">
  <?php finance_page_stat('fa-building', $stats['projects'], 'Projects', 'Budget portfolio', 'admin/finance/budget-tracker.php'); ?>
  <?php finance_page_stat('fa-file-contract', format_money($stats['contract_value']), 'Contract Value', 'Total portfolio', 'admin/finance/budget-tracker.php'); ?>
  <?php finance_page_stat('fa-money-bill-transfer', format_money($stats['paid_value']), 'Paid', 'Processed payments', 'admin/finance/process-payment.php'); ?>
  <?php finance_page_stat('fa-clock', format_money($stats['approved_unpaid']), 'Approved Unpaid', 'Awaiting payment', 'admin/finance/approved-ipcs.php'); ?>
  <?php finance_page_stat('fa-triangle-exclamation', $stats['risk_projects'], 'Signals', 'Projects to review', 'admin/finance/budget-tracker.php?risk=near_limit'); ?>
</section>

<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Process Payment</span></a>
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-column"></i><span>Budget</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>"><i class="fa-solid fa-lock"></i><span>Retention</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Damages</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>Reports</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
</nav>

<section class="card finance-card finance-card--full">
  <div class="card__header">
    <div>
      <h2 class="card__title">Project Budget Register</h2>
      <p class="card__subtitle">Filter projects by financial signal and payment position.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="finance-filter finance-filter--compact" method="get" action="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>">
    <label class="filter-group" for="q"><span class="filter-label">Search</span><input class="form-input" id="q" type="search" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project name..."></label>
    <label class="filter-group" for="project_id"><span class="filter-label">Project</span><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
    <label class="filter-group" for="risk"><span class="filter-label">Signal</span><select class="form-select" id="risk" name="risk"><option value="">All signals</option><option value="near_limit" <?= (($filters['risk'] ?? '') === 'near_limit') ? 'selected' : '' ?>>Near limit</option><option value="unpaid" <?= (($filters['risk'] ?? '') === 'unpaid') ? 'selected' : '' ?>>Approved unpaid</option><option value="retention" <?= (($filters['risk'] ?? '') === 'retention') ? 'selected' : '' ?>>Retention</option><option value="ld" <?= (($filters['risk'] ?? '') === 'ld') ? 'selected' : '' ?>>Damages</option></select></label>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap finance-table-wrap">
    <table class="data-table finance-table finance-table--budget finance-table--stack">
      <thead>
        <tr>
          <th>Project</th>
          <th>Status</th>
          <th>Signal</th>
          <th>Usage</th>
          <th class="is-money">Contract</th>
          <th class="is-money">Paid</th>
          <th class="is-money">Approved Unpaid</th>
          <th class="is-money">Retention</th>
          <th class="is-money">LDs</th>
          <th class="is-money">Balance</th>
        </tr>
      </thead>
      <tbody>
<?php if ($rows === []): ?>
        <tr><td colspan="10"><div class="empty-state"><strong class="empty-state__title">No budget records found</strong><span class="empty-state__text">Project budget rows will appear once contracts are loaded.</span></div></td></tr>
<?php else: foreach ($rows as $row): ?>
        <tr>
          <td data-label="Project"><strong><?= Security::e($row['name']) ?></strong><small><?= Security::e(format_number((float)$row['pct_complete'])) ?>% physical progress</small></td>
          <td data-label="Status"><span class="badge badge--info"><?= Security::e(status_label($row['status'] ?? 'active')) ?></span></td>
          <td data-label="Signal"><span class="badge <?= Security::e($row['risk_badge']) ?>"><?= Security::e($row['risk_label']) ?></span></td>
          <td data-label="Usage"><?= Security::e(format_number((float)$row['usage_pct'])) ?>%</td>
          <td class="is-money" data-label="Contract"><?= Security::e(format_money((float)$row['contract_sum'])) ?></td>
          <td class="is-money" data-label="Paid"><?= Security::e(format_money((float)$row['paid_amount'])) ?></td>
          <td class="is-money" data-label="Approved Unpaid"><?= Security::e(format_money((float)$row['approved_unpaid'])) ?></td>
          <td class="is-money" data-label="Retention"><?= Security::e(format_money((float)$row['retention_held'])) ?></td>
          <td class="is-money" data-label="LDs"><?= Security::e(format_money((float)$row['ld_value'])) ?></td>
          <td class="is-money" data-label="Balance"><strong><?= Security::e(format_money((float)$row['balance_amount'])) ?></strong></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="Budget pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> projects</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(finance_budget_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number($totalPages)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(finance_budget_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function finance_page_stat(string $icon, mixed $value, string $label, string $hint, string $href = ''): void
{
    $url = $href !== '' ? Url::to($href) : '';
    $tag = $url !== '' ? 'a' : 'article';
    $hrefAttr = $url !== '' ? ' href="' . Security::e($url) . '"' : '';
?>
  <<?= $tag ?> class="finance-stat card<?= $url !== '' ? ' finance-stat--link' : '' ?>"<?= $hrefAttr ?>>
    <span class="finance-stat__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span><strong><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><em><?= Security::e($label) ?></em><small><?= Security::e($hint) ?></small></span>
  </<?= $tag ?>>
<?php
}

function finance_budget_page_url(array $filters, int $page): string
{
    $query = array_merge($filters, ['page' => $page]);
    $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0);
    return Url::to('admin/finance/budget-tracker.php' . ($query ? '?' . http_build_query($query) : ''));
}
