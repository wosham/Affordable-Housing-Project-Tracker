<?php
$basePath = '../';
$activePage = 'disclaimer';
$pageTitle = 'Disclaimer | Trans-Nzoia AHP Tracker';
$pageDescription = 'Disclaimer â€” Trans-Nzoia Affordable Housing Programme Tracker. Important limitations on the use of data and information published on this website.';
$pageKeywords = '';
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = '';
$pageStyles = [
  '../assets/css/global.css',
  'disclaimer.css'
];
$pageScripts = [
  '../assets/js/global.js',
  'disclaimer.js'
];
$headMeta = [];
include __DIR__ . "/../app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/../app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- HERO -->
    <section class="lg-hero lg-hero--disclaimer" aria-label="Disclaimer">
      <div class="lg-hero-bg" aria-hidden="true"></div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="../index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Disclaimer</span>
        </nav>
        <div class="lg-hero-body">
          <div class="lg-hero-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <h1 class="lg-hero-title">Disclaimer</h1>
          <p class="lg-hero-sub">Important limitations and qualifications on the data, information, and content published on the Trans-Nzoia County Affordable Housing Programme Tracker website.</p>
          <div class="lg-hero-meta">
            <span class="lg-meta-badge"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Last updated: 1 January 2026</span>
            <span class="lg-meta-badge"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span id="lgReadingTime">~6 min read</span></span>
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
            <a class="lg-toc-link" href="#ds-general">1. General Notice</a>
            <a class="lg-toc-link" href="#ds-project-data">2. Project Data</a>
            <a class="lg-toc-link" href="#ds-financial">3. Financial Information</a>
            <a class="lg-toc-link" href="#ds-allocation">4. Allocation &amp; Eligibility</a>
            <a class="lg-toc-link" href="#ds-maps">5. Maps &amp; Spatial Data</a>
            <a class="lg-toc-link" href="#ds-third-party">6. Third-Party Content</a>
            <a class="lg-toc-link" href="#ds-advice">7. No Professional Advice</a>
            <a class="lg-toc-link" href="#ds-availability">8. System Availability</a>
            <a class="lg-toc-link" href="#ds-media">9. Media &amp; Photography</a>
            <a class="lg-toc-link" href="#ds-liability">10. Liability</a>
            <a class="lg-toc-link" href="#ds-changes">11. Changes</a>
            <a class="lg-toc-link" href="#ds-contact">12. Contact</a>
          </nav>
          <div class="lg-toc-links">
            <a href="privacy.php" class="lg-toc-related"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Privacy Policy</a>
            <a href="terms.php" class="lg-toc-related"><i class="fa-solid fa-file-contract" aria-hidden="true"></i> Terms of Use</a>
          </div>
        </div>
      </aside>

      <!-- Article -->
      <article class="lg-article" id="lgArticle">

        <section class="lg-section" id="ds-general">
          <h2 class="lg-section-heading"><span class="lg-section-num">1</span> General Notice</h2>
          <p>The Trans-Nzoia County Affordable Housing Programme (AHP) Tracker is an official public information website operated by Trans-Nzoia County Government. It is designed to promote transparency and public accountability in the delivery of the national Affordable Housing Programme within Trans-Nzoia County.</p>
          <p>While every effort is made to ensure accuracy, this website is provided for <strong>general information and transparency purposes only</strong>. The information contained herein does not constitute official government correspondence, legal certification, or binding administrative decision unless explicitly stated as such with appropriate authorisation.</p>
          <div class="lg-callout lg-callout--warn">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
              <strong>Not Legally Binding</strong>
              <p>Data published on this tracker does not constitute official government records, legal titles, approved development plans, or binding contractual obligations. All official records are maintained and certified by the relevant county and national government registries.</p>
            </div>
          </div>
        </section>

        <section class="lg-section" id="ds-project-data">
          <h2 class="lg-section-heading"><span class="lg-section-num">2</span> Project Data &amp; Construction Progress</h2>
          <p>Construction progress figures, unit counts, timeline estimates, contractor performance ratings, and completion percentages published on this website are based on field reports submitted by county-assigned Clerks of Works, project managers, and contractor submissions. These figures are subject to the following limitations:</p>
          <ul class="lg-list">
            <li><strong>Lag time:</strong> There may be a delay of up to 14 working days between on-site events and their publication on this tracker. Data displayed represents the most recent verified submission, not necessarily the real-time site status.</li>
            <li><strong>Measurement standards:</strong> Progress percentages are assessed against the approved Bills of Quantities (BOQs) and programme milestones. Minor variations in methodology between different site officers may affect comparative figures.</li>
            <li><strong>Force majeure:</strong> Timelines may be revised due to adverse weather, supply chain disruptions, labour disputes, contractor insolvency, design revisions, or other factors outside county government control.</li>
            <li><strong>Provisional figures:</strong> Unit counts and allocation figures are based on current approved designs. Changes to approved architectural plans may alter final delivery numbers.</li>
          </ul>
          <p>For certified, legally valid progress records, contact the Department of Land, Housing &amp; Physical Planning directly.</p>
        </section>

        <section class="lg-section" id="ds-financial">
          <h2 class="lg-section-heading"><span class="lg-section-num">3</span> Financial Information</h2>
          <p>Budget figures, contract values, levy contribution rates, and financing information published on this website are provided for public transparency purposes. The following qualifications apply:</p>
          <div class="lg-basis-grid">
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
              <div><strong>Contract Values</strong><p>Published contract values represent the original tender award amount. Variations, extensions, and supplementary agreements may alter final expenditure figures.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-percent" aria-hidden="true"></i></div>
              <div><strong>Levy Rates</strong><p>Affordable Housing Levy contribution rates are determined by national legislation. This website may not reflect the most recent statutory amendments. Always verify with the Kenya Revenue Authority (KRA).</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></div>
              <div><strong>Mortgage Terms</strong><p>Illustrative mortgage and rent-to-own repayment figures are indicative only. Actual terms are determined by approved lending institutions and the Affordable Housing Board.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div>
              <div><strong>Market Data</strong><p>Any property valuation or market comparison data is informational only and does not constitute a formal valuation or investment advice.</p></div>
            </div>
          </div>
          <p>Official financial data is published by the Controller of Budget, the Auditor General, and the County Treasury in accordance with the Public Finance Management Act 2012.</p>
        </section>

        <section class="lg-section" id="ds-allocation">
          <h2 class="lg-section-heading"><span class="lg-section-num">4</span> Allocation &amp; Eligibility Information</h2>
          <p>Information about housing unit allocation, eligibility criteria, application processes, and balloting procedures published on this website is provided for general guidance only.</p>
          <div class="lg-callout lg-callout--warn">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
              <strong>Official Process via eCitizen / Boma Yangu</strong>
              <p>All housing applications, eligibility determinations, and allocation decisions are administered exclusively through the national <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer">eCitizen / Boma Yangu</a> platform under the Affordable Housing Board (AHB). Trans-Nzoia County Government does not make or influence individual allocation decisions.</p>
            </div>
          </div>
          <p>In the event of any conflict or discrepancy between information on this website and official communications from the Affordable Housing Board, the Affordable Housing Board&rsquo;s official communication shall prevail.</p>
          <p>Any third party claiming to facilitate housing allocation in exchange for payment or favour is not acting on behalf of Trans-Nzoia County Government. Report such incidents to <strong>housing@transnzoia.go.ke</strong> or the Ethics and Anti-Corruption Commission (EACC).</p>
        </section>

        <section class="lg-section" id="ds-maps">
          <h2 class="lg-section-heading"><span class="lg-section-num">5</span> Maps &amp; Spatial Data</h2>
          <p>Maps, site boundaries, constituency outlines, and spatial data displayed on this website are for illustrative and navigational purposes only. They are subject to the following limitations:</p>
          <ul class="lg-list">
            <li>Administrative boundaries shown are based on the best available data at the time of publication and may not reflect current boundary determinations by the Independent Electoral and Boundaries Commission (IEBC)</li>
            <li>Site location pins are approximate. Exact surveyed coordinates and legal plot boundaries are recorded in official title documents at the Survey of Kenya and county lands registry</li>
            <li>Maps sourced from or rendered by third-party providers (e.g. Google Maps, OpenStreetMap) are subject to those providers&rsquo; own accuracy limitations and terms of use</li>
            <li>No warranty is given that any map or spatial data accurately represents legal ownership, zoning classification, or planning approval status</li>
          </ul>
          <p>For surveyed plans, official site boundaries, or title information, contact the Department of Land, Housing &amp; Physical Planning or the Survey of Kenya.</p>
        </section>

        <section class="lg-section" id="ds-third-party">
          <h2 class="lg-section-heading"><span class="lg-section-num">6</span> Third-Party Content &amp; External Links</h2>
          <p>This website may reference, cite, or link to content from third-party organisations including national government agencies, development partners, contractors, and media sources. Trans-Nzoia County Government:</p>
          <ul class="lg-list">
            <li>Does not endorse or verify the accuracy of third-party content referenced or linked from this website</li>
            <li>Is not responsible for the availability, content, privacy practices, or security of any linked external website</li>
            <li>Does not control third-party data sources and cannot guarantee their currency or completeness</li>
            <li>May cite reports, studies, or statistics from third parties which represent the views of those organisations, not Trans-Nzoia County Government</li>
          </ul>
          <p>Any opinions, findings, or conclusions expressed in third-party materials cited on this website are those of the original authors.</p>
        </section>

        <section class="lg-section" id="ds-advice">
          <h2 class="lg-section-heading"><span class="lg-section-num">7</span> No Professional Advice</h2>
          <p>Nothing on this website constitutes or should be relied upon as:</p>
          <ul class="lg-list">
            <li><strong>Legal advice</strong> &mdash; for legal matters relating to land tenure, housing rights, or contracts, consult a qualified advocate registered with the Law Society of Kenya</li>
            <li><strong>Financial advice</strong> &mdash; for mortgage, investment, or financial planning decisions, consult a qualified financial adviser regulated by the Capital Markets Authority or Central Bank of Kenya</li>
            <li><strong>Valuation advice</strong> &mdash; for property valuations, engage a registered valuer with the Institution of Surveyors of Kenya (ISK)</li>
            <li><strong>Planning or engineering advice</strong> &mdash; for technical matters relating to construction or land use, engage registered professionals with the Engineers Board of Kenya (EBK) or Architectural Association of Kenya (AAK)</li>
          </ul>
          <div class="lg-callout lg-callout--info">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
              <strong>Seek Professional Guidance</strong>
              <p>Before making any significant decision based on information found on this website &mdash; including applying for housing, entering into contracts, or making financial commitments &mdash; we strongly recommend seeking independent professional advice appropriate to your circumstances.</p>
            </div>
          </div>
        </section>

        <section class="lg-section" id="ds-availability">
          <h2 class="lg-section-heading"><span class="lg-section-num">8</span> System Availability &amp; Technical Limitations</h2>
          <p>This website is operated on a best-efforts basis. Trans-Nzoia County Government does not guarantee continuous, uninterrupted, or error-free availability of the website or its data services. Interruptions may occur due to:</p>
          <ul class="lg-list">
            <li>Scheduled maintenance and system upgrades</li>
            <li>Unplanned server outages, hosting provider incidents, or network disruptions</li>
            <li>Power or connectivity failures at county government infrastructure</li>
            <li>Cyber security incidents or denial-of-service attacks</li>
            <li>Force majeure events</li>
          </ul>
          <p>Critical official communications will always be made available through alternative official channels including county government offices, the Kenya Gazette, and official press releases, in addition to this website.</p>
        </section>

        <section class="lg-section" id="ds-media">
          <h2 class="lg-section-heading"><span class="lg-section-num">9</span> Media &amp; Photography</h2>
          <p>Photographs, videos, and visual media published on this website may depict work in progress and do not represent the final completed state of any housing development. Specific limitations include:</p>
          <ul class="lg-list">
            <li>Images are selected for illustrative purposes and may not represent the specific unit or block you are enquiring about</li>
            <li>Architectural renders and 3D visualisations are artist&rsquo;s impressions only; final completed designs may differ from renders shown</li>
            <li>Photographs of individuals on construction sites are used with consent and do not constitute endorsement of any product or service</li>
            <li>Before-and-after imagery comparisons may not be from the same vantage point or lighting conditions and should not be used as precise progress evidence</li>
          </ul>
        </section>

        <section class="lg-section" id="ds-liability">
          <h2 class="lg-section-heading"><span class="lg-section-num">10</span> Limitation of Liability</h2>
          <p>Trans-Nzoia County Government, its officers, employees, contractors, and agents shall not be liable for any loss, damage, or harm arising from:</p>
          <ul class="lg-list">
            <li>Reliance on any information, data, or content published on this website</li>
            <li>Decisions made on the basis of information found on this website without independent verification</li>
            <li>Website unavailability, data errors, or technical failures</li>
            <li>Any action taken by any third party as a result of information published on this website</li>
          </ul>
          <p>To the fullest extent permitted by the laws of Kenya, all implied warranties, conditions, and representations are excluded. Nothing in this disclaimer limits liability for death or personal injury caused by negligence, or for fraudulent misrepresentation.</p>
          <p>For a full statement of limitations, please also review our <a href="terms.php">Terms of Use</a>.</p>
        </section>

        <section class="lg-section" id="ds-changes">
          <h2 class="lg-section-heading"><span class="lg-section-num">11</span> Changes to This Disclaimer</h2>
          <p>This Disclaimer may be updated from time to time to reflect changes in the information we publish, our operational practices, or applicable law. Material changes will be notified via a notice on the homepage for at least 14 days. The &ldquo;Last updated&rdquo; date at the top of this page will reflect the most recent revision.</p>
          <p>We encourage you to review this Disclaimer periodically. Continued use of the website following publication of an updated Disclaimer constitutes your acceptance of the revised version.</p>
        </section>

        <section class="lg-section" id="ds-contact">
          <h2 class="lg-section-heading"><span class="lg-section-num">12</span> Contact</h2>
          <p>If you believe information on this website is inaccurate, misleading, or requires correction, or if you have questions about this Disclaimer, please contact us:</p>
          <div class="lg-data-card">
            <div class="lg-data-row"><span class="lg-data-key">Entity</span><span class="lg-data-val">Trans-Nzoia County Government</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Department</span><span class="lg-data-val">Department of Land, Housing &amp; Physical Planning</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Address</span><span class="lg-data-val">County Headquarters, Kitale, Trans-Nzoia County, Kenya</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Phone</span><span class="lg-data-val">+254 53 000 0000</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Email</span><span class="lg-data-val">housing@transnzoia.go.ke</span></div>
          </div>
          <p>Data accuracy issues specifically may be reported to the Data Protection Officer at <strong>dpo@transnzoia.go.ke</strong>.</p>
        </section>

        <div class="lg-doc-footer">
          <p>This Disclaimer was last reviewed and approved by the Trans-Nzoia County Government Legal Services Unit on <strong>1 January 2026</strong>. Document reference: <code>TNZ-DISC-2026-01</code>.</p>
          <div class="lg-doc-nav">
            <a href="privacy.php" class="lg-doc-nav-link"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Privacy Policy</a>
            <a href="terms.php" class="lg-doc-nav-link"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Terms of Use</a>
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