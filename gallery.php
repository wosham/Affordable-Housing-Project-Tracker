<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'gallery';
$cmsPage = CmsLoader::page('gallery');
$hero = CmsLoader::content($cmsPage, 'gallery_hero', []);
$highlightsContent = CmsLoader::content($cmsPage, 'gallery_highlights', []);
$archiveContent = CmsLoader::content($cmsPage, 'gallery_archive', []);
$siteContent = CmsLoader::content($cmsPage, 'gallery_site_progress', []);
$videoContent = CmsLoader::content($cmsPage, 'gallery_videos', []);
$emptyContent = CmsLoader::content($cmsPage, 'gallery_empty_states', []);
$text = static fn (array $content, string $key, string $default = ''): string => CmsLoader::text($content, $key, $default);
$e = static fn (mixed $value): string => Security::e($value);

function gallery_asset(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || str_starts_with($path, 'data:')
        ? $path
        : Url::asset($path);
}

function gallery_month_year(?string $date, mixed $year = null): string
{
    $date = trim((string)$date);
    if ($date !== '' && $date !== '0000-00-00') {
        $timestamp = strtotime($date);
        if ($timestamp) {
            return date('M Y', $timestamp);
        }
    }
    return $year ? (string)$year : '';
}

function gallery_item_image(array $item): string
{
    return gallery_asset($item['thumbnail_path'] ?: $item['thumbnail_url'] ?: $item['media_path'] ?: $item['media_url'] ?: '');
}

function gallery_video_url(array $item): string
{
    return trim((string)($item['video_url'] ?? '')) ?: gallery_asset($item['media_path'] ?: $item['media_url'] ?: '');
}

$categories = GalleryCategory::allOrdered();
$photos = GalleryImage::publicItems(['media_type' => 'image']);
$highlights = GalleryImage::publicItems(['highlight' => '1'], 8);
if ($highlights === []) {
    $highlights = GalleryImage::publicItems(['featured' => '1'], 8);
}
$videos = GalleryImage::publicItems(['media_type' => 'video'], 6);
$years = GalleryImage::years();
$siteSummary = GalleryImage::siteSummary();

$photoCount = count($photos);
$siteCount = count($siteSummary);
$yearCount = count($years);
$eventCount = count($highlights);
$heroImage = $text($hero, 'background_image', (string)($cmsPage['hero_image'] ?? 'uploads/gallery/maili-tatu-2.jpg'));

$siteData = [];
foreach ($siteSummary as $site) {
    $key = (string)($site['site_key'] ?? 'general');
    $sitePhotos = array_values(array_filter($photos, static fn (array $item): bool => (string)($item['site_key'] ?? '') === $key || (string)($item['constituency_slug'] ?? '') === $key));
    $siteData[$key] = [
        'name' => (string)($site['site_name'] ?? 'General'),
        'units' => (int)($site['units'] ?? 0),
        'pct' => max(0, min(100, (int)($site['pct'] ?? 0))),
        'photos' => count($sitePhotos),
        'link' => !empty($sitePhotos[0]['constituency_slug']) ? 'constituency-detail.php?id=' . rawurlencode((string)$sitePhotos[0]['constituency_slug']) : 'constituencies.php',
        'images' => array_values(array_filter(array_map('gallery_item_image', array_slice($sitePhotos, 0, 8)))),
    ];
}

$pageTitle = $cmsPage['seo_title'] ?? 'Photo Gallery | Trans-Nzoia County AHP Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Photo and video documentation of Trans-Nzoia Affordable Housing Programme construction progress, ceremonies, community engagements and site activity.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia affordable housing gallery, AHP Kenya photos, construction progress videos';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/gallery.php';
$pageStyles = ['assets/css/global.css', 'assets/css/pages/gallery.css'];
$pageScripts = ['assets/js/global.js', 'assets/js/pages/gallery.js'];
$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . $e($pageTitle) . '">',
    '<meta property="og:description" content="' . $e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . $e($canonicalUrl) . '">',
    '<meta property="og:image" content="' . $e(gallery_asset($heroImage)) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
];
include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

  <section class="gl-hero" aria-label="Gallery overview">
    <div class="gl-hero-bg" aria-hidden="true">
      <img src="<?= $e(gallery_asset($heroImage)) ?>" alt="" loading="eager">
      <div class="gl-hero-overlay"></div>
      <div class="gl-hero-grid" aria-hidden="true"></div>
    </div>
    <div class="container">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php" class="breadcrumb-link">Home</a>
        <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
        <span class="breadcrumb-current" aria-current="page"><?= $e($text($hero, 'breadcrumb_label', 'Photo Gallery')) ?></span>
      </nav>
      <div class="gl-hero-body">
        <div class="gl-hero-eyebrow"><i class="fa-solid fa-images" aria-hidden="true"></i> <?= $e($text($hero, 'eyebrow', 'Visual Documentation - Sites, Events & Progress')) ?></div>
        <h1 class="gl-hero-title"><?= $e($text($hero, 'title', 'Programme in Pictures')) ?></h1>
        <p class="gl-hero-sub"><?= $e($text($hero, 'subtitle', 'A visual record of every milestone across all five Trans-Nzoia constituencies.')) ?></p>
        <div class="gl-hero-kpi-strip" role="region" aria-label="Gallery at a glance">
          <?php
            $kpis = [
                [format_number($photoCount), $text($hero, 'photos_label', 'Photos Archived')],
                [format_number($siteCount), $text($hero, 'sites_label', 'Sites Documented')],
                [format_number($yearCount), $text($hero, 'years_label', 'Years of Coverage')],
                [format_number($eventCount), $text($hero, 'events_label', 'Events Captured')],
            ];
          ?>
          <?php foreach ($kpis as $i => [$value, $label]): ?>
            <?php if ($i > 0): ?><div class="gl-hero-kpi-div" aria-hidden="true"></div><?php endif; ?>
            <div class="gl-hero-kpi-item"><span class="gl-hero-kpi-num"><?= $e($value) ?></span><span class="gl-hero-kpi-lbl"><?= $e($label) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="gl-hero-scroll-hint" aria-hidden="true">
      <span><?= $e($text($hero, 'scroll_label', 'Browse the gallery')) ?></span>
      <i class="fa-solid fa-chevron-down"></i>
    </div>
  </section>

  <section class="gl-highlights" aria-labelledby="highlights-heading">
    <div class="container">
      <div class="gl-section-header fade-up">
        <div class="gl-eyebrow"><i class="fa-solid fa-star" aria-hidden="true"></i> <?= $e($text($highlightsContent, 'eyebrow', 'Featured Moments')) ?></div>
        <h2 class="gl-section-title" id="highlights-heading"><?= $e($text($highlightsContent, 'title', 'Programme Highlights')) ?></h2>
        <p class="gl-section-sub"><?= $e($text($highlightsContent, 'subtitle', 'Landmark moments captured across the programme.')) ?></p>
      </div>
<?php if ($highlights): ?>
      <div class="gl-hl-nav fade-up">
        <button class="gl-hl-arrow" id="hlPrev" aria-label="Previous highlight" disabled><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <div class="gl-hl-track-wrap">
          <div class="gl-hl-track" id="hlTrack">
<?php foreach ($highlights as $index => $item): ?>
            <article class="gl-hl-slide" data-index="<?= $e($index) ?>">
              <div class="gl-hl-img-wrap">
                <img src="<?= $e(gallery_item_image($item)) ?>" alt="<?= $e($item['alt_text'] ?: $item['media_alt_text'] ?: $item['title']) ?>" loading="lazy">
                <span class="gl-hl-badge"><?= $e($item['category_name'] ?? 'Gallery') ?></span>
              </div>
              <div class="gl-hl-body">
                <span class="gl-hl-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> <?= $e(gallery_month_year($item['taken_at'] ?? '', $item['year'] ?? '')) ?></span>
                <h3 class="gl-hl-title"><?= $e($item['title']) ?></h3>
                <p class="gl-hl-desc"><?= $e($item['highlight_summary'] ?: $item['caption'] ?: $item['title']) ?></p>
                <span class="gl-hl-site"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= $e($item['location'] ?: $item['constituency_name'] ?: 'Trans-Nzoia County') ?></span>
              </div>
            </article>
<?php endforeach; ?>
          </div>
        </div>
        <button class="gl-hl-arrow" id="hlNext" aria-label="Next highlight"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
      </div>
      <div class="gl-hl-dots" id="hlDots" role="tablist" aria-label="Highlight slides"></div>
<?php else: ?>
      <div class="gl-no-results gl-empty-inline"><i class="fa-solid fa-star" aria-hidden="true"></i><p><?= $e($text($highlightsContent, 'empty_text', 'Featured gallery moments will appear after they are marked as highlights.')) ?></p></div>
<?php endif; ?>
    </div>
  </section>

  <section class="gl-grid-section" aria-labelledby="grid-heading">
    <div class="container">
      <div class="gl-section-header fade-up">
        <div class="gl-eyebrow"><i class="fa-solid fa-border-all" aria-hidden="true"></i> <?= $e($text($archiveContent, 'eyebrow', 'Full Archive')) ?></div>
        <h2 class="gl-section-title" id="grid-heading"><?= $e($text($archiveContent, 'title', 'Browse All Photos')) ?></h2>
        <p class="gl-section-sub"><?= $e($text($archiveContent, 'subtitle', 'Filter by category, site or year to find specific documentation of the programme.')) ?></p>
      </div>

      <div class="gl-filter-bar fade-up" role="toolbar" aria-label="Photo filters">
        <div class="gl-filter-group">
          <span class="gl-filter-label"><?= $e($text($archiveContent, 'category_label', 'Category')) ?></span>
          <div class="gl-filter-chips" role="group" aria-label="Filter by category">
            <button class="gl-chip is-active" type="button" data-filter="cat" data-val="all"><?= $e($text($archiveContent, 'all_label', 'All')) ?></button>
<?php foreach ($categories as $category): ?>
            <button class="gl-chip" type="button" data-filter="cat" data-val="<?= $e($category['slug']) ?>"><?= $e($category['name']) ?></button>
<?php endforeach; ?>
          </div>
        </div>
        <div class="gl-filter-group">
          <span class="gl-filter-label"><?= $e($text($archiveContent, 'year_label', 'Year')) ?></span>
          <div class="gl-filter-chips" role="group" aria-label="Filter by year">
            <button class="gl-chip is-active" type="button" data-filter="year" data-val="all"><?= $e($text($archiveContent, 'all_label', 'All')) ?></button>
<?php foreach ($years as $year): ?>
            <button class="gl-chip" type="button" data-filter="year" data-val="<?= $e($year) ?>"><?= $e($year) ?></button>
<?php endforeach; ?>
          </div>
        </div>
        <div class="gl-filter-count" id="filterCount" aria-live="polite"><?= $e($text($archiveContent, 'showing_label', 'Showing')) ?> <strong><?= $e($photoCount) ?></strong> <?= $e($text($archiveContent, 'photos_label', 'photos')) ?></div>
      </div>

      <div class="gl-photo-grid" id="photoGrid" role="list" aria-label="Photo gallery">
<?php foreach ($photos as $item): ?>
<?php $image = gallery_item_image($item); ?>
        <button class="gl-photo-item" type="button" data-cat="<?= $e($item['category_slug'] ?? '') ?>" data-year="<?= $e($item['year'] ?? '') ?>" data-site="<?= $e($item['site_key'] ?? '') ?>" data-full-src="<?= $e($image) ?>" role="listitem" aria-label="Open photo: <?= $e($item['title']) ?>">
          <div class="gl-photo-wrap">
            <img src="<?= $e($image) ?>" alt="<?= $e($item['alt_text'] ?: $item['media_alt_text'] ?: $item['title']) ?>" loading="lazy">
            <div class="gl-photo-overlay" aria-hidden="true"><i class="fa-solid fa-expand"></i><span class="gl-photo-overlay-title"><?= $e($item['title']) ?></span></div>
          </div>
          <div class="gl-photo-meta">
            <span class="gl-photo-badge gl-photo-badge--<?= $e($item['category_slug'] ?? 'gallery') ?>"><?= $e($item['category_name'] ?? 'Gallery') ?></span>
            <span class="gl-photo-date"><?= $e(gallery_month_year($item['taken_at'] ?? '', $item['year'] ?? '')) ?></span>
          </div>
        </button>
<?php endforeach; ?>
      </div>

      <div class="gl-no-results" id="noResults" aria-live="polite" <?= $photos ? 'hidden' : '' ?>>
        <i class="fa-solid fa-image-slash" aria-hidden="true"></i>
        <p><?= $e($text($emptyContent, 'no_results_text', 'No photos match the selected filters.')) ?> <button class="gl-reset-link" type="button" id="resetFilters"><?= $e($text($emptyContent, 'reset_label', 'Clear filters')) ?></button></p>
      </div>
    </div>
  </section>

  <section class="gl-sites" aria-labelledby="sites-heading">
    <div class="gl-sites-bg" aria-hidden="true"></div>
    <div class="container">
      <div class="gl-section-header fade-up">
        <div class="gl-eyebrow"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> <?= $e($text($siteContent, 'eyebrow', 'By Constituency')) ?></div>
        <h2 class="gl-section-title gl-section-title--light" id="sites-heading"><?= $e($text($siteContent, 'title', 'Progress by Site')) ?></h2>
        <p class="gl-section-sub gl-section-sub--light"><?= $e($text($siteContent, 'subtitle', 'Select a constituency to see its photos and current construction status.')) ?></p>
      </div>

      <div class="gl-site-tabs fade-up" role="tablist" aria-label="Constituency sites">
<?php $firstSite = array_key_first($siteData); ?>
<?php foreach ($siteData as $key => $site): ?>
        <button class="gl-site-tab <?= $key === $firstSite ? 'is-active' : '' ?>" type="button" role="tab" aria-selected="<?= $key === $firstSite ? 'true' : 'false' ?>" data-site="<?= $e($key) ?>" aria-controls="sitePanel">
          <span class="gl-site-tab-name"><?= $e($site['name']) ?></span>
          <span class="gl-site-tab-count"><?= $e(format_number($site['photos'])) ?> <?= $e(strtolower($text($siteContent, 'photos_label', 'Photos'))) ?></span>
        </button>
<?php endforeach; ?>
      </div>

      <div class="gl-site-panel fade-up" id="sitePanel" role="tabpanel">
        <div class="gl-site-info">
          <div class="gl-site-info-body">
            <div class="gl-site-stat"><span class="gl-site-stat-num" id="siteStat1">0</span><span class="gl-site-stat-lbl"><?= $e($text($siteContent, 'units_label', 'Units Planned')) ?></span></div>
            <div class="gl-site-stat"><span class="gl-site-stat-num" id="siteStat2">0%</span><span class="gl-site-stat-lbl"><?= $e($text($siteContent, 'completion_label', 'Completion')) ?></span></div>
            <div class="gl-site-stat"><span class="gl-site-stat-num" id="siteStat3">0</span><span class="gl-site-stat-lbl"><?= $e($text($siteContent, 'photos_label', 'Photos')) ?></span></div>
          </div>
          <div class="gl-site-progress-bar" aria-hidden="true"><div class="gl-site-progress-fill" id="siteProgressFill" style="width:0%"></div></div>
          <a href="constituencies.php" class="gl-site-link" id="siteDetailsLink"><?= $e($text($siteContent, 'details_label', 'View full site details')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="gl-site-mini-grid" id="siteMiniGrid"></div>
      </div>
    </div>
  </section>

  <section class="gl-video" aria-labelledby="video-heading">
    <div class="container">
      <div class="gl-section-header fade-up">
        <div class="gl-eyebrow"><i class="fa-solid fa-circle-play" aria-hidden="true"></i> <?= $e($text($videoContent, 'eyebrow', 'Video Updates')) ?></div>
        <h2 class="gl-section-title" id="video-heading"><?= $e($text($videoContent, 'title', 'Progress Videos')) ?></h2>
        <p class="gl-section-sub"><?= $e($text($videoContent, 'subtitle', 'Watch construction progress reports, community barazas and official ceremony recordings.')) ?></p>
      </div>
<?php if ($videos): ?>
      <div class="gl-video-grid fade-up">
<?php foreach ($videos as $video): ?>
<?php $videoUrl = gallery_video_url($video); ?>
        <article class="gl-video-card">
          <div class="gl-video-thumb-wrap">
            <img src="<?= $e(gallery_item_image($video)) ?>" alt="<?= $e($video['alt_text'] ?: $video['title']) ?>" loading="lazy">
<?php if ($videoUrl !== ''): ?>
            <a class="gl-video-play-btn" href="<?= $e($videoUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Play <?= $e($video['title']) ?>"><i class="fa-solid fa-play" aria-hidden="true"></i></a>
<?php endif; ?>
<?php if (!empty($video['duration'])): ?><span class="gl-video-duration"><?= $e($video['duration']) ?></span><?php endif; ?>
          </div>
          <div class="gl-video-body">
            <span class="gl-video-cat"><?= $e($video['category_name'] ?? 'Video') ?></span>
            <h3 class="gl-video-title"><?= $e($video['title']) ?></h3>
            <span class="gl-video-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> <?= $e(gallery_month_year($video['taken_at'] ?? '', $video['year'] ?? '')) ?><?= !empty($video['location']) ? ' - ' . $e($video['location']) : '' ?></span>
          </div>
        </article>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <div class="gl-no-results gl-empty-inline"><i class="fa-solid fa-circle-play" aria-hidden="true"></i><p><?= $e($text($videoContent, 'empty_text', 'Progress videos will appear after they are published.')) ?></p></div>
<?php endif; ?>
    </div>
  </section>
</main>

<div class="gl-lightbox" id="glLightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?= $e($text($emptyContent, 'lightbox_label', 'Photo lightbox')) ?>">
  <div class="gl-lb-backdrop" id="glLbBackdrop"></div>
  <div class="gl-lb-content">
    <button class="gl-lb-close" type="button" id="glLbClose" aria-label="Close gallery"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    <button class="gl-lb-nav gl-lb-prev" type="button" id="glLbPrev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
    <div class="gl-lb-img-wrap"><img class="gl-lb-img" id="glLbImg" src="" alt=""></div>
    <button class="gl-lb-nav gl-lb-next" type="button" id="glLbNext" aria-label="Next photo"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
    <div class="gl-lb-footer">
      <div class="gl-lb-meta">
        <span class="gl-lb-badge" id="glLbBadge"></span>
        <p class="gl-lb-caption" id="glLbCaption"></p>
        <span class="gl-lb-date" id="glLbDate"></span>
      </div>
      <span class="gl-lb-counter" id="glLbCounter"></span>
    </div>
  </div>
</div>

<script>
window.GALLERY_SITE_DATA = <?= json_encode($siteData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
