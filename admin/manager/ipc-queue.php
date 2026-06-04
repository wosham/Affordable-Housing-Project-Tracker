<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerIPCQueue::projects($userId, $role);
$filters = array_filter([
    'view' => Security::cleanString((string)($_GET['view'] ?? 'ready')),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
    'amount_min' => Security::cleanFloat($_GET['amount_min'] ?? 0),
    'amount_max' => Security::cleanFloat($_GET['amount_max'] ?? 0),
    'high_value' => Security::cleanInt($_GET['high_value'] ?? 0),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== 0.0 && $value !== null);

if (!in_array((string)($filters['view'] ?? 'ready'), ManagerIPCQueue::VIEWS, true)) {
    $filters['view'] = 'ready';
}

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerIPCQueue::count($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$ipcs = array_map([ManagerIPCQueue::class, 'payload'], ManagerIPCQueue::list($userId, $role, $filters, $perPage, $offset));
$stats = ManagerIPCQueue::summary($userId, $role, array_diff_key($filters, ['view' => true, 'status' => true]));
$insights = ManagerIPCQueue::insights($userId, $role, array_diff_key($filters, ['view' => true, 'status' => true]));
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($ipcs), $total);

$pageTitle = 'IPC Queue';
$pageDescription = 'Endorse certified payment certificates, reject items that need correction and track IPC movement.';
$adminRole = 'manager';
$csrfForm = 'manager_ipc_queue';
$contentClass = 'manager-ipc-page';
$componentCss = ['manager-ipc-queue'];
$pageScripts = ['manager-ipc-queue'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'IPC Queue'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="manager-ipc-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Payment certificates</span>
    <h2>IPC Queue</h2>
    <p>Endorse certified payment certificates, reject items that need correction and track IPC movement.</p>
  </div>
  <div class="manager-ipc-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/boq.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> BOQ</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/reports.php')) ?>"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Reports</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 manager-ipc-stats" aria-label="IPC summary">
  <?php manager_ipc_stat('fa-clipboard-check', $stats['ready'], 'Ready for Endorsement', format_money($stats['ready_value'])); ?>
  <?php manager_ipc_stat('fa-route', $stats['incoming'], 'Incoming', 'With clerk or consultant'); ?>
  <?php manager_ipc_stat('fa-circle-check', $stats['endorsed'], 'Endorsed', 'Sent to final approval'); ?>
  <?php manager_ipc_stat('fa-ban', $stats['rejected'], 'Rejected', 'Returned for correction'); ?>
  <?php manager_ipc_stat('fa-money-bill-wave', format_money($stats['net_value']), 'Net Value', 'Assigned IPCs'); ?>
  <?php manager_ipc_stat('fa-triangle-exclamation', $stats['high_value'], 'High Value', 'Needs close review'); ?>
  <?php manager_ipc_stat('fa-folder-open', count($projects), 'Assigned Projects', 'Current portfolio'); ?>
  <?php manager_ipc_stat('fa-signal', $stats['total'], 'Total IPCs', 'Visible to manager'); ?>
</section>

<section class="card manager-ipc-tabs" aria-label="IPC views">
  <?php foreach (['ready' => 'Ready for Manager', 'incoming' => 'Incoming', 'endorsed' => 'Endorsed', 'rejected' => 'Rejected', 'all' => 'All Assigned IPCs'] as $view => $label): ?>
    <a class="manager-ipc-tab<?= (($filters['view'] ?? 'ready') === $view) ? ' is-active' : '' ?>" href="<?= Security::e(manager_ipc_page_url($filters, ['view' => $view, 'status' => null, 'page' => 1])) ?>"><?= Security::e($label) ?></a>
  <?php endforeach; ?>
</section>

<section class="manager-ipc-layout">
  <div class="manager-ipc-main">
    <section class="card manager-ipc-card">
      <div class="card__header">
        <div>
          <h2 class="card__title">IPC Register</h2>
          <p class="card__subtitle">Review certified IPCs and record manager decisions.</p>
        </div>
        <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> IPCs</span>
      </div>

      <form class="filter-bar manager-ipc-filter" method="get" action="<?= Security::e(Url::to('admin/manager/ipc-queue.php')) ?>">
        <input type="hidden" name="view" value="<?= Security::e($filters['view'] ?? 'ready') ?>">
        <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="IPC, project, contractor..."></div>
        <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">View default</option><?php foreach (IPC::statuses() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
        <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
        <div class="filter-group"><label class="filter-label" for="amount_min">Min amount</label><input class="form-input" type="number" min="0" step="0.01" id="amount_min" name="amount_min" value="<?= Security::e($filters['amount_min'] ?? '') ?>"></div>
        <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/ipc-queue.php')) ?>">Reset</a></div>
      </form>

      <div class="table-wrap">
        <table class="data-table manager-ipc-table">
          <thead><tr><th>IPC</th><th>Project</th><th>Contractor</th><th>Period</th><th class="is-money">Gross</th><th class="is-money">Retention</th><th class="is-money">Net</th><th>Status</th><th>Last Action</th><th>Actions</th></tr></thead>
          <tbody>
<?php if ($ipcs === []): ?>
            <tr><td colspan="10"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></span><strong class="empty-state__title">No IPCs found</strong><span class="empty-state__text">Adjust filters or wait for certified IPCs.</span></div></td></tr>
<?php else: foreach ($ipcs as $ipc): ?>
            <tr data-ipc-row="<?= (int)$ipc['id'] ?>">
              <td><strong>IPC #<?= Security::e($ipc['ipc_number']) ?></strong><small><?= Security::e($ipc['submitted_label']) ?></small><?php if ($ipc['is_high_value']): ?><small class="manager-ipc-signal">High value</small><?php endif; ?></td>
              <td><strong><?= Security::e($ipc['project_name']) ?></strong><small><?= Security::e($ipc['constituency_name'] ?: 'Assigned project') ?></small></td>
              <td><strong><?= Security::e($ipc['contractor_name']) ?></strong><small><?= Security::e($ipc['contractor_email']) ?></small></td>
              <td><?= Security::e($ipc['period_label']) ?></td>
              <td class="is-money"><?= Security::e(format_money($ipc['gross_amount'])) ?></td>
              <td class="is-money"><?= Security::e(format_money($ipc['retention_amount'])) ?></td>
              <td class="is-money"><strong><?= Security::e(format_money($ipc['net_amount'])) ?></strong></td>
              <td><span class="badge <?= Security::e($ipc['status_badge']) ?>"><?= Security::e($ipc['status_label']) ?></span><?php if ($ipc['warning_count'] > 0): ?><small class="manager-ipc-warning"><?= (int)$ipc['warning_count'] ?> warning(s)</small><?php endif; ?></td>
              <td><span class="manager-ipc-muted"><?= Security::e($ipc['last_action_label']) ?></span></td>
              <td><div class="data-table__actions">
                <button class="btn btn--icon btn--outline" type="button" data-ipc-detail data-id="<?= (int)$ipc['id'] ?>" title="View details" aria-label="View details"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
<?php if ($ipc['is_ready_for_manager']): ?>
                <button class="btn btn--icon btn--success" type="button" data-ipc-decision="endorse" data-id="<?= (int)$ipc['id'] ?>" data-title="IPC #<?= Security::e($ipc['ipc_number']) ?>" data-summary="<?= Security::e($ipc['project_name'] . ' - ' . format_money($ipc['net_amount'])) ?>" title="Endorse IPC" aria-label="Endorse IPC"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                <button class="btn btn--icon btn--danger" type="button" data-ipc-decision="reject" data-id="<?= (int)$ipc['id'] ?>" data-title="IPC #<?= Security::e($ipc['ipc_number']) ?>" data-summary="<?= Security::e($ipc['project_name']) ?>" title="Reject IPC" aria-label="Reject IPC"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
<?php endif; ?>
              </div></td>
            </tr>
<?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

<?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="IPC pagination">
        <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> IPCs</p>
        <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_ipc_page_url($filters, ['page' => max(1, $page - 1)])) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(manager_ipc_page_url($filters, ['page' => min($totalPages, $page + 1)])) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
      </nav>
<?php endif; ?>
    </section>
  </div>

  <aside class="manager-ipc-side" aria-label="IPC insights">
    <?php manager_ipc_panel('Ready for Manager', 'fa-clipboard-check', $insights['ready'], 'No certified IPCs waiting'); ?>
    <?php manager_ipc_panel('Incoming', 'fa-route', $insights['incoming'], 'No incoming IPCs'); ?>
    <?php manager_ipc_panel('Recently Endorsed', 'fa-circle-check', $insights['endorsed'], 'No endorsed IPCs yet'); ?>
  </aside>
</section>

<div class="manager-ipc-modal" data-ipc-modal hidden>
  <form class="manager-ipc-modal__panel" data-ipc-form>
    <div class="manager-ipc-modal__header">
      <div><span class="sa-panel-label" data-ipc-modal-kicker>Manager decision</span><h2 data-ipc-modal-title>Record IPC decision</h2><p data-ipc-modal-summary></p></div>
      <button class="btn btn--icon btn--ghost" type="button" data-ipc-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <input type="hidden" name="ipc_id" data-field="ipc_id">
    <input type="hidden" name="mode" data-field="mode">
    <div class="manager-ipc-modal__body">
      <label class="form-field"><span class="form-label" data-ipc-comment-label>Decision note</span><textarea class="form-textarea" name="comment" rows="5" data-field="comment"></textarea></label>
      <p class="manager-ipc-form-status" data-ipc-status></p>
    </div>
    <div class="manager-ipc-modal__footer">
      <button class="btn btn--outline" type="button" data-ipc-close>Cancel</button>
      <button class="btn btn--primary" type="submit" data-ipc-submit><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Decision</button>
    </div>
  </form>
</div>

<div class="manager-ipc-detail" data-ipc-detail-panel hidden>
  <div class="manager-ipc-detail__panel">
    <div class="manager-ipc-detail__header">
      <div><span class="sa-panel-label">IPC details</span><h2 data-detail-title>IPC details</h2><p data-detail-summary></p></div>
      <button class="btn btn--icon btn--ghost" type="button" data-detail-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="manager-ipc-detail__body">
      <div class="manager-ipc-detail-grid" data-detail-metrics></div>
      <section><h3>Line Items</h3><div class="manager-ipc-line-list" data-detail-lines></div></section>
      <section><h3>Workflow Timeline</h3><div class="manager-ipc-timeline" data-detail-timeline></div></section>
      <section data-detail-warnings-wrap hidden><h3>Warnings</h3><ul class="manager-ipc-warnings" data-detail-warnings></ul></section>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function manager_ipc_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number($value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function manager_ipc_panel(string $title, string $icon, array $items, string $empty): void
{
?>
  <section class="card manager-ipc-panel">
    <div class="card__header"><div><h2 class="card__title"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i> <?= Security::e($title) ?></h2></div></div>
    <div class="manager-ipc-mini-list">
<?php if ($items === []): ?>
      <div class="empty-state empty-state--compact"><strong class="empty-state__title"><?= Security::e($empty) ?></strong></div>
<?php else: foreach ($items as $item): ?>
      <button type="button" data-ipc-detail data-id="<?= (int)$item['id'] ?>"><span><strong>IPC #<?= Security::e($item['ipc_number']) ?></strong><small><?= Security::e($item['project_name']) ?></small></span><em><?= Security::e(format_money($item['net_amount'])) ?></em></button>
<?php endforeach; endif; ?>
    </div>
  </section>
<?php
}

function manager_ipc_page_url(array $filters, array $overrides = []): string
{
    $query = array_merge($filters, $overrides);
    $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0 && $value !== 0.0);
    return Url::to('admin/manager/ipc-queue.php' . ($query ? '?' . http_build_query($query) : ''));
}
