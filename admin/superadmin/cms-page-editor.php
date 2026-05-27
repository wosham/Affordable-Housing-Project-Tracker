<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$slug = trim((string)($_GET['slug'] ?? 'home'));
$page = CmsPage::findBySlug($slug);

if (!$page) {
    Session::flash('error', 'CMS page could not be found.');
    Response::redirect(Url::to('admin/superadmin/cms.php'));
}

$sections = CmsSection::forPage((int)$page['id']);
if (!$sections) {
    CmsSection::upsert((int)$page['id'], [
        'section_key' => 'source_snapshot',
        'label' => 'Source Snapshot',
        'section_type' => 'rich_text',
        'editor_mode' => 'rich_text',
        'is_visible' => 1,
        'content' => ['title' => 'Source Snapshot', 'body' => 'No structured sections have been seeded for this page yet.'],
    ]);
    $sections = CmsSection::forPage((int)$page['id']);
}

$sourceSnapshot = null;
$editableSections = [];
foreach ($sections as $section) {
    if (($section['section_key'] ?? '') === 'source_snapshot') {
        $sourceSnapshot = $section;
        continue;
    }

    if (cms_editor_is_retired_section((string)$page['slug'], (string)($page['template'] ?? ''), $section)) {
        continue;
    }

    $editableSections[] = $section;
}

$revisionCount = (int)(Database::fetch('SELECT COUNT(*) AS total FROM cms_revisions WHERE page_id = ?', [(int)$page['id']])['total'] ?? 0);

$pageTitle = 'Edit CMS Page';
$pageDescription = 'Edit page metadata, SEO and structured content sections.';
$adminRole = 'superadmin';
$contentClass = 'sa-cms-editor-page';
$componentCss = ['cms-editor'];
$pageScripts = ['cms-editor'];
$csrfForm = 'cms_editor';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'CMS Pages', 'url' => Url::to('admin/superadmin/cms.php')],
    ['label' => cms_editor_page_label((string)$page['slug'])],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-cms-editor-hero" data-cms-page-id="<?= (int)$page['id'] ?>" data-cms-page-slug="<?= Security::e($page['slug']) ?>">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> <?= Security::e(status_label($page['template'] ?? 'page')) ?></span>
    <h2><?= Security::e(cms_editor_page_label((string)$page['slug'])) ?></h2>
    <p><?= Security::e($page['route_path'] ?: 'Structured content page') ?></p>
  </div>
  <div class="sa-cms-editor-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to CMS</a>
    <?php if (($page['route_path'] ?? '') !== ''): ?>
      <a class="btn btn--primary" href="<?= Security::e(Url::to((string)$page['route_path'])) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Preview Page</a>
    <?php endif; ?>
  </div>
</section>

<div class="sa-cms-editor-layout">
  <main class="sa-cms-editor-main">
    <section class="card sa-cms-meta-card">
      <div class="section-heading">
        <div>
          <h3>Page Settings & SEO</h3>
          <p>Publishing state, search metadata, canonical route and hero asset.</p>
        </div>
        <span class="badge <?= Security::e(status_badge_class($page['status'])) ?>"><?= Security::e(status_label($page['status'])) ?></span>
      </div>
      <form class="sa-cms-page-form" data-cms-page-form>
        <input type="hidden" name="slug" value="<?= Security::e($page['slug']) ?>">
        <div class="form-grid form-grid--2">
          <label class="form-field"><span class="form-label">Status</span>
            <select class="form-select" name="status">
<?php foreach (['published', 'draft', 'maintenance', 'hidden'] as $status): ?>
              <option value="<?= Security::e($status) ?>" <?= $page['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
<?php endforeach; ?>
            </select>
          </label>
          <label class="form-field"><span class="form-label">Route path</span><input class="form-input" name="route_path" value="<?= Security::e($page['route_path'] ?? '') ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">SEO title</span><input class="form-input" name="seo_title" value="<?= Security::e($page['seo_title'] ?? '') ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">SEO description</span><textarea class="form-textarea" name="seo_description" rows="3"><?= Security::e($page['seo_description'] ?? '') ?></textarea></label>
          <label class="form-field"><span class="form-label">SEO keywords</span><input class="form-input" name="seo_keywords" value="<?= Security::e($page['seo_keywords'] ?? '') ?>"></label>
          <label class="form-field"><span class="form-label">Canonical URL</span><input class="form-input" name="canonical_url" value="<?= Security::e($page['canonical_url'] ?? '') ?>"></label>
          <div class="form-field form-field--full">
            <span class="form-label">Hero image</span>
            <div class="sa-cms-upload" data-cms-upload data-upload-folder="heroes">
              <input type="file" accept="image/*" data-cms-upload-input>
              <input class="form-input" name="hero_image" data-cms-upload-target value="<?= Security::e($page['hero_image'] ?? '') ?>" placeholder="Upload a hero image to set this path">
              <span class="sa-cms-upload__hint"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload the image. The stored path will be saved with the page.</span>
            </div>
          </div>
        </div>
        <div class="sa-cms-form-actions">
          <span data-cms-save-state>Ready</span>
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Page Settings</button>
        </div>
      </form>
    </section>

    <section class="sa-cms-section-list" aria-label="Editable sections">
<?php foreach ($editableSections as $section): ?>
      <?php $content = is_array($section['content'] ?? null) ? $section['content'] : []; ?>
      <?php $isDocument = ($section['editor_mode'] ?? '') === 'document' || ($section['section_type'] ?? '') === 'document'; ?>
      <article class="card sa-cms-section-card" data-cms-section data-section-id="<?= (int)$section['id'] ?>" data-section-key="<?= Security::e($section['section_key']) ?>">
        <header class="sa-cms-section-card__head">
          <div>
            <span class="sa-cms-section-kicker"><?= Security::e($section['section_key']) ?> &middot; <?= Security::e(status_label($section['section_type'])) ?></span>
            <h3><?= Security::e($section['label']) ?></h3>
            <p><?= Security::e((int)$section['is_visible'] === 1 ? 'Visible on page' : 'Hidden from page') ?> &middot; Updated <?= Security::e(time_ago($section['updated_at'] ?? null)) ?></p>
          </div>
          <div class="sa-cms-section-tools">
            <button class="btn btn--outline btn--sm" type="button" data-cms-toggle-section><?= (int)$section['is_visible'] === 1 ? 'Hide' : 'Show' ?></button>
            <button class="btn btn--primary btn--sm" type="button" data-cms-save-section><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Section</button>
          </div>
        </header>

        <div class="form-grid form-grid--2 sa-cms-section-fields">
<?php if ($isDocument): ?>
          <label class="form-field form-field--full"><span class="form-label">Document title</span><input class="form-input" data-cms-field="title" value="<?= Security::e($content['title'] ?? $section['label']) ?>"></label>
          <div class="form-field form-field--full">
            <span class="form-label">Main content</span>
            <div class="sa-quill-editor sa-quill-editor--document" data-quill-editor><?= $content['body'] ?? '' ?></div>
            <textarea class="form-textarea is-hidden" data-cms-field="body"><?= Security::e($content['body'] ?? '') ?></textarea>
          </div>
<?php else: ?>
          <label class="form-field"><span class="form-label">Eyebrow / label</span><input class="form-input" data-cms-field="eyebrow" value="<?= Security::e($content['eyebrow'] ?? '') ?>"></label>
          <div class="form-field">
            <span class="form-label">Image</span>
            <div class="sa-cms-upload" data-cms-upload data-upload-folder="cms">
              <input type="file" accept="image/*" data-cms-upload-input>
              <input class="form-input" data-cms-field="image" data-cms-upload-target value="<?= Security::e($content['image'] ?? '') ?>" placeholder="Upload an image to set this path">
              <span class="sa-cms-upload__hint"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload image. The saved path appears here.</span>
            </div>
          </div>
          <label class="form-field form-field--full"><span class="form-label">Title</span><input class="form-input" data-cms-field="title" value="<?= Security::e($content['title'] ?? $section['label']) ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">Subtitle</span><textarea class="form-textarea" data-cms-field="subtitle" rows="2"><?= Security::e($content['subtitle'] ?? '') ?></textarea></label>
          <div class="form-field form-field--full">
            <span class="form-label">Body</span>
            <div class="sa-quill-editor" data-quill-editor><?= $content['body'] ?? '' ?></div>
            <textarea class="form-textarea is-hidden" data-cms-field="body"><?= Security::e($content['body'] ?? '') ?></textarea>
          </div>
          <label class="form-field"><span class="form-label">Primary button label</span><input class="form-input" data-cms-field="primary_label" value="<?= Security::e($content['primary_label'] ?? '') ?>"></label>
          <label class="form-field"><span class="form-label">Primary button URL</span><input class="form-input" data-cms-field="primary_url" value="<?= Security::e($content['primary_url'] ?? '') ?>"></label>
          <label class="form-field"><span class="form-label">Secondary button label</span><input class="form-input" data-cms-field="secondary_label" value="<?= Security::e($content['secondary_label'] ?? '') ?>"></label>
          <label class="form-field"><span class="form-label">Secondary button URL</span><input class="form-input" data-cms-field="secondary_url" value="<?= Security::e($content['secondary_url'] ?? '') ?>"></label>
<?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>
    </section>
  </main>

  <aside class="sa-cms-editor-aside">
    <section class="card sa-cms-inspector">
      <h3>Page Inspector</h3>
      <dl>
        <div><dt>Slug</dt><dd><?= Security::e($page['slug']) ?></dd></div>
        <div><dt>Template</dt><dd><?= Security::e(status_label($page['template'] ?? 'page')) ?></dd></div>
        <div><dt>Editable sections</dt><dd><?= Security::e(format_number(count($editableSections))) ?></dd></div>
        <div><dt>Revisions</dt><dd><?= Security::e(format_number($revisionCount)) ?></dd></div>
      </dl>
      <p>Quill is enabled for body fields only. Structured inputs protect layout-critical labels, images, routes and calls-to-action.</p>
    </section>
<?php if ($sourceSnapshot): ?>
    <section class="card sa-cms-inspector sa-cms-source-card">
      <h3>Source Reference</h3>
      <p>The original hardcoded page snapshot is preserved for comparison while structured CMS sections are edited above.</p>
      <details>
        <summary>View captured headings</summary>
        <?php $snapshot = is_array($sourceSnapshot['content'] ?? null) ? $sourceSnapshot['content'] : []; ?>
        <?php $headings = is_array($snapshot['headings'] ?? null) ? $snapshot['headings'] : []; ?>
        <?php if ($headings): ?>
          <ul>
<?php foreach (array_slice($headings, 0, 12) as $heading): ?>
            <li><?= Security::e($heading['text'] ?? '') ?></li>
<?php endforeach; ?>
          </ul>
        <?php else: ?>
          <span class="text-muted">No source headings captured.</span>
        <?php endif; ?>
      </details>
    </section>
<?php endif; ?>
  </aside>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function cms_editor_page_label(string $slug): string
{
    return match ($slug) {
        'home' => 'Home',
        'not-found' => '404 Page',
        'server-error' => '500 Page',
        default => ucwords(str_replace('-', ' ', $slug)),
    };
}

function cms_editor_is_retired_section(string $pageSlug, string $template, array $section): bool
{
    if ($template === 'legal') {
        return !in_array((string)($section['section_key'] ?? ''), ['legal_document'], true);
    }

    if ($pageSlug !== 'home') {
        return false;
    }

    $retired = ['kpis', 'featured_projects', 'constituency_coverage', 'news_intro', 'apply_cta'];
    if (!in_array((string)($section['section_key'] ?? ''), $retired, true)) {
        return false;
    }

    $content = $section['content'] ?? [];
    return !is_array($content) || trim(implode('', array_map('strval', $content))) === '';
}
