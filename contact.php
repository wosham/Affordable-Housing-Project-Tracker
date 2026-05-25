<?php
$basePath = '';
$activePage = 'contact';
$pageTitle = 'Contact Us | Trans-Nzoia AHP Tracker';
$pageDescription = 'Contact the Trans-Nzoia Affordable Housing Programme team â€” phone, email, office location and online enquiry form.';
$pageKeywords = 'Trans-Nzoia affordable housing contact, AHP Kenya office, Kitale housing desk, housing enquiry Kenya';
$pageAuthor = 'Trans-Nzoia County Government â€” Department of Land, Housing & Physical Planning';
$pageRobots = 'index, follow';
$themeColor = '#163300';
$canonicalUrl = 'https://housing.transnzoia.go.ke/contact.php';
$pageStyles = [
  'assets/css/global.css',
  'assets/css/pages/contact.css'
];
$pageScripts = [
  'assets/js/global.js',
  'assets/js/data.js',
  'assets/js/pages/contact.js'
];
$headMeta = [
  '<meta name="geo.region" content="KE-36">',
  '<meta name="geo.placename" content="Trans-Nzoia County, Kenya">',
  '<meta property="og:title" content="Contact Us | Trans-Nzoia AHP Tracker">',
  '<meta property="og:description" content="Reach the Trans-Nzoia Affordable Housing Programme team by phone, email or online form.">',
  '<meta property="og:type" content="website">',
  '<meta property="og:url" content="https://housing.transnzoia.go.ke/contact.php">',
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
    <section class="ct-hero" aria-label="Contact overview">
      <div class="ct-hero-bg" aria-hidden="true">
        <div class="ct-hero-overlay"></div>
        <div class="ct-hero-dots"></div>
      </div>
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.php" class="breadcrumb-link">Home</a>
          <span class="breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
          <span class="breadcrumb-current" aria-current="page">Contact Us</span>
        </nav>
        <div class="ct-hero-body">
          <div class="ct-hero-eyebrow">
            <i class="fa-solid fa-headset" aria-hidden="true"></i> Get in Touch
          </div>
          <h1 class="ct-hero-title">We're Here<br>to Help.</h1>
          <p class="ct-hero-sub">Reach our county housing team for enquiries about the Affordable Housing Programme â€” applications, site progress, allocation status, or any other question.</p>
          <div class="ct-hero-stats" role="region" aria-label="Response info">
            <div class="ct-stat-item">
              <span class="ct-stat-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></span>
              <div>
                <span class="ct-stat-val">24 hrs</span>
                <span class="ct-stat-lbl">Response Time</span>
              </div>
            </div>
            <div class="ct-stat-div" aria-hidden="true"></div>
            <div class="ct-stat-item">
              <span class="ct-stat-icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span>
              <div>
                <span class="ct-stat-val">Mon â€“ Fri</span>
                <span class="ct-stat-lbl">8am â€“ 5pm EAT</span>
              </div>
            </div>
            <div class="ct-stat-div" aria-hidden="true"></div>
            <div class="ct-stat-item">
              <span class="ct-stat-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
              <div>
                <span class="ct-stat-val">4</span>
                <span class="ct-stat-lbl">Departments</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S2: QUICK CONTACT CARDS
    ============================================================ -->
    <section class="ct-cards-strip fade-up" aria-label="Quick contact options">
      <div class="container">
        <div class="ct-cards-grid">
          <a href="tel:+254530000000" class="ct-card" aria-label="Call us">
            <div class="ct-card-icon ct-card-icon--green">
              <i class="fa-solid fa-phone" aria-hidden="true"></i>
            </div>
            <div class="ct-card-body">
              <span class="ct-card-label">Call Us</span>
              <span class="ct-card-val">+254 53 000 0000</span>
              <span class="ct-card-hint">Monâ€“Fri, 8amâ€“5pm</span>
            </div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
          <a href="mailto:housing@transnzoia.go.ke" class="ct-card" aria-label="Email us">
            <div class="ct-card-icon ct-card-icon--blue">
              <i class="fa-solid fa-envelope" aria-hidden="true"></i>
            </div>
            <div class="ct-card-body">
              <span class="ct-card-label">Email Us</span>
              <span class="ct-card-val">housing@transnzoia.go.ke</span>
              <span class="ct-card-hint">Reply within 24 hours</span>
            </div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
          <a href="https://wa.me/254700000000" target="_blank" rel="noopener noreferrer" class="ct-card" aria-label="WhatsApp us">
            <div class="ct-card-icon ct-card-icon--lime">
              <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
            </div>
            <div class="ct-card-body">
              <span class="ct-card-label">WhatsApp</span>
              <span class="ct-card-val">0700 000 000</span>
              <span class="ct-card-hint">Quick questions welcome</span>
            </div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
          <a href="https://maps.google.com/?q=Kitale+Trans-Nzoia" target="_blank" rel="noopener noreferrer" class="ct-card" aria-label="Find our office on map">
            <div class="ct-card-icon ct-card-icon--amber">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            </div>
            <div class="ct-card-body">
              <span class="ct-card-label">Visit Us</span>
              <span class="ct-card-val">Ardhi House, Kitale</span>
              <span class="ct-card-hint">Open in Google Maps</span>
            </div>
            <div class="ct-card-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
          </a>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S3: FORM + OFFICE INFO
    ============================================================ -->
    <section class="ct-main" aria-labelledby="ct-main-heading">
      <div class="container">
        <h2 class="sr-only" id="ct-main-heading">Send a message and office information</h2>
        <div class="ct-main-grid">

          <!-- LEFT: FORM -->
          <div class="ct-form-wrap fade-up">
            <div class="ct-form-header">
              <h2 class="ct-form-title">Send Us a Message</h2>
              <p class="ct-form-sub">Fill in the form below and a member of our team will get back to you within one business day.</p>
            </div>

            <form class="ct-form" id="contactForm" novalidate aria-label="Contact form">
              <!-- Honeypot anti-spam -->
              <input type="text" name="_gotcha" class="ct-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

              <div class="ct-form-row ct-form-row--2col">
                <div class="ct-field-group">
                  <label class="ct-label" for="ctName">Full Name <span class="ct-required" aria-hidden="true">*</span></label>
                  <div class="ct-input-wrap">
                    <i class="fa-solid fa-user ct-input-icon" aria-hidden="true"></i>
                    <input type="text" id="ctName" name="name" class="ct-input" placeholder="e.g. John Wafula" autocomplete="name" required aria-required="true">
                  </div>
                  <span class="ct-error" id="ctNameErr" role="alert" aria-live="polite"></span>
                </div>
                <div class="ct-field-group">
                  <label class="ct-label" for="ctPhone">Phone Number</label>
                  <div class="ct-input-wrap">
                    <i class="fa-solid fa-phone ct-input-icon" aria-hidden="true"></i>
                    <input type="tel" id="ctPhone" name="phone" class="ct-input" placeholder="07XX XXX XXX" autocomplete="tel">
                  </div>
                </div>
              </div>

              <div class="ct-field-group">
                <label class="ct-label" for="ctEmail">Email Address <span class="ct-required" aria-hidden="true">*</span></label>
                <div class="ct-input-wrap">
                  <i class="fa-solid fa-envelope ct-input-icon" aria-hidden="true"></i>
                  <input type="email" id="ctEmail" name="email" class="ct-input" placeholder="you@example.com" autocomplete="email" required aria-required="true">
                </div>
                <span class="ct-error" id="ctEmailErr" role="alert" aria-live="polite"></span>
              </div>

              <div class="ct-field-group">
                <label class="ct-label" for="ctSubject">Subject / Department <span class="ct-required" aria-hidden="true">*</span></label>
                <div class="ct-select-wrap">
                  <i class="fa-solid fa-tag ct-input-icon" aria-hidden="true"></i>
                  <select id="ctSubject" name="subject" class="ct-select" required aria-required="true">
                    <option value="" disabled selected>Select a subjectâ€¦</option>
                    <option value="general">General Enquiry</option>
                    <option value="application">Application Status</option>
                    <option value="allocation">Unit Allocation</option>
                    <option value="complaint">Complaint / Concern</option>
                    <option value="media">Media &amp; Press</option>
                    <option value="other">Other</option>
                  </select>
                  <i class="fa-solid fa-chevron-down ct-select-arrow" aria-hidden="true"></i>
                </div>
                <span class="ct-error" id="ctSubjectErr" role="alert" aria-live="polite"></span>
              </div>

              <div class="ct-field-group">
                <label class="ct-label" for="ctMessage">Your Message <span class="ct-required" aria-hidden="true">*</span></label>
                <div class="ct-textarea-wrap">
                  <textarea id="ctMessage" name="message" class="ct-textarea" rows="5" placeholder="Please describe your enquiry in detailâ€¦" required aria-required="true" maxlength="1000"></textarea>
                </div>
                <div class="ct-char-counter">
                  <span class="ct-error" id="ctMessageErr" role="alert" aria-live="polite"></span>
                  <span class="ct-char-count" id="ctCharCount" aria-live="polite">0 / 1000</span>
                </div>
              </div>

              <div class="ct-field-group">
                <label class="ct-label" for="ctAttachment">Attachment <span class="ct-optional">(optional)</span></label>
                <label class="ct-file-label" for="ctAttachment" id="ctFileLabel">
                  <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                  <span id="ctFileName">Choose file (PDF, JPG, PNG â€” max 5MB)</span>
                </label>
                <input type="file" id="ctAttachment" name="attachment" class="ct-file-input" accept=".pdf,.jpg,.jpeg,.png" aria-label="Attach a file">
                <span class="ct-error" id="ctFileErr" role="alert" aria-live="polite"></span>
              </div>

              <div class="ct-form-footer">
                <p class="ct-privacy-note">
                  <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
                  Your information is protected under our <a href="legal/privacy.php" class="ct-link">Privacy Policy</a> and will not be shared with third parties.
                </p>
                <button type="submit" class="ct-submit-btn" id="ctSubmitBtn">
                  <span class="ct-submit-text">Send Message</span>
                  <span class="ct-submit-spinner" aria-hidden="true"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                  <i class="fa-solid fa-paper-plane ct-submit-icon" aria-hidden="true"></i>
                </button>
              </div>

              <!-- Success state -->
              <div class="ct-form-success" id="ctFormSuccess" hidden aria-live="polite" role="status">
                <div class="ct-success-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
                <h3 class="ct-success-title">Message Sent!</h3>
                <p class="ct-success-sub">Thank you, <strong id="ctSuccessName"></strong>. We've received your message and will respond to <strong id="ctSuccessEmail"></strong> within one business day.</p>
                <button class="ct-success-reset" id="ctSuccessReset">Send another message</button>
              </div>

              <!-- Error state -->
              <div class="ct-form-error-state" id="ctFormErrorState" hidden aria-live="polite" role="alert">
                <div class="ct-error-icon"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i></div>
                <p class="ct-error-msg">Something went wrong. Please try again or email us directly at <a href="mailto:housing@transnzoia.go.ke" class="ct-link">housing@transnzoia.go.ke</a>.</p>
              </div>
            </form>
          </div>

          <!-- RIGHT: OFFICE INFO -->
          <div class="ct-office-wrap fade-up">

            <div class="ct-office-card">
              <div class="ct-office-card-header">
                <i class="fa-solid fa-building" aria-hidden="true"></i>
                <h3>AHP Field Office â€” Trans-Nzoia</h3>
              </div>
              <address class="ct-office-address">
                <div class="ct-office-row">
                  <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                  <span>Ardhi House, Moi Avenue<br>Kitale, Trans-Nzoia County<br>P.O. Box 123-30200</span>
                </div>
                <div class="ct-office-row">
                  <i class="fa-solid fa-phone" aria-hidden="true"></i>
                  <span><a href="tel:+254530000000" class="ct-link">+254 53 000 0000</a></span>
                </div>
                <div class="ct-office-row">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                  <span><a href="mailto:housing@transnzoia.go.ke" class="ct-link">housing@transnzoia.go.ke</a></span>
                </div>
              </address>

              <div class="ct-hours-table" role="table" aria-label="Office hours">
                <div class="ct-hours-row ct-hours-head" role="row">
                  <span role="columnheader">Day</span>
                  <span role="columnheader">Hours</span>
                  <span role="columnheader">Status</span>
                </div>
                <div class="ct-hours-row" role="row">
                  <span role="cell">Monday â€“ Friday</span>
                  <span role="cell">8:00am â€“ 5:00pm</span>
                  <span role="cell" class="ct-hours-status ct-hours-open" id="ctWeekdayStatus">Open</span>
                </div>
                <div class="ct-hours-row" role="row">
                  <span role="cell">Saturday</span>
                  <span role="cell">9:00am â€“ 1:00pm</span>
                  <span role="cell" class="ct-hours-status" id="ctSaturdayStatus">â€”</span>
                </div>
                <div class="ct-hours-row" role="row">
                  <span role="cell">Sunday &amp; Public Holidays</span>
                  <span role="cell">Closed</span>
                  <span role="cell" class="ct-hours-status ct-hours-closed">Closed</span>
                </div>
              </div>
            </div>

            <!-- Map placeholder -->
            <div class="ct-map-wrap">
              <div class="ct-map-placeholder" aria-label="Office location map">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3986.8!2d35.0062!3d1.0154!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sKitale%2C+Trans-Nzoia!5e0!3m2!1sen!2ske!4v1"
                  width="100%"
                  height="100%"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Trans-Nzoia AHP Field Office location on Google Maps"
                  aria-label="Google Maps showing Kitale, Trans-Nzoia County">
                </iframe>
              </div>
              <a href="https://maps.google.com/?q=Kitale+Trans-Nzoia+County" target="_blank" rel="noopener noreferrer" class="ct-map-link">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open in Google Maps
              </a>
            </div>

            <!-- AHB National helpline -->
            <div class="ct-ahb-card">
              <div class="ct-ahb-icon"><i class="fa-solid fa-phone-volume" aria-hidden="true"></i></div>
              <div>
                <p class="ct-ahb-title">National AHB Helpline</p>
                <a href="tel:0800723133" class="ct-ahb-number">0800 723 133</a>
                <p class="ct-ahb-note">Toll-free &middot; Monâ€“Fri 8amâ€“6pm</p>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         S4: DEPARTMENTS DIRECTORY
    ============================================================ -->
    <section class="ct-depts fade-up" aria-labelledby="ct-depts-heading">
      <div class="container">
        <div class="ct-section-header">
          <div class="ct-section-eyebrow">
            <i class="fa-solid fa-sitemap" aria-hidden="true"></i> Departments
          </div>
          <h2 class="ct-section-title" id="ct-depts-heading">Who to Contact</h2>
          <p class="ct-section-sub">Reach the right team directly for faster assistance.</p>
        </div>
        <div class="ct-depts-grid">

          <div class="ct-dept-card">
            <div class="ct-dept-avatar ct-dept-avatar--a" aria-hidden="true">
              <i class="fa-solid fa-hard-hat"></i>
            </div>
            <div class="ct-dept-body">
              <h3 class="ct-dept-name">Field Operations</h3>
              <p class="ct-dept-role">Construction progress, site visits, contractor oversight</p>
              <div class="ct-dept-contacts">
                <a href="mailto:fieldops@transnzoia.go.ke" class="ct-dept-contact">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i> fieldops@transnzoia.go.ke
                </a>
                <a href="tel:+254530000001" class="ct-dept-contact">
                  <i class="fa-solid fa-phone" aria-hidden="true"></i> +254 53 000 0001
                </a>
              </div>
            </div>
          </div>

          <div class="ct-dept-card">
            <div class="ct-dept-avatar ct-dept-avatar--b" aria-hidden="true">
              <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div class="ct-dept-body">
              <h3 class="ct-dept-name">Legal &amp; Allocation</h3>
              <p class="ct-dept-role">Applications, balloting, title deeds, legal enquiries</p>
              <div class="ct-dept-contacts">
                <a href="mailto:legal@transnzoia.go.ke" class="ct-dept-contact">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i> legal@transnzoia.go.ke
                </a>
                <a href="tel:+254530000002" class="ct-dept-contact">
                  <i class="fa-solid fa-phone" aria-hidden="true"></i> +254 53 000 0002
                </a>
              </div>
            </div>
          </div>

          <div class="ct-dept-card">
            <div class="ct-dept-avatar ct-dept-avatar--c" aria-hidden="true">
              <i class="fa-solid fa-coins"></i>
            </div>
            <div class="ct-dept-body">
              <h3 class="ct-dept-name">Finance &amp; Levy</h3>
              <p class="ct-dept-role">Housing Levy, mortgage, refunds, payment queries</p>
              <div class="ct-dept-contacts">
                <a href="mailto:finance@transnzoia.go.ke" class="ct-dept-contact">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i> finance@transnzoia.go.ke
                </a>
                <a href="tel:+254530000003" class="ct-dept-contact">
                  <i class="fa-solid fa-phone" aria-hidden="true"></i> +254 53 000 0003
                </a>
              </div>
            </div>
          </div>

          <div class="ct-dept-card">
            <div class="ct-dept-avatar ct-dept-avatar--d" aria-hidden="true">
              <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div class="ct-dept-body">
              <h3 class="ct-dept-name">Communications</h3>
              <p class="ct-dept-role">Media, press, events, public announcements</p>
              <div class="ct-dept-contacts">
                <a href="mailto:comms@transnzoia.go.ke" class="ct-dept-contact">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i> comms@transnzoia.go.ke
                </a>
                <a href="tel:+254530000004" class="ct-dept-contact">
                  <i class="fa-solid fa-phone" aria-hidden="true"></i> +254 53 000 0004
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ============================================================
         S5: FAQ SHORTCUT BANNER
    ============================================================ -->
    <section class="ct-faq-banner fade-up" aria-label="FAQ quick links">
      <div class="container">
        <div class="ct-faq-banner-wrap">
          <div class="ct-faq-banner-left">
            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
            <div>
              <strong>Have a quick question?</strong>
              <span>Browse our Frequently Asked Questions for instant answers.</span>
            </div>
          </div>
          <div class="ct-faq-banner-pills">
            <a href="faq.php#q-how-apply" class="ct-faq-pill">How do I apply?</a>
            <a href="faq.php#q-levy-amount" class="ct-faq-pill">What is the levy?</a>
            <a href="faq.php#q-when-complete" class="ct-faq-pill">When do units complete?</a>
          </div>
          <a href="faq.php" class="ct-faq-btn">View All FAQs <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
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