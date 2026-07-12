<?php
declare(strict_types=1);

require_once __DIR__ . '/app/core/bootstrap.php';
header_remove('X-Powered-By');

$basePath = '';
$activePage = 'sitemap';
$groups = SitemapBuilder::grouped();
$entries = SitemapBuilder::entries();
$xmlPreview = SitemapBuilder::xml();
$pageCount = count($entries);
$groupCount = count($groups);
$lastUpdated = date('Y');

foreach ($entries as $entry) {
    $lastmod = strtotime((string)($entry['lastmod'] ?? ''));
    if ($lastmod !== false) {
        $lastUpdated = date('Y-m-d', max(strtotime((string)$lastUpdated) ?: 0, $lastmod));
    }
}

$pageTitle = 'Site Map | Trans-Nzoia AHP Tracker';
$pageDescription = 'Complete public site map for the Trans-Nzoia Affordable Housing Programme Tracker.';
$pageKeywords = '';
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = Url::canonical('sitemap.php');
$pageStyles = [
    'assets/css/pages/sitemap.css',
];
$pageScripts = [
    'assets/js/pages/sitemap.js',
];
$headMeta = [
    '<meta property="og:title" content="Site Map | Trans-Nzoia AHP Tracker">',
    '<meta property="og:description" content="Find public pages, projects, constituencies, news and legal resources.">',
    '<meta property="og:type" content="website">',
];

$cardSkins = [
    'Main Pages' => 'green',
    'Programme' => 'blue',
    'Projects' => 'teal',
    'Constituencies' => 'amber',
    'News' => 'pink',
    'Legal' => 'neutral',
    'Utility' => 'neutral',
];
$cardIcons = [
    'Main Pages' => 'fa-globe',
    'Programme' => 'fa-circle-info',
    'Projects' => 'fa-building-columns',
    'Constituencies' => 'fa-map-location-dot',
    'News' => 'fa-newspaper',
    'Legal' => 'fa-scale-balanced',
    'Utility' => 'fa-sitemap',
];

include __DIR__ . '/app/partials/head.php';
?>
<body>
<?php include __DIR__ . '/app/partials/cursor.php'; ?>
<?php include __DIR__ . '/app/partials/skip-link.php'; ?>
<?php include __DIR__ . '/app/partials/navbar.php'; ?>
<main id="main-content">
  <section class="sm-hero" aria-label="Site map overview">
    <div class="sm-hero-bg" aria-hidden="true">
      <div class="sm-hero-overlay"></div>
      <div class="sm-hero-grid"></div>
    </div>
    <div class="container">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= Security::e(Url::to('index.php')) ?>" class="breadcrumb-link">Home</a>
        <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
        <span class="breadcrumb-current" aria-current="page">Site Map</span>
      </nav>
      <div class="sm-hero-body">
        <div class="sm-hero-eyebrow">
          <i class="fa-solid fa-sitemap" aria-hidden="true"></i> Site Map
        </div>
        <h1 class="sm-hero-title">Everything Public in One Place</h1>
        <p class="sm-hero-sub">Browse live public pages, project details, constituency pages, news articles and legal resources from the current site data.</p>
        <div class="sm-search-wrap">
          <i class="fa-solid fa-magnifying-glass sm-search-icon" aria-hidden="true"></i>
          <input type="search" id="smSearch" class="sm-search-input" placeholder="Search pages, projects, news..." aria-label="Search site map">
          <button class="sm-search-clear" id="smSearchClear" aria-label="Clear search" hidden>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>
        <div class="sm-hero-stats">
          <div class="sm-stat"><span class="sm-stat-num" id="smLiveCount"><?= Security::e((string)$pageCount) ?></span><span class="sm-stat-lbl">URLs</span></div>
          <div class="sm-stat-div" aria-hidden="true"></div>
          <div class="sm-stat"><span class="sm-stat-num"><?= Security::e((string)$groupCount) ?></span><span class="sm-stat-lbl">Groups</span></div>
          <div class="sm-stat-div" aria-hidden="true"></div>
          <div class="sm-stat"><span class="sm-stat-num"><?= Security::e((string)$lastUpdated) ?></span><span class="sm-stat-lbl">Updated</span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="sm-grid-section" aria-labelledby="sm-grid-heading">
    <div class="container">
      <div class="sm-section-header fade-up">
        <h2 class="sm-section-title" id="sm-grid-heading">Public Page Directory</h2>
        <p class="sm-section-sub">Only public, indexable routes are shown. Admin, API, error, maintenance and offline pages are excluded.</p>
      </div>

      <div class="sm-no-results" id="smNoResults" hidden>
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <p>No pages found for <strong id="smNoResultsQuery"></strong>.</p>
        <button class="sm-inline-btn" id="smClearSearch">Clear search</button>
      </div>

      <div class="sm-grid" id="smGrid">
        <?php foreach ($groups as $groupName => $items): ?>
          <?php $skin = $cardSkins[$groupName] ?? 'neutral'; $icon = $cardIcons[$groupName] ?? 'fa-link'; ?>
          <article class="sm-card fade-up" data-section="<?= Security::e(strtolower((string)$groupName)) ?>" data-count="<?= count($items) ?>">
            <div class="sm-card-header sm-card-header--<?= Security::e($skin) ?>">
              <div class="sm-card-icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title"><?= Security::e((string)$groupName) ?></h3>
                <span class="sm-card-count"><?= Security::e(format_number(count($items))) ?> URLs</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <?php foreach ($items as $entry): ?>
                <li data-page="<?= Security::e($entry['label']) ?>" data-desc="<?= Security::e($entry['description']) ?>">
                  <a href="<?= Security::e($entry['url']) ?>" class="sm-link">
                    <span class="sm-link-icon"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
                    <span class="sm-link-body">
                      <span class="sm-link-name"><?= Security::e($entry['label']) ?></span>
                      <span class="sm-link-desc"><?= Security::e($entry['description'] !== '' ? $entry['description'] : $entry['path']) ?></span>
                    </span>
                    <span class="sm-badge sm-badge--live">Live</span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="sm-table-section fade-up" aria-labelledby="sm-table-heading">
    <div class="container">
      <div class="sm-section-header">
        <h2 class="sm-section-title" id="sm-table-heading">All URLs at a Glance</h2>
        <p class="sm-section-sub">Sortable list generated from the same live source as the XML sitemap.</p>
      </div>
      <div class="sm-table-wrap">
        <table class="sm-table" id="smTable" aria-label="All public sitemap URLs">
          <thead>
            <tr>
              <th class="sm-th sortable" data-col="0" aria-sort="none" scope="col">Page Name <i class="fa-solid fa-sort sm-sort-icon" aria-hidden="true"></i></th>
              <th class="sm-th sortable" data-col="1" aria-sort="none" scope="col">Group <i class="fa-solid fa-sort sm-sort-icon" aria-hidden="true"></i></th>
              <th class="sm-th sortable" data-col="2" aria-sort="none" scope="col">Status <i class="fa-solid fa-sort sm-sort-icon" aria-hidden="true"></i></th>
              <th class="sm-th" scope="col">URL</th>
            </tr>
          </thead>
          <tbody id="smTableBody">
            <?php foreach ($entries as $entry): ?>
              <tr data-page="<?= Security::e($entry['label']) ?>" data-section="<?= Security::e($entry['group']) ?>" data-status="live">
                <td><?= Security::e($entry['label']) ?></td>
                <td><?= Security::e($entry['group']) ?></td>
                <td><span class="sm-badge sm-badge--live">Live</span></td>
                <td><a href="<?= Security::e($entry['url']) ?>" class="sm-tbl-link"><?= Security::e($entry['path'] === '' ? '/' : $entry['path']) ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="sm-xml-section fade-up" aria-labelledby="sm-xml-heading">
    <div class="container">
      <div class="sm-xml-wrap">
        <div class="sm-xml-header">
          <div class="sm-xml-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></div>
          <div>
            <h2 class="sm-xml-title" id="sm-xml-heading">XML Sitemap</h2>
            <p class="sm-xml-sub">Machine-readable sitemap generated from live public content.</p>
          </div>
          <div class="sm-xml-actions">
            <button class="sm-xml-btn sm-xml-btn--copy" id="smCopyXml" aria-label="Copy XML sitemap to clipboard">
              <i class="fa-solid fa-copy" aria-hidden="true"></i> Copy
            </button>
            <a href="<?= Security::e(Url::to('sitemap.xml')) ?>" class="sm-xml-btn sm-xml-btn--download" aria-label="Open sitemap.xml">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open
            </a>
          </div>
        </div>
        <div class="sm-xml-code-wrap">
          <pre class="sm-xml-code" id="smXmlContent" aria-label="XML sitemap preview"><code><?= Security::e($xmlPreview) ?></code></pre>
        </div>
        <div class="sm-xml-copy-toast" id="smCopyToast" aria-live="polite" hidden>
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Copied to clipboard!
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
