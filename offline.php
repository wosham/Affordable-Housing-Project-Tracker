<?php
http_response_code(200);
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
  <title>Connection Unavailable | Trans-Nzoia AHP Tracker</title>
  <base href="<?= $asset('') ?>">
  <link rel="icon" type="image/png" href="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= $asset('assets/css/utilities/error-base.css') ?>">
</head>
<body class="system-page system-page--offline">
  <a class="err-skip" href="#main-content">Skip to main content</a>

  <header class="err-header" role="banner">
    <a href="<?= $asset('index.php') ?>" class="err-logo" aria-label="Trans-Nzoia AHP Tracker - Home">
      <img src="<?= $asset('uploads/logos/afforadablehousinglogo.png') ?>" alt="Trans-Nzoia AHP" onerror="this.style.display='none'">
      <div class="err-logo-text">
        AHP Tracker
        <small>Trans-Nzoia County</small>
      </div>
    </a>
    <button class="err-home-btn" id="offlineRetryBtn" type="button">
      <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Retry
    </button>
  </header>

  <main class="err-main" id="main-content">
    <section class="err-card" aria-labelledby="offline-title">
      <span class="err-code err-code--icon" aria-hidden="true"><i class="fa-solid fa-wifi"></i></span>
      <div class="err-icon" aria-hidden="true">
        <i class="fa-solid fa-plug-circle-xmark"></i>
      </div>

      <p class="err-kicker">Connection unavailable</p>
      <h1 class="err-heading" id="offline-title">You appear to be offline</h1>
      <p class="err-sub">Please check your connection and try again. Some pages may not be available until your device is back online.</p>

      <div class="err-actions">
        <button class="err-btn err-btn--primary" id="offlineRetryAction" type="button">
          <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
          Try Again
        </button>
        <a href="<?= $asset('index.php') ?>" class="err-btn err-btn--outline">
          <i class="fa-solid fa-house-chimney" aria-hidden="true"></i>
          Open Homepage
        </a>
      </div>

      <div class="err-status-panel" role="note">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <p>If this page appears while you are connected, refresh the browser or contact support for assistance.</p>
      </div>
    </section>
  </main>

  <script>
    (function () {
      function retry() { window.location.reload(); }
      var retryTop = document.getElementById('offlineRetryBtn');
      var retryMain = document.getElementById('offlineRetryAction');
      if (retryTop) retryTop.addEventListener('click', retry);
      if (retryMain) retryMain.addEventListener('click', retry);
    }());
  </script>
</body>
</html>
