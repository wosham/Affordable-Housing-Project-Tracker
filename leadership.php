<?php
$basePath = '';
$activePage = 'leadership';
$pageTitle = 'Programme Leadership | Trans-Nzoia AHP Tracker';
$pageDescription = 'Programme leadership of the Trans-Nzoia Affordable Housing Programme â€” national government hierarchy, field office, contractors and implementing partners.';
$pageKeywords = 'Trans-Nzoia affordable housing leadership, Moses Owuor, AHP Kenya, State Department Housing, county director housing';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/leadership.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/leadership.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/leadership.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="Programme Leadership | Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Meet the national government team and contractors delivering affordable housing in Trans-Nzoia County.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/leadership.php">',
  '<meta property="og:image" content="uploads/heroes/hero-main.jpg">',
  '<meta property="og:locale" content="en_KE">',
  '<meta property="og:site_name" content="Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:card" content="summary_large_image">',
  '<meta name="twitter:title" content="Programme Leadership | Trans-Nzoia AHP Tracker">',
  '<meta name="twitter:description" content="Meet the national government team delivering affordable housing in Trans-Nzoia County.">',
  '<meta name="twitter:image" content="uploads/heroes/hero-main.jpg">'
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
    <section class="ld-hero" aria-label="Programme leadership overview">
      <div class="ld-hero-bg" aria-hidden="true">
        <img src="uploads/heroes/hero-main.jpg" alt="" loading="eager" onerror="this.style.display='none'">
        <div class="ld-hero-overlay"></div>
        <div class="ld-hero-grid-overlay" aria-hidden="true"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <a href="about.php" class="breadcrumb-link">About</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Leadership</span>
        </nav>
        <div class="ld-hero-body">
          <div class="ld-hero-eyebrow">
            <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
            National Government &mdash; State Dept. of Housing &amp; Urban Development
          </div>
          <h1 class="ld-hero-title">The People Delivering Trans-Nzoia&rsquo;s Housing Future</h1>
          <p class="ld-hero-sub">A nationally-led programme with dedicated field representation in Trans-Nzoia County â€” from Cabinet level to construction site supervisors, every tier accountable.</p>
          <div class="ld-hero-pills">
            <span class="ld-hero-pill"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> 8 Active Projects</span>
            <span class="ld-hero-pill"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i> 1,730 Units Planned</span>
            <span class="ld-hero-pill"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> 5 Constituencies</span>
            <span class="ld-hero-pill"><i class="fa-solid fa-hard-hat" aria-hidden="true"></i> 8 Contractors Active</span>
          </div>
        </div>
      </div>
      <div class="ld-hero-scroll-hint" aria-hidden="true">
        <span>Scroll to explore</span>
        <i class="fa-solid fa-chevron-down"></i>
      </div>
    </section>

    <!-- ============================================================
         S2: ORG CHART â€” Chain of Command
    ============================================================ -->
    <section class="ld-org fade-up" aria-label="Programme chain of command">
      <div class="container">
        <div class="ld-section-header">
          <div class="ld-eyebrow"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Chain of Command</div>
          <h2 class="ld-section-title">From National Government to Your Doorstep</h2>
          <p class="ld-section-sub">The Affordable Housing Programme flows from presidential mandate through Cabinet and the State Department, all the way to the field representatives on the ground in Trans-Nzoia County.</p>
        </div>

        <div class="ld-org-chart" id="orgChart" role="tree" aria-label="Programme leadership hierarchy">

          <!-- Level 1: Head of State -->
          <div class="ld-org-level" role="group" aria-label="Head of State">
            <div class="ld-org-node ld-org-node--tier1" role="treeitem" tabindex="0" aria-expanded="false" aria-haspopup="true" data-popover="pop-president">
              <div class="ld-org-avatar ld-org-avatar--tier1">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Head of State</span>
                <h3 class="ld-org-name">H.E. Dr. William S. Ruto</h3>
                <span class="ld-org-dept">President of the Republic of Kenya</span>
              </div>
              <button class="ld-org-info-btn" aria-label="View details for President Ruto" tabindex="-1">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              </button>
              <div class="ld-org-popover" id="pop-president" role="tooltip">
                <p>H.E. President Ruto launched the Affordable Housing Programme as a flagship Kenya Kwanza manifesto commitment. The programme targets 250,000 units nationally per year, with Trans-Nzoia County allocated 1,730 units across five constituencies.</p>
                <span class="ld-org-pop-dept"><i class="fa-solid fa-building-flag" aria-hidden="true"></i> State House, Nairobi</span>
              </div>
            </div>
          </div>

          <div class="ld-org-connector" aria-hidden="true"><div class="ld-org-connector-line"></div></div>

          <!-- Level 2: Cabinet Secretary -->
          <div class="ld-org-level" role="group" aria-label="Cabinet Secretary">
            <div class="ld-org-node ld-org-node--tier2" role="treeitem" tabindex="0" aria-expanded="false" data-popover="pop-cs">
              <div class="ld-org-avatar ld-org-avatar--tier2">
                <i class="fa-solid fa-landmark" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Cabinet Secretary</span>
                <h3 class="ld-org-name">Hon. Alice Wahome</h3>
                <span class="ld-org-dept">Ministry of Lands, Housing &amp; Urban Development</span>
              </div>
              <button class="ld-org-info-btn" aria-label="View details for CS Alice Wahome" tabindex="-1">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              </button>
              <div class="ld-org-popover" id="pop-cs" role="tooltip">
                <p>The Cabinet Secretary provides overall policy oversight and political accountability for the Affordable Housing Programme. Cabinet-level approvals for land allocation, levy administration and national rollout strategy are signed at this level.</p>
                <span class="ld-org-pop-dept"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Ardhi House, Upper Hill, Nairobi</span>
              </div>
            </div>
          </div>

          <div class="ld-org-connector" aria-hidden="true"><div class="ld-org-connector-line"></div></div>

          <!-- Level 3: Principal Secretary -->
          <div class="ld-org-level" role="group" aria-label="Principal Secretary">
            <div class="ld-org-node ld-org-node--tier2" role="treeitem" tabindex="0" aria-expanded="false" data-popover="pop-ps">
              <div class="ld-org-avatar ld-org-avatar--tier2">
                <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Principal Secretary</span>
                <h3 class="ld-org-name">Eng. Charles Hinga</h3>
                <span class="ld-org-dept">State Dept. of Housing &amp; Urban Development</span>
              </div>
              <button class="ld-org-info-btn" aria-label="View details for PS Charles Hinga" tabindex="-1">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              </button>
              <div class="ld-org-popover" id="pop-ps" role="tooltip">
                <p>Eng. Charles Hinga serves as AHP Secretariat Head and has been the primary technical driver of the programme since its launch. He oversees contractor selection via NCA, land acquisition, unit pricing and the Boma Yangu allocation system.</p>
                <span class="ld-org-pop-dept"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Ardhi House, Upper Hill, Nairobi</span>
              </div>
            </div>
          </div>

          <div class="ld-org-connector ld-org-connector--fork" aria-hidden="true">
            <div class="ld-org-connector-line"></div>
            <div class="ld-org-connector-branch">
              <div class="ld-org-connector-branch-left"></div>
              <div class="ld-org-connector-branch-right"></div>
            </div>
          </div>

          <!-- Level 4: Parallel â€” AHB + County Directors -->
          <div class="ld-org-level ld-org-level--pair" role="group" aria-label="Affordable Housing Board and County Directors">

            <div class="ld-org-node ld-org-node--tier3" role="treeitem" tabindex="0" aria-expanded="false" data-popover="pop-ahb">
              <div class="ld-org-avatar ld-org-avatar--tier3">
                <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Statutory Board</span>
                <h3 class="ld-org-name">Affordable Housing Board</h3>
                <span class="ld-org-dept">Est. under Affordable Housing Act, 2024</span>
              </div>
              <button class="ld-org-info-btn" aria-label="View details for Affordable Housing Board" tabindex="-1">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              </button>
              <div class="ld-org-popover" id="pop-ahb" role="tooltip">
                <p>The Affordable Housing Board administers the Affordable Housing Fund and Levy, approves unit pricing, manages the Boma Yangu allocation system, and provides statutory oversight for all programme expenditure.</p>
                <span class="ld-org-pop-dept"><i class="fa-solid fa-file-contract" aria-hidden="true"></i> Established under AHA 2024</span>
              </div>
            </div>

            <div class="ld-org-node ld-org-node--tier3" role="treeitem" tabindex="0" aria-expanded="false" data-popover="pop-dirs">
              <div class="ld-org-avatar ld-org-avatar--tier3">
                <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">National Appointments</span>
                <h3 class="ld-org-name">County Directors (47 Counties)</h3>
                <span class="ld-org-dept">State Dept. of Housing &mdash; Field Representatives</span>
              </div>
              <button class="ld-org-info-btn" aria-label="View details for County Directors" tabindex="-1">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              </button>
              <div class="ld-org-popover" id="pop-dirs" role="tooltip">
                <p>County Directors are appointed by the national government â€” not the county government â€” to serve as the official field representatives of the State Department of Housing in each county. They coordinate site activities, contractor liaison and community engagement.</p>
                <span class="ld-org-pop-dept"><i class="fa-solid fa-flag" aria-hidden="true"></i> Deployed nationally to all 47 counties</span>
              </div>
            </div>

          </div>

          <div class="ld-org-connector ld-org-connector--right-branch" aria-hidden="true">
            <div class="ld-org-connector-line"></div>
          </div>

          <!-- Level 5: Trans-Nzoia Field Office -->
          <div class="ld-org-level" role="group" aria-label="Trans-Nzoia County Director">
            <div class="ld-org-node ld-org-node--tier4 ld-org-node--highlighted" role="treeitem" tabindex="0" aria-expanded="false" data-popover="pop-county-dir">
              <div class="ld-org-avatar ld-org-avatar--tier4">
                <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge ld-org-tier-badge--lime">Trans-Nzoia County</span>
                <h3 class="ld-org-name">Moses Owuor</h3>
                <span class="ld-org-dept">County Director of Housing &mdash; National Appointment</span>
              </div>
              <div class="ld-org-location-badge">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i> Ardhi House, Kitale
              </div>
              <button class="ld-org-info-btn" aria-label="View details for Moses Owuor" tabindex="-1">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              </button>
              <div class="ld-org-popover" id="pop-county-dir" role="tooltip">
                <p>Moses Owuor is the national government&rsquo;s appointed County Director for Housing in Trans-Nzoia. Based at Ardhi House within the County Commissioner&rsquo;s Premises in Kitale, he coordinates all eight AHP project sites, contractor oversight and community engagement across the five constituencies.</p>
                <span class="ld-org-pop-dept"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Ardhi House, County Commissioner&rsquo;s Premises, Kitale</span>
              </div>
            </div>
          </div>

          <div class="ld-org-connector" aria-hidden="true"><div class="ld-org-connector-line"></div></div>

          <!-- Level 6: Field Officers -->
          <div class="ld-org-level ld-org-level--wide" role="group" aria-label="Field officers and site supervisors">
            <div class="ld-org-node ld-org-node--tier5" role="treeitem" tabindex="0">
              <div class="ld-org-avatar ld-org-avatar--tier5">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Field Officers</span>
                <h3 class="ld-org-name">Project Monitoring Officers</h3>
                <span class="ld-org-dept">Site inspection &amp; compliance reporting</span>
              </div>
            </div>
            <div class="ld-org-node ld-org-node--tier5" role="treeitem" tabindex="0">
              <div class="ld-org-avatar ld-org-avatar--tier5">
                <i class="fa-solid fa-hard-hat" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Construction</span>
                <h3 class="ld-org-name">Site Supervisors (8 Sites)</h3>
                <span class="ld-org-dept">Day-to-day construction management</span>
              </div>
            </div>
            <div class="ld-org-node ld-org-node--tier5" role="treeitem" tabindex="0">
              <div class="ld-org-avatar ld-org-avatar--tier5">
                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
              </div>
              <div class="ld-org-info">
                <span class="ld-org-tier-badge">Outreach</span>
                <h3 class="ld-org-name">Community Liaison Officers</h3>
                <span class="ld-org-dept">Beneficiary engagement &amp; registration</span>
              </div>
            </div>
          </div>

        </div><!-- /ld-org-chart -->
      </div>
    </section>

    <!-- ============================================================
         S3: NATIONAL LEADERSHIP CARDS
    ============================================================ -->
    <section class="ld-national fade-up" aria-label="National programme leadership">
      <div class="container">
        <div class="ld-section-header">
          <div class="ld-eyebrow"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> National Leadership</div>
          <h2 class="ld-section-title">Senior Officials Driving the Programme</h2>
          <p class="ld-section-sub">The Affordable Housing Programme is accountable to the highest levels of national government, ensuring transparency, legal compliance and adequate funding.</p>
        </div>
        <div class="ld-national-track-wrap">
          <div class="ld-national-track" id="nationalTrack">

            <article class="ld-national-card" aria-label="President William Ruto">
              <div class="ld-national-card-top">
                <div class="ld-national-avatar ld-national-avatar--gold">
                  <span aria-hidden="true">WR</span>
                </div>
                <div class="ld-national-flag-strip" aria-hidden="true"></div>
              </div>
              <div class="ld-national-card-body">
                <span class="ld-national-role">Head of State &amp; Government</span>
                <h3 class="ld-national-name">H.E. Dr. William Samoei Ruto</h3>
                <p class="ld-national-mandate">Initiated the AHP as a Kenya Kwanza flagship promise â€” targeting 250,000 affordable units annually across Kenya.</p>
                <div class="ld-national-dept">
                  <i class="fa-solid fa-building-flag" aria-hidden="true"></i>
                  <span>State House, Nairobi</span>
                </div>
                <blockquote class="ld-national-quote">
                  &ldquo;Every Kenyan deserves a decent, affordable place to call home. The AHP is our covenant with ordinary citizens.&rdquo;
                </blockquote>
              </div>
            </article>

            <article class="ld-national-card" aria-label="Cabinet Secretary Alice Wahome">
              <div class="ld-national-card-top">
                <div class="ld-national-avatar ld-national-avatar--green">
                  <span aria-hidden="true">AW</span>
                </div>
                <div class="ld-national-flag-strip" aria-hidden="true"></div>
              </div>
              <div class="ld-national-card-body">
                <span class="ld-national-role">Cabinet Secretary</span>
                <h3 class="ld-national-name">Hon. Alice Wahome</h3>
                <p class="ld-national-mandate">Provides policy oversight, inter-ministerial coordination and Cabinet-level accountability for national housing delivery targets.</p>
                <div class="ld-national-dept">
                  <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                  <span>Ministry of Lands, Housing &amp; Urban Development</span>
                </div>
                <blockquote class="ld-national-quote">
                  &ldquo;We are building communities â€” not just houses. Every unit delivered is a family's future secured.&rdquo;
                </blockquote>
              </div>
            </article>

            <article class="ld-national-card" aria-label="Principal Secretary Charles Hinga">
              <div class="ld-national-card-top">
                <div class="ld-national-avatar ld-national-avatar--teal">
                  <span aria-hidden="true">CH</span>
                </div>
                <div class="ld-national-flag-strip" aria-hidden="true"></div>
              </div>
              <div class="ld-national-card-body">
                <span class="ld-national-role">Principal Secretary</span>
                <h3 class="ld-national-name">Eng. Charles Hinga</h3>
                <p class="ld-national-mandate">Heads the AHP Secretariat. Responsible for technical delivery, contractor procurement, Boma Yangu system and county deployment strategy.</p>
                <div class="ld-national-dept">
                  <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                  <span>State Dept. of Housing &amp; Urban Development</span>
                </div>
                <blockquote class="ld-national-quote">
                  &ldquo;Trans-Nzoia is a model county â€” eight sites active demonstrates the programme's national reach and ambition.&rdquo;
                </blockquote>
              </div>
            </article>

            <article class="ld-national-card" aria-label="Affordable Housing Board">
              <div class="ld-national-card-top">
                <div class="ld-national-avatar ld-national-avatar--blue">
                  <span aria-hidden="true">AHB</span>
                </div>
                <div class="ld-national-flag-strip" aria-hidden="true"></div>
              </div>
              <div class="ld-national-card-body">
                <span class="ld-national-role">Statutory Oversight Body</span>
                <h3 class="ld-national-name">Affordable Housing Board</h3>
                <p class="ld-national-mandate">Administers the AH Fund &amp; Levy, approves unit pricing, oversees the Boma Yangu ballot system and monitors programme expenditure and audit trails.</p>
                <div class="ld-national-dept">
                  <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
                  <span>Est. under Affordable Housing Act, 2024</span>
                </div>
                <blockquote class="ld-national-quote">
                  &ldquo;Transparent allocation is the foundation of public trust. Every unit, every shilling â€” accounted for.&rdquo;
                </blockquote>
              </div>
            </article>

          </div>
        </div>
        <div class="ld-national-nav" aria-label="Leadership card navigation">
          <button class="ld-national-prev" id="natPrev" aria-label="Previous leader"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
          <div class="ld-national-dots" id="natDots" role="tablist" aria-label="Leadership cards"></div>
          <button class="ld-national-next" id="natNext" aria-label="Next leader"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S4: COUNTY FIELD OFFICE SPOTLIGHT â€” Moses Owuor
    ============================================================ -->
    <section class="ld-spotlight fade-up" aria-label="Trans-Nzoia county director spotlight">
      <div class="ld-spotlight-bg" aria-hidden="true"></div>
      <div class="container">
        <div class="ld-spotlight-inner">

          <div class="ld-spotlight-visual">
            <div class="ld-spotlight-avatar" aria-hidden="true">
              <span>MO</span>
              <div class="ld-spotlight-avatar-ring" aria-hidden="true"></div>
            </div>
            <div class="ld-spotlight-badge">
              <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
              Nationally Appointed
            </div>
            <div class="ld-spotlight-office-card" aria-label="Office location">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
              <div>
                <strong>Field Office</strong>
                <span>Ardhi House, County Commissioner&rsquo;s Premises</span>
                <span>Kitale, Trans-Nzoia County</span>
              </div>
            </div>
          </div>

          <div class="ld-spotlight-content">
            <div class="ld-eyebrow"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> County Field Representative</div>
            <h2 class="ld-spotlight-name">Moses Owuor</h2>
            <div class="ld-spotlight-title">County Director of Housing &mdash; Trans-Nzoia</div>
            <div class="ld-spotlight-appointment-note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              Appointed by the <strong>national government</strong> (State Dept. of Housing &amp; Urban Development) to represent the programme in Trans-Nzoia County &mdash; independent of the County Government.
            </div>
            <p class="ld-spotlight-bio">Moses Owuor serves as the principal national government representative for the Affordable Housing Programme in Trans-Nzoia County. Operating from Ardhi House within the County Commissioner&rsquo;s Premises in Kitale, his office coordinates the oversight of all eight active and planning-stage project sites, manages relationships with contractors and the NCA, and leads community engagement and beneficiary registration drives across all five constituencies.</p>
            <blockquote class="ld-spotlight-quote">
              <i class="fa-solid fa-quote-left ld-quote-icon" aria-hidden="true"></i>
              Trans-Nzoia is one of the counties where the national government&rsquo;s housing programme is delivering real, tangible change. We are here, on the ground, every day â€” ensuring every shilling of the Affordable Housing Fund reaches the right hands and the right bricks.
              <footer class="ld-spotlight-quote-attr">
                &mdash; Moses Owuor, County Director of Housing, Trans-Nzoia
              </footer>
            </blockquote>
            <div class="ld-spotlight-responsibilities">
              <h4 class="ld-spotlight-resp-title">Key Responsibilities</h4>
              <ul class="ld-spotlight-resp-list">
                <li><i class="fa-solid fa-check-circle" aria-hidden="true"></i> Oversight of all 8 AHP project sites in Trans-Nzoia</li>
                <li><i class="fa-solid fa-check-circle" aria-hidden="true"></i> Contractor liaison and NCA compliance monitoring</li>
                <li><i class="fa-solid fa-check-circle" aria-hidden="true"></i> Beneficiary registration and Boma Yangu coordination</li>
                <li><i class="fa-solid fa-check-circle" aria-hidden="true"></i> Monthly progress reporting to the AHP Secretariat</li>
                <li><i class="fa-solid fa-check-circle" aria-hidden="true"></i> Community and stakeholder engagement across 5 constituencies</li>
              </ul>
            </div>
            <a href="contact.php" class="ld-btn-lime">
              <i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact the Field Office
            </a>
          </div>

        </div>
      </div>
    </section>

    <!-- ============================================================
         S5: CONTRACTOR SHOWCASE
    ============================================================ -->
    <section class="ld-contractors fade-up" aria-label="Programme contractors">
      <div class="container">
        <div class="ld-section-header">
          <div class="ld-eyebrow"><i class="fa-solid fa-hard-hat" aria-hidden="true"></i> Contractors on the Ground</div>
          <h2 class="ld-section-title">Building Trans-Nzoia&rsquo;s Affordable Homes</h2>
          <p class="ld-section-sub">Eight NCA-registered contractors are delivering project sites across Trans-Nzoia County. All were competitively procured under PPDA 2015 and hold active NCA grading for their project categories.</p>
        </div>
        <div class="ld-contractors-grid" id="contractorsGrid">

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 142">
                <span>AC</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">Apex Construct Ltd</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Maili Tatu Estate &mdash; Kitale Central
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 60%" aria-label="60% complete"></div>
                </div>
                <span>60% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;The Maili Tatu site is our flagship delivery â€” we&rsquo;re proud to be building homes that will change lives in Kitale.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 210">
                <span>NB</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">Nexus Builders Kenya</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Matunda AHP &mdash; Cherangany
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 35%" aria-label="35% complete"></div>
                </div>
                <span>35% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;Ground-floor columns are cast. Cherangany will have its first affordable estate â€” on time and on budget.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 270">
                <span>BC</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--tender">
                <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Tendering
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 6</div>
              <h3 class="ld-contractor-name">BuildCore East Africa</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Kitale Ex-Prison Site &mdash; Kitale Central
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 5%" aria-label="5% complete â€” tender stage"></div>
                </div>
                <span>Bids close 30 Jun 2026</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;This is a landmark urban regeneration site. We are committed to delivering a world-class estate for Kitale.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 30">
                <span>LS</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">Landmark Structures Ltd</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Suam Border Post Estate &mdash; Endebess
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 20%" aria-label="20% complete"></div>
                </div>
                <span>20% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;NEMA clearance secured. We begin full structural works next quarter â€” Endebess is ready for its housing revolution.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 170">
                <span>GC</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">Greenfield Construction Co.</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Saboti Township Estate &mdash; Saboti
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 45%" aria-label="45% complete"></div>
                </div>
                <span>45% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;Saboti deserves affordable, quality homes. We are on track to hand over the first block by Q3 2026.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 0">
                <span>PB</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">ProBuild Kenya Ltd</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Endebess Scheme &mdash; Endebess
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 30%" aria-label="30% complete"></div>
                </div>
                <span>30% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;Our team is embedded in the community â€” we know the people we are building for, and that drives our quality standards.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 55">
                <span>AT</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">Atlas Contractors Ltd</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Kwanza Units &mdash; Kwanza
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 25%" aria-label="25% complete"></div>
                </div>
                <span>25% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;Kwanza&rsquo;s first large-scale estate â€” we are writing history here with every block we lay.&rdquo;
              </blockquote>
            </div>
          </article>

          <article class="ld-contractor-card">
            <div class="ld-contractor-header">
              <div class="ld-contractor-avatar" style="--avatar-hue: 190">
                <span>HB</span>
              </div>
              <div class="ld-contractor-badge ld-contractor-badge--active">
                <i class="fa-solid fa-circle" aria-hidden="true"></i> Active
              </div>
            </div>
            <div class="ld-contractor-body">
              <div class="ld-contractor-nca">NCA Grade 5</div>
              <h3 class="ld-contractor-name">Horizon Builders Ltd</h3>
              <div class="ld-contractor-project">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                Kiminini Estate &mdash; Kiminini
              </div>
              <div class="ld-contractor-progress">
                <div class="ld-contractor-prog-bar">
                  <div class="ld-contractor-prog-fill" style="--prog: 50%" aria-label="50% complete"></div>
                </div>
                <span>50% complete</span>
              </div>
              <blockquote class="ld-contractor-quote">
                &ldquo;Kiminini is a high-priority site â€” we are at 50% and confident of on-time completion for the first 200 units.&rdquo;
              </blockquote>
            </div>
          </article>

        </div><!-- /ld-contractors-grid -->
        <div class="ld-contractors-note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          All contractors are NCA-registered and were procured through open competitive tendering under the <strong>Public Procurement &amp; Asset Disposal Act, 2015</strong>. Company names are representative placeholders â€” final verified contractor data will be published upon contract execution.
        </div>
      </div>
    </section>

    <!-- ============================================================
         S6: QUOTE CAROUSEL â€” In Their Own Words
    ============================================================ -->
    <section class="ld-quotes fade-up" aria-label="In their own words â€” leadership quotes">
      <div class="container">
        <div class="ld-section-header ld-section-header--light">
          <div class="ld-eyebrow ld-eyebrow--light"><i class="fa-solid fa-quote-right" aria-hidden="true"></i> In Their Own Words</div>
          <h2 class="ld-section-title ld-section-title--light">What Our Leaders &amp; Builders Say</h2>
        </div>
        <div class="ld-quotes-carousel" id="quotesCarousel" aria-roledescription="carousel" aria-label="Leadership quotes">
          <div class="ld-quotes-track" id="quotesTrack">

            <div class="ld-quote-slide" role="group" aria-roledescription="slide" aria-label="Quote 1 of 5">
              <div class="ld-quote-mark" aria-hidden="true">&ldquo;</div>
              <p class="ld-quote-text">The Affordable Housing Programme is not charity â€” it is economic justice. Trans-Nzoia residents deserve the same quality of housing that Kenya&rsquo;s urban centres are getting, and we are delivering exactly that.</p>
              <div class="ld-quote-attr">
                <div class="ld-quote-avatar ld-quote-avatar--gold">WR</div>
                <div>
                  <strong>H.E. Dr. William S. Ruto</strong>
                  <span>President of Kenya</span>
                </div>
              </div>
            </div>

            <div class="ld-quote-slide" role="group" aria-roledescription="slide" aria-label="Quote 2 of 5">
              <div class="ld-quote-mark" aria-hidden="true">&ldquo;</div>
              <p class="ld-quote-text">Trans-Nzoia is one of the counties where the national government&rsquo;s housing programme is delivering real, tangible change. We are here, on the ground, every day â€” ensuring every shilling of the Affordable Housing Fund reaches the right hands and the right bricks.</p>
              <div class="ld-quote-attr">
                <div class="ld-quote-avatar ld-quote-avatar--lime">MO</div>
                <div>
                  <strong>Moses Owuor</strong>
                  <span>County Director of Housing, Trans-Nzoia (National Appointment)</span>
                </div>
              </div>
            </div>

            <div class="ld-quote-slide" role="group" aria-roledescription="slide" aria-label="Quote 3 of 5">
              <div class="ld-quote-mark" aria-hidden="true">&ldquo;</div>
              <p class="ld-quote-text">Trans-Nzoia is a model county â€” eight sites active demonstrates the programme&rsquo;s national reach and ambition. The Secretariat is committed to ensuring no site stalls for lack of funding or oversight.</p>
              <div class="ld-quote-attr">
                <div class="ld-quote-avatar ld-quote-avatar--teal">CH</div>
                <div>
                  <strong>Eng. Charles Hinga</strong>
                  <span>Principal Secretary, State Dept. of Housing &amp; Urban Development</span>
                </div>
              </div>
            </div>

            <div class="ld-quote-slide" role="group" aria-roledescription="slide" aria-label="Quote 4 of 5">
              <div class="ld-quote-mark" aria-hidden="true">&ldquo;</div>
              <p class="ld-quote-text">Maili Tatu is our flagship delivery and we are proud to be building homes that will change lives in Kitale. Quality is non-negotiable â€” every slab, every column, inspected and certified before we proceed.</p>
              <div class="ld-quote-attr">
                <div class="ld-quote-avatar ld-quote-avatar--green">AC</div>
                <div>
                  <strong>Apex Construct Ltd</strong>
                  <span>Contractor &mdash; Maili Tatu Estate, Kitale Central</span>
                </div>
              </div>
            </div>

            <div class="ld-quote-slide" role="group" aria-roledescription="slide" aria-label="Quote 5 of 5">
              <div class="ld-quote-mark" aria-hidden="true">&ldquo;</div>
              <p class="ld-quote-text">Transparent allocation is the foundation of public trust. Every unit, every shilling â€” accounted for. The Affordable Housing Board is here to ensure the programme&rsquo;s integrity is never compromised.</p>
              <div class="ld-quote-attr">
                <div class="ld-quote-avatar ld-quote-avatar--blue">AHB</div>
                <div>
                  <strong>Affordable Housing Board</strong>
                  <span>Statutory Oversight Body &mdash; Est. AHA 2024</span>
                </div>
              </div>
            </div>

          </div>
        </div>
        <div class="ld-quotes-controls" aria-label="Quote carousel controls">
          <button class="ld-quotes-prev" id="quotesPrev" aria-label="Previous quote"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
          <div class="ld-quotes-dots" id="quotesDots" role="tablist" aria-label="Quote slides"></div>
          <button class="ld-quotes-next" id="quotesNext" aria-label="Next quote"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S7: IMPLEMENTING PARTNERS STRIP
    ============================================================ -->
    <section class="ld-partners fade-up" aria-label="Implementing partner organisations">
      <div class="container">
        <div class="ld-section-header">
          <div class="ld-eyebrow"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Implementing Partners</div>
          <h2 class="ld-section-title">The Organisations Behind the Programme</h2>
          <p class="ld-section-sub">From statutory oversight to environmental clearance and mortgage financing â€” six national bodies work in concert to deliver Trans-Nzoia&rsquo;s affordable housing pipeline.</p>
        </div>
        <div class="ld-partners-grid">

          <div class="ld-partner-card">
            <div class="ld-partner-icon" aria-hidden="true">
              <i class="fa-solid fa-building-columns"></i>
            </div>
            <div class="ld-partner-info">
              <h3 class="ld-partner-name">State Dept. of Housing</h3>
              <p class="ld-partner-role">Programme Lead &amp; Secretariat</p>
            </div>
            <a href="https://www.ardhi.go.ke" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit State Dept of Housing website">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>

          <div class="ld-partner-card">
            <div class="ld-partner-icon" aria-hidden="true">
              <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div class="ld-partner-info">
              <h3 class="ld-partner-name">Affordable Housing Board</h3>
              <p class="ld-partner-role">Statutory Fund &amp; Levy Administrator</p>
            </div>
            <a href="https://ahb.go.ke" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit Affordable Housing Board website">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>

          <div class="ld-partner-card">
            <div class="ld-partner-icon" aria-hidden="true">
              <i class="fa-solid fa-leaf"></i>
            </div>
            <div class="ld-partner-info">
              <h3 class="ld-partner-name">NEMA</h3>
              <p class="ld-partner-role">Environmental Impact Assessment</p>
            </div>
            <a href="https://www.nema.go.ke" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit NEMA website">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>

          <div class="ld-partner-card">
            <div class="ld-partner-icon" aria-hidden="true">
              <i class="fa-solid fa-helmet-safety"></i>
            </div>
            <div class="ld-partner-info">
              <h3 class="ld-partner-name">NCA</h3>
              <p class="ld-partner-role">Contractor Registration &amp; Standards</p>
            </div>
            <a href="https://www.nca.go.ke" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit NCA website">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>

          <div class="ld-partner-card">
            <div class="ld-partner-icon" aria-hidden="true">
              <i class="fa-solid fa-piggy-bank"></i>
            </div>
            <div class="ld-partner-info">
              <h3 class="ld-partner-name">KMRC</h3>
              <p class="ld-partner-role">Mortgage Refinancing &amp; Subsidy</p>
            </div>
            <a href="https://www.kmrc.co.ke" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit KMRC website">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>

          <div class="ld-partner-card">
            <div class="ld-partner-icon" aria-hidden="true">
              <i class="fa-solid fa-laptop-code"></i>
            </div>
            <div class="ld-partner-info">
              <h3 class="ld-partner-name">Boma Yangu / eCitizen</h3>
              <p class="ld-partner-role">Application &amp; Ballot Platform</p>
            </div>
            <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="ld-partner-link" aria-label="Visit Boma Yangu website">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
          </div>

        </div>
      </div>
    </section>

    <!-- ============================================================
         S8: FIELD OFFICE CTA
    ============================================================ -->
    <section class="ld-cta fade-up" aria-label="Contact the field office">
      <div class="container">
        <div class="ld-cta-inner">
          <div class="ld-cta-content">
            <div class="ld-eyebrow ld-eyebrow--light"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Trans-Nzoia Field Office</div>
            <h2 class="ld-cta-title">Reach the Programme Leadership</h2>
            <p class="ld-cta-sub">The Trans-Nzoia County Director of Housing operates from Ardhi House in Kitale. For project enquiries, contractor matters, or beneficiary questions â€” our field office is open to the public.</p>
            <div class="ld-cta-contact-list">
              <div class="ld-cta-contact-item">
                <div class="ld-cta-contact-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
                <div>
                  <strong>Physical Address</strong>
                  <span>Ardhi House, County Commissioner&rsquo;s Premises</span>
                  <span>Kitale, Trans-Nzoia County, Kenya</span>
                </div>
              </div>
              <div class="ld-cta-contact-item">
                <div class="ld-cta-contact-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
                <div>
                  <strong>Telephone</strong>
                  <span>+254 53 000 0000</span>
                </div>
              </div>
              <div class="ld-cta-contact-item">
                <div class="ld-cta-contact-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></div>
                <div>
                  <strong>Email</strong>
                  <span>housing@transnzoia.go.ke</span>
                </div>
              </div>
              <div class="ld-cta-contact-item">
                <div class="ld-cta-contact-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
                <div>
                  <strong>Office Hours</strong>
                  <span>Monday &ndash; Friday, 8:00 AM &ndash; 5:00 PM</span>
                </div>
              </div>
            </div>
            <div class="ld-cta-actions">
              <a href="contact.php" class="ld-btn-lime">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i> Send a Message
              </a>
              <a href="https://bomayangu.go.ke" target="_blank" rel="noopener noreferrer" class="ld-btn-outline">
                Apply via Boma Yangu <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              </a>
            </div>
          </div>
          <div class="ld-cta-map-card" aria-label="Office location illustration">
            <div class="ld-cta-map-inner" aria-hidden="true">
              <div class="ld-cta-map-pin">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div class="ld-cta-map-label">Ardhi House</div>
              <div class="ld-cta-map-sublabel">County Commissioner&rsquo;s Premises, Kitale</div>
              <div class="ld-cta-map-grid" aria-hidden="true"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main><!-- /main-content -->

  <!-- ============================================================
       FOOTER
  ============================================================ -->
<?php include __DIR__ . "/app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/app/partials/" . 'scripts.php'; ?>
</body>
</html>