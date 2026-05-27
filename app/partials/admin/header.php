<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$adminRole = $adminRole ?? Auth::role() ?? 'staff';
$pageKicker = $pageKicker ?? role_label((string)$adminRole);
$headerActions = $headerActions ?? [];
$userName = current_user_name();
$userInitials = current_user_initials();
$userAvatar = current_user_avatar();
$userTitle = current_user_title();
$profileUrl = Auth::role() === 'superadmin' && Auth::id()
    ? Url::to('admin/superadmin/user-edit.php?id=' . (int)Auth::id())
    : Url::to('admin/profile.php');
?>
<header class="admin-header">
  <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Open navigation menu" aria-controls="adminSidebar" aria-expanded="false">
    <i class="fa-solid fa-bars" aria-hidden="true"></i>
  </button>

  <div class="admin-header-spacer" aria-hidden="true"></div>

  <div class="admin-header-actions">
<?php foreach ((array)$headerActions as $action): ?>
<?php
    $label = (string)($action['label'] ?? '');
    $href = (string)($action['href'] ?? '#');
    $icon = (string)($action['icon'] ?? '');
    $class = (string)($action['class'] ?? 'btn btn--outline btn--sm');
?>
<?php if ($label !== ''): ?>
    <a class="<?= Security::e($class) ?>" href="<?= Security::e($href) ?>">
<?php if ($icon !== ''): ?>
      <i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i>
<?php endif; ?>
      <span><?= Security::e($label) ?></span>
    </a>
<?php endif; ?>
<?php endforeach; ?>

<?php include __DIR__ . '/notifications-bell.php'; ?>

    <div class="admin-user-menu" data-admin-dropdown>
      <button type="button" class="admin-user-button" data-admin-dropdown-toggle aria-haspopup="true" aria-expanded="false">
        <span class="admin-user-avatar" aria-hidden="true">
<?php if ($userAvatar !== ''): ?>
          <img src="<?= Security::e(Url::asset($userAvatar)) ?>" alt="">
<?php else: ?>
          <?= Security::e($userInitials) ?>
<?php endif; ?>
        </span>
        <span class="admin-user-copy">
          <strong><?= Security::e($userName) ?></strong>
          <small><?= Security::e($userTitle) ?></small>
        </span>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>

      <div class="admin-user-dropdown" data-admin-dropdown-menu>
        <a href="<?= Security::e($profileUrl) ?>">
          <i class="fa-solid fa-user" aria-hidden="true"></i>
          <span>My Profile</span>
        </a>
        <a href="<?= Security::e($profileUrl) ?>">
          <i class="fa-solid fa-user-gear" aria-hidden="true"></i>
          <span>Profile Settings</span>
        </a>
        <div class="admin-user-dropdown-divider" role="separator"></div>
        <a href="<?= Security::e(Url::to('index.php')) ?>" target="_blank" rel="noopener noreferrer">
          <i class="fa-solid fa-globe" aria-hidden="true"></i>
          <span>Public Website</span>
        </a>
        <a href="<?= Security::e(Url::to('admin/logout.php')) ?>" class="is-danger">
          <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </div>
</header>
