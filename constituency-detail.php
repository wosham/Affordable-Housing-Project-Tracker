<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'constituencies';
$cmsPage = CmsLoader::page('constituency-detail');
$slug = strtolower(trim((string)($_GET['id'] ?? $_GET['slug'] ?? '')));

$labels = CmsLoader::content($cmsPage, 'constituency_detail_labels', [
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
]);
$facts = CmsLoader::content($cmsPage, 'constituency_detail_facts', [
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
]);
$relatedCopy = CmsLoader::content($cmsPage, 'constituency_detail_related', [
    'title' => 'Other Constituencies',
    'subtitle' => 'Explore housing developments across Trans-Nzoia County.',
    'view_all_label' => 'View all',
    'explore_label' => 'Explore',
    'not_found_title' => 'Constituency Not Found',
    'not_found_text' => "The constituency you're looking for doesn't exist or the URL is incorrect.",
    'not_found_button' => 'Back to Constituencies',
]);
$cta = CmsLoader::content($cmsPage, 'constituency_detail_apply_cta', [
    'title' => 'Ready to Apply for Affordable Housing?',
    'subtitle' => 'Register on the national Boma Yangu portal to join the allocation list for this constituency.',
    'primary_label' => 'Apply on Boma Yangu',
    'primary_url' => 'https://bomayangu.go.ke',
    'secondary_label' => 'View All Projects',
    'secondary_url' => 'projects.php',
]);

function cd_constituency_rows(): array
{
    $rows = Database::fetchAll("
        SELECT
            c.*,
            COUNT(DISTINCT p.id) AS live_project_count,
            COALESCE(SUM(p.units), 0) AS live_total_units,
            COALESCE(AVG(p.pct_complete), 0) AS live_avg_completion
        FROM constituencies c
        LEFT JOIN projects p ON p.constituency_id = c.id
        WHERE c.slug IN ('saboti','cherangany','endebess','kiminini','kwanza')
        GROUP BY c.id
        ORDER BY FIELD(c.slug, 'saboti','cherangany','endebess','kiminini','kwanza'), c.name ASC
    ");

    $wards = [];
    foreach (Database::fetchAll("
        SELECT c.slug AS constituency_slug, w.name
        FROM wards w
        INNER JOIN constituencies c ON c.id = w.constituency_id
        WHERE c.slug IN ('saboti','cherangany','endebess','kiminini','kwanza')
        ORDER BY FIELD(c.slug, 'saboti','cherangany','endebess','kiminini','kwanza'), w.id ASC
    ") as $ward) {
        $wards[(string)$ward['constituency_slug']][] = (string)$ward['name'];
    }

    foreach ($rows as &$row) {
        $row['wards'] = $wards[(string)$row['slug']] ?? [];
        $row['project_count'] = (int)$row['live_project_count'] > 0 ? (int)$row['live_project_count'] : (int)($row['total_projects'] ?? 0);
        $row['total_units_live'] = (int)$row['live_total_units'] > 0 ? (int)$row['live_total_units'] : (int)($row['total_units'] ?? 0);
        $row['avg_completion_live'] = (int)round((float)$row['live_avg_completion'] > 0 ? (float)$row['live_avg_completion'] : (float)($row['avg_completion'] ?? 0));
    }
    unset($row);

    return $rows;
}

function cd_replace_tokens(string $text, array $values): string
{
    return strtr($text, [
        '{projects}' => (string)$values['projects'],
        '{project_word}' => (string)$values['project_word'],
        '{constituency}' => (string)$values['constituency'],
        '{units}' => (string)$values['units'],
        '{wards}' => (string)$values['wards'],
    ]);
}

$constituencies = cd_constituency_rows();
$constituency = null;
foreach ($constituencies as $row) {
    if ((string)$row['slug'] === $slug) {
        $constituency = $row;
        break;
    }
}

$projects = $constituency ? Project::forPublic(['constituency' => (string)$constituency['slug']]) : [];
$otherConstituencies = array_values(array_filter($constituencies, static fn (array $row): bool => (string)$row['slug'] !== $slug));
$projectCount = count($projects);
$totalUnits = array_sum(array_map(static fn (array $project): int => (int)($project['units'] ?? 0), $projects));
$avgCompletion = $projectCount > 0 ? (int)round(array_sum(array_map(static fn (array $project): int => (int)($project['pct_complete'] ?? 0), $projects)) / $projectCount) : 0;
$wardCount = $constituency ? count($constituency['wards']) : 0;
$isFound = $constituency !== null;

$e = static fn (mixed $value): string => Security::e($value);
$text = static fn (array $content, string $key, string $default = ''): string => CmsLoader::text($content, $key, $default);
$asset = static function (?string $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^https?://#i', $path) ? $path : $path;
};
$statusLabel = static function (string $status, array $labels): string {
    return in_array($status, ['active', 'completed'], true)
        ? CmsLoader::text($labels, 'active_status_label', 'Active Construction')
        : CmsLoader::text($labels, 'planning_status_label', 'Planning Stage');
};
$publicStatus = static fn (string $status): string => in_array($status, ['active', 'completed'], true) ? 'active' : 'planning';
$shortPopulation = static function (mixed $value): string {
    $number = (int)$value;
    return $number > 0 ? '~' . format_number($number) : '-';
};

$pageTitle = $isFound
    ? (string)$constituency['name'] . ' Constituency | Trans-Nzoia AHP Tracker'
    : ($cmsPage['seo_title'] ?? 'Constituency Detail | Trans-Nzoia County Affordable Housing Tracker');
$pageDescription = $isFound
    ? (string)($constituency['description'] ?? 'Detailed affordable housing information for this Trans-Nzoia constituency.')
    : ($cmsPage['seo_description'] ?? 'Detailed affordable housing information for Trans-Nzoia constituencies, including live projects, progress, wards and local facts.');
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia constituency housing, AHP Kenya, affordable housing projects';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = $isFound ? 'index, follow' : 'noindex, follow';
$themeColor = '#163300';
$canonicalUrl = $isFound
    ? 'https://housing.transnzoia.go.ke/constituency-detail.php?id=' . rawurlencode((string)$constituency['slug'])
    : ($cmsPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/constituency-detail.php');
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/constituency-detail.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/constituency-detail.js',
];
$heroImage = $isFound ? $asset((string)($constituency['hero_image'] ?? '')) : '';
$headMeta = [
    '<meta property="og:title" content="' . $e($pageTitle) . '">',
    '<meta property="og:description" content="' . $e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . $e($canonicalUrl) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
];
if ($heroImage !== '') {
    $headMeta[] = '<meta property="og:image" content="' . $e($heroImage) . '">';
}

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

<?php if ($isFound): ?>
    <section class="cd-hero" id="cdHero" aria-label="Constituency overview">
      <div class="cd-hero-bg" id="cdHeroBg" aria-hidden="true">
<?php if ($heroImage !== ''): ?>
        <img src="<?= $e($heroImage) ?>" alt="" loading="eager" onerror="this.style.display='none'">
<?php endif; ?>
        <div class="cd-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb" id="cdBreadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <a href="constituencies.php" class="breadcrumb-link">Constituencies</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current" id="cdBreadcrumbCurrent"><?= $e($constituency['name']) ?></span>
        </nav>
        <div class="cd-hero-body" id="cdHeroBody">
          <div class="cd-hero-eyebrow">
            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
            Trans-Nzoia County - <?= $e($statusLabel((string)($constituency['status'] ?? 'planning'), $labels)) ?>
          </div>
          <h1 class="cd-hero-title"><?= $e($constituency['name']) ?></h1>
          <p class="cd-hero-desc"><?= $e($constituency['description'] ?? '') ?></p>
        </div>
        <div class="cd-hero-kpis" id="cdHeroKpis">
          <div class="cd-kpi-card">
            <i class="fa-solid fa-building-columns cd-kpi-icon" aria-hidden="true"></i>
            <span class="cd-kpi-val"><?= $e(format_number($projectCount)) ?></span>
            <span class="cd-kpi-lbl"><?= $e($text($labels, 'projects_label', 'Projects')) ?></span>
          </div>
          <div class="cd-kpi-card">
            <i class="fa-solid fa-house-chimney cd-kpi-icon" aria-hidden="true"></i>
            <span class="cd-kpi-val"><?= $e(format_number($totalUnits)) ?></span>
            <span class="cd-kpi-lbl"><?= $e($text($labels, 'units_label', 'Units Planned')) ?></span>
          </div>
          <div class="cd-kpi-card">
            <i class="fa-solid fa-users cd-kpi-icon" aria-hidden="true"></i>
            <span class="cd-kpi-val"><?= $e($shortPopulation($constituency['population'] ?? 0)) ?></span>
            <span class="cd-kpi-lbl"><?= $e($text($labels, 'population_label', 'Population')) ?></span>
          </div>
          <div class="cd-kpi-card">
            <i class="fa-solid fa-chart-simple cd-kpi-icon" aria-hidden="true"></i>
            <span class="cd-kpi-val"><?= $avgCompletion ?>%</span>
            <span class="cd-kpi-lbl"><?= $e($text($labels, 'completion_label', 'Avg. Completion')) ?></span>
          </div>
        </div>
      </div>
    </section>

    <div class="cd-ward-strip" id="cdWardStrip" aria-label="Constituency wards">
      <div class="container">
        <div class="cd-ward-strip-inner">
          <span class="cd-ward-strip-label"><i class="fa-solid fa-map-pin" aria-hidden="true"></i> <?= $e($text($labels, 'wards_label', 'Wards')) ?></span>
<?php foreach ($constituency['wards'] as $ward): ?>
          <span class="cd-ward-pill"><?= $e($ward) ?></span>
<?php endforeach; ?>
        </div>
      </div>
    </div>

    <section class="cd-projects-section" aria-label="Projects in this constituency">
      <div class="container">
        <div class="cd-section-header">
          <div>
            <h2 class="cd-section-title" id="cdProjectsTitle"><?= $e($constituency['name']) ?> <?= $e($text($labels, 'projects_title_suffix', 'Projects')) ?></h2>
            <p class="cd-section-sub"><?= $e($text($labels, 'projects_subtitle', 'All housing developments in this constituency under the national AHP programme.')) ?></p>
          </div>
          <a href="projects.php?constituency=<?= $e(rawurlencode((string)$constituency['slug'])) ?>" class="cd-view-all" id="cdViewAll">
            <?= $e($text($labels, 'view_all_projects_label', 'View all projects')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div class="cd-projects-grid" id="cdProjectsGrid">
<?php if ($projects): ?>
<?php foreach ($projects as $project): ?>
          <?php
            $projectStatus = $publicStatus((string)($project['status'] ?? 'planning'));
            $pct = max(0, min(100, (int)($project['pct_complete'] ?? 0)));
            $image = $asset((string)($project['hero_image'] ?? ''));
          ?>
          <article class="proj-card">
            <div class="proj-card-img">
<?php if ($image !== ''): ?>
              <img src="<?= $e($image) ?>" alt="<?= $e($project['name'] ?? '') ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="proj-card-img-placeholder" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
<?php else: ?>
              <div class="proj-card-img-placeholder"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
<?php endif; ?>
              <span class="proj-status-badge proj-status-badge--<?= $e($projectStatus) ?>"><?= $e($projectStatus === 'active' ? 'Active' : 'Planning') ?></span>
              <span class="proj-con-tag"><?= $e($project['ward_name'] ?? $project['location_label'] ?? '') ?></span>
            </div>
            <div class="proj-card-body">
              <div class="proj-card-meta"><span class="proj-ward"><?= $e($project['ward_name'] ?? $project['location_label'] ?? '') ?></span></div>
              <h3 class="proj-card-title"><?= $e($project['name'] ?? 'Project') ?></h3>
              <div class="proj-card-stats">
                <div class="proj-stat">
                  <span class="proj-stat-val"><?= $e(format_number((int)($project['units'] ?? 0))) ?></span>
                  <span class="proj-stat-lbl"><?= $e($text($labels, 'unit_card_label', 'Units')) ?></span>
                </div>
                <div class="proj-stat">
                  <span class="proj-stat-val"><?= $pct ?>%</span>
                  <span class="proj-stat-lbl"><?= $e($text($labels, 'complete_card_label', 'Complete')) ?></span>
                </div>
              </div>
              <div class="proj-progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="proj-progress-fill" data-target="<?= $pct ?>"></div>
              </div>
              <div class="proj-milestone">
                <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
                <span><?= $e($project['current_milestone'] ?? 'Project details pending') ?></span>
              </div>
              <div class="proj-card-footer">
                <span class="proj-contractor" title="<?= $e($project['contractor_name'] ?? '') ?>"><?= $e($project['contractor_name'] ?? '') ?></span>
                <a href="project-detail.php?id=<?= $e(rawurlencode((string)($project['slug'] ?? ''))) ?>" class="proj-cta"><?= $e($text($labels, 'view_project_label', 'View Project')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
              </div>
            </div>
          </article>
<?php endforeach; ?>
<?php else: ?>
          <p class="cd-empty-text"><?= $e($text($labels, 'empty_projects_text', 'No projects listed yet for this constituency.')) ?></p>
<?php endif; ?>
        </div>
      </div>
    </section>

    <section class="cd-progress-section" id="cdProgressSection" aria-label="Constituency progress overview">
      <div class="container">
        <div class="cd-prog-inner">
          <div class="cd-prog-left">
            <div class="cd-prog-label"><?= $e($text($labels, 'progress_eyebrow', 'Progress Overview')) ?></div>
            <h2 class="cd-prog-title"><?= $e($constituency['name']) ?> <?= $e($text($labels, 'progress_title_suffix', 'Construction Progress')) ?></h2>
            <p class="cd-prog-sub">
              <?= $e(cd_replace_tokens($text($labels, 'progress_subtitle', 'Across {projects} active {project_word}, {constituency} Constituency has delivered {units} units under the national AHP programme. Work is progressing across {wards} wards.'), [
                  'projects' => format_number($projectCount),
                  'project_word' => $projectCount === 1 ? 'project' : 'projects',
                  'constituency' => (string)$constituency['name'],
                  'units' => format_number($totalUnits),
                  'wards' => format_number($wardCount),
              ])) ?>
            </p>
          </div>
          <div class="cd-prog-right">
            <div>
              <div class="cd-prog-pct-display"><?= $avgCompletion ?>%</div>
              <div class="cd-prog-pct-lbl"><?= $e($text($labels, 'completion_label', 'Average Completion')) ?></div>
            </div>
            <div class="cd-prog-bar-wrap" role="progressbar" aria-valuenow="<?= $avgCompletion ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="cd-prog-bar-fill" data-target="<?= $avgCompletion ?>"></div>
            </div>
            <div class="cd-prog-stats-row">
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val"><?= $e(format_number($totalUnits)) ?></span>
                <span class="cd-prog-stat-lbl"><?= $e($text($labels, 'total_units_label', 'Total Units')) ?></span>
              </div>
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val"><?= $e(format_number($projectCount)) ?></span>
                <span class="cd-prog-stat-lbl"><?= $e($text($labels, 'projects_label', 'Projects')) ?></span>
              </div>
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val"><?= $e(format_number($wardCount)) ?></span>
                <span class="cd-prog-stat-lbl"><?= $e($text($labels, 'wards_label', 'Wards')) ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="cd-facts-section" aria-label="Constituency facts">
      <div class="container">
        <div class="cd-facts-inner">
          <div class="cd-facts-col">
            <h2 class="cd-section-title"><?= $e($text($facts, 'facts_title', 'Constituency Facts')) ?></h2>
            <div class="cd-facts-grid" id="cdFactsGrid">
              <?php
                $factRows = [
                    ['fa-map-pin', $text($facts, 'county_label', 'County'), 'Trans-Nzoia County, Kenya'],
                    ['fa-users', $text($facts, 'population_label', 'Population'), $shortPopulation($constituency['population'] ?? 0)],
                    ['fa-map', $text($facts, 'wards_label', 'Wards'), implode(', ', $constituency['wards'])],
                    ['fa-person-shelter', $text($facts, 'lead_agency_label', 'Lead Agency'), $text($facts, 'lead_agency_value', 'State Dept. of Housing')],
                    ['fa-coins', $text($facts, 'funding_label', 'Funding'), $text($facts, 'funding_value', 'National AHP Fund + County Budget')],
                    ['fa-building-columns', $text($facts, 'programme_label', 'Programme'), $text($facts, 'programme_value', 'National Affordable Housing Programme')],
                ];
              ?>
<?php foreach ($factRows as [$icon, $label, $value]): ?>
              <div class="cd-fact-card">
                <i class="fa-solid <?= $e($icon) ?> cd-fact-icon" aria-hidden="true"></i>
                <span class="cd-fact-label"><?= $e($label) ?></span>
                <span class="cd-fact-value"><?= $e($value) ?></span>
              </div>
<?php endforeach; ?>
            </div>
          </div>
          <div class="cd-mini-map-col">
            <h2 class="cd-section-title"><?= $e($text($facts, 'location_title', 'Location')) ?></h2>
            <div class="cd-mini-map-wrap">
              <svg id="cdMiniMap" viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg" aria-label="Trans-Nzoia County map with selected constituency highlighted">
<?php
$mapShapes = [
    'endebess' => ['M 20,20 L 220,20 L 220,185 L 120,205 L 20,165 Z', 112, 105, 'Endebess'],
    'cherangany' => ['M 220,20 L 460,20 L 460,235 L 265,235 L 220,185 Z', 352, 120, 'Cherangany'],
    'kiminini' => ['M 20,165 L 120,205 L 120,385 L 20,385 Z', 62, 295, 'Kiminini'],
    'saboti' => ['M 120,205 L 220,185 L 265,235 L 265,385 L 120,385 Z', 190, 308, 'Saboti'],
    'kwanza' => ['M 265,235 L 460,235 L 460,385 L 265,385 Z', 365, 313, 'Kwanza'],
];
?>
<?php foreach ($mapShapes as $mapSlug => [$path, $x, $y, $name]): ?>
                <g class="cdm-path-group<?= $mapSlug === (string)$constituency['slug'] ? ' is-selected' : '' ?>" data-id="<?= $e($mapSlug) ?>">
                  <path d="<?= $e($path) ?>" class="cdm-path"/>
                  <text class="cdm-label" x="<?= (int)$x ?>" y="<?= (int)$y ?>"><?= $e($name) ?></text>
                </g>
<?php endforeach; ?>
              </svg>
            </div>
            <div class="cd-mini-map-nav">
              <a href="constituencies.php" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-map" aria-hidden="true"></i> <?= $e($text($facts, 'all_constituencies_label', 'All Constituencies')) ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="cd-related-section" aria-label="Other constituencies">
      <div class="container">
        <div class="cd-section-header">
          <div>
            <h2 class="cd-section-title"><?= $e($text($relatedCopy, 'title', 'Other Constituencies')) ?></h2>
            <p class="cd-section-sub"><?= $e($text($relatedCopy, 'subtitle', 'Explore housing developments across Trans-Nzoia County.')) ?></p>
          </div>
          <a href="constituencies.php" class="cd-view-all">
            <?= $e($text($relatedCopy, 'view_all_label', 'View all')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div class="cd-related-grid" id="cdRelatedGrid">
<?php foreach ($otherConstituencies as $other): ?>
          <?php $otherStatus = $publicStatus((string)($other['status'] ?? 'planning')); ?>
          <a href="constituency-detail.php?id=<?= $e(rawurlencode((string)$other['slug'])) ?>" class="cd-related-card">
            <div class="cd-related-head">
              <div>
                <div class="cd-related-name"><?= $e($other['name']) ?></div>
                <div class="cd-related-pop"><?= $e($shortPopulation($other['population'] ?? 0)) ?></div>
              </div>
              <span class="con-browse-status con-browse-status--<?= $e($otherStatus) ?>"><?= $e($otherStatus === 'active' ? 'Active' : 'Planning') ?></span>
            </div>
            <div class="cd-related-stats">
              <div class="cd-related-stat">
                <span class="cd-related-stat-val"><?= $e(format_number((int)$other['project_count'])) ?></span>
                <span class="cd-related-stat-lbl"><?= $e($text($labels, 'projects_label', 'Projects')) ?></span>
              </div>
              <div class="cd-related-stat">
                <span class="cd-related-stat-val"><?= $e(format_number((int)$other['total_units_live'])) ?></span>
                <span class="cd-related-stat-lbl"><?= $e($text($labels, 'unit_card_label', 'Units')) ?></span>
              </div>
              <div class="cd-related-stat">
                <span class="cd-related-stat-val"><?= $e(format_number(count($other['wards'] ?? []))) ?></span>
                <span class="cd-related-stat-lbl"><?= $e($text($labels, 'wards_label', 'Wards')) ?></span>
              </div>
            </div>
            <div class="cd-related-cta"><?= $e($text($relatedCopy, 'explore_label', 'Explore')) ?> <?= $e($other['name']) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div>
          </a>
<?php endforeach; ?>
        </div>
      </div>
    </section>
<?php else: ?>
    <div class="cd-not-found" id="cdNotFound">
      <div class="container">
        <div class="cd-not-found-inner">
          <i class="fa-solid fa-map-location-dot cd-not-found-icon" aria-hidden="true"></i>
          <h2><?= $e($text($relatedCopy, 'not_found_title', 'Constituency Not Found')) ?></h2>
          <p><?= $e($text($relatedCopy, 'not_found_text', "The constituency you're looking for doesn't exist or the URL is incorrect.")) ?></p>
          <a href="constituencies.php" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= $e($text($relatedCopy, 'not_found_button', 'Back to Constituencies')) ?>
          </a>
        </div>
      </div>
    </div>
<?php endif; ?>

  </main>

<?php if ($isFound): ?>
  <div class="cd-apply-banner" role="complementary" aria-label="Apply for housing">
    <div class="container">
      <div class="cd-apply-inner">
        <div class="cd-apply-icon"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i></div>
        <div class="cd-apply-copy">
          <h3 class="cd-apply-title"><?= $e($text($cta, 'title', 'Ready to Apply for Affordable Housing?')) ?></h3>
          <p class="cd-apply-sub"><?= $e($text($cta, 'subtitle', 'Register on the national Boma Yangu portal to join the allocation list for this constituency.')) ?></p>
        </div>
        <div class="cd-apply-ctas">
          <a href="<?= $e($text($cta, 'primary_url', 'https://bomayangu.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="cd-btn-lime">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> <?= $e($text($cta, 'primary_label', 'Apply on Boma Yangu')) ?>
          </a>
          <a href="<?= $e($text($cta, 'secondary_url', 'projects.php')) ?>" class="cd-btn-outline-light">
            <?= $e($text($cta, 'secondary_label', 'View All Projects')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
