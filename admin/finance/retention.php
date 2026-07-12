<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$filters = FinanceBudget::filters($_GET);
$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = FinanceBudget::retentionCount($filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$rows = FinanceBudget::retentionRows($filters, $perPage, $offset);
$stats = FinanceBudget::summary($filters);
$projects = FinanceBudget::projects();
$from = $total > 0 ? $offset + 1 : 0;
$to = min($offset + count($rows), $total);

$pageTitle = 'Retention';
$pageDescription = 'Track retention balances, releases and linked IPCs.';
$adminRole = 'finance';
$csrfForm = 'finance_reports';
$contentClass = 'finance-payments-page finance-retention-page';
$componentCss = ['finance-payments', 'finance-reports'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Retention'],
];
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="finance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-lock" aria-hidden="true"></i> Retention control</span>
    <h2>Retention</h2>
    <p>Track held balances, release dates and linked IPC records. Retention is created when an approved IPC is paid.</p>
  </div>
  <div class="finance-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('api/finance/generate-report.php?type=retention_report&format=csv&' . http_build_query($filters))) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
  </div>
</section>

<section class="finance-stat-grid finance-stat-grid--3" aria-label="Retention summary">
  <?php finance_retention_stat('fa-shield-halved', format_money($stats['retention_held']), 'Retention Held', 'Open balances', 'admin/finance/retention.php'); ?>
  <?php finance_retention_stat('fa-building', $stats['projects'], 'Projects', 'Current filter', 'admin/finance/budget-tracker.php'); ?>
  <?php finance_retention_stat('fa-circle-check', format_number($total), 'Records', 'Matching filter', 'admin/finance/retention.php'); ?>
</section>

<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Process Payment</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-column"></i><span>Budget</span></a>
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>"><i class="fa-solid fa-lock"></i><span>Retention</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Damages</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>Reports</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
</nav>

<section class="card finance-card finance-card--full">
  <div class="card__header">
    <div>
      <h2 class="card__title">Retention Register</h2>
      <p class="card__subtitle">Held and released amounts by project and IPC.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="finance-filter finance-filter--wide" method="get" action="<?= Security::e(Url::to('admin/finance/retention.php')) ?>">
    <label class="filter-group" for="q"><span class="filter-label">Search</span><input class="form-input" id="q" type="search" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project name..."></label>
    <label class="filter-group" for="project_id"><span class="filter-label">Project</span><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
    <label class="filter-group" for="status"><span class="filter-label">Status</span><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (['held', 'released', 'waived'] as $status): ?><option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
    <label class="filter-group" for="date_from"><span class="filter-label">From</span><input class="form-input" id="date_from" type="date" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></label>
    <label class="filter-group" for="date_to"><span class="filter-label">To</span><input class="form-input" id="date_to" type="date" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></label>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap finance-table-wrap">
    <table class="data-table finance-table finance-table--stack">
      <thead>
        <tr>
          <th>Project</th>
          <th>IPC</th>
          <th>Status</th>
          <th class="is-money">Held</th>
          <th class="is-money">Released</th>
          <th class="is-money">Balance</th>
          <th>Release Date</th>
          <th>Processed By</th>
        </tr>
      </thead>
      <tbody>
<?php if ($rows === []): ?>
        <tr><td colspan="8"><div class="empty-state"><strong class="empty-state__title">No retention records found</strong><span class="empty-state__text">Retention appears after approved IPCs are paid.</span></div></td></tr>
<?php else: foreach ($rows as $row): ?>
        <tr>
          <td data-label="Project"><strong><?= Security::e($row['project_name']) ?></strong></td>
          <td data-label="IPC"><?= !empty($row['ipc_number']) ? 'IPC #' . Security::e($row['ipc_number']) : '-' ?></td>
          <td data-label="Status"><span class="badge <?= Security::e($row['status_badge']) ?>"><?= Security::e($row['status_label']) ?></span></td>
          <td class="is-money" data-label="Held"><?= Security::e(format_money((float)$row['total_held'])) ?></td>
          <td class="is-money" data-label="Released"><?= Security::e(format_money((float)$row['released_amount'])) ?></td>
          <td class="is-money" data-label="Balance"><strong><?= Security::e(format_money((float)$row['balance_amount'])) ?></strong></td>
          <td data-label="Release Date"><?= Security::e(format_date($row['release_date'] ?? null)) ?></td>
          <td data-label="Processed By"><?= Security::e($row['processed_by_name'] ?: '-') ?></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="Retention pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> records</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(finance_retention_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number($totalPages)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(finance_retention_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
<?php
function finance_retention_stat(string $icon, mixed $value, string $label, string $hint, string $href = ''): void
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

function finance_retention_page_url(array $filters, int $page): string
{
    $query = array_merge($filters, ['page' => $page]);
    $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0);
    return Url::to('admin/finance/retention.php' . ($query ? '?' . http_build_query($query) : ''));
}
