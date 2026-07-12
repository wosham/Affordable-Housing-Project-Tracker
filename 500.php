<?php
http_response_code(500);
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
  <title>Service Error | Trans-Nzoia AHP Tracker</title>
  <base href="<?= $asset('') ?>">
  <link rel="icon" type="image/png" href="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= $asset('assets/css/pages/500.css') ?>">
</head>
<body class="system-page system-page--500">
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
      <span class="err-code" aria-label="Error 500">500</span>
      <div class="err-icon" aria-hidden="true">
        <i class="fa-solid fa-server"></i>
      </div>

      <p class="err-kicker">Temporary service issue</p>
      <h1 class="err-heading" id="error-title">We could not complete that request</h1>
      <p class="err-sub">The service is temporarily unavailable. Please try again shortly or return to the homepage.</p>

      <div class="err-actions">
        <button class="err-btn err-btn--primary" id="retryBtn" type="button">
          <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
          Try Again
        </button>
        <a href="<?= $asset('index.php') ?>" class="err-btn err-btn--outline">
          <i class="fa-solid fa-house-chimney" aria-hidden="true"></i>
          Go to Homepage
        </a>
        <a href="mailto:ict@transnzoia.go.ke?subject=AHP%20Tracker%20service%20issue" class="err-btn err-btn--outline">
          <i class="fa-solid fa-envelope" aria-hidden="true"></i>
          Contact ICT
        </a>
      </div>

      <div class="err-status-panel" role="note">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        <p>No public technical details are shown on this page. If the issue continues, contact support and include the page you were trying to open.</p>
      </div>
    </section>
  </main>

  <script src="<?= $asset('assets/js/pages/500.js') ?>"></script>
</body>
</html>
