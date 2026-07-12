<?php
http_response_code(404);
header_remove('X-Powered-By');

$scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
$baseUrl = rtrim($scriptDir === '/' ? '' : $scriptDir, '/') . '/';
$asset = static fn (string $path): string => htmlspecialchars($baseUrl . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Page Not Found | Trans-Nzoia AHP Tracker</title>
  <base href="<?= $asset('') ?>">
  <link rel="icon" type="image/png" href="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= $asset('assets/css/pages/404.css') ?>">
</head>
<body class="system-page system-page--404">
  <a class="err-skip" href="#main-content">Skip to main content</a>

  <header class="err-header" role="banner">
    <a href="<?= $asset('index.php') ?>" class="err-logo" aria-label="Trans-Nzoia AHP Tracker - Home">
      <img src="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>" alt="Trans-Nzoia AHP" onerror="this.style.display='none'">
      <div class="err-logo-text">
        AHP Tracker
        <small>Trans-Nzoia County</small>
      </div>
    </a>
    <a href="<?= $asset('index.php') ?>" class="err-home-btn">
      <i class="fa-solid fa-house-chimney" aria-hidden="true"></i> Home
    </a>
  </header>

  <main class="err-main" id="main-content">
    <section class="err-card" aria-labelledby="error-title">
      <span class="err-code" aria-label="Error 404">404</span>
      <div class="err-icon" aria-hidden="true">
        <i class="fa-solid fa-map-location-dot"></i>
      </div>

      <p class="err-kicker">Page unavailable</p>
      <h1 class="err-heading" id="error-title">We could not find that page</h1>
      <p class="err-sub">The link may be outdated, the page may have moved, or the address may have been typed incorrectly.</p>

      <div class="err-actions">
        <a href="<?= $asset('index.php') ?>" class="err-btn err-btn--primary">
          <i class="fa-solid fa-house-chimney" aria-hidden="true"></i>
          Go to Homepage
        </a>
        <button class="err-btn err-btn--outline" id="goBackBtn" type="button">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          Go Back
        </button>
        <a href="<?= $asset('contact.php') ?>" class="err-btn err-btn--outline">
          <i class="fa-solid fa-envelope" aria-hidden="true"></i>
          Contact Support
        </a>
      </div>

      <hr class="err-divider">

      <p class="err-links-label">Try one of these public pages</p>
      <nav class="err-links-grid" aria-label="Suggested pages">
        <a href="<?= $asset('projects.php') ?>" class="err-link-item">
          <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
          <span>Projects</span>
        </a>
        <a href="<?= $asset('constituencies.php') ?>" class="err-link-item">
          <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
          <span>Constituencies</span>
        </a>
        <a href="<?= $asset('news.php') ?>" class="err-link-item">
          <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
          <span>News</span>
        </a>
        <a href="<?= $asset('contact.php') ?>" class="err-link-item">
          <i class="fa-solid fa-envelope" aria-hidden="true"></i>
          <span>Contact</span>
        </a>
      </nav>

      <a href="mailto:housing@transnzoia.go.ke" class="err-report" id="reportLink">
        <i class="fa-solid fa-flag" aria-hidden="true"></i>
        Report a broken link
      </a>
    </section>
  </main>

  <script src="<?= $asset('assets/js/pages/404.js') ?>"></script>
</body>
</html>
