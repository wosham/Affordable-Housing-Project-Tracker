<?php
$basePath = '';
$activePage = 'constituencies';
$pageTitle = 'Constituencies | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = 'Explore affordable housing coverage across all 5 constituencies in Trans-Nzoia County â€” Saboti, Cherangany, Endebess, Kiminini and Kwanza.';
$pageKeywords = 'Trans-Nzoia constituencies, Saboti housing, Cherangany AHP, Endebess housing, Kiminini housing, Kwanza housing';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/constituencies.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/constituencies.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/constituencies.js'
];
$headMeta = [
  '<meta property="og:title" content="Constituencies â€” Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Housing coverage map and statistics for all 5 Trans-Nzoia constituencies under the national Affordable Housing Programme.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/constituencies.php">',
  '<meta property="og:image" content="uploads/gallery/maili-tatu-1.jpg">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">',
  '<meta name="twitter:title" content="Constituencies â€” Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:description" content="Interactive constituency map with housing project data for Trans-Nzoia County, Kenya.">',
  '<meta name="twitter:image" content="uploads/gallery/maili-tatu-1.jpg">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ===== PAGE HERO ===== -->
    <section class="page-hero page-hero--constituencies" aria-label="Constituencies overview">
      <div class="page-hero-bg" aria-hidden="true">
        <img src="uploads/gallery/maili-tatu-3.jpg" alt="" loading="eager" onerror="this.style.display='none'">
        <div class="page-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current">Constituencies</span>
        </nav>
        <div class="page-hero-body">
          <div class="page-hero-eyebrow">
            <span class="page-hero-tag"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Coverage Map</span>
          </div>
          <h1 class="page-hero-title">All <span class="text-lime">5 Constituencies</span></h1>
          <p class="page-hero-sub">Explore how the Affordable Housing Programme reaches every corner of Trans-Nzoia County â€” from Endebess on the Uganda border to Kwanza in the south.</p>
        </div>
        <div class="projects-hero-stats" role="region" aria-label="County-wide statistics">
          <div class="phs-item">
            <span class="phs-val">5</span>
            <span class="phs-lbl">Constituencies</span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val">8</span>
            <span class="phs-lbl">Projects</span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val">1,730</span>
            <span class="phs-lbl">Units Planned</span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val">671K</span>
            <span class="phs-lbl">Residents Served</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== MAP + CARDS SPLIT ===== -->
    <section class="constituencies-split" aria-label="Constituency map and listings">
      <div class="constituencies-split-inner">

        <!-- LEFT: SVG Map panel -->
        <div class="con-map-panel" aria-label="Interactive constituency map">
          <div class="con-map-sticky">
            <div class="con-map-header">
              <h2 class="con-map-title">Trans-Nzoia County</h2>
              <p class="con-map-hint">Click a constituency to explore its housing projects</p>
            </div>
            <div class="con-map-wrap">
              <svg id="countyMap" viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Trans-Nzoia County map showing 5 constituencies">
                <defs>
                  <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="rgba(10,31,5,0.25)"/>
                  </filter>
                </defs>

                <!-- Endebess (northwest) -->
                <g class="con-path-group" data-id="endebess" tabindex="0" role="button" aria-label="Endebess â€” 2 projects, 10% average completion">
                  <path d="M 20,20 L 220,20 L 220,185 L 120,205 L 20,165 Z" class="con-path"/>
                  <text class="con-path-label" x="112" y="100">Endebess</text>
                  <text class="con-path-sub" x="112" y="116">2 projects</text>
                </g>

                <!-- Cherangany (centre-east, largest) -->
                <g class="con-path-group" data-id="cherangany" tabindex="0" role="button" aria-label="Cherangany â€” 1 project, 35% completion">
                  <path d="M 220,20 L 460,20 L 460,235 L 265,235 L 220,185 Z" class="con-path"/>
                  <text class="con-path-label" x="352" y="115">Cherangany</text>
                  <text class="con-path-sub" x="352" y="131">1 project</text>
                </g>

                <!-- Kiminini (west) -->
                <g class="con-path-group" data-id="kiminini" tabindex="0" role="button" aria-label="Kiminini â€” 2 projects in planning">
                  <path d="M 20,165 L 120,205 L 120,385 L 20,385 Z" class="con-path"/>
                  <text class="con-path-label" x="62" y="295">Kiminini</text>
                  <text class="con-path-sub" x="62" y="311">2 projects</text>
                </g>

                <!-- Saboti (south-centre) -->
                <g class="con-path-group" data-id="saboti" tabindex="0" role="button" aria-label="Saboti â€” 2 projects, 58% average completion">
                  <path d="M 120,205 L 220,185 L 265,235 L 265,385 L 120,385 Z" class="con-path"/>
                  <text class="con-path-label" x="190" y="308">Saboti</text>
                  <text class="con-path-sub" x="190" y="324">2 projects</text>
                </g>

                <!-- Kwanza (southeast) -->
                <g class="con-path-group" data-id="kwanza" tabindex="0" role="button" aria-label="Kwanza â€” 1 project in planning">
                  <path d="M 265,235 L 460,235 L 460,385 L 265,385 Z" class="con-path"/>
                  <text class="con-path-label" x="365" y="313">Kwanza</text>
                  <text class="con-path-sub" x="365" y="329">1 project</text>
                </g>
              </svg>
            </div>
            <!-- Map legend -->
            <div class="con-map-legend" aria-label="Map legend">
              <div class="con-legend-item">
                <span class="con-legend-dot con-legend-dot--active"></span>
                <span>Active construction</span>
              </div>
              <div class="con-legend-item">
                <span class="con-legend-dot con-legend-dot--planning"></span>
                <span>Planning stage</span>
              </div>
              <div class="con-legend-item">
                <span class="con-legend-dot con-legend-dot--selected"></span>
                <span>Selected</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Constituency cards list -->
        <div class="con-cards-panel" id="conCardsPanel" aria-label="Constituency listings">
          <!-- Injected by JS -->
        </div>

      </div>
    </section>

    <!-- ===== COUNTY TOTAL BAR ===== -->
    <section class="county-total-section" aria-label="County-wide programme totals">
      <div class="container">
        <div class="county-total-inner">
          <div class="county-total-left">
            <h2 class="county-total-title">County-Wide Programme Progress</h2>
            <p class="county-total-sub">Across all 5 constituencies, the programme is delivering <strong>1,730 affordable housing units</strong> â€” targeting Kenyans registered on the national Boma Yangu portal.</p>
            <a href="projects.php" class="btn btn-primary">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i> Browse All Projects
            </a>
          </div>
          <div class="county-total-right" id="countyTotalsChart" aria-label="Constituency progress comparison">
            <!-- Injected by JS -->
          </div>
        </div>
      </div>
    </section>

    <!-- ===== CONSTITUENCY CARDS GRID ===== -->
    <section class="con-grid-section" aria-label="All constituencies overview">
      <div class="container">
        <div class="con-grid-header">
          <div class="con-grid-header-left">
            <p class="con-grid-eyebrow">Browse by Constituency</p>
            <h2 class="con-grid-title">All <span class="text-lime">5 Constituencies</span></h2>
          </div>
          <div class="con-grid-sort" id="conGridSort">
            <span class="con-sort-label">Sort:</span>
            <button class="con-sort-btn is-active" data-sort="default">Default</button>
            <button class="con-sort-btn" data-sort="progress">Highest Progress</button>
            <button class="con-sort-btn" data-sort="units">Most Units</button>
            <button class="con-sort-btn" data-sort="alpha">A â€“ Z</button>
          </div>
        </div>
        <div class="con-grid" id="conBrowseGrid">
          <!-- Injected by JS -->
        </div>
      </div>
    </section>

  </main>

  <!-- ===== APPLY CTA BANNER ===== -->
  <div class="con-apply-banner" role="complementary" aria-label="Apply for housing">
    <div class="container">
      <div class="con-apply-inner">
        <div class="con-apply-icon"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i></div>
        <div class="con-apply-copy">
          <h3 class="con-apply-title">Ready to Apply for Affordable Housing?</h3>
          <p class="con-apply-sub">Register on the national Boma Yangu portal to join the Trans-Nzoia County AHP allocation list.</p>
        </div>
        <div class="con-apply-ctas">
          <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="btn btn-lime">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Apply on Boma Yangu
          </a>
          <a href="projects.php" class="btn btn-outline-light">
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