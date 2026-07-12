<?php
http_response_code(503);
header_remove('X-Powered-By');
header('Retry-After: 3600');

$scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
$baseUrl = rtrim($scriptDir === '/' ? '' : $scriptDir, '/') . '/';
$asset = static fn (string $path): string => htmlspecialchars($baseUrl . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
$maintenanceEnd = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Scheduled Maintenance | Trans-Nzoia AHP Tracker</title>
  <base href="<?= $asset('') ?>">
  <link rel="icon" type="image/png" href="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= $asset('assets/css/pages/maintenance.css') ?>">
</head>
<body class="system-page system-page--maintenance" data-maintenance-end="<?= htmlspecialchars($maintenanceEnd, ENT_QUOTES, 'UTF-8') ?>">
  <a class="err-skip maint-skip" href="#main-content">Skip to main content</a>

  <main class="maint-container" role="main" id="main-content">
    <a href="<?= $asset('index.php') ?>" class="maint-logo" aria-label="Trans-Nzoia AHP Tracker - Home">
      <img src="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>" alt="Trans-Nzoia AHP" onerror="this.style.display='none'">
      <div class="maint-logo-text">
        AHP Tracker
        <small>Trans-Nzoia County</small>
      </div>
    </a>

    <div class="maint-icon-wrap" aria-hidden="true">
      <i class="fa-solid fa-screwdriver-wrench"></i>
    </div>

    <div class="maint-badge" role="status" aria-live="polite">
      <span class="maint-badge-dot" aria-hidden="true"></span>
      Maintenance in Progress
    </div>

    <h1 class="maint-heading">We will be right back</h1>
    <p class="maint-sub">The Trans-Nzoia Affordable Housing Project Tracker is undergoing scheduled maintenance. Public access will resume as soon as the update is complete.</p>

    <div class="maint-status-card" role="note">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      <div>
        <strong id="maintEtaTitle">Service update in progress</strong>
        <span id="maintEtaText">Please check again shortly. No project data has been lost.</span>
      </div>
    </div>

    <div class="maint-countdown" id="maintCountdown" aria-label="Estimated time remaining" role="timer" hidden>
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

    <div class="maint-actions">
      <button class="maint-btn maint-btn--primary" id="maintRetryBtn" type="button">
        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
        Try Again
      </button>
      <a class="maint-btn maint-btn--outline" href="mailto:housing@transnzoia.go.ke?subject=AHP%20Tracker%20maintenance">
        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
        Contact Support
      </a>
    </div>

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

    <p class="maint-footer-line">
      &copy; 2026 <span>Trans-Nzoia County Government</span> - Department of Land, Housing and Physical Planning
    </p>
  </main>

  <script src="<?= $asset('assets/js/pages/maintenance.js') ?>"></script>
</body>
</html>
