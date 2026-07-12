<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$userId = (int)(Auth::id() ?? 0);
$role = (string)(Auth::role() ?? '');
$projects = ContractorIPC::projects($userId, $role);
$filters = [
    'contractor_id' => $userId,
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0) ?: null,
    'status' => in_array((string)($_GET['status'] ?? ''), IPC::statuses(), true) ? (string)$_GET['status'] : '',
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['date_from'] ?? '')) ? (string)$_GET['date_from'] : '',
    'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['date_to'] ?? '')) ? (string)$_GET['date_to'] : '',
];
$filters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');
$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$perPage = 10;
$total = IPC::countDetailed($filters);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$ipcs = IPC::detailed($filters, $perPage, ($page - 1) * $perPage);
$stats = ContractorIPC::stats($userId, $role);

$pageTitle = 'IPC History';
$pageDescription = 'Track submitted IPCs, claim status and payment progress.';
$adminRole = 'contractor';
$contentClass = 'contractor-ipc-page';
$componentCss = ['contractor-ipc'];
$pageScripts = ['contractor-ipc-history'];
$csrfForm = 'contractor_ipc';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'IPC History'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="contractor-ipc-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Claims tracker</span>
    <h2>IPC History</h2>
    <p>Track submitted claims, review status and payment progress across your assigned projects.</p>
  </div>
  <div class="contractor-ipc-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/payment-history.php')) ?>"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Payment History</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php')) ?>"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Submit IPC</a>
  </div>
</section>

<section class="contractor-ipc-stats" aria-label="IPC summary">
  <?php contractor_ipc_stat('fa-file-invoice-dollar', $stats['total'], 'Total IPCs', 'All claims', ipc_history_url([])); ?>
  <?php contractor_ipc_stat('fa-paper-plane', $stats['submitted'], 'Submitted', 'Awaiting site check', ipc_history_url(['status' => 'submitted'])); ?>
  <?php contractor_ipc_stat('fa-user-check', $stats['clerk_endorsed'], 'Clerk Verified', 'Ready for consultant', ipc_history_url(['status' => 'clerk-endorsed'])); ?>
  <?php contractor_ipc_stat('fa-stamp', $stats['certified'], 'Certified', 'Consultant certified', ipc_history_url(['status' => 'certified'])); ?>
  <?php contractor_ipc_stat('fa-file-signature', $stats['endorsed'], 'Manager Endorsed', 'Awaiting director', ipc_history_url(['status' => 'endorsed'])); ?>
  <?php contractor_ipc_stat('fa-circle-check', $stats['approved'], 'Approved', 'Ready for payout', ipc_history_url(['status' => 'approved'])); ?>
  <?php contractor_ipc_stat('fa-credit-card', $stats['paid'], 'Paid', 'Completed claims', ipc_history_url(['status' => 'paid'])); ?>
  <?php contractor_ipc_stat('fa-rotate-left', $stats['rejected'], 'Rejected', 'Returned claims', ipc_history_url(['status' => 'rejected'])); ?>
  <?php contractor_ipc_stat('fa-coins', format_money($stats['net_value']), 'Net Value', 'Total claimed', ipc_history_url([])); ?>
</section>

<section class="card contractor-ipc-panel">
  <div class="card__header">
    <div>
      <h2 class="card__title">IPC Registry</h2>
      <p class="card__subtitle">Search, filter and open claim details for your submitted IPCs.</p>
    </div>
    <span class="badge badge--info"><?= format_number($total) ?> records</span>
  </div>
  <form class="contractor-ipc-filter" method="get" action="<?= Security::e(Url::to('admin/contractor/ipc-history.php')) ?>">
    <label><span>Search</span><input type="search" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="IPC number or project..."></label>
    <label><span>Project</span>
      <select name="project_id">
        <option value="">All projects</option>
        <?php foreach ($projects as $project): ?>
          <option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>Status</span>
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['submitted', 'clerk-endorsed', 'certified', 'endorsed', 'approved', 'paid', 'rejected'] as $status): ?>
          <option value="<?= Security::e($status) ?>" <?= (string)($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>From</span><input type="date" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></label>
    <label><span>To</span><input type="date" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></label>
    <div class="contractor-ipc-filter__actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/contractor/ipc-history.php')) ?>">Reset</a>
    </div>
  </form>

  <div class="contractor-ipc-table-wrap">
    <table class="contractor-ipc-table contractor-ipc-table--history">
      <thead>
        <tr>
          <th>IPC</th>
          <th>Project</th>
          <th>Period</th>
          <th>Amounts</th>
          <th>Status</th>
          <th>Progress</th>
          <th>Last Action</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($ipcs === []): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state empty-state--compact">
                <strong class="empty-state__title">No IPCs found</strong>
                <span class="empty-state__text">Submit an IPC or adjust the current filters.</span>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($ipcs as $ipc): ?>
          <?php $state = IPC::workflowState($ipc); ?>
          <tr>
            <td><strong>IPC #<?= (int)$ipc['ipc_number'] ?></strong><small><?= Security::e($ipc['contractor_reference'] ?? 'No reference') ?></small></td>
            <td><strong title="<?= Security::e($ipc['project_name']) ?>"><?= Security::e(safe_truncate($ipc['project_name'], 36)) ?></strong><small><?= Security::e($ipc['constituency_name'] ?? '-') ?></small></td>
            <td><?= Security::e(format_date($ipc['period_from'] ?? null)) ?> – <?= Security::e(format_date($ipc['period_to'] ?? null)) ?><small>Submitted <?= Security::e(format_datetime($ipc['submitted_at'] ?? null)) ?></small></td>
            <td><strong title="<?= Security::e(format_money($ipc['net_amount'])) ?>"><?= Security::e(format_money($ipc['net_amount'])) ?></strong><small>Gross <?= Security::e(format_money($ipc['gross_amount'])) ?></small></td>
            <td><span class="badge <?= Security::e(contractor_ipc_status_badge((string)$ipc['status'])) ?>"><?= Security::e(status_label((string)$ipc['status'])) ?></span></td>
            <td>
              <div class="contractor-ipc-mini-flow" style="--step: <?= max(0, (int)$state['current_step']) ?>">
                <?php for ($i = 1; $i <= 6; $i++): ?><span<?= $i <= (int)$state['current_step'] ? ' class="is-done"' : '' ?>></span><?php endfor; ?>
              </div>
              <small><?= Security::e(contractor_ipc_progress_label((int)$state['current_step'], (string)$ipc['status'])) ?></small>
            </td>
            <td><strong><?= Security::e(status_label($ipc['last_action'] ?? $ipc['status'])) ?></strong><small><?= Security::e($ipc['last_action_by'] ?: 'System') ?> · <?= Security::e(format_datetime($ipc['last_actioned_at'] ?? $ipc['updated_at'] ?? null)) ?></small></td>
            <td>
              <div class="contractor-ipc-row-actions">
                <button class="btn btn--sm btn--outline" type="button" data-ipc-open="<?= (int)$ipc['id'] ?>" title="View claim details"><i class="fa-solid fa-eye" aria-hidden="true"></i><span class="sr-only">View details</span></button>
                <?php if ((string)$ipc['status'] === 'paid'): ?>
                  <a class="btn btn--sm btn--outline" href="<?= Security::e(Url::to('admin/contractor/payment-history.php?project_id=' . (int)$ipc['project_id'] . '&status=paid')) ?>" title="Payment history"><i class="fa-solid fa-credit-card" aria-hidden="true"></i><span class="sr-only">Payments</span></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php contractor_ipc_pagination($page, $pages, $total, $perPage); ?>
</section>

<div class="contractor-ipc-overlay" data-ipc-detail-modal hidden aria-hidden="true">
  <div class="contractor-ipc-modal" role="dialog" aria-modal="true" aria-labelledby="ipcDetailTitle">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Claim detail</span>
        <h2 id="ipcDetailTitle" data-ipc-detail-title>IPC</h2>
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

function ipc_history_url(array $extra): string
{
    $base = [
        'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
        'status' => trim((string)($_GET['status'] ?? '')),
        'q' => trim((string)($_GET['q'] ?? '')),
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to' => trim((string)($_GET['date_to'] ?? '')),
    ];
    $query = array_filter(array_merge($base, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to('admin/contractor/ipc-history.php' . ($query ? '?' . http_build_query($query) : ''));
}

function contractor_ipc_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="contractor-ipc-stat card"' . $href . '><span><i class="fa-solid ' . Security::e($icon) . '" aria-hidden="true"></i></span><div><strong>' . Security::e((string)$value) . '</strong><b>' . Security::e($label) . '</b><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function contractor_ipc_status_badge(string $status): string
{
    return match ($status) {
        'paid', 'approved' => 'badge--success',
        'rejected' => 'badge--danger',
        'submitted', 'clerk-endorsed', 'certified', 'endorsed' => 'badge--info',
        default => 'badge--info',
    };
}

function contractor_ipc_progress_label(int $step, string $status): string
{
    if ($status === 'rejected') {
        return 'Returned for action';
    }
    if ($status === 'paid') {
        return 'Completed';
    }
    return match (true) {
        $step >= 5 => 'Payment pending',
        $step >= 4 => 'Director review',
        $step >= 3 => 'Manager review',
        $step >= 2 => 'Consultant review',
        $step >= 1 => 'Site verification',
        default => 'Pending submission',
    };
}

function contractor_ipc_pagination(int $page, int $pages, int $total, int $limit): void
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
