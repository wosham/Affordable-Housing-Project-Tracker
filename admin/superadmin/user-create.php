<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_user_form';
$roles = Role::allOrdered();
$rolesById = [];
foreach ($roles as $role) {
    $rolesById[(int)$role['id']] = $role;
}
$projects = user_project_options();
$workLocations = user_work_location_options();
$superadminExists = User::superadminCount() >= 1;

$statusOptions = ['active', 'inactive', 'suspended'];
$errors = [];
$selectedProjectIds = [];
$selectedWorkLocationIds = [];
$values = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'job_title' => '',
    'department' => '',
    'bio' => '',
    'avatar' => '',
    'role_id' => '',
    'status' => 'active',
    'is_public' => 1,
    'send_invite' => 1,
];

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/user-create.php'));
    }

    $values = user_form_values($_POST, $values);
    $selectedProjectIds = user_selected_project_ids($_POST['project_ids'] ?? []);
    $selectedWorkLocationIds = user_selected_project_ids($_POST['work_location_ids'] ?? []);
    $errors = user_validate_values($values, $rolesById, $statusOptions);

    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    if (strlen($password) < 8) {
        $errors['password'] = 'Enter a password with at least 8 characters.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if ($values['email'] !== '' && User::emailExists($values['email'])) {
        $errors['email'] = 'A user with this email already exists.';
    }
    if ($values['phone'] !== '' && User::phoneExists($values['phone'])) {
        $errors['phone'] = 'A user with this phone number already exists.';
    }

    $submittedRole = $rolesById[(int)$values['role_id']]['slug'] ?? '';
    if ($submittedRole === 'superadmin' && User::superadminCount() >= 1) {
        $errors['role_id'] = 'Only one County Director account is allowed.';
    }
    if (in_array($submittedRole, ProjectAssignment::assignableRoles(), true) && $selectedProjectIds === [] && !($submittedRole === 'intern' && $selectedWorkLocationIds !== [])) {
        $errors['project_ids'] = 'Select at least one project site or HQ work location for this role.';
    }
    if (ProjectAssignment::isSingleSiteRole($submittedRole) && count($selectedProjectIds) > 1) {
        $errors['project_ids'] = 'Clerks and interns may be assigned to only one project site. Select a single site.';
        $selectedProjectIds = [(int)$selectedProjectIds[0]];
    }

    $avatarPath = user_store_avatar($_FILES['avatar_upload'] ?? null, $errors);
    if ($avatarPath !== null) {
        $values['avatar'] = $avatarPath;
    }

    if ($errors === []) {
        $userId = User::create([
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
            'password_hash' => User::hashPassword($password),
        ]);

        if (in_array($submittedRole, ProjectAssignment::assignableRoles(), true)) {
            ProjectAssignment::syncUserAssignments((int)$userId, $selectedProjectIds, (int)Auth::id());
        }
        WorkLocation::syncUserAssignments((int)$userId, $submittedRole === 'intern' ? $selectedWorkLocationIds : [], $submittedRole, (int)Auth::id());

        Logger::log('create', 'users', (int)$userId, ['email' => $values['email']]);
        $message = 'User account created successfully.';
        if ((int)$values['send_invite'] === 1) {
            try {
                $createdUser = User::findDetailed((int)$userId) ?: ['id' => (int)$userId] + $values;
                $reset = PasswordReset::createForUser($createdUser, 1440);
                $result = AuthEmailService::sendUserInvitation($createdUser, $reset['token'], $reset['expires_at'], [
                    'role_label' => role_label($submittedRole),
                    'access_summary' => user_access_summary($submittedRole, $projects, $selectedProjectIds, $workLocations, $selectedWorkLocationIds),
                ]);
                Logger::log(!empty($result['success']) ? 'invite-sent' : 'invite-failed', 'users', (int)$userId, [
                    'email' => $values['email'],
                    'message' => $result['message'] ?? null,
                ]);
                $message = !empty($result['success'])
                    ? 'User account created and setup email sent successfully.'
                    : 'User account created, but the setup email could not be sent. Check email settings.';
            } catch (Throwable $emailError) {
                Logger::log('invite-failed', 'users', (int)$userId, ['email' => $values['email'], 'error' => $emailError->getMessage()]);
                $message = 'User account created, but the setup email could not be prepared.';
            }
        }
        Session::flash('status', $message);
        Response::redirect(Url::to('admin/superadmin/user-edit.php?id=' . (int)$userId));
    }
}

$pageTitle = 'Create User';
$pageDescription = 'Create a new AHPTC user account with role, security and profile details.';
$adminRole = 'superadmin';
$contentClass = 'sa-user-form-page';
$componentCss = ['user-profile'];
$pageScripts = ['user-form'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Users', 'url' => Url::to('admin/superadmin/users.php')],
    ['label' => 'Create User'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<form class="sa-user-editor" method="post" action="<?= Security::e(Url::to('admin/superadmin/user-create.php')) ?>" enctype="multipart/form-data" novalidate>
  <?= Csrf::field($csrfForm) ?>

  <section class="sa-projects-hero sa-user-hero card">
    <div>
      <span class="sa-panel-label"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> New Workspace Account</span>
      <h2>Create user profile</h2>
      <p>Add secure portal access, role permissions and a complete profile used across AHPTC content areas.</p>
    </div>
    <div class="sa-action-grid">
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Users</a>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Create User</button>
    </div>
  </section>

  <div class="sa-editor-layout">
    <div class="sa-editor-main">
      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Identity & Contact</h2>
            <p class="card__subtitle">The name, email and contact details used throughout the portal and AHPTC records.</p>
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
        <?php render_user_selected_project_summary($projects, $selectedProjectIds, (string)($rolesById[(int)$values['role_id']]['slug'] ?? '')); ?>
      </section>

      <section class="card sa-form-card sa-user-form-card sa-assignment-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Project Access</h2>
            <p class="card__subtitle">Attach this user to the project sites they should see after sign-in.</p>
          </div>
        </div>
        <?php render_user_project_assignment_picker($projects, $selectedProjectIds); ?>
        <?= sa_user_error($errors, 'project_ids') ?>
      </section>

      <section class="card sa-form-card sa-user-form-card sa-assignment-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Work Location Access</h2>
            <p class="card__subtitle">Assign office-based interns to internal locations such as Headquarters Office. These locations do not appear on the public project tracker.</p>
          </div>
        </div>
        <?php render_user_work_location_picker($workLocations, $selectedWorkLocationIds, (string)($values['role_slug'] ?? '')); ?>
      </section>

      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Access & Security</h2>
            <p class="card__subtitle">Assign the correct dashboard role and initial password. Suspended users cannot sign in.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <label class="form-field">
            <span class="form-label">Role <strong>*</strong></span>
            <select class="form-select <?= isset($errors['role_id']) ? 'is-error' : '' ?>" name="role_id" required data-role-select>
              <option value="">Choose role</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?= Security::e((string)$role['id']) ?>" data-role-slug="<?= Security::e((string)$role['slug']) ?>" <?= (string)$values['role_id'] === (string)$role['id'] ? 'selected' : '' ?> <?= $superadminExists && ($role['slug'] ?? '') === 'superadmin' ? 'disabled' : '' ?>><?= Security::e(role_label((string)$role['slug'])) ?><?= $superadminExists && ($role['slug'] ?? '') === 'superadmin' ? ' (already configured)' : '' ?></option>
              <?php endforeach; ?>
            </select>
            <?= sa_user_error($errors, 'role_id') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Status <strong>*</strong></span>
            <select class="form-select <?= isset($errors['status']) ? 'is-error' : '' ?>" name="status" required>
              <?php foreach ($statusOptions as $status): ?>
                <option value="<?= Security::e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
              <?php endforeach; ?>
            </select>
            <?= sa_user_error($errors, 'status') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Password <strong>*</strong></span>
            <div class="sa-password-field">
              <input class="form-input <?= isset($errors['password']) ? 'is-error' : '' ?>" type="password" name="password" autocomplete="new-password" required minlength="8" data-password-input>
              <button class="btn btn--icon btn--outline" type="button" data-password-toggle aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
            <span class="form-hint">Minimum 8 characters.</span>
            <?= sa_user_error($errors, 'password') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Confirm password <strong>*</strong></span>
            <div class="sa-password-field">
              <input class="form-input <?= isset($errors['password_confirm']) ? 'is-error' : '' ?>" type="password" name="password_confirm" autocomplete="new-password" required minlength="8" data-password-input>
              <button class="btn btn--icon btn--outline" type="button" data-password-toggle aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
            <?= sa_user_error($errors, 'password_confirm') ?>
          </label>
          <label class="form-field sa-toggle-field">
            <span class="form-label">Account email</span>
            <span class="sa-checkbox-line">
              <input type="checkbox" name="send_invite" value="1" <?= (int)$values['send_invite'] === 1 ? 'checked' : '' ?>>
              <span>Email this user a secure password setup link and login details.</span>
            </span>
            <span class="form-hint">The email includes their role, login email, project access summary and one-time password setup link.</span>
          </label>
        </div>
      </section>

      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Profile Media & Bio</h2>
            <p class="card__subtitle">Profile photo and biography keep the user identity consistent across AHPTC modules.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <label class="form-field">
            <span class="form-label">Profile photo</span>
            <span class="sa-avatar-upload" data-avatar-drop>
              <input type="file" name="avatar_upload" accept="image/jpeg,image/png,image/webp,image/gif" data-avatar-input>
              <span class="sa-avatar-upload__preview" data-avatar-preview><?= Security::e(user_initials($values)) ?></span>
              <strong>Upload profile photo</strong>
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
      $selectedRole = $rolesById[(int)$values['role_id']] ?? [];
      $previewValues = $values;
      $previewUser = [
          'role_slug' => (string)($selectedRole['slug'] ?? ''),
          'role_name' => (string)($selectedRole['name'] ?? 'Staff'),
          'status' => $values['status'],
      ];
      $previewProjects = $selectedPreviewProjects;
      $previewMode = 'create';
      include __DIR__ . '/../../app/partials/admin/user-preview-card.php';
      ?>
    </aside>
  </div>

  <div class="sa-form-actionbar">
    <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> CSRF protected user creation</span>
    <div>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/users.php')) ?>">Cancel</a>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Create User</button>
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

function render_user_project_assignment_picker(array $projects, array $selectedProjectIds): void
{
    $grouped = [];
    foreach ($projects as $project) {
        $constituency = trim((string)($project['constituency_name'] ?? '')) ?: 'Unassigned Constituency';
        $grouped[$constituency][] = $project;
    }
?>
  <div class="sa-assignment-picker" data-assignment-picker>
    <div class="sa-assignment-mode sa-assignment-mode--empty" data-assignment-empty-state>
      <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
      <div>
        <strong>Choose a role first</strong>
        <span>Project access options will appear here after you choose the user role in Access &amp; Security.</span>
      </div>
    </div>
    <div class="sa-assignment-mode sa-assignment-mode--all" data-assignment-all-access hidden>
      <i class="fa-solid fa-crown" aria-hidden="true"></i>
      <div>
        <strong>All project sites</strong>
        <span>County Director accounts automatically receive every project site. Manual project selection is not required.</span>
      </div>
    </div>
    <div class="sa-assignment-mode sa-assignment-mode--finance" data-assignment-finance-access hidden>
      <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
      <div>
        <strong>Finance portfolio visibility</strong>
        <span>Finance users can view project financial workflows without being assigned as site operators.</span>
      </div>
    </div>
    <div class="sa-assignment-picker__tools">
      <span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Select project access</span>
      <label class="sa-assignment-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" placeholder="Search project sites..." data-assignment-search></label>
      <div>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-show-selected aria-pressed="false">Selected only</button>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-select-all>Select all</button>
        <button class="btn btn--outline btn--sm" type="button" data-assignment-clear>Clear</button>
      </div>
    </div>
    <div class="sa-assignment-groups">
<?php foreach ($grouped as $constituency => $items): ?>
      <section class="sa-assignment-group" data-assignment-group>
        <div class="sa-assignment-group__head">
          <span><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> <?= Security::e($constituency) ?></span>
          <button class="btn btn--outline btn--sm" type="button" data-assignment-group-toggle>Select constituency</button>
        </div>
        <div class="sa-assignment-grid">
<?php foreach ($items as $project): ?>
<?php $projectId = (int)$project['id']; ?>
          <label class="sa-assignment-option">
            <input type="checkbox" name="project_ids[]" value="<?= Security::e((string)$projectId) ?>" <?= in_array($projectId, $selectedProjectIds, true) ? 'checked' : '' ?> data-project-name="<?= Security::e((string)$project['name']) ?>">
            <span>
              <strong><?= Security::e((string)$project['name']) ?></strong>
              <small>
                <?= Security::e(status_label((string)($project['status'] ?? 'active'))) ?>
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
    <p class="form-hint">Clerks and interns may only be assigned to <strong>one</strong> project site (enforced on save). Managers, consultants and contractors may hold multiple sites.</p>
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
