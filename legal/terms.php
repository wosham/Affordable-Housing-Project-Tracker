<?php
$basePath = '../';
$activePage = 'terms';
$pageTitle = 'Terms of Use | Trans-Nzoia AHP Tracker';
$pageDescription = 'Terms of Use â€” Trans-Nzoia Affordable Housing Programme Tracker. Rules and conditions governing your use of this website and its services.';
$pageKeywords = '';
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = '';
$pageStyles = [
  '../assets/css/global.css',
  'terms.css'
];
$pageScripts = [
  '../assets/js/global.js',
  'terms.js'
];
$headMeta = [];
include __DIR__ . "/../app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/../app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- HERO -->
    <section class="lg-hero lg-hero--terms" aria-label="Terms of Use">
      <div class="lg-hero-bg" aria-hidden="true"></div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="../index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Terms of Use</span>
        </nav>
        <div class="lg-hero-body">
          <div class="lg-hero-icon" aria-hidden="true"><i class="fa-solid fa-file-contract"></i></div>
          <h1 class="lg-hero-title">Terms of Use</h1>
          <p class="lg-hero-sub">Rules and conditions governing your access to and use of the Trans-Nzoia County Affordable Housing Programme Tracker website and associated digital services.</p>
          <div class="lg-hero-meta">
            <span class="lg-meta-badge"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Last updated: 1 January 2026</span>
            <span class="lg-meta-badge"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span id="lgReadingTime">~7 min read</span></span>
            <button class="lg-meta-badge lg-print-btn" id="lgPrintBtn" aria-label="Print this page">
              <i class="fa-solid fa-print" aria-hidden="true"></i> Print
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- LAYOUT -->
    <div class="lg-layout container">

      <!-- TOC Sidebar -->
      <aside class="lg-toc-sidebar" aria-label="Table of contents">
        <div class="lg-toc-inner" id="lgToc">
          <div class="lg-toc-head">
            <span><i class="fa-solid fa-list-ul" aria-hidden="true"></i> Contents</span>
            <button class="lg-toc-toggle" id="lgTocToggle" aria-label="Toggle contents" aria-expanded="true">
              <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
            </button>
          </div>
          <nav class="lg-toc-nav" id="lgTocNav" aria-label="Section navigation">
            <a class="lg-toc-link" href="#tu-acceptance">1. Acceptance</a>
            <a class="lg-toc-link" href="#tu-purpose">2. Website Purpose</a>
            <a class="lg-toc-link" href="#tu-acceptable">3. Acceptable Use</a>
            <a class="lg-toc-link" href="#tu-ip">4. Intellectual Property</a>
            <a class="lg-toc-link" href="#tu-portal">5. Staff Portal</a>
            <a class="lg-toc-link" href="#tu-accuracy">6. Information Accuracy</a>
            <a class="lg-toc-link" href="#tu-third-party">7. Third-Party Links</a>
            <a class="lg-toc-link" href="#tu-warranties">8. Disclaimer of Warranties</a>
            <a class="lg-toc-link" href="#tu-liability">9. Limitation of Liability</a>
            <a class="lg-toc-link" href="#tu-privacy">10. Privacy</a>
            <a class="lg-toc-link" href="#tu-governing">11. Governing Law</a>
            <a class="lg-toc-link" href="#tu-changes">12. Changes to Terms</a>
            <a class="lg-toc-link" href="#tu-contact">13. Contact</a>
          </nav>
          <div class="lg-toc-links">
            <a href="privacy.php" class="lg-toc-related"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Privacy Policy</a>
            <a href="disclaimer.php" class="lg-toc-related"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Disclaimer</a>
          </div>
        </div>
      </aside>

      <!-- Article -->
      <article class="lg-article" id="lgArticle">

        <section class="lg-section" id="tu-acceptance">
          <h2 class="lg-section-heading"><span class="lg-section-num">1</span> Acceptance of Terms</h2>
          <p>Welcome to the Trans-Nzoia County Affordable Housing Programme (AHP) Tracker. By accessing or using this website at <strong>housing.transnzoia.go.ke</strong>, you agree to be bound by these Terms of Use (&ldquo;Terms&rdquo;), our <a href="privacy.php">Privacy Policy</a>, and our <a href="disclaimer.php">Disclaimer</a>. These Terms form a legally binding agreement between you and Trans-Nzoia County Government.</p>
          <p>If you do not agree with any part of these Terms, you must discontinue use of this website immediately. Continued use of the website after any modification constitutes your acceptance of the updated Terms.</p>
          <div class="lg-callout lg-callout--info">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
              <strong>Public Information Service</strong>
              <p>This website is a public accountability and transparency tool operated by Trans-Nzoia County Government. Most content is freely accessible without registration. The Staff Portal is restricted to authorised county government personnel only.</p>
            </div>
          </div>
        </section>

        <section class="lg-section" id="tu-purpose">
          <h2 class="lg-section-heading"><span class="lg-section-num">2</span> Website Purpose &amp; Scope</h2>
          <p>The Trans-Nzoia AHP Tracker is an official public-facing website operated by the Department of Land, Housing &amp; Physical Planning. Its core purposes are:</p>
          <ul class="lg-list">
            <li>Provide real-time and regularly updated information on affordable housing construction progress across all five constituencies of Trans-Nzoia County</li>
            <li>Publish official notices, tender announcements, and programme milestones under the national Affordable Housing Programme (AHP)</li>
            <li>Facilitate public accountability by making construction data, unit delivery timelines, and contractor performance information publicly accessible</li>
            <li>Direct residents to the national Boma Yangu / eCitizen portal for housing applications and allocation decisions</li>
            <li>Provide a restricted Staff Portal for authorised county officials to manage project data, field reports, and communications</li>
          </ul>
          <div class="lg-callout lg-callout--warn">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
              <strong>Applications via eCitizen Only</strong>
              <p>This website does not process housing applications directly. All formal applications must be submitted through the national <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer">eCitizen / Boma Yangu</a> platform operated by the State Department for Housing &amp; Urban Development.</p>
            </div>
          </div>
        </section>

        <section class="lg-section" id="tu-acceptable">
          <h2 class="lg-section-heading"><span class="lg-section-num">3</span> Acceptable Use</h2>
          <p>You may use this website for lawful purposes only. When using this website, you agree <strong>not</strong> to:</p>
          <ul class="lg-list">
            <li>Deploy any automated tool, script, spider, or scraper to extract data in a manner that places excessive load on our servers or circumvents access controls</li>
            <li>Attempt to gain unauthorised access to the Staff Portal, administrative systems, databases, or any restricted section of this website</li>
            <li>Transmit malware, viruses, ransomware, or any malicious code intended to disrupt, damage, or gain control of any computer system</li>
            <li>Impersonate any county official, staff member, contractor, or other person in connection with this website</li>
            <li>Use this website to spread misinformation, make false statements about the programme, or misrepresent published data</li>
            <li>Reproduce, republish, or redistribute content from this website for commercial gain without prior written consent from Trans-Nzoia County Government</li>
            <li>Conduct any activity that violates applicable Kenyan law, including the Computer Misuse and Cybercrimes Act 2018</li>
          </ul>
          <p>We reserve the right to block access to any person or entity found to be in violation of these standards, without prior notice.</p>
        </section>

        <section class="lg-section" id="tu-ip">
          <h2 class="lg-section-heading"><span class="lg-section-num">4</span> Intellectual Property</h2>
          <p>Unless otherwise stated, all content on this website &mdash; including text, graphics, maps, photographs, logos, icons, project data, and page layouts &mdash; is the property of Trans-Nzoia County Government or its licensed content providers.</p>
          <h3 class="lg-subsection-heading">4.1 Permitted Use</h3>
          <ul class="lg-list">
            <li>Access and read content for personal, non-commercial, and public information purposes</li>
            <li>Print or save a single copy of individual pages for personal reference or official correspondence</li>
            <li>Journalists may quote limited extracts for reporting purposes with clear attribution to Trans-Nzoia County Government</li>
            <li>Academic and research use of aggregated data is permitted with proper citation of the Trans-Nzoia AHP Tracker as the source</li>
          </ul>
          <h3 class="lg-subsection-heading">4.2 Prohibited Use</h3>
          <ul class="lg-list">
            <li>Commercial republication or redistribution of any content without written consent</li>
            <li>Removal or alteration of any copyright, trademark, or attribution notices</li>
            <li>Use of county government logos, seals, or official marks in a misleading context</li>
            <li>Creation of derivative works or commercial databases from website content without authorisation</li>
          </ul>
          <p>Content reuse and licensing requests should be directed to <strong>housing@transnzoia.go.ke</strong>.</p>
        </section>

        <section class="lg-section" id="tu-portal">
          <h2 class="lg-section-heading"><span class="lg-section-num">5</span> Staff Portal &amp; User Accounts</h2>
          <p>Access to the AHP Staff Portal is restricted to authorised Trans-Nzoia County Government employees and designated contractors. Accounts are granted at the sole discretion of the County Director of Housing.</p>
          <div class="lg-basis-grid">
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></div>
              <div><strong>Account Security</strong><p>You are responsible for maintaining confidentiality of your credentials. Do not share passwords or permit unauthorised use of your account.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
              <div><strong>Report Breaches</strong><p>Immediately report any suspected unauthorised access to your ICT administrator or to <strong>ict@transnzoia.go.ke</strong>.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-ban" aria-hidden="true"></i></div>
              <div><strong>Account Termination</strong><p>Accounts may be suspended or revoked upon cessation of employment, contract end, or breach of these Terms.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-scroll" aria-hidden="true"></i></div>
              <div><strong>Audit Logging</strong><p>All Staff Portal actions are logged and retained for security auditing. Logs may be used in disciplinary or legal proceedings.</p></div>
            </div>
          </div>
          <p>Unauthorised access attempts are a criminal offence under the Computer Misuse and Cybercrimes Act 2018 and will be reported to relevant law enforcement authorities.</p>
        </section>

        <section class="lg-section" id="tu-accuracy">
          <h2 class="lg-section-heading"><span class="lg-section-num">6</span> Information Accuracy &amp; Updates</h2>
          <p>Trans-Nzoia County Government makes every reasonable effort to ensure information on this website is accurate, current, and complete. However, construction data, progress percentages, unit counts, and delivery timelines are subject to continuous revision as projects evolve.</p>
          <div class="lg-callout lg-callout--info">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
              <strong>Data Currency Notice</strong>
              <p>Project data is updated on a best-efforts basis by county field officers. There may be a lag between on-site events and their reflection on this tracker. Official certified progress records are maintained by the respective Clerks of Works and county project managers.</p>
            </div>
          </div>
          <p>If you notice a factual error or data discrepancy, please report it to <strong>housing@transnzoia.go.ke</strong> so we can investigate and correct it promptly.</p>
        </section>

        <section class="lg-section" id="tu-third-party">
          <h2 class="lg-section-heading"><span class="lg-section-num">7</span> Third-Party Links &amp; Services</h2>
          <p>This website contains links to third-party websites and services for reference and convenience, including:</p>
          <ul class="lg-list">
            <li><strong>eCitizen / Boma Yangu</strong> &mdash; the national housing application portal operated by the State Department for Housing</li>
            <li><strong>Kenya National Bureau of Statistics (KNBS)</strong> &mdash; for socioeconomic data references</li>
            <li><strong>National Environment Management Authority (NEMA)</strong> &mdash; for environmental clearance references</li>
            <li><strong>National Housing Corporation (NHC)</strong> &mdash; for financing programme information</li>
            <li><strong>Affordable Housing Board (AHB)</strong> &mdash; for national programme oversight updates</li>
          </ul>
          <p>Trans-Nzoia County Government has no control over the content, privacy practices, or availability of third-party websites and is not responsible for any loss or damage arising from your use of them. Inclusion of a link does not constitute endorsement of that website or its content.</p>
        </section>

        <section class="lg-section" id="tu-warranties">
          <h2 class="lg-section-heading"><span class="lg-section-num">8</span> Disclaimer of Warranties</h2>
          <p>This website and its content are provided on an &ldquo;<strong>as is</strong>&rdquo; and &ldquo;<strong>as available</strong>&rdquo; basis, without warranties of any kind &mdash; either express or implied &mdash; to the fullest extent permissible under Kenyan law. Trans-Nzoia County Government does not warrant that:</p>
          <ul class="lg-list">
            <li>The website will be available, uninterrupted, or error-free at all times</li>
            <li>Any defects or errors will be corrected immediately upon discovery</li>
            <li>The website or its servers are free of viruses or other harmful components</li>
            <li>Information on this website is complete, accurate, or up to date at every moment</li>
          </ul>
          <p>Please refer to our separate <a href="disclaimer.php">Disclaimer</a> for a detailed statement on the limitations of data published on this website.</p>
        </section>

        <section class="lg-section" id="tu-liability">
          <h2 class="lg-section-heading"><span class="lg-section-num">9</span> Limitation of Liability</h2>
          <p>To the maximum extent permitted by law, Trans-Nzoia County Government, its officers, employees, and agents shall not be liable for any direct, indirect, incidental, consequential, special, or punitive damages arising from:</p>
          <ul class="lg-list">
            <li>Your use of, or inability to use, this website or its content</li>
            <li>Any reliance placed on information published on this website</li>
            <li>Unauthorised access to or alteration of your data transmissions</li>
            <li>Any interruption, suspension, or cessation of this website or its services</li>
            <li>Any loss of profits, data, business opportunity, or goodwill</li>
          </ul>
          <p>Nothing in these Terms shall exclude or limit liability for death or personal injury caused by negligence, or for any fraudulent misrepresentation, or any other liability that cannot be excluded under Kenyan law.</p>
        </section>

        <section class="lg-section" id="tu-privacy">
          <h2 class="lg-section-heading"><span class="lg-section-num">10</span> Privacy</h2>
          <p>Your use of this website is also governed by our <a href="privacy.php">Privacy Policy</a>, which is incorporated into these Terms by reference. The Privacy Policy explains how we collect, use, and protect personal data you provide or that is automatically collected during your visit.</p>
          <p>By using this website, you consent to data processing as described in the Privacy Policy. If you do not agree with how we handle personal data, please discontinue use of this website.</p>
        </section>

        <section class="lg-section" id="tu-governing">
          <h2 class="lg-section-heading"><span class="lg-section-num">11</span> Governing Law &amp; Jurisdiction</h2>
          <p>These Terms shall be governed by and construed in accordance with the laws of the Republic of Kenya, including:</p>
          <ul class="lg-list">
            <li>The Constitution of Kenya 2010</li>
            <li>The County Governments Act 2012</li>
            <li>The Computer Misuse and Cybercrimes Act 2018</li>
            <li>The Kenya Information and Communications Act (Cap. 411A)</li>
            <li>The Affordable Housing Act 2024</li>
          </ul>
          <p>Any dispute arising from these Terms shall be subject to the exclusive jurisdiction of the courts of Kenya. Disputes will first be referred for mediation through the Trans-Nzoia County Dispute Resolution Committee before proceeding to formal litigation.</p>
        </section>

        <section class="lg-section" id="tu-changes">
          <h2 class="lg-section-heading"><span class="lg-section-num">12</span> Changes to These Terms</h2>
          <p>We reserve the right to amend these Terms at any time to reflect changes in law, operational requirements, or website functionality. When we make material changes, we will:</p>
          <ul class="lg-list">
            <li>Update the &ldquo;Last updated&rdquo; date at the top of this page</li>
            <li>Post a notice on our website homepage for at least 14 days following the change</li>
            <li>Where feasible, notify registered Staff Portal users by email</li>
          </ul>
          <p>Continued use of this website after changes take effect constitutes your acceptance of the revised Terms.</p>
        </section>

        <section class="lg-section" id="tu-contact">
          <h2 class="lg-section-heading"><span class="lg-section-num">13</span> Contact</h2>
          <p>For questions about these Terms, to report a violation, or to request content use permission, please contact us:</p>
          <div class="lg-data-card">
            <div class="lg-data-row"><span class="lg-data-key">Entity</span><span class="lg-data-val">Trans-Nzoia County Government</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Department</span><span class="lg-data-val">Department of Land, Housing &amp; Physical Planning</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Address</span><span class="lg-data-val">County Headquarters, Kitale, Trans-Nzoia County, Kenya</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Phone</span><span class="lg-data-val">+254 53 000 0000</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Email</span><span class="lg-data-val">housing@transnzoia.go.ke</span></div>
          </div>
          <p>For formal legal notices, address correspondence to the County Secretary, Trans-Nzoia County Government, P.O. Box 4210-30200, Kitale, Kenya.</p>
        </section>

        <div class="lg-doc-footer">
          <p>These Terms were last reviewed and approved by the Trans-Nzoia County Government Legal Services Unit on <strong>1 January 2026</strong>. Document reference: <code>TNZ-TERMS-2026-01</code>.</p>
          <div class="lg-doc-nav">
            <a href="privacy.php" class="lg-doc-nav-link"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Privacy Policy</a>
            <a href="disclaimer.php" class="lg-doc-nav-link"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Disclaimer</a>
          </div>
        </div>

      </article>
    </div><!-- /lg-layout -->

  </main>

  <!-- FOOTER -->
<?php include __DIR__ . "/../app/partials/" . 'footer.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'back-to-top.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'mobile-menu.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'scripts.php'; ?>
</body>
</html>