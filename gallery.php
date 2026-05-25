<?php
$basePath = '';
$activePage = 'gallery';
$pageTitle = 'Photo Gallery | Trans-Nzoia AHP Tracker';
$pageDescription = 'Photo gallery documenting the Trans-Nzoia Affordable Housing Programme â€” construction progress, groundbreaking ceremonies, community events and leadership visits across all five constituencies.';
$pageKeywords = 'Trans-Nzoia affordable housing gallery, AHP Kenya photos, Kitale housing construction, community events';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/gallery.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/gallery.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/pages/gallery.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="Photo Gallery | Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Visual documentation of the Affordable Housing Programme across Trans-Nzoia County.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/gallery.php">',
  '<meta property="og:image" content="uploads/heroes/hero-main.jpg">',
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

    <!-- ============================================================
         S1: HERO
    ============================================================ -->
    <section class="gl-hero" aria-label="Gallery overview">
      <div class="gl-hero-bg" aria-hidden="true">
        <img src="https://picsum.photos/seed/ahp-hero/1600/700" alt="" loading="eager" onerror="this.style.display='none'">
        <div class="gl-hero-overlay"></div>
        <div class="gl-hero-grid" aria-hidden="true"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Photo Gallery</span>
        </nav>
        <div class="gl-hero-body">
          <div class="gl-hero-eyebrow">
            <i class="fa-solid fa-images" aria-hidden="true"></i>
            Visual Documentation &mdash; Sites, Events &amp; Progress
          </div>
          <h1 class="gl-hero-title">Programme in Pictures</h1>
          <p class="gl-hero-sub">A visual record of every milestone â€” from groundbreaking ceremonies to community barazas, site inspections and construction progress across all five Trans-Nzoia constituencies.</p>
          <div class="gl-hero-kpi-strip" role="region" aria-label="Gallery at a glance">
            <div class="gl-hero-kpi-item">
              <span class="gl-hero-kpi-num">240+</span>
              <span class="gl-hero-kpi-lbl">Photos Archived</span>
            </div>
            <div class="gl-hero-kpi-div" aria-hidden="true"></div>
            <div class="gl-hero-kpi-item">
              <span class="gl-hero-kpi-num">8</span>
              <span class="gl-hero-kpi-lbl">Sites Documented</span>
            </div>
            <div class="gl-hero-kpi-div" aria-hidden="true"></div>
            <div class="gl-hero-kpi-item">
              <span class="gl-hero-kpi-num">3</span>
              <span class="gl-hero-kpi-lbl">Years of Coverage</span>
            </div>
            <div class="gl-hero-kpi-div" aria-hidden="true"></div>
            <div class="gl-hero-kpi-item">
              <span class="gl-hero-kpi-num">12</span>
              <span class="gl-hero-kpi-lbl">Events Captured</span>
            </div>
          </div>
        </div>
      </div>
      <div class="gl-hero-scroll-hint" aria-hidden="true">
        <span>Browse the gallery</span>
        <i class="fa-solid fa-chevron-down"></i>
      </div>
    </section>

    <!-- ============================================================
         S2: FEATURED HIGHLIGHTS CAROUSEL
    ============================================================ -->
    <section class="gl-highlights" aria-labelledby="highlights-heading">
      <div class="container">
        <div class="gl-section-header fade-up">
          <div class="gl-eyebrow"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured Moments</div>
          <h2 class="gl-section-title" id="highlights-heading">Programme Highlights</h2>
          <p class="gl-section-sub">Landmark moments captured â€” from the presidential directive to community handovers.</p>
        </div>

        <div class="gl-hl-nav fade-up">
          <button class="gl-hl-arrow" id="hlPrev" aria-label="Previous highlight" disabled>
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
          </button>
          <div class="gl-hl-track-wrap">
            <div class="gl-hl-track" id="hlTrack">

              <article class="gl-hl-slide" data-index="0">
                <div class="gl-hl-img-wrap">
                  <img src="https://picsum.photos/seed/ahp-hl1/800/480" alt="CS for Housing officiates groundbreaking at Maili Tatu, Kitale â€” February 2024" loading="lazy">
                  <span class="gl-hl-badge">Groundbreaking</span>
                </div>
                <div class="gl-hl-body">
                  <span class="gl-hl-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Feb 2024</span>
                  <h3 class="gl-hl-title">CS Officiates Maili Tatu Groundbreaking</h3>
                  <p class="gl-hl-desc">Cabinet Secretary for Housing officiates the official groundbreaking ceremony at the Maili Tatu Estate site in Kitale, marking the start of construction for 400 units.</p>
                  <span class="gl-hl-site"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Kiminini Constituency, Kitale</span>
                </div>
              </article>

              <article class="gl-hl-slide" data-index="1">
                <div class="gl-hl-img-wrap">
                  <img src="https://picsum.photos/seed/ahp-hl2/800/480" alt="Governor signs MoU with AHB â€” land allocation for five AHP sites" loading="lazy">
                  <span class="gl-hl-badge">MoU Signing</span>
                </div>
                <div class="gl-hl-body">
                  <span class="gl-hl-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Mar 2023</span>
                  <h3 class="gl-hl-title">Governor Signs Land Allocation MoU</h3>
                  <p class="gl-hl-desc">H.E. the Governor of Trans-Nzoia County signs the formal MoU with the Affordable Housing Board, setting aside five parcels of county land for the programme.</p>
                  <span class="gl-hl-site"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> County Headquarters, Kitale</span>
                </div>
              </article>

              <article class="gl-hl-slide" data-index="2">
                <div class="gl-hl-img-wrap">
                  <img src="https://picsum.photos/seed/ahp-hl3/800/480" alt="Ward baraza community participation â€” Saboti Constituency, September 2023" loading="lazy">
                  <span class="gl-hl-badge">Community</span>
                </div>
                <div class="gl-hl-body">
                  <span class="gl-hl-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Sep 2023</span>
                  <h3 class="gl-hl-title">Ward Baraza â€” Saboti Constituency</h3>
                  <p class="gl-hl-desc">Over 300 residents attend the Saboti Ward public participation baraza. Community inputs on site positioning and housing unit design are recorded and integrated into project plans.</p>
                  <span class="gl-hl-site"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Saboti Constituency</span>
                </div>
              </article>

              <article class="gl-hl-slide" data-index="3">
                <div class="gl-hl-img-wrap">
                  <img src="https://picsum.photos/seed/ahp-hl4/800/480" alt="NCA site inspection â€” Matunda AHP site passes quarterly audit" loading="lazy">
                  <span class="gl-hl-badge">Site Audit</span>
                </div>
                <div class="gl-hl-body">
                  <span class="gl-hl-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Jun 2024</span>
                  <h3 class="gl-hl-title">NCA Quarterly Audit â€” All Sites Pass</h3>
                  <p class="gl-hl-desc">National Construction Authority engineers conduct the Q2 2024 structural quality inspection. All active sites pass. Minor drainage issue flagged at Kiminini, since resolved.</p>
                  <span class="gl-hl-site"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Matunda â€” Trans-Nzoia West</span>
                </div>
              </article>

              <article class="gl-hl-slide" data-index="4">
                <div class="gl-hl-img-wrap">
                  <img src="https://picsum.photos/seed/ahp-hl5/800/480" alt="Phase 1 beneficiary balloting ceremony â€” AHB Field Office Kitale, January 2025" loading="lazy">
                  <span class="gl-hl-badge">Balloting</span>
                </div>
                <div class="gl-hl-body">
                  <span class="gl-hl-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Jan 2025</span>
                  <h3 class="gl-hl-title">Phase 1 Beneficiary Balloting Ceremony</h3>
                  <p class="gl-hl-desc">AHB conducts the first transparent allocation ballot for 400 units. 1,840 verified applicants participate. Priority given to teachers, nurses, and PWD households.</p>
                  <span class="gl-hl-site"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> AHB Field Office, Kitale</span>
                </div>
              </article>

            </div><!-- /gl-hl-track -->
          </div>
          <button class="gl-hl-arrow" id="hlNext" aria-label="Next highlight">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          </button>
        </div>

        <div class="gl-hl-dots" id="hlDots" role="tablist" aria-label="Highlight slides"></div>

      </div>
    </section>

    <!-- ============================================================
         S3: FILTERABLE PHOTO GRID
    ============================================================ -->
    <section class="gl-grid-section" aria-labelledby="grid-heading">
      <div class="container">
        <div class="gl-section-header fade-up">
          <div class="gl-eyebrow"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Full Archive</div>
          <h2 class="gl-section-title" id="grid-heading">Browse All Photos</h2>
          <p class="gl-section-sub">Filter by category, site or year to find specific documentation of the programme.</p>
        </div>

        <!-- Filter Bar -->
        <div class="gl-filter-bar fade-up" role="toolbar" aria-label="Photo filters">
          <div class="gl-filter-group">
            <span class="gl-filter-label">Category</span>
            <div class="gl-filter-chips" role="group" aria-label="Filter by category">
              <button class="gl-chip is-active" data-filter="cat" data-val="all">All</button>
              <button class="gl-chip" data-filter="cat" data-val="construction"><i class="fa-solid fa-hard-hat" aria-hidden="true"></i> Construction</button>
              <button class="gl-chip" data-filter="cat" data-val="groundbreaking"><i class="fa-solid fa-shovel" aria-hidden="true"></i> Groundbreaking</button>
              <button class="gl-chip" data-filter="cat" data-val="community"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Community</button>
              <button class="gl-chip" data-filter="cat" data-val="leadership"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> Leadership</button>
              <button class="gl-chip" data-filter="cat" data-val="survey"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Site Survey</button>
            </div>
          </div>
          <div class="gl-filter-group">
            <span class="gl-filter-label">Year</span>
            <div class="gl-filter-chips" role="group" aria-label="Filter by year">
              <button class="gl-chip is-active" data-filter="year" data-val="all">All</button>
              <button class="gl-chip" data-filter="year" data-val="2022">2022</button>
              <button class="gl-chip" data-filter="year" data-val="2023">2023</button>
              <button class="gl-chip" data-filter="year" data-val="2024">2024</button>
              <button class="gl-chip" data-filter="year" data-val="2025">2025</button>
            </div>
          </div>
          <div class="gl-filter-count" id="filterCount" aria-live="polite">Showing <strong>20</strong> photos</div>
        </div>

        <!-- Photo Grid -->
        <div class="gl-photo-grid" id="photoGrid" role="list" aria-label="Photo gallery">

          <button class="gl-photo-item" data-cat="construction" data-year="2024" data-site="kiminini" role="listitem" aria-label="Open photo: Maili Tatu Block A â€” Foundation slab pour, February 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl1/600/420" alt="Maili Tatu Block A â€” Foundation slab pour" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Maili Tatu Block A â€” Foundation Slab</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--construction">Construction</span>
              <span class="gl-photo-date">Feb 2024</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="groundbreaking" data-year="2024" data-site="kiminini" role="listitem" aria-label="Open photo: Groundbreaking ceremony at Maili Tatu Estate, February 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl2/600/420" alt="Groundbreaking ceremony â€” Maili Tatu Estate" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Groundbreaking â€” Maili Tatu Estate</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--groundbreaking">Groundbreaking</span>
              <span class="gl-photo-date">Feb 2024</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="community" data-year="2023" data-site="saboti" role="listitem" aria-label="Open photo: Ward baraza at Saboti, September 2023">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl3/600/420" alt="Ward baraza â€” Saboti Constituency" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Ward Baraza â€” Saboti</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--community">Community</span>
              <span class="gl-photo-date">Sep 2023</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="leadership" data-year="2023" data-site="kiminini" role="listitem" aria-label="Open photo: Governor signs land MoU, March 2023">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl4/600/420" alt="Governor signs Land Allocation MoU" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Governor Signs Land MoU</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--leadership">Leadership</span>
              <span class="gl-photo-date">Mar 2023</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="survey" data-year="2023" data-site="kwanza" role="listitem" aria-label="Open photo: NEMA EIA site survey â€” Kwanza constituency, July 2023">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl5/600/420" alt="NEMA EIA site survey â€” Kwanza" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">NEMA EIA Survey â€” Kwanza</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--survey">Site Survey</span>
              <span class="gl-photo-date">Jul 2023</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="construction" data-year="2024" data-site="tn-west" role="listitem" aria-label="Open photo: Matunda AHP ground floor columns cast, May 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl6/600/420" alt="Matunda AHP â€” Ground floor columns cast" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Matunda â€” Ground Floor Columns</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--construction">Construction</span>
              <span class="gl-photo-date">May 2024</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="community" data-year="2023" data-site="kwanza" role="listitem" aria-label="Open photo: Community baraza at Kwanza, October 2023">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl7/600/420" alt="Community baraza â€” Kwanza Constituency" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Community Baraza â€” Kwanza</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--community">Community</span>
              <span class="gl-photo-date">Oct 2023</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="survey" data-year="2024" data-site="kiminini" role="listitem" aria-label="Open photo: NCA quarterly site inspection at Maili Tatu, June 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl8/600/420" alt="NCA quarterly inspection â€” Maili Tatu" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">NCA Inspection â€” Maili Tatu</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--survey">Site Survey</span>
              <span class="gl-photo-date">Jun 2024</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="leadership" data-year="2025" data-site="kiminini" role="listitem" aria-label="Open photo: Phase 1 beneficiary balloting ceremony, January 2025">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl9/600/420" alt="Phase 1 beneficiary balloting ceremony" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Beneficiary Balloting Ceremony</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--leadership">Leadership</span>
              <span class="gl-photo-date">Jan 2025</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="construction" data-year="2025" data-site="saboti" role="listitem" aria-label="Open photo: Saboti AHP roofing milestone, March 2025">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl10/600/420" alt="Saboti AHP â€” Roofing milestone" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Saboti AHP â€” Roofing Milestone</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--construction">Construction</span>
              <span class="gl-photo-date">Mar 2025</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="community" data-year="2025" data-site="tn-east" role="listitem" aria-label="Open photo: Women's Housing Network advocacy meeting, February 2025">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl11/600/420" alt="Trans-Nzoia Women's Housing Network meeting" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Women's Housing Network Meeting</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--community">Community</span>
              <span class="gl-photo-date">Feb 2025</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="groundbreaking" data-year="2024" data-site="tn-west" role="listitem" aria-label="Open photo: Matunda AHP groundbreaking ceremony, March 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl12/600/420" alt="Matunda AHP â€” Groundbreaking ceremony" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Matunda AHP â€” Groundbreaking</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--groundbreaking">Groundbreaking</span>
              <span class="gl-photo-date">Mar 2024</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="construction" data-year="2022" data-site="kiminini" role="listitem" aria-label="Open photo: Maili Tatu site clearing, December 2022">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl13/600/420" alt="Maili Tatu â€” Site clearing works" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Maili Tatu â€” Site Clearing</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--construction">Construction</span>
              <span class="gl-photo-date">Dec 2022</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="survey" data-year="2022" data-site="saboti" role="listitem" aria-label="Open photo: Land survey â€” Saboti AHP site, October 2022">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl14/600/420" alt="Land survey â€” Saboti AHP site" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Land Survey â€” Saboti Site</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--survey">Site Survey</span>
              <span class="gl-photo-date">Oct 2022</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="leadership" data-year="2022" data-site="kiminini" role="listitem" aria-label="Open photo: AHB Field Director receives appointment letter, September 2022">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl15/600/420" alt="Field Director appointment â€” AHB Kitale Office" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Field Director Appointment</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--leadership">Leadership</span>
              <span class="gl-photo-date">Sep 2022</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="construction" data-year="2025" data-site="kwanza" role="listitem" aria-label="Open photo: Kwanza AHP superstructure rising, April 2025">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl16/600/420" alt="Kwanza AHP â€” Superstructure rising" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Kwanza AHP â€” Superstructure</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--construction">Construction</span>
              <span class="gl-photo-date">Apr 2025</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="community" data-year="2024" data-site="tn-east" role="listitem" aria-label="Open photo: Youth cohort orientation session, August 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl17/600/420" alt="Youth cohort orientation â€” Trans-Nzoia East" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Youth Cohort Orientation</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--community">Community</span>
              <span class="gl-photo-date">Aug 2024</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="survey" data-year="2023" data-site="tn-east" role="listitem" aria-label="Open photo: Structural engineering review â€” Trans-Nzoia East site, November 2023">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl18/600/420" alt="Structural engineering review â€” Trans-Nzoia East" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Structural Review â€” TN East</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--survey">Site Survey</span>
              <span class="gl-photo-date">Nov 2023</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="groundbreaking" data-year="2023" data-site="tn-east" role="listitem" aria-label="Open photo: Cherang'any AHP groundbreaking, December 2023">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl19/600/420" alt="Cherang'any AHP â€” Groundbreaking ceremony" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">Cherang'any AHP â€” Groundbreaking</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--groundbreaking">Groundbreaking</span>
              <span class="gl-photo-date">Dec 2023</span>
            </div>
          </button>

          <button class="gl-photo-item" data-cat="leadership" data-year="2024" data-site="saboti" role="listitem" aria-label="Open photo: CS Housing county visit â€” Saboti site tour, September 2024">
            <div class="gl-photo-wrap">
              <img src="https://picsum.photos/seed/gl20/600/420" alt="CS Housing county visit â€” Saboti site tour" loading="lazy">
              <div class="gl-photo-overlay" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                <span class="gl-photo-overlay-title">CS Housing County Visit</span>
              </div>
            </div>
            <div class="gl-photo-meta">
              <span class="gl-photo-badge gl-photo-badge--leadership">Leadership</span>
              <span class="gl-photo-date">Sep 2024</span>
            </div>
          </button>

        </div><!-- /gl-photo-grid -->

        <!-- No-results state -->
        <div class="gl-no-results" id="noResults" aria-live="polite" hidden>
          <i class="fa-solid fa-image-slash" aria-hidden="true"></i>
          <p>No photos match the selected filters. <button class="gl-reset-link" id="resetFilters">Clear filters</button></p>
        </div>

      </div>
    </section>

    <!-- ============================================================
         S4: SITE PROGRESS STRIP
    ============================================================ -->
    <section class="gl-sites" aria-labelledby="sites-heading">
      <div class="gl-sites-bg" aria-hidden="true"></div>
      <div class="container">
        <div class="gl-section-header fade-up">
          <div class="gl-eyebrow"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> By Constituency</div>
          <h2 class="gl-section-title gl-section-title--light" id="sites-heading">Progress by Site</h2>
          <p class="gl-section-sub gl-section-sub--light">Select a constituency to see its photos and current construction status.</p>
        </div>

        <div class="gl-site-tabs fade-up" role="tablist" aria-label="Constituency sites">
          <button class="gl-site-tab is-active" role="tab" aria-selected="true" data-site="kiminini" aria-controls="sitePanel">
            <span class="gl-site-tab-name">Kiminini</span>
            <span class="gl-site-tab-count">7 photos</span>
          </button>
          <button class="gl-site-tab" role="tab" aria-selected="false" data-site="saboti" aria-controls="sitePanel">
            <span class="gl-site-tab-name">Saboti</span>
            <span class="gl-site-tab-count">4 photos</span>
          </button>
          <button class="gl-site-tab" role="tab" aria-selected="false" data-site="kwanza" aria-controls="sitePanel">
            <span class="gl-site-tab-name">Kwanza</span>
            <span class="gl-site-tab-count">3 photos</span>
          </button>
          <button class="gl-site-tab" role="tab" aria-selected="false" data-site="tn-east" aria-controls="sitePanel">
            <span class="gl-site-tab-name">TN East</span>
            <span class="gl-site-tab-count">4 photos</span>
          </button>
          <button class="gl-site-tab" role="tab" aria-selected="false" data-site="tn-west" aria-controls="sitePanel">
            <span class="gl-site-tab-name">TN West</span>
            <span class="gl-site-tab-count">2 photos</span>
          </button>
        </div>

        <div class="gl-site-panel fade-up" id="sitePanel" role="tabpanel">
          <div class="gl-site-info">
            <div class="gl-site-info-body">
              <div class="gl-site-stat">
                <span class="gl-site-stat-num" id="siteStat1">400</span>
                <span class="gl-site-stat-lbl">Units Planned</span>
              </div>
              <div class="gl-site-stat">
                <span class="gl-site-stat-num" id="siteStat2">60%</span>
                <span class="gl-site-stat-lbl">Completion</span>
              </div>
              <div class="gl-site-stat">
                <span class="gl-site-stat-num" id="siteStat3">7</span>
                <span class="gl-site-stat-lbl">Photos</span>
              </div>
            </div>
            <div class="gl-site-progress-bar" aria-hidden="true">
              <div class="gl-site-progress-fill" id="siteProgressFill" style="width:60%"></div>
            </div>
            <a href="constituencies.php" class="gl-site-link">
              View full site details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
          <div class="gl-site-mini-grid" id="siteMiniGrid">
            <!-- populated by JS -->
          </div>
        </div>

      </div>
    </section>

    <!-- ============================================================
         S5: VIDEO GALLERY
    ============================================================ -->
    <section class="gl-video" aria-labelledby="video-heading">
      <div class="container">
        <div class="gl-section-header fade-up">
          <div class="gl-eyebrow"><i class="fa-solid fa-circle-play" aria-hidden="true"></i> Video Updates</div>
          <h2 class="gl-section-title" id="video-heading">Progress Videos</h2>
          <p class="gl-section-sub">Watch construction progress reports, community barazas and official ceremony recordings.</p>
        </div>

        <div class="gl-video-grid fade-up">

          <div class="gl-video-card">
            <div class="gl-video-thumb-wrap">
              <img src="https://picsum.photos/seed/vid1/640/360" alt="Groundbreaking ceremony video thumbnail" loading="lazy">
              <button class="gl-video-play-btn" aria-label="Play groundbreaking ceremony video">
                <i class="fa-solid fa-play" aria-hidden="true"></i>
              </button>
              <span class="gl-video-duration">4:32</span>
            </div>
            <div class="gl-video-body">
              <span class="gl-video-cat">Ceremony</span>
              <h3 class="gl-video-title">Maili Tatu Groundbreaking â€” Official Recording</h3>
              <span class="gl-video-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Feb 2024 &bull; Kiminini</span>
            </div>
          </div>

          <div class="gl-video-card">
            <div class="gl-video-thumb-wrap">
              <img src="https://picsum.photos/seed/vid2/640/360" alt="Site progress walkthrough video thumbnail" loading="lazy">
              <button class="gl-video-play-btn" aria-label="Play site progress walkthrough video">
                <i class="fa-solid fa-play" aria-hidden="true"></i>
              </button>
              <span class="gl-video-duration">7:15</span>
            </div>
            <div class="gl-video-body">
              <span class="gl-video-cat">Progress Report</span>
              <h3 class="gl-video-title">Q4 2024 Construction Progress Walkthrough</h3>
              <span class="gl-video-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Nov 2024 &bull; All Sites</span>
            </div>
          </div>

          <div class="gl-video-card">
            <div class="gl-video-thumb-wrap">
              <img src="https://picsum.photos/seed/vid3/640/360" alt="Community baraza recording thumbnail" loading="lazy">
              <button class="gl-video-play-btn" aria-label="Play community baraza recording">
                <i class="fa-solid fa-play" aria-hidden="true"></i>
              </button>
              <span class="gl-video-duration">12:08</span>
            </div>
            <div class="gl-video-body">
              <span class="gl-video-cat">Community</span>
              <h3 class="gl-video-title">Ward Baraza Series â€” Saboti Full Recording</h3>
              <span class="gl-video-date"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Sep 2023 &bull; Saboti</span>
            </div>
          </div>

        </div><!-- /gl-video-grid -->
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