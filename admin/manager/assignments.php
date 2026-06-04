<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('manager'));

$userId = (int)Auth::id();
$role = (string)Auth::role();
$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$perPage = 20;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ProjectAssignment::countForManager($userId, $role, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$assignments = array_map([ProjectAssignment::class, 'payload'], ProjectAssignment::listForManager($userId, $role, $filters, $perPage, $offset));
$summary = ProjectAssignment::summaryForManager($userId, $role);
$projects = ProjectAssignment::managerProjects($userId, $role);
$constituencies = Database::fetchAll('SELECT id, name FROM constituencies ORDER BY name ASC');
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($assignments), $total);

$pageTitle = 'Assignments';
$pageDescription = 'Assign clerks, interns, consultants and contractors to project sites.';
$adminRole = 'manager';
$csrfForm = 'assignments';
$contentClass = 'manager-assignments-page';
$componentCss = ['assignments'];
$pageScripts = ['assignments'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Project Manager', 'url' => Url::to('admin/manager/dashboard.php')],
    ['label' => 'Assignments'],
];
$exportUrl = Url::to('api/assignments/export.php?' . http_build_query($filters));

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="assignments-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Project staffing</span>
    <h2>Assignments</h2>
    <p>Assign site staff to projects, control primary coverage and keep attendance, messaging and reporting connected.</p>
  </div>
  <div class="assignments-hero__actions">
    <button class="btn btn--outline" type="button" data-assignment-bulk><i class="fa-solid fa-users-gear" aria-hidden="true"></i> Bulk Assign</button>
    <a class="btn btn--outline" href="<?= Security::e($exportUrl) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV</a>
    <button class="btn btn--primary" type="button" data-assignment-create><i class="fa-solid fa-plus" aria-hidden="true"></i> New Assignment</button>
  </div>
</section>

<section class="stat-grid stat-grid--4 assignments-stats" aria-label="Assignment summary">
  <?php assignment_stat('fa-building', $summary['projects'], 'Managed Projects', 'Available sites'); ?>
  <?php assignment_stat('fa-user-check', $summary['active_assignments'], 'Active Staff', 'Current assignments'); ?>
  <?php assignment_stat('fa-clipboard-user', $summary['clerks'], 'Clerks', 'Site control'); ?>
  <?php assignment_stat('fa-user-graduate', $summary['interns'], 'Interns', 'Field support'); ?>
  <?php assignment_stat('fa-hard-hat', $summary['contractors'], 'Contractors', 'Delivery teams'); ?>
  <?php assignment_stat('fa-user-tie', $summary['consultants'], 'Consultants', 'Technical review'); ?>
  <?php assignment_stat('fa-triangle-exclamation', $summary['missing_clerks'], 'Missing Clerk', 'Sites needing clerk'); ?>
  <?php assignment_stat('fa-location-dot', $summary['missing_interns'], 'Missing Intern', 'Sites needing intern'); ?>
</section>

<section class="card assignments-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Assignment Registry</h2>
      <p class="card__subtitle">Filter project staffing, update assignment scope and revoke access without deleting history.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($total)) ?> assignments</span>
  </div>

  <form class="filter-bar assignments-filter" method="get" action="<?= Security::e(Url::to('admin/manager/assignments.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Project, user, email, role..."></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">All roles</option><?php foreach (ProjectAssignment::assignableRoles() as $roleSlug): ?><option value="<?= Security::e($roleSlug) ?>" <?= (($filters['role'] ?? '') === $roleSlug) ? 'selected' : '' ?>><?= Security::e(role_label($roleSlug)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (ProjectAssignment::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="constituency_id">Constituency</label><select class="form-select" id="constituency_id" name="constituency_id"><option value="">All constituencies</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= (int)($filters['constituency_id'] ?? 0) === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/manager/assignments.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table assignments-table">
      <thead><tr><th>Project / Site</th><th>User</th><th>Role</th><th>Scope</th><th>Status</th><th>Dates</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($assignments === []): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-users-slash" aria-hidden="true"></i></span><strong class="empty-state__title">No assignments found</strong><span class="empty-state__text">Assign a clerk, intern, consultant or contractor to start linking site workflows.</span></div></td></tr>
<?php else: foreach ($assignments as $assignment): ?>
        <tr>
          <td><strong><?= Security::e($assignment['project_name']) ?></strong><small><?= Security::e(trim($assignment['constituency_name'] . ' / ' . $assignment['ward_name'], ' /') ?: 'No location') ?></small></td>
          <td><strong><?= Security::e($assignment['user_name']) ?></strong><small><?= Security::e($assignment['email']) ?></small></td>
          <td><span class="assignment-role"><?= Security::e(role_label($assignment['role_slug'])) ?></span><?php if ($assignment['is_primary']): ?><small class="assignment-primary"><i class="fa-solid fa-star" aria-hidden="true"></i> Primary</small><?php endif; ?></td>
          <td><strong><?= Security::e(status_label($assignment['assignment_type'])) ?></strong><small><?= Security::e(status_label($assignment['scope'])) ?></small></td>
          <td><span class="badge <?= Security::e(status_badge_class($assignment['status'])) ?>"><?= Security::e(status_label($assignment['status'])) ?></span><small>By <?= Security::e($assignment['assigned_by_name']) ?></small></td>
          <td><?= Security::e(format_date($assignment['start_date'] ?: $assignment['assigned_at'])) ?><small>Ends: <?= Security::e(format_date($assignment['end_date'] ?: null)) ?></small></td>
          <td>
            <div class="assignment-actions">
              <button class="btn btn--icon btn--primary" type="button" data-assignment-edit='<?= Security::e(json_encode($assignment, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' title="Edit assignment" aria-label="Edit assignment"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
<?php if ($assignment['status'] === 'revoked'): ?>
              <button class="btn btn--icon btn--outline" type="button" data-assignment-status="reactivate" data-id="<?= (int)$assignment['id'] ?>" title="Reactivate assignment" aria-label="Reactivate assignment"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
<?php else: ?>
              <button class="btn btn--icon btn--danger" type="button" data-assignment-status="revoke" data-id="<?= (int)$assignment['id'] ?>" title="Revoke assignment" aria-label="Revoke assignment"><i class="fa-solid fa-user-slash" aria-hidden="true"></i></button>
<?php endif; ?>
            </div>
          </td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Assignments pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> assignments</p>
    <div class="pagination__links"><a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(assignments_page_url($filters, max(1, $page - 1))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><span class="pagination__link is-active"><?= Security::e(format_number($page)) ?></span><a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(assignments_page_url($filters, min($totalPages, $page + 1))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
  </nav>
<?php endif; ?>
</section>

<div class="modal assignment-modal" data-assignment-modal hidden>
  <div class="modal__dialog assignment-modal__dialog">
    <form class="modal__content assignment-form" data-assignment-form>
      <div class="modal__header"><h2 data-assignment-title>New assignment</h2><button class="btn btn--icon btn--ghost" type="button" data-assignment-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
      <div class="modal__body assignment-form__grid">
        <input type="hidden" name="id" value="">
        <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" required data-assignment-project><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Role</span><select class="form-select" name="role_filter" data-assignment-role><option value="">Any assignable role</option><?php foreach (ProjectAssignment::assignableRoles() as $roleSlug): ?><option value="<?= Security::e($roleSlug) ?>"><?= Security::e(role_label($roleSlug)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field assignment-form__wide"><span class="form-label">User</span><select class="form-select" name="user_id" required data-assignment-user><option value="">Select project first</option></select></label>
        <label class="form-field"><span class="form-label">Assignment type</span><select class="form-select" name="assignment_type"><?php foreach (ProjectAssignment::typeOptions() as $type): ?><option value="<?= Security::e($type) ?>"><?= Security::e(status_label($type)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Scope</span><select class="form-select" name="scope"><?php foreach (ProjectAssignment::scopeOptions() as $scope): ?><option value="<?= Security::e($scope) ?>"><?= Security::e(status_label($scope)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (ProjectAssignment::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Start date</span><input class="form-input" type="date" name="start_date"></label>
        <label class="form-field"><span class="form-label">End date</span><input class="form-input" type="date" name="end_date"></label>
        <label class="form-check assignment-form__primary"><input type="checkbox" name="is_primary" value="1"> <span>Primary assignment for this project role</span></label>
        <label class="form-field assignment-form__wide"><span class="form-label">Notes</span><textarea class="form-textarea" name="notes" rows="4" placeholder="Shift notes, scope limits or site instructions"></textarea></label>
      </div>
      <div class="modal__footer"><button class="btn btn--outline" type="button" data-assignment-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Assignment</button></div>
    </form>
  </div>
</div>

<div class="modal assignment-modal" data-assignment-bulk-modal hidden>
  <div class="modal__dialog assignment-modal__dialog">
    <form class="modal__content assignment-form" data-assignment-bulk-form>
      <div class="modal__header"><h2>Bulk assign users</h2><button class="btn btn--icon btn--ghost" type="button" data-assignment-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
      <div class="modal__body assignment-form__grid">
        <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id" required data-bulk-project><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Role</span><select class="form-select" name="role_filter" data-bulk-role><option value="">Any assignable role</option><?php foreach (ProjectAssignment::assignableRoles() as $roleSlug): ?><option value="<?= Security::e($roleSlug) ?>"><?= Security::e(role_label($roleSlug)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Assignment type</span><select class="form-select" name="assignment_type"><?php foreach (ProjectAssignment::typeOptions() as $type): ?><option value="<?= Security::e($type) ?>"><?= Security::e(status_label($type)) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span class="form-label">Scope</span><select class="form-select" name="scope"><?php foreach (ProjectAssignment::scopeOptions() as $scope): ?><option value="<?= Security::e($scope) ?>"><?= Security::e(status_label($scope)) ?></option><?php endforeach; ?></select></label>
        <fieldset class="assignment-user-picker assignment-form__wide"><legend>Available users</legend><div data-bulk-users class="assignment-user-picker__list"><span class="text-muted">Choose a project to load users.</span></div></fieldset>
        <label class="form-field"><span class="form-label">Start date</span><input class="form-input" type="date" name="start_date"></label>
        <label class="form-field"><span class="form-label">End date</span><input class="form-input" type="date" name="end_date"></label>
        <label class="form-field assignment-form__wide"><span class="form-label">Notes</span><textarea class="form-textarea" name="notes" rows="3"></textarea></label>
      </div>
      <div class="modal__footer"><button class="btn btn--outline" type="button" data-assignment-close>Cancel</button><button class="btn btn--primary" type="submit"><i class="fa-solid fa-users-gear" aria-hidden="true"></i> Bulk Assign</button></div>
    </form>
  </div>
</div>

<?php
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function assignment_stat(string $icon, mixed $value, string $label, string $trend): void
{
?>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number((float)$value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($trend) ?></small></span>
  </article>
<?php
}

function assignments_page_url(array $filters, int $page): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => $value !== '' && $value !== null);
    return Url::to('admin/manager/assignments.php?' . http_build_query($query));
}
