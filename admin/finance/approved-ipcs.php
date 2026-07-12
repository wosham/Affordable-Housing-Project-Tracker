<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'approved_from' => Security::cleanString((string)($_GET['approved_from'] ?? '')),
    'approved_to' => Security::cleanString((string)($_GET['approved_to'] ?? '')),
    'age' => Security::cleanString((string)($_GET['age'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

if (!in_array((string)($filters['age'] ?? ''), ['', 'fresh', 'overdue'], true)) {
    unset($filters['age']);
}

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = FinancePayment::approvedCount($filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$ipcs = FinancePayment::approvedList($filters, $perPage, $offset);
$stats = FinancePayment::summary($filters);
$projects = Project::withRelations([], 200, 0);
$recentPayments = FinancePayment::recentPayments(6);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($ipcs), $total);

$pageTitle = 'Approved IPCs';
$pageDescription = 'Review approved payment certificates ready for finance processing.';
$adminRole = 'finance';
$csrfForm = 'finance_payments';
$contentClass = 'finance-payments-page finance-approved-page';
$componentCss = ['finance-payments'];
$pageScripts = ['finance-payments'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Approved IPCs'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="finance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Payment readiness</span>
    <h2>Approved IPCs</h2>
    <p>Review approved certificates, confirm payable amounts and move cleared payments to processing.</p>
  </div>
  <div class="finance-hero__actions">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i> Process Payment</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
  </div>
</section>

<section class="finance-stat-grid" aria-label="Finance payment summary">
  <?php finance_payment_stat('fa-file-circle-check', $stats['approved_count'], 'Approved IPCs', 'Awaiting payment', 'admin/finance/approved-ipcs.php'); ?>
  <?php finance_payment_stat('fa-money-bill-transfer', format_money($stats['approved_value']), 'Net Payable', 'Ready to process', 'admin/finance/process-payment.php'); ?>
  <?php finance_payment_stat('fa-shield-halved', format_money($stats['retention_value']), 'Retention Held', 'From approved IPCs', 'admin/finance/retention.php'); ?>
  <?php finance_payment_stat('fa-clock', $stats['overdue_count'], 'Past Target', 'More than 14 days', 'admin/finance/approved-ipcs.php?age=overdue'); ?>
  <?php finance_payment_stat('fa-calendar-check', $stats['paid_month_count'], 'Paid This Month', format_money($stats['paid_month_value']), 'admin/finance/process-payment.php'); ?>
</section>

<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Process Payment</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-column"></i><span>Budget</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>"><i class="fa-solid fa-lock"></i><span>Retention</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Damages</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>Reports</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/messages.php')) ?>"><i class="fa-solid fa-comments"></i><span>Messages</span></a>
</nav>

<section class="card finance-card finance-card--full">
  <div class="card__header">
    <div>
      <h2 class="card__title">Approved IPC Register</h2>
      <p class="card__subtitle">Filter certificates and open the payment workspace when ready.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="finance-filter finance-filter--wide" method="get" action="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>">
    <label class="filter-group" for="q"><span class="filter-label">Search</span><input class="form-input" id="q" type="search" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="IPC, project or contractor..."></label>
    <label class="filter-group" for="project_id"><span class="filter-label">Project</span><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
    <label class="filter-group" for="approved_from"><span class="filter-label">Approved from</span><input class="form-input" id="approved_from" type="date" name="approved_from" value="<?= Security::e($filters['approved_from'] ?? '') ?>"></label>
    <label class="filter-group" for="approved_to"><span class="filter-label">Approved to</span><input class="form-input" id="approved_to" type="date" name="approved_to" value="<?= Security::e($filters['approved_to'] ?? '') ?>"></label>
    <label class="filter-group" for="age"><span class="filter-label">Age</span><select class="form-select" id="age" name="age"><option value="">Any age</option><option value="fresh" <?= (($filters['age'] ?? '') === 'fresh') ? 'selected' : '' ?>>Within target</option><option value="overdue" <?= (($filters['age'] ?? '') === 'overdue') ? 'selected' : '' ?>>Past target</option></select></label>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap finance-table-wrap">
    <table class="data-table finance-table finance-table--stack">
      <thead>
        <tr>
          <th>IPC</th>
          <th>Project</th>
          <th>Contractor</th>
          <th>Approved</th>
          <th class="is-money">Gross</th>
          <th class="is-money">Retention</th>
          <th class="is-money">Net Payable</th>
          <th>Age</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
<?php if ($ipcs === []): ?>
        <tr><td colspan="9"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></span><strong class="empty-state__title">No approved IPCs found</strong><span class="empty-state__text">Cleared payment certificates will appear here.</span></div></td></tr>
<?php else: foreach ($ipcs as $ipc): ?>
        <tr>
          <td data-label="IPC"><strong>IPC #<?= Security::e($ipc['ipc_number']) ?></strong><small><?= Security::e($ipc['contractor_reference'] ?: 'Payment certificate') ?></small></td>
          <td data-label="Project"><strong><?= Security::e($ipc['project_name']) ?></strong><small><?= Security::e(format_money((float)($ipc['contract_sum'] ?? 0))) ?> contract sum</small></td>
          <td data-label="Contractor"><strong><?= Security::e($ipc['contractor_name']) ?></strong><small><?= Security::e($ipc['contractor_email']) ?></small></td>
          <td data-label="Approved"><?= Security::e(format_date($ipc['approved_at'] ?? null)) ?><small><?= Security::e($ipc['approved_by_name'] ?: 'Approved') ?></small></td>
          <td class="is-money" data-label="Gross"><?= Security::e(format_money((float)$ipc['gross_amount'])) ?></td>
          <td class="is-money" data-label="Retention"><?= Security::e(format_money((float)$ipc['retention_amount'])) ?></td>
          <td class="is-money" data-label="Net Payable"><strong><?= Security::e(format_money((float)$ipc['outstanding_amount'])) ?></strong></td>
          <td data-label="Age"><span class="badge <?= ((int)($ipc['approval_age'] ?? 0) > 14) ? 'badge--warning' : 'badge--success' ?>"><?= (int)($ipc['approval_age'] ?? 0) ?> days</span></td>
          <td data-label="Actions"><div class="data-table__actions finance-actions">
            <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/finance/process-payment.php?ipc_id=' . (int)$ipc['id'])) ?>" title="Process payment" aria-label="Process payment"><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i></a>
            <button class="btn btn--icon btn--outline" type="button" data-payment-detail="<?= (int)$ipc['id'] ?>" title="View payment details" aria-label="View payment details"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
          </div></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="Approved IPC pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> approved IPCs</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(finance_payment_page_url($filters, ['page' => max(1, $page - 1)])) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number($totalPages)) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(finance_payment_page_url($filters, ['page' => min($totalPages, $page + 1)])) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<section class="card finance-card finance-card--full">
  <div class="card__header">
    <div>
      <h2 class="card__title">Recent Payments</h2>
      <p class="card__subtitle">Latest processed IPC payments.</p>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>">Open process</a>
  </div>
  <div class="finance-mini-list finance-mini-list--grid">
<?php if ($recentPayments === []): ?>
    <div class="empty-state empty-state--compact"><strong class="empty-state__title">No payments yet</strong></div>
<?php else: foreach ($recentPayments as $payment): ?>
    <article class="finance-mini-item">
      <strong><?= Security::e($payment['reference_no'] ?: 'Payment') ?></strong>
      <span>IPC #<?= Security::e($payment['ipc_number']) ?> / <?= Security::e($payment['project_name']) ?></span>
      <em><?= Security::e(format_money((float)$payment['amount'])) ?></em>
    </article>
<?php endforeach; endif; ?>
  </div>
</section>

<div class="finance-detail-drawer" data-finance-detail hidden>
  <div class="finance-detail-drawer__panel">
    <div class="finance-detail-drawer__header">
      <div><span class="sa-panel-label">Payment details</span><h2 data-detail-title>IPC details</h2><p data-detail-summary></p></div>
      <button class="btn btn--icon btn--ghost" type="button" data-detail-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="finance-detail-drawer__body" data-detail-body></div>
    <div class="finance-detail-drawer__footer">
      <button class="btn btn--outline" type="button" data-detail-close>Close</button>
      <a class="btn btn--primary" href="#" data-detail-process hidden><i class="fa-solid fa-money-check-dollar"></i> Process payment</a>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function finance_payment_stat(string $icon, mixed $value, string $label, string $hint, string $href = ''): void
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

function finance_payment_page_url(array $filters, array $overrides = []): string
{
    $query = array_merge($filters, $overrides);
    $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0);
    return Url::to('admin/finance/approved-ipcs.php' . ($query ? '?' . http_build_query($query) : ''));
}
