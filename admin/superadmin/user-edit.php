<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_user_form';
$userId = Security::cleanInt($_GET['id'] ?? 0);
$user = $userId > 0 ? User::findDetailed($userId) : null;

if (!$user) {
    Session::flash('error', 'User account could not be found.');
    Response::redirect(Url::to('admin/superadmin/users.php'));
}

$roles = Role::allOrdered();
$rolesById = [];
foreach ($roles as $role) {
    $rolesById[(int)$role['id']] = $role;
}
$projects = user_project_options();
$workLocations = user_work_location_options();
$userAssignments = ProjectAssignment::projectsForUser($userId);
$userWorkLocationAssignments = WorkLocation::forUser($userId);
$selectedProjectIds = array_values(array_unique(array_map(
    'intval',
    array_column(array_filter($userAssignments, fn ($row) => (string)($row['status'] ?? '') === 'active'), 'project_id')
)));
$selectedWorkLocationIds = array_values(array_unique(array_map(
    'intval',
    array_column($userWorkLocationAssignments, 'work_location_id')
)));

$statusOptions = ['active', 'inactive', 'suspended'];
$currentUserId = (int)(Auth::id() ?? 0);
$errors = [];
$values = [
    'first_name' => (string)($user['first_name'] ?? ''),
    'last_name' => (string)($user['last_name'] ?? ''),
    'email' => (string)($user['email'] ?? ''),
    'phone' => (string)($user['phone'] ?? ''),
    'job_title' => (string)($user['job_title'] ?? ''),
    'department' => (string)($user['department'] ?? ''),
    'bio' => (string)($user['bio'] ?? ''),
    'avatar' => (string)($user['avatar'] ?? ''),
    'role_id' => (int)($user['role_id'] ?? 0),
    'status' => (string)($user['status'] ?? 'inactive'),
    'is_public' => 1,
    'send_invite' => 0,
];
$isProtectedSuperadminView = ($user['role_slug'] ?? '') === 'superadmin';
$oldRoleSlug = (string)($user['role_slug'] ?? '');

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/user-edit.php?id=' . $userId));
    }

    $values = user_form_values($_POST, $values);
    $selectedProjectIds = user_selected_project_ids($_POST['project_ids'] ?? []);
    $selectedWorkLocationIds = user_selected_project_ids($_POST['work_location_ids'] ?? []);
    $errors = user_validate_values($values, $rolesById, $statusOptions);

    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors['password'] = 'Enter a password with at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
    }

    if ($values['email'] !== '' && User::emailExists($values['email'], $userId)) {
        $errors['email'] = 'A different user already uses this Gmail address.';
    }
    if ($values['phone'] !== '' && User::phoneExists($values['phone'], $userId)) {
        $errors['phone'] = 'A different user already uses this phone number.';
    }

    $submittedRole = $rolesById[(int)$values['role_id']]['slug'] ?? '';
    $isProtectedSuperadmin = $isProtectedSuperadminView;
    $removesOwnAccess = $userId === $currentUserId && $values['status'] !== 'active';
    $removesLastSuperadmin = $isProtectedSuperadmin && ($submittedRole !== 'superadmin' || $values['status'] !== 'active');
    $createsSecondSuperadmin = !$isProtectedSuperadmin && $submittedRole === 'superadmin' && User::superadminCount($userId) >= 1;

    if ($removesOwnAccess) {
        $errors['status'] = 'You cannot deactivate or suspend your own account.';
    }
    if ($removesLastSuperadmin) {
        $errors['role_id'] = 'The County Director account is protected and must remain active.';
    }
    if ($createsSecondSuperadmin) {
        $errors['role_id'] = 'Only one County Director account is allowed.';
    }
    if (in_array($submittedRole, ProjectAssignment::assignableRoles(), true) && $selectedProjectIds === [] && !($submittedRole === 'intern' && $selectedWorkLocationIds !== [])) {
        $errors['project_ids'] = 'Select at least one project site or HQ work location for this role.';
    }
    if (ProjectAssignment::isSingleSiteRole($submittedRole) && count($selectedProjectIds) > 1) {
        $errors['project_ids'] = 'Clerks and interns may be assigned to only one project site. Select a single site.';
        $selectedProjectIds = [(int)$selectedProjectIds[0]];
    }

    $oldAvatar = (string)($user['avatar'] ?? '');
    $avatarPath = user_store_avatar($_FILES['avatar_upload'] ?? null, $errors);
    if ($avatarPath !== null) {
        $values['avatar'] = $avatarPath;
    }

    if ($errors === []) {
        $payload = [
            'first_name' => $values['first_name'],
            'last_name' => $values['last_name'],
            'email' => $values['email'],
            'phone' => $values['phone'] ?: null,
            'job_title' => $values['job_title'] ?: null,
            'department' => $values['department'] ?: null,
            'bio' => $values['bio'] ?: null,
            'avatar' => $values['avatar'] ?: null,
            'role_id' => (int)$values['role_id'],
            'status' => $values['status'],
            'is_public' => 1,
        ];

        if ($password !== '') {
            $payload['password_hash'] = User::hashPassword($password);
        }

        User::update($userId, $payload);
        if (in_array($submittedRole, ProjectAssignment::assignableRoles(), true)) {
            ProjectAssignment::syncUserAssignments($userId, $selectedProjectIds, $currentUserId);
        } else {
            ProjectAssignment::syncUserAssignments($userId, [], $currentUserId);
        }
        WorkLocation::syncUserAssignments($userId, $submittedRole === 'intern' ? $selectedWorkLocationIds : [], $submittedRole, $currentUserId);
        if ($avatarPath !== null && $oldAvatar !== '' && $oldAvatar !== $avatarPath) {
            user_delete_avatar_file($oldAvatar);
        }
        $accessChanged = $password !== '' || $oldRoleSlug !== $submittedRole || $values['status'] !== (string)($user['status'] ?? 'inactive');
        if ($accessChanged) {
            $exceptToken = $userId === $currentUserId ? UserSession::currentToken() : null;
            UserSession::revokeForUser($userId, $exceptToken);
        }
        if ($userId === $currentUserId) {
            Auth::refresh([
                'id' => $userId,
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'name' => trim($values['first_name'] . ' ' . $values['last_name']),
                'email' => $values['email'],
                'avatar' => $values['avatar'],
                'job_title' => $values['job_title'],
                'role' => $submittedRole,
                'role_name' => $rolesById[(int)$values['role_id']]['name'] ?? role_label($submittedRole),
            ]);
        }
        $message = 'User account updated successfully.';
        if ((int)$values['send_invite'] === 1) {
            try {
                $updatedUser = User::findDetailed($userId) ?: ['id' => $userId] + $values;
                $reset = PasswordReset::createForUser($updatedUser, 1440);
                $result = AuthEmailService::sendUserInvitation($updatedUser, $reset['token'], $reset['expires_at'], [
                    'role_label' => role_label($submittedRole),
                    'access_summary' => user_access_summary($submittedRole, $projects, $selectedProjectIds, $workLocations, $selectedWorkLocationIds),
                ]);
                Logger::log(!empty($result['success']) ? 'invite-sent' : 'invite-failed', 'users', $userId, [
                    'email' => $values['email'],
                    'message' => $result['message'] ?? null,
                ]);
                $message = !empty($result['success'])
                    ? 'User account updated and setup email sent successfully.'
                    : 'User account updated, but the setup email could not be sent. Check email settings.';
            } catch (Throwable $emailError) {
                Logger::log('invite-failed', 'users', $userId, ['email' => $values['email'], 'error' => $emailError->getMessage()]);
                $message = 'User account updated, but the setup email could not be prepared.';
            }
        }
        Logger::log('update', 'users', $userId, ['email' => $values['email']]);
        Session::flash('status', $message);
        Response::redirect(Url::to('admin/superadmin/user-edit.php?id=' . $userId));
    }
}

$pageTitle = 'Edit User';
$pageDescription = 'Update AHPTC user access, status, profile photo and profile details.';
$adminRole = 'superadmin';
$contentClass = 'sa-user-form-page';
$componentCss = ['user-profile'];
$pageScripts = ['user-form'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Users', 'url' => Url::to('admin/superadmin/users.php')],
    ['label' => 'Edit User'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<form class="sa-user-editor" method="post" action="<?= Security::e(Url::to('admin/superadmin/user-edit.php?id=' . $userId)) ?>" enctype="multipart/form-data" novalidate>
  <?= Csrf::field($csrfForm) ?>

  <section class="sa-projects-hero sa-user-hero card">
    <div>
      <span class="sa-panel-label"><i class="fa-solid fa-user-pen" aria-hidden="true"></i> Account Editor</span>
      <h2><?= Security::e(trim($values['first_name'] . ' ' . $values['last_name'])) ?></h2>
      <p>Update role access, profile data, account state and optional password reset without disturbing audit history.</p>
    </div>
    <div class="sa-action-grid">
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Users</a>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes</button>
    </div>
  </section>

  <section class="stat-grid stat-grid--3 sa-user-edit-stats" aria-label="User account summary">
    <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(role_label((string)($rolesById[(int)$values['role_id']]['slug'] ?? ''))) ?></strong><span class="stat-widget__label">Current Role</span><small class="stat-widget__trend">Dashboard permission group</small></span></article>
    <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-signal" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(status_label($values['status'])) ?></strong><span class="stat-widget__label">Account Status</span><small class="stat-widget__trend">Sign-in eligibility</small></span></article>
    <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(time_ago($user['last_login'] ?? null)) ?></strong><span class="stat-widget__label">Last Login</span><small class="stat-widget__trend">Most recent portal access</small></span></article>
  </section>

  <div class="sa-editor-layout">
    <div class="sa-editor-main">
      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Identity & Contact</h2>
            <p class="card__subtitle">Keep user identity consistent across dashboard records, messages and approvals.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <?= user_field('First name', 'first_name', $values, $errors, true) ?>
          <?= user_field('Last name', 'last_name', $values, $errors, true) ?>
          <?= user_field('Email address', 'email', $values, $errors, true, 'email') ?>
          <?= user_field('Phone number', 'phone', $values, $errors, true, 'tel', '+254712345678') ?>
          <?= user_field('Job title', 'job_title', $values, $errors, true, 'text', 'County Director, Resident Engineer...') ?>
          <?= user_field('Department / organisation', 'department', $values, $errors, true, 'text', 'County Housing Department') ?>
        </div>
        <?php render_user_selected_project_summary($projects, $selectedProjectIds, (string)($user['role_slug'] ?? '')); ?>
      </section>

      <section class="card sa-form-card sa-user-form-card sa-assignment-card">
        <div class="card__header">
          <div>
            <h2 class="card__title"><?= in_array((string)($user['role_slug'] ?? ''), ['superadmin', 'finance'], true) ? 'Project Sites' : 'Project Access' ?></h2>
            <p class="card__subtitle"><?= in_array((string)($user['role_slug'] ?? ''), ['superadmin', 'finance'], true) ? 'This role receives portfolio visibility automatically.' : 'Control which project sites this user can see and update from their dashboard.' ?></p>
          </div>
        </div>
        <?php render_user_project_assignment_picker($projects, $selectedProjectIds, $userAssignments, (string)($user['role_slug'] ?? '')); ?>
        <?= sa_user_error($errors, 'project_ids') ?>
      </section>

      <section class="card sa-form-card sa-user-form-card sa-assignment-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Work Location Access</h2>
            <p class="card__subtitle">Assign office-based interns to internal locations such as Headquarters Office. These assignments are managed by County Director/Superadmin, not site clerks.</p>
          </div>
        </div>
        <?php render_user_work_location_picker($workLocations, $selectedWorkLocationIds, (string)($user['role_slug'] ?? '')); ?>
      </section>

      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Access & Security</h2>
            <p class="card__subtitle">Change role, suspend access or reset the password. Leave password blank to keep the existing one.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <label class="form-field">
            <span class="form-label">Role <strong>*</strong></span>
<?php if ($isProtectedSuperadminView): ?>
            <input type="hidden" name="role_id" value="<?= Security::e((string)$values['role_id']) ?>">
            <input class="form-input" type="text" value="<?= Security::e(role_label((string)($rolesById[(int)$values['role_id']]['slug'] ?? 'superadmin'))) ?>" disabled>
            <span class="form-hint">System owner role is locked.</span>
<?php else: ?>
            <select class="form-select <?= isset($errors['role_id']) ? 'is-error' : '' ?>" name="role_id" required data-role-select>
              <option value="">Choose role</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?= Security::e((string)$role['id']) ?>" data-role-slug="<?= Security::e((string)$role['slug']) ?>" <?= (string)$values['role_id'] === (string)$role['id'] ? 'selected' : '' ?>><?= Security::e(role_label((string)$role['slug'])) ?></option>
              <?php endforeach; ?>
            </select>
<?php endif; ?>
            <?= sa_user_error($errors, 'role_id') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Status <strong>*</strong></span>
<?php if ($isProtectedSuperadminView): ?>
            <input type="hidden" name="status" value="active">
            <input class="form-input" type="text" value="Active" disabled>
            <span class="form-hint">System owner account must remain active.</span>
<?php else: ?>
            <select class="form-select <?= isset($errors['status']) ? 'is-error' : '' ?>" name="status" required>
              <?php foreach ($statusOptions as $status): ?>
                <option value="<?= Security::e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
              <?php endforeach; ?>
            </select>
<?php endif; ?>
            <?= sa_user_error($errors, 'status') ?>
          </label>
          <label class="form-field">
            <span class="form-label">New password</span>
            <div class="sa-password-field">
              <input class="form-input <?= isset($errors['password']) ? 'is-error' : '' ?>" type="password" name="password" autocomplete="new-password" minlength="8" data-password-input>
              <button class="btn btn--icon btn--outline" type="button" data-password-toggle aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
            <span class="form-hint">Optional. Minimum 8 characters when used.</span>
            <?= sa_user_error($errors, 'password') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Confirm new password</span>
            <div class="sa-password-field">
              <input class="form-input <?= isset($errors['password_confirm']) ? 'is-error' : '' ?>" type="password" name="password_confirm" autocomplete="new-password" minlength="8" data-password-input>
              <button class="btn btn--icon btn--outline" type="button" data-password-toggle aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
            <?= sa_user_error($errors, 'password_confirm') ?>
          </label>
          <label class="form-field sa-toggle-field">
            <span class="form-label">Account email</span>
            <span class="sa-checkbox-line">
              <input type="checkbox" name="send_invite" value="1" <?= (int)$values['send_invite'] === 1 ? 'checked' : '' ?>>
              <span>Email this user a fresh setup link and current login details.</span>
            </span>
            <span class="form-hint">Use this after changing role, project access or resetting a user who cannot sign in.</span>
          </label>
        </div>
      </section>

      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Profile Media & Bio</h2>
            <p class="card__subtitle">Manage the photo and biography used to identify this user across AHPTC modules.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <label class="form-field">
            <span class="form-label">Profile photo</span>
            <span class="sa-avatar-upload" data-avatar-drop>
              <input type="file" name="avatar_upload" accept="image/jpeg,image/png,image/webp,image/gif" data-avatar-input>
              <span class="sa-avatar-upload__preview" data-avatar-preview><?php if ($values['avatar'] !== ''): ?><img src="<?= Security::e(Url::asset($values['avatar'])) ?>" alt=""><?php else: ?><?= Security::e(user_initials($values)) ?><?php endif; ?></span>
              <strong>Upload replacement photo</strong>
              <small>JPG, PNG, WebP or GIF up to 5MB.</small>
            </span>
            <input class="form-input sa-path-input" type="text" name="avatar" value="<?= Security::e($values['avatar']) ?>" placeholder="Uploaded avatar path will appear here" readonly data-avatar-path>
            <?= sa_user_error($errors, 'avatar_upload') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Short biography</span>
            <textarea class="form-textarea" name="bio" rows="6" data-profile-bio placeholder="Role summary, professional background or approved profile note."><?= Security::e($values['bio']) ?></textarea>
          </label>
        </div>
      </section>
    </div>

    <aside class="sa-editor-aside">
      <?php
      $selectedPreviewProjects = array_values(array_filter($projects, static fn (array $project): bool => in_array((int)$project['id'], $selectedProjectIds, true)));
      $previewValues = $values;
      $previewUser = $user + [
          'role_name' => (string)($rolesById[(int)$values['role_id']]['name'] ?? role_label((string)($user['role_slug'] ?? 'staff'))),
          'status' => $values['status'],
      ];
      $previewProjects = in_array((string)($user['role_slug'] ?? ''), ['superadmin', 'finance'], true) ? $projects : $selectedPreviewProjects;
      $previewMode = 'edit';
      include __DIR__ . '/../../app/partials/admin/user-preview-card.php';
      ?>
    </aside>
  </div>

  <div class="sa-form-actionbar">
    <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Updates are audited and CSRF protected</span>
    <div>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>">Cancel</a>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes</button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function user_form_values(array $source, array $defaults): array
{
    return [
        'first_name' => Security::cleanString((string)($source['first_name'] ?? $defaults['first_name'])),
        'last_name' => Security::cleanString((string)($source['last_name'] ?? $defaults['last_name'])),
        'email' => User::normaliseEmail(Security::cleanEmail((string)($source['email'] ?? $defaults['email']))),
        'phone' => User::normalisePhone(Security::cleanString((string)($source['phone'] ?? $defaults['phone']))),
        'job_title' => Security::cleanString((string)($source['job_title'] ?? $defaults['job_title'])),
        'department' => Security::cleanString((string)($source['department'] ?? $defaults['department'])),
        'bio' => trim(strip_tags((string)($source['bio'] ?? $defaults['bio']))),
        'avatar' => Security::cleanString((string)($source['avatar'] ?? $defaults['avatar'])),
        'role_id' => Security::cleanInt($source['role_id'] ?? $defaults['role_id']),
        'status' => Security::cleanString((string)($source['status'] ?? $defaults['status'])),
        'is_public' => 1,
        'send_invite' => !empty($source['send_invite']) ? 1 : 0,
    ];
}

function user_validate_values(array $values, array $rolesById, array $statusOptions): array
{
    $errors = [];
    if ($values['first_name'] === '') {
        $errors['first_name'] = 'First name is required.';
    }
    if ($values['last_name'] === '') {
        $errors['last_name'] = 'Last name is required.';
    }
    if (!User::isValidGmail((string)$values['email'])) {
        $errors['email'] = 'Only Gmail addresses are accepted.';
    }
    if (!User::isValidKenyanPhone((string)$values['phone'])) {
        $errors['phone'] = 'Enter a Kenyan phone number, for example +254712345678.';
    }
    if ($values['job_title'] === '') {
        $errors['job_title'] = 'Job title is required.';
    }
    if ($values['department'] === '') {
        $errors['department'] = 'Department is required.';
    }
    if (!isset($rolesById[(int)$values['role_id']])) {
        $errors['role_id'] = 'Choose a valid role.';
    }
    if (!in_array($values['status'], $statusOptions, true)) {
        $errors['status'] = 'Choose a valid account status.';
    }
    return $errors;
}

function user_selected_project_ids(mixed $source): array
{
    if (!is_array($source)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map('intval', $source), fn ($id) => $id > 0)));
}

function user_project_options(): array
{
    return Database::fetchAll(
        'SELECT p.id, p.name, p.slug, p.status, p.units,
                c.name AS constituency_name,
                w.name AS ward_name
         FROM projects p
         LEFT JOIN constituencies c ON c.id = p.constituency_id
         LEFT JOIN wards w ON w.id = p.ward_id
         ORDER BY COALESCE(c.name, "Unassigned Constituency") ASC, p.name ASC'
    );
}

function user_work_location_options(): array
{
    return WorkLocation::active();
}

function render_user_selected_project_summary(array $projects, array $selectedProjectIds, string $roleSlug = ''): void
{
    $selected = array_values(array_filter($projects, static fn (array $project): bool => in_array((int)$project['id'], $selectedProjectIds, true)));
    $allAccess = in_array(strtolower($roleSlug), ['superadmin', 'county_director'], true);
    $financeAccess = strtolower($roleSlug) === 'finance';
    $count = $allAccess || $financeAccess ? count($projects) : count($selected);
?>
  <div class="sa-selected-projects" data-selected-project-summary data-empty-text="No project sites selected yet.">
    <div class="sa-selected-projects__head">
      <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Selected project sites</span>
      <strong data-selected-project-count><?= Security::e((string)$count) ?></strong>
    </div>
    <div class="sa-selected-projects__list" data-selected-project-list>
<?php if ($allAccess): ?>
      <span class="sa-selected-project-pill is-all-access">All project sites</span>
<?php elseif ($financeAccess): ?>
      <span class="sa-selected-project-pill is-all-access">Finance portfolio visibility</span>
<?php elseif ($selected === []): ?>
      <span class="sa-selected-projects__empty">No project sites selected yet.</span>
<?php else: foreach ($selected as $project): ?>
      <span class="sa-selected-project-pill" data-project-id="<?= (int)$project['id'] ?>"><?= Security::e((string)$project['name']) ?></span>
<?php endforeach; endif; ?>
    </div>
  </div>
<?php
}

function render_user_project_assignment_picker(array $projects, array $selectedProjectIds, array $assignments = [], string $currentRole = ''): void
{
    $currentRole = strtolower($currentRole);
    if (in_array($currentRole, ['superadmin', 'county_director'], true)) {
?>
  <div class="sa-assignment-picker sa-assignment-picker--readonly">
    <div class="sa-assignment-mode sa-assignment-mode--all">
      <i class="fa-solid fa-crown" aria-hidden="true"></i>
      <div>
        <strong>All project sites</strong>
        <span>County Director accounts automatically see every project site. Manual project selection is not required.</span>
      </div>
    </div>
    <p class="form-hint">To assign specific project sites, use manager, consultant, contractor, clerk or intern roles.</p>
  </div>
<?php
        return;
    }

    if ($currentRole === 'finance') {
?>
  <div class="sa-assignment-picker sa-assignment-picker--readonly">
    <div class="sa-assignment-mode sa-assignment-mode--finance">
      <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
      <div>
        <strong>Finance project portfolio</strong>
        <span>Finance users see project financial workflows without site-operation assignment.</span>
      </div>
    </div>
    <p class="form-hint">Site-operation assignment is only required for manager, consultant, contractor, clerk and intern roles.</p>
  </div>
<?php
        return;
    }

    $assignmentByProject = [];
    foreach ($assignments as $assignment) {
        $assignmentByProject[(int)$assignment['project_id']] = $assignment;
    }
    $grouped = [];
    foreach ($projects as $project) {
        $constituency = trim((string)($project['constituency_name'] ?? '')) ?: 'Unassigned Constituency';
        $grouped[$constituency][] = $project;
    }
?>
  <div class="sa-assignment-picker" data-assignment-picker data-static-role="<?= Security::e($currentRole) ?>">
    <div class="sa-assignment-mode sa-assignment-mode--all" data-assignment-all-access hidden>
      <i class="fa-solid fa-crown" aria-hidden="true"></i>
      <div>
        <strong>Automatic all-site access</strong>
        <span>This role can access every project site by default. Manual project selection is not required.</span>
      </div>
    </div>
    <div class="sa-assignment-mode sa-assignment-mode--finance" data-assignment-finance-access hidden>
      <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
      <div>
        <strong>Finance portfolio visibility</strong>
        <span>Finance users can view project financial workflows without being assigned as site operators.</span>
      </div>
    </div>
<?php
    $singleSite = ProjectAssignment::isSingleSiteRole($currentRole);
    $inputType = $singleSite ? 'radio' : 'checkbox';
    $inputName = $singleSite ? 'project_ids[]' : 'project_ids[]';
?>
    <div class="sa-assignment-picker__tools">
      <span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> <?= $singleSite ? 'Select one project site' : 'Select project access' ?></span>
      <label class="sa-assignment-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" placeholder="Search project sites..." data-assignment-search></label>
      <div>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-show-selected aria-pressed="false">Selected only</button>
<?php if (!$singleSite): ?>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-select-all>Select all</button>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-group-toggle-all hidden>Select constituency</button>
<?php endif; ?>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-clear>Clear</button>
      </div>
    </div>
    <div class="sa-assignment-groups" data-single-site="<?= $singleSite ? '1' : '0' ?>">
<?php foreach ($grouped as $constituency => $items): ?>
      <section class="sa-assignment-group" data-assignment-group>
        <div class="sa-assignment-group__head">
          <span><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> <?= Security::e($constituency) ?></span>
<?php if (!$singleSite): ?>
          <button class="btn btn--outline btn--sm" type="button" data-assignment-group-toggle>Select constituency</button>
<?php endif; ?>
        </div>
        <div class="sa-assignment-grid">
<?php foreach ($items as $project): ?>
<?php
    $projectId = (int)$project['id'];
    $assignment = $assignmentByProject[$projectId] ?? null;
    $assignmentStatus = (string)($assignment['status'] ?? '');
    $isSelected = in_array($projectId, $selectedProjectIds, true);
    if ($singleSite && count($selectedProjectIds) > 1) {
        $isSelected = $projectId === (int)$selectedProjectIds[0];
    }
?>
          <label class="sa-assignment-option">
            <input type="<?= Security::e($inputType) ?>" name="<?= Security::e($inputName) ?>" value="<?= Security::e((string)$projectId) ?>" <?= $isSelected ? 'checked' : '' ?> data-project-name="<?= Security::e((string)$project['name']) ?>" <?= $singleSite ? 'data-single-site-input' : '' ?>>
            <span>
              <strong><?= Security::e((string)$project['name']) ?></strong>
              <small>
                <?= Security::e(status_label((string)($project['status'] ?? 'active'))) ?><?= $assignmentStatus !== '' ? ' / ' . Security::e(status_label($assignmentStatus)) : '' ?>
                <?php if (!empty($project['ward_name'])): ?>/ <?= Security::e((string)$project['ward_name']) ?><?php endif; ?>
                <?php if ((int)($project['units'] ?? 0) > 0): ?>/ <?= Security::e(format_number((int)$project['units'])) ?> units<?php endif; ?>
              </small>
            </span>
          </label>
<?php endforeach; ?>
        </div>
      </section>
<?php endforeach; ?>
    </div>
    <p class="form-hint"><?= $singleSite
        ? 'Clerks and interns may only be assigned to one active project site. A site may still have many clerks and interns.'
        : 'You are assigning access as County Director. Site selection is required only for manager, consultant, contractor, clerk and intern roles.' ?></p>
  </div>
<?php
}

function render_user_work_location_picker(array $locations, array $selectedLocationIds, string $currentRole = ''): void
{
    $currentRole = strtolower($currentRole);
    if ($currentRole === 'superadmin') {
?>
  <div class="sa-assignment-picker sa-work-location-picker sa-assignment-picker--readonly">
    <div class="sa-assignment-mode sa-assignment-mode--all">
      <i class="fa-solid fa-ban" aria-hidden="true"></i>
      <div>
        <strong>Not applicable</strong>
        <span>Only office-based interns use internal work locations. Manual assignment is not required.</span>
      </div>
    </div>
  </div>
<?php
        return;
    }
?>
  <div class="sa-assignment-picker sa-work-location-picker" data-static-role="<?= Security::e($currentRole) ?>">
    
    <div class="sa-assignment-mode sa-assignment-mode--empty" data-work-location-empty-state hidden>
      <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
      <div>
        <strong>Choose a role first</strong>
        <span>Work location options will appear here if the selected role requires them.</span>
      </div>
    </div>

    <div class="sa-assignment-mode sa-assignment-mode--all" data-work-location-not-applicable hidden>
      <i class="fa-solid fa-ban" aria-hidden="true"></i>
      <div>
        <strong>Not applicable</strong>
        <span>Only office-based interns are assigned to internal work locations.</span>
      </div>
    </div>

    <div data-work-location-groups>
      <?php if ($locations === []): ?>
        <div class="sa-assignment-mode sa-assignment-mode--empty">
          <i class="fa-solid fa-building-user" aria-hidden="true"></i>
          <div><strong>No work locations configured</strong><span>Create internal work locations from the Work Locations page.</span></div>
        </div>
      <?php else: ?>
        <div class="sa-assignment-groups">
          <section class="sa-assignment-group">
            <div class="sa-assignment-group__head">
              <span><i class="fa-solid fa-building-user" aria-hidden="true"></i> Internal work locations</span>
            </div>
            <div class="sa-assignment-grid">
            <?php foreach ($locations as $location): ?>
            <?php $locationId = (int)$location['id']; ?>
              <label class="sa-assignment-option">
                <input type="checkbox" name="work_location_ids[]" value="<?= $locationId ?>" <?= in_array($locationId, $selectedLocationIds, true) ? 'checked' : '' ?>>
                <span>
                  <strong><?= Security::e((string)$location['name']) ?></strong>
                  <small><?= Security::e(status_label((string)$location['status'])) ?> / <?= Security::e((string)($location['address'] ?: 'Internal office')) ?></small>
                </span>
              </label>
            <?php endforeach; ?>
            </div>
          </section>
        </div>
        <p class="form-hint">Use this for HQ interns. Site clerks do not open or supervise these office assignments.</p>
      <?php endif; ?>
    </div>
  </div>
<?php
}

function user_project_access_summary(string $roleSlug, array $projects, array $selectedProjectIds): string
{
    $roleSlug = strtolower($roleSlug);
    if (in_array($roleSlug, ['superadmin', 'county_director'], true)) {
        return 'All project sites (' . count($projects) . ' total)';
    }
    if ($roleSlug === 'finance') {
        return 'Finance portfolio visibility across project financial workflows';
    }

    $names = [];
    foreach ($projects as $project) {
        if (in_array((int)$project['id'], $selectedProjectIds, true)) {
            $names[] = (string)$project['name'];
        }
    }

    if ($names === []) {
        return 'No project sites selected';
    }

    return implode(', ', array_slice($names, 0, 8)) . (count($names) > 8 ? ' and ' . (count($names) - 8) . ' more' : '');
}

function user_access_summary(string $roleSlug, array $projects, array $selectedProjectIds, array $locations, array $selectedLocationIds): string
{
    $projectSummary = user_project_access_summary($roleSlug, $projects, $selectedProjectIds);
    $locationNames = [];
    foreach ($locations as $location) {
        if (in_array((int)$location['id'], $selectedLocationIds, true)) {
            $locationNames[] = (string)$location['name'];
        }
    }

    if ($locationNames === []) {
        return $projectSummary;
    }

    return $projectSummary . '; Work locations: ' . implode(', ', $locationNames);
}

function user_store_avatar(?array $file, array &$errors): ?string
{
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $errors['avatar_upload'] = 'Profile photo upload failed. Please try again.';
        return null;
    }
    if ((int)$file['size'] > 5 * 1024 * 1024) {
        $errors['avatar_upload'] = 'Profile photo must be 5MB or smaller.';
        return null;
    }
    $name = (string)($file['name'] ?? '');
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!Security::extensionAllowed($name, $allowed) || @getimagesize((string)$file['tmp_name']) === false) {
        $errors['avatar_upload'] = 'Upload a valid JPG, PNG, WebP or GIF image.';
        return null;
    }
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $uploadDir = dirname(__DIR__, 2) . '/uploads/profiles';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $filename = 'profile-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        $errors['avatar_upload'] = 'Could not save the uploaded profile photo.';
        return null;
    }
    return 'uploads/profiles/' . $filename;
}

function user_delete_avatar_file(string $path): void
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, 'uploads/profiles/')) {
        return;
    }

    $root = realpath(dirname(__DIR__, 2));
    $file = realpath($root . '/' . $path);
    $profileDir = realpath($root . '/uploads/profiles');

    if ($root === false || $file === false || $profileDir === false) {
        return;
    }

    if (str_starts_with($file, $profileDir . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}

function user_field(string $label, string $name, array $values, array $errors, bool $required = false, string $type = 'text', string $placeholder = ''): string
{
    $errorClass = isset($errors[$name]) ? ' is-error' : '';
    $requiredAttr = $required ? ' required' : '';
    return '<label class="form-field"><span class="form-label">' . Security::e($label) . ($required ? ' <strong>*</strong>' : '') . '</span><input class="form-input' . $errorClass . '" type="' . Security::e($type) . '" name="' . Security::e($name) . '" value="' . Security::e($values[$name] ?? '') . '" placeholder="' . Security::e($placeholder) . '"' . $requiredAttr . ' data-profile-field="' . Security::e($name) . '">' . sa_user_error($errors, $name) . '</label>';
}

function sa_user_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<span class="form-error">' . Security::e($errors[$field]) . '</span>' : '';
}
