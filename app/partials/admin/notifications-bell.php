<?php
$notificationUserId = (int)(Auth::id() ?? 0);
$notifications = [];
$unreadCount = 0;

if ($notificationUserId > 0) {
    try {
        $unreadCount = Notification::unreadCount($notificationUserId);
        $notifications = array_map([Notification::class, 'payload'], Notification::forUser($notificationUserId, 10));
    } catch (Throwable $exception) {
        Logger::error('Notification bell preload failed', [
            'user_id' => $notificationUserId,
            'error' => $exception->getMessage(),
        ]);
        $notifications = [];
        $unreadCount = 0;
    }
}
?>
<div class="admin-notifications" data-notifications>
  <button type="button" class="notification-button" data-notification-toggle aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
    <i class="fa-solid fa-bell" aria-hidden="true"></i>
    <span class="notification-count<?= $unreadCount === 0 ? ' is-empty' : '' ?>" data-notification-count aria-label="<?= Security::e((string)$unreadCount) ?> unread notifications"<?= $unreadCount === 0 ? ' hidden' : '' ?>><?= Security::e((string)min($unreadCount, 99)) ?></span>
  </button>

  <div class="notification-dropdown" data-notification-dropdown>
    <div class="notification-dropdown-header">
      <strong>Notifications</strong>
      <button type="button" class="notification-mark-all" data-notification-mark-all<?= $unreadCount === 0 ? ' disabled' : '' ?>>Mark all read</button>
    </div>

    <div class="notification-list" data-notification-list aria-live="polite">
<?php if ($notifications === []): ?>
      <div class="notification-empty" data-notification-empty>
        <i class="fa-solid fa-bell-slash" aria-hidden="true"></i>
        <span>No notifications yet</span>
      </div>
<?php else: ?>
<?php foreach ($notifications as $notification): ?>
<?php
    $itemClass = 'notification-item' . ($notification['isUnread'] ? ' is-unread' : '') . ($notification['priority'] === 'urgent' ? ' is-urgent' : '');
?>
      <a class="<?= Security::e($itemClass) ?>" href="<?= Security::e($notification['link']) ?>" data-notification-id="<?= (int)$notification['id'] ?>" data-notification-priority="<?= Security::e($notification['priority']) ?>">
        <span class="notification-item-icon">
          <i class="fa-solid <?= Security::e($notification['icon']) ?>" aria-hidden="true"></i>
        </span>
        <span class="notification-item-copy">
          <strong><?= Security::e($notification['title']) ?></strong>
<?php if ($notification['bodyShort'] !== ''): ?>
          <small><?= Security::e($notification['bodyShort']) ?></small>
<?php endif; ?>
          <em><?= Security::e($notification['timeAgo']) ?></em>
        </span>
<?php if ($notification['priority'] === 'urgent'): ?>
        <span class="notification-priority">Urgent</span>
<?php endif; ?>
      </a>
<?php endforeach; ?>
<?php endif; ?>
    </div>
  </div>
</div>
