<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('contractor'));

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
$perPage = 12;
$total = IPC::countDetailed($filters);
$ipcs = IPC::detailed($filters, $perPage, ($page - 1) * $perPage);
$stats = ContractorIPC::stats($userId, $role);
$statusFlow = IPC::statusFlow();

$pageTitle = 'IPC History';
$pageDescription = 'Track submitted IPCs, workflow status and payment progress.';
$adminRole = 'contractor';
$contentClass = 'contractor-ipc-page';
$componentCss = ['contractor-ipc'];
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
    <p>Track submitted claims through verification, certification, approval and payment.</p>
  </div>
  <div class="contractor-ipc-actions">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/contractor/ipc-submit.php')) ?>"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Submit IPC</a>
    <button class="btn btn--outline" type="button" onclick="window.print()"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
  </div>
</section>

<section class="contractor-ipc-stats">
  <?php contractor_ipc_stat('fa-file-invoice-dollar', $stats['total'], 'Total IPCs', 'All submitted claims'); ?>
  <?php contractor_ipc_stat('fa-paper-plane', $stats['submitted'], 'Submitted', 'Awaiting site verification'); ?>
  <?php contractor_ipc_stat('fa-stamp', $stats['certified'], 'Certified', 'Consultant reviewed'); ?>
  <?php contractor_ipc_stat('fa-circle-check', $stats['approved'], 'Approved', 'Ready for payment'); ?>
  <?php contractor_ipc_stat('fa-credit-card', $stats['paid'], 'Paid', 'Completed claims'); ?>
  <?php contractor_ipc_stat('fa-coins', 'KES ' . number_format((float)$stats['net_value'], 2), 'Net Value', 'Total claimed'); ?>
</section>

<section class="card contractor-ipc-panel">
  <div class="card__header">
    <div>
      <h2 class="card__title">IPC Registry</h2>
      <p class="card__subtitle">Search and filter submitted claims.</p>
    </div>
    <span class="badge badge--info"><?= (int)$total ?> records</span>
  </div>
  <form class="contractor-ipc-filter" method="get">
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
        <?php foreach (IPC::statuses() as $status): ?>
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
          <th>Workflow</th>
          <th>Last Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($ipcs === []): ?>
          <tr><td colspan="7"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No IPCs found</strong><span class="empty-state__text">Submit an IPC or adjust the current filters.</span></div></td></tr>
        <?php endif; ?>
        <?php foreach ($ipcs as $ipc): ?>
          <?php $state = IPC::workflowState($ipc); ?>
          <tr>
            <td><strong>IPC #<?= (int)$ipc['ipc_number'] ?></strong><small><?= Security::e($ipc['contractor_reference'] ?? 'No reference') ?></small></td>
            <td><?= Security::e($ipc['project_name']) ?><small><?= Security::e($ipc['constituency_name'] ?? '-') ?></small></td>
            <td><?= Security::e(format_date($ipc['period_from'] ?? null)) ?> to <?= Security::e(format_date($ipc['period_to'] ?? null)) ?><small>Submitted <?= Security::e(format_datetime($ipc['submitted_at'] ?? null)) ?></small></td>
            <td><strong>KES <?= number_format((float)$ipc['net_amount'], 2) ?></strong><small>Gross KES <?= number_format((float)$ipc['gross_amount'], 2) ?></small></td>
            <td><span class="badge badge--<?= (string)$ipc['status'] === 'rejected' ? 'danger' : ((string)$ipc['status'] === 'paid' ? 'success' : 'info') ?>"><?= Security::e(status_label((string)$ipc['status'])) ?></span></td>
            <td>
              <div class="contractor-ipc-mini-flow" style="--step: <?= max(0, (int)$state['current_step']) ?>">
                <?php for ($i = 1; $i <= 6; $i++): ?><span<?= $i <= (int)$state['current_step'] ? ' class="is-done"' : '' ?>></span><?php endfor; ?>
              </div>
              <small><?= $state['current_step'] > 0 ? 'Step ' . (int)$state['current_step'] . ' of 6' : 'Returned for action' ?></small>
            </td>
            <td><strong><?= Security::e(status_label($ipc['last_action'] ?? $ipc['status'])) ?></strong><small><?= Security::e($ipc['last_action_by'] ?: 'System') ?> / <?= Security::e(format_datetime($ipc['last_actioned_at'] ?? $ipc['updated_at'] ?? null)) ?></small></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($total > $perPage): ?>
    <div class="pagination">
      <span class="pagination__meta">Showing <?= (($page - 1) * $perPage) + 1 ?>-<?= min($total, $page * $perPage) ?> of <?= (int)$total ?> IPCs</span>
      <div class="pagination__links">
        <a class="pagination__link" href="?<?= Security::e(http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)]))) ?>"><i class="fa-solid fa-chevron-left"></i></a>
        <span class="pagination__link is-active"><?= (int)$page ?></span>
        <a class="pagination__link" href="?<?= Security::e(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>"><i class="fa-solid fa-chevron-right"></i></a>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function contractor_ipc_stat(string $icon, string|int|float $value, string $label, string $hint): void
{
?>
  <article class="card contractor-ipc-stat">
    <span><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <div><strong><?= Security::e((string)$value) ?></strong><b><?= Security::e($label) ?></b><small><?= Security::e($hint) ?></small></div>
  </article>
<?php
}
?>
