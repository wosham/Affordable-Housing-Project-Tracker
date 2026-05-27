<?php
$notificationUserId = Auth::id();
$notifications = [];
$unreadCount = 0;

$notificationIcon = static function (?string $type): string {
    return match (strtolower(trim((string)$type))) {
        'ipc' => 'fa-file-invoice',
        'message' => 'fa-comments',
        'attendance' => 'fa-calendar-check',
        'milestone' => 'fa-bullseye',
        'contact' => 'fa-inbox',
        'rfi' => 'fa-circle-question',
        default => 'fa-bell',
    };
};

$notificationHref = static function (?string $link): string {
    $link = trim((string)$link);

    if ($link === '') {
        return '#';
    }

    if (str_starts_with($link, Url::basePath())) {
        return $link;
    }

    if (preg_match('#^(admin|api|uploads|secure-uploads|legal)/#', $link) || str_ends_with($link, '.php')) {
        return Url::to($link);
    }

    return '#';
};

if ($notificationUserId !== null) {
    try {
        $countRow = Database::fetch(
            'SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0',
            [$notificationUserId]
        );
        $unreadCount = (int)($countRow['total'] ?? 0);

        $notifications = Database::fetchAll(
            'SELECT id, type, title, body, link, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 10',
            [$notificationUserId]
        );
    } catch (Throwable) {
        $notifications = [];
        $unreadCount = 0;
    }
}
?>
<div class="admin-notifications" data-notifications>
  <button type="button" class="notification-button" data-notification-toggle aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
    <i class="fa-solid fa-bell" aria-hidden="true"></i>
<?php if ($unreadCount > 0): ?>
    <span class="notification-count" data-notification-count aria-label="<?= Security::e((string)$unreadCount) ?> unread notifications"><?= Security::e((string)min($unreadCount, 99)) ?></span>
<?php else: ?>
    <span class="notification-count is-empty" data-notification-count hidden>0</span>
<?php endif; ?>
  </button>

  <div class="notification-dropdown" data-notification-dropdown>
    <div class="notification-dropdown-header">
      <strong>Notifications</strong>
      <button type="button" class="notification-mark-all" data-notification-mark-all<?= $unreadCount === 0 ? ' disabled' : '' ?>>Mark all read</button>
    </div>

    <div class="notification-list" data-notification-list>
<?php if ($notifications === []): ?>
      <div class="notification-empty">
        <i class="fa-solid fa-bell-slash" aria-hidden="true"></i>
        <span>No notifications yet</span>
      </div>
<?php else: ?>
<?php foreach ($notifications as $notification): ?>
<?php
    $isUnread = (int)($notification['is_read'] ?? 0) === 0;
    $itemClass = 'notification-item' . ($isUnread ? ' is-unread' : '');
    $href = $notificationHref($notification['link'] ?? '');
?>
      <a class="<?= Security::e($itemClass) ?>" href="<?= Security::e($href) ?>" data-notification-id="<?= Security::e((string)($notification['id'] ?? '')) ?>">
        <span class="notification-item-icon">
          <i class="fa-solid <?= Security::e($notificationIcon($notification['type'] ?? '')) ?>" aria-hidden="true"></i>
        </span>
        <span class="notification-item-copy">
          <strong><?= Security::e($notification['title'] ?? 'Notification') ?></strong>
<?php if (!empty($notification['body'])): ?>
          <small><?= Security::e(safe_truncate((string)$notification['body'], 90)) ?></small>
<?php endif; ?>
          <em><?= Security::e(time_ago($notification['created_at'] ?? null)) ?></em>
        </span>
      </a>
<?php endforeach; ?>
<?php endif; ?>
    </div>
  </div>
</div>
