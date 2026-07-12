<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'home';
$homePage = CmsLoader::page('home');
$projectStats = Project::publicStats();
$featuredProjects = Project::featured();
if (!$featuredProjects) {
    $featuredProjects = Project::publicListing([], 3);
}
$constituencies = Constituency::withProjectCounts();
$publicStories = home_public_story_items(9);
$featuredStories = home_featured_story_items(3);
$displayStories = $featuredStories ?: array_slice($publicStories, 0, 3);
$latestAlerts = home_latest_alert_items(6);
$mapProjects = Project::publicListing(['sort' => 'updated-desc']);

$hero = CmsLoader::content($homePage, 'home_hero');
$featured = CmsLoader::content($homePage, 'featured_projects');
$coverage = CmsLoader::content($homePage, 'constituency_coverage');
$reports = CmsLoader::content($homePage, 'ground_reports');
$cta = CmsLoader::content($homePage, 'ecitizen_cta');

$pageTitle = (string)($homePage['seo_title'] ?? 'Trans-Nzoia County | Project Delivery Tracker');
$pageDescription = (string)($homePage['seo_description'] ?? 'Trans-Nzoia County project delivery tracker for AHP housing, modern markets, ESP sites, and institutional housing.');
$pageKeywords = (string)($homePage['seo_keywords'] ?? 'Trans-Nzoia projects, affordable housing, modern markets, ESP Kenya, institutional housing');
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = trim((string)($homePage['canonical_url'] ?? '')) ?: Url::canonical();
$heroImage = CmsLoader::text($hero, 'background_image', (string)($homePage['hero_image'] ?? 'uploads/heroes/hero-main.jpg'));
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/index.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/index.js',
];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
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
<?= public_page_json('home', [
    'map_projects' => home_map_project_payload($mapProjects),
    'constituencies' => home_constituency_payload($constituencies),
], [], 'ahp-page-data') ?>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

<?php if (CmsLoader::visible($homePage, 'home_hero')): ?>
  <section class="tracker-hero" aria-label="Programme overview">
    <div class="tracker-hero-bg">
      <img <?= public_image_attrs($heroImage, CmsLoader::text($hero, 'background_alt', 'Affordable housing construction site in Trans-Nzoia County, Kenya'), ['loading' => 'eager', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
      <div class="tracker-hero-overlay" aria-hidden="true"></div>
    </div>
    <div class="container tracker-hero-content">
      <div class="tracker-hero-inner">
        <div class="programme-badge" role="status">
          <span class="programme-badge-dot" aria-hidden="true"></span>
          <?= Security::e(CmsLoader::text($hero, 'eyebrow', 'County Delivery Tracker - Trans-Nzoia')) ?>
        </div>
        <h1 class="tracker-hero-title"><?= nl2br(Security::e(CmsLoader::text($hero, 'title', "Trans-Nzoia\nProject Delivery Tracker")), false) ?></h1>
        <p class="tracker-hero-subtitle"><?= Security::e(CmsLoader::text($hero, 'subtitle', 'Transparent monitoring of AHP housing, modern markets, ESP sites, and institutional housing across all five constituencies.')) ?></p>
        <div class="tracker-hero-cta">
          <a href="<?= Security::e(CmsLoader::text($hero, 'primary_url', 'projects.php')) ?>" class="btn btn-lg btn-white">
            <i class="fa-solid <?= Security::e(CmsLoader::text($hero, 'primary_icon', 'fa-table-cells-large')) ?>" aria-hidden="true"></i>
            <?= Security::e(CmsLoader::text($hero, 'primary_label', 'View All Projects')) ?>
          </a>
          <a href="<?= Security::e(CmsLoader::text($hero, 'secondary_url', 'constituencies.php')) ?>" class="btn btn-lg btn-outline-white">
            <i class="fa-solid <?= Security::e(CmsLoader::text($hero, 'secondary_icon', 'fa-map-location-dot')) ?>" aria-hidden="true"></i>
            <?= Security::e(CmsLoader::text($hero, 'secondary_label', 'By Constituency')) ?>
          </a>
        </div>
      </div>

<?php if (CmsLoader::bool($hero, 'show_kpis', true)): ?>
      <div class="hero-kpi-strip" role="region" aria-label="Programme key metrics">
        <?php home_kpi('Active Projects', (int)($projectStats['active_projects'] ?? 0)); ?>
        <div class="hero-kpi-divider" aria-hidden="true"></div>
        <?php home_kpi('Tracked Outputs', (int)($projectStats['total_units'] ?? 0), '+'); ?>
        <div class="hero-kpi-divider" aria-hidden="true"></div>
        <?php home_kpi('Constituencies Covered', count($constituencies)); ?>
        <div class="hero-kpi-divider" aria-hidden="true"></div>
        <?php home_kpi('Avg. Progress', (int)round((float)($projectStats['avg_completion'] ?? $projectStats['average_completion'] ?? 0)), '%'); ?>
      </div>
<?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if (CmsLoader::visible($homePage, 'featured_projects')): ?>
  <section class="section projects-section" id="featured-projects" aria-labelledby="projects-heading">
    <div class="container">
      <?php home_section_header('projects-heading', $featured, 'Featured Project Sites', 'Track housing, market, ESP, and institutional projects across Trans-Nzoia County.', 'Full Portfolio', 'projects.php'); ?>
      <div class="project-cards-grid stagger-children">
<?php foreach (array_slice($featuredProjects, 0, max(1, (int)($featured['display_count'] ?? 3))) as $project): ?>
        <?php home_project_card($project); ?>
<?php endforeach; ?>
<?php if (!$featuredProjects): ?>
        <div class="empty-state">
          <strong><?= Security::e(CmsLoader::text($featured, 'empty_title', 'Featured projects will appear here')) ?></strong>
          <p><?= Security::e(CmsLoader::text($featured, 'empty_text', 'Mark projects as featured in the project editor to populate this section.')) ?></p>
        </div>
<?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if (CmsLoader::visible($homePage, 'constituency_coverage')): ?>
  <section class="section map-section" aria-labelledby="map-heading">
    <div class="container">
      <?php home_section_header('map-heading', $coverage, 'Coverage by Constituency', 'All five constituencies are tracked for county delivery progress.', 'All Constituencies', 'constituencies.php'); ?>
      <div class="map-interactive-wrap">
        <div class="map-canvas-wrap">
          <svg viewBox="0 0 480 400" class="county-map" role="img" aria-label="Interactive map of Trans-Nzoia County showing five constituencies">
            <title>Trans-Nzoia County Constituency Map</title>
            <defs><filter id="mapGlow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>
<?php foreach (home_map_shapes() as $slug => $shape): ?>
            <?php $c = home_constituency($constituencies, $slug); $projects = (int)($c['live_project_count'] ?? 0); $pct = (int)round((float)($c['live_avg_completion'] ?? 0)); ?>
            <g class="map-constituency" data-id="<?= Security::e($slug) ?>" data-name="<?= Security::e($c['name'] ?? $shape['name']) ?>" data-projects="<?= $projects ?>" data-status="<?= Security::e($pct > 0 ? 'active' : 'planning') ?>" data-units="<?= (int)($c['live_total_units'] ?? 0) ?>" data-pct="<?= $pct ?>" data-link="constituency-detail.php?id=<?= Security::e($slug) ?>" tabindex="0" role="button" aria-label="<?= Security::e(($c['name'] ?? $shape['name']) . ' - ' . $projects . ' projects') ?>">
              <path d="<?= Security::e($shape['path']) ?>" class="map-path"/>
              <text class="map-label" x="<?= Security::e($shape['x']) ?>" y="<?= Security::e($shape['y']) ?>"><?= Security::e($c['name'] ?? $shape['name']) ?></text>
            </g>
<?php endforeach; ?>
          </svg>
          <div class="map-compass" aria-hidden="true"><i class="fa-solid fa-location-crosshairs"></i><span>N</span></div>
          <div class="map-legend" aria-hidden="true">
            <span class="map-legend-item"><span class="map-legend-dot map-legend-active"></span><?= Security::e(CmsLoader::text($coverage, 'legend_active', 'Active')) ?></span>
            <span class="map-legend-item"><span class="map-legend-dot map-legend-plan"></span><?= Security::e(CmsLoader::text($coverage, 'legend_planning', 'Planning')) ?></span>
          </div>
        </div>
        <aside class="map-side-panel" id="mapPanel" aria-live="polite">
          <div class="map-panel-default" id="mapPanelDefault">
            <i class="fa-solid fa-map-location-dot map-panel-icon" aria-hidden="true"></i>
            <p class="map-panel-hint"><?= Security::e(CmsLoader::text($coverage, 'panel_hint', 'Click a constituency on the map to see project data')) ?></p>
            <div class="map-panel-overview">
              <div class="map-ov-item"><span class="map-ov-val"><?= Security::e(format_number(count($constituencies))) ?></span><span class="map-ov-lbl">Constituencies</span></div>
              <div class="map-ov-item"><span class="map-ov-val"><?= Security::e(format_number((int)($projectStats['active_projects'] ?? 0))) ?></span><span class="map-ov-lbl">Active Projects</span></div>
              <div class="map-ov-item"><span class="map-ov-val"><?= Security::e(format_number((int)($projectStats['total_units'] ?? 0))) ?>+</span><span class="map-ov-lbl">Tracked Outputs</span></div>
            </div>
          </div>
          <div class="map-panel-detail" id="mapPanelDetail"></div>
        </aside>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if (CmsLoader::visible($homePage, 'ground_reports')): ?>
  <section class="section news-section" aria-labelledby="news-heading">
    <div class="container">
      <?php home_section_header('news-heading', $reports, 'From the Ground', 'Project milestones, tender notices, and official clearances.', 'All Reports', 'news.php'); ?>
      <div class="news-asymmetric">
<?php if ($displayStories || $latestAlerts): ?>
<?php if ($displayStories): ?>
        <?php home_news_card($displayStories[0], true); ?>
        <div class="news-stack">
<?php foreach (array_slice($displayStories, 1, max(0, (int)($reports['compact_count'] ?? 2))) as $item): ?>
          <?php home_news_card($item, false); ?>
<?php endforeach; ?>
        </div>
<?php endif; ?>
        <aside class="news-alerts-sidebar" aria-label="Latest programme alerts">
          <div class="nas-header"><i class="fa-solid fa-bolt nas-icon" aria-hidden="true"></i><span class="nas-title"><?= Security::e(CmsLoader::text($reports, 'alerts_title', 'Latest Alerts')) ?></span></div>
          <ul class="nas-list">
<?php foreach (array_slice($latestAlerts, 0, max(1, (int)($reports['alert_count'] ?? 6))) as $index => $item): ?>
            <li class="nas-item <?= $index === 0 ? 'nas-item--urgent' : '' ?>">
              <span class="nas-dot" aria-hidden="true"></span>
              <div class="nas-content">
                <span class="nas-tag"><?= Security::e($item['category_name'] ?? 'Report') ?></span>
                <a href="news-article.php?id=<?= Security::e($item['slug']) ?>" class="nas-headline"><?= Security::e($item['title']) ?></a>
                <time class="nas-time" datetime="<?= Security::e(substr((string)$item['published_at'], 0, 10)) ?>"><?= Security::e(format_date($item['published_at'] ?? null, 'd M Y')) ?></time>
              </div>
            </li>
<?php endforeach; ?>
          </ul>
        </aside>
<?php else: ?>
        <div class="empty-state">
          <strong><?= Security::e(CmsLoader::text($reports, 'empty_title', 'Reports will appear here')) ?></strong>
          <p><?= Security::e(CmsLoader::text($reports, 'empty_text', 'Published news and alerts will populate this live section.')) ?></p>
        </div>
<?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if (CmsLoader::visible($homePage, 'ecitizen_cta')): ?>
  <section class="ecitizen-cta" aria-labelledby="ecitizen-heading">
    <div class="ecitizen-cta-bg" aria-hidden="true">
      <img <?= public_image_attrs(CmsLoader::text($cta, 'background_image', 'uploads/site-photos/maili-tatu-2.jpg'), '', ['onerror' => "this.style.display='none'"]) ?>>
      <div class="ecitizen-cta-overlay"></div>
    </div>
    <div class="container">
      <div class="ecitizen-inner fade-up">
        <div class="ecitizen-left">
          <div class="ecitizen-eyebrow">
            <img <?= public_image_attrs(CmsLoader::text($cta, 'logo_image', 'uploads/logos/BomaYanguLogo.png'), 'Boma Yangu', ['onerror' => "this.style.display='none'"]) ?>>
            <span><?= Security::e(CmsLoader::text($cta, 'eyebrow', 'Boma Yangu - eCitizen')) ?></span>
          </div>
          <h2 id="ecitizen-heading" class="ecitizen-headline"><?= nl2br(Security::e(CmsLoader::text($cta, 'title', "Your Home.\nApplied Online.")), false) ?></h2>
          <p class="ecitizen-sub"><?= Security::e(CmsLoader::text($cta, 'subtitle', 'Register on the national Boma Yangu portal via eCitizen to apply for affordable housing units.')) ?></p>
          <div class="ecitizen-actions">
            <a href="<?= Security::e(CmsLoader::text($cta, 'primary_url', 'https://ecitizen.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="ecitizen-btn-main">
              <?= Security::e(CmsLoader::text($cta, 'primary_label', 'Apply for Housing')) ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
            <a href="<?= Security::e(CmsLoader::text($cta, 'secondary_url', 'about.php')) ?>" class="ecitizen-btn-ghost">
              <?= Security::e(CmsLoader::text($cta, 'secondary_label', 'About the programme')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
        <div class="ecitizen-steps" aria-label="How to apply via eCitizen">
          <h3 class="ecitizen-steps-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> How to Apply</h3>
          <div class="ecitizen-steps-grid">
<?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="ecitizen-step-card">
              <div class="ecitizen-step-icon-wrap">
                <i class="fa-solid <?= Security::e(CmsLoader::text($cta, "step_{$i}_icon", 'fa-circle-check')) ?>" aria-hidden="true"></i>
                <span class="ecitizen-step-num"><?= str_pad((string)$i, 2, '0', STR_PAD_LEFT) ?></span>
              </div>
              <strong><?= Security::e(CmsLoader::text($cta, "step_{$i}_title", 'Application step')) ?></strong>
              <p><?= Security::e(CmsLoader::text($cta, "step_{$i}_body", 'Complete this step to continue.')) ?></p>
            </div>
<?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

</main>

<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>

<?php
function home_kpi(string $label, int $value, string $suffix = ''): void
{
    ?>
    <div class="hero-kpi-item">
      <span class="hero-kpi-number" data-count="<?= $value ?>" data-suffix="<?= Security::e($suffix) ?>" aria-label="<?= Security::e(format_number($value) . $suffix . ' ' . $label) ?>">0</span>
      <span class="hero-kpi-label"><?= Security::e($label) ?></span>
    </div>
    <?php
}

function home_section_header(string $id, array $content, string $fallbackTitle, string $fallbackSubtitle, string $fallbackButton, string $fallbackUrl): void
{
    ?>
    <div class="split-section-header fade-up">
      <div class="split-section-left">
        <span class="accent-line"></span>
        <h2 id="<?= Security::e($id) ?>" class="section-title"><?= Security::e(CmsLoader::text($content, 'title', $fallbackTitle)) ?></h2>
        <p class="section-subtitle" style="margin-bottom:0"><?= Security::e(CmsLoader::text($content, 'subtitle', $fallbackSubtitle)) ?></p>
      </div>
      <div class="split-section-right">
        <a href="<?= Security::e(CmsLoader::text($content, 'button_url', $fallbackUrl)) ?>" class="btn btn-outline">
          <?= Security::e(CmsLoader::text($content, 'button_label', $fallbackButton)) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
      </div>
    </div>
    <?php
}

function home_project_card(array $project): void
{
    $image = home_project_image($project);
    $status = status_label($project['status'] ?? 'planning');
    $pct = (int)($project['pct_complete'] ?? 0);
    $outputLabel = home_project_output_label($project);
    ?>
    <article class="ptc">
      <div class="ptc-image">
        <img <?= public_image_attrs($image, ($project['name'] ?? 'Project') . ' construction site', ['onerror' => "this.parentElement.classList.add('ptc-img-fallback')"]) ?>>
        <div class="ptc-image-overlay" aria-hidden="true"></div>
        <div class="ptc-badges">
          <span class="badge <?= ($project['status'] ?? '') === 'active' ? 'badge-ongoing' : 'badge-warning' ?>"><?= Security::e($status) ?></span>
          <span class="ptc-category-tag"><?= Security::e($project['category_name'] ?? 'AHP') ?></span>
        </div>
      </div>
      <div class="ptc-body">
        <div class="ptc-location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= Security::e(($project['constituency_name'] ?? 'Trans-Nzoia') . ' Constituency - ' . ($project['ward_name'] ?? $project['location_label'] ?? '')) ?></div>
        <h3 class="ptc-title"><?= Security::e($project['name'] ?? 'Project') ?></h3>
        <div class="ptc-stats-row">
          <div class="ptc-stat"><span class="ptc-stat-val"><?= Security::e(format_number((int)($project['units'] ?? 0))) ?></span><span class="ptc-stat-lbl"><?= Security::e($outputLabel) ?></span></div>
          <div class="ptc-stat-sep" aria-hidden="true"></div>
          <div class="ptc-stat"><span class="ptc-stat-val ptc-stat-pct"><?= $pct ?>%</span><span class="ptc-stat-lbl">Complete</span></div>
          <div class="ptc-stat-sep" aria-hidden="true"></div>
          <div class="ptc-stat"><span class="ptc-stat-val"><?= Security::e(home_delivery_year($project['est_delivery'] ?? null)) ?></span><span class="ptc-stat-lbl">Est. Delivery</span></div>
        </div>
        <div class="ptc-progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Construction <?= $pct ?>% complete">
          <div class="ptc-progress-track"><div class="ptc-progress-fill" data-progress="<?= $pct ?>"></div></div>
          <span class="ptc-progress-label"><?= $pct ?>% Completed</span>
        </div>
        <div class="ptc-actions">
          <a href="project-detail.php?id=<?= Security::e($project['slug'] ?? '') ?>" class="btn btn-primary btn-sm">View Details</a>
          <?php if (home_project_is_housing($project)): ?>
          <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Apply on Boma Yangu <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          <?php else: ?>
          <a href="projects.php?category=<?= Security::e($project['category_slug'] ?? '') ?>" class="btn btn-outline btn-sm">View Category <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php
}

function home_project_output_label(array $project): string
{
    $slug = strtolower((string)($project['category_slug'] ?? ''));
    $name = strtolower((string)($project['category_name'] ?? ''));

    if ($slug === 'modern-market' || strpos($name, 'market') !== false) {
        return 'Stalls';
    }

    if ($slug === 'esps' || strpos($name, 'esp') !== false) {
        return 'Site';
    }

    return 'Units';
}

function home_project_is_housing(array $project): bool
{
    $slug = strtolower((string)($project['category_slug'] ?? ''));
    $name = strtolower((string)($project['category_name'] ?? ''));

    return $slug === 'ahps'
        || $slug === 'institutional-housing'
        || strpos($name, 'housing') !== false
        || strpos($name, 'ahp') !== false;
}
function home_project_image(array $project): string
{
    if (!empty($project['hero_image'])) {
        return (string)$project['hero_image'];
    }

    $images = json_decode((string)($project['images_json'] ?? ''), true);
    return is_array($images) && !empty($images[0]) ? (string)$images[0] : 'uploads/heroes/hero-main.jpg';
}

function home_delivery_year(mixed $date): string
{
    if (!$date) {
        return 'TBD';
    }

    $timestamp = strtotime((string)$date);
    return $timestamp ? date('Y', $timestamp) : (string)$date;
}

function home_constituency(array $rows, string $slug): array
{
    foreach ($rows as $row) {
        if (($row['slug'] ?? '') === $slug) {
            return $row;
        }
    }

    return [];
}

function home_map_shapes(): array
{
    return [
        'endebess' => ['name' => 'Endebess', 'path' => 'M 20,20 L 220,20 L 220,180 L 120,200 L 20,160 Z', 'x' => '112', 'y' => '98'],
        'cherangany' => ['name' => 'Cherangany', 'path' => 'M 220,20 L 460,20 L 460,230 L 260,230 L 220,180 Z', 'x' => '348', 'y' => '112'],
        'kiminini' => ['name' => 'Kiminini', 'path' => 'M 20,160 L 120,200 L 120,380 L 20,380 Z', 'x' => '62', 'y' => '290'],
        'saboti' => ['name' => 'Saboti', 'path' => 'M 120,200 L 220,180 L 260,230 L 260,380 L 120,380 Z', 'x' => '186', 'y' => '305'],
        'kwanza' => ['name' => 'Kwanza', 'path' => 'M 260,230 L 460,230 L 460,380 L 260,380 Z', 'x' => '362', 'y' => '310'],
    ];
}

function home_public_story_items(int $limit): array
{
    try {
        return Database::fetchAll("
            SELECT na.*, nc.name AS category_name, nc.slug AS category_slug, ml.path AS image_path
            FROM news_articles na
            LEFT JOIN news_categories nc ON nc.id = na.category_id
            LEFT JOIN media_library ml ON ml.id = na.featured_image_id
            WHERE na.status = 'published'
              AND COALESCE(na.is_visible, 1) = 1
              AND na.deleted_at IS NULL
              AND na.post_format <> 'announcement'
            ORDER BY COALESCE(na.published_at, na.created_at) DESC, na.id DESC
            LIMIT {$limit}
        ");
    } catch (Throwable $e) {
        return [];
    }
}

function home_featured_story_items(int $limit): array
{
    try {
        return Database::fetchAll("
            SELECT na.*, nc.name AS category_name, nc.slug AS category_slug, ml.path AS image_path
            FROM news_articles na
            LEFT JOIN news_categories nc ON nc.id = na.category_id
            LEFT JOIN media_library ml ON ml.id = na.featured_image_id
            WHERE na.status = 'published'
              AND COALESCE(na.is_visible, 1) = 1
              AND COALESCE(na.is_featured, 0) = 1
              AND na.deleted_at IS NULL
              AND na.post_format <> 'announcement'
            ORDER BY COALESCE(na.published_at, na.created_at) DESC, na.id DESC
            LIMIT {$limit}
        ");
    } catch (Throwable $e) {
        return [];
    }
}

function home_latest_alert_items(int $limit): array
{
    return home_public_story_items($limit);
}

function home_constituency_payload(array $constituencies): array
{
    $payload = [];
    foreach ($constituencies as $row) {
        $slug = (string)($row['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $payload[$slug] = [
            'name' => (string)($row['name'] ?? ''),
            'projectCount' => (int)($row['live_project_count'] ?? 0),
            'totalUnits' => (int)($row['live_total_units'] ?? 0),
            'avgCompletion' => (int)round((float)($row['live_avg_completion'] ?? 0)),
            'link' => 'constituency-detail.php?id=' . rawurlencode($slug),
        ];
    }
    return $payload;
}

function home_map_project_payload(array $projects): array
{
    $grouped = [];
    foreach ($projects as $project) {
        $slug = (string)($project['constituency_slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $grouped[$slug][] = [
            'name' => (string)($project['name'] ?? ''),
            'ward' => (string)($project['ward_name'] ?: ($project['location_label'] ?? '')),
            'units' => (int)($project['units'] ?? 0),
            'pct' => (int)($project['pct_complete'] ?? 0),
            'status' => (string)($project['status'] ?? 'planning'),
            'statusLabel' => (string)($project['status_label'] ?? status_label($project['status'] ?? 'planning')),
            'contractor' => (string)($project['contractor_name'] ?: 'TBD'),
            'funding' => (string)($project['funding_source'] ?: 'TBD'),
            'leadAgency' => (string)($project['lead_agency'] ?: 'TBD'),
            'siteEngineer' => (string)($project['site_engineer'] ?: 'TBD'),
            'startDate' => home_project_date_label($project['start_date'] ?? null),
            'estDelivery' => home_project_quarter_label($project['est_delivery'] ?? null),
            'milestone' => (string)($project['current_milestone'] ?: 'Project update pending'),
            'link' => 'project-detail.php?id=' . rawurlencode((string)($project['slug'] ?? '')),
        ];
    }
    return $grouped;
}

function home_project_date_label(mixed $date): string
{
    $date = trim((string)$date);
    if ($date === '' || $date === '0000-00-00') {
        return 'TBD';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('M Y', $timestamp) : $date;
}

function home_project_quarter_label(mixed $date): string
{
    $date = trim((string)$date);
    if ($date === '' || $date === '0000-00-00') {
        return 'TBD';
    }
    $timestamp = strtotime($date);
    return $timestamp ? 'Q' . (int)ceil((int)date('n', $timestamp) / 3) . ' ' . date('Y', $timestamp) : $date;
}

function home_news_card(array $item, bool $feature): void
{
    $image = (string)($item['image_path'] ?? 'uploads/news/modern-market.jpg');
    $class = $feature ? 'news-card news-card--feature' : 'news-card news-card--compact';
    $articleUrl = 'news-article.php?id=' . rawurlencode((string)($item['slug'] ?? ''));
    ?>
    <article class="<?= $class ?>">
      <div class="news-card-img">
        <a href="<?= Security::e($articleUrl) ?>" aria-label="Read <?= Security::e($item['title'] ?? 'programme report') ?>">
          <img <?= public_image_attrs($image, $item['title'] ?? 'Programme report', ['onerror' => "this.parentElement.parentElement.classList.add('news-img-fallback')"]) ?>>
        </a>
        <span class="news-cat-badge news-cat-milestone"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i> <?= Security::e($item['category_name'] ?? 'Report') ?></span>
      </div>
      <div class="news-card-body">
        <time class="news-date" datetime="<?= Security::e(substr((string)($item['published_at'] ?? $item['created_at'] ?? ''), 0, 10)) ?>"><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= Security::e(format_date($item['published_at'] ?? $item['created_at'] ?? null, 'd M Y')) ?></time>
        <h3 class="news-card-title"><a href="<?= Security::e($articleUrl) ?>"><?= Security::e($item['title'] ?? 'Programme report') ?></a></h3>
        <p class="news-card-text <?= $feature ? 'line-clamp-4' : 'line-clamp-4' ?>"><?= Security::e(safe_truncate((string)($item['excerpt'] ?? ''), $feature ? 210 : 150)) ?></p>
      </div>
    </article>
    <?php
}
