<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'constituencies';
$cmsPage = CmsLoader::publicPage('constituencies');

if (!function_exists('constituencies_content')) {
function constituencies_content(array $page, string $key, array $defaults): array
{
    return CmsLoader::content($page, $key, $defaults);
}
}

if (!function_exists('constituencies_number')) {
function constituencies_number(int|float $value): string
{
    return number_format((float)$value, 0);
}
}

if (!function_exists('constituencies_short_number')) {
function constituencies_short_number(int|float $value): string
{
    $value = (float)$value;
    if ($value >= 1000000) {
        return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
    }
    if ($value >= 1000) {
        return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
    }
    return number_format($value, 0);
}
}

if (!function_exists('constituencies_plural')) {
function constituencies_plural(int $count, string $singular, string $plural): string
{
    return $count === 1 ? $singular : $plural;
}
}

if (!function_exists('constituencies_replace_stats')) {
function constituencies_replace_stats(string $text, array $stats): string
{
    return strtr($text, [
        '{constituencies}' => (string)$stats['constituencies'],
        '{projects}' => (string)$stats['projects'],
        '{units}' => constituencies_number((int)$stats['units']),
        '{residents}' => constituencies_short_number((int)$stats['residents']),
    ]);
}
}

$hero = constituencies_content($cmsPage, 'constituencies_hero', [
    'background_image' => 'uploads/gallery/maili-tatu-3.jpg',
    'background_alt' => 'Affordable housing construction site in Trans-Nzoia County',
    'eyebrow' => 'Coverage Map',
    'title_prefix' => 'All',
    'title_highlight' => '5 Constituencies',
    'subtitle' => 'Explore how the county project tracker covers every constituency in Trans-Nzoia County - from Endebess on the Uganda border to Kwanza in the south.',
    'constituencies_label' => 'Constituencies',
    'projects_label' => 'Projects',
    'units_label' => 'Tracked Outputs',
    'residents_label' => 'Residents Served',
]);
$mapCopy = constituencies_content($cmsPage, 'constituencies_map', [
    'title' => 'Trans-Nzoia County',
    'subtitle' => 'Click a constituency to explore its project sites',
    'active_label' => 'Active construction',
    'planning_label' => 'Planning stage',
    'selected_label' => 'Selected',
    'empty_text' => 'Constituency data will appear after projects and wards are added to the registry.',
]);
$progressCopy = constituencies_content($cmsPage, 'constituencies_progress', [
    'title' => "County-Wide\nProgramme\nProgress",
    'subtitle' => 'Across all {constituencies} constituencies, the programme is tracking {units} project outputs across housing, markets, ESP sites, and institutional facilities.',
    'button_label' => 'Browse All Projects',
    'button_url' => 'projects.php',
]);
$gridCopy = constituencies_content($cmsPage, 'constituencies_grid', [
    'eyebrow' => 'Browse by Constituency',
    'title_prefix' => 'All',
    'title_highlight' => '5 Constituencies',
    'sort_label' => 'Sort:',
    'default_label' => 'Default',
    'progress_label' => 'Highest Progress',
    'units_label' => 'Most Outputs',
    'alpha_label' => 'A - Z',
]);
$ctaCopy = constituencies_content($cmsPage, 'constituencies_apply_cta', [
    'title' => 'Ready to Apply for Affordable Housing?',
    'subtitle' => 'Register on the national Boma Yangu portal to join the Trans-Nzoia County AHP allocation list.',
    'primary_label' => 'Apply on Boma Yangu',
    'primary_url' => 'https://bomayangu.go.ke',
    'secondary_label' => 'View All Projects',
    'secondary_url' => 'projects.php',
]);

$constituencies = Constituency::publicList();
$stats = Constituency::publicStats();
$constituenciesBySlug = [];
foreach ($constituencies as $item) {
    $constituenciesBySlug[$item['id']] = $item;
}
$clientLabels = [
    'active' => 'Active',
    'planning' => 'Planning',
    'residents' => 'residents',
    'projects' => CmsLoader::text($hero, 'projects_label', 'Projects'),
    'units' => 'Outputs',
    'wards' => 'Wards',
    'completion' => 'Avg. Completion',
    'explore' => 'Explore',
    'previous' => 'Prev',
    'next' => 'Next',
    'showing' => 'Showing',
    'of' => 'of',
];
$pagePayload = public_page_payload('constituencies', [
    'constituencies' => $constituencies,
    'stats' => $stats,
    'labels' => $clientLabels,
]);

$pageTitle = $cmsPage['seo_title'] ?? 'Constituencies | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Explore project coverage across all 5 constituencies in Trans-Nzoia County - Saboti, Cherangany, Endebess, Kiminini and Kwanza.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia constituencies, Saboti housing, Cherangany AHP, Endebess housing, Kiminini housing, Kwanza housing';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = trim((string)($cmsPage['canonical_url'] ?? '')) ?: Url::canonical('constituencies.php');
$heroImage = CmsLoader::text($hero, 'background_image', (string)($cmsPage['hero_image'] ?? 'uploads/gallery/maili-tatu-3.jpg'));
$pageStyles = ['assets/css/global.css', 'assets/css/pages/constituencies.css'];
$pageScripts = ['assets/js/global.js', 'assets/js/pages/constituencies.js'];
$headMeta = [
    '<meta property="og:title" content="' . Security::e($pageTitle) . '">',
    '<meta property="og:description" content="' . Security::e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . Security::e($canonicalUrl) . '">',
    '<meta property="og:image" content="' . Security::e($heroImage) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . Security::e($pageTitle) . '">',
    '<meta name="twitter:description" content="' . Security::e($pageDescription) . '">',
    '<meta name="twitter:image" content="' . Security::e($heroImage) . '">',
];
include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?><main id="main-content">

    <section class="page-hero page-hero--constituencies" aria-label="Constituencies overview">
      <div class="page-hero-bg" aria-hidden="true">
        <img <?= public_image_attrs($heroImage, '', ['loading' => 'eager', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
        <div class="page-hero-overlay"></div>
      </div>
      <div class="container">
        <div class="page-hero-body">
          <div class="page-hero-eyebrow">
            <span class="page-hero-tag"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($hero, 'eyebrow', 'Coverage Map')) ?></span>
          </div>
          <h1 class="page-hero-title"><?= Security::e(CmsLoader::text($hero, 'title_prefix', 'All')) ?> <span class="text-lime"><?= Security::e(CmsLoader::text($hero, 'title_highlight', $stats['constituencies'] . ' Constituencies')) ?></span></h1>
          <p class="page-hero-sub"><?= Security::e(CmsLoader::text($hero, 'subtitle', 'Explore how the Affordable Housing Programme reaches every corner of Trans-Nzoia County.')) ?></p>
        </div>
        <div class="projects-hero-stats" role="region" aria-label="County-wide statistics">
          <div class="phs-item"><span class="phs-val"><?= Security::e((string)$stats['constituencies']) ?></span><span class="phs-lbl"><?= Security::e(CmsLoader::text($hero, 'constituencies_label', 'Constituencies')) ?></span></div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item"><span class="phs-val"><?= Security::e((string)$stats['projects']) ?></span><span class="phs-lbl"><?= Security::e(CmsLoader::text($hero, 'projects_label', 'Projects')) ?></span></div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item"><span class="phs-val"><?= Security::e(constituencies_number($stats['units'])) ?></span><span class="phs-lbl"><?= Security::e(CmsLoader::text($hero, 'units_label', 'Units Planned')) ?></span></div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item"><span class="phs-val"><?= Security::e(constituencies_short_number($stats['residents'])) ?></span><span class="phs-lbl"><?= Security::e(CmsLoader::text($hero, 'residents_label', 'Residents Served')) ?></span></div>
        </div>
      </div>
    </section>

    <section class="constituencies-split" aria-label="Constituency map and listings">
      <div class="constituencies-split-inner">
        <div class="con-map-panel" aria-label="Interactive constituency map">
          <div class="con-map-sticky">
            <div class="con-map-header">
              <h2 class="con-map-title"><?= Security::e(CmsLoader::text($mapCopy, 'title', 'Trans-Nzoia County')) ?></h2>
              <p class="con-map-hint"><?= Security::e(CmsLoader::text($mapCopy, 'subtitle', 'Click a constituency to explore its housing projects')) ?></p>
            </div>
            <div class="con-map-wrap">
              <svg id="countyMap" viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Trans-Nzoia County map showing constituencies">
                <defs>
                  <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="rgba(10,31,5,0.25)"/>
                  </filter>
                </defs>
<?php foreach (Constituency::mapShapes() as $slug => $shape): ?>
<?php $item = $constituenciesBySlug[$slug] ?? ['name' => ucwords($slug), 'projectCount' => 0, 'avgCompletion' => 0]; ?>
                <g class="con-path-group" data-id="<?= Security::e($slug) ?>" tabindex="0" role="button" aria-label="<?= Security::e($item['name'] . ' - ' . $item['projectCount'] . ' ' . constituencies_plural((int)$item['projectCount'], 'project', 'projects') . ', ' . $item['avgCompletion'] . '% average completion') ?>">
                  <path d="<?= Security::e($shape['path']) ?>" class="con-path"/>
                  <text class="con-path-label" x="<?= (int)$shape['label_x'] ?>" y="<?= (int)$shape['label_y'] ?>"><?= Security::e($item['name']) ?></text>
                  <text class="con-path-sub" x="<?= (int)$shape['label_x'] ?>" y="<?= (int)$shape['sub_y'] ?>"><?= Security::e($item['projectCount'] . ' ' . constituencies_plural((int)$item['projectCount'], 'project', 'projects')) ?></text>
                </g>
<?php endforeach; ?>
              </svg>
            </div>
            <div class="con-map-legend" aria-label="Map legend">
              <div class="con-legend-item"><span class="con-legend-dot con-legend-dot--active"></span><span><?= Security::e(CmsLoader::text($mapCopy, 'active_label', 'Active construction')) ?></span></div>
              <div class="con-legend-item"><span class="con-legend-dot con-legend-dot--planning"></span><span><?= Security::e(CmsLoader::text($mapCopy, 'planning_label', 'Planning stage')) ?></span></div>
              <div class="con-legend-item"><span class="con-legend-dot con-legend-dot--selected"></span><span><?= Security::e(CmsLoader::text($mapCopy, 'selected_label', 'Selected')) ?></span></div>
            </div>
          </div>
        </div>
        <div class="con-cards-panel" id="conCardsPanel" aria-label="Constituency listings">
<?php if ($constituencies): ?>
<?php foreach ($constituencies as $item): ?>
          <article class="con-card" data-id="<?= Security::e($item['id']) ?>" tabindex="0" aria-label="Explore <?= Security::e($item['name']) ?>">
            <div class="con-card-header">
              <div class="con-card-name-wrap">
                <h3 class="con-card-name"><?= Security::e($item['name']) ?></h3>
                <span class="con-card-pop"><?= Security::e($item['population']) ?> residents</span>
              </div>
              <span class="con-card-status con-card-status--<?= Security::e($item['status']) ?>"><?= Security::e($item['status'] === 'active' ? 'Active' : 'Planning') ?></span>
            </div>
            <div class="con-card-stats">
              <div class="con-card-stat"><span class="con-card-stat-val"><?= Security::e((string)$item['projectCount']) ?></span><span class="con-card-stat-lbl">Projects</span></div>
              <div class="con-card-stat"><span class="con-card-stat-val"><?= Security::e(format_number((int)$item['totalUnits'])) ?></span><span class="con-card-stat-lbl">Outputs</span></div>
              <div class="con-card-stat"><span class="con-card-stat-val"><?= Security::e((string)count($item['wards'])) ?></span><span class="con-card-stat-lbl">Wards</span></div>
            </div>
            <div class="con-card-progress-wrap">
              <div class="con-card-progress-meta">
                <span class="con-card-progress-label">Avg. Completion</span>
                <span class="con-card-progress-pct"><?= Security::e((string)$item['avgCompletion']) ?>%</span>
              </div>
              <div class="con-card-progress-bar" role="progressbar" aria-valuenow="<?= (int)$item['avgCompletion'] ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="con-card-progress-fill" data-target="<?= (int)$item['avgCompletion'] ?>" style="width:<?= (int)$item['avgCompletion'] ?>%"></div>
              </div>
            </div>
            <div class="con-card-footer">
                            <div class="con-card-wards" aria-label="Wards">
<?php foreach ($item['wards'] as $ward): ?>
                <span class="con-card-ward-chip"><?= Security::e($ward) ?></span>
<?php endforeach; ?>
              </div>
              <a href="<?= Security::e($item['link']) ?>" class="con-card-cta" aria-label="Explore <?= Security::e($item['name']) ?>">
                Explore <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </article>
<?php endforeach; ?>
<?php else: ?>
          <p class="con-map-hint"><?= Security::e(CmsLoader::text($mapCopy, 'empty_text', 'Constituency data will appear after projects and wards are added to the registry.')) ?></p>
<?php endif; ?>
        </div>
      </div>
    </section>

    <section class="county-total-section" aria-label="County-wide programme totals">
      <div class="container">
        <div class="county-total-inner">
          <div class="county-total-left">
            <h2 class="county-total-title"><?= nl2br(Security::e(CmsLoader::text($progressCopy, 'title', "County-Wide\nProgramme\nProgress"))) ?></h2>
            <p class="county-total-sub"><?= Security::e(constituencies_replace_stats(CmsLoader::text($progressCopy, 'subtitle', 'Across all {constituencies} constituencies, the programme is delivering {units} affordable housing units.'), $stats)) ?></p>
            <a href="<?= Security::e(CmsLoader::text($progressCopy, 'button_url', 'projects.php')) ?>" class="btn btn-primary">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($progressCopy, 'button_label', 'Browse All Projects')) ?>
            </a>
          </div>
          <div class="county-total-right" id="countyTotalsChart" aria-label="Constituency progress comparison">
            <div class="county-bar-row">
<?php foreach ($constituencies as $item): ?>
              <div class="county-bar-item">
                <span class="county-bar-name"><?= Security::e($item['name']) ?></span>
                <div class="county-bar-track">
                  <div class="county-bar-fill" data-target="<?= (int)$item['avgCompletion'] ?>" style="width:<?= (int)$item['avgCompletion'] ?>%"></div>
                </div>
                <span class="county-bar-pct"><?= Security::e((string)$item['avgCompletion']) ?>%</span>
              </div>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="con-grid-section" aria-label="All constituencies overview">
      <div class="container">
        <div class="con-grid-header">
          <div class="con-grid-header-left">
            <p class="con-grid-eyebrow"><?= Security::e(CmsLoader::text($gridCopy, 'eyebrow', 'Browse by Constituency')) ?></p>
            <h2 class="con-grid-title"><?= Security::e(CmsLoader::text($gridCopy, 'title_prefix', 'All')) ?> <span class="text-lime"><?= Security::e(CmsLoader::text($gridCopy, 'title_highlight', $stats['constituencies'] . ' Constituencies')) ?></span></h2>
          </div>
          <div class="con-grid-sort" id="conGridSort">
            <span class="con-sort-label"><?= Security::e(CmsLoader::text($gridCopy, 'sort_label', 'Sort:')) ?></span>
            <button class="con-sort-btn is-active" data-sort="default"><?= Security::e(CmsLoader::text($gridCopy, 'default_label', 'Default')) ?></button>
            <button class="con-sort-btn" data-sort="progress"><?= Security::e(CmsLoader::text($gridCopy, 'progress_label', 'Highest Progress')) ?></button>
            <button class="con-sort-btn" data-sort="units"><?= Security::e(CmsLoader::text($gridCopy, 'units_label', 'Most Units')) ?></button>
            <button class="con-sort-btn" data-sort="alpha"><?= Security::e(CmsLoader::text($gridCopy, 'alpha_label', 'A - Z')) ?></button>
          </div>
        </div>
        <div class="con-grid" id="conBrowseGrid">
<?php foreach ($constituencies as $item): ?>
          <article class="con-browse-card">
            <div class="con-browse-card-top">
              <div>
                <h3 class="con-browse-card-name"><?= Security::e($item['name']) ?></h3>
                <div class="con-browse-card-pop"><?= Security::e($item['population']) ?> residents</div>
              </div>
              <span class="con-browse-status con-browse-status--<?= Security::e($item['status']) ?>"><?= Security::e($item['status'] === 'active' ? 'Active' : 'Planning') ?></span>
            </div>
            <div class="con-browse-stats">
              <div class="con-browse-stat"><span class="con-browse-stat-val"><?= Security::e((string)$item['projectCount']) ?></span><span class="con-browse-stat-lbl">Projects</span></div>
              <div class="con-browse-stat"><span class="con-browse-stat-val"><?= Security::e(format_number((int)$item['totalUnits'])) ?></span><span class="con-browse-stat-lbl">Outputs</span></div>
              <div class="con-browse-stat"><span class="con-browse-stat-val"><?= Security::e((string)count($item['wards'])) ?></span><span class="con-browse-stat-lbl">Wards</span></div>
            </div>
            <div class="con-browse-progress">
              <div class="con-browse-progress-meta"><span>Avg. Completion</span><span><?= Security::e((string)$item['avgCompletion']) ?>%</span></div>
              <div class="con-browse-bar-track"><div class="con-browse-bar-fill" data-target="<?= (int)$item['avgCompletion'] ?>" style="width:<?= (int)$item['avgCompletion'] ?>%"></div></div>
            </div>
            <div class="con-browse-wards">
<?php foreach ($item['wards'] as $ward): ?>
              <span class="con-browse-wards-chip"><?= Security::e($ward) ?></span>
<?php endforeach; ?>
            </div>
            <a href="<?= Security::e($item['link']) ?>" class="con-browse-cta">
              Explore <?= Security::e($item['name']) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </article>
<?php endforeach; ?>
        </div>
      </div>
    </section>

  </main>

  <div class="con-apply-banner" role="complementary" aria-label="Apply for housing">
    <div class="container">
      <div class="con-apply-inner">
        <div class="con-apply-icon"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i></div>
        <div class="con-apply-copy">
          <h3 class="con-apply-title"><?= Security::e(CmsLoader::text($ctaCopy, 'title', 'Ready to Apply for Affordable Housing?')) ?></h3>
          <p class="con-apply-sub"><?= Security::e(CmsLoader::text($ctaCopy, 'subtitle', 'Register on the national Boma Yangu portal to join the Trans-Nzoia County AHP allocation list.')) ?></p>
        </div>
        <div class="con-apply-ctas">
          <a href="<?= Security::e(CmsLoader::text($ctaCopy, 'primary_url', 'https://bomayangu.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-lime">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> <?= Security::e(CmsLoader::text($ctaCopy, 'primary_label', 'Apply on Boma Yangu')) ?>
          </a>
          <a href="<?= Security::e(CmsLoader::text($ctaCopy, 'secondary_url', 'projects.php')) ?>" class="btn btn-outline-light">
            <?= Security::e(CmsLoader::text($ctaCopy, 'secondary_label', 'View All Projects')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  <?= public_json_script('ahp-page-data', $pagePayload) ?>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
