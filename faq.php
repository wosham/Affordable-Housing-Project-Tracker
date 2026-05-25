<?php
$basePath = '';
$activePage = 'faq';
$pageTitle = 'FAQ | Trans-Nzoia AHP Tracker';
$pageDescription = 'Frequently asked questions about the Trans-Nzoia Affordable Housing Programme â€” eligibility, application, payments, unit allocation, construction timelines and beneficiary rights.';
$pageKeywords = 'Trans-Nzoia affordable housing FAQ, AHP Kenya questions, housing levy Kenya, Kitale housing application';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/faq.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/faq.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/faq.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="FAQ | Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Answers to your questions about the Trans-Nzoia Affordable Housing Programme.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/faq.php">',
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
    <section class="fq-hero" aria-label="FAQ overview">
      <div class="fq-hero-bg" aria-hidden="true">
        <div class="fq-hero-overlay"></div>
        <div class="fq-hero-grid"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">FAQ</span>
        </nav>
        <div class="fq-hero-body">
          <div class="fq-hero-eyebrow">
            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
            Frequently Asked Questions
          </div>
          <h1 class="fq-hero-title">Your Questions,<br>Answered.</h1>
          <p class="fq-hero-sub">Everything you need to know about eligibility, applying, monthly contributions, unit allocation and your rights as an AHP beneficiary in Trans-Nzoia County.</p>

          <!-- Live Search -->
          <div class="fq-search-wrap" role="search" aria-label="Search FAQ">
            <label for="faqSearch" class="sr-only">Search frequently asked questions</label>
            <i class="fa-solid fa-magnifying-glass fq-search-icon" aria-hidden="true"></i>
            <input type="search" id="faqSearch" class="fq-search-input" placeholder="Search questions, e.g. &ldquo;How do I apply?&rdquo;" autocomplete="off" spellcheck="false">
            <button class="fq-search-clear" id="faqSearchClear" aria-label="Clear search" hidden>
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>

          <div class="fq-hero-kpi-strip" role="region" aria-label="FAQ at a glance">
            <div class="fq-kpi-item">
              <span class="fq-kpi-num">45+</span>
              <span class="fq-kpi-lbl">Questions Answered</span>
            </div>
            <div class="fq-kpi-div" aria-hidden="true"></div>
            <div class="fq-kpi-item">
              <span class="fq-kpi-num">8</span>
              <span class="fq-kpi-lbl">Categories</span>
            </div>
            <div class="fq-kpi-div" aria-hidden="true"></div>
            <div class="fq-kpi-item">
              <span class="fq-kpi-num">Monthly</span>
              <span class="fq-kpi-lbl">Content Updates</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S2: POPULAR QUESTIONS QUICK LINKS
    ============================================================ -->
    <section class="fq-popular fade-up" aria-labelledby="popular-heading">
      <div class="container">
        <p class="fq-popular-label" id="popular-heading">
          <i class="fa-solid fa-fire" aria-hidden="true"></i> Popular Questions
        </p>
        <div class="fq-popular-pills" role="list">
          <button class="fq-pill" data-target="q-how-apply" role="listitem">How do I apply for a unit?</button>
          <button class="fq-pill" data-target="q-who-eligible" role="listitem">Who is eligible?</button>
          <button class="fq-pill" data-target="q-levy-amount" role="listitem">What is the monthly levy?</button>
          <button class="fq-pill" data-target="q-when-complete" role="listitem">When will construction finish?</button>
          <button class="fq-pill" data-target="q-allocation-process" role="listitem">How are units allocated?</button>
          <button class="fq-pill" data-target="q-pwd-priority" role="listitem">Is there priority for PWD?</button>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S3: CATEGORY TABS + ACCORDION GROUPS
    ============================================================ -->
    <section class="fq-main" aria-labelledby="faq-main-heading">
      <div class="container">
        <h2 class="sr-only" id="faq-main-heading">All FAQ Categories</h2>

        <!-- Category Tab Strip -->
        <div class="fq-cat-tabs fade-up" role="tablist" aria-label="FAQ categories" id="fqCatTabs">
          <button class="fq-cat-tab is-active" role="tab" aria-selected="true"  data-cat="all">
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i> All
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="eligibility">
            <i class="fa-solid fa-user-check" aria-hidden="true"></i> Eligibility
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="application">
            <i class="fa-solid fa-file-pen" aria-hidden="true"></i> Application
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="payments">
            <i class="fa-solid fa-coins" aria-hidden="true"></i> Payments &amp; Levy
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="allocation">
            <i class="fa-solid fa-ballot-check" aria-hidden="true"></i> Unit Allocation
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="construction">
            <i class="fa-solid fa-hard-hat" aria-hidden="true"></i> Construction
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="legal">
            <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Legal &amp; Docs
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="rights">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Beneficiary Rights
          </button>
          <button class="fq-cat-tab" role="tab" aria-selected="false" data-cat="contact">
            <i class="fa-solid fa-phone" aria-hidden="true"></i> Contact &amp; Support
          </button>
        </div>

        <!-- Search no-results state -->
        <div class="fq-search-noresults" id="fqNoResults" hidden aria-live="polite">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <p>No questions match <strong id="fqNoResultsQuery"></strong>. Try a different keyword or <button class="fq-inline-btn" id="fqClearSearch">clear the search</button>.</p>
        </div>

        <!-- ---- GROUP: ELIGIBILITY ---- -->
        <div class="fq-group" data-group="eligibility" id="group-eligibility">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Eligibility</h3>
              <p class="fq-group-sub">Who qualifies for the programme and what conditions apply.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-eligibility">

            <div class="fq-item" data-cat="eligibility" id="q-who-eligible">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-who-eligible">
                <span class="fq-q-text">Who is eligible to apply for an affordable housing unit?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-who-eligible" role="region">
                <div class="fq-panel-inner">
                  <p>To be eligible, you must be a <strong>Kenyan citizen</strong> who:</p>
                  <ul>
                    <li>Is <strong>18 years of age or older</strong></li>
                    <li>Has a valid <strong>National ID or Passport</strong></li>
                    <li>Has a <strong>KRA PIN</strong> (required for levy deductions and eCitizen registration)</li>
                    <li>Does <strong>not</strong> already own a residential property in Kenya</li>
                    <li>Falls within the defined <strong>income brackets</strong> (Social Housing: below KSh 19,999/month; Affordable: KSh 20,000â€“149,999/month)</li>
                  </ul>
                  <p>Priority is given to civil servants, teachers, nurses, uniformed service personnel, persons with disabilities (PWD), and widowed or single-parent households.</p>
                  <div class="fq-helpful" data-id="q-who-eligible">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="eligibility" id="q-pwd-priority">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-pwd-priority">
                <span class="fq-q-text">Is there special consideration for persons with disabilities (PWD)?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-pwd-priority" role="region">
                <div class="fq-panel-inner">
                  <p>Yes. The Affordable Housing Programme reserves <strong>at least 5% of all units</strong> in every development for persons with disabilities. PWD applicants must provide a <strong>National Council for Persons with Disabilities (NCPWD) certificate</strong> at the point of application.</p>
                  <p>In Trans-Nzoia, ground-floor accessible units with widened doorways and adapted bathroom fittings are included in the Maili Tatu Estate design.</p>
                  <div class="fq-helpful" data-id="q-pwd-priority">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="eligibility">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-diaspora">
                <span class="fq-q-text">Can Kenyans in the diaspora apply?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-diaspora" role="region">
                <div class="fq-panel-inner">
                  <p>Yes, Kenyans living and working abroad may apply through eCitizen using their Kenyan National ID or Passport. The <strong>Housing Levy</strong> for diaspora applicants is calculated based on self-declared income and must be remitted voluntarily via eCitizen or a participating bank.</p>
                  <div class="fq-helpful" data-id="ans-diaspora">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="eligibility">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-existing-homeowner">
                <span class="fq-q-text">Can I apply if I already own land but not a house?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-existing-homeowner" role="region">
                <div class="fq-panel-inner">
                  <p>Owning a <strong>plot of land</strong> does not automatically disqualify you. The restriction is on <strong>existing residential property</strong>. If you own land but have no habitable structure on it, you may still qualify, subject to income verification. However, owning a house â€” regardless of its size or location â€” disqualifies you from the Social Housing category.</p>
                  <div class="fq-helpful" data-id="ans-existing-homeowner">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group eligibility -->

        <!-- ---- GROUP: APPLICATION ---- -->
        <div class="fq-group" data-group="application" id="group-application">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-file-pen" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Application Process</h3>
              <p class="fq-group-sub">Step-by-step guidance on how to register and submit your application.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-application">

            <div class="fq-item" data-cat="application" id="q-how-apply">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-how-apply">
                <span class="fq-q-text">How do I apply for an affordable housing unit?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-how-apply" role="region">
                <div class="fq-panel-inner">
                  <p>Applications are made through <strong>eCitizen</strong> at <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="fq-link">ecitizen.go.ke</a>. The process is:</p>
                  <ol>
                    <li>Log in to eCitizen with your National ID and phone number</li>
                    <li>Navigate to <strong>Ministry of Lands &amp; Housing â†’ Affordable Housing â†’ Apply for Unit</strong></li>
                    <li>Complete the household income declaration form</li>
                    <li>Upload required documents (ID, KRA PIN, payslip or bank statement)</li>
                    <li>Select your preferred constituency in Trans-Nzoia</li>
                    <li>Submit and retain your <strong>Application Reference Number</strong></li>
                  </ol>
                  <p>You will receive an SMS and email confirmation within 24 hours. Shortlisted applicants are contacted for biometric verification.</p>
                  <div class="fq-helpful" data-id="q-how-apply">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="application">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-docs-needed">
                <span class="fq-q-text">What documents do I need to apply?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-docs-needed" role="region">
                <div class="fq-panel-inner">
                  <p>Required documents include:</p>
                  <ul>
                    <li>Copy of your <strong>National Identity Card</strong> (both sides)</li>
                    <li><strong>KRA PIN Certificate</strong></li>
                    <li>Recent <strong>payslip</strong> (last 3 months) <em>or</em> certified bank statements</li>
                    <li><strong>Employment letter</strong> from your employer (for formal sector workers)</li>
                    <li>For self-employed applicants: <strong>business permit</strong> and audited accounts or sworn affidavit of income</li>
                    <li>NCPWD certificate (PWD applicants only)</li>
                    <li>Death certificate of spouse (widowed applicants claiming priority)</li>
                  </ul>
                  <div class="fq-helpful" data-id="ans-docs-needed">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="application">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-apply-fee">
                <span class="fq-q-text">Is there a fee to submit an application?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-apply-fee" role="region">
                <div class="fq-panel-inner">
                  <p><strong>No application fee is charged.</strong> The application process through eCitizen is entirely free. Be wary of any individual or agent claiming to charge a processing fee â€” this is fraudulent. Report such cases to the AHB field office or through the <a href="contact.php" class="fq-link">Contact Us</a> page.</p>
                  <div class="fq-helpful" data-id="ans-apply-fee">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="application">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-track-application">
                <span class="fq-q-text">How do I track the status of my application?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-track-application" role="region">
                <div class="fq-panel-inner">
                  <p>You can track your application status by:</p>
                  <ul>
                    <li>Logging into <strong>eCitizen</strong> and navigating to "My Applications" under the Housing module</li>
                    <li>Calling the <strong>AHB Call Centre</strong> at 0800 723 133 (toll-free) with your reference number</li>
                    <li>Visiting the <strong>AHB Field Office</strong> in Kitale (Ardhi House, off Moi Avenue)</li>
                    <li>Checking for SMS/email updates linked to your registered phone number and email</li>
                  </ul>
                  <div class="fq-helpful" data-id="ans-track-application">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group application -->

        <!-- ---- GROUP: PAYMENTS & LEVY ---- -->
        <div class="fq-group" data-group="payments" id="group-payments">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Payments &amp; Housing Levy</h3>
              <p class="fq-group-sub">Understanding the Housing Levy, monthly contributions and refund policies.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-payments">

            <div class="fq-item" data-cat="payments" id="q-levy-amount">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-levy-amount">
                <span class="fq-q-text">What is the Affordable Housing Levy and how much is it?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-levy-amount" role="region">
                <div class="fq-panel-inner">
                  <p>The <strong>Affordable Housing Levy</strong> is a statutory contribution introduced under the <em>Affordable Housing Act, 2024</em>. Every employed person contributes:</p>
                  <ul>
                    <li><strong>Employee:</strong> 1.5% of gross monthly salary</li>
                    <li><strong>Employer:</strong> A matching 1.5% of the employee's gross monthly salary</li>
                  </ul>
                  <p>For a gross salary of <strong>KSh 50,000</strong>, the employee contribution is <strong>KSh 750/month</strong>, with the employer contributing an equal amount. Contributions are remitted to the Kenya Revenue Authority (KRA) alongside PAYE.</p>
                  <p>Self-employed individuals pay a <strong>voluntary contribution</strong> of 1.5% of their declared income via eCitizen or Paybill <strong>222222</strong>.</p>
                  <div class="fq-helpful" data-id="q-levy-amount">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="payments">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-levy-refund">
                <span class="fq-q-text">Will I get a refund if I am not allocated a unit?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-levy-refund" role="region">
                <div class="fq-panel-inner">
                  <p>Yes. Under Section 28 of the Affordable Housing Act, levy contributions that do not result in unit allocation within <strong>15 years</strong> are refundable with interest. Additionally, if you choose to withdraw from the programme, you can apply for a refund at the AHB offices after a <strong>mandatory 3-year contribution period</strong>. Interest is accrued at the prevailing Treasury bond rate.</p>
                  <div class="fq-helpful" data-id="ans-levy-refund">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="payments">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-monthly-mortgage">
                <span class="fq-q-text">What are the monthly mortgage repayments after allocation?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-monthly-mortgage" role="region">
                <div class="fq-panel-inner">
                  <p>After unit allocation, beneficiaries pay a subsidised mortgage through the <strong>Kenya Mortgage Refinance Company (KMRC)</strong>. Typical monthly repayments are:</p>
                  <ul>
                    <li><strong>1-bedroom unit</strong> (approx. KSh 1.5M): ~KSh 7,000â€“9,000/month over 25 years at 7% p.a.</li>
                    <li><strong>2-bedroom unit</strong> (approx. KSh 2.5M): ~KSh 12,000â€“15,000/month over 25 years at 7% p.a.</li>
                    <li><strong>3-bedroom unit</strong> (approx. KSh 4.0M): ~KSh 18,000â€“22,000/month over 25 years at 7% p.a.</li>
                  </ul>
                  <p>Accumulated Housing Levy contributions are applied as a <strong>deposit</strong>, reducing the principal and therefore the monthly amount. Exact figures are confirmed at the point of allocation.</p>
                  <div class="fq-helpful" data-id="ans-monthly-mortgage">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group payments -->

        <!-- ---- GROUP: UNIT ALLOCATION ---- -->
        <div class="fq-group" data-group="allocation" id="group-allocation">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-ballot-check" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Unit Allocation</h3>
              <p class="fq-group-sub">How units are assigned, balloting, and what happens after selection.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-allocation">

            <div class="fq-item" data-cat="allocation" id="q-allocation-process">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-allocation-process">
                <span class="fq-q-text">How are housing units allocated to applicants?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-allocation-process" role="region">
                <div class="fq-panel-inner">
                  <p>Unit allocation follows a <strong>transparent balloting process</strong> overseen by the Affordable Housing Board (AHB) and supervised by a neutral public officer. The steps are:</p>
                  <ol>
                    <li><strong>Shortlisting</strong> â€” all verified applicants within the income bracket for the specific development are shortlisted</li>
                    <li><strong>Priority sorting</strong> â€” PWD, civil servants, teachers, nurses, and widows/widowers get priority slots (reserved at least 30%)</li>
                    <li><strong>Open ballot</strong> â€” remaining units are allocated by public ballot. Results are published on eCitizen and at the AHB field offices</li>
                    <li><strong>Offer letter</strong> â€” successful applicants receive a formal offer letter via email and SMS</li>
                    <li><strong>Acceptance &amp; deposit</strong> â€” applicants must formally accept and pay the first instalment within 30 days</li>
                  </ol>
                  <div class="fq-helpful" data-id="q-allocation-process">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="allocation">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-ballot-result">
                <span class="fq-q-text">What if I am not selected in the ballot?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-ballot-result" role="region">
                <div class="fq-panel-inner">
                  <p>Unsuccessful applicants are placed on a <strong>waiting list</strong> and automatically considered for future developments without re-applying. Your position in the waiting list improves with each ballot cycle based on the length of your contribution period. You will receive notification via eCitizen when the next allocation round opens.</p>
                  <div class="fq-helpful" data-id="ans-ballot-result">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="allocation">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-unit-type-choice">
                <span class="fq-q-text">Can I choose the type of unit (1BR, 2BR, 3BR)?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-unit-type-choice" role="region">
                <div class="fq-panel-inner">
                  <p>Yes. During the application process you indicate your <strong>preferred unit type and size</strong>. However, final allocation depends on availability of your preferred type in the development assigned to your constituency. Where your first preference is unavailable, you may be offered an alternative type or held on the waiting list for the preferred type in the next phase.</p>
                  <div class="fq-helpful" data-id="ans-unit-type-choice">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group allocation -->

        <!-- ---- GROUP: CONSTRUCTION ---- -->
        <div class="fq-group" data-group="construction" id="group-construction">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-hard-hat" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Construction &amp; Timeline</h3>
              <p class="fq-group-sub">Progress updates, timelines and what to expect at each site.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-construction">

            <div class="fq-item" data-cat="construction" id="q-when-complete">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-when-complete">
                <span class="fq-q-text">When will construction be completed and units handed over?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-when-complete" role="region">
                <div class="fq-panel-inner">
                  <p>Completion timelines vary by site. Current projections are:</p>
                  <ul>
                    <li><strong>Maili Tatu Estate (Kiminini):</strong> Phase 1 â€” 400 units, targeted completion <strong>Q4 2026</strong></li>
                    <li><strong>Matunda AHP (TN West):</strong> 200 units, targeted completion <strong>Q2 2027</strong></li>
                    <li><strong>Saboti AHP:</strong> 300 units, targeted completion <strong>Q3 2027</strong></li>
                    <li><strong>Kwanza AHP:</strong> 250 units, design phase ongoing, construction start <strong>Q1 2026</strong></li>
                    <li><strong>TN East (Cherang'any):</strong> Land allocation finalised, tender advertised <strong>2026</strong></li>
                  </ul>
                  <p>All timelines are subject to contractor performance, funding releases, and NCA certification. Check the <a href="projects.php" class="fq-link">Projects page</a> for live updates.</p>
                  <div class="fq-helpful" data-id="q-when-complete">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="construction">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-quality-checks">
                <span class="fq-q-text">Who checks the quality of construction?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-quality-checks" role="region">
                <div class="fq-panel-inner">
                  <p>Construction quality is overseen by multiple independent bodies:</p>
                  <ul>
                    <li><strong>National Construction Authority (NCA)</strong> â€” quarterly structural inspections and site occupancy certification</li>
                    <li><strong>National Environment Management Authority (NEMA)</strong> â€” environmental compliance oversight</li>
                    <li><strong>AHB Project Management Unit (PMU)</strong> â€” monthly progress certification before contractor payment</li>
                    <li><strong>County Department of Physical Planning</strong> â€” building plan approvals and compliance checks</li>
                    <li><strong>Independent structural engineers</strong> â€” engaged by AHB for third-party verification</li>
                  </ul>
                  <div class="fq-helpful" data-id="ans-quality-checks">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="construction">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-visit-site">
                <span class="fq-q-text">Can I visit the construction site to see progress?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-visit-site" role="region">
                <div class="fq-panel-inner">
                  <p>General public access to active construction sites is restricted for <strong>safety reasons</strong>. However, the AHB Field Office organises <strong>quarterly open days</strong> where vetted applicants and the public can visit specific zones under supervision. These are announced on the AHB website, eCitizen, and through this platform's <a href="news.php" class="fq-link">News page</a>.</p>
                  <p>Real-time progress is also documented in the <a href="gallery.php" class="fq-link">Photo Gallery</a> and on the <a href="projects.php" class="fq-link">Projects page</a>.</p>
                  <div class="fq-helpful" data-id="ans-visit-site">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group construction -->

        <!-- ---- GROUP: LEGAL & DOCS ---- -->
        <div class="fq-group" data-group="legal" id="group-legal">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Legal &amp; Documents</h3>
              <p class="fq-group-sub">Ownership titles, tenancy agreements, and legal protections.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-legal">

            <div class="fq-item" data-cat="legal">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-title-deed">
                <span class="fq-q-text">Will I receive a title deed for my unit?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-title-deed" role="region">
                <div class="fq-panel-inner">
                  <p>Yes. Upon full payment of the purchase price, beneficiaries receive a <strong>Certificate of Title (Sectional Title)</strong> under the Land Registration Act, 2012. For mortgage holders, the title is held by the lender (KMRC or bank) as security and is released upon full loan repayment. The Sectional Title confers <strong>absolute ownership</strong> of the specific unit and a shared ownership interest in common areas.</p>
                  <div class="fq-helpful" data-id="ans-title-deed">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="legal">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-sell-unit">
                <span class="fq-q-text">Can I sell or rent out my unit?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-sell-unit" role="region">
                <div class="fq-panel-inner">
                  <p>Resale and subletting restrictions apply for the first <strong>5 years</strong> from the date of handover. During this period, you may not sell, assign, or sublet the unit without written AHB approval. After 5 years, the unit is fully yours to sell or rent at market rates. Any sale during the restricted period must be reported to AHB and is subject to a <strong>claw-back provision</strong> on the government subsidy.</p>
                  <div class="fq-helpful" data-id="ans-sell-unit">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group legal -->

        <!-- ---- GROUP: BENEFICIARY RIGHTS ---- -->
        <div class="fq-group" data-group="rights" id="group-rights">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Beneficiary Rights</h3>
              <p class="fq-group-sub">Your rights as an AHP applicant and how to report malpractice.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-rights">

            <div class="fq-item" data-cat="rights">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-rights-list">
                <span class="fq-q-text">What rights do I have as an AHP applicant?</span>
                <span class="fq-trigger-icon" aria-hidden="true"></i><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-rights-list" role="region">
                <div class="fq-panel-inner">
                  <p>As an AHP applicant you have the right to:</p>
                  <ul>
                    <li>A <strong>free, fair and transparent</strong> application and balloting process</li>
                    <li>Access and inspect the <strong>levy statement</strong> showing your cumulative contributions at any time via eCitizen</li>
                    <li>Receive a <strong>formal offer letter</strong> and sale agreement before making any payment</li>
                    <li>Lodge a <strong>complaint or appeal</strong> against any allocation decision within 30 days of notification</li>
                    <li>A <strong>refund</strong> of levy contributions with interest as provided under the Act</li>
                    <li>Not be discriminated against on the basis of <strong>ethnicity, religion, gender or disability</strong></li>
                  </ul>
                  <div class="fq-helpful" data-id="ans-rights-list">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="rights">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-report-fraud">
                <span class="fq-q-text">How do I report fraud or corruption related to the programme?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-report-fraud" role="region">
                <div class="fq-panel-inner">
                  <p>Report any suspected fraud, bribery or corruption through:</p>
                  <ul>
                    <li><strong>Ethics and Anti-Corruption Commission (EACC):</strong> <a href="tel:0800720650" class="fq-link">0800 720 650</a> (toll-free) or <a href="https://eacc.go.ke" target="_blank" rel="noopener noreferrer" class="fq-link">eacc.go.ke</a></li>
                    <li><strong>AHB Tip-off Line:</strong> <a href="tel:0800723133" class="fq-link">0800 723 133</a></li>
                    <li><strong>County Ombudsman:</strong> Trans-Nzoia County offices, Kitale</li>
                    <li>Through this platform's <a href="contact.php" class="fq-link">Contact page</a> (all submissions are confidential)</li>
                  </ul>
                  <div class="fq-helpful" data-id="ans-report-fraud">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group rights -->

        <!-- ---- GROUP: CONTACT ---- -->
        <div class="fq-group" data-group="contact" id="group-contact">
          <div class="fq-group-header fade-up">
            <div class="fq-group-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
            <div>
              <h3 class="fq-group-title">Contact &amp; Support</h3>
              <p class="fq-group-sub">How to reach the AHB Field Office and Trans-Nzoia County housing desk.</p>
            </div>
          </div>
          <div class="fq-accordion fade-up" id="accordion-contact">

            <div class="fq-item" data-cat="contact">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-field-office">
                <span class="fq-q-text">Where is the AHB Field Office in Trans-Nzoia?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-field-office" role="region">
                <div class="fq-panel-inner">
                  <p>The AHB Field Office for Trans-Nzoia County is located at:</p>
                  <address class="fq-address">
                    <strong>Ardhi House, Moi Avenue</strong><br>
                    Near the County Commissioner's Premises<br>
                    Kitale, Trans-Nzoia County<br><br>
                    <strong>Office hours:</strong> Mondayâ€“Friday, 8:00amâ€“5:00pm<br>
                    <strong>Phone:</strong> <a href="tel:+254530000000" class="fq-link">+254 53 000 0000</a><br>
                    <strong>Email:</strong> <a href="mailto:housing@transnzoia.go.ke" class="fq-link">housing@transnzoia.go.ke</a>
                  </address>
                  <div class="fq-helpful" data-id="ans-field-office">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="fq-item" data-cat="contact">
              <button class="fq-trigger" aria-expanded="false" aria-controls="ans-national-helpline">
                <span class="fq-q-text">Is there a national AHP helpline?</span>
                <span class="fq-trigger-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
              </button>
              <div class="fq-panel" id="ans-national-helpline" role="region">
                <div class="fq-panel-inner">
                  <p>Yes. The <strong>Affordable Housing Board national helpline</strong> is:</p>
                  <ul>
                    <li><strong>Toll-free:</strong> <a href="tel:0800723133" class="fq-link">0800 723 133</a> (available Monâ€“Fri, 8amâ€“6pm)</li>
                    <li><strong>WhatsApp:</strong> <a href="https://wa.me/254700000000" target="_blank" rel="noopener noreferrer" class="fq-link">0700 000 000</a></li>
                    <li><strong>Email:</strong> <a href="mailto:info@affordablehousing.go.ke" class="fq-link">info@affordablehousing.go.ke</a></li>
                    <li><strong>Website:</strong> <a href="https://affordablehousing.go.ke" target="_blank" rel="noopener noreferrer" class="fq-link">affordablehousing.go.ke</a></li>
                  </ul>
                  <div class="fq-helpful" data-id="ans-national-helpline">
                    <span>Was this helpful?</span>
                    <button class="fq-helpful-btn" data-vote="yes" aria-label="Yes, this was helpful"><i class="fa-regular fa-thumbs-up"></i></button>
                    <button class="fq-helpful-btn" data-vote="no" aria-label="No, this was not helpful"><i class="fa-regular fa-thumbs-down"></i></button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div><!-- /group contact -->

      </div>
    </section>

    <!-- ============================================================
         S4: STILL HAVE QUESTIONS â€” CTA
    ============================================================ -->
    <section class="fq-cta fade-up" aria-labelledby="fq-cta-heading">
      <div class="container">
        <div class="fq-cta-wrap">
          <div class="fq-cta-left">
            <div class="fq-cta-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></div>
            <div class="fq-cta-body">
              <h2 class="fq-cta-title" id="fq-cta-heading">Still Have Questions?</h2>
              <p class="fq-cta-sub">Our county housing team is available Monday to Friday, 8amâ€“5pm. Reach us by phone, email or in person at the Kitale field office.</p>
              <div class="fq-cta-contacts">
                <a href="tel:+254530000000" class="fq-cta-contact-item">
                  <i class="fa-solid fa-phone" aria-hidden="true"></i>
                  <span>+254 53 000 0000</span>
                </a>
                <a href="mailto:housing@transnzoia.go.ke" class="fq-cta-contact-item">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                  <span>housing@transnzoia.go.ke</span>
                </a>
              </div>
              <a href="contact.php" class="fq-cta-btn">
                <i class="fa-solid fa-message" aria-hidden="true"></i> Send Us a Message
              </a>
            </div>
          </div>
          <div class="fq-cta-divider" aria-hidden="true"></div>
          <div class="fq-cta-right">
            <div class="fq-cta-ecitizen-body">
              <div class="fq-cta-ecitizen-badge">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
              </div>
              <h3 class="fq-cta-ecitizen-title">Ready to Apply?</h3>
              <p class="fq-cta-ecitizen-sub">Applications are processed entirely through the Government's eCitizen portal â€” free, secure and available 24/7.</p>
              <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="fq-ecitizen-btn">
                Apply via eCitizen <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
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