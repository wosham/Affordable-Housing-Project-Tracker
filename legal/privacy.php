<?php
$basePath = '../';
$activePage = 'privacy';
$pageTitle = 'Privacy Policy | Trans-Nzoia AHP Tracker';
$pageDescription = 'Privacy Policy â€” Trans-Nzoia Affordable Housing Programme Tracker. How we collect, use and protect your personal data.';
$pageKeywords = '';
$pageAuthor = 'Trans-Nzoia County Government';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = '';
$pageStyles = [
  '../assets/css/global.css',
  'privacy.css'
];
$pageScripts = [
  '../assets/js/global.js',
  'privacy.js'
];
$headMeta = [];
include __DIR__ . "/../app/partials/" . 'head.php';
?>
<body>
<?php include __DIR__ . "/../app/partials/" . 'cursor.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'skip-link.php'; ?>
<?php include __DIR__ . "/../app/partials/" . 'navbar.php'; ?><main id="main-content">

    <!-- HERO -->
    <section class="lg-hero lg-hero--privacy" aria-label="Privacy Policy">
      <div class="lg-hero-bg" aria-hidden="true"></div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="../index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Privacy Policy</span>
        </nav>
        <div class="lg-hero-body">
          <div class="lg-hero-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
          <h1 class="lg-hero-title">Privacy Policy</h1>
          <p class="lg-hero-sub">How Trans-Nzoia County Government collects, uses, and protects your personal data in connection with the Affordable Housing Programme.</p>
          <div class="lg-hero-meta">
            <span class="lg-meta-badge"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Last updated: 1 January 2026</span>
            <span class="lg-meta-badge"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span id="lgReadingTime">~8 min read</span></span>
            <button class="lg-meta-badge lg-print-btn" id="lgPrintBtn" aria-label="Print this page">
              <i class="fa-solid fa-print" aria-hidden="true"></i> Print
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- LAYOUT: TOC + ARTICLE -->
    <div class="lg-layout container">

      <!-- Sticky TOC sidebar -->
      <aside class="lg-toc-sidebar" aria-label="Table of contents">
        <div class="lg-toc-inner" id="lgToc">
          <div class="lg-toc-head">
            <span><i class="fa-solid fa-list-ul" aria-hidden="true"></i> Contents</span>
            <button class="lg-toc-toggle" id="lgTocToggle" aria-label="Toggle contents" aria-expanded="true">
              <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
            </button>
          </div>
          <nav class="lg-toc-nav" id="lgTocNav" aria-label="Section navigation">
            <a class="lg-toc-link" href="#pp-intro">1. Introduction</a>
            <a class="lg-toc-link" href="#pp-controller">2. Data Controller</a>
            <a class="lg-toc-link" href="#pp-collect">3. Data We Collect</a>
            <a class="lg-toc-link" href="#pp-use">4. How We Use Your Data</a>
            <a class="lg-toc-link" href="#pp-legal-basis">5. Legal Basis</a>
            <a class="lg-toc-link" href="#pp-sharing">6. Data Sharing</a>
            <a class="lg-toc-link" href="#pp-retention">7. Data Retention</a>
            <a class="lg-toc-link" href="#pp-rights">8. Your Rights</a>
            <a class="lg-toc-link" href="#pp-cookies">9. Cookies</a>
            <a class="lg-toc-link" href="#pp-security">10. Security</a>
            <a class="lg-toc-link" href="#pp-children">11. Children's Privacy</a>
            <a class="lg-toc-link" href="#pp-changes">12. Policy Changes</a>
            <a class="lg-toc-link" href="#pp-contact">13. Contact &amp; Complaints</a>
          </nav>
          <div class="lg-toc-links">
            <a href="terms.php" class="lg-toc-related"><i class="fa-solid fa-file-contract" aria-hidden="true"></i> Terms of Use</a>
            <a href="disclaimer.php" class="lg-toc-related"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Disclaimer</a>
          </div>
        </div>
      </aside>

      <!-- Article -->
      <article class="lg-article" id="lgArticle">

        <section class="lg-section" id="pp-intro">
          <h2 class="lg-section-heading"><span class="lg-section-num">1</span> Introduction</h2>
          <p>Trans-Nzoia County Government ("<strong>we</strong>", "<strong>us</strong>", "<strong>the County</strong>") is committed to protecting your personal data and respecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard information when you access the Trans-Nzoia Affordable Housing Programme (AHP) Tracker website and related digital services.</p>
          <p>This policy applies to all information collected through our website at <strong>housing.transnzoia.go.ke</strong>, the AHP staff portal, online enquiry forms, and any other services we operate in connection with the county's affordable housing programme.</p>
          <div class="lg-callout lg-callout--info">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
              <strong>Kenya Data Protection Act 2019</strong>
              <p>This policy is drafted in compliance with the Kenya Data Protection Act 2019, the Data Protection (General) Regulations 2021, and any applicable guidelines issued by the Office of the Data Protection Commissioner (ODPC Kenya).</p>
            </div>
          </div>
          <p>By using our website or submitting information to us, you acknowledge that you have read and understood this Privacy Policy. If you do not agree with the terms of this policy, please discontinue use of our services.</p>
        </section>

        <section class="lg-section" id="pp-controller">
          <h2 class="lg-section-heading"><span class="lg-section-num">2</span> Data Controller</h2>
          <p>The data controller responsible for your personal information is:</p>
          <div class="lg-data-card">
            <div class="lg-data-row"><span class="lg-data-key">Entity</span><span class="lg-data-val">Trans-Nzoia County Government</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Department</span><span class="lg-data-val">Department of Land, Housing &amp; Physical Planning</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Address</span><span class="lg-data-val">Ardhi House, County Commissioner's Premises, Kitale, Trans-Nzoia County, Kenya</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Phone</span><span class="lg-data-val">+254 53 000 0000</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Email</span><span class="lg-data-val">housing@transnzoia.go.ke</span></div>
            <div class="lg-data-row"><span class="lg-data-key">DPO Email</span><span class="lg-data-val">dpo@transnzoia.go.ke</span></div>
          </div>
          <p>Our Data Protection Officer (DPO) oversees compliance with data protection law and is your first point of contact for any data privacy enquiries.</p>
        </section>

        <section class="lg-section" id="pp-collect">
          <h2 class="lg-section-heading"><span class="lg-section-num">3</span> Data We Collect</h2>
          <p>We collect different categories of information depending on how you interact with our services:</p>
          <h3 class="lg-subsection-heading">3.1 Information You Provide Directly</h3>
          <ul class="lg-list">
            <li><strong>Identity data:</strong> full name, national ID number, date of birth, gender</li>
            <li><strong>Contact data:</strong> postal address, telephone number, email address</li>
            <li><strong>Housing application data:</strong> employment status, household income, household size, current housing status, constituency of residence, supporting documentation</li>
            <li><strong>Enquiry data:</strong> the content of messages sent via our contact form, including any attachments you voluntarily submit</li>
            <li><strong>Staff portal credentials:</strong> email address, hashed password, role/department assignment (for authorised county staff only)</li>
          </ul>
          <h3 class="lg-subsection-heading">3.2 Information Collected Automatically</h3>
          <ul class="lg-list">
            <li><strong>Technical data:</strong> IP address, browser type and version, operating system, device type, screen resolution</li>
            <li><strong>Usage data:</strong> pages visited, links clicked, time spent on pages, referring URL</li>
            <li><strong>Session data:</strong> session cookies required for authenticated portal access (see Section 9)</li>
          </ul>
          <div class="lg-callout lg-callout--warn">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
              <strong>We do not collect sensitive personal data</strong>
              <p>We do not intentionally collect special category data (ethnicity, health, biometrics, religious beliefs) except where required by the national government's beneficiary verification process under the State Department for Housing regulations.</p>
            </div>
          </div>
        </section>

        <section class="lg-section" id="pp-use">
          <h2 class="lg-section-heading"><span class="lg-section-num">4</span> How We Use Your Data</h2>
          <p>We use the personal data we collect for the following purposes:</p>
          <ul class="lg-list">
            <li><strong>Programme administration:</strong> processing housing applications, verifying eligibility, conducting beneficiary balloting, communicating allocation decisions</li>
            <li><strong>Communication:</strong> responding to enquiries, sending programme updates, issuing official notices regarding your application status</li>
            <li><strong>Compliance &amp; legal obligations:</strong> fulfilling reporting requirements to the National Treasury, State Department for Housing, and the Affordable Housing Board (AHB)</li>
            <li><strong>Website operation:</strong> maintaining and improving this website, troubleshooting technical issues, preventing fraud and abuse</li>
            <li><strong>Analytics:</strong> understanding how the public uses our services so we can improve the user experience â€” we use anonymised, aggregated data only for this purpose</li>
            <li><strong>Staff portal management:</strong> managing access rights, audit logs, and security of authenticated county staff accounts</li>
          </ul>
          <p>We will not use your personal data for commercial marketing, sell your data to third parties, or use it for any purpose incompatible with the original reason for collection.</p>
        </section>

        <section class="lg-section" id="pp-legal-basis">
          <h2 class="lg-section-heading"><span class="lg-section-num">5</span> Legal Basis for Processing</h2>
          <p>Under the Kenya Data Protection Act 2019, we must have a lawful basis for processing your personal data. Our processing activities rely on one or more of the following bases:</p>
          <div class="lg-basis-grid">
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-handshake" aria-hidden="true"></i></div>
              <div><strong>Consent</strong><p>For newsletter subscriptions and optional enquiry form submissions.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-file-signature" aria-hidden="true"></i></div>
              <div><strong>Contractual necessity</strong><p>Where processing is necessary to perform our obligations to a housing applicant.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
              <div><strong>Legal obligation</strong><p>Where we are required to process data by the Housing Act (Cap. 117), County Governments Act 2012, or other Kenyan legislation.</p></div>
            </div>
            <div class="lg-basis-item">
              <div class="lg-basis-icon"><i class="fa-solid fa-building-flag" aria-hidden="true"></i></div>
              <div><strong>Public task</strong><p>For processing in the exercise of official county government authority vested in us under the Constitution of Kenya.</p></div>
            </div>
          </div>
        </section>

        <section class="lg-section" id="pp-sharing">
          <h2 class="lg-section-heading"><span class="lg-section-num">6</span> Data Sharing &amp; Third Parties</h2>
          <p>We may share your personal data with the following parties where necessary and lawful:</p>
          <ul class="lg-list">
            <li><strong>Affordable Housing Board (AHB):</strong> the national body responsible for programme oversight receives applicant data to verify eligibility and register allocations</li>
            <li><strong>State Department for Housing &amp; Urban Development:</strong> national-level reporting as mandated by the Affordable Housing Act</li>
            <li><strong>National Housing Corporation (NHC):</strong> where applicable for financing and unit allocation verification</li>
            <li><strong>Other Trans-Nzoia County departments:</strong> internal sharing limited to the County Revenue Authority (rate verification) and County Planning department</li>
            <li><strong>eCitizen / Kenya National BRS:</strong> identity verification against national databases via the IPRS</li>
            <li><strong>Technical service providers:</strong> web hosting, email services, and IT support providers are bound by strict data processing agreements and may not use your data for their own purposes</li>
          </ul>
          <div class="lg-callout lg-callout--success">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <div>
              <strong>No commercial data sales</strong>
              <p>We do not sell, rent, lease, or share your personal data with any commercial entity for marketing or profiling purposes. Your information is used solely for the administration of the Affordable Housing Programme.</p>
            </div>
          </div>
        </section>

        <section class="lg-section" id="pp-retention">
          <h2 class="lg-section-heading"><span class="lg-section-num">7</span> Data Retention</h2>
          <p>We retain personal data only for as long as necessary to fulfil the purpose for which it was collected, and as required by Kenyan law:</p>
          <div class="lg-retention-table">
            <div class="lg-rt-head">
              <span>Data Category</span><span>Retention Period</span><span>Reason</span>
            </div>
            <div class="lg-rt-row">
              <span>Housing application records</span><span>7 years after programme close</span><span>Public audit &amp; accountability requirements</span>
            </div>
            <div class="lg-rt-row">
              <span>Beneficiary allocation records</span><span>Permanent (archived)</span><span>Constitutional accountability for public resources</span>
            </div>
            <div class="lg-rt-row">
              <span>Online enquiry data</span><span>2 years</span><span>Follow-up and dispute resolution</span>
            </div>
            <div class="lg-rt-row">
              <span>Staff portal credentials</span><span>Duration of employment + 1 year</span><span>Security and audit logs</span>
            </div>
            <div class="lg-rt-row">
              <span>Web server access logs</span><span>90 days</span><span>Security monitoring</span>
            </div>
          </div>
          <p>After the retention period expires, data is securely deleted or anonymised in accordance with our Records Management Policy.</p>
        </section>

        <section class="lg-section" id="pp-rights">
          <h2 class="lg-section-heading"><span class="lg-section-num">8</span> Your Rights</h2>
          <p>Under the Kenya Data Protection Act 2019, you have the following rights in relation to your personal data:</p>
          <div class="lg-rights-grid">
            <div class="lg-right-item"><i class="fa-solid fa-eye" aria-hidden="true"></i><div><strong>Right of Access</strong><p>Request a copy of the personal data we hold about you.</p></div></div>
            <div class="lg-right-item"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><div><strong>Right to Rectification</strong><p>Request correction of inaccurate or incomplete data.</p></div></div>
            <div class="lg-right-item"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><div><strong>Right to Erasure</strong><p>Request deletion of your data where there is no compelling reason for continued processing.</p></div></div>
            <div class="lg-right-item"><i class="fa-solid fa-right-left" aria-hidden="true"></i><div><strong>Right to Portability</strong><p>Receive your data in a structured, commonly used format.</p></div></div>
            <div class="lg-right-item"><i class="fa-solid fa-hand" aria-hidden="true"></i><div><strong>Right to Object</strong><p>Object to processing based on legitimate interests or public task.</p></div></div>
            <div class="lg-right-item"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><div><strong>Withdraw Consent</strong><p>Where processing is based on consent, withdraw it at any time without affecting prior processing.</p></div></div>
          </div>
          <p>To exercise any of these rights, submit a written request to <strong>dpo@transnzoia.go.ke</strong>. We will respond within 21 days as required by law. We may need to verify your identity before fulfilling the request.</p>
        </section>

        <section class="lg-section" id="pp-cookies">
          <h2 class="lg-section-heading"><span class="lg-section-num">9</span> Cookies &amp; Tracking</h2>
          <p>We use a minimal set of cookies strictly necessary for the operation of this website. We do not use advertising or tracking cookies, and we do not permit third-party trackers on this site.</p>
          <div class="lg-retention-table">
            <div class="lg-rt-head"><span>Cookie Name</span><span>Type</span><span>Purpose</span><span>Duration</span></div>
            <div class="lg-rt-row"><span><code>ahp_session</code></span><span>Session</span><span>Staff portal authentication</span><span>Session (deleted on close)</span></div>
            <div class="lg-rt-row"><span><code>ahp_remember</code></span><span>Persistent</span><span>"Remember me" login convenience</span><span>30 days</span></div>
            <div class="lg-rt-row"><span><code>ahp_csrf</code></span><span>Session</span><span>Cross-site request forgery protection</span><span>Session</span></div>
          </div>
          <p>Our public-facing pages (tracker, projects, news) operate without any cookies. No analytics pixels, social media embeds, or advertising networks are loaded on this website.</p>
        </section>

        <section class="lg-section" id="pp-security">
          <h2 class="lg-section-heading"><span class="lg-section-num">10</span> Security Measures</h2>
          <p>We take the security of your personal data seriously and implement appropriate technical and organisational measures to protect it against unauthorised access, loss, destruction, or disclosure. These include:</p>
          <ul class="lg-list">
            <li>HTTPS encryption for all data in transit</li>
            <li>Encrypted storage for all sensitive data fields at rest</li>
            <li>Role-based access controls â€” staff can only access data relevant to their duties</li>
            <li>Regular security audits and vulnerability assessments of our web infrastructure</li>
            <li>Mandatory data protection training for all county staff with access to personal data</li>
            <li>Incident response procedures â€” in the event of a breach, we will notify the ODPC within 72 hours and affected individuals as required by law</li>
          </ul>
          <p>While we take every reasonable precaution, no internet transmission or electronic storage method is 100% secure. We cannot guarantee absolute security but are committed to protecting your data to the highest practical standard.</p>
        </section>

        <section class="lg-section" id="pp-children">
          <h2 class="lg-section-heading"><span class="lg-section-num">11</span> Children's Privacy</h2>
          <p>Our website and the AHP staff portal are intended for adults aged 18 and above. We do not knowingly collect personal data from children under 18 years of age. Housing applications require the applicant to be an adult Kenyan citizen.</p>
          <p>If you believe a child has submitted personal data to us without parental consent, please contact our DPO at <strong>dpo@transnzoia.go.ke</strong> and we will promptly delete the information.</p>
        </section>

        <section class="lg-section" id="pp-changes">
          <h2 class="lg-section-heading"><span class="lg-section-num">12</span> Changes to This Policy</h2>
          <p>We may update this Privacy Policy from time to time to reflect changes in law, technology, or our data processing practices. When we make material changes, we will:</p>
          <ul class="lg-list">
            <li>Update the "Last updated" date at the top of this page</li>
            <li>Post a notice on our website homepage for at least 14 days</li>
            <li>Where feasible, notify registered applicants by email</li>
          </ul>
          <p>We encourage you to review this page periodically. Continued use of our services after changes take effect constitutes acceptance of the updated policy.</p>
        </section>

        <section class="lg-section" id="pp-contact">
          <h2 class="lg-section-heading"><span class="lg-section-num">13</span> Contact &amp; Complaints</h2>
          <p>If you have questions, concerns, or wish to exercise your data rights, please contact our Data Protection Officer:</p>
          <div class="lg-data-card">
            <div class="lg-data-row"><span class="lg-data-key">DPO Name</span><span class="lg-data-val">Data Protection Officer</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Email</span><span class="lg-data-val">dpo@transnzoia.go.ke</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Post</span><span class="lg-data-val">DPO, Trans-Nzoia County Government, P.O. Box 4210-30200, Kitale</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Phone</span><span class="lg-data-val">+254 53 000 0000 Ext. 201</span></div>
          </div>
          <p>If you are not satisfied with our response, you have the right to lodge a complaint with the Office of the Data Protection Commissioner (ODPC Kenya):</p>
          <div class="lg-data-card">
            <div class="lg-data-row"><span class="lg-data-key">Website</span><span class="lg-data-val"><a href="https://www.odpc.go.ke" target="_blank" rel="noopener noreferrer">www.odpc.go.ke</a></span></div>
            <div class="lg-data-row"><span class="lg-data-key">Email</span><span class="lg-data-val">info@odpc.go.ke</span></div>
            <div class="lg-data-row"><span class="lg-data-key">Address</span><span class="lg-data-val">Westlands Commercial Centre, 9th Floor, Ring Road Westlands, Nairobi</span></div>
          </div>
        </section>

        <!-- Document footer -->
        <div class="lg-doc-footer">
          <p>This policy was last reviewed and approved by the Trans-Nzoia County Government Legal Services Unit on <strong>1 January 2026</strong>. Document reference: <code>TNZ-PRIVACY-2026-01</code>.</p>
          <div class="lg-doc-nav">
            <a href="terms.php" class="lg-doc-nav-link">
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Terms of Use
            </a>
            <a href="disclaimer.php" class="lg-doc-nav-link">
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Disclaimer
            </a>
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