<?php
$basePath = '';
$activePage = 'projects';
$pageTitle = 'Project Detail | Trans-Nzoia County Affordable Housing Tracker';
$pageDescription = 'Detailed construction progress, contractor information, milestones and site photos for an affordable housing project in Trans-Nzoia County.';
$pageKeywords = 'Trans-Nzoia AHP project detail, affordable housing Kenya, construction progress';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/project-detail.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/project-detail.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/project-detail.js'
];
$headMeta = [
  '<meta property="og:title" content="Project Detail â€” Trans-Nzoia AHP Tracker">',
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

    <!-- ===== PROJECT HERO (full-bleed photo) ===== -->
    <section class="pd-hero" id="pdHero" aria-label="Project overview">
      <div class="pd-hero-bg" id="pdHeroBg" aria-hidden="true">
        <div class="pd-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <a href="projects.php" class="breadcrumb-link">Projects</a>
          <i class="fa-solid fa-chevron-right breadcrumb-sep" aria-hidden="true"></i>
          <span class="breadcrumb-current" id="pdBreadcrumbCurrent">Loadingâ€¦</span>
        </nav>
        <div class="pd-hero-content" id="pdHeroContent">
          <!-- populated by JS -->
        </div>
      </div>
    </section>

    <!-- ===== MAIN BODY: 2-COLUMN LAYOUT ===== -->
    <div class="pd-body" id="pdBody">
      <div class="container">
        <div class="pd-body-inner">

          <!-- LEFT: Main content -->
          <div class="pd-main" id="pdMain">

            <!-- Key facts strip -->
            <div class="pd-facts-strip" id="pdFactsStrip">
              <!-- populated by JS -->
            </div>

            <!-- Description -->
            <div class="pd-description" id="pdDescription">
              <!-- populated by JS -->
            </div>

            <!-- Construction Progress -->
            <div class="pd-progress-section" id="pdProgressSection">
              <!-- populated by JS -->
            </div>

            <!-- Milestone Timeline -->
            <div class="pd-timeline-section" id="pdTimelineSection">
              <!-- populated by JS -->
            </div>

            <!-- Photo Gallery -->
            <div class="pd-gallery-section" id="pdGallerySection">
              <!-- populated by JS -->
            </div>

          </div>

          <!-- RIGHT: Sidebar -->
          <aside class="pd-sidebar" id="pdSidebar" aria-label="Project quick facts">
            <!-- populated by JS -->
          </aside>

        </div>
      </div>
    </div>

    <!-- ===== NOT FOUND ===== -->
    <div class="pd-not-found" id="pdNotFound" hidden>
      <div class="container">
        <div class="pd-not-found-inner">
          <i class="fa-solid fa-building-circle-xmark pd-not-found-icon" aria-hidden="true"></i>
          <h2>Project Not Found</h2>
          <p>The project you're looking for doesn't exist or the URL is incorrect.</p>
          <a href="projects.php" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to All Projects
          </a>
        </div>
      </div>
    </div>

  </main>
<?php include __DIR__ . "/app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'scripts.php'; ?>
</body>
</html>