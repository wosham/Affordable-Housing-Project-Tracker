<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('finance');

$stats = FinancePayment::dashboard();
$budgetRows = FinancePayment::budgetRows(8);
$recentPayments = FinancePayment::recentPayments(6);
$retentionRows = FinancePayment::retentionRows(5);
$portalAnnouncements = Announcement::activeForRole('finance', 5, (int)(Auth::id() ?? 0));

$pageTitle = 'Finance Dashboard';
$pageDescription = 'Finance overview for approved IPCs, payments, retention and budget movement.';
$adminRole = 'finance';
$csrfForm = 'finance_payments';
$contentClass = 'finance-payments-page finance-dashboard-page';
$componentCss = ['finance-payments'];
$pageScripts = ['finance-payments'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance'],
    ['label' => 'Dashboard'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="finance-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-coins" aria-hidden="true"></i> Finance overview</span>
    <h2>Welcome, <?= Security::e(Auth::name()) ?></h2>
    <p>Monitor approved IPCs, processed payments, retention and project budget movement from one place. Click a summary card to open the related workspace.</p>
  </div>
  <div class="finance-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approved IPCs</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i> Process Payment</a>
  </div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/dashboard-announcements.php'; ?>

<section class="finance-stat-grid" aria-label="Finance summary">
  <?php finance_dashboard_stat('fa-file-circle-check', $stats['approved_count'], 'Ready to Pay', format_money($stats['approved_value']), 'admin/finance/approved-ipcs.php'); ?>
  <?php finance_dashboard_stat('fa-money-bill-transfer', format_money($stats['paid_value']), 'Paid to Date', $stats['payment_count'] . ' payments', 'admin/finance/process-payment.php'); ?>
  <?php finance_dashboard_stat('fa-shield-halved', format_money($stats['retention_held_value']), 'Retention Held', 'Tracked balances', 'admin/finance/retention.php'); ?>
  <?php finance_dashboard_stat('fa-triangle-exclamation', format_money($stats['ld_value']), 'Damages Logged', $stats['ld_count'] . ' records', 'admin/finance/liquidated-damages.php'); ?>
  <?php finance_dashboard_stat('fa-building', $stats['project_count'], 'Projects', round($stats['avg_progress']) . '% average progress', 'admin/finance/budget-tracker.php'); ?>
</section>

<nav class="finance-hub card" aria-label="Finance modules">
  <a class="finance-hub__link is-active" href="<?= Security::e(Url::to('admin/finance/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
  <a class="finance-hub__link" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-circle-check"></i><span>Approved IPCs</span></a>
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
      <h2 class="card__title">Budget Movement</h2>
      <p class="card__subtitle">Contract value, paid, approved unpaid and remaining balance by project.</p>
    </div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>">View all</a>
  </div>
  <div class="table-wrap finance-table-wrap">
    <table class="data-table finance-table finance-table--budget finance-table--stack">
      <thead>
        <tr>
          <th>Project</th>
          <th>Status</th>
          <th>Progress</th>
          <th class="is-money">Contract</th>
          <th class="is-money">Paid</th>
          <th class="is-money">Approved unpaid</th>
          <th class="is-money">Balance</th>
        </tr>
      </thead>
      <tbody>
<?php if ($budgetRows === []): ?>
        <tr><td colspan="7"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No project budget data yet</strong></div></td></tr>
<?php else: foreach ($budgetRows as $row): ?>
        <tr>
          <td data-label="Project"><strong><a class="finance-inline-link" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php?project_id=' . (int)$row['id'])) ?>"><?= Security::e($row['name']) ?></a></strong></td>
          <td data-label="Status"><span class="badge badge--info"><?= Security::e(status_label($row['status'] ?? 'active')) ?></span></td>
          <td data-label="Progress"><?= Security::e(format_number((float)$row['pct_complete'])) ?>%</td>
          <td class="is-money" data-label="Contract"><?= Security::e(format_money((float)$row['contract_sum'])) ?></td>
          <td class="is-money" data-label="Paid"><?= Security::e(format_money((float)$row['paid_amount'])) ?></td>
          <td class="is-money" data-label="Approved unpaid"><?= Security::e(format_money((float)($row['approved_unpaid'] ?? 0))) ?></td>
          <td class="is-money" data-label="Balance"><strong><?= Security::e(format_money((float)$row['balance_amount'])) ?></strong></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="finance-split" aria-label="Payments and retention">
  <section class="card finance-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Recent Payments</h2>
        <p class="card__subtitle">Latest processed payments.</p>
      </div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>">Open</a>
    </div>
    <?php finance_dashboard_mini_list($recentPayments, 'payment'); ?>
  </section>
  <section class="card finance-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Retention Watch</h2>
        <p class="card__subtitle">Upcoming retained balances.</p>
      </div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>">Open</a>
    </div>
    <?php finance_dashboard_mini_list($retentionRows, 'retention'); ?>
  </section>
</section>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function finance_dashboard_stat(string $icon, mixed $value, string $label, string $hint, string $href = ''): void
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

function finance_dashboard_mini_list(array $rows, string $type): void
{
?>
  <div class="finance-mini-list">
<?php if ($rows === []): ?>
    <div class="empty-state empty-state--compact"><strong class="empty-state__title">No records yet</strong></div>
<?php else: foreach ($rows as $row): ?>
    <article class="finance-mini-item">
<?php if ($type === 'payment'): ?>
      <strong><?= Security::e($row['reference_no'] ?: 'Payment') ?></strong>
      <span>IPC #<?= Security::e($row['ipc_number']) ?> / <?= Security::e($row['project_name']) ?></span>
      <em><?= Security::e(format_money((float)$row['amount'])) ?></em>
<?php else: ?>
      <strong><?= Security::e($row['project_name']) ?></strong>
      <span><?= !empty($row['ipc_number']) ? 'IPC #' . Security::e($row['ipc_number']) : 'Project retention' ?></span>
      <em><?= Security::e(format_money((float)$row['total_held'] - (float)$row['released_amount'])) ?></em>
<?php endif; ?>
    </article>
<?php endforeach; endif; ?>
  </div>
<?php
}
