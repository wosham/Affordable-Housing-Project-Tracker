<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'projects';
$cmsPage = CmsLoader::page('project-detail');
$labels = CmsLoader::content($cmsPage, 'project_detail_labels', []);
$overview = CmsLoader::content($cmsPage, 'project_detail_overview', []);
$sidebar = CmsLoader::content($cmsPage, 'project_detail_sidebar', []);
$applyCta = CmsLoader::content($cmsPage, 'project_detail_apply_cta', []);
$notFound = CmsLoader::content($cmsPage, 'project_detail_not_found', []);
$text = static fn (array $content, string $key, string $default = ''): string => CmsLoader::text($content, $key, $default);
$e = static fn (mixed $value): string => Security::e($value);

$lookup = trim((string)($_GET['id'] ?? $_GET['slug'] ?? ''));
$project = $lookup !== '' ? Project::findDetailed($lookup) : null;
$isFound = is_array($project);

if (!$isFound) {
    http_response_code(404);
}

function pd_asset_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    return Url::asset($path);
}

function pd_images(array $project): array
{
    $images = [];
    $hero = trim((string)($project['hero_image'] ?? ''));
    if ($hero !== '') {
        $images[] = $hero;
    }

    $decoded = json_decode((string)($project['images_json'] ?? '[]'), true);
    if (is_array($decoded)) {
        foreach ($decoded as $image) {
            $image = trim((string)$image);
            if ($image !== '') {
                $images[] = $image;
            }
        }
    }

    $unique = [];
    foreach ($images as $image) {
        if (!in_array($image, $unique, true)) {
            $unique[] = $image;
        }
    }

    return $unique;
}

function pd_gallery_media_image(array $item): string
{
    return (string)($item['thumbnail_path'] ?: $item['thumbnail_url'] ?: $item['media_path'] ?: $item['media_url'] ?: '');
}

function pd_quarter_label(?string $date, string $fallback = ''): string
{
    $date = trim((string)$date);
    if ($date === '' || $date === '0000-00-00') {
        return $fallback;
    }
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return $date;
    }
    return 'Q' . (int)ceil((int)date('n', $timestamp) / 3) . ' ' . date('Y', $timestamp);
}

function pd_date_label(?string $date, string $fallback = ''): string
{
    $date = trim((string)$date);
    if ($date === '' || $date === '0000-00-00') {
        return $fallback;
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('j M Y', $timestamp) : $date;
}

function pd_number(mixed $value): string
{
    return number_format((float)$value, 0);
}

function pd_status_class(string $status): string
{
    return match ($status) {
        'active' => 'active',
        'completed' => 'completed',
        'on_hold', 'stalled' => 'watch',
        default => 'planning',
    };
}

function pd_status_label(string $status, array $labels): string
{
    return match ($status) {
        'active' => CmsLoader::text($labels, 'active_status_label', 'Active'),
        'completed' => CmsLoader::text($labels, 'completed_status_label', 'Completed'),
        'on_hold', 'stalled' => CmsLoader::text($labels, 'watch_status_label', 'On Hold'),
        default => CmsLoader::text($labels, 'planning_status_label', 'Planning'),
    };
}

function pd_icon_for_status(string $status): string
{
    return match ($status) {
        'active' => 'fa-circle-dot',
        'completed' => 'fa-circle-check',
        'on_hold', 'stalled' => 'fa-triangle-exclamation',
        default => 'fa-clock',
    };
}

$images = $isFound ? pd_images($project) : [];
if ($isFound && !empty($project['id'])) {
    foreach (GalleryImage::projectMedia((int)$project['id'], 'image', 48) as $galleryItem) {
        $image = pd_gallery_media_image($galleryItem);
        if ($image !== '' && !in_array($image, $images, true)) {
            $images[] = $image;
        }
    }
}
$heroImage = $images[0] ?? '';
$projectName = $isFound ? (string)($project['name'] ?? 'Project Detail') : $text($notFound, 'title', 'Project Not Found');
$projectDescription = $isFound
    ? trim((string)($project['description'] ?? ''))
    : $text($notFound, 'text', "The project you're looking for doesn't exist or the URL is incorrect.");
$pageTitle = $isFound
    ? $projectName . ' | Trans-Nzoia County Affordable Housing Tracker'
    : $text($notFound, 'title', 'Project Not Found') . ' | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = $isFound && $projectDescription !== ''
    ? substr(strip_tags($projectDescription), 0, 160)
    : ($cmsPage['seo_description'] ?? 'Detailed construction progress, contractor information, milestones and site photos for an affordable housing project in Trans-Nzoia County.');
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia AHP project detail, affordable housing Kenya, construction progress';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = $isFound ? 'index, follow' : 'noindex, follow';
$themeColor = '#163300';
$canonicalUrl = $isFound
    ? 'https://housing.transnzoia.go.ke/project-detail.php?id=' . rawurlencode((string)($project['slug'] ?? $lookup))
    : 'https://housing.transnzoia.go.ke/project-detail.php';
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/project-detail.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/project-detail.js',
];
$headMeta = [
    '<meta property="og:title" content="' . $e($pageTitle) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
];
if ($heroImage !== '') {
    $headMeta[] = '<meta property="og:image" content="' . $e(pd_asset_url($heroImage)) . '">';
}

$milestones = [];
$relatedProjects = [];
if ($isFound) {
    $milestones = Database::fetchAll(
        'SELECT * FROM milestones WHERE project_id = ? ORDER BY sequence ASC, id ASC',
        [(int)$project['id']]
    );
    if (!empty($project['constituency_id'])) {
        $relatedProjects = array_values(array_filter(
            Project::withRelations(['constituency_id' => (int)$project['constituency_id']], 4),
            static fn (array $row): bool => (int)$row['id'] !== (int)$project['id']
        ));
    }
}

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

<?php if (!$isFound): ?>
  <section class="pd-not-found" id="pdNotFound">
    <div class="container">
      <div class="pd-not-found-inner">
        <i class="fa-solid fa-building-circle-xmark pd-not-found-icon" aria-hidden="true"></i>
        <h1><?= $e($text($notFound, 'title', 'Project Not Found')) ?></h1>
        <p><?= $e($text($notFound, 'text', "The project you're looking for doesn't exist or the URL is incorrect.")) ?></p>
        <a href="projects.php" class="btn btn-primary">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= $e($text($notFound, 'button_label', 'Back to All Projects')) ?>
        </a>
      </div>
    </div>
  </section>
<?php else:
    $status = (string)($project['status'] ?? 'planning');
    $statusClass = pd_status_class($status);
    $statusLabel = pd_status_label($status, $labels);
    $pct = max(0, min(100, (int)($project['pct_complete'] ?? 0)));
    $units = (int)($project['units'] ?? 0);
    $unitsInProgress = (int)round($units * $pct / 100);
    $constituencyName = (string)($project['constituency_name'] ?? 'Trans-Nzoia');
    $constituencySlug = (string)($project['constituency_slug'] ?? '');
    $wardName = trim((string)($project['ward_name'] ?? $project['location_label'] ?? ''));
    $location = trim(($wardName !== '' ? $wardName . ', ' : '') . $constituencyName);
    $contractor = trim((string)($project['contractor_name'] ?? ''));
    $siteEngineer = trim((string)($project['site_engineer'] ?? ''));
    $leadAgency = trim((string)($project['lead_agency'] ?? 'State Dept. of Housing'));
    $funding = trim((string)($project['funding_source'] ?? 'National AHP Fund'));
    $currentMilestone = trim((string)($project['current_milestone'] ?? ''));
    $startDate = pd_quarter_label($project['start_date'] ?? null);
    $deliveryDate = pd_quarter_label($project['est_delivery'] ?? null);
    $detailUrl = 'project-detail.php?id=' . rawurlencode((string)$project['slug']);
?>
  <section class="pd-hero" id="pdHero" aria-label="Project overview">
    <div class="pd-hero-bg" id="pdHeroBg" aria-hidden="true">
<?php if ($heroImage !== ''): ?>
      <img src="<?= $e(pd_asset_url($heroImage)) ?>" alt="" loading="eager">
<?php endif; ?>
      <div class="pd-hero-overlay"></div>
    </div>
    <div class="container">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php" class="breadcrumb-link">Home</a>
        <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
        <a href="projects.php" class="breadcrumb-link"><?= $e($text($labels, 'projects_breadcrumb_label', 'Projects')) ?></a>
        <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
        <span class="breadcrumb-current"><?= $e($projectName) ?></span>
      </nav>

      <div class="pd-hero-content" id="pdHeroContent">
        <div class="pd-hero-badges">
          <span class="pd-badge pd-badge--<?= $e($statusClass) ?>">
            <i class="fa-solid <?= $e(pd_icon_for_status($status)) ?>" aria-hidden="true"></i> <?= $e($statusLabel) ?>
          </span>
          <span class="pd-badge pd-badge--con">
            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> <?= $e($constituencyName) ?>
          </span>
<?php if ($startDate !== ''): ?>
          <span class="pd-badge pd-badge--date"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> <?= $e($text($labels, 'started_label', 'Started')) ?> <?= $e($startDate) ?></span>
<?php endif; ?>
<?php if ($deliveryDate !== ''): ?>
          <span class="pd-badge pd-badge--date"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i> <?= $e($text($labels, 'delivery_label', 'Est. Delivery')) ?> <?= $e($deliveryDate) ?></span>
<?php endif; ?>
        </div>
        <h1 class="pd-hero-title"><?= $e($projectName) ?></h1>
        <div class="pd-hero-meta">
<?php if ($location !== ''): ?>
          <span class="pd-hero-meta-item"><i class="fa-solid fa-map-pin" aria-hidden="true"></i> <?= $e($location) ?></span>
<?php endif; ?>
<?php if ($currentMilestone !== ''): ?>
          <span class="pd-hero-meta-item"><i class="fa-solid fa-hard-hat" aria-hidden="true"></i> <?= $e($currentMilestone) ?></span>
<?php endif; ?>
<?php if ($contractor !== ''): ?>
          <span class="pd-hero-meta-item"><i class="fa-solid fa-person-digging" aria-hidden="true"></i> <?= $e($contractor) ?></span>
<?php endif; ?>
        </div>
        <div class="pd-hero-pct-wrap">
          <div class="pd-hero-pct">
            <span class="pd-hero-pct-num"><?= $e($pct) ?><span class="pd-hero-pct-sign">%</span></span>
            <span class="pd-hero-pct-lbl"><?= $e($text($labels, 'construction_complete_label', 'Construction Complete')) ?></span>
          </div>
          <div class="pd-hero-pct-bar" aria-hidden="true">
            <div class="pd-hero-pct-bar-fill" data-target="<?= $e($pct) ?>" style="width:0%"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="pd-body" id="pdBody">
    <div class="container">
      <div class="pd-body-inner">
        <div class="pd-main" id="pdMain">
          <div class="pd-facts-strip" id="pdFactsStrip">
<?php
    $facts = [
        ['fa-house-chimney', $text($labels, 'units_label', 'Units Planned'), pd_number($units)],
        ['fa-map-pin', $text($labels, 'ward_label', 'Ward'), $wardName !== '' ? $wardName : '-'],
        ['fa-person-digging', $text($labels, 'contractor_label', 'Contractor'), $contractor !== '' ? $contractor : 'TBD'],
        ['fa-coins', $text($labels, 'funding_label', 'Funding Source'), $funding !== '' ? $funding : '-'],
        ['fa-calendar-plus', $text($labels, 'start_date_label', 'Start Date'), $startDate !== '' ? $startDate : '-'],
        ['fa-flag-checkered', $text($labels, 'est_delivery_label', 'Est. Delivery'), $deliveryDate !== '' ? $deliveryDate : '-'],
    ];
?>
<?php foreach ($facts as [$icon, $label, $value]): ?>
            <div class="pd-fact">
              <div class="pd-fact-icon-wrap" aria-hidden="true"><i class="fa-solid <?= $e($icon) ?>"></i></div>
              <span class="pd-fact-lbl"><?= $e($label) ?></span>
              <span class="pd-fact-val"><?= $e($value) ?></span>
            </div>
<?php endforeach; ?>
          </div>

          <section class="pd-description pd-card" id="pdDescription">
            <p class="pd-section-label"><?= $e($text($overview, 'section_label', 'About This Project')) ?></p>
            <h2 class="pd-section-title"><?= $e($text($overview, 'title', 'Project Overview')) ?></h2>
            <p class="pd-description-text"><?= nl2br($e($projectDescription)) ?></p>
            <div class="pd-desc-meta-strip">
<?php if ($leadAgency !== ''): ?>
              <div class="pd-desc-meta-item"><i class="fa-solid fa-building-government" aria-hidden="true"></i><span><strong><?= $e($text($overview, 'lead_agency_label', 'Lead Agency')) ?></strong><?= $e($leadAgency) ?></span></div>
<?php endif; ?>
<?php if ($siteEngineer !== '' && strtolower($siteEngineer) !== 'tbd'): ?>
              <div class="pd-desc-meta-item"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i><span><strong><?= $e($text($overview, 'site_engineer_label', 'Site Engineer')) ?></strong><?= $e($siteEngineer) ?></span></div>
<?php endif; ?>
<?php if ($funding !== ''): ?>
              <div class="pd-desc-meta-item"><i class="fa-solid fa-coins" aria-hidden="true"></i><span><strong><?= $e($text($overview, 'funding_label', 'Funding')) ?></strong><?= $e($funding) ?></span></div>
<?php endif; ?>
            </div>
<?php if ($currentMilestone !== ''): ?>
            <div class="pd-current-milestone">
              <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
              <span><strong><?= $e($text($overview, 'current_activity_label', 'Current Activity')) ?>:</strong> <?= $e($currentMilestone) ?></span>
            </div>
<?php endif; ?>
          </section>

          <section class="pd-progress-section pd-card" id="pdProgressSection">
            <p class="pd-section-label"><?= $e($text($overview, 'progress_label', 'Construction Progress')) ?></p>
            <h2 class="pd-section-title"><?= $e($text($overview, 'progress_title', 'Live Progress Tracker')) ?></h2>
            <div class="pd-progress-bar-wrap">
              <div class="pd-progress-meta">
                <span class="pd-progress-lbl"><?= $e($text($overview, 'overall_completion_label', 'Overall Completion')) ?></span>
                <span class="pd-progress-pct"><?= $e($pct) ?>%</span>
              </div>
              <div class="pd-progress-track" role="progressbar" aria-valuenow="<?= $e($pct) ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= $e($pct) ?>% construction complete">
                <div class="pd-progress-fill" data-target="<?= $e($pct) ?>"></div>
              </div>
              <p class="pd-progress-note"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $e($text($overview, 'progress_note', 'Data updated regularly by the Trans-Nzoia County Housing Department.')) ?></p>
            </div>
            <div class="pd-mini-stats">
              <div class="pd-mini-stat"><span class="pd-mini-stat-val"><?= $e(pd_number($units)) ?></span><span class="pd-mini-stat-lbl"><?= $e($text($labels, 'units_label', 'Units Planned')) ?></span></div>
              <div class="pd-mini-stat"><span class="pd-mini-stat-val"><?= $e(pd_number($unitsInProgress)) ?></span><span class="pd-mini-stat-lbl"><?= $e($text($overview, 'units_in_progress_label', 'Units In Progress')) ?></span></div>
              <div class="pd-mini-stat"><span class="pd-mini-stat-val"><?= $e($deliveryDate !== '' ? $deliveryDate : '-') ?></span><span class="pd-mini-stat-lbl"><?= $e($text($overview, 'target_delivery_label', 'Target Delivery')) ?></span></div>
            </div>
          </section>

          <section class="pd-timeline-section pd-card" id="pdTimelineSection">
            <p class="pd-section-label"><?= $e($text($overview, 'timeline_label', 'Key Milestones')) ?></p>
            <h2 class="pd-section-title"><?= $e($text($overview, 'timeline_title', 'Construction Timeline')) ?></h2>
<?php if ($milestones): ?>
            <div class="pd-timeline">
<?php foreach ($milestones as $milestone):
        $milestoneStatus = (string)($milestone['status'] ?? 'pending');
        $itemClass = $milestoneStatus === 'done' ? 'is-done' : ($milestoneStatus === 'current' ? 'is-current' : '');
        $milestoneDate = pd_date_label($milestone['actual_date'] ?: ($milestone['target_date'] ?? ''), (string)($milestone['target_date'] ?? ''));
?>
              <div class="pd-tl-item <?= $e($itemClass) ?>">
                <div class="pd-tl-spine" aria-hidden="true">
                  <div class="pd-tl-dot"><?php if ($milestoneStatus === 'done'): ?><i class="fa-solid fa-check" aria-hidden="true"></i><?php elseif ($milestoneStatus === 'current'): ?><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i><?php endif; ?></div>
                </div>
                <div class="pd-tl-content">
                  <span class="pd-tl-date"><?= $e($milestoneDate !== '' ? $milestoneDate : 'Pending') ?></span>
                  <p class="pd-tl-label"><?= $e($milestone['label'] ?? '') ?><?php if ($milestoneStatus === 'current'): ?> <span class="pd-tl-badge">CURRENT</span><?php endif; ?></p>
                </div>
              </div>
<?php endforeach; ?>
            </div>
<?php else: ?>
            <div class="pd-timeline-empty">
              <i class="fa-solid fa-list-check" aria-hidden="true"></i>
              <p><?= $e($text($overview, 'timeline_empty_text', 'Milestones will appear after they are added to this project.')) ?></p>
            </div>
<?php endif; ?>
          </section>

          <section class="pd-gallery-section pd-card" id="pdGallerySection">
            <p class="pd-section-label"><?= $e($text($overview, 'gallery_label', 'Site Photography')) ?></p>
            <h2 class="pd-section-title"><?= $e($text($overview, 'gallery_title', 'Photo Gallery')) ?></h2>
<?php if ($images): ?>
            <div class="pd-gallery">
<?php foreach ($images as $index => $image): ?>
<?php if ($index === 1): ?>
              <div class="pd-gallery-grid">
<?php endif; ?>
              <button class="<?= $index === 0 ? 'pd-gallery-featured' : 'pd-gallery-item' ?>" type="button" data-gallery-index="<?= $e($index) ?>" data-gallery-src="<?= $e(pd_asset_url($image)) ?>" data-gallery-alt="<?= $e($projectName . ' site photo ' . ($index + 1)) ?>" aria-label="View photo <?= $e($index + 1) ?>">
                <img src="<?= $e(pd_asset_url($image)) ?>" alt="<?= $e($projectName . ' site photo ' . ($index + 1)) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>">
                <div class="pd-gallery-item-overlay"><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></div>
              </button>
<?php endforeach; ?>
<?php if (count($images) > 1): ?>
              </div>
<?php endif; ?>
            </div>
<?php else: ?>
            <div class="pd-gallery-empty">
              <i class="fa-solid fa-camera-slash" aria-hidden="true"></i>
              <p><?= $e($text($overview, 'gallery_empty_text', 'Site photography will be added as construction progresses.')) ?></p>
            </div>
<?php endif; ?>
          </section>
        </div>

        <aside class="pd-sidebar" id="pdSidebar" aria-label="Project quick facts">
          <div class="pd-sidebar-card pd-sb-contractor">
            <div class="pd-sb-con-avatar" aria-hidden="true"><i class="fa-solid fa-helmet-safety"></i></div>
            <div class="pd-sb-con-info">
              <p class="pd-sidebar-card-title"><?= $e($text($sidebar, 'contractor_title', 'Contractor')) ?></p>
              <p class="pd-contractor-name"><?= $e($contractor !== '' ? $contractor : 'TBD') ?></p>
              <span class="pd-contractor-role-badge"><?= $e($text($sidebar, 'contractor_role_label', 'Principal Contractor')) ?></span>
            </div>
<?php if ($leadAgency !== ''): ?>
            <div class="pd-sb-agency"><i class="fa-solid fa-building-government" aria-hidden="true"></i><span><?= $e($leadAgency) ?></span></div>
<?php endif; ?>
          </div>

          <div class="pd-sidebar-card">
            <p class="pd-sidebar-card-title"><?= $e($text($sidebar, 'project_info_title', 'Project Info')) ?></p>
            <div class="pd-sidebar-list">
<?php
    $infoRows = [
        [$text($sidebar, 'constituency_label', 'Constituency'), $constituencySlug !== '' ? '<a href="constituency-detail.php?id=' . $e(rawurlencode($constituencySlug)) . '" class="pd-sb-link">' . $e($constituencyName) . '</a>' : $e($constituencyName), true],
        [$text($labels, 'ward_label', 'Ward'), $e($wardName !== '' ? $wardName : '-') , true],
        [$text($sidebar, 'status_label', 'Status'), '<span class="pd-sb-status pd-sb-status--' . $e($statusClass) . '">' . $e($statusLabel) . '</span>', true],
        [$text($sidebar, 'target_units_label', 'Target Units'), $e(pd_number($units)), true],
        [$text($labels, 'start_date_label', 'Start Date'), $e($startDate !== '' ? $startDate : '-'), true],
        [$text($labels, 'est_delivery_label', 'Est. Delivery'), $e($deliveryDate !== '' ? $deliveryDate : '-'), true],
        [$text($sidebar, 'completion_label', 'Completion'), '<span class="pd-sb-pct">' . $e($pct) . '%</span><div class="pd-sb-mini-bar"><div class="pd-sb-mini-fill" style="width:' . $e($pct) . '%"></div></div>', true],
    ];
?>
<?php foreach ($infoRows as $index => [$label, $value, $raw]): ?>
              <div class="pd-sidebar-row<?= $index % 2 === 1 ? ' is-alt' : '' ?>">
                <span class="pd-sidebar-row-lbl"><?= $e($label) ?></span>
                <span class="pd-sidebar-row-val"><?= $raw ? $value : $e($value) ?></span>
              </div>
<?php endforeach; ?>
            </div>
          </div>

          <div class="pd-sidebar-card pd-apply-card">
            <div class="pd-apply-icon-wrap" aria-hidden="true"><i class="fa-solid fa-house-chimney-user"></i></div>
            <p class="pd-apply-title"><?= $e($text($applyCta, 'title', 'Interested in a Unit?')) ?></p>
            <p class="pd-apply-sub"><?= $e($text($applyCta, 'subtitle', 'Register on the national Boma Yangu portal to apply for affordable housing in Trans-Nzoia County.')) ?></p>
            <a href="<?= $e($text($applyCta, 'button_url', 'https://app.bomayangu.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="pd-apply-btn">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> <?= $e($text($applyCta, 'button_label', 'Apply on Boma Yangu')) ?>
            </a>
          </div>

          <div class="pd-sidebar-card">
            <p class="pd-sidebar-card-title"><?= $e($text($sidebar, 'related_title', 'Related')) ?></p>
            <div class="pd-related-links">
<?php if ($constituencySlug !== ''): ?>
              <a href="constituency-detail.php?id=<?= $e(rawurlencode($constituencySlug)) ?>" class="pd-related-link">
                <div class="pd-related-icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></div>
                <span><?= $e($constituencyName . ' ' . $text($sidebar, 'constituency_link_suffix', 'Constituency')) ?></span>
                <i class="fa-solid fa-chevron-right pd-related-arrow" aria-hidden="true"></i>
              </a>
              <a href="projects.php?constituency=<?= $e(rawurlencode($constituencySlug)) ?>" class="pd-related-link">
                <div class="pd-related-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></div>
                <span><?= $e($text($sidebar, 'all_projects_prefix', 'All') . ' ' . $constituencyName . ' Projects') ?></span>
                <i class="fa-solid fa-chevron-right pd-related-arrow" aria-hidden="true"></i>
              </a>
<?php endif; ?>
              <a href="projects.php" class="pd-related-link">
                <div class="pd-related-icon"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></div>
                <span><?= $e($text($sidebar, 'back_projects_label', 'Back to All Projects')) ?></span>
                <i class="fa-solid fa-chevron-right pd-related-arrow" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
<?php endif; ?>
</main>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
