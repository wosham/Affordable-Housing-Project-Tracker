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
$publicationStatus = cms_editor_publication_status((string)($page['status'] ?? 'draft'));
$missingImageCount = trim((string)($page['hero_image'] ?? '')) === '' ? 1 : 0;
$emptyFieldCount = 0;

foreach ($editableSections as $editorSection) {
    $editorContent = is_array($editorSection['content'] ?? null) ? $editorSection['content'] : [];
    foreach ($editorContent as $contentKey => $contentValue) {
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

<div class="sa-cms-editor-layout">
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
<?php else: ?>
    <section class="sa-cms-section-list" aria-label="Editable sections">
<?php foreach ($editableSections as $section): ?>
      <?php $content = is_array($section['content'] ?? null) ? $section['content'] : []; ?>
      <?php $isDocument = ($section['editor_mode'] ?? '') === 'document' || ($section['section_type'] ?? '') === 'document'; ?>
      <article class="card sa-cms-section-card" data-cms-section data-section-id="<?= (int)$section['id'] ?>" data-section-key="<?= Security::e($section['section_key']) ?>">
        <header class="sa-cms-section-card__head">
          <div>
            <h3><?= Security::e($section['label']) ?></h3>
            <p>Last updated <?= Security::e(time_ago($section['updated_at'] ?? null)) ?></p>
          </div>
          <div class="sa-cms-section-tools">
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
        <div><dt>Hero image</dt><dd><?= trim((string)($page['hero_image'] ?? '')) !== '' ? 'Selected' : 'Missing' ?></dd></div>
      </dl>
    </section>

    <section class="card sa-cms-inspector sa-cms-inspector--quality">
      <h3>Content Quality</h3>
      <ul class="sa-cms-quality-list">
        <li class="<?= $seoConfigured ? 'is-ok' : 'is-warning' ?>"><i class="fa-solid <?= $seoConfigured ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>" aria-hidden="true"></i><span>SEO metadata</span><strong><?= $seoConfigured ? 'Ready' : 'Needs review' ?></strong></li>
        <li class="<?= $missingImageCount === 0 ? 'is-ok' : 'is-warning' ?>"><i class="fa-solid <?= $missingImageCount === 0 ? 'fa-circle-check' : 'fa-image' ?>" aria-hidden="true"></i><span>Images</span><strong><?= Security::e($missingImageCount === 0 ? 'Ready' : format_number($missingImageCount) . ' missing') ?></strong></li>
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
