<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Access denied — Trans-Nzoia AHP Tracker">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Access Denied | Trans-Nzoia AHP Tracker</title>
  <link rel="icon" type="image/png" href="../../uploads/logos/afforadablehousinglogo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="../../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/unauthorised.css">
</head>
<body>
  <div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>
  <div class="cursor-ring" id="cursorRing" aria-hidden="true"></div>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <main class="unauth-page" id="main-content" role="main">

    <!-- Decorative background -->
    <div class="unauth-bg" aria-hidden="true">
      <div class="unauth-bg-grid"></div>
      <div class="unauth-bg-glow"></div>
    </div>

    <!-- Top bar -->
    <div class="unauth-topbar">
      <a href="../../index.php" class="unauth-logo" aria-label="Trans-Nzoia AHP — Home">
        <img src="../../uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia AHP" onerror="this.style.display='none'" height="36" width="36">
        <span>AHP Tracker</span>
      </a>
    </div>

    <div class="unauth-inner">

      <!-- Reason-specific banners (JS shows the relevant one) -->
      <div class="unauth-reason-banner unauth-reason--timeout" id="reasonTimeout" hidden>
        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
        <span>Your session has expired. Please sign in again to continue.</span>
      </div>
      <div class="unauth-reason-banner unauth-reason--role" id="reasonRole" hidden>
        <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
        <span>Your account does not have the required permissions for this area.</span>
      </div>
      <div class="unauth-reason-banner unauth-reason--noauth" id="reasonNoAuth" hidden>
        <i class="fa-solid fa-lock" aria-hidden="true"></i>
        <span>You need to be signed in to access this page.</span>
      </div>

      <!-- 403 Code -->
      <div class="unauth-code" aria-hidden="true">403</div>

      <!-- Content -->
      <h1 class="unauth-title">Access Denied</h1>
      <p class="unauth-sub">
        This area is restricted to authorised Trans-Nzoia County staff with valid credentials.
        If you believe this is an error, please contact your system administrator.
      </p>

      <!-- Action buttons -->
      <div class="unauth-actions" role="group" aria-label="Navigation options">
        <a href="../login.php" class="unauth-btn unauth-btn--primary" id="unauthGoLogin">
          <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
          Sign In
        </a>
        <a href="../../index.php" class="unauth-btn unauth-btn--outline" id="unauthGoHome">
          <i class="fa-solid fa-house" aria-hidden="true"></i>
          Go to Home
        </a>
        <a href="../../contact.php" class="unauth-btn unauth-btn--ghost">
          <i class="fa-solid fa-headset" aria-hidden="true"></i>
          Contact Admin
        </a>
      </div>

      <!-- Redirect countdown -->
      <div class="unauth-countdown-wrap" id="unauthCountdownWrap">
        <div class="unauth-countdown-ring" aria-hidden="true">
          <svg viewBox="0 0 44 44" class="unauth-countdown-svg">
            <circle cx="22" cy="22" r="18" class="unauth-countdown-track"/>
            <circle cx="22" cy="22" r="18" class="unauth-countdown-progress" id="unauthCountdownCircle"/>
          </svg>
          <span class="unauth-countdown-num" id="unauthCountdownNum">20</span>
        </div>
        <div class="unauth-countdown-text">
          <p>Redirecting to home page in <strong id="unauthCountdownSec">20</strong> seconds</p>
          <button type="button" class="unauth-stay-btn" id="unauthStayBtn">
            <i class="fa-solid fa-hand" aria-hidden="true"></i> Stay on this page
          </button>
        </div>
      </div>

    </div><!-- /unauth-inner -->

    <footer class="unauth-footer">
      <a href="../../index.php">Home</a>
      <a href="../../projects.php">Projects</a>
      <a href="../../contact.php">Contact</a>
      <span>&copy; 2026 Trans-Nzoia County Government</span>
    </footer>

  </main>

  <script src="../../assets/js/global.js"></script>
  <script src="../assets/js/unauthorised.js"></script>
</body>
</html>
