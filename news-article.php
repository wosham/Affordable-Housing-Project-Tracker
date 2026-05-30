<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$basePath = '';
$activePage = 'news';
$slug = trim((string)($_GET['id'] ?? $_GET['slug'] ?? ''));
$article = $slug !== '' ? NewsArticle::findBySlug($slug) : NewsArticle::featured();

if (!$article) {
    http_response_code(404);
    $pageTitle = 'News Article Not Found | Trans-Nzoia County AHP Tracker';
    $pageDescription = 'The requested news article could not be found.';
    $pageKeywords = 'Trans-Nzoia news';
    $pageAuthor = 'Trans-Nzoia County Government';
    $pageRobots = 'noindex, follow';
    $themeColor = '#163300';
    $canonicalUrl = 'https://housing.transnzoia.go.ke/news.php';
    $pageStyles = ['assets/css/global.css', 'assets/css/pages/news.css'];
    $pageScripts = ['assets/js/global.js'];
    include __DIR__ . '/app/partials/head.php';
    ?>
    <body>
    <?php include __DIR__ . '/app/partials/navbar.php'; ?>
    <main id="main-content">
      <section class="news-grid-section">
        <div class="container">
          <div class="news-empty is-visible">
            <div class="news-empty-icon" aria-hidden="true"><i class="fa-solid fa-newspaper"></i></div>
            <h1>Article not found</h1>
            <p>The article may be unpublished, archived or no longer available.</p>
            <a class="news-empty-reset" href="news.php">Back to News</a>
          </div>
        </div>
      </section>
    </main>
    <?php include __DIR__ . '/app/partials/footer.php'; ?>
    <?php include __DIR__ . '/app/partials/scripts.php'; ?>
    </body></html>
    <?php
    exit;
}

Database::query('UPDATE news_articles SET views = COALESCE(views, 0) + 1 WHERE id = ?', [(int)$article['id']]);

$e = static fn (mixed $value): string => Security::e($value);
$articleAsset = static function (mixed $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    return preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) ? $path : Url::asset($path);
};
$image = $articleAsset($article['featured_image_path'] ?? $article['featured_image_url'] ?? '');
$ogImage = $articleAsset($article['og_image_path'] ?? $article['og_image_url'] ?? $image);
$publishedAt = strtotime((string)($article['published_at'] ?? $article['created_at'] ?? ''));
$publishedIso = $publishedAt === false ? '' : date('c', $publishedAt);
$publishedLabel = $publishedAt === false ? '' : date('j M Y', $publishedAt);
$authorName = trim((string)($article['author_first_name'] ?? '') . ' ' . (string)($article['author_last_name'] ?? ''));
$authorName = $authorName !== '' ? $authorName : 'Trans-Nzoia County Department of Land, Housing & Physical Planning';
$categoryName = (string)($article['category_name'] ?? 'News');
$body = trim((string)($article['body'] ?? ''));
$attachmentUrl = $articleAsset($article['attachment_path'] ?? $article['attachment_url'] ?? '');
$externalUrl = trim((string)($article['external_url'] ?? ''));
$related = NewsArticle::withRelations(['category' => (string)($article['category_slug'] ?? '')], 4);
$related = array_values(array_filter($related, static fn (array $item): bool => (int)$item['id'] !== (int)$article['id']));
$shortTitle = strlen((string)$article['title']) > 58 ? rtrim(substr((string)$article['title'], 0, 55)) . '...' : (string)$article['title'];

$pageTitle = ($article['seo_title'] ?: $article['title']) . ' | Trans-Nzoia County AHP Tracker';
$pageDescription = $article['seo_description'] ?: ($article['excerpt'] ?? '');
$pageKeywords = 'Trans-Nzoia housing news, affordable housing, ' . $categoryName;
$pageAuthor = $authorName;
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/news-article.php?id=' . rawurlencode((string)$article['slug']);
$pageStyles = [
    'assets/css/global.css',
    'assets/css/pages/news.css',
    'assets/css/pages/news-article.css',
];
$pageScripts = [
    'assets/js/global.js',
    'assets/js/pages/news-article.js',
];
$headMeta = [
    '<meta property="og:title" content="' . $e($article['title']) . '">',
    '<meta property="og:description" content="' . $e($pageDescription) . '">',
    '<meta property="og:type" content="article">',
    '<meta property="og:url" content="' . $e($canonicalUrl) . '">',
    $ogImage !== '' ? '<meta property="og:image" content="' . $e($ogImage) . '">' : '',
    '<meta property="og:locale" content="en_KE">',
    '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
    '<meta property="article:published_time" content="' . $e($publishedIso) . '">',
    '<meta property="article:section" content="' . $e($categoryName) . '">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta name="twitter:title" content="' . $e($article['title']) . '">',
];
$headMeta = array_filter($headMeta);

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">

    <section class="article-hero" aria-label="Article header">
      <div class="article-hero-bg" aria-hidden="true"></div>
      <div class="article-hero-glow" aria-hidden="true"></div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <a href="news.php" class="breadcrumb-link">News &amp; Updates</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page"><?= $e($shortTitle) ?></span>
        </nav>

        <div class="article-hero-layout">
          <div class="article-hero-content">
            <span class="news-cat-badge news-cat--<?= $e($article['category_slug'] ?? 'programme-updates') ?>"><?= $e($categoryName) ?></span>
            <h1 class="article-headline"><?= $e($article['title']) ?></h1>
            <p class="article-deck"><?= $e($article['excerpt'] ?? '') ?></p>
            <div class="article-meta" aria-label="Article information">
              <span class="article-meta-item is-author"><i class="fa-solid fa-building-columns" aria-hidden="true"></i><?= $e($authorName) ?></span>
              <span class="article-meta-divider" aria-hidden="true"></span>
              <span class="article-meta-item"><i class="fa-regular fa-calendar" aria-hidden="true"></i><time datetime="<?= $e($publishedIso) ?>"><?= $e($publishedLabel) ?></time></span>
              <span class="article-meta-divider" aria-hidden="true"></span>
              <span class="article-meta-item"><i class="fa-regular fa-clock" aria-hidden="true"></i><span id="articleReadTime"><?= $e($article['read_time'] ?: '4 min') ?></span></span>
            </div>
          </div>

          <div class="article-hero-media" aria-label="Article featured image">
            <div class="article-hero-img-frame">
              <div class="article-hero-img-ph" aria-hidden="true"><div class="article-hero-img-ph-inner"><i class="fa-solid fa-newspaper"></i><span><?= $e($categoryName) ?></span></div></div>
<?php if ($image !== ''): ?>
              <img src="<?= $e($image) ?>" alt="<?= $e($article['featured_image_alt'] ?? $article['title']) ?>" loading="eager" onerror="this.style.display='none'">
<?php endif; ?>
            </div>
<?php if (!empty($article['image_caption'])): ?>
            <div class="article-hero-media-caption"><i class="fa-solid fa-camera" aria-hidden="true"></i><?= $e($article['image_caption']) ?></div>
<?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <div class="article-body-wrap">
      <div class="container">
        <div class="article-layout">
          <div>
            <article class="article-prose" id="articleContent">
              <?= $body ?>
<?php if ($attachmentUrl !== '' || $externalUrl !== ''): ?>
              <div class="article-downloads">
<?php if ($attachmentUrl !== ''): ?>
                <a href="<?= $e($attachmentUrl) ?>" class="news-featured-cta" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Download Report</a>
<?php endif; ?>
<?php if ($externalUrl !== ''): ?>
                <a href="<?= $e($externalUrl) ?>" class="news-featured-cta" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open Source Link</a>
<?php endif; ?>
              </div>
<?php endif; ?>
            </article>
          </div>

          <aside class="article-sidebar" aria-label="Article side information">
            <div class="sidebar-card">
              <div class="sidebar-card-head"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Article Details</div>
              <div class="sidebar-card-body">
                <div class="key-fact-row"><span class="key-fact-icon"><i class="fa-solid fa-folder" aria-hidden="true"></i></span><span><span class="key-fact-label">Category</span><strong class="key-fact-value"><?= $e($categoryName) ?></strong></span></div>
                <div class="key-fact-row"><span class="key-fact-icon"><i class="fa-solid <?= $e(NewsArticle::formatIcon($article['post_format'] ?? 'article')) ?>" aria-hidden="true"></i></span><span><span class="key-fact-label">Format</span><strong class="key-fact-value"><?= $e(NewsArticle::formatLabel($article['post_format'] ?? 'article')) ?></strong></span></div>
                <div class="key-fact-row"><span class="key-fact-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span><span><span class="key-fact-label">Published</span><strong class="key-fact-value"><?= $e($publishedLabel) ?></strong></span></div>
              </div>
            </div>
            <div class="sidebar-card">
              <div class="sidebar-card-head"><i class="fa-solid fa-link" aria-hidden="true"></i> Related Stories</div>
              <div class="sidebar-card-body">
<?php foreach (array_slice($related, 0, 3) as $item): ?>
                <a class="article-related-link" href="news-article.php?id=<?= $e(rawurlencode((string)$item['slug'])) ?>"><?= $e($item['title']) ?></a>
<?php endforeach; ?>
                <a class="article-related-link" href="news.php">View all news</a>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </div>

  </main>

<?php include __DIR__ . '/app/partials/footer.php'; ?>
<?php include __DIR__ . '/app/partials/back-to-top.php'; ?>
<?php include __DIR__ . '/app/partials/mobile-menu.php'; ?>
<?php include __DIR__ . '/app/partials/scripts.php'; ?>
</body>
</html>
