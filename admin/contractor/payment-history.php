<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$projects = ContractorIPC::projects($userId, $role);
$filters = [
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => in_array((string)($_GET['status'] ?? ''), ['paid', 'unpaid', 'approved', 'submitted', 'certified', 'endorsed', 'clerk-endorsed', 'rejected'], true)
        ? (string)$_GET['status']
        : '',
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$summary = ContractorIPC::paymentSummary($userId, $role, $filters);
$total = ContractorIPC::paymentCount($userId, $role, $filters);
$pages = max(1, (int)ceil($total / $limit));
$page = min($page, $pages);
$offset = ($page - 1) * $limit;
$records = ContractorIPC::paymentRows($userId, $role, $filters, $limit, $offset);

$pageTitle = 'Payment History';
$pageDescription = 'Payment, unpaid approved IPC and retention visibility.';
$adminRole = 'contractor';
$contentClass = 'contractor-ipc-page';
$componentCss = ['contractor-ipc'];
$pageScripts = ['contractor-ipc-history'];
$csrfForm = 'contractor_ipc';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Payment History'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-ipc-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Payments and retention</span>
    <h2>Payment History</h2>
    <p>Review paid claims, approved unpaid balances and retention held across your assigned projects. Payment records are read-only.</p>
  </div>
  <div class="contractor-ipc-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/ipc-history.php')) ?>"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> IPC History</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php')) ?>"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Submit IPC</a>
  </div>
</section>

<section class="contractor-ipc-stats" aria-label="Payment summary">
  <?php contractor_pay_stat('fa-file-invoice-dollar', $summary['total'], 'Claim records', 'Visible claims', pay_history_url([])); ?>
  <?php contractor_pay_stat('fa-credit-card', $summary['paid_count'], 'Paid claims', 'With payment recorded', pay_history_url(['status' => 'paid'])); ?>
  <?php contractor_pay_stat('fa-money-check-dollar', format_money($summary['paid_value']), 'Paid value', 'Received to date', pay_history_url(['status' => 'paid'])); ?>
  <?php contractor_pay_stat('fa-hourglass-half', $summary['unpaid_count'], 'Unpaid approved', 'Awaiting finance', pay_history_url(['status' => 'unpaid'])); ?>
  <?php contractor_pay_stat('fa-wallet', format_money($summary['unpaid_value']), 'Unpaid value', 'Approved not paid', pay_history_url(['status' => 'unpaid'])); ?>
  <?php contractor_pay_stat('fa-lock', format_money($summary['retention']), 'Retention held', 'Across claims', pay_history_url([])); ?>
</section>

<section class="card contractor-ipc-panel">
  <div class="card__header">
    <div>
      <h2 class="card__title">Payment register</h2>
      <p class="card__subtitle">Filter paid and unpaid approved claims. Open claim details for line-level amounts.</p>
    </div>
    <span class="badge badge--info"><?= format_number($total) ?> records</span>
  </div>

  <form class="contractor-ipc-filter contractor-ipc-filter--payments" method="get" action="<?= Security::e(Url::to('admin/contractor/payment-history.php')) ?>">
    <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="IPC, project or payment ref..."></label>
    <label><span>Project</span>
      <select name="project_id">
        <option value="0">All projects</option>
        <?php foreach ($projects as $project): ?>
          <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>View</span>
      <select name="status">
        <option value="">All claims</option>
        <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
        <option value="unpaid" <?= $filters['status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid approved</option>
        <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
      </select>
    </label>
    <div class="contractor-ipc-filter__actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/payment-history.php')) ?>">Reset</a>
    </div>
  </form>

  <div class="contractor-ipc-table-wrap">
    <table class="contractor-ipc-table contractor-ipc-table--history">
      <thead>
        <tr>
          <th>IPC</th>
          <th>Project</th>
          <th>Status</th>
          <th>Net amount</th>
          <th>Paid</th>
          <th>Payment details</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($records === []): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state empty-state--compact">
                <strong class="empty-state__title">No payment records found</strong>
                <span class="empty-state__text">Paid claims and approved unpaid balances will appear here when recorded.</span>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($records as $row): ?>
          <?php $isPaid = !empty($row['payment_date']) || !empty($row['amount']); ?>
          <tr>
            <td><strong>IPC #<?= (int)$row['ipc_number'] ?></strong><small><?= Security::e($row['contractor_reference'] ?: 'No reference') ?></small></td>
            <td><strong title="<?= Security::e($row['project_name'] ?? '') ?>"><?= Security::e(safe_truncate($row['project_name'] ?? '-', 36)) ?></strong></td>
            <td><span class="badge <?= Security::e(contractor_pay_status_badge((string)$row['status'], $isPaid)) ?>"><?= Security::e(status_label((string)$row['status'])) ?></span></td>
            <td>
              <strong title="<?= Security::e(format_money($row['net_amount'] ?? 0)) ?>"><?= Security::e(format_money($row['net_amount'] ?? 0)) ?></strong>
              <small>Retention <?= Security::e(format_money($row['retention_amount'] ?? 0)) ?></small>
            </td>
            <td>
              <?php if ($isPaid): ?>
                <strong title="<?= Security::e(format_money($row['amount'] ?? 0)) ?>"><?= Security::e(format_money($row['amount'] ?? 0)) ?></strong>
                <small><?= Security::e(format_date($row['payment_date'] ?? null)) ?></small>
              <?php else: ?>
                <strong>—</strong>
                <small><?= (string)$row['status'] === 'approved' ? 'Awaiting payment' : 'Not paid' ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($isPaid): ?>
                <strong><?= Security::e($row['reference_no'] ?: '—') ?></strong>
                <small><?= Security::e(($row['bank'] ?: '—') . ' · ' . status_label((string)($row['payment_method'] ?? 'bank_transfer'))) ?></small>
              <?php else: ?>
                <strong>—</strong>
                <small>No payment recorded</small>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn--sm btn--outline" type="button" data-ipc-open="<?= (int)$row['id'] ?>" title="View claim details">
                <i class="fa-solid fa-eye" aria-hidden="true"></i><span class="sr-only">View details</span>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php contractor_pay_pagination($page, $pages, $total, $limit); ?>
</section>

<div class="contractor-ipc-overlay" data-ipc-detail-modal hidden aria-hidden="true">
  <div class="contractor-ipc-modal" role="dialog" aria-modal="true" aria-labelledby="payDetailTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Claim detail</span>
        <h2 id="payDetailTitle" data-ipc-detail-title>IPC</h2>
        <p data-ipc-detail-sub></p>
      </div>
      <button class="btn btn--outline btn--sm" type="button" data-ipc-detail-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </header>
    <div class="contractor-ipc-modal__body" data-ipc-detail-body>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title">Select a claim</strong></div>
    </div>
    <footer>
      <button class="btn btn--outline" type="button" data-ipc-detail-close>Close</button>
    </footer>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function pay_history_url(array $extra): string
{
    $base = [
        'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
        'status' => trim((string)($_GET['status'] ?? '')),
        'q' => trim((string)($_GET['q'] ?? '')),
    ];
    $query = array_filter(array_merge($base, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/contractor/payment-history.php' . ($query ? '?' . http_build_query($query) : ''));
}

function contractor_pay_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="contractor-ipc-stat card"' . $href . '><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><b>' . Security::e($label) . '</b><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function contractor_pay_status_badge(string $status, bool $isPaid): string
{
    if ($isPaid || $status === 'paid') {
        return 'badge--success';
    }
    if ($status === 'approved') {
        return 'badge--warning';
    }
    if ($status === 'rejected') {
        return 'badge--danger';
    }
    return 'badge--info';
}

function contractor_pay_pagination(int $page, int $pages, int $total, int $limit): void
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
