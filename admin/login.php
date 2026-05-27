<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
Guard::guest();
$csrfToken = Csrf::token('login');
$statusMessage = Session::flash('status');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Staff portal login — Trans-Nzoia Affordable Housing Programme Tracker">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Staff Login | Trans-Nzoia AHP Tracker</title>
  <link rel="icon" type="image/png" href="../uploads/logos/afforadablehousinglogo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <meta name="csrf-token" content="<?= Security::e($csrfToken) ?>">
  <meta name="csrf-token-name" content="<?= Security::e(Csrf::tokenName()) ?>">
  <meta name="csrf-form" content="login">
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
  <div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>
  <div class="cursor-ring" id="cursorRing" aria-hidden="true"></div>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <div class="auth-wrap">

    <!-- ===== LEFT: BRAND PANEL ===== -->
    <aside class="auth-brand" aria-label="Site branding">
      <div class="auth-brand-bg" aria-hidden="true">
        <div class="auth-brand-grid"></div>
        <div class="auth-brand-glow"></div>
      </div>
      <div class="auth-brand-inner">
        <div class="auth-brand-top">
          <a href="../index.php" class="auth-logo" aria-label="Trans-Nzoia AHP — Home">
            <img src="../uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia County" onerror="this.style.display='none'" width="42" height="42">
            <div class="auth-logo-text">AHP Tracker<br><small>Trans-Nzoia County</small></div>
          </a>
          <span class="auth-brand-badge">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Staff Portal
          </span>
        </div>

        <div class="auth-brand-body">
          <h1 class="auth-brand-title">Manage.<br>Track.<br>Deliver.</h1>
          <p class="auth-brand-sub">The Trans-Nzoia Affordable Housing Programme management system — secure, real-time, county-wide oversight.</p>
          <div class="auth-brand-stats" aria-label="Programme at a glance">
            <div class="auth-bs-item">
              <span class="auth-bs-num">7</span>
              <span class="auth-bs-lbl">Constituencies</span>
            </div>
            <div class="auth-bs-div" aria-hidden="true"></div>
            <div class="auth-bs-item">
              <span class="auth-bs-num">4,000+</span>
              <span class="auth-bs-lbl">Units Targeted</span>
            </div>
            <div class="auth-bs-div" aria-hidden="true"></div>
            <div class="auth-bs-item">
              <span class="auth-bs-num">Live</span>
              <span class="auth-bs-lbl">Real-time Data</span>
            </div>
          </div>
        </div>

        <div class="auth-brand-footer">
          <a href="../index.php" class="auth-back-link">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to public site
          </a>
          <span class="auth-brand-copy">&copy; 2026 Trans-Nzoia County</span>
        </div>
      </div>
    </aside>

    <!-- ===== RIGHT: FORM PANEL ===== -->
    <main class="auth-form-panel" id="main-content">
      <div class="auth-form-inner">

        <!-- Alerts -->
        <div class="auth-alert auth-alert--warn" id="authTimeoutAlert" hidden role="alert">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
          <div>
            <strong>Session expired</strong>
            <span>Your session has expired. Please sign in again to continue.</span>
          </div>
          <button class="auth-alert-close" aria-label="Dismiss alert"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="auth-alert auth-alert--error" id="authErrorAlert" hidden role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <div>
            <strong>Sign in failed</strong>
            <span id="authErrorMsg">Invalid email or password. Please try again.</span>
          </div>
          <button class="auth-alert-close" aria-label="Dismiss alert"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <?php if ($statusMessage): ?>
        <div class="auth-alert auth-alert--success" role="status">
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
          <div>
            <strong>Signed out</strong>
            <span><?= Security::e($statusMessage) ?></span>
          </div>
          <button class="auth-alert-close" aria-label="Dismiss alert"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="auth-form-header">
          <div class="auth-form-icon" aria-hidden="true">
            <i class="fa-solid fa-lock"></i>
          </div>
          <h2 class="auth-form-title">Welcome back</h2>
          <p class="auth-form-sub">Sign in to the AHP staff portal to manage projects and data.</p>
        </div>

        <!-- Form -->
        <form class="auth-form" id="loginForm" novalidate autocomplete="on">
          <input type="text" name="_gotcha" class="auth-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

          <div class="auth-field">
            <label class="auth-label" for="loginEmail">
              Email Address <span class="auth-required" aria-hidden="true">*</span>
            </label>
            <div class="auth-input-wrap">
              <i class="fa-solid fa-envelope auth-input-icon" aria-hidden="true"></i>
              <input type="email" id="loginEmail" name="email" class="auth-input"
                     placeholder="you@transnzoia.go.ke" autocomplete="email" required aria-required="true">
            </div>
            <span class="auth-field-error" id="loginEmailErr" role="alert" aria-live="polite"></span>
          </div>

          <div class="auth-field">
            <label class="auth-label" for="loginPassword">
              Password <span class="auth-required" aria-hidden="true">*</span>
            </label>
            <div class="auth-input-wrap">
              <i class="fa-solid fa-key auth-input-icon" aria-hidden="true"></i>
              <input type="password" id="loginPassword" name="password" class="auth-input auth-input--pw"
                     placeholder="Enter your password" autocomplete="current-password" required aria-required="true">
              <button type="button" class="auth-pwd-toggle" id="loginPwdToggle"
                      aria-label="Show password" aria-pressed="false">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <span class="auth-field-error" id="loginPasswordErr" role="alert" aria-live="polite"></span>
          </div>

          <div class="auth-form-row">
            <label class="auth-check-label" for="loginRemember">
              <input type="checkbox" id="loginRemember" name="remember" class="auth-check-input">
              <span class="auth-check-box" aria-hidden="true"></span>
              <span>Remember me for 30&nbsp;days</span>
            </label>
            <a href="auth/forgot-password.php" class="auth-forgot-link">Forgot password?</a>
          </div>

          <button type="submit" class="auth-submit" id="loginSubmitBtn">
            <span class="auth-submit-text">Sign In <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            <span class="auth-submit-loading" aria-hidden="true">
              <i class="fa-solid fa-circle-notch fa-spin"></i> Signing in&hellip;
            </span>
          </button>
        </form>

        <div class="auth-divider" aria-hidden="true"><span>need help?</span></div>

        <div class="auth-help">
          <a href="../contact.php" class="auth-help-link">
            <i class="fa-solid fa-headset" aria-hidden="true"></i>
            Contact the system administrator
          </a>
          <a href="auth/unauthorised.php" class="auth-help-link auth-help-link--muted">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            Access &amp; permissions information
          </a>
        </div>

        <p class="auth-legal">
          This system is restricted to authorised Trans-Nzoia County staff.
          Unauthorised access is prohibited.
          <a href="../legal/privacy.php">Privacy Policy</a>
        </p>

      </div>
    </main>
  </div>

  <script src="../assets/js/global.js"></script>
  <script src="assets/js/login.js"></script>
</body>
</html>
