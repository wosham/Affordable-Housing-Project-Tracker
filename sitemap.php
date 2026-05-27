<?php
$basePath = '';
$activePage = 'sitemap';
$pageTitle = 'Site Map | Trans-Nzoia AHP Tracker';
$pageDescription = 'Complete site map for the Trans-Nzoia Affordable Housing Programme Tracker â€” find every page, section and resource.';
$pageKeywords = '';
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/sitemap.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/sitemap.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/pages/sitemap.js'
];
$headMeta = [
  '<meta property="og:title" content="Site Map | Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Find every page and resource on the Trans-Nzoia AHP Tracker website.">',
  '<meta property="og:type" content="website">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ============================================================
         S1: HERO
    ============================================================ -->
    <section class="sm-hero" aria-label="Site map overview">
      <div class="sm-hero-bg" aria-hidden="true">
        <div class="sm-hero-overlay"></div>
        <div class="sm-hero-grid"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Site Map</span>
        </nav>
        <div class="sm-hero-body">
          <div class="sm-hero-eyebrow">
            <i class="fa-solid fa-sitemap" aria-hidden="true"></i> Site Map
          </div>
          <h1 class="sm-hero-title">Everything in One Place</h1>
          <p class="sm-hero-sub">Find every page, section, and resource on the Trans-Nzoia AHP Tracker. Use the search below to jump straight to what you need.</p>
          <div class="sm-search-wrap">
            <i class="fa-solid fa-magnifying-glass sm-search-icon" aria-hidden="true"></i>
            <input type="search" id="smSearch" class="sm-search-input" placeholder="Search pages, sectionsâ€¦" aria-label="Search site map">
            <button class="sm-search-clear" id="smSearchClear" aria-label="Clear search" hidden>
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="sm-hero-stats">
            <div class="sm-stat"><span class="sm-stat-num" id="smLiveCount">26</span><span class="sm-stat-lbl">Pages</span></div>
            <div class="sm-stat-div" aria-hidden="true"></div>
            <div class="sm-stat"><span class="sm-stat-num">6</span><span class="sm-stat-lbl">Sections</span></div>
            <div class="sm-stat-div" aria-hidden="true"></div>
            <div class="sm-stat"><span class="sm-stat-num">2026</span><span class="sm-stat-lbl">Last Updated</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S2: VISUAL SITEMAP GRID
    ============================================================ -->
    <section class="sm-grid-section" aria-labelledby="sm-grid-heading">
      <div class="container">
        <div class="sm-section-header fade-up">
          <h2 class="sm-section-title" id="sm-grid-heading">Page Directory</h2>
          <p class="sm-section-sub">All pages organised by section. Click any link to go there directly.</p>
        </div>

        <div class="sm-no-results" id="smNoResults" hidden>
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <p>No pages found for <strong id="smNoResultsQuery"></strong>.</p>
          <button class="sm-inline-btn" id="smClearSearch">Clear search</button>
        </div>

        <div class="sm-grid" id="smGrid">

          <!-- PUBLIC PAGES -->
          <div class="sm-card fade-up" data-section="public" data-count="7">
            <div class="sm-card-header sm-card-header--green">
              <div class="sm-card-icon"><i class="fa-solid fa-globe" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title">Public Pages</h3>
                <span class="sm-card-count">7 pages</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <li data-page="Home" data-desc="Programme overview, live stats, news highlights">
                <a href="index.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Home</span><span class="sm-link-desc">Programme overview, live stats, news highlights</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Projects" data-desc="All construction projects across Trans-Nzoia">
                <a href="projects.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Projects</span><span class="sm-link-desc">All construction projects across Trans-Nzoia</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Project Detail" data-desc="Individual project progress page">
                <a href="project-detail.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Project Detail</span><span class="sm-link-desc">Individual project progress page</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Constituencies" data-desc="Five constituencies with housing allocation data">
                <a href="constituencies.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Constituencies</span><span class="sm-link-desc">Five constituencies with housing allocation data</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Constituency Detail" data-desc="Individual constituency breakdown">
                <a href="constituency-detail.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-map-pin" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Constituency Detail</span><span class="sm-link-desc">Individual constituency breakdown</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="News" data-desc="Announcements, updates and press releases">
                <a href="news.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">News &amp; Announcements</span><span class="sm-link-desc">Press releases, updates and field dispatches</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Gallery" data-desc="Photo gallery of all project sites">
                <a href="gallery.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-images" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Photo Gallery</span><span class="sm-link-desc">Site photography and progress images</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- PROGRAMME INFO -->
          <div class="sm-card fade-up" data-section="programme" data-count="5">
            <div class="sm-card-header sm-card-header--blue">
              <div class="sm-card-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title">Programme Info</h3>
                <span class="sm-card-count">5 pages</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <li data-page="About" data-desc="History, vision, mission and programme facts">
                <a href="about.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">About the Programme</span><span class="sm-link-desc">History, vision, mission and facts</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Leadership" data-desc="County leadership and programme officials">
                <a href="leadership.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">County Leadership</span><span class="sm-link-desc">County officials and programme directors</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Stakeholders" data-desc="Partner organisations and collaborators">
                <a href="stakeholders.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-handshake" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Stakeholders</span><span class="sm-link-desc">Partner organisations and collaborators</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="FAQ" data-desc="Frequently asked questions with live search">
                <a href="faq.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">FAQ</span><span class="sm-link-desc">Frequently asked questions with live search</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Contact" data-desc="Office contacts, form and department directory">
                <a href="contact.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Contact Us</span><span class="sm-link-desc">Office contacts, form, department directory</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- STAFF PORTAL -->
          <div class="sm-card fade-up" data-section="portal" data-count="4">
            <div class="sm-card-header sm-card-header--amber">
              <div class="sm-card-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title">Staff Portal</h3>
                <span class="sm-card-count">4 pages</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <li data-page="Staff Login" data-desc="Secure login for county staff">
                <a href="admin/login.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Staff Login</span><span class="sm-link-desc">Secure login for county staff</span></span>
                  <span class="sm-badge sm-badge--restricted">Restricted</span>
                </a>
              </li>
              <li data-page="Dashboard" data-desc="Staff dashboard and management tools">
                <a href="admin/index.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Dashboard</span><span class="sm-link-desc">Staff dashboard and management tools</span></span>
                  <span class="sm-badge sm-badge--restricted">Restricted</span>
                </a>
              </li>
              <li data-page="Project Editor" data-desc="Add and update project records">
                <a href="admin/index.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Project Editor</span><span class="sm-link-desc">Add and update project records</span></span>
                  <span class="sm-badge sm-badge--restricted">Restricted</span>
                </a>
              </li>
              <li data-page="Forgot Password" data-desc="Reset staff account password">
                <a href="admin/auth/forgot-password.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Forgot Password</span><span class="sm-link-desc">Reset your staff account password</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- LEGAL -->
          <div class="sm-card fade-up" data-section="legal" data-count="3">
            <div class="sm-card-header sm-card-header--neutral">
              <div class="sm-card-icon"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title">Legal</h3>
                <span class="sm-card-count">3 pages</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <li data-page="Privacy Policy" data-desc="How we collect and use your data">
                <a href="legal/privacy.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Privacy Policy</span><span class="sm-link-desc">How we collect and handle your data</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Terms of Use" data-desc="Terms and conditions for using this site">
                <a href="legal/terms.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-file-contract" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Terms of Use</span><span class="sm-link-desc">Conditions for using this website</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Disclaimer" data-desc="Legal disclaimer for housing data">
                <a href="legal/disclaimer.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Disclaimer</span><span class="sm-link-desc">Legal disclaimer for housing data</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- SITE UTILITIES -->
          <div class="sm-card fade-up" data-section="utility" data-count="4">
            <div class="sm-card-header sm-card-header--pink">
              <div class="sm-card-icon"><i class="fa-solid fa-toolbox" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title">Site Utilities</h3>
                <span class="sm-card-count">4 pages</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <li data-page="Site Map" data-desc="You are here â€” complete page directory">
                <a href="sitemap.php" class="sm-link sm-link--current" aria-current="page">
                  <span class="sm-link-icon"><i class="fa-solid fa-sitemap" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Site Map</span><span class="sm-link-desc">Complete page directory (you are here)</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="404 Error" data-desc="Page not found error page">
                <a href="404.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">404 Error Page</span><span class="sm-link-desc">Page not found â€” helpful redirects</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
              <li data-page="Offline Page" data-desc="Displayed when no internet connection">
                <a href="offline.php" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-plug-circle-xmark" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Offline Page</span><span class="sm-link-desc">Shown when connection is unavailable</span></span>
                  <span class="sm-badge sm-badge--soon">Coming Soon</span>
                </a>
              </li>
              <li data-page="XML Sitemap" data-desc="Machine-readable sitemap.xml for search engines">
                <a href="sitemap.xml" class="sm-link" target="_blank" rel="noopener noreferrer">
                  <span class="sm-link-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">XML Sitemap</span><span class="sm-link-desc">Machine-readable file for search engines</span></span>
                  <span class="sm-badge sm-badge--live">Live</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- EXTERNAL RESOURCES -->
          <div class="sm-card fade-up" data-section="external" data-count="3">
            <div class="sm-card-header sm-card-header--teal">
              <div class="sm-card-icon"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></div>
              <div>
                <h3 class="sm-card-title">External Resources</h3>
                <span class="sm-card-count">3 links</span>
              </div>
            </div>
            <ul class="sm-card-links">
              <li data-page="eCitizen Apply" data-desc="Official housing application portal">
                <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-file-pen" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Apply via eCitizen</span><span class="sm-link-desc">Official national housing application portal</span></span>
                  <span class="sm-badge sm-badge--ext">External</span>
                </a>
              </li>
              <li data-page="National AHB" data-desc="Affordable Housing Board national website">
                <a href="https://ahb.go.ke" target="_blank" rel="noopener noreferrer" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-building-government" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Affordable Housing Board</span><span class="sm-link-desc">National AHB programme website</span></span>
                  <span class="sm-badge sm-badge--ext">External</span>
                </a>
              </li>
              <li data-page="Trans-Nzoia County" data-desc="Official Trans-Nzoia County Government website">
                <a href="https://transnzoia.go.ke" target="_blank" rel="noopener noreferrer" class="sm-link">
                  <span class="sm-link-icon"><i class="fa-solid fa-landmark" aria-hidden="true"></i></span>
                  <span class="sm-link-body"><span class="sm-link-name">Trans-Nzoia County Gov.</span><span class="sm-link-desc">Official county government portal</span></span>
                  <span class="sm-badge sm-badge--ext">External</span>
                </a>
              </li>
            </ul>
          </div>

        </div><!-- /.sm-grid -->
      </div>
    </section>

    <!-- ============================================================
         S3: ALL PAGES TABLE
    ============================================================ -->
    <section class="sm-table-section fade-up" aria-labelledby="sm-table-heading">
      <div class="container">
        <div class="sm-section-header">
          <h2 class="sm-section-title" id="sm-table-heading">All Pages at a Glance</h2>
          <p class="sm-section-sub">Sortable table â€” click any column header to re-order.</p>
        </div>
        <div class="sm-table-wrap">
          <table class="sm-table" id="smTable" aria-label="All pages table">
            <thead>
              <tr>
                <th class="sm-th sortable" data-col="0" aria-sort="none" scope="col">Page Name <i class="fa-solid fa-sort sm-sort-icon" aria-hidden="true"></i></th>
                <th class="sm-th sortable" data-col="1" aria-sort="none" scope="col">Section <i class="fa-solid fa-sort sm-sort-icon" aria-hidden="true"></i></th>
                <th class="sm-th sortable" data-col="2" aria-sort="none" scope="col">Status <i class="fa-solid fa-sort sm-sort-icon" aria-hidden="true"></i></th>
                <th class="sm-th" scope="col">URL</th>
              </tr>
            </thead>
            <tbody id="smTableBody">
              <tr data-page="Home" data-section="Public" data-status="live"><td>Home</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="index.php" class="sm-tbl-link">index.php</a></td></tr>
              <tr data-page="Projects" data-section="Public" data-status="live"><td>Projects</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="projects.php" class="sm-tbl-link">projects.php</a></td></tr>
              <tr data-page="Project Detail" data-section="Public" data-status="live"><td>Project Detail</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="project-detail.php" class="sm-tbl-link">project-detail.php</a></td></tr>
              <tr data-page="Constituencies" data-section="Public" data-status="live"><td>Constituencies</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="constituencies.php" class="sm-tbl-link">constituencies.php</a></td></tr>
              <tr data-page="Constituency Detail" data-section="Public" data-status="live"><td>Constituency Detail</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="constituency-detail.php" class="sm-tbl-link">constituency-detail.php</a></td></tr>
              <tr data-page="News" data-section="Public" data-status="live"><td>News &amp; Announcements</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="news.php" class="sm-tbl-link">news.php</a></td></tr>
              <tr data-page="Gallery" data-section="Public" data-status="live"><td>Photo Gallery</td><td>Public</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="gallery.php" class="sm-tbl-link">gallery.php</a></td></tr>
              <tr data-page="About" data-section="Programme" data-status="live"><td>About the Programme</td><td>Programme</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="about.php" class="sm-tbl-link">about.php</a></td></tr>
              <tr data-page="Leadership" data-section="Programme" data-status="live"><td>County Leadership</td><td>Programme</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="leadership.php" class="sm-tbl-link">leadership.php</a></td></tr>
              <tr data-page="Stakeholders" data-section="Programme" data-status="live"><td>Stakeholders</td><td>Programme</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="stakeholders.php" class="sm-tbl-link">stakeholders.php</a></td></tr>
              <tr data-page="FAQ" data-section="Programme" data-status="live"><td>FAQ</td><td>Programme</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="faq.php" class="sm-tbl-link">faq.php</a></td></tr>
              <tr data-page="Contact" data-section="Programme" data-status="live"><td>Contact Us</td><td>Programme</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="contact.php" class="sm-tbl-link">contact.php</a></td></tr>
              <tr data-page="Staff Login" data-section="Staff Portal" data-status="restricted"><td>Staff Login</td><td>Staff Portal</td><td><span class="sm-badge sm-badge--restricted">Restricted</span></td><td><a href="admin/login.php" class="sm-tbl-link">admin/login.php</a></td></tr>
              <tr data-page="Dashboard" data-section="Staff Portal" data-status="restricted"><td>Dashboard</td><td>Staff Portal</td><td><span class="sm-badge sm-badge--restricted">Restricted</span></td><td><a href="admin/index.php" class="sm-tbl-link">admin/index.php</a></td></tr>
              <tr data-page="Project Editor" data-section="Staff Portal" data-status="restricted"><td>Project Editor</td><td>Staff Portal</td><td><span class="sm-badge sm-badge--restricted">Restricted</span></td><td><a href="admin/index.php" class="sm-tbl-link">admin/index.php</a></td></tr>
              <tr data-page="Forgot Password" data-section="Staff Portal" data-status="live"><td>Forgot Password</td><td>Staff Portal</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="admin/auth/forgot-password.php" class="sm-tbl-link">admin/auth/forgot-password.php</a></td></tr>
              <tr data-page="Privacy Policy" data-section="Legal" data-status="live"><td>Privacy Policy</td><td>Legal</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="legal/privacy.php" class="sm-tbl-link">legal/privacy.php</a></td></tr>
              <tr data-page="Terms of Use" data-section="Legal" data-status="live"><td>Terms of Use</td><td>Legal</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="legal/terms.php" class="sm-tbl-link">legal/terms.php</a></td></tr>
              <tr data-page="Disclaimer" data-section="Legal" data-status="live"><td>Disclaimer</td><td>Legal</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="legal/disclaimer.php" class="sm-tbl-link">legal/disclaimer.php</a></td></tr>
              <tr data-page="Site Map" data-section="Utility" data-status="live"><td>Site Map</td><td>Utility</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="sitemap.php" class="sm-tbl-link">sitemap.php</a></td></tr>
              <tr data-page="404 Error" data-section="Utility" data-status="live"><td>404 Error Page</td><td>Utility</td><td><span class="sm-badge sm-badge--live">Live</span></td><td><a href="404.php" class="sm-tbl-link">404.php</a></td></tr>
              <tr data-page="Offline Page" data-section="Utility" data-status="soon"><td>Offline Page</td><td>Utility</td><td><span class="sm-badge sm-badge--soon">Coming Soon</span></td><td><span class="sm-tbl-na">offline.php</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S4: XML SITEMAP DOWNLOAD
    ============================================================ -->
    <section class="sm-xml-section fade-up" aria-labelledby="sm-xml-heading">
      <div class="container">
        <div class="sm-xml-wrap">
          <div class="sm-xml-header">
            <div class="sm-xml-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></div>
            <div>
              <h2 class="sm-xml-title" id="sm-xml-heading">XML Sitemap</h2>
              <p class="sm-xml-sub">Machine-readable sitemap for search engine crawlers (Google, Bing, etc.).</p>
            </div>
            <div class="sm-xml-actions">
              <button class="sm-xml-btn sm-xml-btn--copy" id="smCopyXml" aria-label="Copy XML sitemap to clipboard">
                <i class="fa-solid fa-copy" aria-hidden="true"></i> Copy
              </button>
              <a href="sitemap.xml" download class="sm-xml-btn sm-xml-btn--download" aria-label="Download sitemap.xml">
                <i class="fa-solid fa-download" aria-hidden="true"></i> Download
              </a>
            </div>
          </div>
          <div class="sm-xml-code-wrap">
            <pre class="sm-xml-code" id="smXmlContent" aria-label="XML sitemap preview"><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"&gt;

  &lt;!-- Public Pages --&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/&lt;/loc&gt;&lt;changefreq&gt;daily&lt;/changefreq&gt;&lt;priority&gt;1.0&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/projects.php&lt;/loc&gt;&lt;changefreq&gt;weekly&lt;/changefreq&gt;&lt;priority&gt;0.9&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/constituencies.php&lt;/loc&gt;&lt;changefreq&gt;weekly&lt;/changefreq&gt;&lt;priority&gt;0.9&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/news.php&lt;/loc&gt;&lt;changefreq&gt;daily&lt;/changefreq&gt;&lt;priority&gt;0.8&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/gallery.php&lt;/loc&gt;&lt;changefreq&gt;weekly&lt;/changefreq&gt;&lt;priority&gt;0.7&lt;/priority&gt;&lt;/url&gt;

  &lt;!-- Programme Info --&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/about.php&lt;/loc&gt;&lt;changefreq&gt;monthly&lt;/changefreq&gt;&lt;priority&gt;0.7&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/leadership.php&lt;/loc&gt;&lt;changefreq&gt;monthly&lt;/changefreq&gt;&lt;priority&gt;0.6&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/stakeholders.php&lt;/loc&gt;&lt;changefreq&gt;monthly&lt;/changefreq&gt;&lt;priority&gt;0.6&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/faq.php&lt;/loc&gt;&lt;changefreq&gt;monthly&lt;/changefreq&gt;&lt;priority&gt;0.7&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/contact.php&lt;/loc&gt;&lt;changefreq&gt;monthly&lt;/changefreq&gt;&lt;priority&gt;0.6&lt;/priority&gt;&lt;/url&gt;

  &lt;!-- Legal --&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/legal/privacy.php&lt;/loc&gt;&lt;changefreq&gt;yearly&lt;/changefreq&gt;&lt;priority&gt;0.3&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/legal/terms.php&lt;/loc&gt;&lt;changefreq&gt;yearly&lt;/changefreq&gt;&lt;priority&gt;0.3&lt;/priority&gt;&lt;/url&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/legal/disclaimer.php&lt;/loc&gt;&lt;changefreq&gt;yearly&lt;/changefreq&gt;&lt;priority&gt;0.3&lt;/priority&gt;&lt;/url&gt;

  &lt;!-- Utilities --&gt;
  &lt;url&gt;&lt;loc&gt;https://housing.transnzoia.go.ke/sitemap.php&lt;/loc&gt;&lt;changefreq&gt;monthly&lt;/changefreq&gt;&lt;priority&gt;0.4&lt;/priority&gt;&lt;/url&gt;

&lt;/urlset&gt;</code></pre>
          </div>
          <div class="sm-xml-copy-toast" id="smCopyToast" aria-live="polite" hidden>
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Copied to clipboard!
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- FOOTER -->
<?php include __DIR__ . "/app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'scripts.php'; ?>
</body>
</html>
