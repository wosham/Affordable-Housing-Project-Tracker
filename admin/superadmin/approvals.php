<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_approvals';
$activeTab = Security::cleanString((string)($_GET['tab'] ?? 'ipcs'));
$allowedTabs = ['ipcs', 'variations', 'eots', 'history'];
$activeTab = in_array($activeTab, $allowedTabs, true) ? $activeTab : 'ipcs';
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$perPage = 10;

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0);

$ipcStats = IPC::stats();
$variationStats = Variation::stats();
$eotStats = EOTRequest::stats();
$projects = Database::fetchAll('SELECT id, name FROM projects ORDER BY name ASC');

$ipcFilters = $filters;
if ($activeTab === 'ipcs' && empty($ipcFilters['status'])) {
    $ipcFilters['status'] = 'endorsed';
}
$variationFilters = $filters;
if ($activeTab === 'variations' && empty($variationFilters['status'])) {
    $variationFilters['status'] = 'pending';
}
$eotFilters = $filters;
if ($activeTab === 'eots' && empty($eotFilters['status'])) {
    $eotFilters['status'] = 'pending';
}

$activeTotal = match ($activeTab) {
    'variations' => Variation::countDetailed($variationFilters),
    'eots' => EOTRequest::countDetailed($eotFilters),
    'history' => approval_history_count($filters),
    default => IPC::countDetailed($ipcFilters),
};
$totalPages = max(1, (int)ceil($activeTotal / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$ipcRows = $activeTab === 'ipcs' ? IPC::detailed($ipcFilters, $perPage, $offset) : IPC::detailed(['status' => 'endorsed'], 5);
$variationRows = $activeTab === 'variations' ? Variation::detailed($variationFilters, $perPage, $offset) : Variation::detailed(['status' => 'pending'], 5);
$eotRows = $activeTab === 'eots' ? EOTRequest::detailed($eotFilters, $perPage, $offset) : EOTRequest::detailed(['status' => 'pending'], 5);
$historyRows = $activeTab === 'history' ? approval_history($filters, $perPage, $offset) : approval_history([], 8, 0);

$pageTitle = 'IPCs & Approvals';
$pageDescription = 'Final approval command centre for IPCs, variations and extension of time requests.';
$adminRole = 'superadmin';
$contentClass = 'sa-approvals-page';
$pageScripts = ['approvals'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'IPCs & Approvals'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-projects-hero sa-approval-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Director Approval Desk</span>
    <h2>Payment certificates, variations and time claims</h2>
    <p>Review final approval queues, inspect supporting details, approve valid submissions and reject incomplete claims with a recorded decision note.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/financials.php')) ?>"><i class="fa-solid fa-coins" aria-hidden="true"></i> Financials</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Audit Log</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 sa-approval-stats" aria-label="Approval summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($ipcStats['awaiting_final'] ?? 0)) ?></strong><span class="stat-widget__label">IPCs Awaiting Approval</span><small class="stat-widget__trend"><?= Security::e(format_money($ipcStats['awaiting_value'] ?? 0)) ?> in queue</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-code-branch" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($variationStats['pending_variations'] ?? 0)) ?></strong><span class="stat-widget__label">Pending Variations</span><small class="stat-widget__trend"><?= Security::e(format_money($variationStats['pending_value'] ?? 0)) ?> requested</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-clock" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($eotStats['pending_eots'] ?? 0)) ?></strong><span class="stat-widget__label">Pending EOTs</span><small class="stat-widget__trend"><?= Security::e(format_number($eotStats['pending_days'] ?? 0)) ?> day(s) requested</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_money($ipcStats['approved_value'] ?? 0)) ?></strong><span class="stat-widget__label">Approved IPC Value</span><small class="stat-widget__trend">Ready for finance processing</small></span></article>
</section>

<section class="card sa-approval-board">
  <div class="tabs">
    <div class="tabs__list sa-approval-tabs" role="tablist">
      <?= approval_tab('ipcs', 'IPC Final Approvals', $activeTab, $filters, $ipcStats['awaiting_final'] ?? 0) ?>
      <?= approval_tab('variations', 'Variations', $activeTab, $filters, $variationStats['pending_variations'] ?? 0) ?>
      <?= approval_tab('eots', 'EOT Requests', $activeTab, $filters, $eotStats['pending_eots'] ?? 0) ?>
      <?= approval_tab('history', 'Decision History', $activeTab, $filters, $activeTotal) ?>
    </div>
  </div>

  <form class="filter-bar sa-project-filter sa-approval-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/approvals.php')) ?>">
    <input type="hidden" name="tab" value="<?= Security::e($activeTab) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project, contractor, claim number..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= Security::e((string)$project['id']) ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Default queue</option><?php foreach (approval_status_options($activeTab) as $status): ?><option value="<?= Security::e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/approvals.php?tab=' . $activeTab)) ?>">Reset</a></div>
  </form>

<?php if ($activeTab === 'ipcs'): ?>
  <?= render_ipc_table($ipcRows, $offset) ?>
<?php elseif ($activeTab === 'variations'): ?>
  <?= render_variation_table($variationRows, $offset) ?>
<?php elseif ($activeTab === 'eots'): ?>
  <?= render_eot_table($eotRows, $offset) ?>
<?php else: ?>
  <?= render_history_table($historyRows, $offset) ?>
<?php endif; ?>

  <?= render_pagination($activeTab, $filters, $page, $totalPages, $activeTotal, $perPage) ?>
</section>

<div class="modal-backdrop" data-approval-backdrop></div>
<div class="modal sa-approval-modal" id="approvalActionModal" aria-hidden="true">
  <div class="modal__dialog">
    <form class="sa-approval-action-form" data-approval-form>
      <div class="modal__header">
        <h2 class="modal__title" data-action-title>Record decision</h2>
        <button class="modal__close" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
      <div class="modal__body">
        <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token($csrfForm)) ?>">
        <input type="hidden" name="endpoint" data-action-endpoint>
        <input type="hidden" name="entity_field" data-action-entity-field>
        <input type="hidden" name="entity_id" data-action-entity-id>
        <div class="alert alert--info" data-action-summary>Confirm this approval decision.</div>
        <label class="form-field" data-granted-days-field hidden>
          <span class="form-label">Granted days</span>
          <input class="form-input" type="number" min="1" name="granted_days" data-granted-days-input>
        </label>
        <label class="form-field">
          <span class="form-label" data-comment-label>Decision note</span>
          <textarea class="form-textarea" name="comment" rows="5" data-action-comment placeholder="Add a clear decision note."></textarea>
        </label>
      </div>
      <div class="modal__footer">
        <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
        <button class="btn btn--primary" type="submit" data-action-submit>Submit decision</button>
      </div>
    </form>
  </div>
</div>

<div class="modal sa-approval-modal" id="approvalDetailModal" aria-hidden="true">
  <div class="modal__dialog modal__dialog--wide">
    <div class="modal__header">
      <h2 class="modal__title">Claim details</h2>
      <button class="modal__close" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="modal__body" data-detail-content></div>
  </div>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function approval_tab(string $tab, string $label, string $activeTab, array $filters, int|string $count): string
{
    $url = Url::to('admin/superadmin/approvals.php?' . http_build_query(array_merge($filters, ['tab' => $tab, 'page' => 1])));
    $class = 'tabs__button' . ($tab === $activeTab ? ' is-active' : '');
    return '<a class="' . Security::e($class) . '" href="' . Security::e($url) . '">' . Security::e($label) . ' <span class="badge badge--lime">' . Security::e(format_number($count)) . '</span></a>';
}

function approval_status_options(string $tab): array
{
    return match ($tab) {
        'variations' => ['pending', 'approved', 'rejected'],
        'eots' => ['pending', 'granted', 'partially-granted', 'rejected'],
        'history' => ['endorsed', 'certified', 'approved', 'paid', 'rejected'],
        default => ['submitted', 'clerk-endorsed', 'certified', 'endorsed', 'approved', 'rejected', 'paid'],
    };
}

function render_ipc_table(array $rows, int $offset): string
{
    ob_start();
?>
  <div class="table-wrap">
    <table class="data-table sa-approval-table">
      <thead><tr><th>#</th><th>IPC</th><th>Project</th><th>Contractor</th><th>Period</th><th>Net Amount</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($rows === []): ?>
        <tr><td colspan="9"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i></span><strong class="empty-state__title">No IPCs in this queue</strong><span class="empty-state__text">Final approval claims will appear here after certification and endorsement.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($rows as $index => $ipc): ?>
        <tr>
          <td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td>
          <td><strong>IPC #<?= Security::e((string)$ipc['ipc_number']) ?></strong><small><?= Security::e(format_money($ipc['gross_amount'])) ?> gross</small></td>
          <td><strong><?= Security::e($ipc['project_name']) ?></strong><small><?= Security::e($ipc['constituency_name'] ?: 'County project') ?></small></td>
          <td><strong><?= Security::e($ipc['contractor_name']) ?></strong><small><?= Security::e($ipc['contractor_email']) ?></small></td>
          <td><?= Security::e(format_date($ipc['period_from'])) ?> - <?= Security::e(format_date($ipc['period_to'])) ?></td>
          <td><strong><?= Security::e(format_money($ipc['net_amount'])) ?></strong><small>Retention <?= Security::e(format_money($ipc['retention_amount'])) ?></small></td>
          <td><span class="badge <?= Security::e(status_badge_class($ipc['status'])) ?>"><?= Security::e(status_label($ipc['status'])) ?></span></td>
          <td><?= Security::e(time_ago($ipc['submitted_at'] ?? $ipc['created_at'])) ?></td>
          <td><div class="data-table__actions">
            <button class="btn btn--icon btn--outline" type="button" data-detail-open="#ipc-detail-<?= (int)$ipc['id'] ?>" title="View details"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
<?php if (in_array($ipc['status'], ['endorsed', 'certified'], true)): ?>
            <button class="btn btn--icon btn--primary" type="button" data-approval-action data-endpoint="api/ipcs/approve.php" data-entity-field="ipc_id" data-entity-id="<?= (int)$ipc['id'] ?>" data-title="Approve IPC #<?= Security::e((string)$ipc['ipc_number']) ?>" data-summary="Approve <?= Security::e(format_money($ipc['net_amount'])) ?> for <?= Security::e($ipc['project_name']) ?>?"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
            <button class="btn btn--icon btn--danger" type="button" data-approval-action data-requires-reason="1" data-endpoint="api/ipcs/reject.php" data-entity-field="ipc_id" data-entity-id="<?= (int)$ipc['id'] ?>" data-title="Reject IPC #<?= Security::e((string)$ipc['ipc_number']) ?>" data-summary="Record why this IPC should not proceed."><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
<?php endif; ?>
          </div>
          <template id="ipc-detail-<?= (int)$ipc['id'] ?>"><?= render_ipc_detail($ipc) ?></template>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php
    return ob_get_clean();
}

function render_ipc_detail(array $ipc): string
{
    $lines = IPC::lineItems((int)$ipc['id']);
    $trail = IPCApproval::forIPC((int)$ipc['id']);
    ob_start();
?>
  <div class="sa-detail-grid">
    <section>
      <h3>IPC #<?= Security::e((string)$ipc['ipc_number']) ?> - <?= Security::e($ipc['project_name']) ?></h3>
      <dl class="sa-preview-list">
        <div><dt>Contractor</dt><dd><?= Security::e($ipc['contractor_name']) ?></dd></div>
        <div><dt>Period</dt><dd><?= Security::e(format_date($ipc['period_from'])) ?> - <?= Security::e(format_date($ipc['period_to'])) ?></dd></div>
        <div><dt>Gross</dt><dd><?= Security::e(format_money($ipc['gross_amount'])) ?></dd></div>
        <div><dt>Retention</dt><dd><?= Security::e(format_money($ipc['retention_amount'])) ?></dd></div>
        <div><dt>Net</dt><dd><?= Security::e(format_money($ipc['net_amount'])) ?></dd></div>
      </dl>
    </section>
    <section>
      <h3>Approval trail</h3>
      <div class="sa-timeline">
<?php if ($trail === []): ?><p class="muted">No approval trail recorded yet.</p><?php endif; ?>
<?php foreach ($trail as $item): ?>
        <div><span class="badge <?= Security::e(status_badge_class($item['action'])) ?>"><?= Security::e(status_label($item['action'])) ?></span><strong><?= Security::e($item['actor_name']) ?></strong><small><?= Security::e(format_datetime($item['actioned_at'])) ?> <?= $item['comments'] ? '- ' . Security::e($item['comments']) : '' ?></small></div>
<?php endforeach; ?>
      </div>
    </section>
  </div>
  <h3>Line items</h3>
  <div class="table-wrap"><table class="data-table"><thead><tr><th>#</th><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>
<?php if ($lines === []): ?><tr><td colspan="5">No line items recorded.</td></tr><?php endif; ?>
<?php foreach ($lines as $i => $line): ?><tr><td><?= $i + 1 ?></td><td><?= Security::e($line['description']) ?></td><td><?= Security::e(format_number($line['qty_this_period'], 3)) ?></td><td><?= Security::e(format_money($line['rate'])) ?></td><td><?= Security::e(format_money($line['amount'])) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php
    return ob_get_clean();
}

function render_variation_table(array $rows, int $offset): string
{
    ob_start();
?>
  <div class="table-wrap"><table class="data-table sa-approval-table"><thead><tr><th>#</th><th>VO</th><th>Project</th><th>Submitted By</th><th>Description</th><th>Amount</th><th>Time Impact</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if ($rows === []): ?><tr><td colspan="9"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-code-branch" aria-hidden="true"></i></span><strong class="empty-state__title">No variations in this queue</strong><span class="empty-state__text">Variation orders will appear here once submitted.</span></div></td></tr><?php endif; ?>
<?php foreach ($rows as $index => $row): ?><tr><td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td><td><strong>VO #<?= Security::e((string)$row['vo_number']) ?></strong><small><?= Security::e(format_date($row['created_at'])) ?></small></td><td><?= Security::e($row['project_name']) ?></td><td><?= Security::e($row['submitter_name']) ?></td><td><?= Security::e(safe_truncate($row['description'], 90)) ?></td><td><?= Security::e(format_money($row['amount'])) ?></td><td><?= Security::e(format_number($row['impact_on_time_days'])) ?> days</td><td><span class="badge <?= Security::e(status_badge_class($row['status'])) ?>"><?= Security::e(status_label($row['status'])) ?></span></td><td><div class="data-table__actions"><?php if ($row['status'] === 'pending'): ?><button class="btn btn--icon btn--primary" type="button" data-approval-action data-endpoint="api/variations/approve.php" data-entity-field="variation_id" data-entity-id="<?= (int)$row['id'] ?>" data-title="Approve VO #<?= Security::e((string)$row['vo_number']) ?>" data-summary="Approve variation worth <?= Security::e(format_money($row['amount'])) ?>?"><i class="fa-solid fa-check"></i></button><button class="btn btn--icon btn--danger" type="button" data-approval-action data-requires-reason="1" data-endpoint="api/variations/reject.php" data-entity-field="variation_id" data-entity-id="<?= (int)$row['id'] ?>" data-title="Reject VO #<?= Security::e((string)$row['vo_number']) ?>" data-summary="Record the rejection reason."><i class="fa-solid fa-xmark"></i></button><?php endif; ?></div></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php
    return ob_get_clean();
}

function render_eot_table(array $rows, int $offset): string
{
    ob_start();
?>
  <div class="table-wrap"><table class="data-table sa-approval-table"><thead><tr><th>#</th><th>EOT</th><th>Project</th><th>Submitted By</th><th>Days</th><th>Reason</th><th>Evidence</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if ($rows === []): ?><tr><td colspan="9"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></span><strong class="empty-state__title">No EOT requests in this queue</strong><span class="empty-state__text">Time extension requests will appear here once submitted.</span></div></td></tr><?php endif; ?>
<?php foreach ($rows as $index => $row): ?><tr><td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td><td><strong>EOT #<?= Security::e((string)$row['eot_number']) ?></strong><small><?= Security::e(format_date($row['created_at'])) ?></small></td><td><?= Security::e($row['project_name']) ?></td><td><?= Security::e($row['submitter_name']) ?></td><td><?= Security::e(format_number($row['days_requested'])) ?> requested</td><td><?= Security::e(safe_truncate($row['reason'], 90)) ?></td><td><?php if ($row['supporting_evidence']): ?><a href="<?= Security::e(Url::asset($row['supporting_evidence'])) ?>" target="_blank" rel="noopener">Open</a><?php else: ?>-<?php endif; ?></td><td><span class="badge <?= Security::e(status_badge_class($row['status'])) ?>"><?= Security::e(status_label($row['status'])) ?></span></td><td><div class="data-table__actions"><?php if ($row['status'] === 'pending'): ?><button class="btn btn--icon btn--primary" type="button" data-approval-action data-endpoint="api/eot/approve.php" data-entity-field="eot_id" data-entity-id="<?= (int)$row['id'] ?>" data-title="Approve EOT #<?= Security::e((string)$row['eot_number']) ?>" data-summary="Grant up to <?= Security::e(format_number($row['days_requested'])) ?> day(s)." data-granted-days="<?= Security::e((string)$row['days_requested']) ?>"><i class="fa-solid fa-check"></i></button><button class="btn btn--icon btn--danger" type="button" data-approval-action data-requires-reason="1" data-endpoint="api/eot/reject.php" data-entity-field="eot_id" data-entity-id="<?= (int)$row['id'] ?>" data-title="Reject EOT #<?= Security::e((string)$row['eot_number']) ?>" data-summary="Record the rejection reason."><i class="fa-solid fa-xmark"></i></button><?php endif; ?></div></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php
    return ob_get_clean();
}

function render_history_table(array $rows, int $offset): string
{
    ob_start();
?>
  <div class="table-wrap"><table class="data-table sa-approval-table"><thead><tr><th>#</th><th>Decision</th><th>Project</th><th>Actor</th><th>Type</th><th>Comment</th><th>Date</th></tr></thead><tbody>
<?php if ($rows === []): ?><tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><strong class="empty-state__title">No approval history yet</strong><span class="empty-state__text">Director decisions and workflow records will appear here.</span></div></td></tr><?php endif; ?>
<?php foreach ($rows as $index => $row): ?><tr><td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td><td><span class="badge <?= Security::e(status_badge_class($row['action'])) ?>"><?= Security::e(status_label($row['action'])) ?></span></td><td><?= Security::e($row['project_name']) ?></td><td><?= Security::e($row['actor_name']) ?></td><td><?= Security::e($row['record_type']) ?></td><td><?= Security::e($row['comments'] ?: '-') ?></td><td><?= Security::e(format_datetime($row['actioned_at'])) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php
    return ob_get_clean();
}

function approval_history(array $filters, int $limit, int $offset): array
{
    [$where, $bindings] = approval_history_where($filters);
    return Database::fetchAll("
        SELECT ia.action, ia.comments, ia.actioned_at, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS actor_name, 'IPC' AS record_type
        FROM ipc_approvals ia
        INNER JOIN ipcs i ON i.id = ia.ipc_id
        INNER JOIN projects p ON p.id = i.project_id
        INNER JOIN users u ON u.id = ia.action_by
        {$where}
        ORDER BY ia.actioned_at DESC, ia.id DESC
        LIMIT " . (int)$limit . " OFFSET " . max(0, $offset), $bindings);
}

function approval_history_count(array $filters): int
{
    [$where, $bindings] = approval_history_where($filters);
    $row = Database::fetch("SELECT COUNT(*) AS total FROM ipc_approvals ia INNER JOIN ipcs i ON i.id = ia.ipc_id INNER JOIN projects p ON p.id = i.project_id INNER JOIN users u ON u.id = ia.action_by {$where}", $bindings);
    return (int)($row['total'] ?? 0);
}

function approval_history_where(array $filters): array
{
    $where = [];
    $bindings = [];

    if (!empty($filters['q'])) {
        $where[] = '(p.name LIKE ? OR ia.comments LIKE ? OR CONCAT(u.first_name, " ", u.last_name) LIKE ?)';
        $term = '%' . $filters['q'] . '%';
        array_push($bindings, $term, $term, $term);
    }

    if (!empty($filters['status'])) {
        $where[] = 'ia.action = ?';
        $bindings[] = $filters['status'];
    }

    if (!empty($filters['project_id'])) {
        $where[] = 'i.project_id = ?';
        $bindings[] = (int)$filters['project_id'];
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'DATE(ia.actioned_at) >= ?';
        $bindings[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'DATE(ia.actioned_at) <= ?';
        $bindings[] = $filters['date_to'];
    }

    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
}

function render_pagination(string $tab, array $filters, int $page, int $totalPages, int $total, int $perPage): string
{
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $to = min($total, $page * $perPage);
    ob_start();
?>
  <nav class="pagination sa-project-pagination" aria-label="Approval pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> records</p>
    <div class="pagination__list">
      <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(approval_page_url($tab, $filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(approval_page_url($tab, $filters, $i)) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
      <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(approval_page_url($tab, $filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right"></i></a>
    </div>
  </nav>
<?php
    return ob_get_clean();
}

function approval_page_url(string $tab, array $filters, int $page): string
{
    return Url::to('admin/superadmin/approvals.php?' . http_build_query(array_merge($filters, ['tab' => $tab, 'page' => $page])));
}
