<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerSiteRecord::projects($userId, $role);
$defaultProjectId = 0;
foreach ($projects as $project) {
    if ((int)($project['incident_count'] ?? 0) > 0) {
        $defaultProjectId = (int)$project['id'];
        break;
    }
}
if ($defaultProjectId === 0 && $projects !== []) {
    $defaultProjectId = (int)$projects[0]['id'];
}

$filters = array_filter([
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? $defaultProjectId),
    'incident_type' => Security::cleanString((string)($_GET['incident_type'] ?? '')),
    'severity' => Security::cleanString((string)($_GET['severity'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'followup' => Security::cleanString((string)($_GET['followup'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerSiteRecord::countIncidents($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = array_map([ManagerSiteRecord::class, 'incidentPayload'], ManagerSiteRecord::incidents($userId, $role, $filters, $perPage, $offset));
$summary = ManagerSiteRecord::incidentSummary($userId, $role, $filters);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($records), $total);

$pageTitle = 'H&S Incidents';
$pageDescription = 'Track site incidents, corrective actions, severity and closure status.';
$adminRole = 'manager';
$csrfForm = 'manager_site_records';
$contentClass = 'manager-site-records-page';
$componentCss = ['manager-site-records'];
$pageScripts = ['manager-site-records'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'H&S Incidents'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="msr-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i> Safety records</span>
    <h2>H&S Incidents</h2>
    <p>Track site incidents, corrective actions, severity and closure status.</p>
  </div>
  <div class="msr-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/site-meeting-minutes.php')) ?>"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Meetings</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/community-liaison.php')) ?>"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Community</a>
    <button class="btn btn--primary" type="button" data-msr-open="incident"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Incident</button>
  </div>
</section>

<section class="msr-projects" aria-label="Assigned projects">
<?php if ($projects === []): ?>
  <article class="card msr-project is-empty"><strong>No assigned projects</strong><span>Incident records will appear once projects are allocated.</span></article>
<?php else: foreach ($projects as $project): ?>
  <a class="card msr-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(msr_incident_url(array_merge($filters, ['project_id' => (int)$project['id']]), 1)) ?>">
    <span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small></span>
    <em><?= Security::e(format_number($project['incident_count'] ?? 0)) ?> incidents</em>
  </a>
<?php endforeach; endif; ?>
</section>

<section class="stat-grid stat-grid--4 msr-stats" aria-label="Incident summary">
  <?php msr_stat('fa-triangle-exclamation', $summary['total'], 'Total Incidents', 'Assigned portfolio'); ?>
  <?php msr_stat('fa-folder-open', $summary['open_items'], 'Open', 'Needs follow-up'); ?>
  <?php msr_stat('fa-circle-exclamation', $summary['severe'], 'High/Critical', 'Priority cases'); ?>
  <?php msr_stat('fa-calendar-days', $summary['this_month'], 'This Month', 'Recent incidents'); ?>
  <?php msr_stat('fa-screwdriver-wrench', $summary['action_pending'], 'Action Pending', 'Corrective actions'); ?>
  <?php msr_stat('fa-circle-check', $summary['closed_items'], 'Resolved/Closed', 'Completed cases'); ?>
</section>

<section class="card msr-card">
  <div class="card__header">
    <div><h2 class="card__title">Incident Register</h2><p class="card__subtitle">Filter incidents, review severity and keep corrective actions moving.</p></div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="filter-bar msr-filter" method="get" action="<?= Security::e(Url::to('admin/manager/hs-incidents.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Description, person or action..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="incident_type">Type</label><select class="form-select" id="incident_type" name="incident_type"><option value="">All types</option><?php foreach (ManagerSiteRecord::INCIDENT_TYPES as $type): ?><option value="<?= Security::e($type) ?>" <?= (($filters['incident_type'] ?? '') === $type) ? 'selected' : '' ?>><?= Security::e(status_label($type)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="severity">Severity</label><select class="form-select" id="severity" name="severity"><option value="">All severities</option><?php foreach (ManagerSiteRecord::INCIDENT_SEVERITIES as $severity): ?><option value="<?= Security::e($severity) ?>" <?= (($filters['severity'] ?? '') === $severity) ? 'selected' : '' ?>><?= Security::e(status_label($severity)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerSiteRecord::INCIDENT_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="followup">Follow-up</label><select class="form-select" id="followup" name="followup"><option value="">Any</option><option value="due" <?= (($filters['followup'] ?? '') === 'due') ? 'selected' : '' ?>>Due soon</option><option value="overdue" <?= (($filters['followup'] ?? '') === 'overdue') ? 'selected' : '' ?>>Overdue</option></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/hs-incidents.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table msr-table">
      <thead><tr><th>Incident</th><th>Project</th><th>Severity</th><th>Corrective Action</th><th>Status</th><th>Reported</th><th>Manage</th></tr></thead>
      <tbody>
<?php if ($records === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i></span><strong class="empty-state__title">No H&S incidents found</strong><span class="empty-state__text">Create an incident or adjust your filters.</span></div></td></tr>
<?php else: foreach ($records as $record): ?>
        <tr>
          <td><strong><?= Security::e($record['date_label']) ?> / <?= Security::e($record['type_label']) ?></strong><small><?= Security::e(safe_truncate($record['description'], 95)) ?></small></td>
          <td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['constituency_name'] ?: 'Assigned site') ?></small></td>
          <td><span class="msr-pill msr-pill--<?= Security::e($record['severity']) ?>"><?= Security::e($record['severity_label']) ?></span><small><?= Security::e($record['persons_involved'] ?: 'No persons listed') ?></small></td>
          <td><strong><?= Security::e(safe_truncate($record['corrective_action'] ?: 'No corrective action added', 80)) ?></strong><small>Cause: <?= Security::e(safe_truncate($record['cause'] ?: 'Not recorded', 60)) ?></small></td>
          <td><span class="msr-pill msr-pill--<?= Security::e($record['status']) ?>"><?= Security::e($record['status_label']) ?></span><small>Follow-up: <?= Security::e($record['follow_up_label'] ?: '-') ?></small></td>
          <td><strong><?= Security::e($record['reported_by_name']) ?></strong><small><?= Security::e(format_datetime($record['created_at'] ?? null)) ?></small></td>
          <td class="msr-actions">
            <button class="btn btn--icon btn--primary" type="button" data-msr-open="incident" data-record="<?= Security::e(json_encode($record, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>" title="Edit incident" aria-label="Edit incident"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            <button class="btn btn--icon btn--outline" type="button" data-msr-status data-endpoint="api/manager/hs-incident-status.php" data-id="<?= (int)$record['id'] ?>" data-status="resolved" title="Mark resolved" aria-label="Mark resolved"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
          </td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Incident pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> incident records</p>
    <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(msr_incident_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(msr_incident_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
  </nav>
<?php endif; ?>
</section>

<div class="msr-modal" data-msr-modal="incident" hidden>
  <form class="msr-modal__panel" data-msr-form data-endpoint="api/manager/hs-incident-save.php">
    <div class="msr-modal__header"><div><span class="sa-panel-label">H&S record</span><h2 data-msr-title>New H&S incident</h2></div><button class="btn btn--icon btn--ghost" type="button" data-msr-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
    <div class="msr-modal__body">
      <input type="hidden" name="id" data-field="id">
      <section class="msr-form-grid">
        <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" data-field="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Incident date</span><input class="form-input" type="date" name="incident_date" data-field="incident_date" required></label>
        <label class="form-field"><span class="form-label">Type</span><select class="form-select" name="incident_type" data-field="incident_type"><?php foreach (ManagerSiteRecord::INCIDENT_TYPES as $type): ?><option value="<?= Security::e($type) ?>"><?= Security::e(status_label($type)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Severity</span><select class="form-select" name="severity" data-field="severity"><?php foreach (ManagerSiteRecord::INCIDENT_SEVERITIES as $severity): ?><option value="<?= Security::e($severity) ?>"><?= Security::e(status_label($severity)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status" data-field="status"><?php foreach (ManagerSiteRecord::INCIDENT_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Follow-up date</span><input class="form-input" type="date" name="follow_up_date" data-field="follow_up_date"></label>
        <label class="form-field form-field--wide"><span class="form-label">Description</span><textarea class="form-textarea" name="description" data-field="description" rows="4" required></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Persons involved</span><textarea class="form-textarea" name="persons_involved" data-field="persons_involved" rows="3"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Cause</span><textarea class="form-textarea" name="cause" data-field="cause" rows="3"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Corrective action</span><textarea class="form-textarea" name="corrective_action" data-field="corrective_action" rows="4"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Attachment path</span><input class="form-input" name="attachment_path" data-field="attachment_path" maxlength="255"></label>
      </section>
      <p class="msr-form-status" data-msr-status-text></p>
    </div>
    <div class="msr-modal__footer"><button class="btn btn--outline" type="button" data-msr-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Incident</button></div>
  </form>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function msr_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget"><span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span></article>
<?php
}

function msr_incident_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);
    return Url::to('admin/manager/hs-incidents.php' . ($query ? '?' . http_build_query($query) : ''));
}
