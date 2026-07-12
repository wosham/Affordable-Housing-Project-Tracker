<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_user_action';
$currentUserId = (int)(Auth::id() ?? 0);

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/users.php'));
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));
    $userId = Security::cleanInt($_POST['user_id'] ?? 0);
    $target = $userId > 0 ? User::findDetailed($userId) : null;

    if (!$target) {
        Session::flash('error', 'User account could not be found.');
    } elseif (($target['role_slug'] ?? '') === 'superadmin' && in_array($action, ['suspend', 'deactivate'], true)) {
        Session::flash('error', 'The County Director account is protected and must remain active.');
    } elseif ($userId === $currentUserId && in_array($action, ['suspend', 'deactivate'], true)) {
        Session::flash('error', 'You cannot suspend or deactivate your own account.');
    } elseif ($userId === $currentUserId && $action === 'force_logout') {
        Session::flash('error', 'You cannot force logout your own active session from this screen.');
    } elseif (($target['role_slug'] ?? '') === 'superadmin' && ($target['status'] ?? '') === 'active' && User::activeSuperadminCount() <= 1 && in_array($action, ['suspend', 'deactivate'], true)) {
        Session::flash('error', 'At least one active County Director account must remain.');
    } elseif ($action === 'force_logout') {
        $revoked = UserSession::revokeForUser($userId);
        Logger::log('force-logout', 'users', $userId, ['email' => $target['email'] ?? '', 'sessions' => $revoked]);
        Session::flash('status', 'Active sessions for this user were revoked.');
    } else {
        $nextStatus = match ($action) {
            'activate' => 'active',
            'suspend' => 'suspended',
            'deactivate' => 'inactive',
            default => null,
        };

        if ($nextStatus) {
            User::update($userId, ['status' => $nextStatus]);
            if (in_array($nextStatus, ['suspended', 'inactive'], true)) {
                UserSession::revokeForUser($userId);
            }
            Logger::log($action, 'users', $userId, ['email' => $target['email'] ?? '']);
            Session::flash('status', 'User account updated successfully.');
        }
    }

    Response::redirect(Url::to('admin/superadmin/users.php'));
}

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
    'assignment_state' => Security::cleanString((string)($_GET['assignment_state'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0);

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalUsers = User::countWithFilters($filters);
$totalPages = max(1, (int)ceil($totalUsers / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$users = User::withRoles($filters, $perPage, $offset);
$showingFrom = $totalUsers > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($users), $totalUsers);

$roles = Role::allOrdered();
$projectOptions = Database::fetchAll(
    'SELECT p.id, p.name, c.name AS constituency_name
     FROM projects p
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     ORDER BY COALESCE(c.name, "Unassigned Constituency") ASC, p.name ASC'
);
$constituencies = Database::fetchAll('SELECT id, name FROM constituencies ORDER BY name ASC');
$stats = User::stats();
$roleDistribution = User::roleDistribution();

$pageTitle = 'Users';
$pageDescription = 'Manage AHPTC users, roles, account status and profile readiness.';
$adminRole = 'superadmin';
$contentClass = 'sa-users-page';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Users'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-projects-hero sa-users-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-users-gear" aria-hidden="true"></i> User Command Centre</span>
    <h2>People, roles and staff profiles</h2>
    <p>Control staff access, profile readiness, role coverage and account status across the AHPTC workspace.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/user-create.php')) ?>"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> New User</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Audit Log</a>
  </div>
</section>

<section class="stat-grid stat-grid--4 sa-user-stat-row" aria-label="User summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-users" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['total_users'] ?? 0)) ?></strong><span class="stat-widget__label">Total Users</span><small class="stat-widget__trend">All portal accounts</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['active_users'] ?? 0)) ?></strong><span class="stat-widget__label">Active Users</span><small class="stat-widget__trend">Can access workspace</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-user-lock" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['suspended_users'] ?? 0)) ?></strong><span class="stat-widget__label">Suspended</span><small class="stat-widget__trend">Access blocked</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-id-badge" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['inactive_users'] ?? 0)) ?></strong><span class="stat-widget__label">Inactive Users</span><small class="stat-widget__trend">Dormant accounts</small></span></article>
</section>

<section class="card sa-role-strip" aria-label="Role distribution">
<?php foreach ($roleDistribution as $role): ?>
  <span><strong><?= Security::e(format_number($role['total'] ?? 0)) ?></strong><?= Security::e($role['name']) ?></span>
<?php endforeach; ?>
</section>

<section class="card sa-users-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">User Directory</h2>
      <p class="card__subtitle">Search and manage all system users, profile photos, account status and permission groups.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalUsers)) ?> records</span>
  </div>

  <form class="filter-bar sa-project-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Name, email, phone, department..."></div>
    <div class="filter-group"><label class="filter-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">All roles</option><?php foreach ($roles as $role): ?><option value="<?= Security::e($role['slug']) ?>" <?= (($filters['role'] ?? '') === $role['slug']) ? 'selected' : '' ?>><?= Security::e($role['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (['active', 'inactive', 'suspended'] as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="constituency_id">Constituency</label><select class="form-select" id="constituency_id" name="constituency_id"><option value="">All constituencies</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= (int)($filters['constituency_id'] ?? 0) === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="project_id">Project Site</label><select class="form-select" id="project_id" name="project_id"><option value="">All project sites</option><?php foreach ($projectOptions as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e(trim((string)($project['constituency_name'] ?? '')) !== '' ? $project['constituency_name'] . ' - ' . $project['name'] : $project['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="assignment_state">Project Access</label><select class="form-select" id="assignment_state" name="assignment_state"><option value="">Any access state</option><option value="assigned" <?= (($filters['assignment_state'] ?? '') === 'assigned') ? 'selected' : '' ?>>Has assigned sites</option><option value="unassigned" <?= (($filters['assignment_state'] ?? '') === 'unassigned') ? 'selected' : '' ?>>No assigned sites</option></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table sa-users-table">
      <thead><tr><th class="sa-table-number">#</th><th>User</th><th>Contact</th><th>Role</th><th>Project Access</th><th>Status</th><th>Last Login</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
<?php if ($users === []): ?>
        <tr><td colspan="9"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-user-slash" aria-hidden="true"></i></span><strong class="empty-state__title">No users match your filters</strong><span class="empty-state__text">Create a new user or widen the filters.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($users as $index => $user): ?>
<?php $avatar = (string)($user['avatar'] ?? ''); ?>
<?php $roleSlug = (string)($user['role_slug'] ?? ''); ?>
        <tr>
          <td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td>
          <td><div class="sa-user-cell"><span class="sa-user-avatar"><?php if ($avatar !== ''): ?><img src="<?= Security::e(Url::asset($avatar)) ?>" alt=""><?php else: ?><?= Security::e(user_initials($user)) ?><?php endif; ?></span><span><strong><?= Security::e($user['name']) ?></strong><small><?= Security::e($user['job_title'] ?: ($user['department'] ?: 'No profile title')) ?></small></span></div></td>
          <td><strong><?= Security::e($user['email']) ?></strong><small><?= Security::e($user['phone'] ?: 'No phone') ?></small></td>
          <td><span class="badge badge--info"><?= Security::e(role_label($roleSlug)) ?></span></td>
          <td>
<?php if ($roleSlug === 'superadmin'): ?>
            <span class="badge badge--lime">All project sites</span>
<?php elseif ($roleSlug === 'finance'): ?>
            <span class="badge badge--neutral">Finance portfolio</span>
<?php else: ?>
            <span class="badge <?= (int)($user['active_project_count'] ?? 0) > 0 ? 'badge--success' : 'badge--warning' ?>"><?= Security::e(format_number((int)($user['active_project_count'] ?? 0))) ?> sites</span>
<?php endif; ?>
          </td>
          <td><span class="badge <?= Security::e(status_badge_class($user['status'])) ?>"><?= Security::e(status_label($user['status'])) ?></span></td>
          <td><time datetime="<?= Security::e($user['last_login'] ?? '') ?>"><?= Security::e(time_ago($user['last_login'] ?? null)) ?></time></td>
          <td><?= Security::e(format_date($user['created_at'] ?? null)) ?></td>
          <td>
            <div class="data-table__actions">
              <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/user-edit.php?id=' . (int)$user['id'])) ?>" title="Edit user" aria-label="Edit user"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
<?php if (($user['role_slug'] ?? '') !== 'superadmin'): ?>
<?php if (($user['status'] ?? '') !== 'active'): ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>" data-confirm="Activate this user account?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="activate"><input type="hidden" name="user_id" value="<?= Security::e((string)$user['id']) ?>"><button class="btn btn--icon btn--outline" type="submit" title="Activate" aria-label="Activate"><i class="fa-solid fa-user-check" aria-hidden="true"></i></button></form>
<?php else: ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>" data-confirm="Suspend this user account?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="suspend"><input type="hidden" name="user_id" value="<?= Security::e((string)$user['id']) ?>"><button class="btn btn--icon btn--outline" type="submit" title="Suspend" aria-label="Suspend"><i class="fa-solid fa-user-lock" aria-hidden="true"></i></button></form>
<?php endif; ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>" data-confirm="Deactivate this user account?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="deactivate"><input type="hidden" name="user_id" value="<?= Security::e((string)$user['id']) ?>"><button class="btn btn--icon btn--danger" type="submit" title="Deactivate" aria-label="Deactivate"><i class="fa-solid fa-ban" aria-hidden="true"></i></button></form>
<?php if ((int)$user['id'] !== $currentUserId): ?>
              <form method="post" action="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>" data-confirm="Force logout this user from all active sessions?"><?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="force_logout"><input type="hidden" name="user_id" value="<?= Security::e((string)$user['id']) ?>"><button class="btn btn--icon btn--outline" type="submit" title="Force logout" aria-label="Force logout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></button></form>
<?php endif; ?>
<?php else: ?>
              <span class="badge badge--lime">System Owner</span>
<?php endif; ?>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination sa-project-pagination" aria-label="User pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalUsers)) ?> users</p>
    <div class="pagination__list">
      <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(user_page_url(max(1, $page - 1), $filters)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(user_page_url($i, $filters)) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
      <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(user_page_url(min($totalPages, $page + 1), $filters)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function user_page_url(int $page, array $filters): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => (string)$value !== '');
    return Url::to('admin/superadmin/users.php' . ($query ? '?' . http_build_query($query) : ''));
}
