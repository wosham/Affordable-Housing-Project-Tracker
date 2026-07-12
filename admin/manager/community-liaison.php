<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
require_once __DIR__ . '/../../app/partials/admin/manager-site-record-helpers.php';
Guard::exactRole('manager');

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerSiteRecord::projects($userId, $role);
$defaultProjectId = 0;
foreach ($projects as $project) {
    if ((int)($project['community_count'] ?? 0) > 0) {
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
    'engagement_type' => Security::cleanString((string)($_GET['engagement_type'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'followup' => Security::cleanString((string)($_GET['followup'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerSiteRecord::countCommunity($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = array_map([ManagerSiteRecord::class, 'communityPayload'], ManagerSiteRecord::community($userId, $role, $filters, $perPage, $offset));
$summary = ManagerSiteRecord::communitySummary($userId, $role, $filters);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($records), $total);

$pageTitle = 'Community Liaison';
$pageDescription = 'Track community engagements, issues, resolutions and follow-up commitments.';
$adminRole = 'manager';
$csrfForm = 'manager_site_records';
$contentClass = 'manager-site-records-page';
$componentCss = ['manager-site-records'];
$pageScripts = ['manager-site-records'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Community Liaison'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="msr-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Community records</span>
    <h2>Community Liaison</h2>
    <p>Track community engagements, issues and follow-ups for your assigned projects only.</p>
  </div>
  <div class="msr-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/site-meeting-minutes.php')) ?>"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Meetings</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/hs-incidents.php')) ?>"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> H&S Incidents</a>
    <button class="btn btn--primary" type="button" data-msr-open="community"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Engagement</button>
  </div>
</section>

<section class="msr-projects" aria-label="Assigned projects">
<?php if ($projects === []): ?>
  <article class="card msr-project is-empty"><strong>No assigned projects</strong><span>Community records will appear once projects are allocated.</span></article>
<?php else: foreach ($projects as $project): ?>
  <a class="card msr-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(msr_community_url(array_merge($filters, ['project_id' => (int)$project['id']]), 1)) ?>">
    <span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small></span>
    <em><?= Security::e(format_number($project['community_count'] ?? 0)) ?> records</em>
  </a>
<?php endforeach; endif; ?>
</section>

<section class="stat-grid stat-grid--4 msr-stats" aria-label="Community summary">
  <?php msr_stat('fa-handshake', $summary['total'], 'Total Engagements', 'Assigned portfolio'); ?>
  <?php msr_stat('fa-folder-open', $summary['open_items'], 'Open Issues', 'Needs response'); ?>
  <?php msr_stat('fa-calendar-check', $summary['due_soon'], 'Due Soon', 'Next seven days'); ?>
  <?php msr_stat('fa-clock', $summary['overdue'], 'Overdue', 'Past follow-up date'); ?>
  <?php msr_stat('fa-circle-check', $summary['resolved'], 'Resolved', 'Completed cases'); ?>
  <?php msr_stat('fa-calendar-days', $summary['this_month'], 'This Month', 'Recent engagements'); ?>
</section>

<section class="card msr-card">
  <div class="card__header">
    <div><h2 class="card__title">Community Register</h2><p class="card__subtitle">Log engagements, issue resolution and follow-up commitments.</p></div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="filter-bar msr-filter" method="get" action="<?= Security::e(Url::to('admin/manager/community-liaison.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Representative, issue or resolution..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="engagement_type">Type</label><select class="form-select" id="engagement_type" name="engagement_type"><option value="">All types</option><?php foreach (ManagerSiteRecord::COMMUNITY_TYPES as $type): ?><option value="<?= Security::e($type) ?>" <?= (($filters['engagement_type'] ?? '') === $type) ? 'selected' : '' ?>><?= Security::e(status_label($type)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerSiteRecord::COMMUNITY_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="followup">Follow-up</label><select class="form-select" id="followup" name="followup"><option value="">Any</option><option value="due" <?= (($filters['followup'] ?? '') === 'due') ? 'selected' : '' ?>>Due soon</option><option value="overdue" <?= (($filters['followup'] ?? '') === 'overdue') ? 'selected' : '' ?>>Overdue</option></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/community-liaison.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table msr-table">
      <thead><tr><th>Engagement</th><th>Project</th><th>Issue</th><th>Resolution</th><th>Status</th><th>Recorded</th><th>Manage</th></tr></thead>
      <tbody>
<?php if ($records === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-handshake" aria-hidden="true"></i></span><strong class="empty-state__title">No community records found</strong><span class="empty-state__text">Create a record or adjust your filters.</span></div></td></tr>
<?php else: foreach ($records as $record): ?>
        <tr>
          <td><strong><?= Security::e($record['date_label']) ?> / <?= Security::e($record['type_label']) ?></strong><small><?= Security::e($record['community_rep'] ?: 'No representative listed') ?></small></td>
          <td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['constituency_name'] ?: 'Assigned site') ?></small></td>
          <td><strong><?= Security::e(safe_truncate($record['issues_raised'] ?: 'No issue recorded', 95)) ?></strong></td>
          <td><strong><?= Security::e(safe_truncate($record['resolution'] ?: 'No resolution added', 80)) ?></strong><small>Follow-up: <?= Security::e($record['follow_up_label'] ?: '-') ?></small></td>
          <td><span class="msr-pill msr-pill--<?= Security::e($record['status']) ?>"><?= Security::e($record['status_label']) ?></span></td>
          <td><strong><?= Security::e($record['recorded_by_name']) ?></strong><small><?= Security::e(format_datetime($record['created_at'] ?? null)) ?></small></td>
          <td class="msr-actions manager-table-actions">
            <button class="btn btn--icon btn--primary" type="button" data-msr-open="community"<?= msr_data_attrs([
                'id' => $record['id'],
                'project_id' => $record['project_id'],
                'log_date' => $record['log_date'] ?? '',
                'engagement_type' => $record['engagement_type'] ?? '',
                'status' => $record['status'] ?? 'open',
                'follow_up_date' => $record['follow_up_date'] ?? '',
                'community_rep' => $record['community_rep'] ?? '',
                'issues_raised' => $record['issues_raised'] ?? '',
                'resolution' => $record['resolution'] ?? '',
            ]) ?> title="Edit community record" aria-label="Edit community record"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            <button class="btn btn--icon btn--outline" type="button" data-msr-status data-endpoint="api/manager/community-liaison-status.php" data-id="<?= (int)$record['id'] ?>" data-status="resolved" title="Mark resolved" aria-label="Mark resolved"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
          </td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php msr_pagination($showingFrom, $showingTo, $total, $page, $totalPages, msr_community_url($filters, max(1, $page - 1)), msr_community_url($filters, min($totalPages, $page + 1)), $perPage, 'community records'); ?>
</section>

<div class="msr-modal" data-msr-modal="community" hidden>
  <form class="msr-modal__panel" data-msr-form data-endpoint="api/manager/community-liaison-save.php">
    <div class="msr-modal__header"><div><span class="sa-panel-label">Community record</span><h2 data-msr-title>New community engagement</h2></div><button class="btn btn--icon btn--ghost" type="button" data-msr-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
    <div class="msr-modal__body">
      <input type="hidden" name="id" data-field="id">
      <section class="msr-form-grid">
        <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" data-field="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Log date</span><input class="form-input" type="date" name="log_date" data-field="log_date" required></label>
        <label class="form-field"><span class="form-label">Engagement type</span><select class="form-select" name="engagement_type" data-field="engagement_type"><?php foreach (ManagerSiteRecord::COMMUNITY_TYPES as $type): ?><option value="<?= Security::e($type) ?>"><?= Security::e(status_label($type)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status" data-field="status"><?php foreach (ManagerSiteRecord::COMMUNITY_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Follow-up date</span><input class="form-input" type="date" name="follow_up_date" data-field="follow_up_date"></label>
        <label class="form-field"><span class="form-label">Community representative</span><input class="form-input" name="community_rep" data-field="community_rep" maxlength="150"></label>
        <label class="form-field form-field--wide"><span class="form-label">Issues raised</span><textarea class="form-textarea" name="issues_raised" data-field="issues_raised" rows="4"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Resolution</span><textarea class="form-textarea" name="resolution" data-field="resolution" rows="4"></textarea></label>
      </section>
      <p class="msr-form-status" data-msr-status-text></p>
    </div>
    <div class="msr-modal__footer"><button class="btn btn--outline" type="button" data-msr-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Engagement</button></div>
  </form>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';


function msr_community_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);
    return Url::to('admin/manager/community-liaison.php' . ($query ? '?' . http_build_query($query) : ''));
}
