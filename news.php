<?php
$basePath = '';
$activePage = 'news';
$pageTitle = 'News & Updates | Trans-Nzoia County AHP Tracker';
$pageDescription = 'Latest news, official announcements, construction progress reports, and community updates from the Trans-Nzoia County Affordable Housing Programme.';
$pageKeywords = 'Trans-Nzoia housing news, AHP announcements, affordable housing Kenya, county housing updates';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/news.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/news.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/pages/news.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="News &amp; Updates | Trans-Nzoia County AHP Tracker">',
  '<meta property="og:description" content="Latest news, announcements and construction updates from the Trans-Nzoia County Affordable Housing Programme.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/news.php">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">',
  '<meta name="twitter:title" content="News &amp; Updates | Trans-Nzoia County AHP Tracker">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ===== HERO ===== -->
    <section class="news-hero" aria-label="News and updates">
      <div class="news-hero-bg" aria-hidden="true"></div>
      <div class="news-hero-glow" aria-hidden="true"></div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">News &amp; Updates</span>
        </nav>
        <div class="news-hero-inner">
          <div class="news-hero-layout">

            <!-- Left Column -->
            <div class="news-hero-left">
              <div class="news-hero-badge">
                <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                News &amp; Updates
              </div>
              <h1 class="news-hero-title">
                <span class="title-plain">Latest </span><span class="title-accent">News &amp;</span><br>
                <span class="title-plain">Announcements</span>
              </h1>
              <p class="news-hero-sub">Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.</p>
              <div class="news-search-wrap" role="search">
                <i class="fa-solid fa-magnifying-glass news-search-icon" aria-hidden="true"></i>
                <input type="search" class="news-search-input" id="newsSearch" placeholder="Search articlesâ€¦" aria-label="Search news articles" autocomplete="off">
                <button class="news-search-clear" id="newsSearchClear" type="button" aria-label="Clear search">
                  <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
              </div>
              <div class="news-hero-stats" aria-label="Programme statistics">
                <div class="news-stat-block">
                  <span class="news-stat-num">12</span>
                  <span class="news-stat-lbl">Articles</span>
                </div>
                <div class="news-stat-block">
                  <span class="news-stat-num">7</span>
                  <span class="news-stat-lbl">Categories</span>
                </div>
                <div class="news-stat-block">
                  <span class="news-stat-num">May&nbsp;'26</span>
                  <span class="news-stat-lbl">Last Updated</span>
                </div>
              </div>
            </div>

            <!-- Right Column â€” Decorative Mosaic -->
            <div class="news-hero-right">
              <div class="news-hero-mosaic" aria-hidden="true">
                <div class="mosaic-tile mosaic-tile--programme">
                  <i class="fa-solid fa-bullhorn"></i><span>Programme</span>
                </div>
                <div class="mosaic-tile mosaic-tile--groundbreak">
                  <i class="fa-solid fa-hammer"></i><span>Groundbreaking</span>
                </div>
                <div class="mosaic-tile mosaic-tile--construction">
                  <i class="fa-solid fa-hard-hat"></i><span>Construction</span>
                </div>
                <div class="mosaic-tile mosaic-tile--policy">
                  <i class="fa-solid fa-file-contract"></i><span>Policy</span>
                </div>
                <div class="mosaic-tile mosaic-tile--community">
                  <i class="fa-solid fa-people-group"></i><span>Community</span>
                </div>
                <div class="mosaic-tile mosaic-tile--official">
                  <i class="fa-solid fa-stamp"></i><span>Official</span>
                </div>
                <div class="mosaic-tile mosaic-tile--field">
                  <i class="fa-solid fa-map-pin"></i><span>Field Reports</span>
                </div>
                <div class="mosaic-tile mosaic-tile--count">
                  <span class="mosaic-big-num">12</span>
                  <span class="mosaic-big-lbl">Total<br>Articles</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>

    <!-- ===== FEATURED ARTICLE ===== -->
    <section class="news-featured-section" aria-label="Featured article">
      <div class="container">
        <p class="news-section-label"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured Story</p>
        <article class="news-featured">
          <div class="news-featured-img">
            <div class="news-featured-img-placeholder" aria-hidden="true">
              <i class="fa-solid fa-newspaper"></i>
              <span>Featured Article</span>
            </div>
            <img src="uploads/news/featured-maili-tatu.jpg" alt="Beneficiaries receive housing unit keys at Maili Tatu Estate, Kitale" loading="eager" onerror="this.style.display='none'">
            <div class="news-featured-img-overlay" aria-hidden="true"></div>
            <span class="news-cat-badge news-cat--programme-updates">Programme Updates</span>
            <span class="news-featured-label-badge"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</span>
          </div>
          <div class="news-featured-body">
            <div class="news-featured-meta">
              <time class="news-featured-date" datetime="2026-05-15">
                <i class="fa-regular fa-calendar" aria-hidden="true"></i> 15 May 2026
              </time>
              <span class="news-featured-read">
                <i class="fa-regular fa-clock" aria-hidden="true"></i> 6 min read
              </span>
            </div>
            <h2 class="news-featured-title">Trans-Nzoia AHP Delivers First 200 Units at Maili Tatu Estate â€” Beneficiaries Receive Keys</h2>
            <p class="news-featured-excerpt">In a landmark ceremony held at the Maili Tatu Estate in Kitale, Governor George Natembeya presided over the handover of 200 affordable housing units to qualifying beneficiaries. The milestone marks a significant step in the county's commitment to delivering dignified, affordable housing to low- and middle-income residents under the national AHP framework.</p>
            <div class="news-featured-author">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
              Trans-Nzoia County Department of Land, Housing &amp; Physical Planning
            </div>
            <a href="news-article.php" class="news-featured-cta">
              Read Full Article <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </article>
      </div>
    </section>

    <!-- ===== FILTER BAR ===== -->
    <div class="news-filter-bar" role="toolbar" aria-label="Filter articles by category">
      <div class="container">
        <div class="news-filter-inner">
          <div class="news-filter-pills" role="group" aria-label="Category filters">
            <button class="news-filter-pill is-active" data-category="all" type="button"><span class="pill-dot"></span>All Articles</button>
            <button class="news-filter-pill" data-category="programme-updates" type="button"><span class="pill-dot"></span>Programme Updates</button>
            <button class="news-filter-pill" data-category="groundbreaking" type="button"><span class="pill-dot"></span>Groundbreaking</button>
            <button class="news-filter-pill" data-category="construction" type="button"><span class="pill-dot"></span>Construction</button>
            <button class="news-filter-pill" data-category="policy" type="button"><span class="pill-dot"></span>Policy</button>
            <button class="news-filter-pill" data-category="community" type="button"><span class="pill-dot"></span>Community</button>
            <button class="news-filter-pill" data-category="official" type="button"><span class="pill-dot"></span>Official</button>
            <button class="news-filter-pill" data-category="field-reports" type="button"><span class="pill-dot"></span>Field Reports</button>
          </div>
          <div class="news-filter-right">
            <span class="news-results-count" id="newsResultsCount" aria-live="polite" aria-atomic="true">12 articles</span>
            <select class="news-sort-select" id="newsSort" aria-label="Sort articles">
              <option value="latest">Latest First</option>
              <option value="oldest">Oldest First</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== NEWS GRID ===== -->
    <section class="news-grid-section" aria-label="News articles">
      <div class="container">
        <div class="news-grid-header">
          <h2 class="news-grid-title">All Articles</h2>
          <span class="news-grid-subtitle" id="newsGridSubtitle">Showing latest news &amp; updates</span>
        </div>
        <div class="news-grid" id="newsGrid">

          <!-- Card 1 -->
          <article class="news-card" data-category="groundbreaking" data-date="2026-05-10" data-title="ground-breaking ceremony marks start of endebess phase 2 construction">
            <div class="news-card-img">
              <img src="uploads/news/endebess-groundbreaking.jpg" alt="Ground-breaking ceremony at Endebess Phase 2 housing site" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--groundbreaking">Groundbreaking</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-05-10"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 10 May 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 3 min</span>
              </div>
              <h3 class="news-card-title">Ground-Breaking Ceremony Marks Start of Endebess Phase 2 Construction</h3>
              <p class="news-card-excerpt">County officials and community leaders gathered at the Endebess site to officially launch the second phase of the affordable housing project, set to deliver 120 additional units to qualifying residents.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 2 -->
          <article class="news-card" data-category="construction" data-date="2026-05-08" data-title="cherangany housing project reaches 70% completion milestone">
            <div class="news-card-img">
              <img src="uploads/news/cherangany-progress.jpg" alt="Aerial view of Cherangany housing project construction progress" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--construction">Construction</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-05-08"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 8 May 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 4 min</span>
              </div>
              <h3 class="news-card-title">Cherangany Housing Project Reaches 70% Completion Milestone</h3>
              <p class="news-card-excerpt">The Cherangany constituency AHP project has surpassed the 70% completion threshold, with roofing works now underway on the final two residential blocks ahead of the projected Q3 2026 handover date.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 3 -->
          <article class="news-card" data-category="policy" data-date="2026-05-05" data-title="county receives ksh 1.8 billion national ahp allocation for fy 2026/27">
            <div class="news-card-img">
              <img src="uploads/news/policy-allocation.jpg" alt="County executive committee meeting on housing budget allocation" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--policy">Policy</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-05-05"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 5 May 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 5 min</span>
              </div>
              <h3 class="news-card-title">County Receives KSh 1.8 Billion National AHP Allocation for FY 2026/27</h3>
              <p class="news-card-excerpt">The National Treasury has confirmed a KSh 1.8 billion disbursement to Trans-Nzoia County under the Affordable Housing Programme for the upcoming financial year, enabling the commencement of three new estate projects.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 4 -->
          <article class="news-card" data-category="community" data-date="2026-05-03" data-title="community stakeholders forum held in kwanza sub-county">
            <div class="news-card-img">
              <img src="uploads/news/kwanza-forum.jpg" alt="Community members at stakeholder engagement forum in Kwanza" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--community">Community</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-05-03"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 3 May 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 3 min</span>
              </div>
              <h3 class="news-card-title">Community Stakeholders Forum Held in Kwanza Sub-County</h3>
              <p class="news-card-excerpt">Over 400 residents attended the public participation forum in Kwanza, where the Housing Department presented the beneficiary registration process, eligibility criteria, and construction timelines for the local AHP estate.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 5 -->
          <article class="news-card" data-category="official" data-date="2026-05-01" data-title="governor issues statement on beneficiary selection and allocation criteria">
            <div class="news-card-img">
              <img src="uploads/news/governor-statement.jpg" alt="Governor addressing beneficiary selection criteria at press conference" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--official">Official Statement</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-05-01"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 1 May 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 2 min</span>
              </div>
              <h3 class="news-card-title">Governor Issues Statement on Beneficiary Selection and Allocation Criteria</h3>
              <p class="news-card-excerpt">The Office of the Governor has released an official statement clarifying the eligibility criteria for affordable housing unit allocation, reaffirming that selection will be transparent, needs-based, and verifiable through public registers.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 6 -->
          <article class="news-card" data-category="field-reports" data-date="2026-04-28" data-title="field report site visit to kiminini social housing complex">
            <div class="news-card-img">
              <img src="uploads/news/kiminini-site.jpg" alt="Site inspection at Kiminini Social Housing Complex" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--field-reports">Field Report</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-28"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 28 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 5 min</span>
              </div>
              <h3 class="news-card-title">Field Report: Site Visit to Kiminini Social Housing Complex</h3>
              <p class="news-card-excerpt">County housing inspectors conducted a comprehensive site assessment at the Kiminini Social Housing Complex, confirming structural compliance on all four residential blocks and certifying the plumbing installations on Blocks A and B.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 7 -->
          <article class="news-card" data-category="programme-updates" data-date="2026-04-25" data-title="national housing corporation signs new agreement with trans-nzoia county">
            <div class="news-card-img">
              <img src="uploads/news/nhc-agreement.jpg" alt="Signing of implementation agreement between NHC and Trans-Nzoia County" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--programme-updates">Programme Updates</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-25"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 25 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 4 min</span>
              </div>
              <h3 class="news-card-title">National Housing Corporation Signs New Implementation Agreement with Trans-Nzoia County</h3>
              <p class="news-card-excerpt">The National Housing Corporation and Trans-Nzoia County Government have signed a revised implementation agreement expanding the scope of the AHP programme to include two additional estate sites in Saboti and Kwanza constituencies.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 8 -->
          <article class="news-card" data-category="community" data-date="2026-04-22" data-title="residents urged to register beneficiary interest via ecitizen portal">
            <div class="news-card-img">
              <img src="uploads/news/ecitizen-campaign.jpg" alt="County official demonstrating eCitizen registration at community outreach" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--community">Community</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-22"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 22 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 3 min</span>
              </div>
              <h3 class="news-card-title">Residents Urged to Register Beneficiary Interest via eCitizen Portal</h3>
              <p class="news-card-excerpt">The Department of Land, Housing and Physical Planning has called on all eligible Trans-Nzoia residents to formally register their interest for affordable housing units through the government's eCitizen online portal before the 30 June 2026 deadline.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 9 -->
          <article class="news-card" data-category="policy" data-date="2026-04-18" data-title="new affordable housing allocation policy framework released for public input">
            <div class="news-card-img">
              <img src="uploads/news/policy-framework.jpg" alt="County assembly housing committee reviewing policy framework document" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--policy">Policy</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-18"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 18 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 6 min</span>
              </div>
              <h3 class="news-card-title">New Affordable Housing Allocation Policy Framework Released for Public Input</h3>
              <p class="news-card-excerpt">The County Assembly Housing Committee has published a draft allocation policy framework for public comment, outlining income bands, household size requirements, and priority scoring for first-time homeowners under the AHP.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 10 (initially hidden â€” beyond BATCH_SIZE of 9) -->
          <article class="news-card" data-category="construction" data-date="2026-04-15" data-title="saboti constituency first 50 housing units ready for beneficiary handover">
            <div class="news-card-img">
              <img src="uploads/news/saboti-handover.jpg" alt="Completed housing units in Saboti ready for beneficiary handover" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--construction">Construction</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-15"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 15 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 4 min</span>
              </div>
              <h3 class="news-card-title">Saboti Constituency: First 50 Housing Units Ready for Beneficiary Handover</h3>
              <p class="news-card-excerpt">Engineers have issued practical completion certificates for the first 50 units at the Saboti AHP estate following final electrical, plumbing, and structural sign-offs, clearing the way for the formal handover ceremony scheduled for May 2026.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 11 -->
          <article class="news-card" data-category="community" data-date="2026-04-12" data-title="300 local youth artisans employed across trans-nzoia ahp construction sites">
            <div class="news-card-img">
              <img src="uploads/news/youth-employment.jpg" alt="Young artisans working on affordable housing construction site" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--community">Community</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-12"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 12 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 3 min</span>
              </div>
              <h3 class="news-card-title">300 Local Youth Artisans Employed Across Trans-Nzoia AHP Construction Sites</h3>
              <p class="news-card-excerpt">A county-led youth employment initiative has placed over 300 trained artisans â€” including masons, carpenters, and electricians â€” on active AHP construction sites, aligning housing delivery with local economic empowerment goals.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Card 12 -->
          <article class="news-card" data-category="programme-updates" data-date="2026-04-08" data-title="ahp programme update 1200 units under active construction county-wide">
            <div class="news-card-img">
              <img src="uploads/news/programme-overview.jpg" alt="Overview of multiple AHP construction sites across Trans-Nzoia County" loading="lazy" onerror="this.style.display='none'">
              <span class="news-cat-badge news-cat--programme-updates">Programme Updates</span>
            </div>
            <div class="news-card-body">
              <div class="news-card-meta">
                <time class="news-card-date" datetime="2026-04-08"><i class="fa-regular fa-calendar" aria-hidden="true"></i> 8 Apr 2026</time>
                <span class="news-card-read"><i class="fa-regular fa-clock" aria-hidden="true"></i> 5 min</span>
              </div>
              <h3 class="news-card-title">AHP Programme Update: 1,200 Units Under Active Construction County-Wide</h3>
              <p class="news-card-excerpt">The latest quarterly programme report confirms that 1,200 affordable housing units are now under active construction across all five constituencies in Trans-Nzoia County, representing a 40% increase on the previous quarter's figures.</p>
              <a href="news-article.php" class="news-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>

          <!-- Empty State -->
          <div class="news-empty" id="newsEmpty" role="status" aria-live="polite">
            <div class="news-empty-icon" aria-hidden="true">
              <i class="fa-solid fa-newspaper"></i>
            </div>
            <h3>No Articles Found</h3>
            <p>No articles match your current search or filter. Try a different keyword or category.</p>
            <button class="news-empty-reset" id="emptyReset" type="button">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Clear Filters
            </button>
          </div>

        </div><!-- /news-grid -->

        <!-- Load More -->
        <div class="news-load-more-wrap">
          <button class="news-load-more-btn" id="loadMoreBtn" type="button">
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            Load More Articles
          </button>
          <span class="news-load-more-label" id="loadMoreLabel" aria-live="polite"></span>
        </div>

      </div>
    </section>

    <!-- ===== STAY UPDATED CTA ===== -->
    <section class="news-cta-strip" aria-label="Stay updated">
      <div class="container">
        <div class="news-cta-inner">
          <div class="news-cta-text">
            <h2>Stay <span>Up to Date</span></h2>
            <p>Follow the programme on social media or apply for housing directly through the eCitizen portal to receive official notifications about unit availability and beneficiary selection.</p>
          </div>
          <div class="news-cta-actions">
            <div class="news-cta-social" aria-label="Follow us on social media">
              <a href="#" aria-label="Follow us on X / Twitter">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
              <a href="#" aria-label="Follow us on Facebook">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a href="#" aria-label="Watch us on YouTube">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.08C5.12 19.54 12 19.54 12 19.54s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2A29 29 0 0 0 23 11.75a29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
              </a>
            </div>
            <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="news-cta-ecitizen">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              Apply via eCitizen
            </a>
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