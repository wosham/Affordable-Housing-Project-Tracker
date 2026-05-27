<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_user_form';
$userId = Security::cleanInt($_GET['id'] ?? 0);
$user = $userId > 0 ? User::findDetailed($userId) : null;

if (!$user) {
    Session::flash('error', 'User account could not be found.');
    Response::redirect(Url::to('admin/superadmin/users.php'));
}

$roles = Database::fetchAll('SELECT * FROM roles ORDER BY id ASC');
$rolesById = [];
foreach ($roles as $role) {
    $rolesById[(int)$role['id']] = $role;
}

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
];
$isProtectedSuperadminView = ($user['role_slug'] ?? '') === 'superadmin';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/user-edit.php?id=' . $userId));
    }

    $values = user_form_values($_POST, $values);
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
        $errors['email'] = 'A different user already uses this email.';
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
        $errors['role_id'] = 'The super administrator account is protected and must remain active.';
    }
    if ($createsSecondSuperadmin) {
        $errors['role_id'] = 'Only one super administrator account is allowed.';
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
        if ($avatarPath !== null && $oldAvatar !== '' && $oldAvatar !== $avatarPath) {
            user_delete_avatar_file($oldAvatar);
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
        Logger::log('update', 'users', $userId, ['email' => $values['email']]);
        Session::flash('status', 'User account updated successfully.');
        Response::redirect(Url::to('admin/superadmin/user-edit.php?id=' . $userId));
    }
}

$pageTitle = 'Edit User';
$pageDescription = 'Update AHPTC user access, status, profile photo and profile details.';
$adminRole = 'superadmin';
$contentClass = 'sa-user-form-page';
$pageScripts = ['user-form'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
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
    <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e($rolesById[(int)$values['role_id']]['name'] ?? 'No role') ?></strong><span class="stat-widget__label">Current Role</span><small class="stat-widget__trend">Dashboard permission group</small></span></article>
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
          <?= user_field('Phone number', 'phone', $values, $errors, false, 'tel') ?>
          <?= user_field('Job title', 'job_title', $values, $errors, false, 'text', 'County Director, Resident Engineer...') ?>
          <?= user_field('Department / organisation', 'department', $values, $errors, false, 'text', 'County Housing Department') ?>
        </div>
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
            <input class="form-input" type="text" value="<?= Security::e($rolesById[(int)$values['role_id']]['name'] ?? 'Super Administrator') ?>" disabled>
            <span class="form-hint">System owner role is locked.</span>
<?php else: ?>
            <select class="form-select <?= isset($errors['role_id']) ? 'is-error' : '' ?>" name="role_id" required>
              <option value="">Choose role</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?= Security::e((string)$role['id']) ?>" <?= (string)$values['role_id'] === (string)$role['id'] ? 'selected' : '' ?>><?= Security::e($role['name']) ?></option>
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
      <section class="card sa-user-preview">
        <div class="sa-user-preview__top">
          <span class="sa-user-preview__avatar" data-profile-avatar><?php if ($values['avatar'] !== ''): ?><img src="<?= Security::e(Url::asset($values['avatar'])) ?>" alt=""><?php else: ?><?= Security::e(user_initials($values)) ?><?php endif; ?></span>
          <span class="badge <?= Security::e(status_badge_class($values['status'])) ?>" data-profile-status><?= Security::e(status_label($values['status'])) ?></span>
        </div>
        <div class="sa-user-preview__body">
          <span class="sa-panel-label"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Live profile preview</span>
          <h3 data-profile-name><?= Security::e(trim($values['first_name'] . ' ' . $values['last_name']) ?: 'User') ?></h3>
          <p data-profile-title><?= Security::e($values['job_title'] ?: 'Role title will appear here') ?></p>
          <dl class="sa-preview-list">
            <div><dt>Email</dt><dd data-profile-email><?= Security::e($values['email'] ?: 'email@example.com') ?></dd></div>
            <div><dt>Department</dt><dd data-profile-department><?= Security::e($values['department'] ?: 'Not set') ?></dd></div>
            <div><dt>Created</dt><dd><?= Security::e(format_date($user['created_at'] ?? null)) ?></dd></div>
          </dl>
        </div>
      </section>
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
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
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
