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
$sectionsByKey = [];
foreach ($sections as $section) {
    if (($section['section_key'] ?? '') === 'source_snapshot') {
        $sourceSnapshot = $section;
        continue;
    }

    if (cms_editor_is_retired_section((string)$page['slug'], (string)($page['template'] ?? ''), $section)) {
        continue;
    }

    $editableSections[] = $section;
    $sectionsByKey[(string)$section['section_key']] = $section;
}

$revisionCount = (int)(Database::fetch('SELECT COUNT(*) AS total FROM cms_revisions WHERE page_id = ?', [(int)$page['id']])['total'] ?? 0);
$isHomeEditor = (string)$page['slug'] === 'home';
$isAboutEditor = (string)$page['slug'] === 'about';

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

<?php if ($isHomeEditor): ?>
    <section class="sa-home-cms" aria-label="Homepage editor">
      <nav class="card sa-home-cms-nav" aria-label="Homepage editor sections">
        <a href="#home-hero"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Hero</a>
        <a href="#home-featured"><i class="fa-solid fa-building" aria-hidden="true"></i> Featured Projects</a>
        <a href="#home-coverage"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Constituency Coverage</a>
        <a href="#home-reports"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> From the Ground</a>
        <a href="#home-cta"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i> Application CTA</a>
      </nav>

      <?php cms_editor_home_block('home-hero', $sectionsByKey['home_hero'] ?? null, 'Hero Command Area', 'Controls the first viewport, hero image, action buttons and KPI strip.', [
          ['background_image', 'Hero background image', 'upload', 'heroes'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Programme badge', 'text'],
          ['title', 'Headline', 'textarea'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['primary_icon', 'Primary button icon', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
          ['secondary_icon', 'Secondary button icon', 'text'],
          ['show_kpis', 'Show live KPI strip', 'checkbox'],
      ]); ?>

      <?php cms_editor_home_block('home-featured', $sectionsByKey['featured_projects'] ?? null, 'Featured Projects', 'Editorial wrapper around live featured project records. Cards come from the project registry.', [
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
          ['display_count', 'Number of project cards', 'number'],
          ['empty_title', 'Empty state title', 'text'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('home-coverage', $sectionsByKey['constituency_coverage'] ?? null, 'Coverage by Constituency', 'Controls the heading, CTA, map legend and default side-panel copy. Map data is live from constituency and project records.', [
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
          ['panel_hint', 'Default panel hint', 'textarea'],
          ['legend_active', 'Active legend label', 'text'],
          ['legend_planning', 'Planning legend label', 'text'],
          ['legend_site', 'Project site legend label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('home-reports', $sectionsByKey['ground_reports'] ?? null, 'From the Ground', 'Controls the reports section wrapper and alert rail. Published reports populate the cards automatically.', [
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
          ['alerts_title', 'Alerts rail title', 'text'],
          ['feature_count', 'Feature card count', 'number'],
          ['compact_count', 'Compact card count', 'number'],
          ['alert_count', 'Alert list count', 'number'],
          ['empty_title', 'Empty state title', 'text'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('home-cta', $sectionsByKey['ecitizen_cta'] ?? null, 'Application CTA', 'Controls the Boma Yangu application banner and four application steps.', [
          ['background_image', 'CTA background image', 'upload', 'cms'],
          ['logo_image', 'Boma Yangu logo', 'upload', 'logos'],
          ['eyebrow', 'CTA eyebrow', 'text'],
          ['title', 'CTA headline', 'textarea'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
          ['step_1_icon', 'Step 1 icon', 'text'],
          ['step_1_title', 'Step 1 title', 'text'],
          ['step_1_body', 'Step 1 body', 'textarea'],
          ['step_2_icon', 'Step 2 icon', 'text'],
          ['step_2_title', 'Step 2 title', 'text'],
          ['step_2_body', 'Step 2 body', 'textarea'],
          ['step_3_icon', 'Step 3 icon', 'text'],
          ['step_3_title', 'Step 3 title', 'text'],
          ['step_3_body', 'Step 3 body', 'textarea'],
          ['step_4_icon', 'Step 4 icon', 'text'],
          ['step_4_title', 'Step 4 title', 'text'],
          ['step_4_body', 'Step 4 body', 'textarea'],
      ]); ?>
    </section>
<?php elseif ($isAboutEditor): ?>
    <section class="sa-about-cms sa-home-cms" aria-label="About page editor">
      <nav class="card sa-home-cms-nav" aria-label="About page editor sections">
        <a href="#about-hero"><i class="fa-solid fa-image" aria-hidden="true"></i> Hero</a>
        <a href="#about-overview"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Overview</a>
        <a href="#about-pillars"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Pillars</a>
        <a href="#about-history"><i class="fa-solid fa-timeline" aria-hidden="true"></i> History</a>
        <a href="#about-legal"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Legal</a>
        <a href="#about-partners"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Partners</a>
        <a href="#about-faq"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQ</a>
        <a href="#about-leadership"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> Leadership</a>
        <a href="#about-cta"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i> Apply CTA</a>
      </nav>

      <?php cms_editor_home_block('about-hero', $sectionsByKey['about_hero'] ?? null, 'Hero Command Area', 'Controls the About page first viewport, image, calls-to-action and launch year label.', [
          ['background_image', 'Hero background image', 'upload', 'heroes'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Programme badge', 'text'],
          ['title', 'Headline', 'textarea'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
          ['launch_year', 'Programme launch year', 'text'],
          ['launch_label', 'Launch KPI label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('about-overview', $sectionsByKey['programme_overview'] ?? null, 'Programme Overview', 'Controls the explanatory copy and the Programme at a Glance side card.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['lead', 'Lead paragraph', 'textarea'],
          ['body_1', 'Body paragraph 1', 'textarea'],
          ['body_2', 'Body paragraph 2', 'textarea'],
          ['link_label', 'Text link label', 'text'],
          ['link_url', 'Text link URL', 'text'],
          ['snapshot_title', 'Snapshot card title', 'text'],
          ['glance_1_label', 'Glance row 1 label', 'text'],
          ['glance_1_value', 'Glance row 1 value', 'text'],
          ['glance_1_url', 'Glance row 1 URL', 'text'],
          ['glance_2_label', 'Glance row 2 label', 'text'],
          ['glance_2_value', 'Glance row 2 value', 'text'],
          ['glance_2_url', 'Glance row 2 URL', 'text'],
          ['glance_3_label', 'Glance row 3 label', 'text'],
          ['glance_3_value', 'Glance row 3 value', 'text'],
          ['glance_3_url', 'Glance row 3 URL', 'text'],
          ['glance_4_label', 'Glance row 4 label', 'text'],
          ['glance_4_value', 'Glance row 4 value', 'text'],
          ['glance_4_url', 'Glance row 4 URL', 'text'],
          ['glance_5_label', 'Glance row 5 label', 'text'],
          ['glance_5_value', 'Glance row 5 value', 'text'],
          ['glance_5_url', 'Glance row 5 URL', 'text'],
          ['glance_6_label', 'Glance row 6 label', 'text'],
          ['glance_6_value', 'Glance row 6 value', 'text'],
          ['glance_6_url', 'Glance row 6 URL', 'text'],
      ]); ?>

      <?php cms_editor_home_block('about-pillars', $sectionsByKey['core_commitments'] ?? null, 'Core Commitments', 'Controls the dark pillars section. Live project numbers still come from the project registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['pillar_1_icon', 'Pillar 1 icon', 'text'],
          ['pillar_1_title', 'Pillar 1 title', 'text'],
          ['pillar_1_body', 'Pillar 1 body', 'textarea'],
          ['pillar_2_icon', 'Pillar 2 icon', 'text'],
          ['pillar_2_title', 'Pillar 2 title', 'text'],
          ['pillar_2_body', 'Pillar 2 body', 'textarea'],
          ['pillar_3_icon', 'Pillar 3 icon', 'text'],
          ['pillar_3_title', 'Pillar 3 title', 'text'],
          ['pillar_3_body', 'Pillar 3 body', 'textarea'],
          ['pillar_4_icon', 'Pillar 4 icon', 'text'],
          ['pillar_4_title', 'Pillar 4 title', 'text'],
          ['pillar_4_body', 'Pillar 4 body', 'textarea'],
          ['families_target', 'Families to benefit', 'number'],
      ]); ?>

      <?php cms_editor_home_block('about-history', $sectionsByKey['programme_history'] ?? null, 'Programme History Timeline', 'Controls the milestone timeline shown on the About page.', array_merge(
          [
              ['eyebrow', 'Section eyebrow', 'text'],
              ['title', 'Section heading', 'text'],
              ['subtitle', 'Section introduction', 'textarea'],
          ],
          cms_editor_about_series_fields('item', 6, [
              'period' => 'Period',
              'icon' => 'Icon',
              'title' => 'Title',
              'body' => 'Description',
              'status' => 'Status',
          ])
      )); ?>

      <?php cms_editor_home_block('about-legal', $sectionsByKey['legal_framework'] ?? null, 'Legal Framework', 'Controls the legal and policy cards.', array_merge(
          [
              ['eyebrow', 'Section eyebrow', 'text'],
              ['title', 'Section heading', 'text'],
              ['subtitle', 'Section introduction', 'textarea'],
          ],
          cms_editor_about_series_fields('card', 6, [
              'icon' => 'Icon',
              'title' => 'Title',
              'body' => 'Description',
              'link_label' => 'Link label',
              'url' => 'Link URL',
          ])
      )); ?>

      <?php cms_editor_home_block('about-partners', $sectionsByKey['partners_teaser'] ?? null, 'Partners & Stakeholders', 'Controls the wrapper around published stakeholder cards.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of stakeholder cards', 'number'],
          ['empty_text', 'Empty state text', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('about-faq', $sectionsByKey['faq_teaser'] ?? null, 'FAQ Teaser', 'Controls the wrapper around published FAQ answers.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of FAQ items', 'number'],
          ['link_label', 'Text link label', 'text'],
          ['link_url', 'Text link URL', 'text'],
      ]); ?>

      <?php cms_editor_home_block('about-leadership', $sectionsByKey['leadership_contact'] ?? null, 'Leadership & Contact', 'Controls leadership copy and the contact card wrapper. Contact values come from global settings.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['link_label', 'Leadership link label', 'text'],
          ['link_url', 'Leadership link URL', 'text'],
          ['contact_title', 'Contact card title', 'text'],
          ['contact_intro', 'Contact card introduction', 'textarea'],
          ['contact_button_label', 'Contact button label', 'text'],
          ['contact_button_url', 'Contact button URL', 'text'],
          ['office_hours', 'Office hours', 'text'],
      ]); ?>

      <?php cms_editor_home_block('about-cta', $sectionsByKey['apply_cta'] ?? null, 'Application CTA', 'Controls the final apply banner at the bottom of the About page.', [
          ['title', 'CTA headline', 'textarea'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
      ]); ?>
    </section>
<?php else: ?>
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
<?php endif; ?>
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

    $activeHome = ['home_hero', 'featured_projects', 'constituency_coverage', 'ground_reports', 'ecitizen_cta'];
    return !in_array((string)($section['section_key'] ?? ''), $activeHome, true);
}

function cms_editor_home_block(string $anchor, ?array $section, string $title, string $description, array $fields): void
{
    if (!$section) {
        return;
    }

    $content = is_array($section['content'] ?? null) ? $section['content'] : [];
    ?>
    <article id="<?= Security::e($anchor) ?>" class="card sa-home-cms-block" data-cms-section data-section-id="<?= (int)$section['id'] ?>" data-section-key="<?= Security::e($section['section_key']) ?>">
      <header class="sa-home-cms-block__head">
        <div>
          <span class="sa-cms-section-kicker"><?= Security::e($section['section_key']) ?> &middot; <?= Security::e(status_label($section['section_type'])) ?></span>
          <h3><?= Security::e($title) ?></h3>
          <p><?= Security::e($description) ?></p>
        </div>
        <div class="sa-cms-section-tools">
          <button class="btn btn--outline btn--sm" type="button" data-cms-toggle-section><?= (int)$section['is_visible'] === 1 ? 'Hide' : 'Show' ?></button>
          <button class="btn btn--primary btn--sm" type="button" data-cms-save-section><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Block</button>
        </div>
      </header>
      <div class="sa-home-cms-fields">
<?php foreach ($fields as $field): ?>
        <?php cms_editor_home_field($field, $content); ?>
<?php endforeach; ?>
      </div>
    </article>
    <?php
}

function cms_editor_about_series_fields(string $prefix, int $count, array $labels): array
{
    $fields = [];
    for ($i = 1; $i <= $count; $i++) {
        foreach ($labels as $key => $label) {
            $type = in_array($key, ['body', 'description'], true) ? 'textarea' : 'text';
            $fields[] = [$prefix . '_' . $i . '_' . $key, $label . ' ' . $i, $type];
        }
    }

    return $fields;
}

function cms_editor_home_field(array $field, array $content): void
{
    [$key, $label, $type] = [$field[0], $field[1], $field[2]];
    $folder = $field[3] ?? 'cms';
    $value = (string)($content[$key] ?? '');
    $wide = in_array($type, ['textarea', 'upload'], true) ? ' form-field--full' : '';
    ?>
    <label class="form-field<?= $wide ?>">
      <span class="form-label"><?= Security::e($label) ?></span>
<?php if ($type === 'textarea'): ?>
      <textarea class="form-textarea" rows="3" data-cms-field="<?= Security::e($key) ?>"><?= Security::e($value) ?></textarea>
<?php elseif ($type === 'number'): ?>
      <input class="form-input" type="number" min="0" data-cms-field="<?= Security::e($key) ?>" value="<?= Security::e($value) ?>">
<?php elseif ($type === 'checkbox'): ?>
      <span class="sa-home-check"><input type="checkbox" data-cms-field="<?= Security::e($key) ?>" value="1" <?= in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) ? 'checked' : '' ?>> <strong><?= Security::e($label) ?></strong></span>
<?php elseif ($type === 'upload'): ?>
      <span class="sa-cms-upload" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>">
        <input type="file" accept="image/*" data-cms-upload-input>
        <input class="form-input" data-cms-field="<?= Security::e($key) ?>" data-cms-upload-target value="<?= Security::e($value) ?>" placeholder="Upload an image to set this path">
        <span class="sa-cms-upload__hint"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload image. The stored path is saved with this block.</span>
      </span>
<?php else: ?>
      <input class="form-input" data-cms-field="<?= Security::e($key) ?>" value="<?= Security::e($value) ?>">
<?php endif; ?>
    </label>
    <?php
}
