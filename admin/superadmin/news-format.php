<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Choose Post Format';
$pageDescription = 'Choose the type of public news, announcement or report to publish.';
$adminRole = 'superadmin';
$contentClass = 'sa-news-page';
$componentCss = ['news-admin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'News', 'url' => Url::to('admin/superadmin/news.php')],
    ['label' => 'Post Format'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-news-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Publishing Workflow</span>
    <h2>Choose a post format</h2>
    <p>Select the content type first so the editor can prepare the right fields for public news, reports and official programme updates.</p>
  </div>
  <div class="sa-news-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>"><i class="fa-solid fa-list" aria-hidden="true"></i> Posts</a>
  </div>
</section>

<section class="sa-format-grid" aria-label="Post format choices">
<?php foreach (NewsArticle::FORMATS as $format => $meta): ?>
  <a class="sa-format-card" href="<?= Security::e(Url::to('admin/superadmin/news-editor.php?format=' . rawurlencode($format))) ?>">
    <span class="sa-format-card__icon"><i class="fa-solid <?= Security::e($meta['icon']) ?>" aria-hidden="true"></i></span>
    <strong><?= Security::e($meta['label']) ?></strong>
    <small><?= Security::e($meta['hint']) ?></small>
    <em>Start writing <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></em>
  </a>
<?php endforeach; ?>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>
