<?php
$galleryFormAction = $galleryFormAction ?? Url::to('admin/superadmin/gallery-create.php');
$galleryItem = $galleryItem ?? [];
$galleryCategories = $galleryCategories ?? GalleryCategory::allOrdered();
$galleryProjects = $galleryProjects ?? Project::withRelations([], 200);
$galleryConstituencies = $galleryConstituencies ?? Database::fetchAll('SELECT id, name, slug FROM constituencies ORDER BY name ASC');

$mediaItems = is_array($galleryItem['media_items'] ?? null) ? $galleryItem['media_items'] : [];
$imageMediaIds = [];
$videoMediaIds = [];
$imageMediaItems = [];
$videoMediaItems = [];
foreach ($mediaItems as $mediaItem) {
    if (($mediaItem['media_type'] ?? '') === 'video' && !empty($mediaItem['media_id'])) {
        $videoMediaIds[] = (int)$mediaItem['media_id'];
        $videoMediaItems[] = $mediaItem;
    } elseif (($mediaItem['media_type'] ?? '') === 'image' && !empty($mediaItem['media_id'])) {
        $imageMediaIds[] = (int)$mediaItem['media_id'];
        $imageMediaItems[] = $mediaItem;
    }
}
if (!$imageMediaIds && !empty($galleryItem['image_id']) && ($galleryItem['media_type'] ?? 'image') === 'image') {
    $imageMediaIds[] = (int)$galleryItem['image_id'];
}
if (!$videoMediaIds && !empty($galleryItem['image_id']) && ($galleryItem['media_type'] ?? '') === 'video' && empty($galleryItem['video_url'])) {
    $videoMediaIds[] = (int)$galleryItem['image_id'];
}
$externalVideoUrls = array_values(array_filter(array_map(static fn (array $item): string => (string)($item['video_url'] ?? ''), $mediaItems)));
if (!$externalVideoUrls && !empty($galleryItem['video_url'])) {
    $externalVideoUrls[] = (string)$galleryItem['video_url'];
}
$selectedPrimary = $imageMediaIds ? MediaLibrary::findDetailed((int)$imageMediaIds[0]) : (!empty($galleryItem['image_id']) ? MediaLibrary::findDetailed((int)$galleryItem['image_id']) : null);
$selectedVideo = $videoMediaIds ? MediaLibrary::findDetailed((int)$videoMediaIds[0]) : null;
$selectedThumb = !empty($galleryItem['thumbnail_media_id']) ? MediaLibrary::findDetailed((int)$galleryItem['thumbnail_media_id']) : null;
$mediaType = (string)($galleryItem['media_type'] ?? 'image');
$primaryImageValue = implode(',', $imageMediaIds);
$videoAssetValue = implode(',', $videoMediaIds);
$videoThumbValue = (string)($galleryItem['thumbnail_media_id'] ?? ($mediaType === 'video' && !empty($galleryItem['video_url']) ? ($galleryItem['image_id'] ?? '') : ''));
?>

<form class="sa-gallery-editor" method="post" action="<?= Security::e($galleryFormAction) ?>" data-gallery-editor-form>
  <?= Csrf::field($csrfForm) ?>
  <input type="hidden" name="action" value="save_item">
  <input type="hidden" name="id" value="<?= Security::e((string)($galleryItem['id'] ?? 0)) ?>">

  <section class="card sa-gallery-editor-hero">
    <div>
      <span class="sa-panel-label"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Gallery Item</span>
      <h2><?= !empty($galleryItem['id']) ? 'Edit gallery item' : 'Add gallery item' ?></h2>
      <p>Publish programme photos, uploaded progress videos, or YouTube/Vimeo recordings into the public Gallery page.</p>
    </div>
    <div class="sa-gallery-hero__actions">
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/gallery.php')) ?>"><i class="fa-solid fa-list" aria-hidden="true"></i> Registry</a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/media-library.php')) ?>"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Media Library</a>
    </div>
  </section>

  <div class="sa-gallery-editor-layout">
    <aside class="sa-gallery-editor-side">
      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Publishing</h3><p class="card__subtitle">Visibility and placement.</p></div></div>
        <div class="card__body">
          <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (GalleryImage::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($galleryItem['status'] ?? 'published') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span class="form-label">Date taken</span><input class="form-input" type="date" name="taken_at" value="<?= Security::e($galleryItem['taken_at'] ?? '') ?>"></label>
          <label class="form-field"><span class="form-label">Year</span><input class="form-input" type="number" min="2020" max="2100" name="year" value="<?= Security::e($galleryItem['year'] ?? date('Y')) ?>"></label>
          <label class="form-field"><span class="form-label">Sort order</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e($galleryItem['sort_order'] ?? 0) ?>"></label>
          <label class="sa-gallery-switch"><input type="checkbox" name="is_featured" value="1" <?= !empty($galleryItem['is_featured']) ? 'checked' : '' ?>><span>Featured in archive</span></label>
          <label class="sa-gallery-switch"><input type="checkbox" name="is_highlight" value="1" <?= !empty($galleryItem['is_highlight']) ? 'checked' : '' ?>><span>Show in highlight carousel</span></label>
        </div>
      </section>

      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Media</h3><p class="card__subtitle">Use the Media Library picker.</p></div></div>
        <div class="card__body">
          <?php gallery_media_control('primary_image_ids', 'Photo images', $primaryImageValue, $selectedPrimary, 'gallery', 'image', 'Choose or Upload Images', 'Select one or many images, even from different folders.', true, count($imageMediaIds), $imageMediaItems); ?>
          <?php gallery_media_control('video_media_ids', 'Uploaded videos', $videoAssetValue, $selectedVideo, 'gallery-videos', 'video', 'Choose or Upload Videos', 'Select one or many uploaded videos. YouTube/Vimeo links can be added below.', true, count($videoMediaIds), $videoMediaItems); ?>
          <?php gallery_media_control('thumbnail_media_id', 'Video thumbnail', $videoThumbValue, $selectedThumb ?: ($mediaType === 'video' && !empty($galleryItem['video_url']) ? $selectedPrimary : null), 'gallery-thumbnails', 'image', 'Choose or Upload Thumbnail', 'Required for YouTube/Vimeo videos.'); ?>
        </div>
      </section>
    </aside>

    <main class="sa-gallery-editor-main">
      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Details</h3><p class="card__subtitle">Public title, classification and site links.</p></div></div>
        <div class="card__body">
          <div class="sa-gallery-format" role="radiogroup" aria-label="Media type">
            <label><input type="radio" name="media_type" value="image" <?= $mediaType === 'image' ? 'checked' : '' ?>><span><i class="fa-solid fa-image"></i> Photo</span></label>
            <label><input type="radio" name="media_type" value="video" <?= $mediaType === 'video' ? 'checked' : '' ?>><span><i class="fa-solid fa-video"></i> Video</span></label>
          </div>

          <label class="form-field"><span class="form-label">Title *</span><input class="form-input" name="title" required value="<?= Security::e($galleryItem['title'] ?? '') ?>" placeholder="e.g. Maili Tatu Block A foundation slab"></label>

          <div class="form-grid form-grid--2">
            <label class="form-field"><span class="form-label">Category *</span><select class="form-select" name="category_id" required><option value="">Select category</option><?php foreach ($galleryCategories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($galleryItem['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-field"><span class="form-label">Location label</span><input class="form-input" name="location" value="<?= Security::e($galleryItem['location'] ?? '') ?>" placeholder="e.g. Kiminini, Kitale"></label>
            <label class="form-field"><span class="form-label">Project</span><select class="form-select" name="project_id"><option value="">No project link</option><?php foreach ($galleryProjects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= (int)($galleryItem['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-field"><span class="form-label">Constituency / site</span><select class="form-select" name="constituency_id"><option value="">No constituency link</option><?php foreach ($galleryConstituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= (int)($galleryItem['constituency_id'] ?? 0) === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></label>
          </div>

          <label class="form-field"><span class="form-label">Caption</span><textarea class="form-textarea" name="caption" rows="4" placeholder="Short public caption"><?= Security::e($galleryItem['caption'] ?? '') ?></textarea></label>
          <label class="form-field"><span class="form-label">Alt text</span><input class="form-input" name="alt_text" value="<?= Security::e($galleryItem['alt_text'] ?? '') ?>" placeholder="Describe what appears in the image or thumbnail"></label>
        </div>
      </section>

      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Video and Source</h3><p class="card__subtitle">Use for YouTube/Vimeo links, video duration and credits.</p></div></div>
        <div class="card__body">
          <div class="form-grid form-grid--2">
            <label class="form-field"><span class="form-label">YouTube/Vimeo URLs</span><textarea class="form-textarea" name="video_urls" rows="4" placeholder="One URL per line"><?= Security::e(implode("\n", $externalVideoUrls)) ?></textarea></label>
            <label class="form-field"><span class="form-label">Duration</span><input class="form-input" name="duration" value="<?= Security::e($galleryItem['duration'] ?? '') ?>" placeholder="4:32"></label>
            <label class="form-field"><span class="form-label">Credit</span><input class="form-input" name="credit" value="<?= Security::e($galleryItem['credit'] ?? '') ?>" placeholder="Photographer, videographer or office"></label>
            <label class="form-field"><span class="form-label">External source URL</span><input class="form-input" type="url" name="external_url" value="<?= Security::e($galleryItem['external_url'] ?? '') ?>" placeholder="Optional source link"></label>
          </div>
          <label class="form-field"><span class="form-label">Highlight summary</span><textarea class="form-textarea" name="highlight_summary" rows="4" placeholder="Shown in the featured moments carousel"><?= Security::e($galleryItem['highlight_summary'] ?? '') ?></textarea></label>
        </div>
      </section>
    </main>
  </div>

  <div class="sa-gallery-editor-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/gallery.php')) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Gallery Item</button>
  </div>
</form>

<?php
function gallery_media_control(string $name, string $label, mixed $value, ?array $selected, string $folder, string $type, string $buttonLabel, string $hint, bool $multiple = false, int $selectedCount = 0, array $selectedItems = []): void
{
    $preview = $selected ? gallery_editor_asset_url($selected['path'] ?? $selected['url'] ?? '') : '';
    $title = $multiple && $selectedCount > 0 ? $selectedCount . ' assets selected' : ($selected ? (string)($selected['title'] ?: $selected['filename']) : 'No asset selected');
    $icon = $type === 'video' ? 'fa-video' : 'fa-image';
    $accept = $type === 'video' ? 'video/mp4,video/webm,video/quicktime,.mov' : 'image/*';
    ?>
    <div class="form-field" data-gallery-media-field="<?= Security::e($name) ?>">
      <span class="form-label"><?= Security::e($label) ?></span>
      <div class="sa-gallery-media-picker" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>" data-media-kind="<?= Security::e($type) ?>">
        <div class="sa-gallery-media-picker__preview" data-cms-asset-preview>
<?php if ($type === 'image' && $preview !== ''): ?>
          <img src="<?= Security::e($preview) ?>" alt="">
<?php else: ?>
          <span><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
<?php endif; ?>
        </div>
        <div class="sa-gallery-media-picker__body">
          <strong data-cms-asset-name><?= Security::e($title) ?></strong>
          <small><?= Security::e($hint) ?></small>
          <input type="hidden" name="<?= Security::e($name) ?>" value="<?= Security::e((string)$value) ?>" data-cms-upload-target data-media-picker-value="id">
          <button class="btn btn--primary btn--sm" type="button" data-media-picker-open <?= $multiple ? 'data-media-picker-multiple' : '' ?> data-media-picker-folder="<?= Security::e($folder) ?>" data-media-picker-type="<?= Security::e($type) ?>" data-media-picker-accept="<?= Security::e($accept) ?>" data-media-picker-title="<?= Security::e($buttonLabel) ?>">
            <i class="fa-solid fa-photo-film" aria-hidden="true"></i> <?= Security::e($buttonLabel) ?>
          </button>
          <button class="btn btn--outline btn--sm" type="button" data-gallery-media-clear>Clear</button>
        </div>
      </div>
<?php if ($multiple): ?>
      <div class="sa-gallery-selected-list" data-selected-list>
<?php foreach ($selectedItems as $item): ?>
<?php
        $itemId = (int)($item['media_id'] ?? 0);
        $itemTitle = (string)($item['media_title'] ?: $item['media_filename'] ?: ('Asset #' . $itemId));
        $itemPath = gallery_editor_asset_url($item['thumbnail_path'] ?: $item['media_path'] ?: $item['media_url'] ?: '');
?>
        <div class="sa-gallery-selected-media" data-selected-id="<?= Security::e((string)$itemId) ?>">
          <div class="sa-gallery-selected-media__thumb">
<?php if ($type === 'image' && $itemPath !== ''): ?>
            <img src="<?= Security::e($itemPath) ?>" alt="">
<?php else: ?>
            <span><i class="fa-solid <?= $type === 'video' ? 'fa-video' : 'fa-image' ?>" aria-hidden="true"></i></span>
<?php endif; ?>
          </div>
          <span><?= Security::e($itemTitle) ?></span>
          <button type="button" data-gallery-selected-remove="<?= Security::e((string)$itemId) ?>" aria-label="Remove selected asset"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
<?php endforeach; ?>
      </div>
<?php endif; ?>
    </div>
    <?php
}

function gallery_editor_asset_url(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) ? $path : Url::asset($path);
}
