<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'constituencies';
$cmsPage = CmsLoader::page('constituencies');

function constituencies_content(array $page, string $key, array $defaults): array
{
    return CmsLoader::content($page, $key, $defaults);
}

function constituencies_number(int|float $value): string
{
    return number_format((float)$value, 0);
}

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

function constituencies_plural(int $count, string $singular, string $plural): string
{
    return $count === 1 ? $singular : $plural;
}

function constituencies_replace_stats(string $text, array $stats): string
{
    return strtr($text, [
        '{constituencies}' => (string)$stats['constituencies'],
        '{projects}' => (string)$stats['projects'],
        '{units}' => constituencies_number((int)$stats['units']),
        '{residents}' => constituencies_short_number((int)$stats['residents']),
    ]);
}

function constituencies_map_shapes(): array
{
    return [
        'endebess' => ['path' => 'M 20,20 L 220,20 L 220,185 L 120,205 L 20,165 Z', 'label_x' => 112, 'label_y' => 100, 'sub_y' => 116],
        'cherangany' => ['path' => 'M 220,20 L 460,20 L 460,235 L 265,235 L 220,185 Z', 'label_x' => 352, 'label_y' => 115, 'sub_y' => 131],
        'kiminini' => ['path' => 'M 20,165 L 120,205 L 120,385 L 20,385 Z', 'label_x' => 62, 'label_y' => 295, 'sub_y' => 311],
        'saboti' => ['path' => 'M 120,205 L 220,185 L 265,235 L 265,385 L 120,385 Z', 'label_x' => 190, 'label_y' => 308, 'sub_y' => 324],
        'kwanza' => ['path' => 'M 265,235 L 460,235 L 460,385 L 265,385 Z', 'label_x' => 365, 'label_y' => 313, 'sub_y' => 329],
    ];
}

function constituencies_rows(): array
{
    $rows = Database::fetchAll("
        SELECT
            c.id,
            c.slug,
            c.name,
            c.mp,
            c.description,
            c.population,
            c.total_units,
            c.total_projects,
            c.avg_completion,
            c.status,
            c.hero_image,
            COUNT(DISTINCT p.id) AS live_project_count,
            COALESCE(SUM(p.units), 0) AS live_total_units,
            COALESCE(AVG(p.pct_complete), 0) AS live_avg_completion
        FROM constituencies c
        LEFT JOIN projects p ON p.constituency_id = c.id
        WHERE c.slug IN ('saboti','cherangany','endebess','kiminini','kwanza')
        GROUP BY c.id
        ORDER BY FIELD(c.slug, 'saboti','cherangany','endebess','kiminini','kwanza')
    ");

    $wardsByConstituency = [];
    foreach (Database::fetchAll("
        SELECT c.slug AS constituency_slug, w.name
        FROM wards w
        INNER JOIN constituencies c ON c.id = w.constituency_id
        WHERE c.slug IN ('saboti','cherangany','endebess','kiminini','kwanza')
        ORDER BY FIELD(c.slug, 'saboti','cherangany','endebess','kiminini','kwanza'), w.id ASC
    ") as $ward) {
        $wardsByConstituency[(string)$ward['constituency_slug']][] = (string)$ward['name'];
    }

    $data = [];
    foreach ($rows as $row) {
        $slug = (string)$row['slug'];
        $projectCount = (int)$row['live_project_count'] > 0 ? (int)$row['live_project_count'] : (int)$row['total_projects'];
        $totalUnits = (int)$row['live_total_units'] > 0 ? (int)$row['live_total_units'] : (int)$row['total_units'];
        $avgCompletion = (int)round((float)$row['live_avg_completion'] > 0 ? (float)$row['live_avg_completion'] : (float)$row['avg_completion']);
        $population = (int)($row['population'] ?? 0);

        $data[] = [
            'id' => $slug,
            'name' => (string)$row['name'],
            'wards' => $wardsByConstituency[$slug] ?? [],
            'population' => $population > 0 ? '~' . constituencies_number($population) : '-',
            'populationRaw' => $population,
            'mp' => (string)($row['mp'] ?? ''),
            'totalUnits' => $totalUnits,
            'avgCompletion' => max(0, min(100, $avgCompletion)),
            'status' => in_array((string)$row['status'], ['active', 'completed'], true) ? 'active' : 'planning',
            'projectCount' => $projectCount,
            'description' => (string)($row['description'] ?? ''),
            'link' => 'constituency-detail.php?id=' . rawurlencode($slug),
            'heroImage' => $row['hero_image'] ?: null,
        ];
    }

    return $data;
}

$hero = constituencies_content($cmsPage, 'constituencies_hero', [
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
]);
$mapCopy = constituencies_content($cmsPage, 'constituencies_map', [
    'title' => 'Trans-Nzoia County',
    'subtitle' => 'Click a constituency to explore its housing projects',
    'active_label' => 'Active construction',
    'planning_label' => 'Planning stage',
    'selected_label' => 'Selected',
    'empty_text' => 'Constituency data will appear after projects and wards are added to the registry.',
]);
$progressCopy = constituencies_content($cmsPage, 'constituencies_progress', [
    'title' => "County-Wide\nProgramme\nProgress",
    'subtitle' => 'Across all {constituencies} constituencies, the programme is delivering {units} affordable housing units - targeting Kenyans registered on the national Boma Yangu portal.',
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
    'units_label' => 'Most Units',
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

$constituencies = constituencies_rows();
$stats = [
    'constituencies' => count($constituencies),
    'projects' => array_sum(array_map(static fn (array $item): int => (int)$item['projectCount'], $constituencies)),
    'units' => array_sum(array_map(static fn (array $item): int => (int)$item['totalUnits'], $constituencies)),
    'residents' => array_sum(array_map(static fn (array $item): int => (int)$item['populationRaw'], $constituencies)),
];
$constituenciesBySlug = [];
foreach ($constituencies as $item) {
    $constituenciesBySlug[$item['id']] = $item;
}

$pageTitle = $cmsPage['seo_title'] ?? 'Constituencies | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Explore affordable housing coverage across all 5 constituencies in Trans-Nzoia County - Saboti, Cherangany, Endebess, Kiminini and Kwanza.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia constituencies, Saboti housing, Cherangany AHP, Endebess housing, Kiminini housing, Kwanza housing';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/constituencies.php';
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
        <img src="<?= Security::e($heroImage) ?>" alt="" loading="eager" onerror="this.style.display='none'">
        <div class="page-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current">Constituencies</span>
        </nav>
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
<?php foreach (constituencies_map_shapes() as $slug => $shape): ?>
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
          <?php if (!$constituencies): ?><p class="con-map-hint"><?= Security::e(CmsLoader::text($mapCopy, 'empty_text', 'Constituency data will appear after projects and wards are added to the registry.')) ?></p><?php endif; ?>
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
          <div class="county-total-right" id="countyTotalsChart" aria-label="Constituency progress comparison"></div>
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
        <div class="con-grid" id="conBrowseGrid"></div>
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
  <script>
    window.AHP_DATA = {
      constituencies: <?= json_encode($constituencies, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
      projects: [],
      getConstituency: function (id) {
        return this.constituencies.find(function (item) { return item.id === id; }) || null;
      },
      getProject: function () {
        return null;
      },
      getProjectsByConstituency: function () {
        return [];
      }
    };
  </script>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
