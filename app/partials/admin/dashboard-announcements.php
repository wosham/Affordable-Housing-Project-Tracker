<?php
$portalAnnouncements = $portalAnnouncements ?? [];
$portalAnnouncementTitle = $portalAnnouncementTitle ?? 'Staff Announcements';
$portalAnnouncementSubtitle = $portalAnnouncementSubtitle ?? 'Role-targeted notices from the County Director.';
$portalAnnouncementManageUrl = $portalAnnouncementManageUrl ?? null;
$portalAnnouncementCsrfForm = $portalAnnouncementCsrfForm ?? ($csrfForm ?? 'default');
$portalAnnouncementUserId = (int)($portalAnnouncementUserId ?? (Auth::id() ?? 0));
?>
<section class="card portal-announcements-card" data-portal-announcements data-csrf-form="<?= Security::e($portalAnnouncementCsrfForm) ?>">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= Security::e($portalAnnouncementTitle) ?></h2>
      <p class="card__subtitle"><?= Security::e($portalAnnouncementSubtitle) ?></p>
    </div>
<?php if ($portalAnnouncementManageUrl): ?>
    <a class="btn btn--outline btn--sm" href="<?= Security::e($portalAnnouncementManageUrl) ?>">Manage</a>
<?php endif; ?>
  </div>
<?php if ($portalAnnouncements === []): ?>
  <div class="empty-state empty-state--compact" data-portal-announcements-empty>
    <span class="empty-state__icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span>
    <strong class="empty-state__title">No active staff announcements</strong>
    <span class="empty-state__text">Published role notices will appear here.</span>
  </div>
<?php else: ?>
  <div class="portal-announcement-list" data-portal-announcement-list>
<?php foreach ($portalAnnouncements as $announcement): ?>
<?php
    $priority = strtolower((string)($announcement['priority'] ?? 'normal'));
    if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
        $priority = 'normal';
    }
    $type = (string)($announcement['type'] ?? 'info');
    $announcementId = (int)($announcement['id'] ?? 0);
    $isPinned = (int)($announcement['is_pinned'] ?? 0) === 1;
    $dateLabel = time_ago($announcement['published_at'] ?? $announcement['created_at'] ?? null);
    $body = safe_truncate((string)($announcement['body'] ?? ''), 160);
    $ctaLabel = trim((string)($announcement['cta_label'] ?? ''));
    $ctaUrl = trim((string)($announcement['cta_url'] ?? ''));
?>
    <article
      class="portal-announcement portal-announcement--<?= Security::e($priority) ?>"
      data-announcement-id="<?= $announcementId ?>"
      data-announcement-item
    >
      <span class="portal-announcement__accent" aria-hidden="true"></span>
      <span class="portal-announcement__icon" aria-hidden="true">
        <i class="fa-solid <?= Security::e(Announcement::typeIcon($type)) ?>"></i>
      </span>
      <div class="portal-announcement__body">
        <div class="portal-announcement__title-row">
          <strong class="portal-announcement__title"><?= Security::e($announcement['title'] ?? 'Announcement') ?></strong>
<?php if ($isPinned): ?>
          <span class="portal-announcement__pin" title="Pinned"><i class="fa-solid fa-thumbtack" aria-hidden="true"></i><span class="sr-only">Pinned</span></span>
<?php endif; ?>
        </div>
        <div class="portal-announcement__meta">
          <span class="portal-chip portal-chip--priority portal-chip--<?= Security::e($priority) ?>"><?= Security::e(status_label($priority)) ?></span>
          <span class="portal-chip"><?= Security::e(Announcement::typeLabel($type)) ?></span>
<?php if ($dateLabel !== '-'): ?>
          <span class="portal-chip portal-chip--muted"><?= Security::e($dateLabel) ?></span>
<?php endif; ?>
        </div>
<?php if ($body !== ''): ?>
        <p class="portal-announcement__text"><?= Security::e($body) ?></p>
<?php endif; ?>
<?php if ($ctaLabel !== '' && $ctaUrl !== ''): ?>
        <a class="portal-announcement__cta" href="<?= Security::e($ctaUrl) ?>"<?= str_starts_with($ctaUrl, 'http') ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
          <?= Security::e($ctaLabel) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
<?php endif; ?>
      </div>
<?php if ($portalAnnouncementUserId > 0 && $announcementId > 0): ?>
      <button
        class="portal-announcement__dismiss"
        type="button"
        data-dismiss-announcement="<?= $announcementId ?>"
        aria-label="<?= Security::e('Dismiss announcement: ' . (string)($announcement['title'] ?? 'Announcement')) ?>"
      >
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
<?php endif; ?>
    </article>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>
