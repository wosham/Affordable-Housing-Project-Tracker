<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'projects';
$cmsPage = CmsLoader::page('projects');

$hero = CmsLoader::content($cmsPage, 'projects_hero', [
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
]);
$filters = CmsLoader::content($cmsPage, 'projects_filters', [
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
]);
$listing = CmsLoader::content($cmsPage, 'projects_listing', [
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
]);

$projects = Project::forPublic();
$constituencies = Database::fetchAll("
    SELECT DISTINCT c.slug, c.name
    FROM constituencies c
    INNER JOIN projects p ON p.constituency_id = c.id
    ORDER BY FIELD(c.slug, 'saboti','cherangany','endebess','kiminini','kwanza'), c.name ASC
");

$totalProjects = count($projects);
$totalUnits = array_sum(array_map(static fn (array $project): int => (int)($project['units'] ?? 0), $projects));
$activeProjects = count(array_filter($projects, static fn (array $project): bool => (string)($project['status'] ?? '') === 'active'));
$coveredConstituencies = count(array_unique(array_filter(array_map(static fn (array $project): string => (string)($project['constituency_slug'] ?? ''), $projects))));

$pageTitle = $cmsPage['seo_title'] ?? 'All Projects | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Browse all affordable housing projects in Trans-Nzoia County with live construction progress, contractor details and unit counts across all 5 constituencies.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing projects, AHP Kenya, Maili Tatu estate, Matunda estate, Saboti housing, Cherangany housing';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/projects.php';
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/projects.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/projects.js',
];

$e = static fn (mixed $value): string => Security::e($value);
$text = static fn (array $content, string $key, string $default = ''): string => CmsLoader::text($content, $key, $default);
$asset = static function (?string $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^https?://#i', $path) ? $path : $path;
};
$statusLabel = static function (string $status): string {
    return match ($status) {
        'active' => 'Active',
        'completed' => 'Completed',
        'stalled' => 'Stalled',
        'on_hold' => 'On Hold',
        'cancelled' => 'Cancelled',
        default => 'Planning',
    };
};
$publicStatus = static function (string $status): string {
    return $status === 'active' || $status === 'completed' ? 'active' : 'planning';
};

$headMeta = [
    '<meta property="og:title" content="' . $e($pageTitle) . '">',
    '<meta property="og:description" content="' . $e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . $e($canonicalUrl) . '">',
    '<meta property="og:image" content="' . $e($asset((string)($hero['background_image'] ?? 'uploads/gallery/maili-tatu-2.jpg'))) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . $e($pageTitle) . '">',
    '<meta name="twitter:description" content="' . $e($pageDescription) . '">',
    '<meta name="twitter:image" content="' . $e($asset((string)($hero['background_image'] ?? 'uploads/gallery/maili-tatu-2.jpg'))) . '">',
];

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

    <section class="page-hero page-hero--projects" aria-label="Projects directory">
      <div class="page-hero-bg" aria-hidden="true">
        <?php $heroImage = $asset((string)($hero['background_image'] ?? 'uploads/gallery/maili-tatu-2.jpg')); ?>
        <?php if ($heroImage !== ''): ?>
          <img src="<?= $e($heroImage) ?>" alt="<?= $e($text($hero, 'background_alt', '')) ?>" loading="eager" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="page-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current">All Projects</span>
        </nav>
        <div class="page-hero-body">
          <div class="page-hero-eyebrow">
            <span class="page-hero-tag"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> <?= $e($text($hero, 'eyebrow', 'AHP Projects')) ?></span>
          </div>
          <h1 class="page-hero-title"><?= $e($text($hero, 'title_plain', 'Housing Projects')) ?><br><span class="text-lime"><?= $e($text($hero, 'title_highlight', 'Directory')) ?></span></h1>
          <p class="page-hero-sub"><?= $e($text($hero, 'subtitle', 'Track every affordable housing project across Trans-Nzoia County - construction progress, contractor details, timelines and unit counts in real time.')) ?></p>
        </div>
        <div class="projects-hero-stats" role="region" aria-label="Programme statistics">
          <div class="phs-item">
            <span class="phs-val" id="statTotalProjects"><?= $e(format_number($totalProjects)) ?></span>
            <span class="phs-lbl"><?= $e($text($hero, 'total_projects_label', 'Total Projects')) ?></span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statTotalUnits"><?= $e(format_number($totalUnits)) ?></span>
            <span class="phs-lbl"><?= $e($text($hero, 'units_label', 'Units Planned')) ?></span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statActiveProjects"><?= $e(format_number($activeProjects)) ?></span>
            <span class="phs-lbl"><?= $e($text($hero, 'active_label', 'Active')) ?></span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statConstituencies"><?= $e(format_number($coveredConstituencies)) ?></span>
            <span class="phs-lbl"><?= $e($text($hero, 'constituencies_label', 'Constituencies')) ?></span>
          </div>
        </div>
      </div>
    </section>

    <section class="projects-main" aria-label="Project listings">
      <div class="container">
        <div class="projects-filter-bar" role="search" aria-label="Filter projects">
          <div class="pfb-search-wrap">
            <i class="fa-solid fa-magnifying-glass pfb-search-icon" aria-hidden="true"></i>
            <input type="search" class="pfb-search-input" id="projectSearch" placeholder="<?= $e($text($filters, 'search_placeholder', 'Search projects...')) ?>" aria-label="Search projects by name or location">
          </div>
          <div class="pfb-filters" role="group" aria-label="Filter by status">
            <span class="pfb-label"><?= $e($text($filters, 'status_label', 'Status:')) ?></span>
            <button class="pfb-chip is-active" type="button" data-filter-status="all"><?= $e($text($filters, 'all_label', 'All')) ?></button>
            <button class="pfb-chip" type="button" data-filter-status="active">
              <span class="pfb-chip-dot pfb-chip-dot--active" aria-hidden="true"></span><?= $e($text($filters, 'active_label', 'Active')) ?>
            </button>
            <button class="pfb-chip" type="button" data-filter-status="planning">
              <span class="pfb-chip-dot pfb-chip-dot--planning" aria-hidden="true"></span><?= $e($text($filters, 'planning_label', 'Planning')) ?>
            </button>
          </div>
          <div class="pfb-filters" role="group" aria-label="Filter by constituency">
            <span class="pfb-label"><?= $e($text($filters, 'constituency_label', 'Constituency:')) ?></span>
            <button class="pfb-chip is-active" type="button" data-filter-con="all"><?= $e($text($filters, 'all_label', 'All')) ?></button>
<?php foreach ($constituencies as $constituency): ?>
            <button class="pfb-chip" type="button" data-filter-con="<?= $e($constituency['slug']) ?>"><?= $e($constituency['name']) ?></button>
<?php endforeach; ?>
          </div>
          <div class="pfb-sort-wrap">
            <label for="projectSort" class="pfb-label"><?= $e($text($filters, 'sort_label', 'Sort:')) ?></label>
            <select id="projectSort" class="pfb-sort-select" aria-label="Sort projects">
              <option value="pct-desc"><?= $e($text($filters, 'sort_completion_desc', 'Completion high to low')) ?></option>
              <option value="pct-asc"><?= $e($text($filters, 'sort_completion_asc', 'Completion low to high')) ?></option>
              <option value="units-desc"><?= $e($text($filters, 'sort_units_desc', 'Most units')) ?></option>
              <option value="name-asc"><?= $e($text($filters, 'sort_name_asc', 'Name A-Z')) ?></option>
            </select>
          </div>
        </div>

        <div class="projects-results-meta" aria-live="polite" aria-atomic="true">
          <span id="resultsCount"><?= $e($text($listing, 'results_prefix', 'Showing')) ?> <?= $e(format_number($totalProjects)) ?> <?= $e($totalProjects === 1 ? $text($listing, 'project_single', 'project') : $text($listing, 'project_plural', 'projects')) ?></span>
          <button class="pfb-reset" type="button" id="resetFilters" hidden>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> <?= $e($text($filters, 'reset_label', 'Clear filters')) ?>
          </button>
        </div>

        <div class="projects-grid"
             id="projectsGrid"
             role="list"
             data-results-prefix="<?= $e($text($listing, 'results_prefix', 'Showing')) ?>"
             data-project-single="<?= $e($text($listing, 'project_single', 'project')) ?>"
             data-project-plural="<?= $e($text($listing, 'project_plural', 'projects')) ?>"
             data-pagination-showing="<?= $e($text($listing, 'pagination_showing_label', 'Showing')) ?>"
             data-pagination-of="<?= $e($text($listing, 'pagination_of_label', 'of')) ?>">
<?php foreach ($projects as $index => $project): ?>
          <?php
            $status = (string)($project['status'] ?? 'planning');
            $displayStatus = $publicStatus($status);
            $pct = max(0, min(100, (int)($project['pct_complete'] ?? 0)));
            $image = $asset((string)($project['hero_image'] ?? ''));
            $name = (string)($project['name'] ?? 'Project');
            $search = strtolower(trim(implode(' ', [
                $name,
                $project['constituency_name'] ?? '',
                $project['ward_name'] ?? '',
                $project['contractor_name'] ?? '',
                $project['current_milestone'] ?? '',
            ])));
          ?>
          <article class="proj-card is-visible"
                   role="listitem"
                   style="animation-delay:<?= (int)($index % 6) * 55 ?>ms"
                   data-status="<?= $e($displayStatus) ?>"
                   data-constituency="<?= $e($project['constituency_slug'] ?? '') ?>"
                   data-name="<?= $e(strtolower($name)) ?>"
                   data-units="<?= (int)($project['units'] ?? 0) ?>"
                   data-pct="<?= $pct ?>"
                   data-search="<?= $e($search) ?>">
            <div class="proj-card-img">
<?php if ($image !== ''): ?>
              <img src="<?= $e($image) ?>" alt="<?= $e($name) ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="proj-card-img-placeholder" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
<?php else: ?>
              <div class="proj-card-img-placeholder"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
<?php endif; ?>
              <span class="proj-status-badge proj-status-badge--<?= $e($displayStatus) ?>" aria-label="Status: <?= $e($statusLabel($status)) ?>"><?= $e($statusLabel($status)) ?></span>
              <span class="proj-con-tag"><?= $e($project['constituency_name'] ?? '') ?></span>
            </div>
            <div class="proj-card-body">
              <div class="proj-card-meta">
                <span class="proj-ward"><?= $e($project['ward_name'] ?? $project['location_label'] ?? '') ?></span>
              </div>
              <h3 class="proj-card-title"><?= $e($name) ?></h3>
              <div class="proj-card-stats">
                <div class="proj-stat">
                  <span class="proj-stat-val"><?= $e(format_number((int)($project['units'] ?? 0))) ?></span>
                  <span class="proj-stat-lbl"><?= $e($text($listing, 'units_label', 'Units')) ?></span>
                </div>
                <div class="proj-stat">
                  <span class="proj-stat-val"><?= $pct ?>%</span>
                  <span class="proj-stat-lbl"><?= $e($text($listing, 'complete_label', 'Complete')) ?></span>
                </div>
              </div>
              <div class="proj-progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= $pct ?>% complete">
                <div class="proj-progress-fill" data-target="<?= $pct ?>"></div>
              </div>
              <div class="proj-milestone">
                <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
                <span><?= $e($project['current_milestone'] ?? 'Project details pending') ?></span>
              </div>
              <div class="proj-card-footer">
                <span class="proj-contractor" title="<?= $e($project['contractor_name'] ?? '') ?>"><?= $e($project['contractor_name'] ?? '') ?></span>
                <a href="project-detail.php?id=<?= $e(rawurlencode((string)($project['slug'] ?? ''))) ?>" class="proj-cta" aria-label="View details for <?= $e($name) ?>">
                  <?= $e($text($listing, 'view_label', 'View Project')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
              </div>
            </div>
          </article>
<?php endforeach; ?>
        </div>

        <nav class="projects-pagination" id="projectsPagination" aria-label="Project pages" hidden></nav>

        <div class="projects-empty" id="projectsEmpty" hidden aria-live="polite">
          <div class="projects-empty-icon" aria-hidden="true">
            <i class="fa-solid fa-building-circle-xmark"></i>
          </div>
          <h3><?= $e($text($listing, 'empty_title', 'No projects found')) ?></h3>
          <p><?= $e($text($listing, 'empty_text', 'Try adjusting your filters or search term.')) ?></p>
          <button class="btn btn-primary" type="button" id="emptyReset"><?= $e($text($listing, 'empty_reset_label', 'Clear all filters')) ?></button>
        </div>
      </div>
    </section>

  </main>

<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
