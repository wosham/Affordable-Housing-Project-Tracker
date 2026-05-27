<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Server Error | Trans-Nzoia AHP Tracker</title>
  <link rel="icon" type="image/png" href="uploads/logos/afforadablehousinglogo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="assets/css/pages/500.css">
</head>
<body>

  <!-- Stripped Header -->
  <header class="err-header" role="banner">
    <a href="index.php" class="err-logo" aria-label="Trans-Nzoia AHP Tracker â€” Home">
      <img src="uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia AHP" onerror="this.style.display='none'">
      <div class="err-logo-text">
        AHP Tracker
        <small>Trans-Nzoia County</small>
      </div>
    </a>
    <a href="index.php" class="err-home-btn">
      <i class="fa-solid fa-house-chimney" aria-hidden="true"></i> Back to Home
    </a>
  </header>

  <!-- Main Content -->
  <main class="err-main" id="main-content">
    <div class="err-card">

      <!-- Error Code -->
      <span class="err-code" aria-label="Error 500">500</span>

      <!-- Icon -->
      <div class="err-icon" aria-hidden="true">
        <i class="fa-solid fa-server"></i>
      </div>

      <!-- Heading + Sub -->
      <h1 class="err-heading">Something Went Wrong</h1>
      <p class="err-sub">Our server encountered an unexpected error. This has been logged and our ICT team has been notified. Please try again in a few moments.</p>

      <!-- Action Buttons -->
      <div class="err-actions">
        <button class="err-btn err-btn--primary" id="retryBtn" type="button">
          <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
          Reload Page <span class="err-retry-count">(<span id="retryCount">30</span>s)</span>
        </button>
        <a href="index.php" class="err-btn err-btn--outline">
          <i class="fa-solid fa-house-chimney" aria-hidden="true"></i>
          Go to Homepage
        </a>
        <button class="err-btn err-btn--outline" id="copyErrBtn" type="button">
          <i class="fa-regular fa-copy" aria-hidden="true"></i>
          Copy Details
        </button>
      </div>

      <!-- Technical Details -->
      <div class="err-tech-details" role="region" aria-label="Technical error details">
        <button class="err-tech-toggle" id="techToggle" type="button" aria-expanded="false" aria-controls="techBody">
          <span><i class="fa-solid fa-terminal" aria-hidden="true"></i> Technical Details</span>
          <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="err-tech-body" id="techBody" aria-hidden="true">
          <div class="err-tech-row">
            <span class="err-tech-label">Status</span>
            <span class="err-tech-value" id="errStatus">500 Internal Server Error</span>
          </div>
          <div class="err-tech-row">
            <span class="err-tech-label">Timestamp</span>
            <span class="err-tech-value" id="errTimestamp">â€”</span>
          </div>
          <div class="err-tech-row">
            <span class="err-tech-label">URL</span>
            <span class="err-tech-value" id="errUrl">â€”</span>
          </div>
          <div class="err-tech-row">
            <span class="err-tech-label">Browser</span>
            <span class="err-tech-value" id="errBrowser">â€”</span>
          </div>
        </div>
      </div>

      <hr class="err-divider">

      <p class="err-sub" style="font-size:.82rem;margin-bottom:.75rem;">If this problem persists, please contact our ICT support team directly:</p>
      <a href="mailto:ict@transnzoia.go.ke?subject=500%20Server%20Error%20Report" class="err-report">
        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
        ict@transnzoia.go.ke
      </a>

    </div>
  </main>

  <script src="assets/js/pages/500.js"></script>
</body>
</html>
