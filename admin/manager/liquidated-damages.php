<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
require_once __DIR__ . '/../../app/partials/admin/manager-contract-helpers.php';
Guard::exactRole('manager');

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerContractControl::projects($userId, $role);
$defaultProjectId = mcc_default_project($projects, 'ld_count');
$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'applied' => Security::cleanString((string)($_GET['applied'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerContractControl::countLds($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = array_map([ManagerContractControl::class, 'ldPayload'], ManagerContractControl::lds($userId, $role, $filters, $perPage, $offset));
$summary = ManagerContractControl::ldSummary($userId, $role, $filters);
$ipcs = !empty($filters['project_id']) ? ManagerContractControl::ipcsForProject($userId, $role, (int)$filters['project_id']) : [];

$pageTitle = 'Liquidated Damages';
$pageDescription = 'Track delay exposure, LD calculations and IPC application for your assigned projects only.';
$adminRole = 'manager';
$csrfForm = 'manager_contract_controls';
$contentClass = 'manager-contract-controls-page';
$componentCss = ['manager-contract-controls'];
$pageScripts = ['manager-contract-controls'];
$breadcrumbs = [['label' => 'Portal', 'url' => Url::to('admin/index.php')], ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')], ['label' => 'Liquidated Damages']];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="mcc-hero card">
  <div><span class="sa-panel-label"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Contract controls</span><h2>Liquidated Damages</h2><p>Track delay exposure, LD calculations and IPC application for your assigned projects only.</p></div>
  <div class="mcc-hero__actions"><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/eot-requests.php')) ?>"><i class="fa-solid fa-clock-rotate-left"></i> EOT Requests</a><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/subcontractors.php')) ?>"><i class="fa-solid fa-people-carry-box"></i> Subcontractors</a><button class="btn btn--primary" type="button" data-mcc-open="ld"><i class="fa-solid fa-plus"></i> New LD</button></div>
</section>

<?php mcc_project_strip($projects, $filters, 'ld_count', 'LDs', 'admin/manager/liquidated-damages.php'); ?>

<section class="stat-grid stat-grid--4 mcc-stats">
  <?php mcc_stat('fa-scale-balanced', $summary['total'], 'LD Records', 'Assigned portfolio'); ?>
  <?php mcc_stat('fa-money-bill-trend-up', format_money($summary['total_value']), 'Total Exposure', 'Calculated LDs'); ?>
  <?php mcc_stat('fa-calendar-xmark', $summary['overdue_days'], 'Overdue Days', 'Recorded delay'); ?>
  <?php mcc_stat('fa-file-invoice-dollar', $summary['applied'], 'Applied To IPC', 'Linked deductions'); ?>
  <?php mcc_stat('fa-hourglass-half', $summary['pending'], 'Pending', 'Awaiting action'); ?>
  <?php mcc_stat('fa-ban', $summary['waived'], 'Suspended/Waived', 'Not active'); ?>
</section>

<section class="card mcc-card">
  <div class="card__header"><div><h2 class="card__title">LD Tracker</h2><p class="card__subtitle">Record rates, days overdue, notes and application status.</p></div><span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span></div>
  <form class="filter-bar mcc-filter" method="get" action="<?= Security::e(Url::to('admin/manager/liquidated-damages.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project, IPC or notes..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerContractControl::LD_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="applied">IPC</label><select class="form-select" id="applied" name="applied"><option value="">Any</option><option value="yes" <?= (($filters['applied'] ?? '') === 'yes') ? 'selected' : '' ?>>Applied</option><option value="no" <?= (($filters['applied'] ?? '') === 'no') ? 'selected' : '' ?>>Not applied</option></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/liquidated-damages.php')) ?>">Reset</a></div>
  </form>
  <div class="table-wrap"><table class="data-table mcc-table"><thead><tr><th>Project</th><th>Rate</th><th>Days</th><th>Total LD</th><th>IPC</th><th>Status</th><th>Manage</th></tr></thead><tbody>
<?php if ($records === []): ?><tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-scale-balanced"></i></span><strong class="empty-state__title">No LD records found</strong><span class="empty-state__text">Create an LD record or adjust filters.</span></div></td></tr><?php else: foreach ($records as $record): ?>
    <tr>
      <td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['created_label']) ?></small></td>
      <td class="is-num"><strong><?= Security::e(format_money($record['rate_per_day'])) ?></strong><small>per day</small></td>
      <td class="is-num"><strong><?= Security::e(format_number($record['days_overdue'])) ?></strong></td>
      <td class="is-num"><strong><?= Security::e(format_money($record['total_ld'])) ?></strong></td>
      <td><strong><?= Security::e($record['ipc_label'] ?: '-') ?></strong></td>
      <td><span class="mcc-pill mcc-pill--<?= Security::e($record['status']) ?>"><?= Security::e($record['status_label']) ?></span><small><?= Security::e(safe_truncate($record['notes'] ?: 'No notes', 70)) ?></small></td>
      <td class="mcc-actions manager-table-actions">
        <button class="btn btn--icon btn--primary" type="button" data-mcc-open="ld"<?= mcc_data_attrs([
            'id' => $record['id'],
            'project_id' => $record['project_id'],
            'status' => $record['status'] ?? 'draft',
            'rate_per_day' => $record['rate_per_day'] ?? 0,
            'days_overdue' => $record['days_overdue'] ?? 0,
            'applied_to_ipc_id' => $record['applied_to_ipc_id'] ?? '',
            'notes' => $record['notes'] ?? '',
        ]) ?> title="Edit LD" aria-label="Edit LD"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
<?php if (($record['status'] ?? '') !== 'applied'): ?>
        <button class="btn btn--icon btn--outline" type="button" data-mcc-status data-endpoint="api/manager/liquidated-damage-status.php" data-id="<?= (int)$record['id'] ?>" data-status="applied" title="Mark applied" aria-label="Mark applied"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
<?php else: ?>
        <button class="btn btn--icon btn--outline" type="button" data-mcc-status data-endpoint="api/manager/liquidated-damage-status.php" data-id="<?= (int)$record['id'] ?>" data-status="pending" title="Mark pending" aria-label="Mark pending"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
<?php endif; ?>
      </td>
    </tr>
<?php endforeach; endif; ?></tbody></table></div>
  <?php mcc_pagination($total, $offset, count($records), $page, $totalPages, $filters, 'admin/manager/liquidated-damages.php', $perPage); ?>
</section>

<div class="mcc-modal" data-mcc-modal="ld" hidden><form class="mcc-modal__panel" data-mcc-form data-endpoint="api/manager/liquidated-damage-save.php"><div class="mcc-modal__header"><div><span class="sa-panel-label">LD record</span><h2 data-mcc-title>New LD</h2></div><button class="btn btn--icon btn--ghost" type="button" data-mcc-close><i class="fa-solid fa-xmark"></i></button></div><div class="mcc-modal__body"><input type="hidden" name="id" data-field="id"><section class="mcc-form-grid">
  <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" data-field="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status" data-field="status"><?php foreach (ManagerContractControl::LD_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Rate per day</span><input class="form-input" type="number" min="0" step="0.01" name="rate_per_day" data-field="rate_per_day" data-ld-rate required></label>
  <label class="form-field"><span class="form-label">Days overdue</span><input class="form-input" type="number" min="0" name="days_overdue" data-field="days_overdue" data-ld-days required></label>
  <label class="form-field"><span class="form-label">Applied IPC</span><select class="form-select" name="applied_to_ipc_id" data-field="applied_to_ipc_id"><option value="">Not applied</option><?php foreach ($ipcs as $ipc): ?><option value="<?= (int)$ipc['id'] ?>"><?= Security::e($ipc['ipc_number']) ?> / <?= Security::e(status_label($ipc['status'])) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Calculated total</span><input class="form-input" data-ld-total disabled></label>
  <label class="form-field form-field--wide"><span class="form-label">Notes</span><textarea class="form-textarea" name="notes" data-field="notes" rows="4"></textarea></label>
</section><p class="mcc-form-status" data-mcc-status-text></p></div><div class="mcc-modal__footer"><button class="btn btn--outline" type="button" data-mcc-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save LD</button></div></form></div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
