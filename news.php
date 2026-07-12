<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'news';

$cmsPage = CmsLoader::page('news');

$hero = CmsLoader::content($cmsPage, 'news_hero', [
    'background_image' => 'uploads/heroes/hero-main.jpg',
    'background_alt' => 'Affordable housing programme news and progress updates in Trans-Nzoia County',
    'eyebrow' => 'News & Updates',
    'title_plain_1' => 'Latest',
    'title_accent' => 'News &',
    'title_plain_2' => 'Announcements',
    'subtitle' => 'Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.',
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
    'button_label' => 'Apply via eCitizen',
    'button_url' => 'https://ecitizen.go.ke',
]);

$featuredArticle = NewsArticle::featured();
$articles = NewsArticle::withRelations(['exclude_featured' => true]);
$categories = NewsArticle::publicCategories();
$stats = NewsArticle::publicStats();

$listingCount = count($articles);
$totalArticleCount = max((int)($stats['total_articles'] ?? 0), $listingCount + ($featuredArticle ? 1 : 0));
$categoryCount = max((int)($stats['total_categories'] ?? 0), count($categories));
$lastUpdated = $stats['last_published_at'] ?? null;
$newsHeroImage = CmsLoader::text($hero, 'background_image', trim((string)($cmsPage['hero_image'] ?? '')) ?: 'uploads/heroes/hero-main.jpg');
$newsHeroAlt = CmsLoader::text($hero, 'background_alt', 'Affordable housing programme news and progress updates in Trans-Nzoia County');

$pageTitle = $cmsPage['seo_title'] ?? 'News & Updates | Trans-Nzoia County AHP Tracker';
$pageDescription = $cmsPage['seo_description'] ?? 'Latest news, official announcements, construction progress reports, and community updates from the Trans-Nzoia County Affordable Housing Programme.';
$pageKeywords = $cmsPage['seo_keywords'] ?? 'Trans-Nzoia housing news, AHP announcements, affordable housing Kenya, county housing updates';
$pageAuthor = 'Trans-Nzoia County Government - Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = trim((string)($cmsPage['canonical_url'] ?? '')) ?: Url::canonical('news.php');
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/news.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/news.js',
];

$newsEscape = static fn (mixed $value): string => Security::e($value);
$newsText = static fn (array $content, string $key, string $default = ''): string => CmsLoader::text($content, $key, $default);
$newsAsset = static function (?string $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^https?://#i', $path) ? $path : $path;
};
$newsArticleUrl = static function (array $article): string {
    $slug = trim((string)($article['slug'] ?? ''));
    return 'news-article.php' . ($slug !== '' ? '?id=' . rawurlencode($slug) : '');
};
$newsArticleImage = static function (array $article) use ($newsAsset): string {
    return $newsAsset($article['featured_image_path'] ?? $article['featured_image_url'] ?? '');
};
$newsArticleDate = static function (array $article, string $format = 'j M Y'): string {
    $date = (string)($article['published_at'] ?? $article['created_at'] ?? '');
    $timestamp = strtotime($date);
    return $timestamp === false ? '' : date($format, $timestamp);
};
$newsArticleDateAttr = static function (array $article): string {
    $date = (string)($article['published_at'] ?? $article['created_at'] ?? '');
    $timestamp = strtotime($date);
    return $timestamp === false ? '' : date('Y-m-d', $timestamp);
};
$newsLastUpdatedLabel = static function (?string $date): string {
    $timestamp = strtotime((string)$date);
    return $timestamp === false ? '-' : date("M 'y", $timestamp);
};
$newsCategoryClass = static function (?string $slug): string {
    $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$slug));
    return 'news-cat--' . ($slug !== '' ? $slug : 'programme-updates');
};
$newsAuthorName = static function (array $article): string {
    $name = trim((string)($article['author_first_name'] ?? '') . ' ' . (string)($article['author_last_name'] ?? ''));
    return $name !== '' ? $name : 'Trans-Nzoia County Department of Land, Housing & Physical Planning';
};

$headMeta = [
    '<meta name="geo.region" content="KE-36">',
    '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
    '<meta property="og:title" content="' . $newsEscape($pageTitle) . '">',
    '<meta property="og:description" content="' . $newsEscape($pageDescription) . '">',
    '<meta property="og:type" content="website">',
    '<meta property="og:url" content="' . $newsEscape($canonicalUrl) . '">',
    '<meta property="og:image" content="' . $newsEscape(Url::asset($newsHeroImage)) . '">',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . $newsEscape($pageTitle) . '">',
];

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

    <section class="news-hero" aria-label="News and updates">
      <div class="news-hero-bg" aria-hidden="true"><img <?= public_image_attrs($newsHeroImage, $newsHeroAlt, ['loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'news-hero-image', 'onerror' => "this.style.display='none'"]) ?>></div>
      <div class="news-hero-glow" aria-hidden="true"></div>
      <div class="container">
        <div class="news-hero-inner">
          <div class="news-hero-layout">
            <div class="news-hero-left">
              <div class="news-hero-badge">
                <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                <?= $newsEscape($newsText($hero, 'eyebrow', 'News & Updates')) ?>
              </div>
              <h1 class="news-hero-title">
                <span class="title-plain"><?= $newsEscape($newsText($hero, 'title_plain_1', 'Latest')) ?> </span><span class="title-accent"><?= $newsEscape($newsText($hero, 'title_accent', 'News &')) ?></span><br>
                <span class="title-plain"><?= $newsEscape($newsText($hero, 'title_plain_2', 'Announcements')) ?></span>
              </h1>
              <p class="news-hero-sub"><?= $newsEscape($newsText($hero, 'subtitle', 'Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.')) ?></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="news-featured-section" aria-label="Featured article">
      <div class="container">
        <p class="news-section-label"><i class="fa-solid fa-star" aria-hidden="true"></i> <?= $newsEscape($newsText($featuredCopy, 'section_label', 'Featured Story')) ?></p>
        <?php if ($featuredArticle): ?>
        <?php
            $featuredImage = $newsArticleImage($featuredArticle);
            $featuredCategory = (string)($featuredArticle['category_slug'] ?? '');
            $featuredCategoryName = (string)($featuredArticle['category_name'] ?? 'Programme Updates');
        ?>
        <article class="news-featured">
          <div class="news-featured-img">
            <div class="news-featured-img-placeholder" aria-hidden="true">
              <i class="fa-solid fa-newspaper"></i>
              <span><?= $newsEscape($featuredCategoryName) ?></span>
            </div>
            <?php if ($featuredImage !== ''): ?>
            <img <?= public_image_attrs($featuredImage, $featuredArticle['featured_image_alt'] ?? $featuredArticle['title'] ?? 'Featured article', ['loading' => 'eager', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
            <?php endif; ?>
            <div class="news-featured-img-overlay" aria-hidden="true"></div>
            <span class="news-cat-badge <?= $newsEscape($newsCategoryClass($featuredCategory)) ?>"><?= $newsEscape($featuredCategoryName) ?></span>
            <span class="news-featured-label-badge"><i class="fa-solid fa-star" aria-hidden="true"></i> <?= $newsEscape($newsText($featuredCopy, 'featured_badge', 'Featured')) ?></span>
          </div>
          <div class="news-featured-body">
            <div class="news-featured-meta">
              <time class="news-featured-date" datetime="<?= $newsEscape($newsArticleDateAttr($featuredArticle)) ?>">
                <i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= $newsEscape($newsArticleDate($featuredArticle)) ?>
              </time>
              <span class="news-featured-read">
                <i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $newsEscape($featuredArticle['read_time'] ?? '5 min read') ?>
              </span>
            </div>
            <h2 class="news-featured-title"><?= $newsEscape($featuredArticle['title'] ?? '') ?></h2>
            <p class="news-featured-excerpt"><?= $newsEscape($featuredArticle['excerpt'] ?? '') ?></p>
            <div class="news-featured-author">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
              <?= $newsEscape($newsAuthorName($featuredArticle)) ?>
            </div>
            <a href="<?= $newsEscape($newsArticleUrl($featuredArticle)) ?>" class="news-featured-cta">
              <?= $newsEscape($newsText($featuredCopy, 'read_button_label', 'Read Full Article')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </article>
        <?php else: ?>
        <div class="news-empty is-visible" role="status">
          <div class="news-empty-icon" aria-hidden="true"><i class="fa-solid fa-star"></i></div>
          <h3><?= $newsEscape($newsText($featuredCopy, 'empty_title', 'Featured story coming soon')) ?></h3>
          <p><?= $newsEscape($newsText($featuredCopy, 'empty_text', 'Mark a published news post as featured to show it here.')) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <div class="news-filter-bar" role="toolbar" aria-label="Filter articles by category">
      <div class="container">
        <div class="news-filter-inner">
          <div class="news-filter-pills" role="group" aria-label="Category filters">
            <button class="news-filter-pill is-active" data-category="all" type="button"><span class="pill-dot"></span><?= $newsEscape($newsText($filtersCopy, 'all_label', 'All Articles')) ?></button>
            <?php foreach ($categories as $category): ?>
            <button class="news-filter-pill" data-category="<?= $newsEscape($category['slug'] ?? '') ?>" type="button"><span class="pill-dot"></span><?= $newsEscape($category['name'] ?? 'News') ?></button>
            <?php endforeach; ?>
          </div>
          <div class="news-filter-right">
            <span class="news-results-count" id="newsResultsCount" aria-live="polite" aria-atomic="true"><?= $newsEscape(format_number($listingCount) . ' ' . $newsText($filtersCopy, $listingCount === 1 ? 'results_suffix_single' : 'results_suffix_plural', $listingCount === 1 ? 'article' : 'articles')) ?></span>
            <select class="news-sort-select" id="newsSort" aria-label="Sort articles">
              <option value="latest"><?= $newsEscape($newsText($filtersCopy, 'latest_label', 'Latest First')) ?></option>
              <option value="oldest"><?= $newsEscape($newsText($filtersCopy, 'oldest_label', 'Oldest First')) ?></option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <section class="news-grid-section" aria-label="News articles">
      <div class="container">
        <div class="news-grid-header">
          <h2 class="news-grid-title"><?= $newsEscape($newsText($listingCopy, 'title', 'All Articles')) ?></h2>
          <span class="news-grid-subtitle" id="newsGridSubtitle"><?= $newsEscape($newsText($listingCopy, 'subtitle', 'Showing latest news & updates')) ?></span>
        </div>
        <div
          class="news-grid"
          id="newsGrid"
          data-results-single="<?= $newsEscape($newsText($filtersCopy, 'results_suffix_single', 'article')) ?>"
          data-results-plural="<?= $newsEscape($newsText($filtersCopy, 'results_suffix_plural', 'articles')) ?>"
          data-showing-label="Showing"
          data-of-label="of"
          data-all-label="All"
          data-shown-label="shown"
        >
          <?php foreach ($articles as $article): ?>
          <?php
              $categorySlug = (string)($article['category_slug'] ?? '');
              $categoryName = (string)($article['category_name'] ?? 'News');
              $image = $newsArticleImage($article);
              $dateAttr = $newsArticleDateAttr($article);
          ?>
          <article class="news-card" data-category="<?= $newsEscape($categorySlug) ?>" data-date="<?= $newsEscape($dateAttr) ?>" data-title="<?= $newsEscape(strtolower((string)($article['title'] ?? ''))) ?>">
            <div class="news-card-img">
              <div class="news-card-img-placeholder" aria-hidden="true">
                <i class="fa-solid <?= $newsEscape(NewsArticle::formatIcon($article['post_format'] ?? 'article')) ?>"></i>
                <span><?= $newsEscape($categoryName) ?></span>
              </div>
              <?php if ($image !== ''): ?>
              <img <?= public_image_attrs($image, $article['featured_image_alt'] ?? $article['title'] ?? 'News article', ['onerror' => "this.style.display='none'"]) ?>>
              <?php endif; ?>
              <span class="news-cat-badge <?= $newsEscape($newsCategoryClass($categorySlug)) ?>"><?= $newsEscape($categoryName) ?></span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="<?= $newsEscape($dateAttr) ?>"><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= $newsEscape($newsArticleDate($article)) ?></time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $newsEscape($article['read_time'] ?? '3 min') ?></span>
              </div>
              <h3 class="news-card-title"><?= $newsEscape($article['title'] ?? '') ?></h3>
              <p class="news-card-excerpt"><?= $newsEscape($article['excerpt'] ?? '') ?></p>
              <a href="<?= $newsEscape($newsArticleUrl($article)) ?>" class="news-card-link"><?= $newsEscape($newsText($listingCopy, 'read_more_label', 'Read More')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>
          <?php endforeach; ?>

          <div class="news-empty" id="newsEmpty" role="status" aria-live="polite">
            <div class="news-empty-icon" aria-hidden="true">
              <i class="fa-solid fa-newspaper"></i>
            </div>
            <h3><?= $newsEscape($newsText($listingCopy, 'empty_title', 'No Articles Found')) ?></h3>
            <p><?= $newsEscape($newsText($listingCopy, 'empty_text', 'No articles match your current search or filter. Try a different keyword or category.')) ?></p>
            <button class="news-empty-reset" id="emptyReset" type="button">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> <?= $newsEscape($newsText($listingCopy, 'empty_reset_label', 'Clear Filters')) ?>
            </button>
          </div>
        </div>

        <div class="news-load-more-wrap">
          <button class="news-load-more-btn" id="loadMoreBtn" type="button">
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            <?= $newsEscape($newsText($listingCopy, 'load_more_label', 'Load More Articles')) ?>
          </button>
          <span class="news-load-more-label" id="loadMoreLabel" aria-live="polite"></span>
        </div>
      </div>
    </section>

    <section class="news-cta-strip" aria-label="Stay updated">
      <div class="container">
        <div class="news-cta-inner">
          <div class="news-cta-text">
            <h2><?= $newsEscape($newsText($cta, 'title_prefix', 'Stay')) ?> <span><?= $newsEscape($newsText($cta, 'title_highlight', 'Up to Date')) ?></span></h2>
            <p><?= $newsEscape($newsText($cta, 'subtitle', 'Follow the programme on social media or apply for housing directly through the eCitizen portal to receive official notifications about unit availability and beneficiary selection.')) ?></p>
          </div>
          <div class="news-cta-actions">
            <form class="news-subscribe-form" data-news-subscribe action="<?= $newsEscape(Url::to('api/public/subscribe.php')) ?>" method="post" novalidate>
              <input type="text" name="_gotcha" tabindex="-1" autocomplete="off" aria-hidden="true" hidden>
              <label>
                <span class="visually-hidden">Email address</span>
                <input type="email" name="email" placeholder="Email for programme updates" required>
              </label>
              <button type="submit"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Subscribe</button>
              <small data-news-subscribe-message aria-live="polite"></small>
            </form>
            <a href="<?= $newsEscape($newsText($cta, 'button_url', 'https://ecitizen.go.ke')) ?>" target="_blank" rel="noopener noreferrer" class="news-cta-ecitizen">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              <?= $newsEscape($newsText($cta, 'button_label', 'Apply via eCitizen')) ?>
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
