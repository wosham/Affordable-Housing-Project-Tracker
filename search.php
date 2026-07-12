<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$initialQuery = trim((string)($_GET['q'] ?? ''));
$initialType = strtolower(trim((string)($_GET['type'] ?? 'all')));
$validTypes = array_merge(['all'], PublicSearchService::availableTypes());
if (!in_array($initialType, $validTypes, true)) {
    $initialType = 'all';
}

$pageTitle = 'Search | ' . public_setting('site_short_name', 'AHP Tracker');
$pageDescription = 'Search public projects, news, FAQs, gallery media, and constituency records for the Trans-Nzoia Affordable Housing Programme.';
$pageKeywords = 'Trans-Nzoia affordable housing search, projects, news, constituencies, FAQ, gallery';
$pageRobots = $initialQuery !== '' ? 'noindex, follow' : 'index, follow';
$canonicalUrl = Url::canonical('search.php');
$activePage = 'search';
$pageStyles = ['assets/css/pages/search.css'];
$pageScripts = ['assets/js/pages/search.js'];
$basePath = Url::basePath();

$typeLabels = [
    'all' => 'All',
    'projects' => 'Projects',
    'news' => 'News',
    'faq' => 'FAQ',
    'gallery' => 'Gallery',
    'constituencies' => 'Constituencies',
];
?>
<?php include __DIR__ . '/app/partials/head.php'; ?>
<body class="search-page">
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>

<main id="main-content"
      class="search-shell"
      data-search-page
      data-api-url="<?= Security::e(Url::to('api/public/search.php')) ?>"
      data-initial-query="<?= Security::e($initialQuery) ?>"
      data-initial-type="<?= Security::e($initialType) ?>">
  <section class="search-hero" aria-labelledby="search-title">
    <div class="search-hero__inner">
      <nav class="search-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= Security::e(Url::to('index.php')) ?>">Home</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>Search</span>
      </nav>

      <div class="search-heading">
        <p class="search-kicker">Public Discovery</p>
        <h1 id="search-title">Search the AHP Tracker</h1>
        <p>Find public project records, published updates, constituency pages, gallery media, and frequently asked questions.</p>
      </div>

      <form class="search-form" id="siteSearchForm" role="search">
        <label class="sr-only" for="siteSearchInput">Search public site</label>
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <input
            type="search"
            id="siteSearchInput"
            name="q"
            value="<?= Security::e($initialQuery) ?>"
            placeholder="Search projects, news, FAQ, gallery, constituencies"
            autocomplete="off"
            minlength="2">
          <button type="button" class="search-clear" id="siteSearchClear" aria-label="Clear search">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>
        <button class="search-submit" type="submit">
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          <span>Search</span>
        </button>
      </form>
    </div>
  </section>

  <section class="search-workspace" aria-label="Search results">
    <div class="search-workspace__inner">
      <aside class="search-filters" aria-label="Search categories">
        <div class="search-filters__title">
          <i class="fa-solid fa-sliders" aria-hidden="true"></i>
          <span>Filter Results</span>
        </div>
        <div class="search-tabs" id="searchTypeTabs" role="list">
<?php foreach ($typeLabels as $type => $label): ?>
          <button type="button"
                  class="search-tab<?= $initialType === $type ? ' is-active' : '' ?>"
                  data-search-type="<?= Security::e($type) ?>"
                  aria-pressed="<?= $initialType === $type ? 'true' : 'false' ?>">
            <span><?= Security::e($label) ?></span>
            <strong data-type-count="<?= Security::e($type) ?>">0</strong>
          </button>
<?php endforeach; ?>
        </div>
      </aside>

      <div class="search-results-panel">
        <div class="search-status">
          <div>
            <p class="search-status__label">Results</p>
            <h2 id="searchSummary">Type at least 2 characters to search.</h2>
          </div>
          <div class="search-spinner" id="searchSpinner" hidden>
            <i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i>
            <span>Searching</span>
          </div>
        </div>

        <div class="search-empty" id="searchEmpty">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <h3>Start with a name, location, topic, or status.</h3>
          <p>Examples: Kitale, progress report, procurement, FAQ, Saboti, gallery.</p>
        </div>

        <div class="search-results" id="searchResults" aria-live="polite"></div>

        <button class="search-load-more" type="button" id="searchLoadMore" hidden>
          <i class="fa-solid fa-plus" aria-hidden="true"></i>
          <span>Load more results</span>
        </button>
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
