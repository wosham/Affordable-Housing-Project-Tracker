<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Scheduled Maintenance | Trans-Nzoia AHP Tracker</title>
  <link rel="icon" type="image/png" href="uploads/logos/afforadablehousinglogo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="maintenance.css">
</head>
<body>

  <div class="maint-container" role="main" id="main-content">

    <!-- Logo -->
    <a href="index.php" class="maint-logo" aria-label="Trans-Nzoia AHP Tracker â€” Home">
      <img src="uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia AHP" onerror="this.style.display='none'">
      <div class="maint-logo-text">
        AHP Tracker
        <small>Trans-Nzoia County</small>
      </div>
    </a>

    <!-- Icon -->
    <div class="maint-icon-wrap" aria-hidden="true">
      <i class="fa-solid fa-screwdriver-wrench"></i>
    </div>

    <!-- Live badge -->
    <div class="maint-badge" role="status" aria-live="polite">
      <span class="maint-badge-dot" aria-hidden="true"></span>
      Maintenance in Progress
    </div>

    <!-- Heading + Subtext -->
    <h1 class="maint-heading">We'll Be Right Back</h1>
    <p class="maint-sub">We're performing essential upgrades to improve your experience. The Trans-Nzoia AHP Tracker will be back online shortly. We apologise for the inconvenience.</p>

    <!-- Countdown Timer -->
    <div class="maint-countdown" aria-label="Time remaining" role="timer">
      <div class="maint-count-unit">
        <div class="maint-count-box" id="countH" aria-label="Hours">00</div>
        <span class="maint-count-label">Hours</span>
      </div>
      <span class="maint-count-sep" aria-hidden="true">:</span>
      <div class="maint-count-unit">
        <div class="maint-count-box" id="countM" aria-label="Minutes">00</div>
        <span class="maint-count-label">Minutes</span>
      </div>
      <span class="maint-count-sep" aria-hidden="true">:</span>
      <div class="maint-count-unit">
        <div class="maint-count-box" id="countS" aria-label="Seconds">00</div>
        <span class="maint-count-label">Seconds</span>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="maint-progress-wrap" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="Maintenance progress">
      <div class="maint-progress-meta">
        <span>Maintenance progress</span>
        <span><span id="maintPct">0%</span> complete</span>
      </div>
      <div class="maint-progress-bar-bg">
        <div class="maint-progress-bar" id="maintProgress"></div>
      </div>
    </div>

    <!-- Status message -->
    <p class="maint-status-msg" id="maintStatus">
      Last checked at <span id="lastUpdated">â€”</span> EAT
    </p>

    <!-- Contact -->
    <div class="maint-contact" aria-label="Contact information">
      <a href="tel:+254530000000">
        <i class="fa-solid fa-phone" aria-hidden="true"></i>
        +254 53 000 0000
      </a>
      <a href="mailto:housing@transnzoia.go.ke">
        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
        housing@transnzoia.go.ke
      </a>
    </div>

    <!-- Social -->
    <div class="maint-social" aria-label="Follow us for updates">
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

    <!-- Footer line -->
    <p class="maint-footer-line">
      &copy; 2026 <span>Trans-Nzoia County Government</span> &mdash; Dept. of Land, Housing &amp; Physical Planning
    </p>

  </div>

  <script src="maintenance.js"></script>
</body>
</html>
