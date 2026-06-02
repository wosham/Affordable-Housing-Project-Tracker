<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';

Guard::role('superadmin');

$pageTitle = 'CMS Pages';
$pageDescription = 'Manage editable public pages, templates, SEO and publishing state.';
$adminRole = 'superadmin';
$contentClass = 'sa-cms-page';
$componentCss = ['cms-editor'];
$pageScripts = ['cms-editor'];
$csrfForm = 'cms_editor';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'CMS Pages'],
];

cms_ensure_page_registry();

$pages = CmsPage::allWithSectionCounts(); 
$settings = CmsSetting::grouped();
$cmsStats = cms_dashboard_stats($pages, $settings);
$groups = [
    'main' => ['label' => 'Main Pages', 'description' => 'Primary public information pages controlled by structured editors.', 'templates' => ['landing', 'content', 'contact']],
    'programme' => ['label' => 'Programme Pages', 'description' => 'Live programme views backed by projects, constituencies, reports and templates.', 'templates' => ['listing', 'template']],
    'content' => ['label' => 'Content Libraries', 'description' => 'Media-driven public libraries and gallery surfaces.', 'templates' => ['media']],
    'legal' => ['label' => 'Legal Pages', 'description' => 'Single-document pages managed with the rich text editor.', 'templates' => ['legal']],
    'system' => ['label' => 'System Pages', 'description' => 'Operational fallback pages for errors, maintenance and navigation.', 'templates' => ['system']],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-cms-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> Structured CMS</span>
    <h2>Public Website Control Centre</h2>
    <p>Control page metadata, hero copy, legal text, calls-to-action and shared website settings from one protected workspace.</p>
  </div>
  <div class="sa-cms-hero__stats">
    <span><strong><?= Security::e(format_number($cmsStats['published_pages'])) ?></strong><small>Published pages</small></span>
    <span><strong><?= Security::e(format_number($cmsStats['needs_attention'])) ?></strong><small>Need attention</small></span>
    <span><strong><?= Security::e(format_number($cmsStats['editable_sections'])) ?></strong><small>Editable sections</small></span>
    <span><strong><?= Security::e(format_number($cmsStats['global_settings'])) ?></strong><small>Global settings</small></span>
  </div>
</section>

<section class="card sa-cms-health">
  <div class="section-heading">
    <div>
      <h3>CMS Health Briefing</h3>
      <p>System-generated checks for page readiness, SEO coverage and content governance.</p>
    </div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms-page-editor.php?slug=home')) ?>"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i> Edit Homepage</a>
  </div>
  <div class="sa-cms-health-grid">
    <?php cms_health_tile('SEO gaps', $cmsStats['seo_gaps'], 'Pages missing SEO titles or descriptions', 'fa-magnifying-glass-chart', $cmsStats['seo_gaps'] > 0 ? 'warning' : 'success'); ?>
    <?php cms_health_tile('Draft pages', $cmsStats['draft_pages'], 'Pages still awaiting publication', 'fa-file-pen', $cmsStats['draft_pages'] > 0 ? 'info' : 'success'); ?>
    <?php cms_health_tile('Routes tracked', $cmsStats['route_ready'], 'Pages with configured route paths', 'fa-route', 'success'); ?>
    <?php cms_health_tile('Image gaps', $cmsStats['missing_hero_images'], 'Pages without a hero image path', 'fa-image', $cmsStats['missing_hero_images'] > 0 ? 'warning' : 'success'); ?>
  </div>
</section>

<section class="card sa-cms-board">
  <div class="section-heading">
    <div>
      <h3>Page Registry</h3>
      <p>Find, inspect and edit every controlled page without breaking its designed layout.</p>
    </div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('index.php')) ?>" target="_blank" rel="noopener noreferrer">
      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open Site
    </a>
  </div>

  <div class="sa-cms-registry-tools" data-cms-registry-tools>
    <label class="form-field">
      <span class="form-label">Search pages</span>
      <input class="form-input" type="search" data-cms-page-search placeholder="Search title, route or template">
    </label>
    <label class="form-field">
      <span class="form-label">Template</span>
      <select class="form-select" data-cms-page-template>
        <option value="">All templates</option>
        <?php foreach (['landing', 'content', 'contact', 'listing', 'template', 'media', 'legal', 'system'] as $template): ?>
          <option value="<?= Security::e($template) ?>"><?= Security::e(status_label($template)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="form-field">
      <span class="form-label">Status</span>
      <select class="form-select" data-cms-page-status>
        <option value="">All statuses</option>
        <?php foreach (['published', 'draft'] as $status): ?>
          <option value="<?= Security::e($status) ?>"><?= Security::e(status_label($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="sa-cms-filter-check">
      <input type="checkbox" data-cms-page-attention>
      <span>Needs attention only</span>
    </label>
  </div>

<?php foreach ($groups as $group): ?>
  <?php $groupPages = array_values(array_filter($pages, static fn ($page): bool => in_array((string)($page['template'] ?? ''), $group['templates'], true))); ?>
  <?php if (!$groupPages) { continue; } ?>
  <div class="sa-cms-group">
    <div class="sa-cms-group-heading">
      <div>
        <h4><?= Security::e($group['label']) ?></h4>
        <p><?= Security::e($group['description']) ?></p>
      </div>
      <span><?= Security::e(format_number(count($groupPages))) ?> pages</span>
    </div>
    <div class="sa-cms-page-grid">
<?php foreach ($groupPages as $cmsPage): ?>
      <?php
      $health = cms_page_health($cmsPage);
      $publicationStatus = cms_publication_status((string)($cmsPage['status'] ?? 'draft'));
      ?>
      <article class="sa-cms-page-card" data-cms-page-card data-search="<?= Security::e(strtolower(cms_page_label((string)$cmsPage['slug']) . ' ' . ($cmsPage['route_path'] ?? '') . ' ' . ($cmsPage['template'] ?? ''))) ?>" data-template="<?= Security::e((string)($cmsPage['template'] ?? '')) ?>" data-status="<?= Security::e($publicationStatus) ?>" data-attention="<?= $health['needs_attention'] ? '1' : '0' ?>">
        <div class="sa-cms-page-card__top">
          <span class="sa-cms-page-icon"><i class="fa-solid <?= Security::e(cms_page_icon((string)$cmsPage['template'])) ?>" aria-hidden="true"></i></span>
          <div>
            <strong><?= Security::e(cms_page_label((string)$cmsPage['slug'])) ?></strong>
            <small><?= Security::e($cmsPage['route_path'] ?: $cmsPage['slug']) ?></small>
          </div>
          <div class="sa-cms-page-card__badges">
            <span class="badge <?= Security::e(status_badge_class($publicationStatus)) ?>"><?= Security::e(status_label($publicationStatus)) ?></span>
            <span class="badge badge--info"><?= Security::e(status_label($cmsPage['template'] ?? 'page')) ?></span>
          </div>
        </div>
        <div class="sa-cms-page-progress">
          <div>
            <span>Editable sections</span>
            <strong><?= Security::e(format_number($cmsPage['section_count'] ?? 0)) ?></strong>
          </div>
          <meter min="0" max="<?= max(1, (int)($cmsStats['max_sections'] ?? 1)) ?>" value="<?= (int)($cmsPage['section_count'] ?? 0) ?>"></meter>
        </div>
        <dl class="sa-cms-page-meta">
          <div>
            <dt><i class="fa-regular fa-clock" aria-hidden="true"></i> Updated</dt>
            <dd><?= Security::e(time_ago($cmsPage['updated_at'] ?? null)) ?></dd>
          </div>
          <div>
            <dt><i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i> SEO</dt>
            <dd class="<?= $health['seo_gap'] ? 'is-warning' : 'is-ready' ?>"><?= Security::e($health['seo_label']) ?></dd>
          </div>
          <div>
            <dt><i class="fa-regular fa-image" aria-hidden="true"></i> Hero</dt>
            <dd class="<?= $health['missing_hero'] ? 'is-warning' : 'is-ready' ?>"><?= Security::e($health['hero_label']) ?></dd>
          </div>
        </dl>
        <?php if ($health['messages']): ?>
          <ul class="sa-cms-page-alerts">
          <?php foreach ($health['messages'] as $message): ?>
            <li><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span><?= Security::e($message) ?></span></li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="sa-cms-page-card__actions">
          <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/cms-page-editor.php?slug=' . urlencode((string)$cmsPage['slug']))) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit</a>
          <?php if (($cmsPage['route_path'] ?? '') !== ''): ?>
          <a class="btn btn--outline" href="<?= Security::e(Url::to((string)$cmsPage['route_path'])) ?>" target="_blank" rel="noopener noreferrer">Preview</a>
          <?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
  <div class="sa-cms-empty-filter" data-cms-empty-filter hidden>
    <i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i>
    <strong>No pages match this view</strong>
    <span>Adjust the search or filters to show more CMS pages.</span>
  </div>
</section>

<section class="card sa-cms-settings" id="settings">
  <div class="section-heading">
    <div>
      <h3>Global Settings</h3>
      <p>Shared contact, ticker, social, asset and statistic values used across the website.</p>
    </div>
  </div>
  <div class="sa-cms-settings-tabs" data-cms-settings-tabs>
<?php $firstSettingGroup = true; ?>
<?php foreach ($settings as $groupName => $items): ?>
    <button class="<?= $firstSettingGroup ? 'is-active' : '' ?>" type="button" data-cms-settings-tab="<?= Security::e((string)$groupName) ?>"><?= Security::e(status_label((string)$groupName)) ?><span><?= Security::e(format_number(count($items))) ?></span></button>
    <?php $firstSettingGroup = false; ?>
<?php endforeach; ?>
  </div>
  <div class="sa-cms-settings-grid">
<?php $firstSettingPanel = true; ?>
<?php foreach ($settings as $groupName => $items): ?>
    <article class="sa-cms-setting-card <?= $firstSettingPanel ? 'is-active' : '' ?>" data-cms-settings-panel="<?= Security::e((string)$groupName) ?>">
      <div class="sa-cms-setting-card__head">
        <h4><?= Security::e(status_label((string)$groupName)) ?></h4>
        <span><?= Security::e(format_number(count($items))) ?> settings</span>
      </div>
      <ul>
<?php foreach ($items as $item): ?>
        <li>
          <span class="sa-cms-setting-label"><?= Security::e($item['label']) ?></span>
          <form data-cms-setting-form>
            <input type="hidden" name="key" value="<?= Security::e($item['key']) ?>">
            <input type="hidden" name="type" value="<?= Security::e($item['type']) ?>">
            <input type="hidden" name="label" value="<?= Security::e($item['label']) ?>">
            <input type="hidden" name="group" value="<?= Security::e($item['group']) ?>">
            <?= cms_setting_control($item) ?>
            <button class="btn btn--sm btn--outline" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save</button>
          </form>
        </li>
<?php endforeach; ?>
      </ul>
    </article>
    <?php $firstSettingPanel = false; ?>
<?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function cms_ensure_page_registry(): void
{
    foreach (cms_page_blueprints() as $slug => $page) {
        $existingPage = CmsPage::findBySlug($slug);
        if ($existingPage) {
            $pageId = (int)$existingPage['id'];
        } else {
            $pageId = CmsPage::upsert([
                'slug' => $slug,
                'template' => $page['template'],
                'route_path' => $page['route_path'],
                'status' => $page['status'] ?? 'published',
                'seo_title' => $page['seo_title'] ?? cms_page_label($slug) . ' | AHPTC',
                'seo_description' => $page['seo_description'] ?? '',
                'seo_keywords' => $page['seo_keywords'] ?? '',
                'canonical_url' => $page['canonical_url'] ?? '',
                'hero_image' => $page['hero_image'] ?? '',
            ]);
        }

        foreach ($page['sections'] as $index => $section) {
            if (CmsSection::findForPage($pageId, $section['section_key'])) {
                continue;
            }

            $section['sort_order'] = $section['sort_order'] ?? (($index + 1) * 10);
            CmsSection::upsert($pageId, $section + ['content' => cms_default_section_content($section)]);
        }
    }
}

function cms_page_blueprints(): array
{
    return [
        'home' => ['template' => 'landing', 'route_path' => 'index.php', 'hero_image' => 'uploads/heroes/hero-main.jpg', 'sections' => cms_sections(['hero', 'ticker', 'stats', 'about', 'projects', 'map', 'news', 'gallery', 'cta'])],
        'about' => ['template' => 'content', 'route_path' => 'about.php', 'hero_image' => 'uploads/gallery/maili-tatu-2.jpg', 'sections' => cms_sections(['hero', 'overview', 'pillars', 'timeline', 'legal_framework', 'partners', 'faq_teaser', 'leadership_contact', 'apply_cta'])],
        'projects' => [
            'template' => 'listing',
            'route_path' => 'projects.php',
            'hero_image' => 'uploads/gallery/maili-tatu-2.jpg',
            'seo_title' => 'All Projects | Trans-Nzoia County Affordable Housing Tracker',
            'seo_description' => 'Browse all affordable housing projects in Trans-Nzoia County with live construction progress, contractor details and unit counts across all 5 constituencies.',
            'seo_keywords' => 'Trans-Nzoia affordable housing projects, AHP Kenya, Maili Tatu estate, Matunda estate, Saboti housing, Cherangany housing',
            'canonical_url' => 'https://housing.transnzoia.go.ke/projects.php',
            'sections' => cms_sections(['projects_hero', 'projects_filters', 'projects_listing']),
        ],
        'project-detail' => [
            'template' => 'template',
            'route_path' => 'project-detail.php',
            'seo_title' => 'Project Detail | Trans-Nzoia County Affordable Housing Tracker',
            'seo_description' => 'Detailed construction progress, contractor information, milestones and site photos for affordable housing projects in Trans-Nzoia County.',
            'seo_keywords' => 'Trans-Nzoia AHP project detail, affordable housing Kenya, construction progress',
            'canonical_url' => 'https://housing.transnzoia.go.ke/project-detail.php',
            'sections' => cms_sections(['project_detail_labels', 'project_detail_overview', 'project_detail_sidebar', 'project_detail_apply_cta', 'project_detail_not_found']),
        ],
        'constituencies' => [
            'template' => 'listing',
            'route_path' => 'constituencies.php',
            'hero_image' => 'uploads/gallery/maili-tatu-3.jpg',
            'seo_title' => 'Constituencies | Trans-Nzoia County Affordable Housing Tracker',
            'seo_description' => 'Explore affordable housing coverage across all 5 constituencies in Trans-Nzoia County - Saboti, Cherangany, Endebess, Kiminini and Kwanza.',
            'seo_keywords' => 'Trans-Nzoia constituencies, Saboti housing, Cherangany AHP, Endebess housing, Kiminini housing, Kwanza housing',
            'canonical_url' => 'https://housing.transnzoia.go.ke/constituencies.php',
            'sections' => cms_sections(['constituencies_hero', 'constituencies_map', 'constituencies_progress', 'constituencies_grid', 'constituencies_apply_cta']),
        ],
        'constituency-detail' => [
            'template' => 'template',
            'route_path' => 'constituency-detail.php',
            'seo_title' => 'Constituency Detail | Trans-Nzoia County Affordable Housing Tracker',
            'seo_description' => 'Detailed affordable housing information for Trans-Nzoia constituencies, including live projects, progress, wards and local facts.',
            'seo_keywords' => 'Trans-Nzoia constituency housing, AHP Kenya, affordable housing projects',
            'canonical_url' => 'https://housing.transnzoia.go.ke/constituency-detail.php',
            'sections' => cms_sections(['constituency_detail_labels', 'constituency_detail_facts', 'constituency_detail_related', 'constituency_detail_apply_cta']),
        ],
        'news' => [
            'template' => 'listing',
            'route_path' => 'news.php',
            'seo_title' => 'News & Updates | Trans-Nzoia County AHP Tracker',
            'seo_description' => 'Latest news, official announcements, construction progress reports, and community updates from the Trans-Nzoia County Affordable Housing Programme.',
            'seo_keywords' => 'Trans-Nzoia housing news, AHP announcements, affordable housing Kenya, county housing updates',
            'canonical_url' => 'https://housing.transnzoia.go.ke/news.php',
            'sections' => cms_sections(['news_hero', 'news_mosaic', 'news_featured', 'news_filters', 'news_listing', 'news_cta']),
        ],
        'news-article' => [
            'template' => 'template',
            'route_path' => 'news-article.php',
            'seo_title' => 'News Article | Trans-Nzoia County AHP Tracker',
            'seo_description' => 'Read official Trans-Nzoia Affordable Housing Programme news, announcements, reports and updates.',
            'seo_keywords' => 'Trans-Nzoia housing news, affordable housing, AHP reports',
            'canonical_url' => 'https://housing.transnzoia.go.ke/news-article.php',
            'sections' => cms_sections(['news_article_labels', 'news_article_sidebar', 'news_article_downloads', 'news_article_not_found']),
        ],
        'gallery' => [
            'template' => 'media',
            'route_path' => 'gallery.php',
            'hero_image' => 'uploads/gallery/maili-tatu-2.jpg',
            'seo_title' => 'Photo Gallery | Trans-Nzoia County AHP Tracker',
            'seo_description' => 'Photo and video documentation of Trans-Nzoia Affordable Housing Programme construction progress, ceremonies, community engagements and site activity.',
            'seo_keywords' => 'Trans-Nzoia affordable housing gallery, AHP Kenya photos, construction progress videos',
            'canonical_url' => 'https://housing.transnzoia.go.ke/gallery.php',
            'sections' => cms_sections(['gallery_hero', 'gallery_highlights', 'gallery_archive', 'gallery_site_progress', 'gallery_videos', 'gallery_empty_states']),
        ],
        'faq' => ['template' => 'content', 'route_path' => 'faq.php', 'sections' => cms_sections(['hero', 'popular_intro', 'faq_listing_intro', 'contact_cta'])],
        'leadership' => ['template' => 'content', 'route_path' => 'leadership.php', 'sections' => cms_sections(['leadership_hero', 'leadership_org_chart', 'leadership_national', 'leadership_spotlight', 'leadership_contractors', 'leadership_quotes', 'leadership_partners', 'leadership_contact_cta'])],
        'stakeholders' => ['template' => 'content', 'route_path' => 'stakeholders.php', 'sections' => cms_sections(['hero', 'ecosystem', 'categories', 'roles', 'timeline', 'voices', 'partners', 'engagement_cta'])],
        'contact' => [
            'template' => 'contact',
            'route_path' => 'contact.php',
            'hero_image' => 'uploads/heroes/hero-main.jpg',
            'seo_title' => 'Contact Us | Trans-Nzoia AHP Tracker',
            'seo_description' => 'Contact the Trans-Nzoia Affordable Housing Programme team by phone, email, office visit or online enquiry form.',
            'seo_keywords' => 'Trans-Nzoia affordable housing contact, AHP Kenya office, Kitale housing desk, housing enquiry Kenya',
            'canonical_url' => 'https://housing.transnzoia.go.ke/contact.php',
            'sections' => cms_sections(['contact_hero', 'contact_quick_cards', 'contact_form', 'contact_office', 'contact_departments', 'contact_faq_banner']),
        ],
        'privacy' => ['template' => 'legal', 'route_path' => 'legal/privacy.php', 'sections' => cms_legal_sections('Privacy Policy')],
        'terms' => ['template' => 'legal', 'route_path' => 'legal/terms.php', 'sections' => cms_legal_sections('Terms of Use')],
        'disclaimer' => ['template' => 'legal', 'route_path' => 'legal/disclaimer.php', 'sections' => cms_legal_sections('Disclaimer')],
        'not-found' => ['template' => 'system', 'route_path' => '404.php', 'sections' => cms_sections(['hero', 'suggested_links'])],
        'server-error' => ['template' => 'system', 'route_path' => '500.php', 'sections' => cms_sections(['hero', 'support_message'])],
        'maintenance' => ['template' => 'system', 'route_path' => 'maintenance.php', 'sections' => cms_sections(['hero', 'maintenance_notice'])],
        'offline' => ['template' => 'system', 'route_path' => 'offline.php', 'sections' => cms_sections(['hero', 'offline_notice'])],
        'sitemap' => ['template' => 'system', 'route_path' => 'sitemap.php', 'sections' => cms_sections(['hero', 'link_groups'])],
    ];
}

function cms_sections(array $keys): array
{
    return array_map(static fn ($key): array => [
        'section_key' => $key,
        'label' => cms_section_label($key),
        'section_type' => str_contains($key, 'hero') ? 'hero' : (str_contains($key, 'cta') ? 'cta' : 'rich_text'),
        'editor_mode' => 'structured',
        'is_visible' => 1,
    ], $keys);
}

function cms_legal_sections(string $title): array
{
    $slug = strtolower(str_replace([' ', 'policy', 'of', 'use'], ['-', '', '', ''], $title));
    $slug = match ($title) {
        'Privacy Policy' => 'privacy',
        'Terms of Use' => 'terms',
        default => 'disclaimer',
    };
    $defaults = cms_legal_default_content($slug, $title);

    return [[
        'section_key' => 'legal_document',
        'label' => $title . ' Document',
        'section_type' => 'document',
        'editor_mode' => 'document',
        'is_visible' => 1,
        'content' => $defaults,
    ]];
}

function cms_legal_default_content(string $slug, string $title): array
{
    $defaults = [
        'privacy' => [
            'subtitle' => 'How Trans-Nzoia County Government collects, uses, and protects your personal data in connection with the Affordable Housing Programme.',
            'icon' => 'fa-shield-halved',
        ],
        'terms' => [
            'subtitle' => 'Rules and conditions for using the Trans-Nzoia County Affordable Housing Programme Tracker website and digital services.',
            'icon' => 'fa-file-contract',
        ],
        'disclaimer' => [
            'subtitle' => 'Important limitations and qualifications on the data, information, and content published on the Trans-Nzoia County Affordable Housing Programme Tracker website.',
            'icon' => 'fa-triangle-exclamation',
        ],
    ];

    return [
        'title' => $title,
        'subtitle' => $defaults[$slug]['subtitle'] ?? '',
        'icon' => $defaults[$slug]['icon'] ?? 'fa-file-shield',
        'last_updated' => '2026-01-01',
        'body' => '<h2>' . Security::e($title) . '</h2><p>Use the legal document editor to maintain this page content.</p>',
    ];
}

function cms_default_section_content(array $section): array
{
    return [
        'eyebrow' => '',
        'title' => $section['label'],
        'subtitle' => '',
        'body' => '',
        'image' => '',
        'primary_label' => '',
        'primary_url' => '',
        'secondary_label' => '',
        'secondary_url' => '',
    ];
}

function cms_page_label(string $slug): string
{
    return match ($slug) {
        'home' => 'Home',
        'not-found' => '404 Page',
        'server-error' => '500 Page',
        default => ucwords(str_replace('-', ' ', $slug)),
    };
}

function cms_section_label(string $key): string
{
    return ucwords(str_replace('_', ' ', $key));
}

function cms_page_icon(string $template): string
{
    return match ($template) {
        'landing' => 'fa-house',
        'listing' => 'fa-list',
        'template' => 'fa-layer-group',
        'media' => 'fa-images',
        'contact' => 'fa-envelope',
        'legal' => 'fa-scale-balanced',
        'system' => 'fa-triangle-exclamation',
        default => 'fa-file-lines',
    };
}

function cms_dashboard_stats(array $pages, array $settings): array
{
    $stats = [
        'published_pages' => 0,
        'draft_pages' => 0,
        'seo_gaps' => 0,
        'missing_hero_images' => 0,
        'editable_sections' => 0,
        'route_ready' => 0,
        'max_sections' => 1,
        'global_settings' => count($settings, COUNT_RECURSIVE) - count($settings),
        'needs_attention' => 0,
    ];

    foreach ($pages as $page) {
        if (($page['status'] ?? '') === 'published') {
            $stats['published_pages']++;
        } else {
            $stats['draft_pages']++;
        }

        $sectionCount = (int)($page['section_count'] ?? 0);
        $stats['editable_sections'] += $sectionCount;
        $stats['max_sections'] = max($stats['max_sections'], $sectionCount);
        if (trim((string)($page['route_path'] ?? '')) !== '') {
            $stats['route_ready']++;
        }

        $health = cms_page_health($page);
        if ($health['seo_gap']) {
            $stats['seo_gaps']++;
        }
        if ($health['missing_hero']) {
            $stats['missing_hero_images']++;
        }
        if ($health['needs_attention']) {
            $stats['needs_attention']++;
        }
    }

    return $stats;
}

function cms_page_health(array $page): array
{
    $seoGap = trim((string)($page['seo_title'] ?? '')) === '' || trim((string)($page['seo_description'] ?? '')) === '';
    $missingHero = in_array((string)($page['template'] ?? ''), ['landing', 'content', 'contact', 'listing', 'media'], true)
        && trim((string)($page['hero_image'] ?? '')) === '';
    $messages = [];

    if ($seoGap) {
        $messages[] = 'SEO metadata incomplete';
    }
    if ($missingHero) {
        $messages[] = 'Hero image missing';
    }
    if (cms_publication_status((string)($page['status'] ?? 'draft')) !== 'published') {
        $messages[] = 'Draft page';
    }

    return [
        'seo_gap' => $seoGap,
        'missing_hero' => $missingHero,
        'needs_attention' => $seoGap || $missingHero || cms_publication_status((string)($page['status'] ?? 'draft')) !== 'published',
        'seo_label' => $seoGap ? 'Needs work' : 'Configured',
        'hero_label' => $missingHero ? 'Missing' : 'Ready',
        'messages' => $messages,
    ];
}

function cms_publication_status(string $status): string
{
    return strtolower(trim($status)) === 'published' ? 'published' : 'draft';
}

function cms_health_tile(string $label, int $value, string $description, string $icon, string $tone): void
{
    ?>
    <article class="sa-cms-health-tile sa-cms-health-tile--<?= Security::e($tone) ?>">
      <span><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
      <div>
        <strong><?= Security::e(format_number($value)) ?></strong>
        <h4><?= Security::e($label) ?></h4>
        <p><?= Security::e($description) ?></p>
      </div>
    </article>
    <?php
}

function cms_setting_control(array $item): string
{
    $key = (string)($item['key'] ?? '');
    $type = (string)($item['type'] ?? 'text');
    $value = Security::e($item['value'] ?? '');
    $lowerKey = strtolower($key);

    if ($type === 'boolean' || str_contains($lowerKey, 'mode')) {
        $checked = in_array(strtolower((string)($item['value'] ?? '')), ['1', 'true', 'yes', 'on'], true) ? ' checked' : '';
        return '<label class="sa-cms-setting-toggle"><input type="checkbox" name="value" value="1"' . $checked . '><span></span><strong>Enabled</strong></label>';
    }

    if ($type === 'number' || str_contains($lowerKey, 'hour') || str_contains($lowerKey, 'radius') || str_contains($lowerKey, 'total')) {
        return '<input class="form-input" type="number" name="value" value="' . $value . '">';
    }

    if (str_contains($lowerKey, 'time') || str_contains($lowerKey, 'window_')) {
        return '<input class="form-input" type="time" name="value" value="' . $value . '">';
    }

    if ($type === 'json' || str_contains($lowerKey, 'metadata') || str_contains($lowerKey, 'items') || str_contains($lowerKey, 'stats')) {
        return '<textarea class="form-textarea sa-cms-json-input" name="value" rows="4">' . $value . '</textarea>';
    }

    if (str_contains($lowerKey, 'url')) {
        return '<input class="form-input" type="url" name="value" value="' . $value . '">';
    }

    if (str_contains($lowerKey, 'logo') || str_contains($lowerKey, 'image') || str_contains($lowerKey, 'favicon')) {
        return '<input class="form-input" name="value" value="' . $value . '" placeholder="uploads/...">';
    }

    return '<input class="form-input" name="value" value="' . $value . '">';
}
