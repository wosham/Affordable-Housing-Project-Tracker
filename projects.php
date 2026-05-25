<?php
$basePath = '';
$activePage = 'projects';
$pageTitle = 'All Projects | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = 'Browse all affordable housing projects in Trans-Nzoia County â€” real-time construction progress, contractor details and unit counts across all 5 constituencies.';
$pageKeywords = 'Trans-Nzoia affordable housing projects, AHP Kenya, Maili Tatu estate, Matunda estate, Saboti housing, Cherangany housing';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/projects.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/projects.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/projects.js'
];
$headMeta = [
  '<meta property="og:title" content="All Projects â€” Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Browse all 8 affordable housing projects across Trans-Nzoia County\'s 5 constituencies with real-time progress tracking.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/projects.php">',
  '<meta property="og:image" content="uploads/gallery/maili-tatu-1.jpg">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">',
  '<meta name="twitter:title" content="All Projects â€” Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:description" content="Real-time construction tracking for all 8 affordable housing projects in Trans-Nzoia County, Kenya.">',
  '<meta name="twitter:image" content="uploads/gallery/maili-tatu-1.jpg">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ===== PAGE HERO ===== -->
    <section class="page-hero page-hero--projects" aria-label="Projects directory">
      <div class="page-hero-bg" aria-hidden="true">
        <img src="uploads/gallery/maili-tatu-2.jpg" alt="" loading="eager" onerror="this.style.display='none'">
        <div class="page-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current">All Projects</span>
        </nav>
        <div class="page-hero-body">
          <div class="page-hero-eyebrow">
            <span class="page-hero-tag"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> AHP Projects</span>
          </div>
          <h1 class="page-hero-title">Housing Projects<br><span class="text-lime">Directory</span></h1>
          <p class="page-hero-sub">Track every affordable housing project across Trans-Nzoia County â€” construction progress, contractor details, timelines and unit counts in real time.</p>
        </div>
        <!-- Stats strip -->
        <div class="projects-hero-stats" role="region" aria-label="Programme statistics">
          <div class="phs-item">
            <span class="phs-val" id="statTotalProjects">8</span>
            <span class="phs-lbl">Total Projects</span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statTotalUnits">1,730</span>
            <span class="phs-lbl">Units Planned</span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statActiveProjects">5</span>
            <span class="phs-lbl">Active</span>
          </div>
          <div class="phs-divider" aria-hidden="true"></div>
          <div class="phs-item">
            <span class="phs-val" id="statConstituencies">5</span>
            <span class="phs-lbl">Constituencies</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FILTER + GRID ===== -->
    <section class="projects-main" aria-label="Project listings">
      <div class="container">

        <!-- Filter Bar -->
        <div class="projects-filter-bar" role="search" aria-label="Filter projects">
          <div class="pfb-search-wrap">
            <i class="fa-solid fa-magnifying-glass pfb-search-icon" aria-hidden="true"></i>
            <input type="search" class="pfb-search-input" id="projectSearch" placeholder="Search projectsâ€¦" aria-label="Search projects by name or location">
          </div>
          <div class="pfb-filters" role="group" aria-label="Filter by status">
            <span class="pfb-label">Status:</span>
            <button class="pfb-chip is-active" data-filter-status="all">All</button>
            <button class="pfb-chip" data-filter-status="active">
              <span class="pfb-chip-dot pfb-chip-dot--active" aria-hidden="true"></span>Active
            </button>
            <button class="pfb-chip" data-filter-status="planning">
              <span class="pfb-chip-dot pfb-chip-dot--planning" aria-hidden="true"></span>Planning
            </button>
          </div>
          <div class="pfb-filters" role="group" aria-label="Filter by constituency">
            <span class="pfb-label">Constituency:</span>
            <button class="pfb-chip is-active" data-filter-con="all">All</button>
            <button class="pfb-chip" data-filter-con="saboti">Saboti</button>
            <button class="pfb-chip" data-filter-con="cherangany">Cherangany</button>
            <button class="pfb-chip" data-filter-con="endebess">Endebess</button>
            <button class="pfb-chip" data-filter-con="kiminini">Kiminini</button>
            <button class="pfb-chip" data-filter-con="kwanza">Kwanza</button>
          </div>
          <div class="pfb-sort-wrap">
            <label for="projectSort" class="pfb-label">Sort:</label>
            <select id="projectSort" class="pfb-sort-select" aria-label="Sort projects">
              <option value="pct-desc">Completion â†“</option>
              <option value="pct-asc">Completion â†‘</option>
              <option value="units-desc">Units â†“</option>
              <option value="name-asc">Name Aâ€“Z</option>
            </select>
          </div>
        </div>

        <!-- Results count -->
        <div class="projects-results-meta" aria-live="polite" aria-atomic="true">
          <span id="resultsCount">Showing 8 projects</span>
          <button class="pfb-reset" id="resetFilters" hidden>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear filters
          </button>
        </div>

        <!-- Projects Grid -->
        <div class="projects-grid" id="projectsGrid" role="list">
          <!-- Cards injected by JS -->
        </div>

        <!-- Pagination -->
        <nav class="projects-pagination" id="projectsPagination" aria-label="Project pages" hidden></nav>

        <!-- Empty State -->
        <div class="projects-empty" id="projectsEmpty" hidden aria-live="polite">
          <div class="projects-empty-icon" aria-hidden="true">
            <i class="fa-solid fa-building-circle-xmark"></i>
          </div>
          <h3>No projects found</h3>
          <p>Try adjusting your filters or search term.</p>
          <button class="btn btn-primary" id="emptyReset">Clear all filters</button>
        </div>

      </div>
    </section>

  </main>

  <!-- ==================== FOOTER ==================== -->
<?php include __DIR__ . "/app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'scripts.php'; ?>
</body>
</html>