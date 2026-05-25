<?php
$basePath = '';
$activePage = 'home';
$pageTitle = 'Trans-Nzoia County | Affordable Housing Project Tracker';
$pageDescription = 'Trans-Nzoia County Affordable Housing Project Tracker â€” Real-time monitoring of housing construction delivery across Saboti, Cherangany, Kwanza, Endebess and Kiminini constituencies. A public accountability initiative by Trans-Nzoia County Government.';
$pageKeywords = 'Trans-Nzoia affordable housing, AHP Kenya, Maili Tatu estate, Kitale housing project, county housing tracker, construction progress Kenya, Cherangany housing, Endebess AHP';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/index.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/pages/index.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="Trans-Nzoia County Affordable Housing Project Tracker">',
  '<meta property="og:description" content="Real-time monitoring of affordable housing construction delivery across all five constituencies in Trans-Nzoia County.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/">',
  '<meta property="og:image" content="uploads/heroes/hero-main.jpg">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">',
  '<meta name="twitter:title" content="Trans-Nzoia County Affordable Housing Project Tracker">',
  '<meta name="twitter:description" content="Real-time monitoring of housing construction delivery across all five constituencies of Trans-Nzoia County.">',
  '<meta name="twitter:image" content="uploads/heroes/hero-main.jpg">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ===== S1: TRACKER HERO ===== -->
    <section class="tracker-hero" aria-label="Programme overview">
      <div class="tracker-hero-bg">
        <img src="uploads/heroes/hero-main.jpg" alt="Affordable housing construction site in Trans-Nzoia County, Kenya" loading="eager" onerror="this.style.display='none'">
        <div class="tracker-hero-overlay" aria-hidden="true"></div>
      </div>
      <div class="container tracker-hero-content">
        <div class="tracker-hero-inner">
          <div class="programme-badge" role="status">
            <span class="programme-badge-dot" aria-hidden="true"></span>
            Ongoing Projects &bull; Trans-Nzoia County
          </div>
          <h1 class="tracker-hero-title">Affordable Housing<br>Project Tracker</h1>
          <p class="tracker-hero-subtitle">Transparent, real-time monitoring of housing construction delivery across all five constituencies of Trans-Nzoia County.</p>
          <div class="tracker-hero-cta">
            <a href="projects.php" class="btn btn-lg btn-white">
              <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
              View All Projects
            </a>
            <a href="constituencies.php" class="btn btn-lg btn-outline-white">
              <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
              By Constituency
            </a>
          </div>
        </div>

        <!-- KPI Strip â€” live programme metrics -->
        <div class="hero-kpi-strip" role="region" aria-label="Programme key metrics">
          <div class="hero-kpi-item">
            <span class="hero-kpi-number" data-count="8" aria-label="8 active projects">0</span>
            <span class="hero-kpi-label">Active Projects</span>
          </div>
          <div class="hero-kpi-divider" aria-hidden="true"></div>
          <div class="hero-kpi-item">
            <span class="hero-kpi-number" data-count="1390" data-suffix="+" aria-label="Over 1390 units under construction">0</span>
            <span class="hero-kpi-label">Units Under Construction</span>
          </div>
          <div class="hero-kpi-divider" aria-hidden="true"></div>
          <div class="hero-kpi-item">
            <span class="hero-kpi-number" data-count="5" aria-label="5 constituencies covered">0</span>
            <span class="hero-kpi-label">Constituencies Covered</span>
          </div>
          <div class="hero-kpi-divider" aria-hidden="true"></div>
          <div class="hero-kpi-item">
            <span class="hero-kpi-number" data-count="38" data-suffix="%" aria-label="38 percent average completion">0</span>
            <span class="hero-kpi-label">Avg. Completion</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== S2: FEATURED PROJECTS ===== -->
    <section class="section projects-section" id="featured-projects" aria-labelledby="projects-heading">
      <div class="container">
        <div class="split-section-header fade-up">
          <div class="split-section-left">
            <span class="accent-line"></span>
            <h2 id="projects-heading" class="section-title">Featured Projects</h2>
            <p class="section-subtitle" style="margin-bottom:0">Track ongoing affordable housing construction across Trans-Nzoia County. View real-time progress, milestones, and unit delivery status for each project.</p>
          </div>
          <div class="split-section-right">
            <a href="projects.php" class="btn btn-outline">
              Full Portfolio
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
          </div>
        </div>

        <div class="project-cards-grid stagger-children">

          <!-- Project Card 1: Maili Tatu -->
          <article class="ptc">
            <div class="ptc-image">
              <img src="uploads/site-photos/maili-tatu-1.jpg" alt="Maili Tatu Estate construction site, Saboti Constituency, Trans-Nzoia" loading="lazy" onerror="this.parentElement.classList.add('ptc-img-fallback')">
              <div class="ptc-image-overlay" aria-hidden="true"></div>
              <div class="ptc-badges">
                <span class="badge badge-ongoing">
                  <span class="badge-dot" aria-hidden="true"></span>Ongoing
                </span>
                <span class="ptc-category-tag">AHP</span>
              </div>
            </div>
            <div class="ptc-body">
              <div class="ptc-location">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                Saboti Constituency &mdash; Kitale
              </div>
              <h3 class="ptc-title">Maili Tatu Estate</h3>
              <div class="ptc-stats-row">
                <div class="ptc-stat">
                  <span class="ptc-stat-val">1,040</span>
                  <span class="ptc-stat-lbl">Units</span>
                </div>
                <div class="ptc-stat-sep" aria-hidden="true"></div>
                <div class="ptc-stat">
                  <span class="ptc-stat-val ptc-stat-pct">60%</span>
                  <span class="ptc-stat-lbl">Complete</span>
                </div>
                <div class="ptc-stat-sep" aria-hidden="true"></div>
                <div class="ptc-stat">
                  <span class="ptc-stat-val">2026</span>
                  <span class="ptc-stat-lbl">Est. Delivery</span>
                </div>
              </div>
              <div class="ptc-progress" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" aria-label="Construction 60% complete">
                <div class="ptc-progress-track">
                  <div class="ptc-progress-fill" data-progress="60"></div>
                </div>
                <span class="ptc-progress-label">60% Completed</span>
              </div>
              <div class="ptc-actions">
                <a href="project-detail.php?id=maili-tatu-estate" class="btn btn-primary btn-sm">View Details</a>
                <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                  Apply on eCitizen
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                </a>
              </div>
            </div>
          </article>

          <!-- Project Card 2: Matunda AHP -->
          <article class="ptc">
            <div class="ptc-image">
              <img src="uploads/site-photos/matunda-ahp-1.jpg" alt="Matunda Affordable Housing Project under construction, Cherangany Constituency" loading="lazy" onerror="this.parentElement.classList.add('ptc-img-fallback')">
              <div class="ptc-image-overlay" aria-hidden="true"></div>
              <div class="ptc-badges">
                <span class="badge badge-ongoing">
                  <span class="badge-dot" aria-hidden="true"></span>Ongoing
                </span>
                <span class="ptc-category-tag">AHP</span>
              </div>
            </div>
            <div class="ptc-body">
              <div class="ptc-location">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                Cherangany Constituency &mdash; Matunda
              </div>
              <h3 class="ptc-title">Matunda AHP</h3>
              <div class="ptc-stats-row">
                <div class="ptc-stat">
                  <span class="ptc-stat-val">200</span>
                  <span class="ptc-stat-lbl">Units</span>
                </div>
                <div class="ptc-stat-sep" aria-hidden="true"></div>
                <div class="ptc-stat">
                  <span class="ptc-stat-val ptc-stat-pct">35%</span>
                  <span class="ptc-stat-lbl">Complete</span>
                </div>
                <div class="ptc-stat-sep" aria-hidden="true"></div>
                <div class="ptc-stat">
                  <span class="ptc-stat-val">2027</span>
                  <span class="ptc-stat-lbl">Est. Delivery</span>
                </div>
              </div>
              <div class="ptc-progress" role="progressbar" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100" aria-label="Construction 35% complete">
                <div class="ptc-progress-track">
                  <div class="ptc-progress-fill" data-progress="35"></div>
                </div>
                <span class="ptc-progress-label">35% Completed</span>
              </div>
              <div class="ptc-actions">
                <a href="project-detail.php?id=matunda-ahp-estate" class="btn btn-primary btn-sm">View Details</a>
                <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                  Apply on eCitizen
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                </a>
              </div>
            </div>
          </article>

          <!-- Project Card 3: Suam Border Post -->
          <article class="ptc">
            <div class="ptc-image">
              <img src="uploads/site-photos/suam-ahp.jpg" alt="Suam Border Post Estate site, Endebess Constituency, Trans-Nzoia" loading="lazy" onerror="this.parentElement.classList.add('ptc-img-fallback')">
              <div class="ptc-image-overlay" aria-hidden="true"></div>
              <div class="ptc-badges">
                <span class="badge badge-warning">Planning</span>
                <span class="ptc-category-tag">AHP</span>
              </div>
            </div>
            <div class="ptc-body">
              <div class="ptc-location">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                Endebess Constituency &mdash; Suam
              </div>
              <h3 class="ptc-title">Suam Border Post Estate</h3>
              <div class="ptc-stats-row">
                <div class="ptc-stat">
                  <span class="ptc-stat-val">150</span>
                  <span class="ptc-stat-lbl">Units</span>
                </div>
                <div class="ptc-stat-sep" aria-hidden="true"></div>
                <div class="ptc-stat">
                  <span class="ptc-stat-val ptc-stat-pct">12%</span>
                  <span class="ptc-stat-lbl">Complete</span>
                </div>
                <div class="ptc-stat-sep" aria-hidden="true"></div>
                <div class="ptc-stat">
                  <span class="ptc-stat-val">2027</span>
                  <span class="ptc-stat-lbl">Est. Delivery</span>
                </div>
              </div>
              <div class="ptc-progress" role="progressbar" aria-valuenow="12" aria-valuemin="0" aria-valuemax="100" aria-label="Construction 12% complete">
                <div class="ptc-progress-track">
                  <div class="ptc-progress-fill" data-progress="12"></div>
                </div>
                <span class="ptc-progress-label">12% Completed</span>
              </div>
              <div class="ptc-actions">
                <a href="project-detail.php?id=suam-border-estate" class="btn btn-primary btn-sm">View Details</a>
                <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                  Apply on eCitizen
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                </a>
              </div>
            </div>
          </article>

        </div>
      </div>
    </section>

    <!-- ===== S3: CONSTITUENCY COVERAGE â€” INTERACTIVE MAP ===== -->
    <section class="section map-section" aria-labelledby="map-heading">
      <div class="container">
        <div class="split-section-header fade-up">
          <div class="split-section-left">
            <span class="accent-line"></span>
            <h2 id="map-heading" class="section-title">Coverage by Constituency</h2>
            <p class="section-subtitle" style="margin-bottom:0">All five constituencies are part of the Trans-Nzoia AHP. Click any constituency to view live project data.</p>
          </div>
          <div class="split-section-right">
            <a href="constituencies.php" class="btn btn-outline">
              All Constituencies <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>

        <div class="map-interactive-wrap">

          <!-- SVG County Map -->
          <div class="map-canvas-wrap">
            <svg viewBox="0 0 480 400" class="county-map" role="img" aria-label="Interactive map of Trans-Nzoia County showing five constituencies">
              <title>Trans-Nzoia County Constituency Map</title>
              <defs>
                <filter id="mapGlow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
              </defs>

              <!-- Endebess (northwest) -->
              <g class="map-constituency" data-id="endebess" data-name="Endebess" data-projects="2" data-status="active" data-units="150" data-pct="12" data-link="constituency-detail.php?id=endebess" tabindex="0" role="button" aria-label="Endebess â€” 2 projects, 12% average completion">
                <path d="M 20,20 L 220,20 L 220,180 L 120,200 L 20,160 Z" class="map-path"/>
                <text class="map-label" x="112" y="98">Endebess</text>
              </g>

              <!-- Cherangany (centre-east, largest) -->
              <g class="map-constituency" data-id="cherangany" data-name="Cherangany" data-projects="1" data-status="active" data-units="200" data-pct="35" data-link="constituency-detail.php?id=cherangany" tabindex="0" role="button" aria-label="Cherangany â€” 1 project, 35% completion">
                <path d="M 220,20 L 460,20 L 460,230 L 260,230 L 220,180 Z" class="map-path"/>
                <text class="map-label" x="348" y="112">Cherangany</text>
              </g>

              <!-- Kiminini (west) -->
              <g class="map-constituency" data-id="kiminini" data-name="Kiminini" data-projects="2" data-status="planning" data-units="120" data-pct="5" data-link="constituency-detail.php?id=kiminini" tabindex="0" role="button" aria-label="Kiminini â€” 2 projects in planning">
                <path d="M 20,160 L 120,200 L 120,380 L 20,380 Z" class="map-path"/>
                <text class="map-label" x="62" y="290">Kiminini</text>
              </g>

              <!-- Saboti (south-centre) -->
              <g class="map-constituency" data-id="saboti" data-name="Saboti" data-projects="2" data-status="active" data-units="1040" data-pct="60" data-link="constituency-detail.php?id=saboti" tabindex="0" role="button" aria-label="Saboti â€” 2 projects, 60% average completion">
                <path d="M 120,200 L 220,180 L 260,230 L 260,380 L 120,380 Z" class="map-path"/>
                <text class="map-label" x="186" y="305">Saboti</text>
              </g>

              <!-- Kwanza (southeast) -->
              <g class="map-constituency" data-id="kwanza" data-name="Kwanza" data-projects="1" data-status="planning" data-units="80" data-pct="8" data-link="constituency-detail.php?id=kwanza" tabindex="0" role="button" aria-label="Kwanza â€” 1 project in planning">
                <path d="M 260,230 L 460,230 L 460,380 L 260,380 Z" class="map-path"/>
                <text class="map-label" x="362" y="310">Kwanza</text>
              </g>

              <!-- Active project site dots -->
              <g class="map-sites" aria-hidden="true">
                <circle class="map-site-dot site-active" cx="178" cy="255" r="6"/>
                <circle class="map-site-pulse" cx="178" cy="255" r="6"/>
                <circle class="map-site-dot site-active" cx="304" cy="125" r="6"/>
                <circle class="map-site-pulse" cx="304" cy="125" r="6"/>
                <circle class="map-site-dot site-planning" cx="88" cy="82" r="5"/>
              </g>
            </svg>

            <!-- Compass -->
            <div class="map-compass" aria-hidden="true">
              <i class="fa-solid fa-location-crosshairs"></i>
              <span>N</span>
            </div>

            <!-- Legend -->
            <div class="map-legend" aria-hidden="true">
              <span class="map-legend-item">
                <span class="map-legend-dot map-legend-active"></span>Active
              </span>
              <span class="map-legend-item">
                <span class="map-legend-dot map-legend-plan"></span>Planning
              </span>
              <span class="map-legend-item">
                <i class="fa-solid fa-location-dot" style="color:var(--lime-muted);font-size:11px"></i> Project site
              </span>
            </div>
          </div>

          <!-- Side Data Panel -->
          <aside class="map-side-panel" id="mapPanel" aria-live="polite">
            <div class="map-panel-default" id="mapPanelDefault">
              <i class="fa-solid fa-map-location-dot map-panel-icon" aria-hidden="true"></i>
              <p class="map-panel-hint">Click a constituency on the map to see project data</p>
              <div class="map-panel-overview">
                <div class="map-ov-item">
                  <span class="map-ov-val">5</span>
                  <span class="map-ov-lbl">Constituencies</span>
                </div>
                <div class="map-ov-item">
                  <span class="map-ov-val">8</span>
                  <span class="map-ov-lbl">Active Projects</span>
                </div>
                <div class="map-ov-item">
                  <span class="map-ov-val">1,390+</span>
                  <span class="map-ov-lbl">Units Planned</span>
                </div>
              </div>
            </div>
            <div class="map-panel-detail" id="mapPanelDetail"></div>
          </aside>

        </div>
      </div>
    </section>

    <!-- ===== S4: FIELD REPORTS ===== -->
    <section class="section news-section" aria-labelledby="news-heading">
      <div class="container">
        <div class="split-section-header fade-up">
          <div class="split-section-left">
            <span class="accent-line"></span>
            <h2 id="news-heading" class="section-title">From the Ground</h2>
            <p class="section-subtitle" style="margin-bottom:0">Project milestones, tender notices, and official clearances â€” directly from Trans-Nzoia County AHP sites.</p>
          </div>
          <div class="split-section-right">
            <a href="news.php" class="btn btn-outline">
              All Reports <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>

        <!-- Asymmetric news grid: 1 feature + 2 stacked + 1 vertical alerts sidebar -->
        <div class="news-asymmetric">

          <!-- Feature card (large) -->
          <article class="news-card news-card--feature">
            <div class="news-card-img">
              <img src="uploads/news/modern-market.jpg" alt="Maili Tatu Estate structural works on Blocks Aâ€“F, 60% completion" loading="lazy" onerror="this.parentElement.classList.add('news-img-fallback')">
              <span class="news-cat-badge news-cat-milestone"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i> Milestone</span>
            </div>
            <div class="news-card-body">
              <time class="news-date" datetime="2026-05-20"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 20 May 2026</time>
              <h3 class="news-card-title">Maili Tatu Estate Hits 60% â€” Blocks Aâ€“F Structural Works Certified</h3>
              <p class="news-card-text">The Clerk of Works has certified structural completion on all six primary blocks at the flagship 1,040-unit Saboti estate. Roofing and MEP works commence next quarter, keeping the Q4 2026 handover on track.</p>
              <a href="news-article.php?id=maili-tatu-milestone" class="news-read-link">
                Read full report <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </article>

          <!-- Stacked small cards -->
          <div class="news-stack">

            <article class="news-card news-card--compact">
              <div class="news-card-img">
                <img src="uploads/news/kitale-ex-prison.jpeg" alt="Kitale Ex-Prison site earmarked for affordable housing redevelopment" loading="lazy" onerror="this.parentElement.classList.add('news-img-fallback')">
                <span class="news-cat-badge news-cat-tender"><i class="fa-solid fa-gavel" aria-hidden="true"></i> Tender</span>
              </div>
              <div class="news-card-body">
                <time class="news-date" datetime="2026-05-15"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 15 May 2026</time>
                <h3 class="news-card-title">Tender: Kitale Ex-Prison Site Redevelopment â€” Bids Close 30 Jun</h3>
                <p class="news-card-text line-clamp-2">Qualified contractors are invited to bid for transformation of the former Kitale Prison grounds into a mixed-income affordable housing estate.</p>
              </div>
            </article>

            <article class="news-card news-card--compact">
              <div class="news-card-img">
                <img src="uploads/news/suam-ahp.jpg" alt="Suam Border Post Estate site â€” NEMA environmental impact clearance" loading="lazy" onerror="this.parentElement.classList.add('news-img-fallback')">
                <span class="news-cat-badge news-cat-approval"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approval</span>
              </div>
              <div class="news-card-body">
                <time class="news-date" datetime="2026-05-10"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 10 May 2026</time>
                <h3 class="news-card-title">Suam Border Post Estate: NEMA Environmental Clearance Issued</h3>
                <p class="news-card-text line-clamp-2">Full site mobilisation can now proceed at the 150-unit Endebess estate following formal NEMA sign-off on the Environmental Impact Assessment.</p>
              </div>
            </article>

          </div>

          <!-- Vertical alerts sidebar -->
          <aside class="news-alerts-sidebar" aria-label="Latest programme alerts">
            <div class="nas-header">
              <i class="fa-solid fa-bolt nas-icon" aria-hidden="true"></i>
              <span class="nas-title">Latest Alerts</span>
            </div>
            <ul class="nas-list">
              <li class="nas-item nas-item--urgent">
                <span class="nas-dot" aria-hidden="true"></span>
                <div class="nas-content">
                  <span class="nas-tag">Tender</span>
                  <a href="news.php" class="nas-headline">Kitale Ex-Prison â€” bids close 30 Jun 2026</a>
                  <time class="nas-time" datetime="2026-05-15">15 May 2026</time>
                </div>
              </li>
              <li class="nas-item">
                <span class="nas-dot" aria-hidden="true"></span>
                <div class="nas-content">
                  <span class="nas-tag">Approval</span>
                  <a href="news-article.php?id=suam-eia" class="nas-headline">NEMA clearance granted â€” Suam Border Post</a>
                  <time class="nas-time" datetime="2026-05-10">10 May 2026</time>
                </div>
              </li>
              <li class="nas-item">
                <span class="nas-dot" aria-hidden="true"></span>
                <div class="nas-content">
                  <span class="nas-tag">Milestone</span>
                  <a href="news-article.php?id=maili-tatu-milestone" class="nas-headline">Maili Tatu Blocks Aâ€“F structural works certified</a>
                  <time class="nas-time" datetime="2026-05-20">20 May 2026</time>
                </div>
              </li>
              <li class="nas-item">
                <span class="nas-dot" aria-hidden="true"></span>
                <div class="nas-content">
                  <span class="nas-tag">Report</span>
                  <a href="news.php" class="nas-headline">Q1 2026 Progress Report now available</a>
                  <time class="nas-time" datetime="2026-04-30">30 Apr 2026</time>
                </div>
              </li>
              <li class="nas-item">
                <span class="nas-dot" aria-hidden="true"></span>
                <div class="nas-content">
                  <span class="nas-tag">Notice</span>
                  <a href="news.php" class="nas-headline">Matunda AHP ground-floor columns cast â€” site visit report</a>
                  <time class="nas-time" datetime="2026-04-18">18 Apr 2026</time>
                </div>
              </li>
              <li class="nas-item">
                <span class="nas-dot" aria-hidden="true"></span>
                <div class="nas-content">
                  <span class="nas-tag">Policy</span>
                  <a href="about.php" class="nas-headline">National AHP allocation criteria updated for 2026</a>
                  <time class="nas-time" datetime="2026-04-05">05 Apr 2026</time>
                </div>
              </li>
            </ul>
          </aside>

        </div>
      </div>
    </section>

    <!-- ===== S5: ECITIZEN CTA ===== -->
    <section class="ecitizen-cta" aria-labelledby="ecitizen-heading">
      <div class="ecitizen-cta-bg" aria-hidden="true">
        <img src="uploads/site-photos/maili-tatu-2.jpg" alt="" loading="lazy" onerror="this.style.display='none'">
        <div class="ecitizen-cta-overlay"></div>
      </div>
      <div class="container">
        <div class="ecitizen-inner fade-up">

          <!-- Left: headline & CTA -->
          <div class="ecitizen-left">
            <div class="ecitizen-eyebrow">
              <img src="uploads/logos/BomaYanguLogo.png" alt="Boma Yangu" loading="lazy" onerror="this.style.display='none'">
              <span>Boma Yangu &middot; eCitizen</span>
            </div>
            <h2 id="ecitizen-heading" class="ecitizen-headline">Your Home.<br>Applied Online.</h2>
            <p class="ecitizen-sub">Register on the national Boma Yangu portal via eCitizen, save your deposit, and choose your preferred constituency in Trans-Nzoia County.</p>
            <div class="ecitizen-actions">
              <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="ecitizen-btn-main">
                Apply via eCitizen <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              </a>
              <a href="about.php" class="ecitizen-btn-ghost">
                About the programme <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </div>

          <!-- Right: How to Apply â€” 2Ã—2 icon grid -->
          <div class="ecitizen-steps" aria-label="How to apply via eCitizen">
            <h3 class="ecitizen-steps-title">
              <i class="fa-solid fa-list-check" aria-hidden="true"></i> How to Apply
            </h3>
            <div class="ecitizen-steps-grid">
              <div class="ecitizen-step-card">
                <div class="ecitizen-step-icon-wrap">
                  <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                  <span class="ecitizen-step-num">01</span>
                </div>
                <strong>Create eCitizen Account</strong>
                <p>Register at ecitizen.go.ke with your National ID or Passport.</p>
              </div>
              <div class="ecitizen-step-card">
                <div class="ecitizen-step-icon-wrap">
                  <i class="fa-solid fa-house-circle-check" aria-hidden="true"></i>
                  <span class="ecitizen-step-num">02</span>
                </div>
                <strong>Register on Boma Yangu</strong>
                <p>Complete your housing application profile on the portal.</p>
              </div>
              <div class="ecitizen-step-card">
                <div class="ecitizen-step-icon-wrap">
                  <i class="fa-solid fa-piggy-bank" aria-hidden="true"></i>
                  <span class="ecitizen-step-num">03</span>
                </div>
                <strong>Save Your Deposit</strong>
                <p>Min. KES 1,000/month to the Boma Yangu savings account.</p>
              </div>
              <div class="ecitizen-step-card">
                <div class="ecitizen-step-icon-wrap">
                  <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                  <span class="ecitizen-step-num">04</span>
                </div>
                <strong>Select Trans-Nzoia</strong>
                <p>Choose your preferred constituency as your allocation preference.</p>
              </div>
            </div>
          </div>

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