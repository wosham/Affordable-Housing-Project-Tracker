<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'projects';
$cmsPage = CmsLoader::publicPage('projects');

$hero = CmsLoader::content($cmsPage, 'projects_hero');
$filters = CmsLoader::content($cmsPage, 'projects_filters');
$listing = CmsLoader::content($cmsPage, 'projects_listing');

$initialFilters = [
    'q' => Security::cleanString((string)($_GET['search'] ?? $_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency' => Security::cleanString((string)($_GET['constituency'] ?? '')),
    'category' => Security::cleanString((string)($_GET['category'] ?? '')),
    'sort' => Security::cleanString((string)($_GET['sort'] ?? 'pct-desc')),
];
foreach (['status', 'constituency', 'category'] as $filterKey) {
    if (strtolower($initialFilters[$filterKey]) === 'all') {
        $initialFilters[$filterKey] = '';
    }
}
$initialFilters['status'] = in_array($initialFilters['status'], ['active', 'planning', 'completed', 'on_hold', 'stalled'], true)
    ? $initialFilters['status']
    : '';
$initialFilters['sort'] = in_array($initialFilters['sort'], ['pct-desc', 'pct-asc', 'units-desc', 'name-asc', 'updated-desc'], true)
    ? $initialFilters['sort']
    : 'pct-desc';
$hasInitialFilters = $initialFilters['q'] !== ''
    || $initialFilters['status'] !== ''
    || $initialFilters['constituency'] !== ''
    || $initialFilters['category'] !== ''
    || $initialFilters['sort'] !== 'pct-desc';

$projects = Project::publicListing($initialFilters);
$constituencies = Project::publicConstituencies();
$categories = Project::publicCategories();
$projectStats = Project::publicStats($initialFilters);
$coverageConstituencies = class_exists('Constituency') ? Constituency::publicList() : [];
$featuredProjects = array_slice($projects, 0, 3);

$totalProjects = (int)($projectStats['total_projects'] ?? count($projects));
$totalUnits = (int)($projectStats['total_units'] ?? 0);
$activeProjects = (int)($projectStats['active_projects'] ?? 0);
$coveredConstituencies = (int)($projectStats['constituencies'] ?? 0);
$publicPayload = public_page_payload('projects', [
    'projects' => $projects,
    'constituencies' => $constituencies,
    'coverage_constituencies' => $coverageConstituencies,
    'featured_projects' => $featuredProjects,
    'categories' => $categories,
    'stats' => $projectStats,
], [
    'title' => 'All Projects',
    'description' => 'Browse AHP housing, modern markets, ESP sites, and institutional housing across Trans-Nzoia County.',
]);

$pageTitle = $cmsPage['seo_title'] ?? 'All Projects | Trans-Nzoia County Project Delivery Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Browse AHP housing, modern markets, ESP sites, and institutional housing projects in Trans-Nzoia County with live progress, contractor details and output counts.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia projects, AHP housing, modern markets, ESP sites, institutional housing, county project tracker';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = trim((string)($cmsPage['canonical_url'] ?? '')) ?: Url::canonical('projects.php');
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
$isActiveFilter = static fn (string $current, string $value): string => $current === $value ? ' is-active' : '';
$isSelected = static fn (string $current, string $value): string => $current === $value ? ' selected' : '';
$outputLabel = static function (array $project): string {
    $slug = strtolower((string)($project['category_slug'] ?? ''));
    $name = strtolower((string)($project['category_name'] ?? ''));
    if ($slug === 'modern-market' || strpos($name, 'market') !== false) {
        return 'Stalls';
    }
    if ($slug === 'esps' || strpos($name, 'esp') !== false) {
        return 'Site';
    }
    return 'Units';
};
$deliveryLabel = static function (mixed $date): string {
    $date = trim((string)$date);
    if ($date === '' || $date === '0000-00-00') {
        return 'TBD';
    }
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return $date;
    }
    return 'Q' . (int)ceil((int)date('n', $timestamp) / 3) . ' ' . date('Y', $timestamp);
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
          <img <?= public_image_attrs($heroImage, $text($hero, 'background_alt', ''), ['loading' => 'eager', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
        <?php endif; ?>
        <div class="page-hero-overlay"></div>
      </div>
      <div class="container">
<div class="page-hero-body">
          <div class="page-hero-eyebrow">
            <span class="page-hero-tag"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> <?= $e($text($hero, 'eyebrow', 'County Project Delivery')) ?></span>
          </div>
          <h1 class="page-hero-title"><?= $e($text($hero, 'title_plain', 'Project Sites')) ?><br><span class="text-lime"><?= $e($text($hero, 'title_highlight', 'Directory')) ?></span></h1>
          <p class="page-hero-sub"><?= $e($text($hero, 'subtitle', 'Track AHP housing, modern markets, ESP sites, and institutional housing across Trans-Nzoia County - progress, contractors, timelines and outputs in real time.')) ?></p>
        </div>
        <div class="projects-hero-stats" role="region" aria-label="Programme statistics">
          <div class="phs-item">
            <span class="phs-val" id="statTotalProjects"><?= $e(format_number($totalProjects)) ?></span>
            <span class="phs-lbl"><?= $e($text($hero, 'total_projects_label', 'Total Projects')) ?></span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statTotalUnits"><?= $e(format_number($totalUnits)) ?></span>
            <span class="phs-lbl"><?= $e($text($hero, 'units_label', 'Tracked Outputs')) ?></span>
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

    <section class="projects-coverage-section" aria-label="Project coverage summary">
      <div class="container">
        <div class="projects-coverage-intro">
          <div>
            <span class="pcv-section-badge"><i class="ph ph-map-trifold"></i> County Coverage</span>
            <h2 class="pcv-title">Projects Across Trans-Nzoia</h2>
            <p class="pcv-subtitle">Browse tracked project sites by constituency, ward coverage, progress, and planned output.</p>
          </div>
          <a href="constituencies.php" class="pcv-link pcv-link--intro">View constituencies <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="projects-coverage-grid">
          <div class="projects-coverage-map">
            <div class="pcv-panel-label">Interactive county map</div>
            <div class="pcv-map-wrap" aria-label="Trans-Nzoia constituencies map">
              <svg viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Public project coverage map">
<?php foreach (Constituency::mapShapes() as $slug => $shape): ?>
<?php
    $coverageItem = null;
    foreach ($coverageConstituencies as $candidate) {
        if ((string)$candidate['slug'] === $slug) {
            $coverageItem = $candidate;
            break;
        }
    }
    $coverageName = $coverageItem['name'] ?? ucwords($slug);
    $coverageCount = (int)($coverageItem['projectCount'] ?? 0);
?>
                <g class="pcv-map-region<?= $coverageCount > 0 ? ' has-projects' : '' ?>">
                  <path d="<?= $e($shape['path']) ?>"></path>
                  <text x="<?= (int)$shape['label_x'] ?>" y="<?= (int)$shape['label_y'] ?>"><?= $e($coverageName) ?></text>
                  <text class="pcv-map-sub" x="<?= (int)$shape['label_x'] ?>" y="<?= (int)$shape['sub_y'] ?>"><?= $e($coverageCount . ' ' . ($coverageCount === 1 ? 'project' : 'projects')) ?></text>
                </g>
<?php endforeach; ?>
              </svg>
            </div>
          </div>
          <div class="projects-coverage-list">
            <div class="pcv-list-head">
              <div>
                <div class="pcv-panel-label">Constituency Snapshot</div>
                <h2 class="pcv-title pcv-title--compact">Where Work Is Happening</h2>
              </div>

            </div>
            <div class="pcv-constituency-cards">
<?php foreach ($coverageConstituencies as $item): ?>
              <?php $constituencySlug = (string)$item['slug']; ?>
              <a href="constituency-detail.php?id=<?= $e(rawurlencode($constituencySlug)) ?>" class="pcv-card<?= empty($item['projectCount']) ? ' pcv-card--empty' : '' ?>">
                <div class="pcv-card-body">
                  <h3><?= $e($item['name']) ?></h3>
                  <div class="pcv-ward-chips" aria-label="<?= $e($item['name']) ?> wards">
<?php foreach (($item['wards'] ?? []) as $ward): ?>
                    <span><?= $e($ward) ?></span>
<?php endforeach; ?>
                  </div>
                  <?php if (empty($item['projectCount'])): ?>
                    <p class="pcv-empty-badge"><i class="fa-solid fa-clock-rotate-left"></i> Awaiting projects</p>
                  <?php endif; ?>
                </div>
                <div class="pcv-card-stats">
<?php if (empty($item['projectCount'])): ?>
                  <span class="pcv-empty-badge"><i class="fa-solid fa-clock-rotate-left"></i> Awaiting projects</span>
<?php else: ?>
                  <span><strong><?= $e((string)$item['projectCount']) ?></strong> projects</span>
                  <span><strong><?= $e(format_number((int)$item['totalUnits'])) ?></strong> outputs</span>
                  <span><strong><?= $e((string)$item['avgCompletion']) ?>%</strong> progress</span>
<?php endif; ?>
                </div>
              </a>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="projects-main" aria-label="Project listings">
      <div class="container">
        <div class="projects-filter-bar" role="search" aria-label="Filter projects">
          <!-- Row 1: Search + Status + Constituency -->
          <div class="pfb-row-primary">
            <div class="pfb-search-wrap">
              <i class="fa-solid fa-magnifying-glass pfb-search-icon" aria-hidden="true"></i>
              <input type="search" class="pfb-search-input" id="projectSearch" value="<?= $e($initialFilters['q']) ?>" placeholder="<?= $e($text($filters, 'search_placeholder', 'Search projects...')) ?>" aria-label="Search projects by name or location">
            </div>
            <span class="pfb-divider" aria-hidden="true"></span>
            <div class="pfb-filters" role="group" aria-label="Filter by status">
              <span class="pfb-label"><?= $e($text($filters, 'status_label', 'Status:')) ?></span>
              <button class="pfb-chip<?= $initialFilters['status'] === '' ? ' is-active' : '' ?>" type="button" data-filter-status="all"><?= $e($text($filters, 'all_label', 'All')) ?></button>
              <button class="pfb-chip<?= $isActiveFilter($initialFilters['status'], 'active') ?>" type="button" data-filter-status="active">
                <span class="pfb-chip-dot pfb-chip-dot--active" aria-hidden="true"></span><?= $e($text($filters, 'active_label', 'Active')) ?>
              </button>
              <button class="pfb-chip<?= $isActiveFilter($initialFilters['status'], 'planning') ?>" type="button" data-filter-status="planning">
                <span class="pfb-chip-dot pfb-chip-dot--planning" aria-hidden="true"></span><?= $e($text($filters, 'planning_label', 'Planning')) ?>
              </button>
            </div>
            <span class="pfb-divider" aria-hidden="true"></span>
            <div class="pfb-filters" role="group" aria-label="Filter by constituency">
              <span class="pfb-label"><?= $e($text($filters, 'constituency_label', 'Constituency:')) ?></span>
              <button class="pfb-chip<?= $initialFilters['constituency'] === '' ? ' is-active' : '' ?>" type="button" data-filter-con="all"><?= $e($text($filters, 'all_label', 'All')) ?></button>
<?php foreach ($constituencies as $constituency): ?>
              <button class="pfb-chip<?= $isActiveFilter($initialFilters['constituency'], (string)$constituency['slug']) ?>" type="button" data-filter-con="<?= $e($constituency['slug']) ?>"><?= $e($constituency['name']) ?></button>
<?php endforeach; ?>
            </div>
          </div>
          <!-- Row 2: Type + Sort -->
          <div class="pfb-row-secondary">
<?php if ($categories): ?>
            <div class="pfb-filters" role="group" aria-label="Filter by project type">
              <span class="pfb-label"><?= $e($text($filters, 'category_label', 'Type:')) ?></span>
              <button class="pfb-chip<?= $initialFilters['category'] === '' ? ' is-active' : '' ?>" type="button" data-filter-category="all"><?= $e($text($filters, 'all_label', 'All')) ?></button>
<?php foreach ($categories as $category): ?>
              <button class="pfb-chip<?= $isActiveFilter($initialFilters['category'], (string)$category['slug']) ?>" type="button" data-filter-category="<?= $e($category['slug']) ?>"><?= $e($category['name']) ?></button>
<?php endforeach; ?>
            </div>
<?php endif; ?>
            <div class="pfb-sort-wrap">
              <label for="projectSort" class="pfb-label"><?= $e($text($filters, 'sort_label', 'Sort:')) ?></label>
              <select id="projectSort" class="pfb-sort-select" aria-label="Sort projects">
                <option value="pct-desc"<?= $isSelected($initialFilters['sort'], 'pct-desc') ?>><?= $e($text($filters, 'sort_completion_desc', 'Progress high to low')) ?></option>
                <option value="pct-asc"<?= $isSelected($initialFilters['sort'], 'pct-asc') ?>><?= $e($text($filters, 'sort_completion_asc', 'Progress low to high')) ?></option>
                <option value="units-desc"<?= $isSelected($initialFilters['sort'], 'units-desc') ?>><?= $e($text($filters, 'sort_units_desc', 'Most outputs')) ?></option>
                <option value="name-asc"<?= $isSelected($initialFilters['sort'], 'name-asc') ?>><?= $e($text($filters, 'sort_name_asc', 'Name A–Z')) ?></option>
              </select>
            </div>
          </div>
        </div>

        <div class="projects-results-header">
          <div class="projects-results-meta" aria-live="polite" aria-atomic="true">
            <span id="resultsCount"><?= $e($text($listing, 'results_prefix', 'Showing')) ?> <?= $e(format_number($totalProjects)) ?> <?= $e($totalProjects === 1 ? $text($listing, 'project_single', 'project') : $text($listing, 'project_plural', 'projects')) ?></span>
          </div>
          <button class="pfb-reset" type="button" id="resetFilters"<?= $hasInitialFilters ? '' : ' hidden' ?>>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> <?= $e($text($filters, 'reset_label', 'Clear filters')) ?>
          </button>
        </div>

        <div class="projects-grid"
             id="projectsGrid"
             role="list"
             data-api-url="<?= $e(public_url('api/public/projects.php')) ?>"
             data-results-prefix="<?= $e($text($listing, 'results_prefix', 'Showing')) ?>"
             data-project-single="<?= $e($text($listing, 'project_single', 'project')) ?>"
             data-project-plural="<?= $e($text($listing, 'project_plural', 'projects')) ?>"
             data-units-label="<?= $e($text($listing, 'units_label', 'Units')) ?>"
             data-complete-label="<?= $e($text($listing, 'complete_label', 'Complete')) ?>"
             data-view-label="<?= $e($text($listing, 'view_label', 'View Project')) ?>"
             data-pagination-showing="<?= $e($text($listing, 'pagination_showing_label', 'Showing')) ?>"
             data-pagination-of="<?= $e($text($listing, 'pagination_of_label', 'of')) ?>">
<?php foreach ($projects as $index => $project): ?>
          <?php
            $status = (string)($project['status'] ?? 'planning');
            $displayStatus = $publicStatus($status);
            $pct = max(0, min(100, (int)($project['pct_complete'] ?? 0)));
            $image = $asset((string)($project['hero_image'] ?? ''));
            $name = (string)($project['name'] ?? 'Project');
            $categoryName = (string)($project['category_name'] ?? 'Project');
            $delivery = $deliveryLabel($project['est_delivery'] ?? null);
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
                   data-category="<?= $e($project['category_slug'] ?? '') ?>"
                   data-name="<?= $e(strtolower($name)) ?>"
                   data-units="<?= (int)($project['units'] ?? 0) ?>"
                   data-pct="<?= $pct ?>"
                   data-search="<?= $e($search) ?>">
            <div class="proj-card-img">
<?php if ($image !== ''): ?>
              <img <?= public_image_attrs($image, $name, ['onerror' => "this.style.display='none';this.nextElementSibling.style.display='flex'"]) ?>>
              <div class="proj-card-img-placeholder" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
<?php else: ?>
              <div class="proj-card-img-placeholder"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
<?php endif; ?>
              <span class="proj-status-badge proj-status-badge--<?= $e($displayStatus) ?>" aria-label="Status: <?= $e($statusLabel($status)) ?>"><?= $e($statusLabel($status)) ?></span>
              <span class="proj-con-tag"><?= $e($project['constituency_name'] ?? '') ?></span>
              <span class="proj-category-tag"><?= $e($categoryName) ?></span>
            </div>
            <div class="proj-card-body">
              <div class="proj-card-meta">
                <span class="proj-category"><?= $e($categoryName) ?></span>
                <span class="proj-ward"><?= $e($project['ward_name'] ?? $project['location_label'] ?? '') ?></span>
              </div>
              <h3 class="proj-card-title"><?= $e($name) ?></h3>
              <div class="proj-card-stats">
                <div class="proj-stat">
                  <span class="proj-stat-val"><?= $e(format_number((int)($project['units'] ?? 0))) ?></span>
                  <span class="proj-stat-lbl"><?= $e($outputLabel($project)) ?></span>
                </div>
                <div class="proj-stat">
                  <span class="proj-stat-val"><?= $pct ?>%</span>
                  <span class="proj-stat-lbl"><?= $e($text($listing, 'complete_label', 'Progress')) ?></span>
                </div>
                <div class="proj-stat proj-stat--delivery">
                  <span class="proj-stat-val proj-stat-val--date"><?= $e($delivery) ?></span>
                  <span class="proj-stat-lbl">Est. Delivery</span>
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
<?= public_json_script('ahp-page-data', $publicPayload) ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
