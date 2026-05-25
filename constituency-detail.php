<?php
$basePath = '';
$activePage = 'constituencies';
$pageTitle = 'Constituency | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = 'Detailed affordable housing information for a Trans-Nzoia County constituency â€” projects, progress, wards and local facts.';
$pageKeywords = 'Trans-Nzoia constituency housing, AHP Kenya, affordable housing projects';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/constituency-detail.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/constituency-detail.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/constituency-detail.js'
];
$headMeta = [
  '<meta property="og:title" content="Constituency Detail â€” Trans-Nzoia AHP Tracker">',
  '<meta property="og:type" content="website">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ===== HERO (populated by JS) ===== -->
    <section class="cd-hero" id="cdHero" aria-label="Constituency overview">
      <div class="cd-hero-bg" id="cdHeroBg" aria-hidden="true">
        <div class="cd-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb" id="cdBreadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <a href="constituencies.php" class="breadcrumb-link">Constituencies</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current" id="cdBreadcrumbCurrent">Loadingâ€¦</span>
        </nav>
        <div class="cd-hero-body" id="cdHeroBody">
          <!-- populated by JS -->
        </div>
        <div class="cd-hero-kpis" id="cdHeroKpis">
          <!-- populated by JS -->
        </div>
      </div>
    </section>

    <!-- ===== WARD PILLS STRIP ===== -->
    <div class="cd-ward-strip" id="cdWardStrip" aria-label="Constituency wards">
      <div class="container">
        <!-- populated by JS -->
      </div>
    </div>

    <!-- ===== PROJECTS IN THIS CONSTITUENCY ===== -->
    <section class="cd-projects-section" aria-label="Projects in this constituency">
      <div class="container">
        <div class="cd-section-header">
          <div>
            <h2 class="cd-section-title" id="cdProjectsTitle">Projects</h2>
            <p class="cd-section-sub">All housing developments in this constituency under the national AHP programme.</p>
          </div>
          <a href="projects.php" class="cd-view-all" id="cdViewAll">
            View all projects <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div class="cd-projects-grid" id="cdProjectsGrid">
          <!-- populated by JS -->
        </div>
      </div>
    </section>

    <!-- ===== CONSTITUENCY PROGRESS ===== -->
    <section class="cd-progress-section" id="cdProgressSection" aria-label="Constituency progress overview">
      <div class="container">
        <!-- populated by JS -->
      </div>
    </section>

    <!-- ===== CONSTITUENCY FACTS + MAP ===== -->
    <section class="cd-facts-section" aria-label="Constituency facts">
      <div class="container">
        <div class="cd-facts-inner">
          <!-- Left: Facts -->
          <div class="cd-facts-col">
            <h2 class="cd-section-title">Constituency Facts</h2>
            <div class="cd-facts-grid" id="cdFactsGrid">
              <!-- populated by JS -->
            </div>
          </div>
          <!-- Right: Mini map highlight -->
          <div class="cd-mini-map-col">
            <h2 class="cd-section-title">Location</h2>
            <div class="cd-mini-map-wrap">
              <svg id="cdMiniMap" viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg" aria-label="Trans-Nzoia County map with selected constituency highlighted">
                <g class="cdm-path-group" data-id="endebess">
                  <path d="M 20,20 L 220,20 L 220,185 L 120,205 L 20,165 Z" class="cdm-path"/>
                  <text class="cdm-label" x="112" y="105">Endebess</text>
                </g>
                <g class="cdm-path-group" data-id="cherangany">
                  <path d="M 220,20 L 460,20 L 460,235 L 265,235 L 220,185 Z" class="cdm-path"/>
                  <text class="cdm-label" x="352" y="120">Cherangany</text>
                </g>
                <g class="cdm-path-group" data-id="kiminini">
                  <path d="M 20,165 L 120,205 L 120,385 L 20,385 Z" class="cdm-path"/>
                  <text class="cdm-label" x="62" y="295">Kiminini</text>
                </g>
                <g class="cdm-path-group" data-id="saboti">
                  <path d="M 120,205 L 220,185 L 265,235 L 265,385 L 120,385 Z" class="cdm-path"/>
                  <text class="cdm-label" x="190" y="308">Saboti</text>
                </g>
                <g class="cdm-path-group" data-id="kwanza">
                  <path d="M 265,235 L 460,235 L 460,385 L 265,385 Z" class="cdm-path"/>
                  <text class="cdm-label" x="365" y="313">Kwanza</text>
                </g>
              </svg>
            </div>
            <div class="cd-mini-map-nav">
              <a href="constituencies.php" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-map" aria-hidden="true"></i> All Constituencies
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== RELATED CONSTITUENCIES ===== -->
    <section class="cd-related-section" aria-label="Other constituencies">
      <div class="container">
        <div class="cd-section-header">
          <div>
            <h2 class="cd-section-title">Other Constituencies</h2>
            <p class="cd-section-sub">Explore housing developments across Trans-Nzoia County.</p>
          </div>
          <a href="constituencies.php" class="cd-view-all">
            View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div class="cd-related-grid" id="cdRelatedGrid">
          <!-- populated by JS -->
        </div>
      </div>
    </section>

    <!-- ===== 404 / NOT FOUND STATE ===== -->
    <div class="cd-not-found" id="cdNotFound" hidden>
      <div class="container">
        <div class="cd-not-found-inner">
          <i class="fa-solid fa-map-location-dot cd-not-found-icon" aria-hidden="true"></i>
          <h2>Constituency Not Found</h2>
          <p>The constituency you're looking for doesn't exist or the URL is incorrect.</p>
          <a href="constituencies.php" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Constituencies
          </a>
        </div>
      </div>
    </div>

  </main>

  <!-- ===== APPLY CTA BANNER ===== -->
  <div class="cd-apply-banner" role="complementary" aria-label="Apply for housing">
    <div class="container">
      <div class="cd-apply-inner">
        <div class="cd-apply-icon"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i></div>
        <div class="cd-apply-copy">
          <h3 class="cd-apply-title">Ready to Apply for Affordable Housing?</h3>
          <p class="cd-apply-sub">Register on the national Boma Yangu portal to join the allocation list for this constituency.</p>
        </div>
        <div class="cd-apply-ctas">
          <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="cd-btn-lime">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Apply on Boma Yangu
          </a>
          <a href="projects.php" class="cd-btn-outline-light">
            View All Projects <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php include __DIR__ . "/app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'scripts.php'; ?>
</body>
</html>