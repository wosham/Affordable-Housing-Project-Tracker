<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$userId = (int)Auth::id();
$projectId = InternProjectWork::defaultProjectId($userId, Security::cleanInt($_GET['project_id'] ?? 0));
$projects = InternProjectWork::projects($userId);
$project = InternProjectWork::projectOverview($userId, $projectId);
$photos = $projectId > 0 ? InternProjectWork::photos($userId, $projectId) : [];

$pageTitle = 'Upload Photos';
$pageDescription = 'Upload site photos for your assigned project.';
$adminRole = 'intern';
$csrfForm = 'intern_work';
$contentClass = 'intern-page intern-work-page';
$componentCss = ['intern-work'];
$pageScripts = ['intern-work'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'Upload Photos'],
];

$activeInternHub = 'photos';
include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<?php include __DIR__ . '/../../app/partials/admin/intern-hub.php'; ?>

<section class="intern-work-hero">
  <div>
    <span class="intern-work-label"><i class="fa-solid fa-camera" aria-hidden="true"></i> Site evidence</span>
    <h1>Upload Photos</h1>
    <p><?= Security::e($project['name'] ?? 'Upload evidence for your assigned project.') ?></p>
  </div>
  <div class="intern-work-actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/my-project.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-building"></i> My Project</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/intern/site-data-entry.php?project_id=' . $projectId)) ?>"><i class="fa-solid fa-pen-to-square"></i> Site Note</a>
  </div>
</section>

<section class="intern-work-grid">
  <div class="intern-work-main">
    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Photo Upload</h2><p>Upload JPG, PNG or WebP site evidence.</p></div></div>
      <form class="intern-upload-form" action="<?= Security::e(Url::to('api/intern/photo-upload.php')) ?>" method="post" enctype="multipart/form-data" data-intern-photo-form>
        <input type="hidden" name="<?= Security::e(Csrf::tokenName()) ?>" value="<?= Security::e(Csrf::token('intern_work')) ?>">
        <input type="hidden" name="latitude" data-photo-latitude>
        <input type="hidden" name="longitude" data-photo-longitude>
        <div class="intern-work-form-grid">
          <label><span>Project</span>
<?php if (count($projects) <= 1): ?>
            <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
            <input class="form-input" type="text" value="<?= Security::e($project['name'] ?? 'No assigned project') ?>" readonly>
<?php else: ?>
            <select class="form-select" name="project_id" required><?php foreach ($projects as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$item['id'] === $projectId ? 'selected' : '' ?>><?= Security::e($item['name']) ?></option><?php endforeach; ?></select>
<?php endif; ?>
          </label>
          <label><span>Category</span><select class="form-select" name="category"><?php foreach (InternProjectWork::PHOTO_CATEGORIES as $category): ?><option value="<?= Security::e($category) ?>"><?= Security::e(status_label($category)) ?></option><?php endforeach; ?></select></label>
        </div>
        <label><span>Caption</span><input class="form-input" name="caption" maxlength="500" placeholder="Describe what this photo shows."></label>
        <div class="intern-upload-zone" data-upload-zone>
          <input id="intern-photo-file" type="file" name="file" accept="image/jpeg,image/png,image/webp" required data-photo-file>
          <span class="intern-upload-zone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
          <strong data-photo-file-name>Choose or drop a site photo</strong>
          <small>JPG, PNG or WebP. The selected file will be linked to your assigned project.</small>
          <button class="btn btn--outline" type="button" data-photo-picker><i class="fa-solid fa-image"></i> Choose Photo</button>
        </div>
        <p class="intern-work-status" data-intern-work-status></p>
        <div class="intern-work-actions intern-work-actions--end">
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-upload"></i> Upload Photo</button>
        </div>
      </form>
    </section>
  </div>

  <aside class="intern-work-side">
    <section class="intern-work-card">
      <div class="intern-work-card__header"><div><h2>Photo Guidance</h2><p>Keep evidence clear and useful.</p></div></div>
      <div class="intern-work-list">
        <article class="intern-work-row"><span class="intern-work-row__icon"><i class="fa-solid fa-check"></i></span><div><strong>Show the work clearly</strong><small>Use a stable angle and enough light.</small></div></article>
        <article class="intern-work-row"><span class="intern-work-row__icon"><i class="fa-solid fa-check"></i></span><div><strong>Add a useful caption</strong><small>Name the block, activity or issue.</small></div></article>
        <article class="intern-work-row"><span class="intern-work-row__icon"><i class="fa-solid fa-check"></i></span><div><strong>Use the right category</strong><small>This helps the project team review faster.</small></div></article>
      </div>
    </section>
  </aside>
</section>

<section class="intern-work-card">
  <div class="intern-work-card__header"><div><h2>My Site Photos</h2><p>Photos you uploaded for this project.</p></div><span class="intern-work-pill"><?= format_number(count($photos)) ?> photos</span></div>
  <div class="intern-photo-grid" data-photo-gallery>
    <?php if ($photos === []): ?><div class="intern-work-empty">No photos uploaded yet.</div><?php endif; ?>
    <?php foreach ($photos as $photo): ?>
      <article class="intern-photo-card" data-photo-card="<?= (int)$photo['id'] ?>">
        <img src="<?= Security::e($photo['url']) ?>" alt="<?= Security::e($photo['caption'] ?: $photo['title']) ?>">
        <div>
          <strong><?= Security::e($photo['caption'] ?: $photo['title']) ?></strong>
          <small><?= Security::e(status_label($photo['category']) . ' / ' . format_datetime($photo['created_at'])) ?></small>
          <button class="btn btn--icon" type="button" data-photo-delete="<?= (int)$photo['id'] ?>" title="Remove photo"><i class="fa-solid fa-trash"></i></button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
