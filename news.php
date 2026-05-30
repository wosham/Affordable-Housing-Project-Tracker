<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'news';

$cmsPage = CmsLoader::page('news');

$hero = CmsLoader::content($cmsPage, 'news_hero', [
    'eyebrow' => 'News & Updates',
    'title_plain_1' => 'Latest',
    'title_accent' => 'News &',
    'title_plain_2' => 'Announcements',
    'subtitle' => 'Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.',
    'search_placeholder' => 'Search articles...',
    'articles_label' => 'Articles',
    'categories_label' => 'Categories',
    'last_updated_label' => 'Last Updated',
]);
$mosaic = CmsLoader::content($cmsPage, 'news_mosaic', [
    'show_mosaic' => '1',
    'programme_label' => 'Programme',
    'groundbreaking_label' => 'Groundbreaking',
    'construction_label' => 'Construction',
    'policy_label' => 'Policy',
    'community_label' => 'Community',
    'official_label' => 'Official',
    'field_reports_label' => 'Field Reports',
    'total_label' => "Total\nArticles",
]);
$featuredCopy = CmsLoader::content($cmsPage, 'news_featured', [
    'section_label' => 'Featured Story',
    'featured_badge' => 'Featured',
    'read_button_label' => 'Read Full Article',
    'empty_title' => 'Featured story coming soon',
    'empty_text' => 'Mark a published news post as featured to show it here.',
]);
$filtersCopy = CmsLoader::content($cmsPage, 'news_filters', [
    'all_label' => 'All Articles',
    'results_suffix_single' => 'article',
    'results_suffix_plural' => 'articles',
    'latest_label' => 'Latest First',
    'oldest_label' => 'Oldest First',
]);
$listingCopy = CmsLoader::content($cmsPage, 'news_listing', [
    'title' => 'All Articles',
    'subtitle' => 'Showing latest news & updates',
    'read_more_label' => 'Read More',
    'load_more_label' => 'Load More Articles',
    'empty_title' => 'No Articles Found',
    'empty_text' => 'No articles match your current search or filter. Try a different keyword or category.',
    'empty_reset_label' => 'Clear Filters',
]);
$cta = CmsLoader::content($cmsPage, 'news_cta', [
    'title_prefix' => 'Stay',
    'title_highlight' => 'Up to Date',
    'subtitle' => 'Follow the programme on social media or apply for housing directly through the eCitizen portal to receive official notifications about unit availability and beneficiary selection.',
    'x_url' => '#',
    'facebook_url' => '#',
    'youtube_url' => '#',
    'button_label' => 'Apply via eCitizen',
    'button_url' => 'https://ecitizen.go.ke',
]);

$featuredArticle = NewsArticle::featured();
$articles = NewsArticle::withRelations(['exclude_featured' => true]);
$categories = NewsArticle::publicCategories();
$stats = NewsArticle::publicStats();

$listingCount = count($articles);
$categoryCount = count($categories);
$lastUpdated = $stats['last_published_at'] ?? null;

$pageTitle = $cmsPage['seo_title'] ?? 'News & Updates | Trans-Nzoia County AHP Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Latest news, official announcements, construction progress reports, and community updates from the Trans-Nzoia County Affordable Housing Programme.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia housing news, AHP announcements, affordable housing Kenya, county housing updates';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = $cmsPage['canonical_url'] ?? 'https://housing.transnzoia.go.ke/news.php';
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/news.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/news.js',
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
$articleUrl = static function (array $article): string {
    $slug = trim((string)($article['slug'] ?? ''));
    return 'news-article.php' . ($slug !== '' ? '?id=' . rawurlencode($slug) : '');
};
$articleImage = static function (array $article) use ($asset): string {
    return $asset($article['featured_image_path'] ?? $article['featured_image_url'] ?? '');
};
$articleDate = static function (array $article, string $format = 'j M Y'): string {
    $date = (string)($article['published_at'] ?? $article['created_at'] ?? '');
    $timestamp = strtotime($date);
    return $timestamp === false ? '' : date($format, $timestamp);
};
$articleDateAttr = static function (array $article): string {
    $date = (string)($article['published_at'] ?? $article['created_at'] ?? '');
    $timestamp = strtotime($date);
    return $timestamp === false ? '' : date('Y-m-d', $timestamp);
};
$lastUpdatedLabel = static function (?string $date): string {
    $timestamp = strtotime((string)$date);
    return $timestamp === false ? '-' : date("M 'y", $timestamp);
};
$categoryClass = static function (?string $slug): string {
    $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$slug));
    return 'news-cat--' . ($slug !== '' ? $slug : 'programme-updates');
};
$categoryLabel = static function (array $category, array $mosaicCopy): string {
    return match ((string)($category['slug'] ?? '')) {
        'programme-updates' => CmsLoader::text($mosaicCopy, 'programme_label', 'Programme'),
        'groundbreaking' => CmsLoader::text($mosaicCopy, 'groundbreaking_label', 'Groundbreaking'),
        'construction' => CmsLoader::text($mosaicCopy, 'construction_label', 'Construction'),
        'policy' => CmsLoader::text($mosaicCopy, 'policy_label', 'Policy'),
        'community' => CmsLoader::text($mosaicCopy, 'community_label', 'Community'),
        'official' => CmsLoader::text($mosaicCopy, 'official_label', 'Official'),
        'field-reports' => CmsLoader::text($mosaicCopy, 'field_reports_label', 'Field Reports'),
        default => (string)($category['name'] ?? 'News'),
    };
};
$authorName = static function (array $article): string {
    $name = trim((string)($article['author_first_name'] ?? '') . ' ' . (string)($article['author_last_name'] ?? ''));
    return $name !== '' ? $name : 'Trans-Nzoia County Department of Land, Housing & Physical Planning';
};

$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . $e($pageTitle) . '">',
    '<meta property="og:description" content="' . $e($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . $e($canonicalUrl) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . $e($pageTitle) . '">',
];

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

    <section class="news-hero" aria-label="News and updates">
      <div class="news-hero-bg" aria-hidden="true"></div>
      <div class="news-hero-glow" aria-hidden="true"></div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page"><?= $e($text($hero, 'eyebrow', 'News & Updates')) ?></span>
        </nav>
        <div class="news-hero-inner">
          <div class="news-hero-layout">
            <div class="news-hero-left">
              <div class="news-hero-badge">
                <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                <?= $e($text($hero, 'eyebrow', 'News & Updates')) ?>
              </div>
              <h1 class="news-hero-title">
                <span class="title-plain"><?= $e($text($hero, 'title_plain_1', 'Latest')) ?> </span><span class="title-accent"><?= $e($text($hero, 'title_accent', 'News &')) ?></span><br>
                <span class="title-plain"><?= $e($text($hero, 'title_plain_2', 'Announcements')) ?></span>
              </h1>
              <p class="news-hero-sub"><?= $e($text($hero, 'subtitle', 'Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.')) ?></p>
              <div class="news-search-wrap" role="search">
                <i class="fa-solid fa-magnifying-glass news-search-icon" aria-hidden="true"></i>
                <input type="search" class="news-search-input" id="newsSearch" placeholder="<?= $e($text($hero, 'search_placeholder', 'Search articles...')) ?>" aria-label="Search news articles" autocomplete="off">
                <button class="news-search-clear" id="newsSearchClear" type="button" aria-label="Clear search">
                  <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
              </div>
              <div class="news-hero-stats" aria-label="Programme statistics">
                <div class="news-stat-block">
                  <span class="news-stat-num"><?= $e(format_number($listingCount)) ?></span>
                  <span class="news-stat-lbl"><?= $e($text($hero, 'articles_label', 'Articles')) ?></span>
                </div>
                <div class="news-stat-block">
                  <span class="news-stat-num"><?= $e(format_number($categoryCount)) ?></span>
                  <span class="news-stat-lbl"><?= $e($text($hero, 'categories_label', 'Categories')) ?></span>
                </div>
                <div class="news-stat-block">
                  <span class="news-stat-num"><?= $e($lastUpdatedLabel($lastUpdated)) ?></span>
                  <span class="news-stat-lbl"><?= $e($text($hero, 'last_updated_label', 'Last Updated')) ?></span>
                </div>
              </div>
            </div>

            <?php if (CmsLoader::bool($mosaic, 'show_mosaic', true)): ?>
            <div class="news-hero-right">
              <div class="news-hero-mosaic" aria-hidden="true">
                <div class="mosaic-tile mosaic-tile--programme">
                  <i class="fa-solid fa-bullhorn"></i><span><?= $e($text($mosaic, 'programme_label', 'Programme')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--groundbreak">
                  <i class="fa-solid fa-hammer"></i><span><?= $e($text($mosaic, 'groundbreaking_label', 'Groundbreaking')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--construction">
                  <i class="fa-solid fa-hard-hat"></i><span><?= $e($text($mosaic, 'construction_label', 'Construction')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--policy">
                  <i class="fa-solid fa-file-contract"></i><span><?= $e($text($mosaic, 'policy_label', 'Policy')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--community">
                  <i class="fa-solid fa-people-group"></i><span><?= $e($text($mosaic, 'community_label', 'Community')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--official">
                  <i class="fa-solid fa-stamp"></i><span><?= $e($text($mosaic, 'official_label', 'Official')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--field">
                  <i class="fa-solid fa-map-pin"></i><span><?= $e($text($mosaic, 'field_reports_label', 'Field Reports')) ?></span>
                </div>
                <div class="mosaic-tile mosaic-tile--count">
                  <span class="mosaic-big-num"><?= $e(format_number($listingCount)) ?></span>
                  <span class="mosaic-big-lbl"><?= nl2br($e($text($mosaic, 'total_label', "Total\nArticles"))) ?></span>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="news-featured-section" aria-label="Featured article">
      <div class="container">
        <p class="news-section-label"><i class="fa-solid fa-star" aria-hidden="true"></i> <?= $e($text($featuredCopy, 'section_label', 'Featured Story')) ?></p>
        <?php if ($featuredArticle): ?>
        <?php
            $featuredImage = $articleImage($featuredArticle);
            $featuredCategory = (string)($featuredArticle['category_slug'] ?? '');
            $featuredCategoryName = (string)($featuredArticle['category_name'] ?? 'Programme Updates');
        ?>
        <article class="news-featured">
          <div class="news-featured-img">
            <div class="news-featured-img-placeholder" aria-hidden="true">
              <i class="fa-solid fa-newspaper"></i>
              <span><?= $e($featuredCategoryName) ?></span>
            </div>
            <?php if ($featuredImage !== ''): ?>
            <img src="<?= $e($featuredImage) ?>" alt="<?= $e($featuredArticle['featured_image_alt'] ?? $featuredArticle['title'] ?? 'Featured article') ?>" loading="eager" onerror="this.style.display='none'">
            <?php endif; ?>
            <div class="news-featured-img-overlay" aria-hidden="true"></div>
            <span class="news-cat-badge <?= $e($categoryClass($featuredCategory)) ?>"><?= $e($featuredCategoryName) ?></span>
            <span class="news-featured-label-badge"><i class="fa-solid fa-star" aria-hidden="true"></i> <?= $e($text($featuredCopy, 'featured_badge', 'Featured')) ?></span>
          </div>
          <div class="news-featured-body">
            <div class="news-featured-meta">
              <time class="news-featured-date" datetime="<?= $e($articleDateAttr($featuredArticle)) ?>">
                <i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= $e($articleDate($featuredArticle)) ?>
              </time>
              <span class="news-featured-read">
                <i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $e($featuredArticle['read_time'] ?? '5 min read') ?>
              </span>
            </div>
            <h2 class="news-featured-title"><?= $e($featuredArticle['title'] ?? '') ?></h2>
            <p class="news-featured-excerpt"><?= $e($featuredArticle['excerpt'] ?? '') ?></p>
            <div class="news-featured-author">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
              <?= $e($authorName($featuredArticle)) ?>
            </div>
            <a href="<?= $e($articleUrl($featuredArticle)) ?>" class="news-featured-cta">
              <?= $e($text($featuredCopy, 'read_button_label', 'Read Full Article')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </article>
        <?php else: ?>
        <div class="news-empty is-visible" role="status">
          <div class="news-empty-icon" aria-hidden="true"><i class="fa-solid fa-star"></i></div>
          <h3><?= $e($text($featuredCopy, 'empty_title', 'Featured story coming soon')) ?></h3>
          <p><?= $e($text($featuredCopy, 'empty_text', 'Mark a published news post as featured to show it here.')) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <div class="news-filter-bar" role="toolbar" aria-label="Filter articles by category">
      <div class="container">
        <div class="news-filter-inner">
          <div class="news-filter-pills" role="group" aria-label="Category filters">
            <button class="news-filter-pill is-active" data-category="all" type="button"><span class="pill-dot"></span><?= $e($text($filtersCopy, 'all_label', 'All Articles')) ?></button>
            <?php foreach ($categories as $category): ?>
            <button class="news-filter-pill" data-category="<?= $e($category['slug'] ?? '') ?>" type="button"><span class="pill-dot"></span><?= $e($categoryLabel($category, $mosaic)) ?></button>
            <?php endforeach; ?>
          </div>
          <div class="news-filter-right">
            <span class="news-results-count" id="newsResultsCount" aria-live="polite" aria-atomic="true"><?= $e(format_number($listingCount) . ' ' . $text($filtersCopy, $listingCount === 1 ? 'results_suffix_single' : 'results_suffix_plural', $listingCount === 1 ? 'article' : 'articles')) ?></span>
            <select class="news-sort-select" id="newsSort" aria-label="Sort articles">
              <option value="latest"><?= $e($text($filtersCopy, 'latest_label', 'Latest First')) ?></option>
              <option value="oldest"><?= $e($text($filtersCopy, 'oldest_label', 'Oldest First')) ?></option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <section class="news-grid-section" aria-label="News articles">
      <div class="container">
        <div class="news-grid-header">
          <h2 class="news-grid-title"><?= $e($text($listingCopy, 'title', 'All Articles')) ?></h2>
          <span class="news-grid-subtitle" id="newsGridSubtitle"><?= $e($text($listingCopy, 'subtitle', 'Showing latest news & updates')) ?></span>
        </div>
        <div
          class="news-grid"
          id="newsGrid"
          data-results-single="<?= $e($text($filtersCopy, 'results_suffix_single', 'article')) ?>"
          data-results-plural="<?= $e($text($filtersCopy, 'results_suffix_plural', 'articles')) ?>"
          data-showing-label="Showing"
          data-of-label="of"
          data-all-label="All"
          data-shown-label="shown"
        >
          <?php foreach ($articles as $article): ?>
          <?php
              $categorySlug = (string)($article['category_slug'] ?? '');
              $categoryName = (string)($article['category_name'] ?? 'News');
              $image = $articleImage($article);
              $dateAttr = $articleDateAttr($article);
          ?>
          <article class="news-card" data-category="<?= $e($categorySlug) ?>" data-date="<?= $e($dateAttr) ?>" data-title="<?= $e(strtolower((string)($article['title'] ?? ''))) ?>">
            <div class="news-card-img">
              <?php if ($image !== ''): ?>
              <img src="<?= $e($image) ?>" alt="<?= $e($article['featured_image_alt'] ?? $article['title'] ?? 'News article') ?>" loading="lazy" onerror="this.style.display='none'">
              <?php endif; ?>
              <span class="news-cat-badge <?= $e($categoryClass($categorySlug)) ?>"><?= $e($categoryName) ?></span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="<?= $e($dateAttr) ?>"><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= $e($articleDate($article)) ?></time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $e($article['read_time'] ?? '3 min') ?></span>
              </div>
              <h3 class="news-card-title"><?= $e($article['title'] ?? '') ?></h3>
              <p class="news-card-excerpt"><?= $e($article['excerpt'] ?? '') ?></p>
              <a href="<?= $e($articleUrl($article)) ?>" class="news-card-link"><?= $e($text($listingCopy, 'read_more_label', 'Read More')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>
          <?php endforeach; ?>

          <div class="news-empty" id="newsEmpty" role="status" aria-live="polite">
            <div class="news-empty-icon" aria-hidden="true">
              <i class="fa-solid fa-newspaper"></i>
            </div>
            <h3><?= $e($text($listingCopy, 'empty_title', 'No Articles Found')) ?></h3>
            <p><?= $e($text($listingCopy, 'empty_text', 'No articles match your current search or filter. Try a different keyword or category.')) ?></p>
            <button class="news-empty-reset" id="emptyReset" type="button">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> <?= $e($text($listingCopy, 'empty_reset_label', 'Clear Filters')) ?>
            </button>
          </div>
        </div>

        <div class="news-load-more-wrap">
          <button class="news-load-more-btn" id="loadMoreBtn" type="button">
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            <?= $e($text($listingCopy, 'load_more_label', 'Load More Articles')) ?>
          </button>
          <span class="news-load-more-label" id="loadMoreLabel" aria-live="polite"></span>
        </div>
      </div>
    </section>

    <section class="news-cta-strip" aria-label="Stay updated">
      <div class="container">
        <div class="news-cta-inner">
          <div class="news-cta-text">
            <h2><?= $e($text($cta, 'title_prefix', 'Stay')) ?> <span><?= $e($text($cta, 'title_highlight', 'Up to Date')) ?></span></h2>
            <p><?= $e($text($cta, 'subtitle', 'Follow the programme on social media or apply for housing directly through the eCitizen portal to receive official notifications about unit availability and beneficiary selection.')) ?></p>
          </div>
          <div class="news-cta-actions">
            <div class="news-cta-social" aria-label="Follow us on social media">
              <a href="<?= $e($text($cta, 'x_url', '#')) ?>" aria-label="Follow us on X / Twitter">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
              <a href="<?= $e($text($cta, 'facebook_url', '#')) ?>" aria-label="Follow us on Facebook">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a href="<?= $e($text($cta, 'youtube_url', '#')) ?>" aria-label="Watch us on YouTube">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.08C5.12 19.54 12 19.54 12 19.54s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2A29 29 0 0 0 23 11.75a29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
              </a>
            </div>
            <a href="<?= $e($text($cta, 'button_url', 'https://ecitizen.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="news-cta-ecitizen">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              <?= $e($text($cta, 'button_label', 'Apply via eCitizen')) ?>
            </a>
          </div>
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
