<?php
$previewValues = is_array($previewValues ?? null) ? $previewValues : [];
$previewUser = is_array($previewUser ?? null) ? $previewUser : [];
$previewProjects = is_array($previewProjects ?? null) ? $previewProjects : [];
$previewMode = (string)($previewMode ?? 'profile');

$previewName = trim((string)($previewValues['first_name'] ?? '') . ' ' . (string)($previewValues['last_name'] ?? ''));
$previewName = $previewName !== '' ? $previewName : ($previewMode === 'create' ? 'New User' : 'User');
$previewEmail = trim((string)($previewValues['email'] ?? ''));
$previewPhone = trim((string)($previewValues['phone'] ?? ''));
$previewTitle = trim((string)($previewValues['job_title'] ?? ''));
$previewDepartment = trim((string)($previewValues['department'] ?? ''));
$previewAvatar = trim((string)($previewValues['avatar'] ?? ''));
$previewStatus = (string)($previewValues['status'] ?? $previewUser['status'] ?? 'active');
$previewRole = (string)($previewUser['role_name'] ?? role_label((string)($previewUser['role_slug'] ?? $previewUser['role'] ?? 'staff')));
$previewProjectCount = count($previewProjects);
$previewProjectLabel = $previewProjectCount === 0
    ? 'No sites attached'
    : ($previewProjectCount === 1 ? '1 project site' : format_number($previewProjectCount) . ' project sites');

if (strtolower((string)($previewUser['role_slug'] ?? $previewUser['role'] ?? '')) === 'superadmin') {
    $previewProjectLabel = 'All project sites';
} elseif (strtolower((string)($previewUser['role_slug'] ?? $previewUser['role'] ?? '')) === 'finance') {
    $previewProjectLabel = 'Finance portfolio';
}

$previewCreated = format_date($previewUser['created_at'] ?? null);
$previewLastLogin = time_ago($previewUser['last_login'] ?? null);
?>
<section class="card sa-user-preview">
  <div class="sa-user-preview__top">
    <span class="sa-user-preview__avatar" data-profile-avatar><?php if ($previewAvatar !== ''): ?><img src="<?= Security::e(Url::asset($previewAvatar)) ?>" alt=""><?php else: ?><?= Security::e(user_initials($previewValues)) ?><?php endif; ?></span>
    <div class="sa-user-preview__badges">
      <span class="badge <?= Security::e(status_badge_class($previewStatus)) ?>" data-profile-status><?= Security::e(status_label($previewStatus)) ?></span>
      <span class="badge badge--neutral" data-profile-role><?= Security::e($previewRole) ?></span>
    </div>
  </div>
  <div class="sa-user-preview__body">
    <span class="sa-panel-label"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Live profile preview</span>
    <h3 data-profile-name><?= Security::e($previewName) ?></h3>
    <p data-profile-title><?= Security::e($previewTitle !== '' ? $previewTitle : 'Role title will appear here') ?></p>

    <div class="sa-preview-access">
      <span><i class="fa-solid fa-building-circle-check" aria-hidden="true"></i> <?= Security::e($previewProjectLabel) ?></span>
    </div>

    <dl class="sa-preview-list">
      <div class="sa-preview-list__row">
        <dt>Email</dt>
        <dd>
          <span class="sa-preview-value" data-profile-email title="<?= Security::e($previewEmail !== '' ? $previewEmail : 'email@example.com') ?>"><?= Security::e($previewEmail !== '' ? $previewEmail : 'email@example.com') ?></span>
        </dd>
      </div>
      <div class="sa-preview-list__row">
        <dt>Phone</dt>
        <dd>
          <span data-profile-phone><?= Security::e($previewPhone !== '' ? $previewPhone : 'Not set') ?></span>
        </dd>
      </div>
      <div class="sa-preview-list__row"><dt>Department</dt><dd data-profile-department><?= Security::e($previewDepartment !== '' ? $previewDepartment : 'Not set') ?></dd></div>
      <div class="sa-preview-list__row"><dt>Created</dt><dd><?= Security::e($previewCreated) ?></dd></div>
      <div class="sa-preview-list__row"><dt>Last login</dt><dd><?= Security::e($previewLastLogin) ?></dd></div>
    </dl>
  </div>
</section>
