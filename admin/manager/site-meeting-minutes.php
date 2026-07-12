<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
require_once __DIR__ . '/../../app/partials/admin/manager-site-record-helpers.php';
Guard::exactRole('manager');

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projects = ManagerSiteRecord::projects($userId, $role);
$defaultProjectId = 0;
foreach ($projects as $project) {
    if ((int)($project['meeting_count'] ?? 0) > 0) {
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
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'action_status' => Security::cleanString((string)($_GET['action_status'] ?? '')),
    'document' => Security::cleanString((string)($_GET['document'] ?? '')),
    'date_from' => Security::cleanString((string)($_GET['date_from'] ?? '')),
    'date_to' => Security::cleanString((string)($_GET['date_to'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 15;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ManagerSiteRecord::countMeetings($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = array_map([ManagerSiteRecord::class, 'meetingPayload'], ManagerSiteRecord::meetings($userId, $role, $filters, $perPage, $offset));
$summary = ManagerSiteRecord::meetingSummary($userId, $role, $filters);
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($records), $total);

$pageTitle = 'Site Meeting Minutes';
$pageDescription = 'Record meetings, actions, attendees and follow-up status across assigned sites.';
$adminRole = 'manager';
$csrfForm = 'manager_site_records';
$contentClass = 'manager-site-records-page';
$componentCss = ['manager-site-records'];
$pageScripts = ['manager-site-records'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Site Meeting Minutes'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="msr-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Site operations</span>
    <h2>Site Meeting Minutes</h2>
    <p>Record meetings, actions, attendees and follow-up status for your assigned projects only.</p>
  </div>
  <div class="msr-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/hs-incidents.php')) ?>"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> H&S Incidents</a>
    <button class="btn btn--primary" type="button" data-msr-open="meeting"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Minutes</button>
  </div>
</section>

<section class="msr-projects" aria-label="Assigned projects">
<?php if ($projects === []): ?>
  <article class="card msr-project is-empty"><strong>No assigned projects</strong><span>Site records will appear once projects are allocated.</span></article>
<?php else: foreach ($projects as $project): ?>
  <a class="card msr-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(msr_meeting_url(array_merge($filters, ['project_id' => (int)$project['id']]), 1)) ?>">
    <span><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small></span>
    <em><?= Security::e(format_number($project['meeting_count'] ?? 0)) ?> meetings</em>
  </a>
<?php endforeach; endif; ?>
</section>

<section class="stat-grid stat-grid--4 msr-stats" aria-label="Meeting summary">
  <?php msr_stat('fa-clipboard-list', $summary['total'], 'Total Meetings', 'Assigned portfolio'); ?>
  <?php msr_stat('fa-calendar-days', $summary['this_month'], 'This Month', 'Recent meetings'); ?>
  <?php msr_stat('fa-list-check', $summary['open_actions'], 'Open Actions', 'Needs follow-up'); ?>
  <?php msr_stat('fa-clock', $summary['overdue_actions'], 'Overdue Actions', 'Past due'); ?>
  <?php msr_stat('fa-file-lines', $summary['with_document'], 'With Documents', 'Attached minutes'); ?>
  <?php msr_stat('fa-circle-check', $summary['reviewed'], 'Reviewed', 'Manager confirmed'); ?>
</section>

<section class="card msr-card">
  <div class="card__header">
    <div><h2 class="card__title">Meeting Registry</h2><p class="card__subtitle">Filter site meetings, inspect actions and update review status.</p></div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> records</span>
  </div>

  <form class="filter-bar msr-filter" method="get" action="<?= Security::e(Url::to('admin/manager/site-meeting-minutes.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Venue, agenda or minutes..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All assigned projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ManagerSiteRecord::MEETING_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="action_status">Actions</label><select class="form-select" id="action_status" name="action_status"><option value="">Any action status</option><?php foreach (ManagerSiteRecord::ACTION_STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['action_status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="document">Document</label><select class="form-select" id="document" name="document"><option value="">Any</option><option value="yes" <?= (($filters['document'] ?? '') === 'yes') ? 'selected' : '' ?>>Attached</option><option value="no" <?= (($filters['document'] ?? '') === 'no') ? 'selected' : '' ?>>Missing</option></select></div>
    <div class="filter-group"><label class="filter-label" for="date_from">From</label><input class="form-input" type="date" id="date_from" name="date_from" value="<?= Security::e($filters['date_from'] ?? '') ?>"></div>
    <div class="filter-group"><label class="filter-label" for="date_to">To</label><input class="form-input" type="date" id="date_to" name="date_to" value="<?= Security::e($filters['date_to'] ?? '') ?>"></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/site-meeting-minutes.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table msr-table">
      <thead><tr><th>Meeting</th><th>Project</th><th>Attendees</th><th>Actions</th><th>Status</th><th>Recorded</th><th>Manage</th></tr></thead>
      <tbody>
<?php if ($records === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span><strong class="empty-state__title">No meeting minutes found</strong><span class="empty-state__text">Create minutes or adjust your filters.</span></div></td></tr>
<?php else: foreach ($records as $record): ?>
        <tr>
          <td><strong><?= Security::e($record['date_label']) ?><?= $record['venue'] ? ' / ' . Security::e($record['venue']) : '' ?></strong><small><?= Security::e(safe_truncate($record['agenda'] ?: $record['minutes_text'] ?: 'No agenda added', 95)) ?></small></td>
          <td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['constituency_name'] ?: 'Assigned site') ?></small></td>
          <td><strong><?= Security::e(format_number($record['attendees_count'])) ?></strong><small><?= Security::e(safe_truncate($record['attendees_text'] ?: 'No attendees listed', 65)) ?></small></td>
          <td><span class="msr-pill msr-pill--<?= Security::e($record['action_status']) ?>"><?= Security::e($record['action_status_label']) ?></span><small><?= Security::e(format_number($record['action_count'])) ?> action item(s)</small></td>
          <td><span class="msr-pill msr-pill--<?= Security::e($record['status']) ?>"><?= Security::e($record['status_label']) ?></span><?php if ($record['document_path']): ?><small><i class="fa-solid fa-paperclip" aria-hidden="true"></i> Document attached</small><?php endif; ?></td>
          <td><strong><?= Security::e($record['recorded_by_name']) ?></strong><small><?= Security::e(format_datetime($record['created_at'] ?? null)) ?></small></td>
          <td class="msr-actions manager-table-actions">
            <button class="btn btn--icon btn--primary" type="button" data-msr-open="meeting"<?= msr_data_attrs([
                'id' => $record['id'],
                'project_id' => $record['project_id'],
                'meeting_date' => $record['meeting_date'] ?? '',
                'venue' => $record['venue'] ?? '',
                'status' => $record['status'] ?? 'recorded',
                'action_status' => $record['action_status'] ?? 'none',
                'document_path' => $record['document_path'] ?? '',
                'attendees_text' => $record['attendees_text'] ?? '',
                'agenda' => $record['agenda'] ?? '',
                'minutes_text' => $record['minutes_text'] ?? '',
                'action_items_text' => $record['action_items_text'] ?? '',
            ]) ?> title="Edit meeting" aria-label="Edit meeting"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            <button class="btn btn--icon btn--outline" type="button" data-msr-status data-endpoint="api/manager/site-meeting-status.php" data-id="<?= (int)$record['id'] ?>" data-status="reviewed" title="Mark reviewed" aria-label="Mark reviewed"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
          </td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php msr_pagination($showingFrom, $showingTo, $total, $page, $totalPages, msr_meeting_url($filters, max(1, $page - 1)), msr_meeting_url($filters, min($totalPages, $page + 1)), $perPage, 'meeting records'); ?>
</section>

<div class="msr-modal" data-msr-modal="meeting" hidden>
  <form class="msr-modal__panel" data-msr-form data-endpoint="api/manager/site-meeting-save.php">
    <div class="msr-modal__header"><div><span class="sa-panel-label">Meeting record</span><h2 data-msr-title>New meeting minutes</h2></div><button class="btn btn--icon btn--ghost" type="button" data-msr-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
    <div class="msr-modal__body">
      <input type="hidden" name="id" data-field="id">
      <section class="msr-form-grid">
        <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" data-field="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Meeting date</span><input class="form-input" type="date" name="meeting_date" data-field="meeting_date" required></label>
        <label class="form-field"><span class="form-label">Venue</span><input class="form-input" name="venue" data-field="venue" maxlength="200"></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status" data-field="status"><?php foreach (ManagerSiteRecord::MEETING_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Action status</span><select class="form-select" name="action_status" data-field="action_status"><?php foreach (ManagerSiteRecord::ACTION_STATUSES as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Document path</span><input class="form-input" name="document_path" data-field="document_path" maxlength="255"></label>
        <label class="form-field form-field--wide"><span class="form-label">Attendees</span><textarea class="form-textarea" name="attendees" data-field="attendees_text" rows="3" placeholder="One attendee per line"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Agenda</span><textarea class="form-textarea" name="agenda" data-field="agenda" rows="3"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Minutes</span><textarea class="form-textarea" name="minutes_text" data-field="minutes_text" rows="5"></textarea></label>
        <label class="form-field form-field--wide"><span class="form-label">Action items</span><textarea class="form-textarea" name="action_items" data-field="action_items_text" rows="4" placeholder="One action item per line"></textarea></label>
      </section>
      <p class="msr-form-status" data-msr-status-text></p>
    </div>
    <div class="msr-modal__footer"><button class="btn btn--outline" type="button" data-msr-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Minutes</button></div>
  </form>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function msr_meeting_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);
    return Url::to('admin/manager/site-meeting-minutes.php' . ($query ? '?' . http_build_query($query) : ''));
}
