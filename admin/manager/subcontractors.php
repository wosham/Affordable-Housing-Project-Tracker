<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerContractControl::projects($userId, $role);
$defaultProjectId = mcc_default_project($projects, 'subcontractor_count');
$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'compliance_status' => Security::cleanString((string)($_GET['compliance_status'] ?? '')),
    'risk_status' => Security::cleanString((string)($_GET['risk_status'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerContractControl::countSubcontractors($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = array_map([ManagerContractControl::class, 'subPayload'], ManagerContractControl::subcontractors($userId, $role, $filters, $perPage, $offset));
$summary = ManagerContractControl::subcontractorSummary($userId, $role, $filters);

$pageTitle = 'Subcontractors';
$pageDescription = 'Monitor subcontractor scope, value, compliance, performance and risk status.';
$adminRole = 'manager';
$csrfForm = 'manager_contract_controls';
$contentClass = 'manager-contract-controls-page';
$componentCss = ['manager-contract-controls'];
$pageScripts = ['manager-contract-controls'];
$breadcrumbs = [['label' => 'Portal', 'url' => Url::to('admin/index.php')], ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')], ['label' => 'Subcontractors']];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="mcc-hero card">
  <div><span class="sa-panel-label"><i class="fa-solid fa-people-carry-box" aria-hidden="true"></i> Contract controls</span><h2>Subcontractors</h2><p>Monitor subcontractor scope, value, compliance, performance and risk status.</p></div>
  <div class="mcc-hero__actions"><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/eot-requests.php')) ?>"><i class="fa-solid fa-clock-rotate-left"></i> EOT Requests</a><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/liquidated-damages.php')) ?>"><i class="fa-solid fa-scale-balanced"></i> LD Tracker</a><button class="btn btn--primary" type="button" data-mcc-open="subcontractor"><i class="fa-solid fa-plus"></i> New Subcontractor</button></div>
</section>

<?php mcc_project_strip($projects, $filters, 'subcontractor_count', 'subs', 'admin/manager/subcontractors.php'); ?>

<section class="stat-grid stat-grid--4 mcc-stats">
  <?php mcc_stat('fa-people-carry-box', $summary['total'], 'Subcontractors', 'Assigned portfolio'); ?>
  <?php mcc_stat('fa-circle-check', $summary['active'], 'Active', 'Working on site'); ?>
  <?php mcc_stat('fa-hourglass-half', $summary['pending'], 'Pending Review', 'Needs confirmation'); ?>
  <?php mcc_stat('fa-ban', $summary['stopped'], 'Suspended/Terminated', 'Stopped works'); ?>
  <?php mcc_stat('fa-money-bill-wave', format_money($summary['contract_value']), 'Subcontract Value', 'Recorded value'); ?>
  <?php mcc_stat('fa-clipboard-check', $summary['compliance_flags'], 'Compliance Flags', 'Needs attention'); ?>
  <?php mcc_stat('fa-triangle-exclamation', $summary['high_risk'], 'High Risk', 'Priority follow-up'); ?>
</section>

<section class="card mcc-card">
  <div class="card__header"><div><h2 class="card__title">Subcontractor Register</h2><p class="card__subtitle">Track scope, contacts, compliance and performance notes.</p></div><span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span></div>
  <form class="filter-bar mcc-filter" method="get" action="<?= Security::e(Url::to('admin/manager/subcontractors.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Company, scope or contact..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerContractControl::SUB_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="compliance_status">Compliance</label><select class="form-select" id="compliance_status" name="compliance_status"><option value="">All compliance</option><?php foreach (ManagerContractControl::COMPLIANCE_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['compliance_status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="risk_status">Risk</label><select class="form-select" id="risk_status" name="risk_status"><option value="">All risk</option><?php foreach (ManagerContractControl::RISK_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['risk_status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/subcontractors.php')) ?>">Reset</a></div>
  </form>
  <div class="table-wrap"><table class="data-table mcc-table"><thead><tr><th>Company</th><th>Project</th><th>Scope</th><th>Value</th><th>Compliance</th><th>Status</th><th>Manage</th></tr></thead><tbody>
<?php if ($records === []): ?><tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-people-carry-box"></i></span><strong class="empty-state__title">No subcontractors found</strong><span class="empty-state__text">Create a record or adjust filters.</span></div></td></tr><?php else: foreach ($records as $record): ?>
    <tr><td><strong><?= Security::e($record['company']) ?></strong><small><?= Security::e($record['contact_person'] ?: 'No contact person') ?><?= $record['phone'] ? ' / ' . Security::e($record['phone']) : '' ?></small></td><td><strong><?= Security::e($record['project_name']) ?></strong></td><td><?= Security::e(safe_truncate($record['scope_of_work'] ?: 'No scope recorded', 95)) ?></td><td class="is-num"><strong><?= Security::e(format_money($record['contract_value'])) ?></strong></td><td><span class="mcc-pill mcc-pill--<?= Security::e($record['compliance_status']) ?>"><?= Security::e($record['compliance_label']) ?></span><small><?= Security::e($record['risk_label']) ?> risk</small></td><td><span class="mcc-pill mcc-pill--<?= Security::e($record['status']) ?>"><?= Security::e($record['status_label']) ?></span><small><?= Security::e(safe_truncate($record['performance_note'] ?: 'No note', 70)) ?></small></td><td class="mcc-actions"><button class="btn btn--icon btn--primary" type="button" data-mcc-open="subcontractor" data-record="<?= Security::e(json_encode($record, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"><i class="fa-solid fa-pen"></i></button><button class="btn btn--icon btn--outline" type="button" data-mcc-status data-endpoint="api/manager/subcontractor-status.php" data-id="<?= (int)$record['id'] ?>" data-status="suspended"><i class="fa-solid fa-pause"></i></button></td></tr>
<?php endforeach; endif; ?></tbody></table></div>
  <?php mcc_pagination($total, $offset, count($records), $page, $totalPages, $filters, 'admin/manager/subcontractors.php'); ?>
</section>

<div class="mcc-modal" data-mcc-modal="subcontractor" hidden><form class="mcc-modal__panel" data-mcc-form data-endpoint="api/manager/subcontractor-save.php"><div class="mcc-modal__header"><div><span class="sa-panel-label">Subcontractor</span><h2 data-mcc-title>New subcontractor</h2></div><button class="btn btn--icon btn--ghost" type="button" data-mcc-close><i class="fa-solid fa-xmark"></i></button></div><div class="mcc-modal__body"><input type="hidden" name="id" data-field="id"><section class="mcc-form-grid">
  <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" data-field="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Company</span><input class="form-input" name="company" data-field="company" required maxlength="200"></label>
  <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status" data-field="status"><?php foreach (ManagerContractControl::SUB_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Contract value</span><input class="form-input" type="number" min="0" step="0.01" name="contract_value" data-field="contract_value"></label>
  <label class="form-field"><span class="form-label">Contact person</span><input class="form-input" name="contact_person" data-field="contact_person" maxlength="150"></label>
  <label class="form-field"><span class="form-label">Phone</span><input class="form-input" name="phone" data-field="phone" maxlength="60"></label>
  <label class="form-field"><span class="form-label">Email</span><input class="form-input" type="email" name="email" data-field="email" maxlength="180"></label>
  <label class="form-field"><span class="form-label">Compliance</span><select class="form-select" name="compliance_status" data-field="compliance_status"><?php foreach (ManagerContractControl::COMPLIANCE_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
  <label class="form-field"><span class="form-label">Risk</span><select class="form-select" name="risk_status" data-field="risk_status"><?php foreach (ManagerContractControl::RISK_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
  <label class="form-field form-field--wide"><span class="form-label">Scope of work</span><textarea class="form-textarea" name="scope_of_work" data-field="scope_of_work" rows="3"></textarea></label>
  <label class="form-field form-field--wide"><span class="form-label">Performance note</span><textarea class="form-textarea" name="performance_note" data-field="performance_note" rows="4"></textarea></label>
</section><p class="mcc-form-status" data-mcc-status-text></p></div><div class="mcc-modal__footer"><button class="btn btn--outline" type="button" data-mcc-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Subcontractor</button></div></form></div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
<?php
function mcc_default_project(array $projects, string $countKey): int { foreach ($projects as $project) { if ((int)($project[$countKey] ?? 0) > 0) return (int)$project['id']; } return $projects ? (int)$projects[0]['id'] : 0; }
function mcc_stat(string $icon, mixed $value, string $label, string $trend): void { ?><article class="stat-widget"><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></article><?php }
function mcc_project_strip(array $projects, array $filters, string $countKey, string $label, string $path): void { ?><section class="mcc-projects"><?php if ($projects === []): ?><article class="card mcc-project is-empty"><strong>No assigned projects</strong><span>Contract controls appear once projects are allocated.</span></article><?php else: foreach ($projects as $project): $query = array_filter(array_merge($filters, ['project_id' => (int)$project['id'], 'page' => 1]), static fn($v) => $v !== '' && $v !== null && $v !== 0); ?><a class="card mcc-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($query))) ?>"><span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small></span><em><?= Security::e(format_number($project[$countKey] ?? 0)) ?> <?= Security::e($label) ?></em></a><?php endforeach; endif; ?></section><?php }
function mcc_pagination(int $total, int $offset, int $count, int $page, int $totalPages, array $filters, string $path): void { if ($totalPages <= 1) return; $from = $total > 0 ? $offset + 1 : 0; $to = min($offset + $count, $total); $prev = array_filter(array_merge($filters, ['page' => max(1, $page - 1)]), static fn($v) => $v !== '' && $v !== null && $v !== 0); $next = array_filter(array_merge($filters, ['page' => min($totalPages, $page + 1)]), static fn($v) => $v !== '' && $v !== null && $v !== 0); ?><nav class="pagination"><p class="pagination__info">Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> records</p><div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($prev))) ?>"><i class="fa-solid fa-chevron-left"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($next))) ?>"><i class="fa-solid fa-chevron-right"></i></a></div></nav><?php }
