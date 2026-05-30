<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$stats = MediaLibrary::stats();
$folders = MediaLibrary::folders();
$pageTitle = 'Media Library';
$pageDescription = 'Central asset library for CMS pages, projects, news, gallery, profiles and documents.';
$adminRole = 'superadmin';
$contentClass = 'sa-media-page';
$componentCss = ['media-library'];
$pageScripts = ['media-library'];
$csrfForm = 'media_library';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Media Library'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-media-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Asset Command Centre</span>
    <h2>Media Library</h2>
    <p>Manage every uploaded asset across CMS pages, projects, news, gallery, profiles, documents and public imagery.</p>
  </div>
  <div class="sa-media-hero__actions">
    <button class="btn btn--outline" type="button" data-media-sync><i class="fa-solid fa-rotate" aria-hidden="true"></i> Sync Uploads</button>
    <button class="btn btn--primary" type="button" data-media-upload-open><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload Assets</button>
  </div>
</section>

<section class="stat-grid stat-grid--4" aria-label="Media summary">
  <?php media_stat('fa-folder-open', format_number($stats['total_files'] ?? 0), 'Total Files', 'All indexed assets'); ?>
  <?php media_stat('fa-image', format_number($stats['total_images'] ?? 0), 'Images', 'Photos, logos and graphics'); ?>
  <?php media_stat('fa-file-pdf', format_number($stats['total_pdfs'] ?? 0), 'Documents', 'PDF and office files'); ?>
  <?php media_stat('fa-hard-drive', media_size_label((int)($stats['total_size'] ?? 0)), 'Storage Used', 'Inside uploads folder'); ?>
</section>

<section class="card sa-media-console" data-media-library>
  <aside class="sa-media-folders" aria-label="Upload folders">
    <div class="sa-media-folders__head">
      <strong>Collections</strong>
      <span><?= Security::e(format_number(count($folders))) ?></span>
    </div>
    <button class="sa-media-folder is-active" type="button" data-media-folder="">
      <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
      <span>All Assets</span>
    </button>
<?php foreach ($folders as $folder => $label): ?>
    <button class="sa-media-folder" type="button" data-media-folder="<?= Security::e($folder) ?>">
      <i class="fa-solid <?= Security::e(media_folder_icon($folder)) ?>" aria-hidden="true"></i>
      <span><?= Security::e($label) ?></span>
    </button>
<?php endforeach; ?>
  </aside>

  <div class="sa-media-workspace">
    <div class="sa-media-toolbar">
      <div class="sa-media-tabs" role="tablist" aria-label="Media type filters">
        <button class="is-active" type="button" data-media-type="">All</button>
        <button type="button" data-media-type="image">Images</button>
        <button type="button" data-media-type="pdf">PDFs</button>
        <button type="button" data-media-type="video">Videos</button>
        <button type="button" data-media-type="document">Files</button>
      </div>
      <label class="sa-media-search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" placeholder="Search title, filename, alt text or folder..." data-media-search>
      </label>
      <div class="sa-media-view">
        <button class="is-active" type="button" data-media-view="grid" aria-label="Grid view"><i class="fa-solid fa-grip"></i></button>
        <button type="button" data-media-view="list" aria-label="List view"><i class="fa-solid fa-list"></i></button>
      </div>
    </div>

    <div class="sa-media-status" data-media-status>Ready</div>
    <div class="sa-media-grid" data-media-grid></div>
    <nav class="pagination sa-media-pagination" data-media-pagination aria-label="Media pagination"></nav>
  </div>

  <aside class="sa-media-inspector" data-media-inspector>
    <div class="sa-media-inspector__empty">
      <i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i>
      <strong>Select an asset</strong>
      <span>Preview details, copy paths, edit metadata and review usage.</span>
    </div>
  </aside>
</section>

<div class="sa-media-modal" data-media-upload-modal hidden>
  <div class="sa-media-modal__backdrop" data-media-modal-close></div>
  <section class="sa-media-modal__panel" role="dialog" aria-modal="true" aria-label="Upload media assets">
    <header>
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload</span>
        <h3>Add files to the library</h3>
        <p>Choose a destination collection so future CMS and project editors find assets quickly.</p>
      </div>
      <button class="btn btn--icon btn--outline" type="button" data-media-modal-close aria-label="Close upload modal"><i class="fa-solid fa-xmark"></i></button>
    </header>
    <form data-media-upload-form>
      <input type="hidden" name="csrf_form" value="media_library">
      <div class="sa-media-drop">
        <input type="file" name="file" required data-media-file>
        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
        <strong>Drop a file here or browse</strong>
        <span>Images, PDFs, office documents and videos up to the configured upload limit.</span>
      </div>
      <div class="form-grid form-grid--2">
        <label class="form-field"><span class="form-label">Destination collection</span>
          <select class="form-select" name="folder">
<?php foreach ($folders as $folder => $label): ?>
            <option value="<?= Security::e($folder) ?>"><?= Security::e($label) ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="form-field"><span class="form-label">Title</span><input class="form-input" name="title" placeholder="Readable asset title"></label>
        <label class="form-field"><span class="form-label">Alt text</span><input class="form-input" name="alt_text" placeholder="Describe the image or file purpose"></label>
        <label class="form-field"><span class="form-label">Caption</span><input class="form-input" name="caption" placeholder="Optional caption"></label>
      </div>
      <footer>
        <span data-media-upload-state>Waiting for file</span>
        <button class="btn btn--outline" type="button" data-media-modal-close>Cancel</button>
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-upload" aria-hidden="true"></i> Upload</button>
      </footer>
    </form>
  </section>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function media_stat(string $icon, string $value, string $label, string $sub): void
{
    ?>
    <article class="stat-widget">
      <span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
      <div>
        <strong class="stat-widget__value"><?= Security::e($value) ?></strong>
        <span class="stat-widget__label"><?= Security::e($label) ?></span>
        <small class="stat-widget__trend"><?= Security::e($sub) ?></small>
      </div>
    </article>
    <?php
}

function media_size_label(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 1) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

function media_folder_icon(string $folder): string
{
    return match ($folder) {
        'heroes', 'backgrounds', 'banners' => 'fa-panorama',
        'gallery', 'projects', 'site-photos' => 'fa-images',
        'logos', 'favicons', 'icons' => 'fa-copyright',
        'news' => 'fa-newspaper',
        'leadership', 'profiles' => 'fa-user-tie',
        'partners' => 'fa-handshake',
        'publications', 'tenders' => 'fa-file-lines',
        'videos' => 'fa-video',
        default => 'fa-folder',
    };
}
