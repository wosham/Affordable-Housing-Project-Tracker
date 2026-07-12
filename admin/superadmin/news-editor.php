<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_news_editor';
$id = Security::cleanInt($_GET['id'] ?? ($_POST['article_id'] ?? 0));
$article = $id > 0 ? NewsArticle::findAdmin($id) : null;

if ($id > 0 && !$article) {
    Session::flash('error', 'News post could not be found.');
    Response::redirect(Url::to('admin/superadmin/news.php'));
}

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/news-editor.php' . ($id ? '?id=' . $id : '')));
    }

    $saveMode = Security::cleanString((string)($_POST['save_mode'] ?? 'draft'));
    $_POST['status'] = match ($saveMode) {
        'publish' => 'published',
        'schedule' => 'scheduled',
        default => Security::cleanString((string)($_POST['status'] ?? 'draft')),
    };

    try {
        $savedId = NewsArticle::saveFromAdmin($_POST, $id > 0 ? $id : null);
        Logger::log($id > 0 ? 'update' : 'create', 'news_articles', $savedId);
        Session::flash('status', $saveMode === 'publish' ? 'News post published.' : 'News post saved.');
        Response::redirect(Url::to('admin/superadmin/news-editor.php?id=' . $savedId));
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
        $article = array_merge($article ?: [], $_POST);
    }
}

$format = (string)($article['post_format'] ?? ($_GET['format'] ?? 'article'));
$format = array_key_exists($format, NewsArticle::FORMATS) ? $format : 'article';
$formatMeta = NewsArticle::FORMATS[$format];
$categories = NewsArticle::categories();
$officialCategoryId = '';
foreach ($categories as $category) {
    if (($category['slug'] ?? '') === 'official') {
        $officialCategoryId = (string)$category['id'];
        break;
    }
}
$imageOptions = NewsArticle::mediaOptions('image');
$pdfOptions = NewsArticle::mediaOptions('pdf');
$tagValue = $article && !empty($article['id']) ? implode(', ', NewsArticle::tagNames((int)$article['id'])) : '';
$meta = json_decode((string)($article['metadata_json'] ?? '{}'), true);
$meta = is_array($meta) ? $meta : [];

$form = array_merge([
    'id' => 0,
    'post_format' => $format,
    'category_id' => '',
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'read_time' => '4 min',
    'body' => '',
    'featured_image_id' => '',
    'image_caption' => '',
    'inline_image_id' => '',
    'attachment_id' => '',
    'external_url' => '',
    'status' => 'draft',
    'is_featured' => 0,
    'is_visible' => $format === 'announcement' ? 0 : 1,
    'show_in_ticker' => $format === 'announcement' ? 1 : 0,
    'ticker_text' => '',
    'ticker_url' => '',
    'ticker_expires_at' => '',
    'ticker_priority' => 0,
    'published_at' => '',
    'scheduled_for' => '',
    'seo_title' => '',
    'seo_description' => '',
    'og_image_id' => '',
], $article ?: []);

if ($format === 'announcement' && (string)$form['category_id'] === '' && $officialCategoryId !== '') {
    $form['category_id'] = $officialCategoryId;
}

$pageTitle = $form['id'] ? 'Edit News Post' : 'Add ' . $formatMeta['label'];
$pageDescription = 'Create and update public news posts, announcements and downloadable reports.';
$adminRole = 'superadmin';
$contentClass = 'sa-news-page sa-news-editor-page';
$componentCss = ['media-library', 'news-admin'];
$pageStyles = ['admin/assets/vendor/quill/quill.snow.css'];
$pageScripts = ['admin/assets/vendor/quill/quill.js', 'media-picker', 'news-editor'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'News', 'url' => Url::to('admin/superadmin/news.php')],
    ['label' => $form['id'] ? 'Edit Post' : 'Add Post'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<form class="sa-news-editor" method="post" action="<?= Security::e(Url::to('admin/superadmin/news-editor.php' . ($form['id'] ? '?id=' . (int)$form['id'] : ''))) ?>" data-news-editor>
  <?= Csrf::field($csrfForm) ?>
  <input type="hidden" name="article_id" value="<?= Security::e((string)$form['id']) ?>">
  <input type="hidden" name="post_format" value="<?= Security::e($format) ?>">
  <input type="hidden" name="body" value="<?= Security::e($form['body']) ?>" data-quill-body>
<?php if ($format === 'announcement'): ?>
  <input type="hidden" name="featured_image_id" value="">
  <input type="hidden" name="image_caption" value="">
  <input type="hidden" name="og_image_id" value="">
  <input type="hidden" name="attachment_id" value="">
  <input type="hidden" name="external_url" value="">
  <input type="hidden" name="source_label" value="">
  <input type="hidden" name="is_visible" value="0">
  <input type="hidden" name="is_featured" value="0">
<?php endif; ?>

  <section class="card sa-news-editor-hero">
    <div>
      <span class="sa-panel-label"><i class="fa-solid <?= Security::e($formatMeta['icon']) ?>" aria-hidden="true"></i> <?= Security::e($formatMeta['label']) ?></span>
      <h2><?= Security::e($form['id'] ? 'Edit public post' : 'Create a new post') ?></h2>
      <p><?= Security::e($formatMeta['hint']) ?></p>
    </div>
    <div class="sa-news-hero__actions">
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>"><i class="fa-solid fa-list" aria-hidden="true"></i> Posts</a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news-format.php')) ?>"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Format</a>
      <?php if (!empty($form['slug'])): ?><a class="btn btn--outline" href="<?= Security::e(Url::to('news-article.php?id=' . rawurlencode((string)$form['slug']))) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-eye" aria-hidden="true"></i> Preview</a><?php endif; ?>
    </div>
  </section>

  <div class="sa-news-editor-layout">
    <aside class="sa-news-editor-side">
      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Publishing</h3><p class="card__subtitle">Control status, visibility and timing.</p></div></div>
        <div class="card__body">
          <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (NewsArticle::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (string)$form['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span class="form-label">Published at</span><input class="form-input" type="datetime-local" name="published_at" value="<?= Security::e(sa_news_datetime_input($form['published_at'] ?? '')) ?>"></label>
          <label class="form-field"><span class="form-label">Scheduled for</span><input class="form-input" type="datetime-local" name="scheduled_for" value="<?= Security::e(sa_news_datetime_input($form['scheduled_for'] ?? '')) ?>"></label>
<?php if ($format !== 'announcement'): ?>
          <label class="sa-news-switch"><input type="checkbox" name="is_visible" value="1" <?= (int)($form['is_visible'] ?? 1) === 1 ? 'checked' : '' ?>><span>Visible on public News page</span></label>
          <label class="sa-news-switch"><input type="checkbox" name="is_featured" value="1" <?= (int)($form['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>><span>Featured story</span></label>
<?php else: ?>
          <div class="alert alert--info"><strong>Ticker-only notice</strong><span>Official public announcements are hidden from the News page and cannot be featured stories.</span></div>
<?php endif; ?>
        </div>
      </section>

      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Ticker & Public Promotion</h3><p class="card__subtitle">Control live ticker visibility and destination.</p></div></div>
        <div class="card__body">
          <label class="sa-news-switch"><input type="checkbox" name="show_in_ticker" value="1" <?= (int)($form['show_in_ticker'] ?? ($format === 'announcement' ? 1 : 0)) === 1 ? 'checked' : '' ?>><span>Show in live ticker</span></label>
          <label class="form-field"><span class="form-label">Ticker text</span><input class="form-input" name="ticker_text" value="<?= Security::e($form['ticker_text'] ?? '') ?>" placeholder="Defaults to post title"></label>
          <label class="form-field"><span class="form-label">Supporting URL</span><input class="form-input" type="url" name="ticker_url" value="<?= Security::e($form['ticker_url'] ?? '') ?>" placeholder="https://... or leave blank"></label>
          <div class="form-grid form-grid--2">
            <label class="form-field"><span class="form-label">Ticker expires at</span><input class="form-input" type="datetime-local" name="ticker_expires_at" value="<?= Security::e(sa_news_datetime_input($form['ticker_expires_at'] ?? '')) ?>"></label>
            <label class="form-field"><span class="form-label">Ticker priority</span><input class="form-input" type="number" name="ticker_priority" min="0" max="100" value="<?= Security::e((string)($form['ticker_priority'] ?? 0)) ?>"></label>
          </div>
          <p class="form-hint">Normal news and reports link to their article when Supporting URL is blank. Ticker-only announcements need a Supporting URL only if they should open another page.</p>
        </div>
      </section>

<?php if ($format !== 'announcement'): ?>
      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Media</h3><p class="card__subtitle">Use existing Media Library assets.</p></div></div>
        <div class="card__body">
          <?php sa_news_media_control('featured_image_id', 'Featured image', $form['featured_image_id'] ?? '', $imageOptions, 'news', 'image'); ?>
          <label class="form-field"><span class="form-label">Image caption</span><input class="form-input" name="image_caption" value="<?= Security::e($form['image_caption']) ?>" placeholder="Short image caption"></label>
          <?php sa_news_media_control('og_image_id', 'Social share image', $form['og_image_id'] ?? '', $imageOptions, 'news', 'image'); ?>
          <?php sa_news_media_control('attachment_id', 'PDF/report attachment', $form['attachment_id'] ?? '', $pdfOptions, 'publications', 'pdf'); ?>
        </div>
      </section>

      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Source</h3><p class="card__subtitle">Optional references.</p></div></div>
        <div class="card__body">
          <label class="form-field"><span class="form-label">External URL</span><input class="form-input" type="url" name="external_url" value="<?= Security::e($form['external_url']) ?>" placeholder="https://..."></label>
          <label class="form-field"><span class="form-label">Source label</span><input class="form-input" name="source_label" value="<?= Security::e($meta['source_label'] ?? '') ?>" placeholder="County department, NHC, etc."></label>
          <label class="form-field"><span class="form-label">Editor notes</span><textarea class="form-textarea" name="editor_notes" rows="4" placeholder="Internal notes only"><?= Security::e($meta['editor_notes'] ?? '') ?></textarea></label>
        </div>
      </section>
<?php else: ?>
      <section class="card">
        <div class="card__header"><div><h3 class="card__title">Official Notice</h3><p class="card__subtitle">Text-only public ticker and News notice.</p></div></div>
        <div class="card__body">
          <p class="form-hint">This format publishes a text-only public announcement. It appears on the public news page and the live ticker without image, PDF or source fields.</p>
          <label class="form-field"><span class="form-label">Editor notes</span><textarea class="form-textarea" name="editor_notes" rows="4" placeholder="Internal notes only"><?= Security::e($meta['editor_notes'] ?? '') ?></textarea></label>
        </div>
      </section>
<?php endif; ?>
    </aside>

    <main class="sa-news-editor-main">
      <section class="card">
        <div class="card__header"><div><h3 class="card__title">General</h3><p class="card__subtitle">Headline, summary, category and tags.</p></div></div>
        <div class="card__body">
          <label class="form-field"><span class="form-label">Title *</span><input class="form-input sa-news-title-input" name="title" value="<?= Security::e($form['title']) ?>" placeholder="Post headline" required data-title-source></label>
          <div class="form-grid form-grid--2">
            <label class="form-field"><span class="form-label">Slug</span><input class="form-input" name="slug" value="<?= Security::e($form['slug']) ?>" placeholder="Auto-generated if blank" data-slug-target></label>
            <label class="form-field"><span class="form-label">Read time</span><input class="form-input" name="read_time" value="<?= Security::e($form['read_time']) ?>" placeholder="4 min"></label>
          </div>
          <label class="form-field"><span class="form-label">Summary / excerpt</span><textarea class="form-textarea" name="excerpt" rows="4" placeholder="Short summary shown on public cards"><?= Security::e($form['excerpt']) ?></textarea></label>
          <div class="form-grid form-grid--2">
            <label class="form-field"><span class="form-label">Category</span><select class="form-select" name="category_id"><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= Security::e((string)$category['id']) ?>" <?= (string)$form['category_id'] === (string)$category['id'] ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-field"><span class="form-label">Tags</span><input class="form-input" name="tags" value="<?= Security::e($tagValue ?: ($_POST['tags'] ?? '')) ?>" placeholder="housing, allocation, Kitale"></label>
          </div>
        </div>
      </section>

      <section class="card sa-news-quill-card">
        <div class="card__header"><div><h3 class="card__title">Content</h3><p class="card__subtitle">Write the article body with formatting, links and media references.</p></div></div>
        <div class="sa-quill-toolbar" id="newsQuillToolbar">
          <span class="ql-formats"><button type="button" class="ql-bold"></button><button type="button" class="ql-italic"></button><button type="button" class="ql-underline"></button><button type="button" class="ql-strike"></button></span>
          <span class="ql-formats"><button type="button" class="ql-header" value="2"></button><button type="button" class="ql-header" value="3"></button><button type="button" class="ql-blockquote"></button></span>
          <span class="ql-formats"><button type="button" class="ql-list" value="ordered"></button><button type="button" class="ql-list" value="bullet"></button></span>
          <span class="ql-formats"><button type="button" class="ql-link"></button><button type="button" class="ql-clean"></button></span>
        </div>
        <div class="sa-news-quill" id="newsQuillEditor"><?= $form['body'] ?></div>
      </section>

      <section class="card">
        <div class="card__header"><div><h3 class="card__title">SEO</h3><p class="card__subtitle">Search and social metadata for the article detail page.</p></div></div>
        <div class="card__body">
          <label class="form-field"><span class="form-label">SEO title</span><input class="form-input" name="seo_title" value="<?= Security::e($form['seo_title']) ?>" placeholder="Defaults to post title"></label>
          <label class="form-field"><span class="form-label">SEO description</span><textarea class="form-textarea" name="seo_description" rows="3" placeholder="Short search description"><?= Security::e($form['seo_description']) ?></textarea></label>
        </div>
      </section>
    </main>
  </div>

  <div class="sa-news-editor-actions">
    <button class="btn btn--outline" type="submit" name="save_mode" value="draft"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Draft</button>
    <button class="btn btn--secondary" type="submit" name="save_mode" value="schedule"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Schedule</button>
    <button class="btn btn--primary" type="submit" name="save_mode" value="publish"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Publish / Update</button>
  </div>
</form>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function sa_news_datetime_input(?string $date): string
{
    $timestamp = strtotime((string)$date);
    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
}

function sa_news_media_control(string $name, string $label, mixed $value, array $options, string $folder, string $type): void
{
    $selected = null;
    foreach ($options as $option) {
        if ((string)$value === (string)$option['id']) {
            $selected = $option;
            break;
        }
    }

    $isImage = $type === 'image';
    $preview = $selected ? sa_news_editor_asset_url($selected['path'] ?? $selected['url'] ?? '') : '';
    $title = $selected ? (string)($selected['title'] ?: $selected['filename']) : 'No asset selected';
    $buttonLabel = $isImage ? 'Choose or Upload Image' : 'Choose or Upload PDF';
    $accept = $isImage ? 'image/*' : 'application/pdf,.pdf';
    ?>
    <div class="form-field">
      <span class="form-label"><?= Security::e($label) ?></span>
      <div class="sa-news-media-control" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>" data-media-kind="<?= Security::e($type) ?>">
        <div class="sa-news-media-control__preview" data-cms-asset-preview>
<?php if ($isImage && $preview !== ''): ?>
          <img src="<?= Security::e($preview) ?>" alt="">
<?php else: ?>
          <span><i class="fa-solid <?= $isImage ? 'fa-image' : 'fa-file-pdf' ?>" aria-hidden="true"></i></span>
<?php endif; ?>
        </div>
        <div class="sa-news-media-control__body">
          <strong data-cms-asset-name><?= Security::e($title) ?></strong>
          <small><?= Security::e($isImage ? 'Used as the public post image.' : 'Attach a downloadable public PDF report.') ?></small>
          <input type="hidden" name="<?= Security::e($name) ?>" value="<?= Security::e((string)$value) ?>" data-cms-upload-target data-media-picker-value="id">
          <button class="btn btn--primary btn--sm" type="button" data-media-picker-open data-media-picker-folder="<?= Security::e($folder) ?>" data-media-picker-type="<?= Security::e($type) ?>" data-media-picker-accept="<?= Security::e($accept) ?>" data-media-picker-title="<?= Security::e($buttonLabel) ?>">
            <i class="fa-solid fa-photo-film" aria-hidden="true"></i> <?= Security::e($buttonLabel) ?>
          </button>
        </div>
      </div>
    </div>
    <?php
}

function sa_news_editor_asset_url(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    return preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) ? $path : Url::asset($path);
}
