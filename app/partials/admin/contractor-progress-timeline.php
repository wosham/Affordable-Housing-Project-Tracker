<?php
/**
 * Shared contractor progress history timeline + detail/lightbox markup.
 *
 * @var array $history
 * @var bool $wide optional
 */
$history = is_array($history ?? null) ? $history : [];
$wide = !empty($wide);
$timelineClass = 'contractor-timeline' . ($wide ? ' contractor-timeline--wide' : '');

if (!function_exists('contractor_progress_is_image')) {
    function contractor_progress_is_image(?string $path): bool
    {
        $path = strtolower(trim((string)$path));
        if ($path === '') {
            return false;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
}

if (!function_exists('contractor_progress_status_class')) {
    function contractor_progress_status_class(string $status): string
    {
        return match ($status) {
            'accepted', 'approved' => 'badge--success',
            'rejected', 'returned' => 'badge--danger',
            default => 'badge--info',
        };
    }
}
?>
<div class="<?= Security::e($timelineClass) ?>" data-progress-timeline>
  <?php if ($history === []): ?>
    <div class="empty-state empty-state--compact">
      <strong class="empty-state__title">No progress updates yet</strong>
      <span class="empty-state__text">Submit a progress update with site evidence to start the trail.</span>
    </div>
  <?php endif; ?>
  <?php foreach ($history as $item): ?>
    <?php
      $photoPath = trim((string)($item['photo_path'] ?? ''));
      $hasImage = $photoPath !== '' && contractor_progress_is_image($photoPath);
      $hasFile = $photoPath !== '';
      $photoUrl = $hasFile ? Url::asset($photoPath) : '';
      $payload = [
          'id' => (int)($item['id'] ?? 0),
          'old' => (int)($item['old_progress'] ?? 0),
          'new' => (int)($item['new_progress'] ?? 0),
          'status' => (string)($item['status'] ?? 'submitted'),
          'status_label' => status_label($item['status'] ?? 'submitted'),
          'milestone' => (string)($item['current_milestone'] ?? ''),
          'summary' => (string)($item['work_summary'] ?? ''),
          'note' => (string)($item['note'] ?? ''),
          'blockers' => (string)($item['blockers'] ?? ''),
          'weather' => (string)($item['weather_note'] ?? ''),
          'when' => format_datetime($item['created_at'] ?? null),
          'by' => trim((string)($item['submitted_by_name'] ?? '')),
          'photo' => $hasImage ? $photoUrl : '',
          'photo_label' => $hasImage ? 'Site photo evidence' : '',
      ];
    ?>
    <article class="contractor-timeline-item" data-progress-item>
      <div class="contractor-timeline-item__head">
        <strong><?= (int)$item['old_progress'] ?>% → <?= (int)$item['new_progress'] ?>%</strong>
        <span class="badge <?= Security::e(contractor_progress_status_class((string)($item['status'] ?? 'submitted'))) ?>"><?= Security::e(status_label($item['status'] ?? 'submitted')) ?></span>
      </div>
      <span class="contractor-timeline-item__meta"><?= Security::e($item['current_milestone'] ?: 'Progress update') ?> · <?= Security::e(format_datetime($item['created_at'] ?? null)) ?></span>
      <p><?= Security::e(safe_truncate($item['work_summary'] ?: ($item['note'] ?? ''), 150)) ?></p>
      <div class="contractor-timeline-item__actions">
        <button
          class="btn btn--outline btn--sm"
          type="button"
          data-progress-open
          data-progress-json="<?= Security::e(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
        >
          <i class="fa-solid fa-eye" aria-hidden="true"></i> View details
        </button>
        <?php if ($hasImage): ?>
          <button
            class="btn btn--outline btn--sm"
            type="button"
            data-progress-photo
            data-photo-url="<?= Security::e($photoUrl) ?>"
            data-photo-title="<?= Security::e(($item['current_milestone'] ?: 'Progress') . ' evidence') ?>"
          >
            <i class="fa-solid fa-image" aria-hidden="true"></i> View photo
          </button>
        <?php elseif ($hasFile): ?>
          <span class="contractor-timeline-item__muted">Evidence file on record</span>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<div class="contractor-progress-overlay" data-progress-detail-modal hidden aria-hidden="true">
  <div class="contractor-progress-modal" role="dialog" aria-modal="true" aria-labelledby="progressDetailTitle">
    <header class="contractor-progress-modal__header">
      <div class="contractor-progress-modal__header-copy">
        <span class="sa-panel-label"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Progress record</span>
        <h2 id="progressDetailTitle" data-detail-title>Progress update</h2>
        <p class="contractor-progress-modal__when" data-detail-when></p>
      </div>
      <button class="btn btn--outline btn--sm contractor-progress-modal__close" type="button" data-progress-detail-close aria-label="Close details"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </header>

    <div class="contractor-progress-modal__body">
      <div class="contractor-progress-modal__chips" aria-label="Progress summary">
        <article class="contractor-progress-chip">
          <small>Progress change</small>
          <strong data-detail-change>—</strong>
        </article>
        <article class="contractor-progress-chip">
          <small>Status</small>
          <strong><span class="badge badge--info" data-detail-status-badge>Submitted</span></strong>
        </article>
        <article class="contractor-progress-chip">
          <small>Submitted by</small>
          <strong data-detail-by>—</strong>
        </article>
      </div>

      <div class="contractor-progress-modal__fields">
        <section class="contractor-progress-field">
          <span class="label">Milestone</span>
          <p data-detail-milestone>—</p>
        </section>
        <section class="contractor-progress-field">
          <span class="label">Work summary</span>
          <p data-detail-summary>—</p>
        </section>
        <section class="contractor-progress-field">
          <span class="label">Progress note</span>
          <p data-detail-note>—</p>
        </section>
        <section class="contractor-progress-field" data-detail-blockers-wrap hidden>
          <span class="label">Blockers / support needed</span>
          <p data-detail-blockers>—</p>
        </section>
        <section class="contractor-progress-field" data-detail-weather-wrap hidden>
          <span class="label">Weather / site condition</span>
          <p data-detail-weather>—</p>
        </section>
      </div>

      <section class="contractor-progress-modal__photo" data-detail-photo-wrap hidden>
        <div class="contractor-progress-modal__photo-head">
          <span class="label">Site evidence</span>
          <small>Click image to enlarge</small>
        </div>
        <button type="button" class="contractor-progress-modal__photo-btn" data-detail-photo-open title="Open full size">
          <img src="" alt="Site evidence" data-detail-photo-img>
        </button>
      </section>
    </div>

    <footer class="contractor-progress-modal__footer">
      <button class="btn btn--outline" type="button" data-progress-detail-close>Close</button>
    </footer>
  </div>
</div>

<div class="contractor-progress-overlay contractor-progress-overlay--lightbox" data-progress-lightbox hidden aria-hidden="true">
  <div class="contractor-progress-lightbox" role="dialog" aria-modal="true" aria-label="Site photo evidence">
    <button class="btn btn--outline btn--sm contractor-progress-lightbox__close" type="button" data-progress-lightbox-close aria-label="Close photo"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    <img src="" alt="Site photo evidence" data-progress-lightbox-img>
    <p data-progress-lightbox-caption></p>
  </div>
</div>
