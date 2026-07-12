<?php
$basePath = $basePath ?? '';
if (class_exists('Auth') && Auth::check()):
  $publicUser = Auth::user() ?? [];
  $publicUserName = trim((string)($publicUser['name'] ?? '')) ?: 'Staff User';
  $publicUserRole = function_exists('role_label') ? role_label((string)($publicUser['role'] ?? '')) : (string)($publicUser['role_name'] ?? 'Staff');
  $publicUserInitials = function_exists('user_initials') ? user_initials($publicUser) : strtoupper(substr($publicUserName, 0, 1));
  $publicUserAvatar = trim((string)($publicUser['avatar'] ?? ''));
  $publicLink = static fn (string $path): string => function_exists('public_url') ? public_url($path) : $basePath . $path;
?>
<div class="public-user-bar" role="region" aria-label="Logged in public site tools">
  <div class="container public-user-bar__inner">
    <nav class="public-user-bar__links" aria-label="Quick public links">
      <a href="<?= htmlspecialchars($publicLink('contact.php'), ENT_QUOTES, 'UTF-8') ?>">Contact</a>
      <a href="<?= htmlspecialchars($publicLink('about.php'), ENT_QUOTES, 'UTF-8') ?>">About</a>
    </nav>
    <details class="public-user-menu">
      <summary aria-label="Open account menu">
        <span class="public-user-menu__avatar" aria-hidden="true">
<?php if ($publicUserAvatar !== ''): ?>
          <img src="<?= htmlspecialchars(function_exists('public_asset') ? public_asset($publicUserAvatar) : $publicUserAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" onerror="this.style.display='none'">
<?php else: ?>
          <?= htmlspecialchars($publicUserInitials, ENT_QUOTES, 'UTF-8') ?>
<?php endif; ?>
        </span>
        <span class="public-user-menu__copy"><strong><?= htmlspecialchars($publicUserName, ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($publicUserRole, ENT_QUOTES, 'UTF-8') ?></small></span>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </summary>
      <div class="public-user-menu__dropdown" role="menu">
        <a href="<?= htmlspecialchars($publicLink('admin/profile.php'), ENT_QUOTES, 'UTF-8') ?>" role="menuitem"><i class="fa-solid fa-user" aria-hidden="true"></i> View Profile</a>
        <a href="<?= htmlspecialchars($publicLink('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" role="menuitem"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Go to Dashboard</a>
        <a href="<?= htmlspecialchars($publicLink('admin/logout.php'), ENT_QUOTES, 'UTF-8') ?>" role="menuitem" class="public-user-menu__logout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout</a>
      </div>
    </details>
  </div>
</div>
<?php endif; ?>