<?php
$basePath = '';
$activePage = 'about';
$pageTitle = 'About the Programme | Trans-Nzoia AHP Tracker';
$pageDescription = 'About the Trans-Nzoia County Affordable Housing Programme â€” mission, legal framework, implementing partners, timeline and how to apply.';
$pageKeywords = 'Trans-Nzoia affordable housing, AHP Kenya, housing programme, Boma Yangu, county housing policy';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/about.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/about.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/pages/about.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="About the Programme | Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Learn about the Trans-Nzoia County Affordable Housing Programme â€” mandate, partners, legal framework and how to apply.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/about.php">',
  '<meta property="og:image" content="uploads/heroes/hero-main.jpg">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">',
  '<meta name="twitter:title" content="About the Programme | Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:description" content="Learn about the Trans-Nzoia County Affordable Housing Programme.">',
  '<meta name="twitter:image" content="uploads/heroes/hero-main.jpg">'
];
include __DIR__ . "/app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- ============================================================
         HERO
    ============================================================ -->
    <section class="ab-hero" aria-label="About the programme">
      <div class="ab-hero-bg" aria-hidden="true">
        <img src="uploads/gallery/maili-tatu-2.jpg" alt="" loading="eager" onerror="this.style.display='none'">
        <div class="ab-hero-overlay"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">About</span>
        </nav>
        <div class="ab-hero-body">
          <div class="ab-hero-eyebrow">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            National Affordable Housing Programme &mdash; Trans-Nzoia County
          </div>
          <h1 class="ab-hero-title">Delivering Affordable Homes for Trans-Nzoia County Residents</h1>
          <p class="ab-hero-desc">A government-led initiative to provide quality, subsidised housing to low-to-middle-income earners across all five constituencies â€” tracked transparently for every resident.</p>
          <div class="ab-hero-ctas">
            <a href="projects.php" class="ab-btn-lime">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i> View All Projects
            </a>
            <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="ab-btn-outline">
              Apply on Boma Yangu <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>
        </div>
        <div class="ab-hero-kpis" aria-label="Programme key figures">
          <div class="ab-kpi">
            <i class="fa-solid fa-map-location-dot ab-kpi-icon" aria-hidden="true"></i>
            <span class="ab-kpi-val">5</span>
            <span class="ab-kpi-lbl">Constituencies</span>
          </div>
          <div class="ab-kpi">
            <i class="fa-solid fa-building-columns ab-kpi-icon" aria-hidden="true"></i>
            <span class="ab-kpi-val">8</span>
            <span class="ab-kpi-lbl">Projects</span>
          </div>
          <div class="ab-kpi">
            <i class="fa-solid fa-house-chimney ab-kpi-icon" aria-hidden="true"></i>
            <span class="ab-kpi-val">1,730</span>
            <span class="ab-kpi-lbl">Units Planned</span>
          </div>
          <div class="ab-kpi">
            <i class="fa-solid fa-calendar-days ab-kpi-icon" aria-hidden="true"></i>
            <span class="ab-kpi-val">2024</span>
            <span class="ab-kpi-lbl">Programme Launch</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         PROGRAMME OVERVIEW
    ============================================================ -->
    <section class="ab-overview fade-up" aria-label="Programme overview">
      <div class="container">
        <div class="ab-overview-inner">
          <div class="ab-overview-copy">
            <div class="ab-section-eyebrow">Programme Overview</div>
            <h2 class="ab-section-title">What Is the Affordable Housing Programme?</h2>
            <p class="ab-overview-lead">The National Affordable Housing Programme (AHP) is a flagship initiative of the Kenya Kwanza Administration, aimed at bridging the nation's housing deficit by delivering quality, affordable units to low-to-middle-income earners.</p>
            <p class="ab-overview-body">In Trans-Nzoia County, the programme is implemented jointly by the <strong>State Department of Housing &amp; Urban Development</strong> and the <strong>Trans-Nzoia County Government â€” Department of Land, Housing &amp; Physical Planning</strong>. Projects span all five constituencies, from the flagship Maili Tatu Estate in Kitale to the strategic Suam Border Post Estate in Endebess.</p>
            <p class="ab-overview-body">Units are allocated exclusively through the <strong>Boma Yangu Portal</strong>, using a transparent ballot to ensure equitable access for all registered applicants across Trans-Nzoia County.</p>
            <a href="projects.php" class="ab-text-link">
              Explore all active projects <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
          <div class="ab-snapshot">
            <div class="ab-snapshot-header">
              <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
              Programme at a Glance
            </div>
            <ul class="ab-snapshot-list">
              <li>
                <span class="ab-snap-label">Launch Year</span>
                <span class="ab-snap-value">2024</span>
              </li>
              <li>
                <span class="ab-snap-label">Implementing Ministry</span>
                <span class="ab-snap-value">State Dept. of Housing</span>
              </li>
              <li>
                <span class="ab-snap-label">Primary Funding</span>
                <span class="ab-snap-value">National AHP Fund</span>
              </li>
              <li>
                <span class="ab-snap-label">Target Beneficiaries</span>
                <span class="ab-snap-value">Low-to-Middle Income Earners</span>
              </li>
              <li>
                <span class="ab-snap-label">Unit Price Range</span>
                <span class="ab-snap-value">KES 1.0M &ndash; 3.0M</span>
              </li>
              <li>
                <span class="ab-snap-label">Eligibility Portal</span>
                <span class="ab-snap-value">
                  <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer">
                    bomayangu.go.ke <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px"></i>
                  </a>
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         PROGRAMME PILLARS
    ============================================================ -->
    <section class="ab-pillars fade-up" aria-label="Programme pillars">
      <div class="container">
        <div class="ab-pillars-header">
          <div class="ab-section-eyebrow ab-section-eyebrow--light">Our Pillars</div>
          <h2 class="ab-section-title ab-section-title--light">Built on Four Core Commitments</h2>
          <p class="ab-section-sub ab-section-sub--light">Every decision, project and process is guided by four foundational principles.</p>
        </div>
        <div class="ab-pillars-grid">
          <div class="ab-pillar-card">
            <div class="ab-pillar-icon-wrap" aria-hidden="true">
              <i class="fa-solid fa-coins"></i>
            </div>
            <h3 class="ab-pillar-title">Affordability</h3>
            <p class="ab-pillar-desc">Units priced KES 1.0Mâ€“3.0M â€” below market rate â€” with KMRC mortgage products making repayments accessible for households earning from KES 15,000/month under GoK subsidy.</p>
          </div>
          <div class="ab-pillar-card">
            <div class="ab-pillar-icon-wrap" aria-hidden="true">
              <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>
            <h3 class="ab-pillar-title">Transparency</h3>
            <p class="ab-pillar-desc">This public tracker publishes real-time construction progress, contractor identities, milestone timelines and site inspection reports â€” every citizen is an accountability overseer.</p>
          </div>
          <div class="ab-pillar-card">
            <div class="ab-pillar-icon-wrap" aria-hidden="true">
              <i class="fa-solid fa-gauge-high"></i>
            </div>
            <h3 class="ab-pillar-title">Speed of Delivery</h3>
            <p class="ab-pillar-desc">Contractors are bound by time-based performance contracts. Milestone payments are certified by independent site engineers to prevent delay and stalled works.</p>
          </div>
          <div class="ab-pillar-card">
            <div class="ab-pillar-icon-wrap" aria-hidden="true">
              <i class="fa-solid fa-people-roof"></i>
            </div>
            <h3 class="ab-pillar-title">Community Impact</h3>
            <p class="ab-pillar-desc">Estates are co-planned with road access, water, sanitation and proximity to schools â€” ensuring developments lift entire communities beyond simply providing shelter.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         STATS STRIP
    ============================================================ -->
    <div class="ab-stats-strip" aria-label="Programme statistics">
      <div class="container">
        <div class="ab-stats-inner">
          <div class="ab-stat-item">
            <span class="ab-stat-val" data-target="5">0</span>
            <span class="ab-stat-lbl">Constituencies</span>
          </div>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <div class="ab-stat-item">
            <span class="ab-stat-val" data-target="8">0</span>
            <span class="ab-stat-lbl">Projects</span>
          </div>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <div class="ab-stat-item">
            <span class="ab-stat-val" data-target="1730">0</span>
            <span class="ab-stat-lbl">Units Planned</span>
          </div>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <div class="ab-stat-item">
            <span class="ab-stat-val" data-target="8650">0</span>
            <span class="ab-stat-lbl">Families to Benefit</span>
          </div>
          <div class="ab-stat-divider" aria-hidden="true"></div>
          <div class="ab-stat-item">
            <span class="ab-stat-val ab-stat-static">2024</span>
            <span class="ab-stat-lbl">Year Launched</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PROGRAMME TIMELINE
    ============================================================ -->
    <section class="ab-timeline fade-up" aria-label="Programme timeline">
      <div class="container">
        <div class="ab-timeline-header">
          <div class="ab-section-eyebrow ab-section-eyebrow--light">Programme History</div>
          <h2 class="ab-section-title ab-section-title--light">From Announcement to Active Construction</h2>
          <p class="ab-section-sub ab-section-sub--light">A chronological record of how affordable housing came to life in Trans-Nzoia County.</p>
        </div>
        <ol class="ab-tl-track">

          <li class="ab-tl-item ab-tl-done">
            <span class="ab-tl-year">2022</span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid fa-flag"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title">National Programme Announced</h3>
              <p class="ab-tl-desc">President Ruto announced the Affordable Housing Programme as a flagship Big Four commitment, targeting 200,000 affordable units per year nationwide to address Kenya's housing deficit.</p>
              <span class="ab-tl-badge ab-tl-badge--done"><i class="fa-solid fa-check" aria-hidden="true"></i> Completed</span>
            </div>
          </li>

          <li class="ab-tl-item ab-tl-done">
            <span class="ab-tl-year">2023</span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid fa-map-pin"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title">Trans-Nzoia Sites Identified &amp; Gazetted</h3>
              <p class="ab-tl-desc">Trans-Nzoia County Government, working with the State Department of Housing, identified and gazetted five priority sites across all constituencies. Land allocation and community consultations completed.</p>
              <span class="ab-tl-badge ab-tl-badge--done"><i class="fa-solid fa-check" aria-hidden="true"></i> Completed</span>
            </div>
          </li>

          <li class="ab-tl-item ab-tl-done">
            <span class="ab-tl-year">Early 2024</span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid fa-file-signature"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title">Contractors Appointed &amp; Groundbreaking Ceremonies</h3>
              <p class="ab-tl-desc">Following competitive PPRA-supervised tender processes, contractors were appointed for Saboti and Cherangany. Groundbreaking ceremonies were held in March 2024 with county leadership and communities.</p>
              <span class="ab-tl-badge ab-tl-badge--done"><i class="fa-solid fa-check" aria-hidden="true"></i> Completed</span>
            </div>
          </li>

          <li class="ab-tl-item ab-tl-done">
            <span class="ab-tl-year">2024 â€“ 2025</span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid fa-hard-hat"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title">Active Construction Begins Across Three Constituencies</h3>
              <p class="ab-tl-desc">Construction commenced across Saboti, Cherangany and Endebess. Maili Tatu Estate progressed to structural frame completion by late 2024. Matunda AHP and Suam Border Estate mobilised through 2025.</p>
              <span class="ab-tl-badge ab-tl-badge--done"><i class="fa-solid fa-check" aria-hidden="true"></i> Completed</span>
            </div>
          </li>

          <li class="ab-tl-item ab-tl-current">
            <span class="ab-tl-year">2026 &mdash; Now</span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid fa-rotate"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title">Roofing, Finishing &amp; Planning Stage Expansion</h3>
              <p class="ab-tl-desc">Maili Tatu Estate is at roofing and finishing stage targeting Q4 2026 handover. Kiminini and Kwanza are progressing through EIA and design phases. First block completions expected by year-end.</p>
              <span class="ab-tl-badge ab-tl-badge--current"><i class="fa-solid fa-rotate fa-spin" aria-hidden="true"></i> In Progress</span>
            </div>
          </li>

          <li class="ab-tl-item">
            <span class="ab-tl-year">2026 â€“ 2028</span>
            <div class="ab-tl-node" aria-hidden="true">
              <div class="ab-tl-dot"><i class="fa-solid fa-key"></i></div>
              <div class="ab-tl-connector"></div>
            </div>
            <div class="ab-tl-content">
              <h3 class="ab-tl-title">Unit Handovers &amp; Boma Yangu Allocation Ballot</h3>
              <p class="ab-tl-desc">Completed units will be allocated via the Boma Yangu ballot system to registered applicants. First handovers in Saboti target Q4 2026, with all remaining projects completing through 2028.</p>
              <span class="ab-tl-badge"><i class="fa-solid fa-clock" aria-hidden="true"></i> Upcoming</span>
            </div>
          </li>

        </ol>
      </div>
    </section>

    <!-- ============================================================
         LEGAL FRAMEWORK
    ============================================================ -->
    <section class="ab-legal fade-up" aria-label="Legal and regulatory framework">
      <div class="container">
        <div class="ab-legal-header">
          <div class="ab-section-eyebrow">Legal Framework</div>
          <h2 class="ab-section-title">Backed by Law &amp; Policy</h2>
          <p class="ab-section-sub">A robust legal and regulatory framework ensures compliance, accountability and environmental responsibility across every project site.</p>
        </div>
        <div class="ab-legal-grid">
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid fa-scale-balanced"></i></div>
            <h3 class="ab-legal-title">Affordable Housing Act, 2024</h3>
            <p class="ab-legal-desc">The principal legislation establishing the Affordable Housing Fund, Levy and Board &mdash; providing the legal basis for land allocation, contractor procurement and unit pricing regulations.</p>
            <a href="https://www.kenyalaw.org" target="_blank" rel="noopener noreferrer" class="ab-legal-link">Read on Kenya Law <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          </div>
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid fa-book"></i></div>
            <h3 class="ab-legal-title">Housing Act, CAP 117</h3>
            <p class="ab-legal-desc">Kenya&rsquo;s foundational housing statute governing standards and public housing authorities, providing the framework for national housing delivery and intergovernmental coordination in estate management.</p>
            <a href="https://www.kenyalaw.org" target="_blank" rel="noopener noreferrer" class="ab-legal-link">Read on Kenya Law <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          </div>
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid fa-leaf"></i></div>
            <h3 class="ab-legal-title">NEMA Environmental Approvals</h3>
            <p class="ab-legal-desc">All project sites require a full Environmental Impact Assessment (EIA) clearance from the National Environment Management Authority before any construction can commence.</p>
            <a href="https://www.nema.go.ke" target="_blank" rel="noopener noreferrer" class="ab-legal-link">Visit NEMA <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          </div>
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid fa-helmet-safety"></i></div>
            <h3 class="ab-legal-title">NCA Contractor Registration</h3>
            <p class="ab-legal-desc">All contractors must hold current National Construction Authority registration at the appropriate grade for the scale and category of works on each project site.</p>
            <a href="https://www.nca.go.ke" target="_blank" rel="noopener noreferrer" class="ab-legal-link">Visit NCA <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          </div>
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid fa-handshake"></i></div>
            <h3 class="ab-legal-title">Intergovernmental Relations Act, 2012</h3>
            <p class="ab-legal-desc">Establishes the legal framework for cooperation between the national government and county governments &mdash; enabling coordinated housing delivery, shared land management and joint community engagement under the AHP.</p>
            <a href="https://www.kenyalaw.org" target="_blank" rel="noopener noreferrer" class="ab-legal-link">Read on Kenya Law <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          </div>
          <div class="ab-legal-card">
            <div class="ab-legal-icon" aria-hidden="true"><i class="fa-solid fa-file-contract"></i></div>
            <h3 class="ab-legal-title">Public Procurement Act, 2015</h3>
            <p class="ab-legal-desc">All contractor selection follows the Public Procurement and Asset Disposal Act, 2015, ensuring open, competitive and transparent tendering for every construction contract.</p>
            <a href="https://ppra.go.ke" target="_blank" rel="noopener noreferrer" class="ab-legal-link">Visit PPRA <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         IMPLEMENTING PARTNERS
    ============================================================ -->
    <section class="ab-partners fade-up" aria-label="Implementing partners">
      <div class="container">
        <div class="ab-partners-header">
          <div class="ab-section-eyebrow ab-section-eyebrow--light">Partners</div>
          <h2 class="ab-section-title ab-section-title--light">Implementing Partners &amp; Stakeholders</h2>
          <p class="ab-section-sub ab-section-sub--light">Delivered through collaboration between national and county government agencies, financial institutions and regulatory bodies.</p>
        </div>
        <div class="ab-partners-grid">
          <div class="ab-partner-card">
            <div class="ab-partner-icon" aria-hidden="true"><i class="fa-solid fa-building-columns"></i></div>
            <h3 class="ab-partner-name">State Dept. of Housing</h3>
            <p class="ab-partner-role">Lead implementing agency â€” project oversight, contractor management and national housing policy direction</p>
          </div>
          <div class="ab-partner-card">
            <div class="ab-partner-icon" aria-hidden="true"><i class="fa-solid fa-landmark-flag"></i></div>
            <h3 class="ab-partner-name">Trans-Nzoia County Government</h3>
            <p class="ab-partner-role">Land allocation, community engagement, site supervision and county budget co-funding across all five constituencies</p>
          </div>
          <div class="ab-partner-card">
            <div class="ab-partner-icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <h3 class="ab-partner-name">Kenya Mortgage Refinance Company</h3>
            <p class="ab-partner-role">Long-term mortgage financing enabling low-income earners to afford monthly repayments on allocated units</p>
          </div>
          <div class="ab-partner-card">
            <div class="ab-partner-icon" aria-hidden="true"><i class="fa-solid fa-house-circle-check"></i></div>
            <h3 class="ab-partner-name">Boma Yangu Portal</h3>
            <p class="ab-partner-role">National beneficiary registration, savings tracking and transparent unit allocation ballot operated by Ardhi House</p>
          </div>
          <div class="ab-partner-card">
            <div class="ab-partner-icon" aria-hidden="true"><i class="fa-solid fa-helmet-safety"></i></div>
            <h3 class="ab-partner-name">National Construction Authority</h3>
            <p class="ab-partner-role">Contractor registration, site inspection compliance and occupational safety oversight for all active construction sites</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         FAQ TEASER
    ============================================================ -->
    <section class="ab-faq fade-up" aria-label="Frequently asked questions">
      <div class="container">
        <div class="ab-faq-wrap">
          <div class="ab-faq-header">
            <div class="ab-section-eyebrow">Common Questions</div>
            <h2 class="ab-section-title">Frequently Asked Questions</h2>
            <p class="ab-section-sub">Quick answers to the most common questions about the programme.</p>
          </div>
          <div class="ab-faq-list">

            <div class="ab-faq-item">
              <button class="ab-faq-trigger" aria-expanded="false" aria-controls="faq-1" id="faq-trigger-1">
                <span>What is the Affordable Housing Programme?</span>
                <i class="fa-solid fa-chevron-down ab-faq-chevron" aria-hidden="true"></i>
              </button>
              <div class="ab-faq-body" id="faq-1" role="region" aria-labelledby="faq-trigger-1">
                <p>The Affordable Housing Programme (AHP) is a Kenya Kwanza government initiative to address Kenya's housing deficit by constructing subsidised homes for low-to-middle-income earners. In Trans-Nzoia County, 1,730 units are targeted across all five constituencies through 8 active and planning-stage projects.</p>
              </div>
            </div>

            <div class="ab-faq-item">
              <button class="ab-faq-trigger" aria-expanded="false" aria-controls="faq-2" id="faq-trigger-2">
                <span>Who qualifies to apply for a unit?</span>
                <i class="fa-solid fa-chevron-down ab-faq-chevron" aria-hidden="true"></i>
              </button>
              <div class="ab-faq-body" id="faq-2" role="region" aria-labelledby="faq-trigger-2">
                <p>Any Kenyan citizen aged 18+ with a National ID or passport can apply. Priority is given to first-time homeowners, low-income earners (below KES 100,000/month household income) and residents of Trans-Nzoia County. Applicants must register on Boma Yangu and maintain an active savings history.</p>
              </div>
            </div>

            <div class="ab-faq-item">
              <button class="ab-faq-trigger" aria-expanded="false" aria-controls="faq-3" id="faq-trigger-3">
                <span>How are units allocated to beneficiaries?</span>
                <i class="fa-solid fa-chevron-down ab-faq-chevron" aria-hidden="true"></i>
              </button>
              <div class="ab-faq-body" id="faq-3" role="region" aria-labelledby="faq-trigger-3">
                <p>Units are allocated through a transparent ballot on the Boma Yangu platform. Registered applicants meeting savings thresholds and eligibility criteria are entered into a county-level lottery. Results are published publicly and applicants notified via SMS and email upon selection.</p>
              </div>
            </div>

            <div class="ab-faq-item">
              <button class="ab-faq-trigger" aria-expanded="false" aria-controls="faq-4" id="faq-trigger-4">
                <span>What is the minimum price for a housing unit?</span>
                <i class="fa-solid fa-chevron-down ab-faq-chevron" aria-hidden="true"></i>
              </button>
              <div class="ab-faq-body" id="faq-4" role="region" aria-labelledby="faq-trigger-4">
                <p>Studio units start at approximately KES 1.0 million, one-bedroom units at KES 1.5 million, and two-bedroom units at KES 2.5â€“3.0 million. Monthly repayments through KMRC can be as low as KES 3,500/month for qualifying beneficiaries under the GoK subsidy scheme.</p>
              </div>
            </div>

          </div>
          <div class="ab-faq-footer">
            <a href="faq.php" class="ab-text-link">
              Read all frequently asked questions <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         LEADERSHIP + CONTACT
    ============================================================ -->
    <section class="ab-leadership fade-up" aria-label="Programme leadership and contact">
      <div class="container">
        <div class="ab-leadership-inner">
          <div class="ab-leadership-col">
            <div class="ab-section-eyebrow">National Programme Leadership</div>
            <h2 class="ab-section-title">Programme Leadership</h2>
            <p class="ab-leadership-sub">This is a <strong>national government</strong> initiative delivered through the <strong>State Department of Housing &amp; Urban Development</strong>. The Trans-Nzoia field office operates from <strong>Ardhi House, County Commissioner&rsquo;s Premises, Kitale</strong> &mdash; not the County Government headquarters.</p>
            <div class="ab-leaders-grid">
              <div class="ab-leader-card">
                <div class="ab-leader-avatar" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
                <div class="ab-leader-info">
                  <span class="ab-leader-role">County Director (National Appointment)</span>
                  <h3 class="ab-leader-name">Moses Owuor</h3>
                  <span class="ab-leader-dept">State Dept. of Housing &amp; Urban Development &mdash; Trans-Nzoia Representative</span>
                </div>
              </div>
              <div class="ab-leader-card">
                <div class="ab-leader-avatar" aria-hidden="true"><i class="fa-solid fa-building-columns"></i></div>
                <div class="ab-leader-info">
                  <span class="ab-leader-role">Lead National Agency</span>
                  <h3 class="ab-leader-name">State Dept. of Housing &amp; Urban Development</h3>
                  <span class="ab-leader-dept">Ministry of Lands, Housing &amp; Urban Development &mdash; National Government</span>
                </div>
              </div>
            </div>
            <a href="leadership.php" class="ab-text-link" style="margin-top:var(--space-6);display:inline-flex;align-items:center;gap:var(--space-2)">
              Meet the full leadership team <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
          <div class="ab-contact-col">
            <div class="ab-contact-card">
              <div class="ab-contact-header">
                <div class="ab-contact-header-icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></div>
                <h3>Get In Touch</h3>
                <p>Have a question about the programme or your application? Reach the housing department directly.</p>
              </div>
              <ul class="ab-contact-list">
                <li>
                  <div class="ab-contact-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></div>
                  <div>
                    <strong>Physical Address</strong>
                    <span>Ardhi House, County Commissioner&rsquo;s Premises<br>Kitale, Trans-Nzoia County, Kenya</span>
                  </div>
                </li>
                <li>
                  <div class="ab-contact-icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></div>
                  <div>
                    <strong>Phone</strong>
                    <span><a href="tel:+254530000000">+254 53 000 0000</a></span>
                  </div>
                </li>
                <li>
                  <div class="ab-contact-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></div>
                  <div>
                    <strong>Email</strong>
                    <span><a href="mailto:housing@transnzoia.go.ke">housing@transnzoia.go.ke</a></span>
                  </div>
                </li>
                <li>
                  <div class="ab-contact-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></div>
                  <div>
                    <strong>Office Hours</strong>
                    <span>Monday &ndash; Friday, 8:00 AM &ndash; 5:00 PM</span>
                  </div>
                </li>
              </ul>
              <a href="contact.php" class="ab-contact-cta">
                Send a Message <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- ============================================================
       APPLY CTA BANNER
  ============================================================ -->
  <div class="ab-apply-banner" role="complementary" aria-label="Apply for affordable housing">
    <div class="container">
      <div class="ab-apply-inner">
        <div class="ab-apply-icon" aria-hidden="true">
          <i class="fa-solid fa-house-circle-check"></i>
        </div>
        <div class="ab-apply-copy">
          <h3 class="ab-apply-title">Ready to Apply for Affordable Housing in Trans-Nzoia?</h3>
          <p class="ab-apply-sub">Register on the national Boma Yangu portal, save your monthly deposit and select Trans-Nzoia as your allocation preference to join the housing ballot.</p>
        </div>
        <div class="ab-apply-ctas">
          <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="ab-btn-lime">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Apply on Boma Yangu
          </a>
          <a href="projects.php" class="ab-btn-outline-dark">
            View All Projects <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       FOOTER
  ============================================================ -->
<?php include __DIR__ . "/app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'scripts.php'; ?>
</body>
</html>