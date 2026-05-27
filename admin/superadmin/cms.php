<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$pageTitle = 'CMS Pages';
$pageDescription = 'Manage editable public pages, templates, SEO and section visibility.';
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
$groups = [
    'main' => ['label' => 'Main Pages', 'templates' => ['landing', 'content', 'contact']],
    'programme' => ['label' => 'Programme Pages', 'templates' => ['listing', 'template']],
    'content' => ['label' => 'Content Libraries', 'templates' => ['media']],
    'legal' => ['label' => 'Legal Pages', 'templates' => ['legal']],
    'system' => ['label' => 'System Pages', 'templates' => ['system']],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-cms-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> Structured CMS</span>
    <h2>Public Website Control Centre</h2>
    <p>Edit page metadata, hero copy, legal text, calls-to-action and section visibility without breaking the designed layouts.</p>
  </div>
  <div class="sa-cms-hero__stats">
    <span><strong><?= Security::e(format_number(count($pages))) ?></strong><small>Registered pages</small></span>
    <span><strong><?= Security::e(format_number(array_sum(array_map(static fn ($p) => (int)$p['section_count'], $pages)))) ?></strong><small>Editable sections</small></span>
    <span><strong><?= Security::e(format_number(count($settings, COUNT_RECURSIVE) - count($settings))) ?></strong><small>Global settings</small></span>
  </div>
</section>

<section class="card sa-cms-board">
  <div class="section-heading">
    <div>
      <h3>Page Registry</h3>
      <p>Each page keeps its layout, while editors control the content blocks and publishing state.</p>
    </div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('index.php')) ?>" target="_blank" rel="noopener noreferrer">
      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open Site
    </a>
  </div>

<?php foreach ($groups as $group): ?>
  <?php $groupPages = array_values(array_filter($pages, static fn ($page): bool => in_array((string)($page['template'] ?? ''), $group['templates'], true))); ?>
  <?php if (!$groupPages) { continue; } ?>
  <div class="sa-cms-group">
    <h4><?= Security::e($group['label']) ?></h4>
    <div class="sa-cms-page-grid">
<?php foreach ($groupPages as $cmsPage): ?>
      <article class="sa-cms-page-card">
        <div class="sa-cms-page-card__top">
          <span class="sa-cms-page-icon"><i class="fa-solid <?= Security::e(cms_page_icon((string)$cmsPage['template'])) ?>" aria-hidden="true"></i></span>
          <div>
            <strong><?= Security::e(cms_page_label((string)$cmsPage['slug'])) ?></strong>
            <small><?= Security::e($cmsPage['route_path'] ?: $cmsPage['slug']) ?></small>
          </div>
          <span class="badge <?= Security::e(status_badge_class($cmsPage['status'])) ?>"><?= Security::e(status_label($cmsPage['status'])) ?></span>
        </div>
        <dl class="sa-cms-page-meta">
          <div><dt>Sections</dt><dd><?= Security::e(format_number($cmsPage['visible_section_count'] ?? 0)) ?> / <?= Security::e(format_number($cmsPage['section_count'] ?? 0)) ?></dd></div>
          <div><dt>Updated</dt><dd><?= Security::e(time_ago($cmsPage['updated_at'] ?? null)) ?></dd></div>
          <div><dt>SEO</dt><dd><?= Security::e(($cmsPage['seo_title'] ?? '') !== '' ? 'Configured' : 'Needs title') ?></dd></div>
        </dl>
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
</section>

<section class="card sa-cms-settings">
  <div class="section-heading">
    <div>
      <h3>Global Settings</h3>
      <p>Shared contact, ticker, social, asset and statistic values used across the website.</p>
    </div>
  </div>
  <div class="sa-cms-settings-grid">
<?php foreach ($settings as $groupName => $items): ?>
    <article class="sa-cms-setting-card">
      <h4><?= Security::e(status_label((string)$groupName)) ?></h4>
      <ul>
<?php foreach (array_slice($items, 0, 6) as $item): ?>
        <li>
          <span><?= Security::e($item['label']) ?></span>
          <form data-cms-setting-form>
            <input type="hidden" name="key" value="<?= Security::e($item['key']) ?>">
            <input type="hidden" name="type" value="<?= Security::e($item['type']) ?>">
            <input type="hidden" name="label" value="<?= Security::e($item['label']) ?>">
            <input type="hidden" name="group" value="<?= Security::e($item['group']) ?>">
            <input class="form-input" name="value" value="<?= Security::e($item['value']) ?>">
            <button class="btn btn--sm btn--outline" type="submit">Save</button>
          </form>
        </li>
<?php endforeach; ?>
      </ul>
    </article>
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
        'projects' => ['template' => 'listing', 'route_path' => 'projects.php', 'hero_image' => 'uploads/gallery/maili-tatu-2.jpg', 'sections' => cms_sections(['hero', 'stats_strip', 'listing_intro'])],
        'project-detail' => ['template' => 'template', 'route_path' => 'project-detail.php', 'sections' => cms_sections(['template_labels', 'sidebar_cta', 'related_links'])],
        'constituencies' => ['template' => 'listing', 'route_path' => 'constituencies.php', 'sections' => cms_sections(['hero', 'map_intro', 'county_progress', 'grid_intro'])],
        'constituency-detail' => ['template' => 'template', 'route_path' => 'constituency-detail.php', 'sections' => cms_sections(['template_labels', 'apply_cta', 'related_links'])],
        'news' => ['template' => 'listing', 'route_path' => 'news.php', 'sections' => cms_sections(['hero', 'featured_intro', 'listing_intro', 'newsletter_cta'])],
        'news-article' => ['template' => 'template', 'route_path' => 'news-article.php', 'sections' => cms_sections(['template_labels', 'sidebar_facts', 'related_intro'])],
        'gallery' => ['template' => 'media', 'route_path' => 'gallery.php', 'sections' => cms_sections(['hero', 'highlights_intro', 'gallery_grid_intro', 'site_progress_intro', 'video_intro'])],
        'faq' => ['template' => 'content', 'route_path' => 'faq.php', 'sections' => cms_sections(['hero', 'popular_intro', 'faq_listing_intro', 'contact_cta'])],
        'leadership' => ['template' => 'content', 'route_path' => 'leadership.php', 'sections' => cms_sections(['hero', 'org_chain', 'national_intro', 'director_spotlight', 'contractors_intro', 'quotes_intro', 'partners_intro', 'contact_cta'])],
        'stakeholders' => ['template' => 'content', 'route_path' => 'stakeholders.php', 'sections' => cms_sections(['hero', 'ecosystem', 'categories', 'roles', 'timeline', 'voices', 'partners', 'engagement_cta'])],
        'contact' => ['template' => 'contact', 'route_path' => 'contact.php', 'sections' => cms_sections(['hero', 'quick_contact', 'form_intro', 'office_info', 'departments_intro', 'faq_banner'])],
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
    return [[
        'section_key' => 'legal_document',
        'label' => $title . ' Document',
        'section_type' => 'document',
        'editor_mode' => 'document',
        'is_visible' => 1,
    ]];
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
