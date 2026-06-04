<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_user_form';
$roles = Database::fetchAll('SELECT * FROM roles ORDER BY id ASC');
$rolesById = [];
foreach ($roles as $role) {
    $rolesById[(int)$role['id']] = $role;
}
$superadminExists = User::superadminCount() >= 1;

$statusOptions = ['active', 'inactive', 'suspended'];
$errors = [];
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
];

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/user-create.php'));
    }

    $values = user_form_values($_POST, $values);
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

    $submittedRole = $rolesById[(int)$values['role_id']]['slug'] ?? '';
    if ($submittedRole === 'superadmin' && User::superadminCount() >= 1) {
        $errors['role_id'] = 'Only one super administrator account is allowed.';
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

        Logger::log('create', 'users', (int)$userId, ['email' => $values['email']]);
        Session::flash('status', 'User account created successfully.');
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
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
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
            <select class="form-select <?= isset($errors['role_id']) ? 'is-error' : '' ?>" name="role_id" required>
              <option value="">Choose role</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?= Security::e((string)$role['id']) ?>" <?= (string)$values['role_id'] === (string)$role['id'] ? 'selected' : '' ?> <?= $superadminExists && ($role['slug'] ?? '') === 'superadmin' ? 'disabled' : '' ?>><?= Security::e($role['name']) ?><?= $superadminExists && ($role['slug'] ?? '') === 'superadmin' ? ' (already configured)' : '' ?></option>
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
      <section class="card sa-user-preview">
        <div class="sa-user-preview__top">
          <span class="sa-user-preview__avatar" data-profile-avatar><?= Security::e(user_initials($values)) ?></span>
          <span class="badge <?= Security::e(status_badge_class($values['status'])) ?>" data-profile-status><?= Security::e(status_label($values['status'])) ?></span>
        </div>
        <div class="sa-user-preview__body">
          <span class="sa-panel-label"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Live profile preview</span>
          <h3 data-profile-name><?= Security::e(trim($values['first_name'] . ' ' . $values['last_name']) ?: 'New User') ?></h3>
          <p data-profile-title><?= Security::e($values['job_title'] ?: 'Role title will appear here') ?></p>
          <dl class="sa-preview-list">
            <div><dt>Email</dt><dd data-profile-email><?= Security::e($values['email'] ?: 'email@example.com') ?></dd></div>
            <div><dt>Department</dt><dd data-profile-department><?= Security::e($values['department'] ?: 'Not set') ?></dd></div>
          </dl>
        </div>
      </section>
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
        'email' => Security::cleanEmail((string)($source['email'] ?? $defaults['email'])),
        'phone' => Security::cleanString((string)($source['phone'] ?? $defaults['phone'])),
        'job_title' => Security::cleanString((string)($source['job_title'] ?? $defaults['job_title'])),
        'department' => Security::cleanString((string)($source['department'] ?? $defaults['department'])),
        'bio' => trim(strip_tags((string)($source['bio'] ?? $defaults['bio']))),
        'avatar' => Security::cleanString((string)($source['avatar'] ?? $defaults['avatar'])),
        'role_id' => Security::cleanInt($source['role_id'] ?? $defaults['role_id']),
        'status' => Security::cleanString((string)($source['status'] ?? $defaults['status'])),
        'is_public' => 1,
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
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strtolower(substr($values['email'], -10)) !== '@gmail.com') {
        $errors['email'] = 'Enter a valid Gmail address.';
    }
    if (!preg_match('/^\+254[17][0-9]{8}$/', trim((string)$values['phone']))) {
        $errors['phone'] = 'Enter a Kenyan phone number in +2547XXXXXXXX or +2541XXXXXXXX format.';
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
