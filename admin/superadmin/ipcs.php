<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_ipcs';
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'contractor_id' => Security::cleanInt($_GET['contractor_id'] ?? 0),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
    'amount_min' => Security::cleanFloat($_GET['amount_min'] ?? 0),
    'amount_max' => Security::cleanFloat($_GET['amount_max'] ?? 0),
    'payment_readiness' => Security::cleanString((string)($_GET['payment_readiness'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== 0.0);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalIpcs = IPC::centreCount($filters);
$totalPages = max(1, (int)ceil($totalIpcs / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$rows = IPC::centreList($filters, $perPage, $offset);
$stats = IPC::centreStats(array_diff_key($filters, ['status' => true]));
$showingFrom = $totalIpcs > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($rows), $totalIpcs);

$projects = Database::fetchAll('SELECT id, name FROM projects ORDER BY name ASC');
$contractors = Database::fetchAll("
    SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name
    FROM users u
    INNER JOIN ipcs i ON i.contractor_id = u.id
    ORDER BY name ASC
");
$constituencies = Database::fetchAll('SELECT id, name FROM constituencies ORDER BY name ASC');

$pageTitle = 'IPC Centre';
$pageDescription = 'Full IPC registry, status flow, approval readiness and payment certificate details.';
$adminRole = 'superadmin';
$contentClass = 'sa-ipc-page';
$componentCss = ['ipc-centre'];
$pageScripts = ['ipc-centre'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'IPC Centre'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card ipc-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Payment Certificates</span>
    <h2>IPC Centre</h2>
    <p>Track every interim payment certificate from submission through verification, certification, approval, payment or rejection.</p>
  </div>
  <div class="ipc-action-bar">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/approvals.php?tab=ipcs')) ?>"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Approval Desk</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/financials.php')) ?>"><i class="fa-solid fa-coins" aria-hidden="true"></i> Financials</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 ipc-stats" aria-label="IPC summary">
  <?php ipc_stat('fa-file-invoice', $stats['total'] ?? 0, 'Total IPCs', 'All records'); ?>
  <?php ipc_stat('fa-hourglass-half', $stats['approval_queue_value'] ?? 0, 'Approval Queue', 'Certified or endorsed', true); ?>
  <?php ipc_stat('fa-circle-check', $stats['approved_unpaid_value'] ?? 0, 'Approved Unpaid', 'Ready for finance', true); ?>
  <?php ipc_stat('fa-money-bill-wave', $stats['net_value'] ?? 0, 'Net Value', 'All IPCs', true); ?>
</section>

<section class="card ipc-status-tabs" aria-label="IPC status filters">
  <a class="<?= ipc_tab_class('', $filters) ?>" href="<?= Security::e(ipc_filter_url($filters, ['status' => null, 'page' => 1])) ?>">All <span><?= Security::e(format_number($stats['total'] ?? 0)) ?></span></a>
  <?php foreach (IPC::statuses() as $status): ?>
    <a class="<?= ipc_tab_class($status, $filters) ?>" href="<?= Security::e(ipc_filter_url($filters, ['status' => $status, 'page' => 1])) ?>"><?= Security::e(status_label($status)) ?> <span><?= Security::e(format_number($stats[str_replace('-', '_', $status)] ?? 0)) ?></span></a>
  <?php endforeach; ?>
</section>

<section class="card ipc-registry">
  <div class="card__header">
    <div>
      <h2 class="card__title">IPC Registry</h2>
      <p class="card__subtitle">Search, filter and open a dedicated certificate detail record.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalIpcs)) ?> records</span>
  </div>

  <form class="filter-bar ipc-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/ipcs.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="IPC, project, contractor..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="contractor_id">Contractor</label><select class="form-select" id="contractor_id" name="contractor_id"><option value="">All contractors</option><?php foreach ($contractors as $contractor): ?><option value="<?= (int)$contractor['id'] ?>" <?= (int)($filters['contractor_id'] ?? 0) === (int)$contractor['id'] ? 'selected' : '' ?>><?= Security::e($contractor['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="constituency_id">Constituency</label><select class="form-select" id="constituency_id" name="constituency_id"><option value="">All constituencies</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= (int)($filters['constituency_id'] ?? 0) === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (IPC::statuses() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="payment_readiness">Readiness</label><select class="form-select" id="payment_readiness" name="payment_readiness"><option value="">Any</option><option value="ready" <?= (($filters['payment_readiness'] ?? '') === 'ready') ? 'selected' : '' ?>>Ready for payment</option><option value="paid" <?= (($filters['payment_readiness'] ?? '') === 'paid') ? 'selected' : '' ?>>Paid</option><option value="blocked" <?= (($filters['payment_readiness'] ?? '') === 'blocked') ? 'selected' : '' ?>>Blocked / pending</option></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/ipcs.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table ipc-table">
      <thead><tr><th>IPC</th><th>Project</th><th>Contractor</th><th>Period</th><th class="is-money">Gross</th><th class="is-money">Retention</th><th class="is-money">Net</th><th>Status</th><th>Last Action</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($rows === []): ?>
        <tr><td colspan="10"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></span><strong class="empty-state__title">No IPCs match these filters</strong><span class="empty-state__text">Clear filters or wait for contractor submissions.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($rows as $ipc): ?>
        <tr>
          <td><strong>IPC #<?= Security::e((string)$ipc['ipc_number']) ?></strong><small><?= Security::e(format_datetime($ipc['submitted_at'] ?? $ipc['created_at'])) ?></small></td>
          <td><strong><?= Security::e($ipc['project_name']) ?></strong><small><?= Security::e($ipc['constituency_name'] ?: 'County project') ?></small></td>
          <td><strong><?= Security::e($ipc['contractor_name']) ?></strong><small><?= Security::e($ipc['contractor_email']) ?></small></td>
          <td><?= Security::e(format_date($ipc['period_from'])) ?> - <?= Security::e(format_date($ipc['period_to'])) ?></td>
          <td class="is-money"><?= Security::e(format_money($ipc['gross_amount'])) ?></td>
          <td class="is-money"><?= Security::e(format_money($ipc['retention_amount'])) ?></td>
          <td class="is-money"><strong><?= Security::e(format_money($ipc['net_amount'])) ?></strong></td>
          <td><span class="badge <?= Security::e(status_badge_class($ipc['status'])) ?>"><?= Security::e(status_label($ipc['status'])) ?></span></td>
          <td><span class="ipc-muted"><?= Security::e($ipc['last_action'] ? status_label($ipc['last_action']) . ' by ' . ($ipc['last_action_by'] ?: 'staff') : 'No action yet') ?></span></td>
          <td><div class="data-table__actions">
            <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/ipc-detail.php?id=' . (int)$ipc['id'])) ?>" title="Open IPC detail" aria-label="Open IPC detail"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
<?php if (IPC::canApprove($ipc)): ?>
            <button class="btn btn--icon btn--success" type="button" data-ipc-action="approve" data-ipc-id="<?= (int)$ipc['id'] ?>" data-ipc-title="IPC #<?= Security::e((string)$ipc['ipc_number']) ?>" data-ipc-summary="<?= Security::e($ipc['project_name'] . ' - ' . format_money($ipc['net_amount'])) ?>" title="Approve" aria-label="Approve"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
<?php endif; ?>
<?php if (IPC::canReject($ipc)): ?>
            <button class="btn btn--icon btn--danger" type="button" data-ipc-action="reject" data-ipc-id="<?= (int)$ipc['id'] ?>" data-ipc-title="IPC #<?= Security::e((string)$ipc['ipc_number']) ?>" data-ipc-summary="<?= Security::e($ipc['project_name']) ?>" title="Reject" aria-label="Reject"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
<?php endif; ?>
          </div></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="IPC pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalIpcs)) ?> IPCs</p>
    <div class="pagination__list">
      <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(ipc_filter_url($filters, ['page' => max(1, $page - 1)])) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(ipc_filter_url($filters, ['page' => $i])) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
      <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(ipc_filter_url($filters, ['page' => min($totalPages, $page + 1)])) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<?= ipc_decision_modal($csrfForm) ?>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function ipc_stat(string $icon, mixed $value, string $label, string $sub, bool $money = false): void
{
    ?>
    <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e($money ? format_money($value) : format_number($value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($sub) ?></small></span></article>
    <?php
}

function ipc_filter_url(array $filters, array $overrides = []): string
{
    $query = array_merge($filters, $overrides);
    $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0 && $value !== 0.0);
    return Url::to('admin/superadmin/ipcs.php' . ($query ? '?' . http_build_query($query) : ''));
}

function ipc_tab_class(string $status, array $filters): string
{
    return 'ipc-tab' . (((string)($filters['status'] ?? '') === $status) ? ' is-active' : '');
}

function ipc_decision_modal(string $csrfForm): string
{
    ob_start();
    ?>
    <div class="modal-backdrop" data-ipc-backdrop></div>
    <div class="modal ipc-decision-modal" id="ipcDecisionModal" aria-hidden="true">
      <div class="modal__dialog">
        <form data-ipc-decision-form>
          <div class="modal__header"><h2 class="modal__title" data-ipc-modal-title>Record IPC decision</h2><button class="modal__close" type="button" data-ipc-modal-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
          <div class="modal__body">
            <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token($csrfForm)) ?>">
            <input type="hidden" name="ipc_id" data-ipc-modal-id>
            <input type="hidden" name="mode" data-ipc-modal-mode>
            <div class="alert alert--info" data-ipc-modal-summary>Confirm the IPC decision.</div>
            <label class="form-field"><span class="form-label" data-ipc-comment-label>Decision note</span><textarea class="form-textarea" name="comment" rows="5" data-ipc-comment></textarea></label>
            <div class="alert alert--danger" data-ipc-error hidden></div>
          </div>
          <div class="modal__footer"><button class="btn btn--outline" type="button" data-ipc-modal-close>Cancel</button><button class="btn btn--primary" type="submit" data-ipc-submit>Submit decision</button></div>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
