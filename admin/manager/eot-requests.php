<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerContractControl::projects($userId, $role);
$defaultProjectId = mcc_default_project($projects, 'eot_count');
$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'manager_recommendation' => Security::cleanString((string)($_GET['manager_recommendation'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerContractControl::countEots($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = array_map([ManagerContractControl::class, 'eotPayload'], ManagerContractControl::eots($userId, $role, $filters, $perPage, $offset));
$summary = ManagerContractControl::eotSummary($userId, $role, $filters);

$pageTitle = 'EOT Requests';
$pageDescription = 'Review extension-of-time requests, record recommendations and forward decisions for final action.';
$adminRole = 'manager';
$csrfForm = 'manager_contract_controls';
$contentClass = 'manager-contract-controls-page';
$componentCss = ['manager-contract-controls'];
$pageScripts = ['manager-contract-controls'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'EOT Requests'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="mcc-hero card">
  <div><span class="sa-panel-label"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Contract controls</span><h2>EOT Requests</h2><p>Review extension-of-time requests, record recommendations and forward decisions for final action.</p></div>
  <div class="mcc-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/liquidated-damages.php')) ?>"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> LD Tracker</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/subcontractors.php')) ?>"><i class="fa-solid fa-people-carry-box" aria-hidden="true"></i> Subcontractors</a>
  </div>
</section>

<?php mcc_project_strip($projects, $filters, 'eot_count', 'EOTs', 'admin/manager/eot-requests.php'); ?>

<section class="stat-grid stat-grid--4 mcc-stats">
  <?php mcc_stat('fa-file-signature', $summary['total'], 'Total EOTs', 'Assigned portfolio'); ?>
  <?php mcc_stat('fa-hourglass-half', $summary['manager_pending'], 'Pending Review', 'Manager action'); ?>
  <?php mcc_stat('fa-thumbs-up', $summary['recommended'], 'Recommended', 'Ready for final action'); ?>
  <?php mcc_stat('fa-circle-question', $summary['clarification'], 'Clarification', 'Sent back'); ?>
  <?php mcc_stat('fa-circle-check', $summary['granted'], 'Granted', 'Final approval'); ?>
  <?php mcc_stat('fa-circle-xmark', $summary['rejected'], 'Rejected', 'Final rejection'); ?>
  <?php mcc_stat('fa-calendar-plus', $summary['requested_days'], 'Days Requested', 'Contract exposure'); ?>
  <?php mcc_stat('fa-calendar-check', $summary['granted_days'], 'Days Granted', 'Approved relief'); ?>
</section>

<section class="card mcc-card">
  <div class="card__header"><div><h2 class="card__title">EOT Review Queue</h2><p class="card__subtitle">Filter requests and capture manager recommendations.</p></div><span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span></div>
  <form class="filter-bar mcc-filter" method="get" action="<?= Security::e(Url::to('admin/manager/eot-requests.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="EOT, project or reason..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Final Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerContractControl::EOT_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="manager_recommendation">Recommendation</label><select class="form-select" id="manager_recommendation" name="manager_recommendation"><option value="">All recommendations</option><?php foreach (ManagerContractControl::EOT_RECOMMENDATIONS as $rec): ?><option value="<?= Security::e($rec) ?>" <?= (($filters['manager_recommendation'] ?? '') === $rec) ? 'selected' : '' ?>><?= Security::e(status_label($rec)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/eot-requests.php')) ?>">Reset</a></div>
  </form>
  <div class="table-wrap"><table class="data-table mcc-table"><thead><tr><th>EOT</th><th>Project</th><th>Days</th><th>Reason</th><th>Recommendation</th><th>Final Status</th><th>Manage</th></tr></thead><tbody>
<?php if ($records === []): ?>
    <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><strong class="empty-state__title">No EOT requests found</strong><span class="empty-state__text">Adjust your filters or wait for contractor submissions.</span></div></td></tr>
<?php else: foreach ($records as $record): ?>
    <tr><td><strong>EOT #<?= Security::e($record['eot_number']) ?></strong><small><?= Security::e($record['created_label']) ?> by <?= Security::e($record['submitted_by_name']) ?></small></td><td><strong><?= Security::e($record['project_name']) ?></strong><small>Delivery: <?= Security::e(format_date($record['est_delivery'] ?? null) ?: '-') ?></small></td><td><strong><?= Security::e(format_number($record['days_requested'])) ?> requested</strong><small><?= Security::e(format_number($record['manager_recommended_days'] ?? 0)) ?> recommended</small></td><td><?= Security::e(safe_truncate($record['reason'], 100)) ?></td><td><span class="mcc-pill mcc-pill--<?= Security::e($record['manager_recommendation']) ?>"><?= Security::e($record['recommendation_label']) ?></span><small><?= Security::e(safe_truncate($record['manager_review_note'] ?? 'No review note', 70)) ?></small></td><td><span class="mcc-pill mcc-pill--<?= Security::e($record['status']) ?>"><?= Security::e($record['status_label']) ?></span></td><td><button class="btn btn--icon btn--primary" type="button" data-mcc-open="eot" data-record="<?= Security::e(json_encode($record, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>" title="Review EOT"><i class="fa-solid fa-pen" aria-hidden="true"></i></button></td></tr>
<?php endforeach; endif; ?>
  </tbody></table></div>
  <?php mcc_pagination($total, $offset, count($records), $page, $totalPages, $filters, 'admin/manager/eot-requests.php'); ?>
</section>

<div class="mcc-modal" data-mcc-modal="eot" hidden>
  <form class="mcc-modal__panel" data-mcc-form data-endpoint="api/manager/eot-review.php">
    <div class="mcc-modal__header"><div><span class="sa-panel-label">EOT review</span><h2 data-mcc-title>Review EOT</h2></div><button class="btn btn--icon btn--ghost" type="button" data-mcc-close><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mcc-modal__body"><input type="hidden" name="id" data-field="id"><section class="mcc-form-grid">
      <label class="form-field"><span class="form-label">Recommendation</span><select class="form-select" name="manager_recommendation" data-field="manager_recommendation"><?php foreach (ManagerContractControl::EOT_RECOMMENDATIONS as $rec): ?><option value="<?= Security::e($rec) ?>"><?= Security::e(status_label($rec)) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Recommended days</span><input class="form-input" type="number" min="0" name="manager_recommended_days" data-field="manager_recommended_days"></label>
      <label class="form-field"><span class="form-label">Delay category</span><input class="form-input" name="delay_category" data-field="delay_category" maxlength="80"></label>
      <label class="form-field form-field--wide"><span class="form-label">Review note</span><textarea class="form-textarea" name="manager_review_note" data-field="manager_review_note" rows="4"></textarea></label>
      <label class="form-field form-field--wide"><span class="form-label">Impact summary</span><textarea class="form-textarea" name="impact_summary" data-field="impact_summary" rows="3"></textarea></label>
    </section><p class="mcc-form-status" data-mcc-status-text></p></div>
    <div class="mcc-modal__footer"><button class="btn btn--outline" type="button" data-mcc-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Review</button></div>
  </form>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function mcc_default_project(array $projects, string $countKey): int { foreach ($projects as $project) { if ((int)($project[$countKey] ?? 0) > 0) return (int)$project['id']; } return $projects ? (int)$projects[0]['id'] : 0; }
function mcc_stat(string $icon, mixed $value, string $label, string $trend): void { ?><article class="stat-widget"><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></article><?php }
function mcc_project_strip(array $projects, array $filters, string $countKey, string $label, string $path): void { ?><section class="mcc-projects"><?php if ($projects === []): ?><article class="card mcc-project is-empty"><strong>No assigned projects</strong><span>Contract controls appear once projects are allocated.</span></article><?php else: foreach ($projects as $project): $query = array_filter(array_merge($filters, ['project_id' => (int)$project['id'], 'page' => 1]), static fn($v) => $v !== '' && $v !== null && $v !== 0); ?><a class="card mcc-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($query))) ?>"><span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small></span><em><?= Security::e(format_number($project[$countKey] ?? 0)) ?> <?= Security::e($label) ?></em></a><?php endforeach; endif; ?></section><?php }
function mcc_pagination(int $total, int $offset, int $count, int $page, int $totalPages, array $filters, string $path): void { if ($totalPages <= 1) return; $from = $total > 0 ? $offset + 1 : 0; $to = min($offset + $count, $total); $prev = array_filter(array_merge($filters, ['page' => max(1, $page - 1)]), static fn($v) => $v !== '' && $v !== null && $v !== 0); $next = array_filter(array_merge($filters, ['page' => min($totalPages, $page + 1)]), static fn($v) => $v !== '' && $v !== null && $v !== 0); ?><nav class="pagination"><p class="pagination__info">Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> records</p><div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($prev))) ?>"><i class="fa-solid fa-chevron-left"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($next))) ?>"><i class="fa-solid fa-chevron-right"></i></a></div></nav><?php }
