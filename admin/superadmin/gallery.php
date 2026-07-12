<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_gallery';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/gallery.php'));
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));
    $id = Security::cleanInt($_POST['id'] ?? 0);

    try {
        match ($action) {
            'publish' => GalleryImage::quickStatus($id, 'published'),
            'draft' => GalleryImage::quickStatus($id, 'draft'),
            'hide' => GalleryImage::quickStatus($id, 'hidden'),
            'feature' => GalleryImage::toggleFeatured($id),
            'highlight' => GalleryImage::toggleHighlight($id),
            'delete' => GalleryImage::deleteItem($id),
            default => throw new RuntimeException('Unknown gallery action.'),
        };

        Logger::log($action, 'gallery_images', $id ?: null);
        Session::flash('status', 'Gallery registry updated.');
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
    }

    Response::redirect(Url::to('admin/superadmin/gallery.php'));
}

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'category' => Security::cleanString((string)($_GET['category'] ?? '')),
    'media_type' => Security::cleanString((string)($_GET['media_type'] ?? '')),
    'year' => Security::cleanString((string)($_GET['year'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '');

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalItems = GalleryImage::adminCount($filters);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$items = GalleryImage::adminList($filters, $perPage, ($page - 1) * $perPage);
$stats = GalleryImage::stats();
$categories = GalleryCategory::allOrdered();
$years = GalleryImage::years();

$pageTitle = 'Gallery Registry';
$pageDescription = 'Manage public gallery photos and videos.';
$adminRole = 'superadmin';
$contentClass = 'sa-gallery-admin-page sa-gallery-registry-page';
$componentCss = ['gallery-admin'];
$csrfForm = 'superadmin_gallery';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Gallery Registry'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-gallery-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-images" aria-hidden="true"></i> Public Gallery</span>
    <h2>Gallery Registry</h2>
    <p>Review, search, publish and organise the photos and videos shown on the public Gallery page.</p>
  </div>
  <div class="sa-gallery-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('gallery.php')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public Gallery</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/media-library.php')) ?>"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Media Library</a>
    <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/gallery-create.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Item</a>
  </div>
</section>

<section class="stat-grid stat-grid--4" aria-label="Gallery summary">
  <?php gallery_stat('fa-photo-film', $stats['total'] ?? 0, 'Total Items', 'Photos and videos'); ?>
  <?php gallery_stat('fa-circle-check', $stats['published'] ?? 0, 'Published', 'Visible publicly'); ?>
  <?php gallery_stat('fa-star', $stats['featured'] ?? 0, 'Featured', 'Archive and page highlights'); ?>
  <?php gallery_stat('fa-circle-play', $stats['videos'] ?? 0, 'Videos', 'Uploaded or linked recordings'); ?>
</section>

<section class="card sa-gallery-registry">
  <div class="card__header">
    <div>
      <h2 class="card__title">Gallery Registry</h2>
      <p class="card__subtitle">Search, filter, edit, publish, hide, feature or remove public gallery records.</p>
    </div>
    <span class="badge badge--lime"><?= Security::e(format_number($totalItems)) ?> records</span>
  </div>

  <form class="filter-bar sa-gallery-filters" method="get" action="<?= Security::e(Url::to('admin/superadmin/gallery.php')) ?>">
    <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Title, caption, location..."></div>
    <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach (GalleryImage::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="media_type">Type</label><select class="form-select" id="media_type" name="media_type"><option value="">All types</option><option value="image" <?= (($filters['media_type'] ?? '') === 'image') ? 'selected' : '' ?>>Photos</option><option value="video" <?= (($filters['media_type'] ?? '') === 'video') ? 'selected' : '' ?>>Videos</option></select></div>
    <div class="filter-group"><label class="filter-label" for="category">Category</label><select class="form-select" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= Security::e($category['slug']) ?>" <?= (($filters['category'] ?? '') === $category['slug']) ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label class="filter-label" for="year">Year</label><select class="form-select" id="year" name="year"><option value="">All years</option><?php foreach ($years as $year): ?><option value="<?= (int)$year ?>" <?= (($filters['year'] ?? '') === (string)$year) ? 'selected' : '' ?>><?= (int)$year ?></option><?php endforeach; ?></select></div>
    <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/gallery.php')) ?>">Reset</a></div>
  </form>

  <div class="table-wrap">
    <table class="data-table sa-gallery-table">
      <thead><tr><th>Media</th><th>Category</th><th>Site</th><th>Date</th><th>Status</th><th>Flags</th><th>Actions</th></tr></thead>
      <tbody>
<?php if (!$items): ?>
        <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-images" aria-hidden="true"></i></span><strong class="empty-state__title">No gallery records found</strong><span class="empty-state__text">Add a gallery item or clear the current filters.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($items as $item): ?>
<?php $thumb = gallery_admin_asset($item['thumbnail_path'] ?: $item['thumbnail_url'] ?: $item['media_path'] ?: $item['media_url'] ?: ''); ?>
        <tr>
          <td>
            <div class="sa-gallery-media-cell">
              <span class="sa-gallery-thumb">
<?php if ($thumb !== '' && (($item['media_type'] ?? 'image') === 'image' || !empty($item['thumbnail_path']) || !empty($item['thumbnail_url']))): ?>
                <img src="<?= Security::e($thumb) ?>" alt="">
<?php else: ?>
                <i class="fa-solid <?= ($item['media_type'] ?? '') === 'video' ? 'fa-video' : 'fa-image' ?>" aria-hidden="true"></i>
<?php endif; ?>
              </span>
              <span><strong><?= Security::e($item['title']) ?></strong><small><?= Security::e(format_number((int)($item['media_count'] ?? 0))) ?> media item<?= (int)($item['media_count'] ?? 0) === 1 ? '' : 's' ?><?= !empty($item['caption']) ? ' - ' . Security::e($item['caption']) : '' ?></small></span>
            </div>
          </td>
          <td><?= Security::e($item['category_name'] ?? 'Uncategorised') ?></td>
          <td><?= Security::e($item['project_name'] ?: $item['constituency_name'] ?: $item['location'] ?: 'General') ?></td>
          <td><?= Security::e($item['taken_at'] ? format_date($item['taken_at']) : ($item['year'] ?: '-')) ?></td>
          <td><span class="badge <?= Security::e(status_badge_class((string)$item['status'])) ?>"><?= Security::e(status_label((string)$item['status'])) ?></span></td>
          <td><span class="sa-gallery-flags"><?= (int)$item['is_featured'] === 1 ? '<i class="fa-solid fa-star" title="Featured"></i>' : '' ?><?= (int)$item['is_highlight'] === 1 ? '<i class="fa-solid fa-wand-magic-sparkles" title="Highlight"></i>' : '' ?></span></td>
          <td>
            <div class="data-table__actions">
              <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/gallery-edit.php?id=' . (int)$item['id'])) ?>" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
              <?php gallery_action_form($csrfForm, (int)$item['id'], 'feature', 'fa-star', 'Toggle featured'); ?>
              <?php gallery_action_form($csrfForm, (int)$item['id'], 'highlight', 'fa-wand-magic-sparkles', 'Toggle highlight'); ?>
              <?php gallery_action_form($csrfForm, (int)$item['id'], (string)$item['status'] === 'published' ? 'hide' : 'publish', (string)$item['status'] === 'published' ? 'fa-eye-slash' : 'fa-paper-plane', (string)$item['status'] === 'published' ? 'Hide' : 'Publish'); ?>
              <?php gallery_action_form($csrfForm, (int)$item['id'], 'delete', 'fa-eye-slash', 'Hide', 'btn--danger', 'Hide this gallery item from the public gallery?'); ?>
            </div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="Gallery pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(Url::to('admin/superadmin/gallery.php?' . http_build_query(array_merge($filters, ['page' => $i])))) ?>"><?= $i ?></a>
<?php endfor; ?>
  </nav>
<?php endif; ?>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function gallery_stat(string $icon, int $value, string $label, string $sub): void
{
    ?>
    <article class="stat-widget">
      <span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
      <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($value)) ?></strong><span class="stat-widget__label"><?= Security::e($label) ?></span><small class="stat-widget__trend"><?= Security::e($sub) ?></small></span>
    </article>
    <?php
}

function gallery_admin_asset(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) ? $path : Url::asset($path);
}

function gallery_action_form(string $csrfForm, int $id, string $action, string $icon, string $label, string $class = 'btn--outline', string $confirm = ''): void
{
    ?>
    <form method="post" action="<?= Security::e(Url::to('admin/superadmin/gallery.php')) ?>" <?= $confirm !== '' ? 'data-confirm="' . Security::e($confirm) . '"' : '' ?>>
      <?= Csrf::field($csrfForm) ?>
      <input type="hidden" name="id" value="<?= Security::e((string)$id) ?>">
      <input type="hidden" name="action" value="<?= Security::e($action) ?>">
      <button class="btn btn--icon <?= Security::e($class) ?>" type="submit" title="<?= Security::e($label) ?>" aria-label="<?= Security::e($label) ?>"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></button>
    </form>
    <?php
}
