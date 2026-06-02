<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$slug = trim((string)($_GET['slug'] ?? 'home'));
$page = CmsPage::findBySlug($slug);

if (!$page) {
    Session::flash('error', 'CMS page could not be found.');
    Response::redirect(Url::to('admin/superadmin/cms.php'));
}

$isFaqEditor = (string)$page['slug'] === 'faq';
if ($isFaqEditor) {
    cms_editor_ensure_faq_sections((int)$page['id']);
}

$isLeadershipEditor = (string)$page['slug'] === 'leadership';
if ($isLeadershipEditor) {
    cms_editor_ensure_leadership_sections((int)$page['id']);
}

$isStakeholdersEditor = (string)$page['slug'] === 'stakeholders';
if ($isStakeholdersEditor) {
    cms_editor_ensure_stakeholders_sections((int)$page['id']);
}

$isContactEditor = (string)$page['slug'] === 'contact';
if ($isContactEditor) {
    cms_editor_ensure_contact_sections((int)$page['id']);
}
$isProjectsEditor = (string)$page['slug'] === 'projects';
if ($isProjectsEditor) {
    cms_editor_ensure_projects_sections((int)$page['id']);
}
$isProjectDetailEditor = (string)$page['slug'] === 'project-detail';
if ($isProjectDetailEditor) {
    cms_editor_ensure_project_detail_sections((int)$page['id']);
}
$isConstituenciesEditor = (string)$page['slug'] === 'constituencies';
if ($isConstituenciesEditor) {
    cms_editor_ensure_constituencies_sections((int)$page['id']);
}
$isConstituencyDetailEditor = (string)$page['slug'] === 'constituency-detail';
if ($isConstituencyDetailEditor) {
    cms_editor_ensure_constituency_detail_sections((int)$page['id']);
}

$isNewsEditor = (string)$page['slug'] === 'news';
if ($isNewsEditor) {
    cms_editor_ensure_news_sections((int)$page['id']);
}
$isNewsArticleEditor = (string)$page['slug'] === 'news-article';
if ($isNewsArticleEditor) {
    cms_editor_ensure_news_article_sections((int)$page['id']);
}
$isGalleryEditor = (string)$page['slug'] === 'gallery';
if ($isGalleryEditor) {
    cms_editor_ensure_gallery_sections((int)$page['id']);
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
$isLeadershipEditor = (string)$page['slug'] === 'leadership';
$isStakeholdersEditor = (string)$page['slug'] === 'stakeholders';
$isContactEditor = (string)$page['slug'] === 'contact';
$isProjectsEditor = (string)$page['slug'] === 'projects';
$isProjectDetailEditor = (string)$page['slug'] === 'project-detail';
$isConstituenciesEditor = (string)$page['slug'] === 'constituencies';
$isConstituencyDetailEditor = (string)$page['slug'] === 'constituency-detail';
$isNewsEditor = (string)$page['slug'] === 'news';
$isNewsArticleEditor = (string)$page['slug'] === 'news-article';
$isGalleryEditor = (string)$page['slug'] === 'gallery';
$isLegalEditor = (string)($page['template'] ?? '') === 'legal';
$publicationStatus = cms_editor_publication_status((string)($page['status'] ?? 'draft'));
$missingImageCount = (!$isLegalEditor && trim((string)($page['hero_image'] ?? '')) === '') ? 1 : 0;
$emptyFieldCount = 0;

foreach ($editableSections as $editorSection) {
    $editorContent = is_array($editorSection['content'] ?? null) ? $editorSection['content'] : [];
    foreach ($editorContent as $contentKey => $contentValue) {
        if ($isLegalEditor && str_contains((string)$contentKey, 'image')) {
            continue;
        }
        if (str_contains((string)$contentKey, 'image') && trim((string)$contentValue) === '') {
            $missingImageCount++;
        }
        if (is_scalar($contentValue) && trim((string)$contentValue) === '') {
            $emptyFieldCount++;
        }
    }
}

$seoConfigured = trim((string)($page['seo_title'] ?? '')) !== ''
    && trim((string)($page['seo_description'] ?? '')) !== ''
    && trim((string)($page['canonical_url'] ?? '')) !== '';

$pageTitle = 'Edit CMS Page';
$pageDescription = 'Edit page metadata, SEO and structured content sections.';
$adminRole = 'superadmin';
$contentClass = 'sa-cms-editor-page';
$componentCss = ['cms-editor', 'media-library'];
$pageScripts = ['media-picker', 'cms-editor'];
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
    <p><?= Security::e(cms_editor_template_description((string)($page['template'] ?? 'page'))) ?></p>
  </div>
  <div class="sa-cms-editor-hero__actions">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to CMS</a>
    <?php if (($page['route_path'] ?? '') !== ''): ?>
      <a class="btn btn--primary" href="<?= Security::e(Url::to((string)$page['route_path'])) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Preview Page</a>
    <?php endif; ?>
  </div>
</section>

<div class="sa-cms-editor-layout <?= $isLegalEditor ? 'sa-cms-editor-layout--legal' : '' ?>">
  <main class="sa-cms-editor-main">
    <section class="card sa-cms-meta-card">
      <div class="section-heading">
        <div>
          <h3>Publishing & Search</h3>
          <p>Control publishing status, search metadata, canonical route and the main page image.</p>
        </div>
        <span class="badge <?= Security::e(status_badge_class($publicationStatus)) ?>"><?= Security::e(status_label($publicationStatus)) ?></span>
      </div>
      <form class="sa-cms-page-form" data-cms-page-form>
        <input type="hidden" name="slug" value="<?= Security::e($page['slug']) ?>">
        <div class="form-grid form-grid--2">
          <label class="form-field"><span class="form-label">Status</span>
            <select class="form-select" name="status">
<?php foreach (['published', 'draft'] as $status): ?>
          <option value="<?= Security::e($status) ?>" <?= $publicationStatus === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option>
<?php endforeach; ?>
            </select>
          </label>
          <label class="form-field"><span class="form-label">Route path</span><input class="form-input" name="route_path" value="<?= Security::e($page['route_path'] ?? '') ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">SEO title</span><input class="form-input" name="seo_title" value="<?= Security::e($page['seo_title'] ?? '') ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">SEO description</span><textarea class="form-textarea" name="seo_description" rows="3"><?= Security::e($page['seo_description'] ?? '') ?></textarea></label>
          <label class="form-field"><span class="form-label">SEO keywords</span><input class="form-input" name="seo_keywords" value="<?= Security::e($page['seo_keywords'] ?? '') ?>"></label>
          <label class="form-field"><span class="form-label">Canonical URL</span><input class="form-input" name="canonical_url" value="<?= Security::e($page['canonical_url'] ?? '') ?>"></label>
          <div class="form-field form-field--full sa-cms-page-hero-field">
            <span class="form-label">Hero image</span>
            <?php cms_editor_asset_control('hero_image', (string)($page['hero_image'] ?? ''), 'heroes', true, 'Main visual used by this page. Choose an approved image from the media library.'); ?>
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

      <?php cms_editor_home_block('home-hero', $sectionsByKey['home_hero'] ?? null, 'Hero Command Area', 'Controls the first screen visitors see: background image, headline, support text, buttons and live KPI strip.', [
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

      <?php cms_editor_home_block('home-featured', $sectionsByKey['featured_projects'] ?? null, 'Featured Projects', 'Controls the heading and empty-state copy. Featured project cards are selected from the project registry.', [
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
          ['display_count', 'Number of project cards', 'number'],
          ['empty_title', 'Empty state title', 'text'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('home-coverage', $sectionsByKey['constituency_coverage'] ?? null, 'Coverage by Constituency', 'Controls map section copy, labels, legend text and default guidance. Constituency data comes from project records.', [
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
          ['panel_hint', 'Default panel hint', 'textarea'],
          ['legend_active', 'Active legend label', 'text'],
          ['legend_planning', 'Planning legend label', 'text'],
          ['legend_site', 'Project site legend label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('home-reports', $sectionsByKey['ground_reports'] ?? null, 'From the Ground', 'Controls the reports section heading, CTA, alert rail title and empty-state copy. Published reports fill the cards.', [
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

      <?php cms_editor_home_block('home-cta', $sectionsByKey['ecitizen_cta'] ?? null, 'Application CTA', 'Controls the Boma Yangu application banner, background image, logo, button links and four application steps.', [
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

      <?php cms_editor_home_block('about-hero', $sectionsByKey['about_hero'] ?? null, 'Hero', 'Controls the first screen of the About page: background image, headline, supporting copy, action buttons and programme launch label.', [
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

      <?php cms_editor_home_block('about-overview', $sectionsByKey['programme_overview'] ?? null, 'Programme Overview', 'Manage the main explanatory story and the Programme at a Glance side card.', [
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

      <?php cms_editor_home_block('about-pillars', $sectionsByKey['core_commitments'] ?? null, 'Core Commitments', 'Manage the four commitments that explain how the programme is governed and delivered.', [
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

      <?php cms_editor_home_block('about-history', $sectionsByKey['programme_history'] ?? null, 'Programme History', 'Manage the milestone timeline that explains how the programme moved from launch to active construction.', array_merge(
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

      <?php cms_editor_home_block('about-legal', $sectionsByKey['legal_framework'] ?? null, 'Legal & Policy', 'Manage the laws, approvals and policy references shown in the legal framework section.', array_merge(
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

      <?php cms_editor_home_block('about-partners', $sectionsByKey['partners_teaser'] ?? null, 'Partners & Stakeholders', 'Manage the section heading and empty-state copy around stakeholder cards.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of stakeholder cards', 'number'],
          ['empty_text', 'Empty state text', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('about-faq', $sectionsByKey['faq_teaser'] ?? null, 'Frequently Asked Questions', 'Manage the heading, introduction and link for the FAQ preview.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of FAQ items', 'number'],
          ['link_label', 'Text link label', 'text'],
          ['link_url', 'Text link URL', 'text'],
      ]); ?>

      <?php cms_editor_home_block('about-leadership', $sectionsByKey['leadership_contact'] ?? null, 'Leadership & Contact', 'Manage the leadership introduction and the contact card wrapper. Contact details come from global settings.', [
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

      <?php cms_editor_home_block('about-cta', $sectionsByKey['apply_cta'] ?? null, 'Application CTA', 'Manage the final application banner at the bottom of the About page.', [
          ['title', 'CTA headline', 'textarea'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
      ]); ?>
    </section>
<?php elseif ($isFaqEditor): ?>
    <section class="sa-faq-cms sa-home-cms" aria-label="FAQ page editor">
      <nav class="card sa-home-cms-nav" aria-label="FAQ page editor sections">
        <a href="#faq-hero"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> Hero</a>
        <a href="#faq-popular"><i class="fa-solid fa-fire" aria-hidden="true"></i> Popular Questions</a>
        <a href="#faq-library"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> FAQ Library</a>
        <a href="#faq-cta"><i class="fa-solid fa-headset" aria-hidden="true"></i> Support CTA</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div>
          <span class="sa-panel-label"><i class="fa-solid fa-list-check" aria-hidden="true"></i> FAQ Content Manager</span>
          <h3>Questions and categories are managed separately</h3>
          <p>This page controls the FAQ wrapper, hero image, search copy, popular strip labels and support call-to-action. Add unlimited questions, answers and categories in the FAQ manager.</p>
        </div>
        <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Open FAQ Manager</a>
      </section>

      <?php cms_editor_home_block('faq-hero', $sectionsByKey['faq_hero'] ?? null, 'Hero', 'Controls the FAQ landing area: background image, headline, search input text and live summary labels.', [
          ['background_image', 'Hero background image', 'upload', 'heroes'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Programme badge', 'text'],
          ['title', 'Headline', 'textarea'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['search_placeholder', 'Search placeholder', 'text'],
          ['questions_label', 'Questions KPI label', 'text'],
          ['categories_label', 'Categories KPI label', 'text'],
          ['updates_value', 'Update cadence value', 'text'],
          ['updates_label', 'Update cadence label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('faq-popular', $sectionsByKey['faq_popular'] ?? null, 'Popular Questions', 'Controls the popular question rail. The actual question chips come from FAQ records marked popular.', [
          ['label', 'Section label', 'text'],
          ['display_count', 'Number of question chips', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('faq-library', $sectionsByKey['faq_library'] ?? null, 'FAQ Library', 'Controls category tab copy and the no-results state used by search and category filtering.', [
          ['all_label', 'All categories label', 'text'],
          ['no_results_title', 'No-results title', 'text'],
          ['no_results_text', 'No-results message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('faq-cta', $sectionsByKey['faq_contact_cta'] ?? null, 'Support CTA', 'Controls the bottom support panel. Phone and email values should come from global contact settings.', [
          ['title', 'Support heading', 'text'],
          ['subtitle', 'Support copy', 'textarea'],
          ['contact_button_label', 'Contact button label', 'text'],
          ['contact_button_url', 'Contact button URL', 'text'],
          ['apply_title', 'Application heading', 'text'],
          ['apply_subtitle', 'Application copy', 'textarea'],
          ['apply_button_label', 'Application button label', 'text'],
          ['apply_button_url', 'Application button URL', 'text'],
      ]); ?>
    </section>
<?php elseif ($isLeadershipEditor): ?>
    <section class="sa-leadership-cms sa-home-cms" aria-label="Leadership page editor">
      <nav class="card sa-home-cms-nav" aria-label="Leadership page editor sections">
        <a href="#ld-hero"><i class="fa-solid fa-image" aria-hidden="true"></i> Hero</a>
        <a href="#ld-org-chart"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Command Chain</a>
        <a href="#ld-national"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Senior Officials</a>
        <a href="#ld-spotlight"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> Spotlight</a>
        <a href="#ld-contractors"><i class="fa-solid fa-hard-hat" aria-hidden="true"></i> Contractors</a>
        <a href="#ld-quotes"><i class="fa-solid fa-quote-right" aria-hidden="true"></i> Quotes</a>
        <a href="#ld-partners"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Partners</a>
        <a href="#ld-contact"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Contact CTA</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-people-roof" aria-hidden="true"></i></div>
        <div>
          <h3>Leadership Registry</h3>
          <p>Manage officials, contractor cards, leadership quotes and partner organisations from the dedicated registry. This editor keeps the page wording, imagery and section labels clean.</p>
        </div>
        <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/leadership.php')) ?>"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open Registry</a>
      </section>

      <?php cms_editor_home_block('ld-hero', $sectionsByKey['leadership_hero'] ?? null, 'Hero', 'Controls the first screen: background image, headline, supporting copy and programme stat labels.', [
          ['background_image', 'Hero background image', 'upload', 'heroes'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Programme badge', 'text'],
          ['title', 'Headline', 'textarea'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['active_projects_label', 'Active projects label', 'text'],
          ['units_label', 'Units label', 'text'],
          ['constituencies_label', 'Constituencies label', 'text'],
          ['contractors_label', 'Contractors label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('ld-org-chart', $sectionsByKey['leadership_org_chart'] ?? null, 'Command Chain', 'Controls the command-chain section heading and fallback copy. The hierarchy itself comes from the Leadership Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('ld-national', $sectionsByKey['leadership_national'] ?? null, 'Senior Officials', 'Controls the section wrapper for national and county leadership cards.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of profile cards', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('ld-spotlight', $sectionsByKey['leadership_spotlight'] ?? null, 'County Director Spotlight', 'Controls the spotlight wrapper and action buttons. The featured person comes from the Leadership Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['fallback_title', 'Empty state title', 'text'],
          ['fallback_text', 'Empty state message', 'textarea'],
          ['contact_button_label', 'Contact button label', 'text'],
          ['contact_button_url', 'Contact button URL', 'text'],
      ]); ?>

      <?php cms_editor_home_block('ld-contractors', $sectionsByKey['leadership_contractors'] ?? null, 'Contractor Showcase', 'Controls the contractor section heading, card count and compliance note. Contractor cards come from the Leadership Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of contractor cards', 'number'],
          ['disclaimer', 'Compliance note', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('ld-quotes', $sectionsByKey['leadership_quotes'] ?? null, 'Quote Carousel', 'Controls the quote carousel heading and empty-state copy. Quotes are maintained in the Leadership Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['display_count', 'Number of quotes', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('ld-partners', $sectionsByKey['leadership_partners'] ?? null, 'Implementing Partners', 'Controls the partner section wrapper. Featured partner records come from Stakeholders.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of partner cards', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('ld-contact', $sectionsByKey['leadership_contact_cta'] ?? null, 'Field Office CTA', 'Controls the contact section at the bottom of the leadership page. Office details can be reused from global contact settings.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
          ['map_label', 'Map card label', 'text'],
          ['map_sublabel', 'Map card sublabel', 'text'],
      ]); ?>
    </section>
<?php elseif ($isStakeholdersEditor): ?>
    <section class="sa-stakeholders-cms sa-home-cms" aria-label="Stakeholders page editor">
      <nav class="card sa-home-cms-nav" aria-label="Stakeholders page editor sections">
        <a href="#st-hero"><i class="fa-solid fa-image" aria-hidden="true"></i> Hero</a>
        <a href="#st-ecosystem"><i class="fa-solid fa-network-wired" aria-hidden="true"></i> Ecosystem</a>
        <a href="#st-pillars"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Pillars</a>
        <a href="#st-mandates"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Mandates</a>
        <a href="#st-milestones"><i class="fa-solid fa-timeline" aria-hidden="true"></i> Milestones</a>
        <a href="#st-voices"><i class="fa-solid fa-comments" aria-hidden="true"></i> Voices</a>
        <a href="#st-partners"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Formal Partners</a>
        <a href="#st-engagement"><i class="fa-solid fa-route" aria-hidden="true"></i> Engage</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-network-wired" aria-hidden="true"></i></div>
        <div>
          <h3>Stakeholder Registry</h3>
          <p>Maintain organisations, ecosystem groups, milestones, community voices and participation paths from the registry. This editor controls the page wording, hero imagery and section wrappers.</p>
        </div>
        <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/stakeholders.php')) ?>"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open Registry</a>
      </section>

      <?php cms_editor_home_block('st-hero', $sectionsByKey['stakeholders_hero'] ?? null, 'Hero', 'Controls the opening screen: background image, badge, headline, introduction and KPI labels.', [
          ['background_image', 'Hero background image', 'upload', 'heroes'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Programme badge', 'text'],
          ['title', 'Headline', 'textarea'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['government_label', 'Government tiers label', 'text'],
          ['partners_label', 'Implementing partners label', 'text'],
          ['households_label', 'Households label', 'text'],
          ['oversight_label', 'Oversight bodies label', 'text'],
          ['scroll_label', 'Scroll label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('st-ecosystem', $sectionsByKey['stakeholders_ecosystem'] ?? null, 'Programme Web', 'Controls the interactive ecosystem section wrapper. Category nodes come from Stakeholder Groups.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['center_label', 'Center node label', 'text'],
          ['empty_text', 'Empty panel guidance', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('st-pillars', $sectionsByKey['stakeholders_pillars'] ?? null, 'Delivery Pillars', 'Controls the group-card section heading and empty state. Cards come from Stakeholder Groups.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of group cards', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('st-mandates', $sectionsByKey['stakeholders_mandates'] ?? null, 'Mandates & Accountabilities', 'Controls the mandate accordion wrapper. Accordion content comes from Stakeholder Groups.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('st-milestones', $sectionsByKey['stakeholders_milestones'] ?? null, 'Engagement Milestones', 'Controls the milestone carousel wrapper. Timeline cards come from the Stakeholder Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'textarea'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of milestones', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('st-voices', $sectionsByKey['stakeholders_voices'] ?? null, 'Community Voices', 'Controls the testimonial section wrapper. Voice records come from the Stakeholder Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'textarea'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of voices', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('st-partners', $sectionsByKey['stakeholders_formal_partners'] ?? null, 'Formal Partners', 'Controls the MoU and institutional partner section. Partner cards come from organisation records marked formal.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'textarea'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['display_count', 'Number of partners', 'number'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('st-engagement', $sectionsByKey['stakeholders_engagement'] ?? null, 'How to Engage', 'Controls the participation path section. Cards and steps come from the Stakeholder Registry.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'textarea'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>
    </section>
<?php elseif ($isProjectsEditor): ?>
    <section class="sa-projects-cms sa-home-cms" aria-label="Projects page editor">
      <nav class="card sa-home-cms-nav" aria-label="Projects page editor sections">
        <a href="#projects-hero"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Hero</a>
        <a href="#projects-filters"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filters</a>
        <a href="#projects-listing"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> Listing</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
        <div>
          <h3>Project records power this page</h3>
          <p>This editor controls the public Projects page wording, hero image, filters, labels and empty states. The cards, units, progress and contractors come from the project registry.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Project Registry</a>
      </section>

      <?php cms_editor_home_block('projects-hero', $sectionsByKey['projects_hero'] ?? null, 'Hero', 'Controls the first screen: background image, badge, headline, support copy and statistic labels. Statistic values are calculated from live projects.', [
          ['background_image', 'Hero background image', 'upload', 'gallery'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Hero badge', 'text'],
          ['title_plain', 'Title plain text', 'text'],
          ['title_highlight', 'Title highlighted text', 'text'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['total_projects_label', 'Total projects label', 'text'],
          ['units_label', 'Units label', 'text'],
          ['active_label', 'Active projects label', 'text'],
          ['constituencies_label', 'Constituencies label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('projects-filters', $sectionsByKey['projects_filters'] ?? null, 'Filters & Sorting', 'Controls the search placeholder, filter labels and sort option wording.', [
          ['search_placeholder', 'Search placeholder', 'text'],
          ['status_label', 'Status label', 'text'],
          ['all_label', 'All label', 'text'],
          ['active_label', 'Active status label', 'text'],
          ['planning_label', 'Planning status label', 'text'],
          ['constituency_label', 'Constituency label', 'text'],
          ['sort_label', 'Sort label', 'text'],
          ['sort_completion_desc', 'Completion high-to-low label', 'text'],
          ['sort_completion_asc', 'Completion low-to-high label', 'text'],
          ['sort_units_desc', 'Most units label', 'text'],
          ['sort_name_asc', 'Name sort label', 'text'],
          ['reset_label', 'Clear filters label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('projects-listing', $sectionsByKey['projects_listing'] ?? null, 'Project Listing', 'Controls card labels, pagination wording and the no-results message. Project cards come from live project records.', [
          ['results_prefix', 'Results prefix', 'text'],
          ['project_single', 'Single project word', 'text'],
          ['project_plural', 'Plural project word', 'text'],
          ['units_label', 'Card units label', 'text'],
          ['complete_label', 'Card completion label', 'text'],
          ['view_label', 'Card button label', 'text'],
          ['pagination_showing_label', 'Pagination showing label', 'text'],
          ['pagination_of_label', 'Pagination of label', 'text'],
          ['empty_title', 'No-results title', 'text'],
          ['empty_text', 'No-results message', 'textarea'],
          ['empty_reset_label', 'No-results reset button', 'text'],
      ]); ?>
    </section>
<?php elseif ($isProjectDetailEditor): ?>
    <section class="sa-project-detail-cms sa-home-cms" aria-label="Project detail template editor">
      <nav class="card sa-home-cms-nav" aria-label="Project detail editor sections">
        <a href="#project-detail-labels"><i class="fa-solid fa-tags" aria-hidden="true"></i> Labels</a>
        <a href="#project-detail-overview"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Overview</a>
        <a href="#project-detail-sidebar"><i class="fa-solid fa-table-list" aria-hidden="true"></i> Sidebar</a>
        <a href="#project-detail-cta"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i> Apply CTA</a>
        <a href="#project-detail-not-found"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Not Found</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
        <div>
          <h3>Project records power this template</h3>
          <p>This editor controls shared wording, sidebar labels and application call-to-action text. Each project's title, images, progress, contractor, dates, milestones and gallery come from the project registry.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Project Registry</a>
      </section>

      <?php cms_editor_home_block('project-detail-labels', $sectionsByKey['project_detail_labels'] ?? null, 'Template Labels', 'Controls hero badges, fact labels and common project wording used by every project detail page.', [
          ['projects_breadcrumb_label', 'Projects breadcrumb label', 'text'],
          ['active_status_label', 'Active status label', 'text'],
          ['planning_status_label', 'Planning status label', 'text'],
          ['completed_status_label', 'Completed status label', 'text'],
          ['watch_status_label', 'Watch status label', 'text'],
          ['started_label', 'Started badge label', 'text'],
          ['delivery_label', 'Delivery badge label', 'text'],
          ['construction_complete_label', 'Completion label', 'text'],
          ['units_label', 'Units label', 'text'],
          ['ward_label', 'Ward label', 'text'],
          ['contractor_label', 'Contractor label', 'text'],
          ['funding_label', 'Funding label', 'text'],
          ['start_date_label', 'Start date label', 'text'],
          ['est_delivery_label', 'Estimated delivery label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('project-detail-overview', $sectionsByKey['project_detail_overview'] ?? null, 'Overview, Progress & Media', 'Controls section headings, empty states and progress wording. Values are pulled from the selected project record.', [
          ['section_label', 'Overview eyebrow', 'text'],
          ['title', 'Overview title', 'text'],
          ['lead_agency_label', 'Lead agency label', 'text'],
          ['site_engineer_label', 'Site engineer label', 'text'],
          ['funding_label', 'Funding label', 'text'],
          ['current_activity_label', 'Current activity label', 'text'],
          ['progress_label', 'Progress eyebrow', 'text'],
          ['progress_title', 'Progress heading', 'text'],
          ['overall_completion_label', 'Overall completion label', 'text'],
          ['progress_note', 'Progress note', 'textarea'],
          ['units_in_progress_label', 'Units in progress label', 'text'],
          ['target_delivery_label', 'Target delivery label', 'text'],
          ['timeline_label', 'Timeline eyebrow', 'text'],
          ['timeline_title', 'Timeline heading', 'text'],
          ['timeline_empty_text', 'Timeline empty state', 'textarea'],
          ['gallery_label', 'Gallery eyebrow', 'text'],
          ['gallery_title', 'Gallery heading', 'text'],
          ['gallery_empty_text', 'Gallery empty state', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('project-detail-sidebar', $sectionsByKey['project_detail_sidebar'] ?? null, 'Sidebar', 'Controls the contractor card, project info table and related-link labels.', [
          ['contractor_title', 'Contractor card title', 'text'],
          ['contractor_role_label', 'Contractor role label', 'text'],
          ['project_info_title', 'Project info heading', 'text'],
          ['constituency_label', 'Constituency label', 'text'],
          ['status_label', 'Status label', 'text'],
          ['target_units_label', 'Target units label', 'text'],
          ['completion_label', 'Completion label', 'text'],
          ['related_title', 'Related heading', 'text'],
          ['constituency_link_suffix', 'Constituency link suffix', 'text'],
          ['all_projects_prefix', 'All projects prefix', 'text'],
          ['back_projects_label', 'Back projects label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('project-detail-cta', $sectionsByKey['project_detail_apply_cta'] ?? null, 'Application CTA', 'Controls the sidebar application card.', [
          ['title', 'CTA heading', 'text'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
      ]); ?>

      <?php cms_editor_home_block('project-detail-not-found', $sectionsByKey['project_detail_not_found'] ?? null, 'Not Found', 'Controls the missing-project message.', [
          ['title', 'Not-found title', 'text'],
          ['text', 'Not-found message', 'textarea'],
          ['button_label', 'Button label', 'text'],
      ]); ?>
    </section>
<?php elseif ($isConstituencyDetailEditor): ?>
    <section class="sa-constituency-detail-cms sa-home-cms" aria-label="Constituency detail template editor">
      <nav class="card sa-home-cms-nav" aria-label="Constituency detail editor sections">
        <a href="#constituency-detail-labels"><i class="fa-solid fa-tags" aria-hidden="true"></i> Labels</a>
        <a href="#constituency-detail-facts"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Facts</a>
        <a href="#constituency-detail-related"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Related</a>
        <a href="#constituency-detail-cta"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i> Apply CTA</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
        <div>
          <h3>One template for every constituency</h3>
          <p>This editor controls labels and calls to action. Constituency names, wards, hero images, project cards, unit totals and completion values come from constituency and project records.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Project Registry</a>
      </section>

      <?php cms_editor_home_block('constituency-detail-labels', $sectionsByKey['constituency_detail_labels'] ?? null, 'Template Labels', 'Controls hero, project section and progress wording used by every constituency detail page.', [
          ['active_status_label', 'Active status label', 'text'],
          ['planning_status_label', 'Planning status label', 'text'],
          ['projects_label', 'Projects stat label', 'text'],
          ['units_label', 'Units stat label', 'text'],
          ['population_label', 'Population stat label', 'text'],
          ['completion_label', 'Completion stat label', 'text'],
          ['wards_label', 'Wards label', 'text'],
          ['projects_title_suffix', 'Projects heading suffix', 'text'],
          ['projects_subtitle', 'Projects section subtitle', 'textarea'],
          ['view_all_projects_label', 'View-all projects label', 'text'],
          ['unit_card_label', 'Card units label', 'text'],
          ['complete_card_label', 'Card completion label', 'text'],
          ['view_project_label', 'Project card button label', 'text'],
          ['empty_projects_text', 'No projects message', 'textarea'],
          ['progress_eyebrow', 'Progress eyebrow', 'text'],
          ['progress_title_suffix', 'Progress title suffix', 'text'],
          ['progress_subtitle', 'Progress summary pattern', 'textarea'],
          ['total_units_label', 'Progress total units label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('constituency-detail-facts', $sectionsByKey['constituency_detail_facts'] ?? null, 'Facts & Location', 'Controls the labels in the facts and map sections. The values are pulled from live records.', [
          ['facts_title', 'Facts heading', 'text'],
          ['county_label', 'County label', 'text'],
          ['population_label', 'Population label', 'text'],
          ['wards_label', 'Wards label', 'text'],
          ['lead_agency_label', 'Lead agency label', 'text'],
          ['lead_agency_value', 'Lead agency value', 'text'],
          ['funding_label', 'Funding label', 'text'],
          ['funding_value', 'Funding value', 'text'],
          ['programme_label', 'Programme label', 'text'],
          ['programme_value', 'Programme value', 'text'],
          ['location_title', 'Location heading', 'text'],
          ['all_constituencies_label', 'All constituencies button', 'text'],
      ]); ?>

      <?php cms_editor_home_block('constituency-detail-related', $sectionsByKey['constituency_detail_related'] ?? null, 'Other Constituencies', 'Controls the related constituency card section and not-found state.', [
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section subtitle', 'textarea'],
          ['view_all_label', 'View all label', 'text'],
          ['explore_label', 'Explore label', 'text'],
          ['not_found_title', 'Not-found title', 'text'],
          ['not_found_text', 'Not-found message', 'textarea'],
          ['not_found_button', 'Not-found button label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('constituency-detail-cta', $sectionsByKey['constituency_detail_apply_cta'] ?? null, 'Application CTA', 'Controls the bottom application banner.', [
          ['title', 'CTA heading', 'textarea'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
      ]); ?>
    </section>
<?php elseif ($isConstituenciesEditor): ?>
    <section class="sa-constituencies-cms sa-home-cms" aria-label="Constituencies page editor">
      <nav class="card sa-home-cms-nav" aria-label="Constituencies page editor sections">
        <a href="#constituencies-hero"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Hero</a>
        <a href="#constituencies-map"><i class="fa-solid fa-draw-polygon" aria-hidden="true"></i> Map</a>
        <a href="#constituencies-progress"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Progress</a>
        <a href="#constituencies-grid"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> Browse Grid</a>
        <a href="#constituencies-cta"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i> Apply CTA</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
        <div>
          <h3>Live constituency data stays in the project registry</h3>
          <p>This editor controls page wording, labels and calls to action. Constituency names, wards, project counts, unit totals and progress bars come from constituencies, wards and projects.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>"><i class="fa-solid fa-building" aria-hidden="true"></i> Project Registry</a>
      </section>

      <?php cms_editor_home_block('constituencies-hero', $sectionsByKey['constituencies_hero'] ?? null, 'Hero', 'Controls the first screen: image, badge, headline, support text and stat labels. Stat values are calculated from live data.', [
          ['background_image', 'Hero background image', 'upload', 'gallery'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Hero badge', 'text'],
          ['title_prefix', 'Title prefix', 'text'],
          ['title_highlight', 'Title highlighted text', 'text'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['constituencies_label', 'Constituencies stat label', 'text'],
          ['projects_label', 'Projects stat label', 'text'],
          ['units_label', 'Units stat label', 'text'],
          ['residents_label', 'Residents stat label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('constituencies-map', $sectionsByKey['constituencies_map'] ?? null, 'Map & Side List', 'Controls the interactive map panel wording, legend labels and empty state. The side cards are populated from live constituency data.', [
          ['title', 'Map heading', 'text'],
          ['subtitle', 'Map hint', 'textarea'],
          ['active_label', 'Active legend label', 'text'],
          ['planning_label', 'Planning legend label', 'text'],
          ['selected_label', 'Selected legend label', 'text'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('constituencies-progress', $sectionsByKey['constituencies_progress'] ?? null, 'County-Wide Progress', 'Controls the dark progress section copy and project CTA. Progress bars come from live constituency completion values.', [
          ['title', 'Section heading', 'textarea'],
          ['subtitle', 'Section copy', 'textarea'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
      ]); ?>

      <?php cms_editor_home_block('constituencies-grid', $sectionsByKey['constituencies_grid'] ?? null, 'Browse Grid', 'Controls the card-grid section labels, sorting text and pagination wording. Cards come from live constituency data.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title_prefix', 'Title prefix', 'text'],
          ['title_highlight', 'Title highlighted text', 'text'],
          ['sort_label', 'Sort label', 'text'],
          ['default_label', 'Default sort label', 'text'],
          ['progress_label', 'Progress sort label', 'text'],
          ['units_label', 'Units sort label', 'text'],
          ['alpha_label', 'Alphabetical sort label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('constituencies-cta', $sectionsByKey['constituencies_apply_cta'] ?? null, 'Application CTA', 'Controls the bottom application banner.', [
          ['title', 'CTA heading', 'textarea'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['primary_label', 'Primary button label', 'text'],
          ['primary_url', 'Primary button URL', 'text'],
          ['secondary_label', 'Secondary button label', 'text'],
          ['secondary_url', 'Secondary button URL', 'text'],
      ]); ?>
    </section>
<?php elseif ($isNewsEditor): ?>
    <section class="sa-news-cms sa-home-cms" aria-label="News page editor">
      <nav class="card sa-home-cms-nav" aria-label="News page editor sections">
        <a href="#news-hero"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> Hero</a>
        <a href="#news-mosaic"><i class="fa-solid fa-table-cells" aria-hidden="true"></i> Mosaic</a>
        <a href="#news-featured"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</a>
        <a href="#news-filters"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filters</a>
        <a href="#news-listing"><i class="fa-solid fa-list" aria-hidden="true"></i> Listing</a>
        <a href="#news-cta"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> CTA</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
        <div>
          <h3>News posts stay in the article registry</h3>
          <p>This editor controls the public News page shell: hero, labels, filters, empty states and CTA copy. Published articles, reports, categories and featured story cards come from the news tables.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> News Registry</a>
      </section>

      <?php cms_editor_home_block('news-hero', $sectionsByKey['news_hero'] ?? null, 'Hero', 'Controls the dark grid hero copy, search placeholder and stat labels. Stat numbers come from published posts.', [
          ['eyebrow', 'Hero badge', 'text'],
          ['title_plain_1', 'Title first plain text', 'text'],
          ['title_accent', 'Title highlighted text', 'text'],
          ['title_plain_2', 'Title second plain text', 'text'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['search_placeholder', 'Search placeholder', 'text'],
          ['articles_label', 'Articles stat label', 'text'],
          ['categories_label', 'Categories stat label', 'text'],
          ['last_updated_label', 'Last updated stat label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('news-mosaic', $sectionsByKey['news_mosaic'] ?? null, 'Category Mosaic', 'Controls category tile labels and whether the hero mosaic is visible. Category counts come from published posts.', [
          ['show_mosaic', 'Show mosaic', 'checkbox'],
          ['programme_label', 'Programme tile label', 'text'],
          ['groundbreaking_label', 'Groundbreaking tile label', 'text'],
          ['construction_label', 'Construction tile label', 'text'],
          ['policy_label', 'Policy tile label', 'text'],
          ['community_label', 'Community tile label', 'text'],
          ['official_label', 'Official tile label', 'text'],
          ['field_reports_label', 'Field reports tile label', 'text'],
          ['total_label', 'Total articles label', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('news-featured', $sectionsByKey['news_featured'] ?? null, 'Featured Story', 'Controls labels around the featured article. The selected story comes from the news article marked featured.', [
          ['section_label', 'Section label', 'text'],
          ['featured_badge', 'Featured badge label', 'text'],
          ['read_button_label', 'Read button label', 'text'],
          ['empty_title', 'Empty state title', 'text'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('news-filters', $sectionsByKey['news_filters'] ?? null, 'Filters & Sorting', 'Controls filter bar labels and sort option text. Category pills come from news categories.', [
          ['all_label', 'All filter label', 'text'],
          ['results_suffix_single', 'Single result suffix', 'text'],
          ['results_suffix_plural', 'Plural result suffix', 'text'],
          ['latest_label', 'Latest sort label', 'text'],
          ['oldest_label', 'Oldest sort label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('news-listing', $sectionsByKey['news_listing'] ?? null, 'Article Listing', 'Controls grid headings, card CTAs, load-more labels and empty-state copy.', [
          ['title', 'Grid heading', 'text'],
          ['subtitle', 'Grid subtitle', 'text'],
          ['read_more_label', 'Card read-more label', 'text'],
          ['load_more_label', 'Load-more button label', 'text'],
          ['empty_title', 'No-results title', 'text'],
          ['empty_text', 'No-results message', 'textarea'],
          ['empty_reset_label', 'Clear filters label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('news-cta', $sectionsByKey['news_cta'] ?? null, 'Stay Updated CTA', 'Controls the bottom CTA strip and public social/application links.', [
          ['title_prefix', 'CTA title prefix', 'text'],
          ['title_highlight', 'CTA highlighted title text', 'text'],
          ['subtitle', 'CTA supporting copy', 'textarea'],
          ['x_url', 'X/Twitter URL', 'text'],
          ['facebook_url', 'Facebook URL', 'text'],
          ['youtube_url', 'YouTube URL', 'text'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
      ]); ?>
    </section>
<?php elseif ($isNewsArticleEditor): ?>
    <section class="sa-news-article-cms sa-home-cms" aria-label="News article template editor">
      <nav class="card sa-home-cms-nav" aria-label="News article editor sections">
        <a href="#news-article-labels"><i class="fa-solid fa-tags" aria-hidden="true"></i> Labels</a>
        <a href="#news-article-sidebar"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sidebar</a>
        <a href="#news-article-downloads"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Downloads</a>
        <a href="#news-article-not-found"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Not Found</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></div>
        <div>
          <h3>One template for every article</h3>
          <p>This editor controls public labels, side panels and empty states. Headlines, body copy, images and attachments are managed in the News post editor.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news.php')) ?>"><i class="fa-solid fa-list" aria-hidden="true"></i> News Registry</a>
      </section>

      <?php cms_editor_home_block('news-article-labels', $sectionsByKey['news_article_labels'] ?? null, 'Article Labels', 'Controls breadcrumb, meta and article-wide labels.', [
          ['news_breadcrumb_label', 'News breadcrumb label', 'text'],
          ['default_read_time', 'Default read time', 'text'],
          ['author_fallback', 'Author fallback', 'text'],
          ['view_all_news_label', 'View all news label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('news-article-sidebar', $sectionsByKey['news_article_sidebar'] ?? null, 'Sidebar', 'Controls article detail and related-story panel labels.', [
          ['details_title', 'Details panel title', 'text'],
          ['category_label', 'Category label', 'text'],
          ['format_label', 'Format label', 'text'],
          ['published_label', 'Published label', 'text'],
          ['source_label', 'Source label', 'text'],
          ['related_title', 'Related stories title', 'text'],
          ['related_empty_text', 'No related stories text', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('news-article-downloads', $sectionsByKey['news_article_downloads'] ?? null, 'Downloads & Links', 'Controls public button text for reports and source links.', [
          ['download_label', 'Download button label', 'text'],
          ['external_label', 'External link button label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('news-article-not-found', $sectionsByKey['news_article_not_found'] ?? null, 'Not Found', 'Controls the message shown when an article is missing or unpublished.', [
          ['title', 'Not-found title', 'text'],
          ['text', 'Not-found message', 'textarea'],
          ['button_label', 'Button label', 'text'],
      ]); ?>
    </section>
<?php elseif ($isGalleryEditor): ?>
    <section class="sa-gallery-cms sa-home-cms" aria-label="Gallery page editor">
      <nav class="card sa-home-cms-nav" aria-label="Gallery page editor sections">
        <a href="#gallery-hero"><i class="fa-solid fa-images" aria-hidden="true"></i> Hero</a>
        <a href="#gallery-highlights"><i class="fa-solid fa-star" aria-hidden="true"></i> Highlights</a>
        <a href="#gallery-archive"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Archive</a>
        <a href="#gallery-sites"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Sites</a>
        <a href="#gallery-videos"><i class="fa-solid fa-circle-play" aria-hidden="true"></i> Videos</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-photo-film" aria-hidden="true"></i></div>
        <div>
          <h3>Gallery records power the public media page</h3>
          <p>This editor controls page wording, labels and empty states. Photos, videos, categories, highlights and site assignments are managed in the Gallery registry.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/gallery.php')) ?>"><i class="fa-solid fa-images" aria-hidden="true"></i> Gallery Registry</a>
      </section>

      <?php cms_editor_home_block('gallery-hero', $sectionsByKey['gallery_hero'] ?? null, 'Hero', 'Controls the first screen, background image, intro copy and KPI labels.', [
          ['background_image', 'Hero background image', 'upload', 'gallery'],
          ['background_alt', 'Hero image description', 'text'],
          ['breadcrumb_label', 'Breadcrumb label', 'text'],
          ['eyebrow', 'Hero badge', 'text'],
          ['title', 'Hero title', 'text'],
          ['subtitle', 'Hero supporting copy', 'textarea'],
          ['scroll_label', 'Scroll hint label', 'text'],
          ['photos_label', 'Photos KPI label', 'text'],
          ['sites_label', 'Sites KPI label', 'text'],
          ['years_label', 'Years KPI label', 'text'],
          ['events_label', 'Events KPI label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('gallery-highlights', $sectionsByKey['gallery_highlights'] ?? null, 'Featured Moments', 'Controls the highlight carousel heading and empty message. Slides come from gallery items marked as highlights.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('gallery-archive', $sectionsByKey['gallery_archive'] ?? null, 'Photo Archive', 'Controls the filterable photo archive labels and heading.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['category_label', 'Category filter label', 'text'],
          ['year_label', 'Year filter label', 'text'],
          ['all_label', 'All filter label', 'text'],
          ['showing_label', 'Showing label', 'text'],
          ['photos_label', 'Photos label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('gallery-sites', $sectionsByKey['gallery_site_progress'] ?? null, 'Progress by Site', 'Controls site progress labels. Site stats come from gallery and project records.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['units_label', 'Units label', 'text'],
          ['completion_label', 'Completion label', 'text'],
          ['photos_label', 'Photos label', 'text'],
          ['details_label', 'Details link label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('gallery-videos', $sectionsByKey['gallery_videos'] ?? null, 'Progress Videos', 'Controls the video section heading and empty state. Video cards come from gallery video records.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('gallery-empty-states', $sectionsByKey['gallery_empty_states'] ?? null, 'Empty States', 'Controls gallery no-results and lightbox labels.', [
          ['no_results_text', 'No-results text', 'textarea'],
          ['reset_label', 'Reset filters label', 'text'],
          ['lightbox_label', 'Lightbox label', 'text'],
      ]); ?>
    </section>
<?php elseif ($isContactEditor): ?>
    <section class="sa-contact-cms sa-home-cms" aria-label="Contact page editor">
      <nav class="card sa-home-cms-nav" aria-label="Contact page editor sections">
        <a href="#contact-hero"><i class="fa-solid fa-headset" aria-hidden="true"></i> Hero</a>
        <a href="#contact-cards"><i class="fa-solid fa-address-card" aria-hidden="true"></i> Quick Cards</a>
        <a href="#contact-form"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Form</a>
        <a href="#contact-office"><i class="fa-solid fa-building" aria-hidden="true"></i> Office</a>
        <a href="#contact-departments"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Departments</a>
        <a href="#contact-faq"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQ Banner</a>
      </nav>

      <section class="card sa-cms-module-callout">
        <div class="sa-cms-module-callout__icon"><i class="fa-solid fa-address-book" aria-hidden="true"></i></div>
        <div>
          <h3>Shared contact data stays centralised</h3>
          <p>This editor controls the Contact page wording. Phone numbers, email, office details and department cards are pulled from global contact settings and the contact departments table.</p>
        </div>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms.php')) ?>#settings"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Global Settings</a>
      </section>

      <?php cms_editor_home_block('contact-hero', $sectionsByKey['contact_hero'] ?? null, 'Hero', 'Controls the first screen: badge, headline, supporting copy and the three response summary labels.', [
          ['background_image', 'Hero background image', 'upload', 'heroes'],
          ['background_alt', 'Hero image description', 'text'],
          ['eyebrow', 'Hero badge', 'text'],
          ['title', 'Headline', 'textarea'],
          ['subtitle', 'Supporting copy', 'textarea'],
          ['response_value', 'Response value', 'text'],
          ['response_label', 'Response label', 'text'],
          ['hours_value', 'Hours value', 'text'],
          ['hours_label', 'Hours label', 'text'],
          ['departments_value', 'Departments value', 'text'],
          ['departments_label', 'Departments label', 'text'],
      ]); ?>

      <?php cms_editor_home_block('contact-cards', $sectionsByKey['contact_quick_cards'] ?? null, 'Quick Contact Cards', 'Controls labels and helper text. Actual phone, email, WhatsApp and address values come from shared contact settings.', [
          ['phone_label', 'Phone card label', 'text'],
          ['phone_hint', 'Phone card hint', 'text'],
          ['email_label', 'Email card label', 'text'],
          ['email_hint', 'Email card hint', 'text'],
          ['whatsapp_label', 'WhatsApp card label', 'text'],
          ['whatsapp_hint', 'WhatsApp card hint', 'text'],
          ['visit_label', 'Visit card label', 'text'],
          ['visit_hint', 'Visit card hint', 'text'],
      ]); ?>

      <?php cms_editor_home_block('contact-form', $sectionsByKey['contact_form'] ?? null, 'Message Form', 'Controls the form heading, helper text, placeholders, privacy note and success/error copy.', [
          ['title', 'Form heading', 'text'],
          ['subtitle', 'Form introduction', 'textarea'],
          ['name_placeholder', 'Name placeholder', 'text'],
          ['phone_placeholder', 'Phone placeholder', 'text'],
          ['email_placeholder', 'Email placeholder', 'text'],
          ['subject_placeholder', 'Subject placeholder', 'text'],
          ['message_placeholder', 'Message placeholder', 'textarea'],
          ['file_label', 'File upload label', 'text'],
          ['privacy_note', 'Privacy note', 'textarea'],
          ['submit_label', 'Submit button label', 'text'],
          ['success_title', 'Success title', 'text'],
          ['success_text', 'Success message', 'textarea'],
          ['error_text', 'Error message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('contact-office', $sectionsByKey['contact_office'] ?? null, 'Office & Map', 'Controls office card labels, hours text, map embed and national helpline presentation.', [
          ['office_title', 'Office card title', 'text'],
          ['weekday_label', 'Weekday label', 'text'],
          ['weekday_hours', 'Weekday hours', 'text'],
          ['saturday_label', 'Saturday label', 'text'],
          ['saturday_hours', 'Saturday hours', 'text'],
          ['holiday_label', 'Holiday label', 'text'],
          ['holiday_hours', 'Holiday hours', 'text'],
          ['map_embed_url', 'Google Maps embed URL', 'textarea'],
          ['map_link_label', 'Map link label', 'text'],
          ['helpline_title', 'Helpline title', 'text'],
          ['helpline_number', 'Helpline number', 'text'],
          ['helpline_note', 'Helpline note', 'text'],
      ]); ?>

      <?php cms_editor_home_block('contact-departments', $sectionsByKey['contact_departments'] ?? null, 'Departments Directory', 'Controls the directory heading and empty state. Department cards and form options come from contact_departments.', [
          ['eyebrow', 'Section eyebrow', 'text'],
          ['title', 'Section heading', 'text'],
          ['subtitle', 'Section introduction', 'textarea'],
          ['empty_text', 'Empty state message', 'textarea'],
      ]); ?>

      <?php cms_editor_home_block('contact-faq', $sectionsByKey['contact_faq_banner'] ?? null, 'FAQ Shortcut Banner', 'Controls the FAQ help strip at the bottom of the Contact page.', [
          ['title', 'Banner heading', 'text'],
          ['subtitle', 'Banner copy', 'textarea'],
          ['pill_1_label', 'Pill 1 label', 'text'],
          ['pill_1_url', 'Pill 1 URL', 'text'],
          ['pill_2_label', 'Pill 2 label', 'text'],
          ['pill_2_url', 'Pill 2 URL', 'text'],
          ['pill_3_label', 'Pill 3 label', 'text'],
          ['pill_3_url', 'Pill 3 URL', 'text'],
          ['button_label', 'Button label', 'text'],
          ['button_url', 'Button URL', 'text'],
      ]); ?>
    </section>
<?php else: ?>
    <section class="sa-cms-section-list" aria-label="Editable sections">
<?php foreach ($editableSections as $section): ?>
      <?php $content = is_array($section['content'] ?? null) ? $section['content'] : []; ?>
      <?php $isDocument = ($section['editor_mode'] ?? '') === 'document' || ($section['section_type'] ?? '') === 'document'; ?>
      <article class="card sa-cms-section-card <?= $isLegalEditor ? 'sa-cms-section-card--legal' : '' ?>" data-cms-section data-section-id="<?= (int)$section['id'] ?>" data-section-key="<?= Security::e($section['section_key']) ?>">
        <header class="sa-cms-section-card__head">
          <div>
            <h3><?= Security::e($section['label']) ?></h3>
            <p><?= $isLegalEditor ? 'Single document editor for the public legal page.' : 'Last updated ' . Security::e(time_ago($section['updated_at'] ?? null)) ?></p>
          </div>
          <div class="sa-cms-section-tools">
            <button class="btn btn--primary btn--sm" type="button" data-cms-save-section><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Section</button>
          </div>
        </header>

        <div class="form-grid form-grid--2 sa-cms-section-fields">
<?php if ($isDocument): ?>
<?php if ($isLegalEditor): ?>
          <label class="form-field"><span class="form-label">Document title</span><input class="form-input" data-cms-field="title" value="<?= Security::e($content['title'] ?? $section['label']) ?>"></label>
          <label class="form-field"><span class="form-label">Hero icon</span><input class="form-input" data-cms-field="icon" placeholder="fa-triangle-exclamation" value="<?= Security::e($content['icon'] ?? 'fa-file-shield') ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">Document summary</span><textarea class="form-textarea" data-cms-field="subtitle" rows="2"><?= Security::e($content['subtitle'] ?? '') ?></textarea></label>
          <label class="form-field"><span class="form-label">Last updated date</span><input class="form-input" type="date" data-cms-field="last_updated" value="<?= Security::e($content['last_updated'] ?? '') ?>"></label>
          <div class="form-field form-field--full sa-legal-editor-shell">
            <div class="sa-legal-editor-shell__intro">
              <span class="sa-cms-section-kicker">Legal content</span>
              <p>Use headings, lists, links, callouts and tables directly in the editor. The public page builds its contents menu from the headings automatically.</p>
            </div>
            <div class="sa-quill-editor sa-quill-editor--document sa-quill-editor--legal" data-quill-editor><?= $content['body'] ?? '' ?></div>
            <textarea class="form-textarea is-hidden" data-cms-field="body"><?= Security::e($content['body'] ?? '') ?></textarea>
          </div>
<?php else: ?>
          <label class="form-field form-field--full"><span class="form-label">Document title</span><input class="form-input" data-cms-field="title" value="<?= Security::e($content['title'] ?? $section['label']) ?>"></label>
          <div class="form-field form-field--full">
            <span class="form-label">Main content</span>
            <div class="sa-quill-editor sa-quill-editor--document" data-quill-editor><?= $content['body'] ?? '' ?></div>
            <textarea class="form-textarea is-hidden" data-cms-field="body"><?= Security::e($content['body'] ?? '') ?></textarea>
          </div>
<?php endif; ?>
<?php else: ?>
          <label class="form-field"><span class="form-label">Eyebrow / label</span><input class="form-input" data-cms-field="eyebrow" value="<?= Security::e($content['eyebrow'] ?? '') ?>"></label>
          <div class="form-field">
            <span class="form-label">Image</span>
            <?php cms_editor_asset_control('image', (string)($content['image'] ?? ''), 'cms'); ?>
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
    <section class="card sa-cms-inspector sa-cms-inspector--summary">
      <h3>Publishing Summary</h3>
      <dl>
        <div><dt>Status</dt><dd><span class="badge <?= Security::e(status_badge_class($publicationStatus)) ?>"><?= Security::e(status_label($publicationStatus)) ?></span></dd></div>
        <div><dt>Page type</dt><dd><?= Security::e(status_label($page['template'] ?? 'page')) ?></dd></div>
        <div><dt>Revisions</dt><dd><?= Security::e(format_number($revisionCount)) ?></dd></div>
        <div><dt>Last saved</dt><dd><?= Security::e(time_ago($page['updated_at'] ?? $page['created_at'] ?? null)) ?></dd></div>
      </dl>
    </section>

    <section class="card sa-cms-inspector sa-cms-inspector--structure">
      <h3>Page Structure</h3>
      <dl>
        <div><dt>Editable sections</dt><dd><?= Security::e(format_number(count($editableSections))) ?></dd></div>
        <div><dt>Route</dt><dd><?= Security::e($page['route_path'] ?: '-') ?></dd></div>
        <div><dt>Canonical</dt><dd><?= trim((string)($page['canonical_url'] ?? '')) !== '' ? 'Set' : 'Missing' ?></dd></div>
        <div><dt><?= $isLegalEditor ? 'Document hero' : 'Hero image' ?></dt><dd><?= $isLegalEditor ? 'Generated' : (trim((string)($page['hero_image'] ?? '')) !== '' ? 'Selected' : 'Missing') ?></dd></div>
      </dl>
    </section>

    <section class="card sa-cms-inspector sa-cms-inspector--quality">
      <h3>Content Quality</h3>
      <ul class="sa-cms-quality-list">
        <li class="<?= $seoConfigured ? 'is-ok' : 'is-warning' ?>"><i class="fa-solid <?= $seoConfigured ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>" aria-hidden="true"></i><span>SEO metadata</span><strong><?= $seoConfigured ? 'Ready' : 'Needs review' ?></strong></li>
        <li class="<?= $missingImageCount === 0 ? 'is-ok' : 'is-warning' ?>"><i class="fa-solid <?= $missingImageCount === 0 ? 'fa-circle-check' : 'fa-image' ?>" aria-hidden="true"></i><span><?= $isLegalEditor ? 'Media' : 'Images' ?></span><strong><?= Security::e($isLegalEditor ? 'Not required' : ($missingImageCount === 0 ? 'Ready' : format_number($missingImageCount) . ' missing')) ?></strong></li>
        <li class="<?= $emptyFieldCount === 0 ? 'is-ok' : 'is-warning' ?>"><i class="fa-solid <?= $emptyFieldCount === 0 ? 'fa-circle-check' : 'fa-pen-to-square' ?>" aria-hidden="true"></i><span>Empty fields</span><strong><?= Security::e(format_number($emptyFieldCount)) ?></strong></li>
      </ul>
    </section>

    <section class="card sa-cms-inspector sa-cms-inspector--actions">
      <h3>Quick Actions</h3>
      <div class="sa-cms-quick-actions">
<?php if (($page['route_path'] ?? '') !== ''): ?>
        <a class="btn btn--primary" href="<?= Security::e(Url::to((string)$page['route_path'])) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Preview Page</a>
<?php endif; ?>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/media-library.php')) ?>"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Media Library</a>
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms.php')) ?>"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> CMS Pages</a>
      </div>
    </section>
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

function cms_editor_template_description(string $template): string
{
    return match ($template) {
        'landing' => 'Landing Page',
        'legal' => 'Legal Document',
        'content' => 'Content Page',
        default => 'Structured CMS Page',
    };
}

function cms_editor_is_retired_section(string $pageSlug, string $template, array $section): bool
{
    if ($template === 'legal') {
        return !in_array((string)($section['section_key'] ?? ''), ['legal_document'], true);
    }

    if ($pageSlug !== 'home') {
        if ($pageSlug === 'about') {
            $activeAbout = [
                'about_hero',
                'programme_overview',
                'core_commitments',
                'programme_history',
                'legal_framework',
                'partners_teaser',
                'faq_teaser',
                'leadership_contact',
                'apply_cta',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeAbout, true);
        }

        if ($pageSlug === 'faq') {
            $activeFaq = [
                'faq_hero',
                'faq_popular',
                'faq_library',
                'faq_contact_cta',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeFaq, true);
        }

        if ($pageSlug === 'leadership') {
            $activeLeadership = [
                'leadership_hero',
                'leadership_org_chart',
                'leadership_national',
                'leadership_spotlight',
                'leadership_contractors',
                'leadership_quotes',
                'leadership_partners',
                'leadership_contact_cta',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeLeadership, true);
        }

        if ($pageSlug === 'contact') {
            $activeContact = [
                'contact_hero',
                'contact_quick_cards',
                'contact_form',
                'contact_office',
                'contact_departments',
                'contact_faq_banner',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeContact, true);
        }

        if ($pageSlug === 'projects') {
            $activeProjects = [
                'projects_hero',
                'projects_filters',
                'projects_listing',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeProjects, true);
        }

        if ($pageSlug === 'project-detail') {
            $activeProjectDetail = [
                'project_detail_labels',
                'project_detail_overview',
                'project_detail_sidebar',
                'project_detail_apply_cta',
                'project_detail_not_found',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeProjectDetail, true);
        }

        if ($pageSlug === 'constituencies') {
            $activeConstituencies = [
                'constituencies_hero',
                'constituencies_map',
                'constituencies_progress',
                'constituencies_grid',
                'constituencies_apply_cta',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeConstituencies, true);
        }

        if ($pageSlug === 'constituency-detail') {
            $activeConstituencyDetail = [
                'constituency_detail_labels',
                'constituency_detail_facts',
                'constituency_detail_related',
                'constituency_detail_apply_cta',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeConstituencyDetail, true);
        }

        if ($pageSlug === 'news') {
            $activeNews = [
                'news_hero',
                'news_mosaic',
                'news_featured',
                'news_filters',
                'news_listing',
                'news_cta',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeNews, true);
        }

        if ($pageSlug === 'news-article') {
            $activeNewsArticle = [
                'news_article_labels',
                'news_article_sidebar',
                'news_article_downloads',
                'news_article_not_found',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeNewsArticle, true);
        }

        if ($pageSlug === 'gallery') {
            $activeGallery = [
                'gallery_hero',
                'gallery_highlights',
                'gallery_archive',
                'gallery_site_progress',
                'gallery_videos',
                'gallery_empty_states',
            ];
            return !in_array((string)($section['section_key'] ?? ''), $activeGallery, true);
        }

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
    $fieldGroups = cms_editor_group_fields($fields);
    $blockClass = 'card sa-home-cms-block' . (str_starts_with($anchor, 'about-') ? ' sa-about-cms-block' : '');
    ?>
    <article id="<?= Security::e($anchor) ?>" class="<?= Security::e($blockClass) ?>" data-cms-section data-section-id="<?= (int)$section['id'] ?>" data-section-key="<?= Security::e($section['section_key']) ?>">
      <header class="sa-home-cms-block__head">
        <div>
          <h3><?= Security::e($title) ?></h3>
          <p><?= Security::e($description) ?></p>
        </div>
        <div class="sa-cms-section-tools">
          <button class="btn btn--primary btn--sm" type="button" data-cms-save-section><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Block</button>
        </div>
      </header>
      <div class="sa-home-cms-fields">
<?php foreach ($fieldGroups['base'] as $field): ?>
        <?php cms_editor_home_field($field, $content); ?>
<?php endforeach; ?>
      </div>
<?php foreach ($fieldGroups['series'] as $series): ?>
      <section class="sa-cms-series-panel" aria-label="<?= Security::e($series['label']) ?>">
        <div class="sa-cms-series-panel__head">
          <span><?= Security::e($series['label']) ?></span>
          <strong><?= Security::e(format_number(count($series['items']))) ?> <?= count($series['items']) === 1 ? 'item' : 'items' ?></strong>
        </div>
        <div class="sa-cms-series-grid">
<?php foreach ($series['items'] as $number => $seriesFields): ?>
          <article class="sa-cms-series-card">
            <header>
              <span><?= Security::e($series['singular']) ?></span>
              <strong><?= Security::e(format_number((int)$number)) ?></strong>
            </header>
            <div class="sa-cms-series-card__fields">
<?php foreach ($seriesFields as $field): ?>
              <?php cms_editor_home_field($field, $content, true); ?>
<?php endforeach; ?>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>
<?php endforeach; ?>
    </article>
    <?php
}

function cms_editor_group_fields(array $fields): array
{
    $groups = [
        'base' => [],
        'series' => [],
    ];

    foreach ($fields as $field) {
        $key = (string)($field[0] ?? '');
        if (!preg_match('/^(pillar|item|card)_(\d+)_(.+)$/', $key, $matches)) {
            $groups['base'][] = $field;
            continue;
        }

        $prefix = $matches[1];
        $number = (int)$matches[2];
        if (!isset($groups['series'][$prefix])) {
            $groups['series'][$prefix] = [
                'label' => cms_editor_series_label($prefix),
                'singular' => cms_editor_series_singular($prefix),
                'items' => [],
            ];
        }

        $field[1] = preg_replace('/\s+' . preg_quote((string)$number, '/') . '$/', '', (string)($field[1] ?? 'Field'));
        $groups['series'][$prefix]['items'][$number][] = $field;
    }

    foreach ($groups['series'] as &$series) {
        ksort($series['items']);
    }
    unset($series);

    return $groups;
}

function cms_editor_series_label(string $prefix): string
{
    return match ($prefix) {
        'pillar' => 'Commitment Cards',
        'item' => 'Timeline Items',
        'card' => 'Policy Cards',
        default => 'Grouped Items',
    };
}

function cms_editor_series_singular(string $prefix): string
{
    return match ($prefix) {
        'pillar' => 'Commitment',
        'item' => 'Timeline item',
        'card' => 'Policy card',
        default => 'Item',
    };
}

function cms_editor_publication_status(string $status): string
{
    return strtolower(trim($status)) === 'published' ? 'published' : 'draft';
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

function cms_editor_ensure_leadership_sections(int $pageId): void
{
    $defaults = [
        ['leadership_hero', 'Leadership Hero', 'leadership_hero', 10, [
            'background_image' => 'uploads/heroes/hero-main.jpg',
            'background_alt' => 'Affordable housing team at a Trans-Nzoia construction site',
            'eyebrow' => 'National Government - State Dept. of Housing & Urban Development',
            'title' => "The People\nDelivering Trans-Nzoia's\nHousing Future",
            'subtitle' => 'A nationally-led programme with dedicated field representation in Trans-Nzoia County - from Cabinet level to construction site supervisors, every tier accountable.',
            'active_projects_label' => 'Active Projects',
            'units_label' => 'Units Planned',
            'constituencies_label' => 'Constituencies',
            'contractors_label' => 'Contractors Active',
        ]],
        ['leadership_org_chart', 'Command Chain', 'leadership_org_chart', 20, [
            'eyebrow' => 'Chain of Command',
            'title' => 'From National Government to Your Doorstep',
            'subtitle' => 'The Affordable Housing Programme flows from presidential mandate through Cabinet and the State Department, all the way to field representatives on the ground in Trans-Nzoia County.',
            'empty_text' => 'Add published leadership profiles to populate the command chain.',
        ]],
        ['leadership_national', 'Senior Officials', 'leadership_national', 30, [
            'eyebrow' => 'National Leadership',
            'title' => "Senior Officials\nDriving the Programme",
            'subtitle' => 'The programme is accountable to the highest levels of national government, ensuring transparency, legal compliance and adequate funding.',
            'display_count' => '4',
            'empty_text' => 'Published official profiles will appear here.',
        ]],
        ['leadership_spotlight', 'County Director Spotlight', 'leadership_spotlight', 40, [
            'eyebrow' => 'County Field Representative',
            'fallback_title' => 'County Director profile pending',
            'fallback_text' => 'Mark one leadership profile as spotlight to populate this section.',
            'contact_button_label' => 'Contact the Field Office',
            'contact_button_url' => 'contact.php',
        ]],
        ['leadership_contractors', 'Contractor Showcase', 'leadership_contractors', 50, [
            'eyebrow' => 'Contractors on the Ground',
            'title' => "Building Trans-Nzoia's Affordable Homes",
            'subtitle' => 'NCA registered contractors are delivering project sites across Trans-Nzoia County. Each record tracks assignment, progress and compliance status.',
            'display_count' => '8',
            'disclaimer' => 'All contractors are NCA-registered and were procured through open competitive tendering under the Public Procurement and Asset Disposal Act, 2015.',
            'empty_text' => 'Contractor records will appear here after they are added to the Leadership Registry.',
        ]],
        ['leadership_quotes', 'Leadership Quotes', 'leadership_quotes', 60, [
            'eyebrow' => 'In Their Own Words',
            'title' => "What Our Leaders &\nBuilders Say",
            'display_count' => '5',
            'empty_text' => 'Featured quotes will appear here after they are added to the Leadership Registry.',
        ]],
        ['leadership_partners', 'Implementing Partners', 'leadership_partners', 70, [
            'eyebrow' => 'Implementing Partners',
            'title' => "The Organisations\nBehind the Programme",
            'subtitle' => "From statutory oversight to environmental clearance and mortgage financing, national bodies work in concert to deliver Trans-Nzoia's affordable housing pipeline.",
            'display_count' => '6',
            'empty_text' => 'Featured stakeholder records will appear here.',
        ]],
        ['leadership_contact_cta', 'Leadership Contact CTA', 'leadership_contact_cta', 80, [
            'eyebrow' => 'Trans-Nzoia Field Office',
            'title' => 'Reach the Programme Leadership',
            'subtitle' => 'The Trans-Nzoia County Director of Housing operates from Ardhi House in Kitale. For project enquiries, contractor matters or beneficiary questions, contact the field office.',
            'primary_label' => 'Send a Message',
            'primary_url' => 'contact.php',
            'secondary_label' => 'Apply via Boma Yangu',
            'secondary_url' => 'https://bomayangu.go.ke',
            'map_label' => 'Ardhi House',
            'map_sublabel' => "County Commissioner's Premises, Kitale",
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_stakeholders_sections(int $pageId): void
{
    $defaults = [
        ['stakeholders_hero', 'Stakeholders Hero', 'stakeholders_hero', 10, [
            'background_image' => 'uploads/heroes/hero-main.jpg',
            'background_alt' => 'Affordable housing programme team and construction site in Trans-Nzoia County',
            'eyebrow' => 'Programme Ecosystem - Partners, Institutions & Communities',
            'title' => "Everyone Who\nMakes It Happen",
            'subtitle' => 'A transparent map of every institution, partner, contractor, regulator and community driving the Affordable Housing Programme in Trans-Nzoia County - from national policy to ground-level delivery.',
            'government_label' => 'Government Tiers',
            'partners_label' => 'Implementing Partners',
            'households_label' => 'Households Targeted',
            'oversight_label' => 'Oversight Bodies',
            'scroll_label' => 'Explore the Ecosystem',
        ]],
        ['stakeholders_ecosystem', 'Programme Web', 'stakeholders_ecosystem', 20, [
            'eyebrow' => 'Ecosystem Overview',
            'title' => 'The Programme Web',
            'subtitle' => 'Click any stakeholder category to highlight its role and connections within the programme delivery chain.',
            'center_label' => "Trans-Nzoia\nAHP",
            'empty_text' => 'Click any stakeholder category above to explore its role in the programme.',
        ]],
        ['stakeholders_pillars', 'Delivery Pillars', 'stakeholders_pillars', 30, [
            'eyebrow' => 'Stakeholder Groups',
            'title' => "Six Pillars of\nProgramme Delivery",
            'subtitle' => 'Every entity in the Trans-Nzoia AHP belongs to one of six interconnected stakeholder groups - each with distinct responsibilities and accountabilities.',
            'display_count' => '6',
            'empty_text' => 'Published stakeholder groups will appear here.',
        ]],
        ['stakeholders_mandates', 'Mandates & Accountabilities', 'stakeholders_mandates', 40, [
            'eyebrow' => 'Mandates & Accountabilities',
            'title' => 'Who Does What?',
            'subtitle' => 'Expand each stakeholder group to understand their legal mandate, core responsibilities and how they interact with other programme actors.',
            'empty_text' => 'Add mandate details to stakeholder groups to populate this section.',
        ]],
        ['stakeholders_milestones', 'Engagement Milestones', 'stakeholders_milestones', 50, [
            'eyebrow' => 'Programme Journey',
            'title' => "Key Stakeholder\nEngagement Milestones",
            'subtitle' => 'From presidential directive to community baraza - a chronological record of how stakeholders have shaped this programme.',
            'display_count' => '10',
            'empty_text' => 'Stakeholder milestones will appear here after they are published.',
        ]],
        ['stakeholders_voices', 'Community Voices', 'stakeholders_voices', 60, [
            'eyebrow' => 'Community Voice',
            'title' => "The People Behind the\nProgramme",
            'subtitle' => 'Ward representatives, registered beneficiaries and community advocates share their experiences with the Trans-Nzoia AHP.',
            'display_count' => '4',
            'empty_text' => 'Community voices will appear here after they are published.',
        ]],
        ['stakeholders_formal_partners', 'Formal Partners', 'stakeholders_formal_partners', 70, [
            'eyebrow' => 'Formal Partnerships',
            'title' => "MoU Signatories &\nInstitutional Partners",
            'subtitle' => 'All formal implementing partners and oversight institutions with active agreements or collaborative mandates.',
            'display_count' => '9',
            'empty_text' => 'Formal partner organisations will appear here.',
        ]],
        ['stakeholders_engagement', 'How to Engage', 'stakeholders_engagement', 80, [
            'eyebrow' => 'Get Involved',
            'title' => "How to Engage the\nProgramme",
            'subtitle' => 'Whether you are a potential beneficiary, a community leader, a journalist or an interested partner - here is how to connect with the Trans-Nzoia AHP.',
            'empty_text' => 'Engagement paths will appear here after they are published.',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_faq_sections(int $pageId): void
{
    $defaults = [
        ['faq_hero', 'FAQ Hero', 'faq_hero', 10, [
            'background_image' => 'uploads/heroes/hero-main.jpg',
            'background_alt' => 'Affordable housing construction site in Trans-Nzoia County',
            'eyebrow' => 'Frequently Asked Questions',
            'title' => "Your Questions,\nAnswered.",
            'subtitle' => 'Everything you need to know about eligibility, applying, monthly contributions, unit allocation and your rights as an AHP beneficiary in Trans-Nzoia County.',
            'search_placeholder' => 'Search questions, e.g. "How do I apply?"',
            'questions_label' => 'Questions Answered',
            'categories_label' => 'Categories',
            'updates_value' => 'Monthly',
            'updates_label' => 'Content Updates',
        ]],
        ['faq_popular', 'Popular Questions', 'faq_popular', 20, [
            'label' => 'Popular Questions',
            'display_count' => '6',
            'empty_text' => 'Mark questions as popular in the FAQ manager to populate this row.',
        ]],
        ['faq_library', 'FAQ Library', 'faq_library', 30, [
            'all_label' => 'All',
            'no_results_title' => 'No questions match this search',
            'no_results_text' => 'Try a different keyword or clear the search.',
        ]],
        ['faq_contact_cta', 'Question Support CTA', 'faq_cta', 40, [
            'title' => 'Still Have Questions?',
            'subtitle' => 'Our county housing team is available Monday to Friday, 8am-5pm. Reach us by phone, email or in person at the Kitale field office.',
            'contact_button_label' => 'Send Us a Message',
            'contact_button_url' => 'contact.php',
            'apply_title' => 'Ready to Apply?',
            'apply_subtitle' => "Applications are processed entirely through the Government's eCitizen portal - free, secure and available 24/7.",
            'apply_button_label' => 'Apply via eCitizen',
            'apply_button_url' => 'https://ecitizen.go.ke',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_projects_sections(int $pageId): void
{
    $defaults = [
        ['projects_hero', 'Projects Hero', 'projects_hero', 10, [
            'background_image' => 'uploads/gallery/maili-tatu-2.jpg',
            'background_alt' => 'Affordable housing construction site in Trans-Nzoia County',
            'eyebrow' => 'AHP Projects',
            'title_plain' => 'Housing Projects',
            'title_highlight' => 'Directory',
            'subtitle' => 'Track every affordable housing project across Trans-Nzoia County - construction progress, contractor details, timelines and unit counts in real time.',
            'total_projects_label' => 'Total Projects',
            'units_label' => 'Units Planned',
            'active_label' => 'Active',
            'constituencies_label' => 'Constituencies',
        ]],
        ['projects_filters', 'Project Filters', 'projects_filters', 20, [
            'search_placeholder' => 'Search projects...',
            'status_label' => 'Status:',
            'all_label' => 'All',
            'active_label' => 'Active',
            'planning_label' => 'Planning',
            'constituency_label' => 'Constituency:',
            'sort_label' => 'Sort:',
            'sort_completion_desc' => 'Completion high to low',
            'sort_completion_asc' => 'Completion low to high',
            'sort_units_desc' => 'Most units',
            'sort_name_asc' => 'Name A-Z',
            'reset_label' => 'Clear filters',
        ]],
        ['projects_listing', 'Project Listing', 'projects_listing', 30, [
            'results_prefix' => 'Showing',
            'project_single' => 'project',
            'project_plural' => 'projects',
            'units_label' => 'Units',
            'complete_label' => 'Complete',
            'view_label' => 'View Project',
            'pagination_showing_label' => 'Showing',
            'pagination_of_label' => 'of',
            'empty_title' => 'No projects found',
            'empty_text' => 'Try adjusting your filters or search term.',
            'empty_reset_label' => 'Clear all filters',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_project_detail_sections(int $pageId): void
{
    $defaults = cms_editor_project_detail_defaults();

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_project_detail_defaults(): array
{
    return [
        ['project_detail_labels', 'Template Labels', 'project_detail_labels', 10, [
            'projects_breadcrumb_label' => 'Projects',
            'active_status_label' => 'Active',
            'planning_status_label' => 'Planning',
            'completed_status_label' => 'Completed',
            'watch_status_label' => 'On Hold',
            'started_label' => 'Started',
            'delivery_label' => 'Est. Delivery',
            'construction_complete_label' => 'Construction Complete',
            'units_label' => 'Units Planned',
            'ward_label' => 'Ward',
            'contractor_label' => 'Contractor',
            'funding_label' => 'Funding Source',
            'start_date_label' => 'Start Date',
            'est_delivery_label' => 'Est. Delivery',
        ]],
        ['project_detail_overview', 'Overview, Progress & Media', 'project_detail_overview', 20, [
            'section_label' => 'About This Project',
            'title' => 'Project Overview',
            'lead_agency_label' => 'Lead Agency',
            'site_engineer_label' => 'Site Engineer',
            'funding_label' => 'Funding',
            'current_activity_label' => 'Current Activity',
            'progress_label' => 'Construction Progress',
            'progress_title' => 'Live Progress Tracker',
            'overall_completion_label' => 'Overall Completion',
            'progress_note' => 'Data updated regularly by the Trans-Nzoia County Housing Department.',
            'units_in_progress_label' => 'Units In Progress',
            'target_delivery_label' => 'Target Delivery',
            'timeline_label' => 'Key Milestones',
            'timeline_title' => 'Construction Timeline',
            'timeline_empty_text' => 'Milestones will appear after they are added to this project.',
            'gallery_label' => 'Site Photography',
            'gallery_title' => 'Photo Gallery',
            'gallery_empty_text' => 'Site photography will be added as construction progresses.',
        ]],
        ['project_detail_sidebar', 'Sidebar', 'project_detail_sidebar', 30, [
            'contractor_title' => 'Contractor',
            'contractor_role_label' => 'Principal Contractor',
            'project_info_title' => 'Project Info',
            'constituency_label' => 'Constituency',
            'status_label' => 'Status',
            'target_units_label' => 'Target Units',
            'completion_label' => 'Completion',
            'related_title' => 'Related',
            'constituency_link_suffix' => 'Constituency',
            'all_projects_prefix' => 'All',
            'back_projects_label' => 'Back to All Projects',
        ]],
        ['project_detail_apply_cta', 'Application CTA', 'project_detail_apply_cta', 40, [
            'title' => 'Interested in a Unit?',
            'subtitle' => 'Register on the national Boma Yangu portal to apply for affordable housing in Trans-Nzoia County.',
            'button_label' => 'Apply on Boma Yangu',
            'button_url' => 'https://app.bomayangu.go.ke',
        ]],
        ['project_detail_not_found', 'Not Found', 'project_detail_not_found', 50, [
            'title' => 'Project Not Found',
            'text' => "The project you're looking for doesn't exist or the URL is incorrect.",
            'button_label' => 'Back to All Projects',
        ]],
    ];
}

function cms_editor_ensure_constituencies_sections(int $pageId): void
{
    $defaults = [
        ['constituencies_hero', 'Constituencies Hero', 'constituencies_hero', 10, [
            'background_image' => 'uploads/gallery/maili-tatu-3.jpg',
            'background_alt' => 'Affordable housing construction site in Trans-Nzoia County',
            'eyebrow' => 'Coverage Map',
            'title_prefix' => 'All',
            'title_highlight' => '5 Constituencies',
            'subtitle' => 'Explore how the Affordable Housing Programme reaches every corner of Trans-Nzoia County - from Endebess on the Uganda border to Kwanza in the south.',
            'constituencies_label' => 'Constituencies',
            'projects_label' => 'Projects',
            'units_label' => 'Units Planned',
            'residents_label' => 'Residents Served',
        ]],
        ['constituencies_map', 'Map & Side List', 'constituencies_map', 20, [
            'title' => 'Trans-Nzoia County',
            'subtitle' => 'Click a constituency to explore its housing projects',
            'active_label' => 'Active construction',
            'planning_label' => 'Planning stage',
            'selected_label' => 'Selected',
            'empty_text' => 'Constituency data will appear after projects and wards are added to the registry.',
        ]],
        ['constituencies_progress', 'County-Wide Progress', 'constituencies_progress', 30, [
            'title' => "County-Wide\nProgramme\nProgress",
            'subtitle' => 'Across all {constituencies} constituencies, the programme is delivering {units} affordable housing units - targeting Kenyans registered on the national Boma Yangu portal.',
            'button_label' => 'Browse All Projects',
            'button_url' => 'projects.php',
        ]],
        ['constituencies_grid', 'Browse Grid', 'constituencies_grid', 40, [
            'eyebrow' => 'Browse by Constituency',
            'title_prefix' => 'All',
            'title_highlight' => '5 Constituencies',
            'sort_label' => 'Sort:',
            'default_label' => 'Default',
            'progress_label' => 'Highest Progress',
            'units_label' => 'Most Units',
            'alpha_label' => 'A - Z',
        ]],
        ['constituencies_apply_cta', 'Application CTA', 'constituencies_apply_cta', 50, [
            'title' => 'Ready to Apply for Affordable Housing?',
            'subtitle' => 'Register on the national Boma Yangu portal to join the Trans-Nzoia County AHP allocation list.',
            'primary_label' => 'Apply on Boma Yangu',
            'primary_url' => 'https://bomayangu.go.ke',
            'secondary_label' => 'View All Projects',
            'secondary_url' => 'projects.php',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_constituency_detail_sections(int $pageId): void
{
    $defaults = cms_editor_constituency_detail_defaults();

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_constituency_detail_defaults(): array
{
    return [
        ['constituency_detail_labels', 'Template Labels', 'constituency_detail_labels', 10, [
            'active_status_label' => 'Active Construction',
            'planning_status_label' => 'Planning Stage',
            'projects_label' => 'Projects',
            'units_label' => 'Units Planned',
            'population_label' => 'Population',
            'completion_label' => 'Avg. Completion',
            'wards_label' => 'Wards',
            'projects_title_suffix' => 'Projects',
            'projects_subtitle' => 'All housing developments in this constituency under the national AHP programme.',
            'view_all_projects_label' => 'View all projects',
            'unit_card_label' => 'Units',
            'complete_card_label' => 'Complete',
            'view_project_label' => 'View Project',
            'empty_projects_text' => 'No projects listed yet for this constituency.',
            'progress_eyebrow' => 'Progress Overview',
            'progress_title_suffix' => 'Construction Progress',
            'progress_subtitle' => 'Across {projects} active {project_word}, {constituency} Constituency has delivered {units} units under the national AHP programme. Work is progressing across {wards} wards.',
            'total_units_label' => 'Total Units',
        ]],
        ['constituency_detail_facts', 'Facts & Location', 'constituency_detail_facts', 20, [
            'facts_title' => 'Constituency Facts',
            'county_label' => 'County',
            'population_label' => 'Population',
            'wards_label' => 'Wards',
            'lead_agency_label' => 'Lead Agency',
            'lead_agency_value' => 'State Dept. of Housing',
            'funding_label' => 'Funding',
            'funding_value' => 'National AHP Fund + County Budget',
            'programme_label' => 'Programme',
            'programme_value' => 'National Affordable Housing Programme',
            'location_title' => 'Location',
            'all_constituencies_label' => 'All Constituencies',
        ]],
        ['constituency_detail_related', 'Other Constituencies', 'constituency_detail_related', 30, [
            'title' => 'Other Constituencies',
            'subtitle' => 'Explore housing developments across Trans-Nzoia County.',
            'view_all_label' => 'View all',
            'explore_label' => 'Explore',
            'not_found_title' => 'Constituency Not Found',
            'not_found_text' => "The constituency you're looking for doesn't exist or the URL is incorrect.",
            'not_found_button' => 'Back to Constituencies',
        ]],
        ['constituency_detail_apply_cta', 'Application CTA', 'constituency_detail_apply_cta', 40, [
            'title' => 'Ready to Apply for Affordable Housing?',
            'subtitle' => 'Register on the national Boma Yangu portal to join the allocation list for this constituency.',
            'primary_label' => 'Apply on Boma Yangu',
            'primary_url' => 'https://bomayangu.go.ke',
            'secondary_label' => 'View All Projects',
            'secondary_url' => 'projects.php',
        ]],
    ];
}

function cms_editor_ensure_news_sections(int $pageId): void
{
    $defaults = [
        ['news_hero', 'News Hero', 'news_hero', 10, [
            'eyebrow' => 'News & Updates',
            'title_plain_1' => 'Latest',
            'title_accent' => 'News &',
            'title_plain_2' => 'Announcements',
            'subtitle' => 'Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.',
            'search_placeholder' => 'Search articles...',
            'articles_label' => 'Articles',
            'categories_label' => 'Categories',
            'last_updated_label' => 'Last Updated',
        ]],
        ['news_mosaic', 'Category Mosaic', 'news_mosaic', 20, [
            'show_mosaic' => '1',
            'programme_label' => 'Programme',
            'groundbreaking_label' => 'Groundbreaking',
            'construction_label' => 'Construction',
            'policy_label' => 'Policy',
            'community_label' => 'Community',
            'official_label' => 'Official',
            'field_reports_label' => 'Field Reports',
            'total_label' => "Total\nArticles",
        ]],
        ['news_featured', 'Featured Story', 'news_featured', 30, [
            'section_label' => 'Featured Story',
            'featured_badge' => 'Featured',
            'read_button_label' => 'Read Full Article',
            'empty_title' => 'No featured story selected',
            'empty_text' => 'Mark a published article as featured to populate this section.',
        ]],
        ['news_filters', 'Filters & Sorting', 'news_filters', 40, [
            'all_label' => 'All Articles',
            'results_suffix_single' => 'article',
            'results_suffix_plural' => 'articles',
            'latest_label' => 'Latest First',
            'oldest_label' => 'Oldest First',
        ]],
        ['news_listing', 'Article Listing', 'news_listing', 50, [
            'title' => 'All Articles',
            'subtitle' => 'Showing latest news & updates',
            'read_more_label' => 'Read More',
            'load_more_label' => 'Load More Articles',
            'empty_title' => 'No Articles Found',
            'empty_text' => 'No articles match your current search or filter. Try a different keyword or category.',
            'empty_reset_label' => 'Clear Filters',
        ]],
        ['news_cta', 'Stay Updated CTA', 'news_cta', 60, [
            'title_prefix' => 'Stay',
            'title_highlight' => 'Up to Date',
            'subtitle' => 'Follow the programme on social media or apply for housing directly through the eCitizen portal to receive official notifications about unit availability and beneficiary selection.',
            'x_url' => '#',
            'facebook_url' => '#',
            'youtube_url' => '#',
            'button_label' => 'Apply via eCitizen',
            'button_url' => 'https://ecitizen.go.ke',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_news_article_sections(int $pageId): void
{
    $defaults = [
        ['news_article_labels', 'Article Labels', 'news_article_labels', 10, [
            'news_breadcrumb_label' => 'News & Updates',
            'default_read_time' => '4 min',
            'author_fallback' => 'Trans-Nzoia County Department of Land, Housing & Physical Planning',
            'view_all_news_label' => 'View all news',
        ]],
        ['news_article_sidebar', 'Sidebar Labels', 'news_article_sidebar', 20, [
            'details_title' => 'Article Details',
            'category_label' => 'Category',
            'format_label' => 'Format',
            'published_label' => 'Published',
            'source_label' => 'Source',
            'related_title' => 'Related Stories',
            'related_empty_text' => 'No related stories are available yet.',
        ]],
        ['news_article_downloads', 'Downloads & Links', 'news_article_downloads', 30, [
            'download_label' => 'Download Report',
            'external_label' => 'Open Source Link',
        ]],
        ['news_article_not_found', 'Not Found', 'news_article_not_found', 40, [
            'title' => 'Article not found',
            'text' => 'The article may be unpublished, archived or no longer available.',
            'button_label' => 'Back to News',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_gallery_sections(int $pageId): void
{
    $defaults = [
        ['gallery_hero', 'Gallery Hero', 'gallery_hero', 10, [
            'background_image' => 'uploads/gallery/maili-tatu-2.jpg',
            'background_alt' => 'Affordable housing construction site photography in Trans-Nzoia County',
            'breadcrumb_label' => 'Photo Gallery',
            'eyebrow' => 'Visual Documentation - Sites, Events & Progress',
            'title' => 'Programme in Pictures',
            'subtitle' => 'A visual record of every milestone - from groundbreaking ceremonies to community barazas, site inspections and construction progress across all five Trans-Nzoia constituencies.',
            'scroll_label' => 'Browse the gallery',
            'photos_label' => 'Photos Archived',
            'sites_label' => 'Sites Documented',
            'years_label' => 'Years of Coverage',
            'events_label' => 'Events Captured',
        ]],
        ['gallery_highlights', 'Featured Moments', 'gallery_highlights', 20, [
            'eyebrow' => 'Featured Moments',
            'title' => 'Programme Highlights',
            'subtitle' => 'Landmark moments captured - from official programme milestones to community handovers.',
            'empty_text' => 'Featured gallery moments will appear after they are marked as highlights.',
        ]],
        ['gallery_archive', 'Photo Archive', 'gallery_archive', 30, [
            'eyebrow' => 'Full Archive',
            'title' => 'Browse All Photos',
            'subtitle' => 'Filter by category, site or year to find specific documentation of the programme.',
            'category_label' => 'Category',
            'year_label' => 'Year',
            'all_label' => 'All',
            'showing_label' => 'Showing',
            'photos_label' => 'photos',
        ]],
        ['gallery_site_progress', 'Progress by Site', 'gallery_site_progress', 40, [
            'eyebrow' => 'By Constituency',
            'title' => 'Progress by Site',
            'subtitle' => 'Select a constituency to see its photos and current construction status.',
            'units_label' => 'Units Planned',
            'completion_label' => 'Completion',
            'photos_label' => 'Photos',
            'details_label' => 'View full site details',
        ]],
        ['gallery_videos', 'Progress Videos', 'gallery_videos', 50, [
            'eyebrow' => 'Video Updates',
            'title' => 'Progress Videos',
            'subtitle' => 'Watch construction progress reports, community barazas and official ceremony recordings.',
            'empty_text' => 'Progress videos will appear after they are published.',
        ]],
        ['gallery_empty_states', 'Empty States', 'gallery_empty_states', 60, [
            'no_results_text' => 'No photos match the selected filters.',
            'reset_label' => 'Clear filters',
            'lightbox_label' => 'Photo lightbox',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_ensure_contact_sections(int $pageId): void
{
    $defaults = [
        ['contact_hero', 'Contact Hero', 'contact_hero', 10, [
            'background_image' => 'uploads/heroes/hero-main.jpg',
            'background_alt' => 'Trans-Nzoia Affordable Housing Programme contact desk',
            'eyebrow' => 'Get in Touch',
            'title' => "We're Here\nto Help.",
            'subtitle' => 'Reach our county housing team for enquiries about the Affordable Housing Programme - applications, site progress, allocation status, or any other question.',
            'response_value' => '24 hrs',
            'response_label' => 'Response Time',
            'hours_value' => 'Mon - Fri',
            'hours_label' => '8am - 5pm EAT',
            'departments_value' => '4',
            'departments_label' => 'Departments',
        ]],
        ['contact_quick_cards', 'Quick Contact Cards', 'contact_quick_cards', 20, [
            'phone_label' => 'Call Us',
            'phone_hint' => 'Mon-Fri, 8am-5pm',
            'email_label' => 'Email Us',
            'email_hint' => 'Reply within 24 hours',
            'whatsapp_label' => 'WhatsApp',
            'whatsapp_hint' => 'Quick questions welcome',
            'visit_label' => 'Visit Us',
            'visit_hint' => 'Open in Google Maps',
        ]],
        ['contact_form', 'Message Form', 'contact_form', 30, [
            'title' => 'Send Us a Message',
            'subtitle' => 'Fill in the form below and a member of our team will get back to you within one business day.',
            'name_placeholder' => 'e.g. John Wafula',
            'phone_placeholder' => '07XX XXX XXX',
            'email_placeholder' => 'you@example.com',
            'subject_placeholder' => 'Select a subject...',
            'message_placeholder' => 'Please describe your enquiry in detail...',
            'file_label' => 'Choose file (PDF, JPG, PNG - max 5MB)',
            'privacy_note' => 'Your information is protected under our Privacy Policy and will not be shared with third parties.',
            'submit_label' => 'Send Message',
            'success_title' => 'Message Sent!',
            'success_text' => 'Thank you. We have received your message and will respond within one business day.',
            'error_text' => 'Something went wrong. Please try again or email us directly.',
        ]],
        ['contact_office', 'Office & Map', 'contact_office', 40, [
            'office_title' => 'AHP Field Office - Trans-Nzoia',
            'weekday_label' => 'Monday - Friday',
            'weekday_hours' => '8:00am - 5:00pm',
            'saturday_label' => 'Saturday',
            'saturday_hours' => '9:00am - 1:00pm',
            'holiday_label' => 'Sunday & Public Holidays',
            'holiday_hours' => 'Closed',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3986.8!2d35.0062!3d1.0154!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sKitale%2C+Trans-Nzoia!5e0!3m2!1sen!2ske!4v1',
            'map_link_label' => 'Open in Google Maps',
            'helpline_title' => 'National AHB Helpline',
            'helpline_number' => '0800 723 133',
            'helpline_note' => 'Toll-free - Mon-Fri 8am-6pm',
        ]],
        ['contact_departments', 'Departments Directory', 'contact_departments', 50, [
            'eyebrow' => 'Departments',
            'title' => 'Who to Contact',
            'subtitle' => 'Reach the right team directly for faster assistance.',
            'empty_text' => 'Department contacts will appear after they are added to the contact directory.',
        ]],
        ['contact_faq_banner', 'FAQ Shortcut Banner', 'contact_faq_banner', 60, [
            'title' => 'Have a quick question?',
            'subtitle' => 'Browse our Frequently Asked Questions for instant answers.',
            'pill_1_label' => 'How do I apply?',
            'pill_1_url' => 'faq.php#q-how-apply',
            'pill_2_label' => 'What is the levy?',
            'pill_2_url' => 'faq.php#q-levy-amount',
            'pill_3_label' => 'When do units complete?',
            'pill_3_url' => 'faq.php#q-when-complete',
            'button_label' => 'View All FAQs',
            'button_url' => 'faq.php',
        ]],
    ];

    foreach ($defaults as [$key, $label, $type, $sort, $content]) {
        if (!CmsSection::findForPage($pageId, $key)) {
            CmsSection::upsert($pageId, [
                'section_key' => $key,
                'label' => $label,
                'section_type' => $type,
                'editor_mode' => $type,
                'sort_order' => $sort,
                'is_visible' => 1,
                'is_locked' => 1,
                'content' => $content,
            ]);
        }
    }
}

function cms_editor_home_field(array $field, array $content, bool $isSeriesField = false): void
{
    [$key, $label, $type] = [$field[0], $field[1], $field[2]];
    $folder = $field[3] ?? 'cms';
    $value = (string)($content[$key] ?? '');
    $wide = in_array($type, ['textarea', 'upload'], true) ? ' form-field--full' : '';
    $fieldClass = $wide . ' sa-cms-editor-field sa-cms-editor-field--' . preg_replace('/[^a-z0-9_-]+/', '-', strtolower((string)$type));
    if ($isSeriesField) {
        $fieldClass .= ' sa-cms-editor-field--series';
    }
    if ($type === 'upload') {
        ?>
        <div class="form-field<?= Security::e($fieldClass) ?>" data-cms-field-wrap="<?= Security::e($key) ?>">
          <span class="form-label"><?= Security::e($label) ?></span>
          <?php cms_editor_asset_control($key, $value, (string)$folder); ?>
        </div>
        <?php
        return;
    }
    ?>
    <label class="form-field<?= Security::e($fieldClass) ?>" data-cms-field-wrap="<?= Security::e($key) ?>">
      <span class="form-label"><?= Security::e($label) ?></span>
<?php if ($type === 'textarea'): ?>
      <textarea class="form-textarea" rows="3" data-cms-field="<?= Security::e($key) ?>"><?= Security::e($value) ?></textarea>
<?php elseif ($type === 'number'): ?>
      <input class="form-input" type="number" min="0" data-cms-field="<?= Security::e($key) ?>" value="<?= Security::e($value) ?>">
<?php elseif ($type === 'checkbox'): ?>
      <span class="sa-home-check"><input type="checkbox" data-cms-field="<?= Security::e($key) ?>" value="1" <?= in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) ? 'checked' : '' ?>> <strong><?= Security::e($label) ?></strong></span>
<?php else: ?>
      <input class="form-input" data-cms-field="<?= Security::e($key) ?>" value="<?= Security::e($value) ?>">
<?php endif; ?>
    </label>
    <?php
}

function cms_editor_asset_control(string $field, string $value, string $folder = 'cms', bool $isPageField = false, string $hint = 'Choose an approved image from the shared media library.'): void
{
    $path = trim($value);
    $url = $path !== '' ? Url::asset($path) : '';
    $filename = $path !== '' ? basename($path) : 'No asset selected';
    $targetAttribute = $isPageField ? 'name="' . Security::e($field) . '"' : 'data-cms-field="' . Security::e($field) . '"';
    ?>
    <div class="sa-cms-asset-control<?= $path !== '' ? ' has-asset' : '' ?>" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>">
      <div class="sa-cms-asset-control__preview" data-cms-asset-preview>
<?php if ($url !== ''): ?>
        <img src="<?= Security::e($url) ?>" alt="">
<?php else: ?>
        <span><i class="fa-solid fa-image" aria-hidden="true"></i></span>
<?php endif; ?>
      </div>
      <div class="sa-cms-asset-control__body">
        <div class="sa-cms-asset-control__meta">
          <span><i class="fa-solid fa-folder-open" aria-hidden="true"></i> <?= Security::e(status_label($folder)) ?></span>
          <strong data-cms-asset-name><?= Security::e($filename) ?></strong>
          <small><?= Security::e($hint) ?></small>
        </div>
        <div class="sa-cms-asset-control__path">
          <input class="form-input" <?= $targetAttribute ?> data-cms-upload-target value="<?= Security::e($path) ?>" placeholder="No image selected yet">
        </div>
        <div class="sa-cms-asset-control__actions">
          <button class="btn btn--primary btn--sm" type="button" data-media-picker-open data-media-picker-folder="<?= Security::e($folder) ?>">
            <i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose from Library
          </button>
        </div>
      </div>
    </div>
    <?php
}
