<?php

require_once __DIR__ . '/../app/core/bootstrap.php';
Guard::auth();

$csrfForm = 'admin_profile_form';
$userId = (int)(Auth::id() ?? 0);
$user = $userId > 0 ? User::findDetailed($userId) : null;

if (!$user) {
    Session::flash('error', 'Your profile could not be loaded. Please sign in again.');
    Response::redirect(Url::to('admin/logout.php'));
}

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
];

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/profile.php'));
    }

    $values = profile_values($_POST, $values);
    $errors = profile_validate($values);

    if ($values['email'] !== '' && User::emailExists($values['email'], $userId)) {
        $errors['email'] = 'A different user already uses this email.';
    }

    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors['password'] = 'Enter a password with at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
    }

    $oldAvatar = (string)($user['avatar'] ?? '');
    $avatarPath = profile_store_avatar($_FILES['avatar_upload'] ?? null, $errors);
    if ($avatarPath !== null) {
        $values['avatar'] = $avatarPath;
    }

    if ($errors === []) {
        $payload = [
            'first_name' => $values['first_name'],
            'last_name' => $values['last_name'],
            'email' => $values['email'],
            'phone' => $values['phone'],
            'job_title' => $values['job_title'],
            'department' => $values['department'],
            'bio' => $values['bio'] !== '' ? $values['bio'] : null,
            'avatar' => $values['avatar'] !== '' ? $values['avatar'] : null,
        ];

        if ($password !== '') {
            $payload['password_hash'] = User::hashPassword($password);
        }

        User::update($userId, $payload);
        if ($avatarPath !== null && $oldAvatar !== '' && $oldAvatar !== $avatarPath) {
            profile_delete_avatar_file($oldAvatar);
        }

        Auth::refresh([
            'id' => $userId,
            'first_name' => $values['first_name'],
            'last_name' => $values['last_name'],
            'name' => trim($values['first_name'] . ' ' . $values['last_name']),
            'email' => $values['email'],
            'avatar' => $values['avatar'],
            'job_title' => $values['job_title'],
        ]);

        Logger::log('update', 'profile', $userId, ['email' => $values['email']]);
        Session::flash('status', 'Profile updated successfully.');
        Response::redirect(Url::to('admin/profile.php'));
    }
}

$pageTitle = 'My Profile';
$pageDescription = 'Update your contact details, profile photo and password.';
$adminRole = Auth::role() ?? 'staff';
$contentClass = 'sa-user-form-page';
$componentCss = ['user-profile'];
$pageScripts = ['user-form'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'My Profile'],
];

include __DIR__ . '/../app/partials/admin/shell-start.php';
?>

<form class="sa-user-editor" method="post" action="<?= Security::e(Url::to('admin/profile.php')) ?>" enctype="multipart/form-data" novalidate>
  <?= Csrf::field($csrfForm) ?>

  <section class="sa-projects-hero sa-user-hero card">
    <div>
      <span class="sa-panel-label"><i class="fa-solid fa-user" aria-hidden="true"></i> Personal profile</span>
      <h2><?= Security::e(trim($values['first_name'] . ' ' . $values['last_name'])) ?></h2>
      <p>Update your own contact details, photo, biography and password. Role and account status are managed by the system administrator.</p>
    </div>
    <div class="sa-action-grid">
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/index.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard</a>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Profile</button>
    </div>
  </section>

  <div class="sa-editor-layout">
    <div class="sa-editor-main">
      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Identity & Contact</h2>
            <p class="card__subtitle">These details identify you across dashboard records, approvals and messages.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <?= profile_field('First name', 'first_name', $values, $errors, true) ?>
          <?= profile_field('Last name', 'last_name', $values, $errors, true) ?>
          <?= profile_field('Gmail address', 'email', $values, $errors, true, 'email') ?>
          <?= profile_field('Phone number', 'phone', $values, $errors, true, 'tel', '+254712345678') ?>
          <?= profile_field('Job title', 'job_title', $values, $errors, true, 'text', 'Clerk of Works') ?>
          <?= profile_field('Department / organisation', 'department', $values, $errors, true, 'text', 'Site Operations') ?>
        </div>
      </section>

      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Password</h2>
            <p class="card__subtitle">Leave password fields blank if you do not want to change your password.</p>
          </div>
        </div>
        <div class="form-grid form-grid--2">
          <label class="form-field">
            <span class="form-label">New password</span>
            <div class="sa-password-field">
              <input class="form-input <?= isset($errors['password']) ? 'is-error' : '' ?>" type="password" name="password" autocomplete="new-password" minlength="8" data-password-input>
              <button class="btn btn--icon btn--outline" type="button" data-password-toggle aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
            <?= profile_error($errors, 'password') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Confirm new password</span>
            <div class="sa-password-field">
              <input class="form-input <?= isset($errors['password_confirm']) ? 'is-error' : '' ?>" type="password" name="password_confirm" autocomplete="new-password" minlength="8" data-password-input>
              <button class="btn btn--icon btn--outline" type="button" data-password-toggle aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
            </div>
            <?= profile_error($errors, 'password_confirm') ?>
          </label>
        </div>
      </section>

      <section class="card sa-form-card sa-user-form-card">
        <div class="card__header">
          <div>
            <h2 class="card__title">Profile Media & Bio</h2>
            <p class="card__subtitle">Upload your profile image and keep your short profile note current.</p>
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
            <input class="form-input sa-path-input" type="text" name="avatar" value="<?= Security::e($values['avatar']) ?>" readonly data-avatar-path>
            <?= profile_error($errors, 'avatar_upload') ?>
          </label>
          <label class="form-field">
            <span class="form-label">Short biography</span>
            <textarea class="form-textarea" name="bio" rows="6" data-profile-bio placeholder="Brief professional profile."><?= Security::e($values['bio']) ?></textarea>
          </label>
        </div>
      </section>
    </div>

    <aside class="sa-editor-aside">
      <section class="card sa-user-preview">
        <div class="sa-user-preview__top">
          <span class="sa-user-preview__avatar" data-profile-avatar><?php if ($values['avatar'] !== ''): ?><img src="<?= Security::e(Url::asset($values['avatar'])) ?>" alt=""><?php else: ?><?= Security::e(user_initials($values)) ?><?php endif; ?></span>
          <span class="badge <?= Security::e(status_badge_class($user['status'] ?? 'active')) ?>"><?= Security::e(status_label($user['status'] ?? 'active')) ?></span>
        </div>
        <div class="sa-user-preview__body">
          <span class="sa-panel-label"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Live profile preview</span>
          <h3 data-profile-name><?= Security::e(trim($values['first_name'] . ' ' . $values['last_name'])) ?></h3>
          <p data-profile-title><?= Security::e($values['job_title']) ?></p>
          <dl class="sa-preview-list">
            <div><dt>Role</dt><dd><?= Security::e($user['role_name'] ?? role_label(Auth::role())) ?></dd></div>
            <div><dt>Email</dt><dd data-profile-email><?= Security::e($values['email']) ?></dd></div>
            <div><dt>Phone</dt><dd><?= Security::e($values['phone']) ?></dd></div>
            <div><dt>Department</dt><dd data-profile-department><?= Security::e($values['department']) ?></dd></div>
          </dl>
        </div>
      </section>
    </aside>
  </div>

  <div class="sa-form-actionbar">
    <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Your role and status are administrator-managed</span>
    <div>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/index.php')) ?>">Cancel</a>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Profile</button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../app/partials/admin/shell-end.php'; ?>

<?php
function profile_values(array $source, array $defaults): array
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
    ];
}

function profile_validate(array $values): array
{
    $errors = [];
    foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'job_title' => 'Job title', 'department' => 'Department'] as $field => $label) {
        if (trim((string)$values[$field]) === '') {
            $errors[$field] = $label . ' is required.';
        }
    }
    if (!profile_valid_gmail($values['email'])) {
        $errors['email'] = 'Enter a valid Gmail address.';
    }
    if (!profile_valid_kenyan_phone($values['phone'])) {
        $errors['phone'] = 'Enter a Kenyan phone number in +2547XXXXXXXX or +2541XXXXXXXX format.';
    }
    return $errors;
}

function profile_valid_gmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strtolower(substr($email, -10)) === '@gmail.com';
}

function profile_valid_kenyan_phone(string $phone): bool
{
    return (bool)preg_match('/^\+254[17][0-9]{8}$/', trim($phone));
}

function profile_store_avatar(?array $file, array &$errors): ?string
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
    $uploadDir = dirname(__DIR__) . '/uploads/profiles';
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

function profile_delete_avatar_file(string $path): void
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, 'uploads/profiles/')) {
        return;
    }
    $root = realpath(dirname(__DIR__));
    $file = $root !== false ? realpath($root . '/' . $path) : false;
    $profileDir = $root !== false ? realpath($root . '/uploads/profiles') : false;
    if ($file !== false && $profileDir !== false && str_starts_with($file, $profileDir . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}

function profile_field(string $label, string $name, array $values, array $errors, bool $required = false, string $type = 'text', string $placeholder = ''): string
{
    $errorClass = isset($errors[$name]) ? ' is-error' : '';
    $requiredAttr = $required ? ' required' : '';
    return '<label class="form-field"><span class="form-label">' . Security::e($label) . ($required ? ' <strong>*</strong>' : '') . '</span><input class="form-input' . $errorClass . '" type="' . Security::e($type) . '" name="' . Security::e($name) . '" value="' . Security::e($values[$name] ?? '') . '" placeholder="' . Security::e($placeholder) . '"' . $requiredAttr . ' data-profile-field="' . Security::e($name) . '">' . profile_error($errors, $name) . '</label>';
}

function profile_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<span class="form-error">' . Security::e($errors[$field]) . '</span>' : '';
}
