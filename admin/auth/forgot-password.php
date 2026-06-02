<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::guest();
$csrfToken = Csrf::token('forgot_password');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Reset your staff portal password — Trans-Nzoia AHP Tracker">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Forgot Password | Trans-Nzoia AHP Tracker</title>
  <link rel="icon" type="image/png" href="../../uploads/logos/afforadablehousinglogo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <meta name="csrf-token" content="<?= Security::e($csrfToken) ?>">
  <meta name="csrf-token-name" content="<?= Security::e(Csrf::tokenName()) ?>">
  <meta name="csrf-form" content="forgot_password">
  <link rel="stylesheet" href="../../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/forgot-password.css">
</head>
<body>
  <div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>
  <div class="cursor-ring" id="cursorRing" aria-hidden="true"></div>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <main class="auth-page" id="main-content">

    <!-- Decorative background -->
    <div class="auth-page-bg" aria-hidden="true">
      <div class="auth-page-grid"></div>
      <div class="auth-page-glow"></div>
    </div>

    <!-- Back link -->
    <a href="../login.php" class="auth-page-back">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Login
    </a>

    <!-- Card -->
    <div class="auth-card" role="region" aria-label="Password reset">

      <!-- Logo -->
      <div class="auth-card-logo">
        <img src="../../uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia AHP" onerror="this.style.display='none'" height="44" width="44">
      </div>

      <!-- === FORM STATE === -->
      <div id="forgotFormState">
        <div class="auth-card-icon auth-card-icon--amber" aria-hidden="true">
          <i class="fa-solid fa-lock-open"></i>
        </div>
        <h1 class="auth-card-title">Forgot Password?</h1>
        <p class="auth-card-sub">Enter your staff email address and we'll send you a secure reset link.</p>

        <div class="auth-alert auth-alert--error" id="forgotErrorAlert" hidden role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <span id="forgotErrorMsg">Something went wrong. Please try again.</span>
        </div>

        <form class="auth-form" id="forgotForm" novalidate>
          <div class="auth-field">
            <label class="auth-label" for="forgotEmail">
              Email Address <span class="auth-required" aria-hidden="true">*</span>
            </label>
            <div class="auth-input-wrap">
              <i class="fa-solid fa-envelope auth-input-icon" aria-hidden="true"></i>
              <input type="email" id="forgotEmail" name="email" class="auth-input"
                     placeholder="you@transnzoia.go.ke" autocomplete="email" required aria-required="true">
            </div>
            <span class="auth-field-error" id="forgotEmailErr" role="alert" aria-live="polite"></span>
          </div>

          <button type="submit" class="auth-submit" id="forgotSubmitBtn">
            <span class="auth-submit-text">
              <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Reset Link
            </span>
            <span class="auth-submit-loading" aria-hidden="true">
              <i class="fa-solid fa-circle-notch fa-spin"></i> Sending&hellip;
            </span>
          </button>
        </form>

        <div class="auth-card-links">
          <a href="../login.php" class="auth-back-link-sm">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to login
          </a>
        </div>
      </div>

      <!-- === SUCCESS STATE === -->
      <div id="forgotSuccessState" hidden>
        <div class="auth-card-icon auth-card-icon--success auth-card-icon--anim" aria-hidden="true">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 class="auth-card-title">Check Your Email</h2>
        <p class="auth-card-sub">
          We sent a password reset link to<br>
          <strong id="forgotSuccessEmail" class="auth-success-email"></strong>
        </p>

        <div class="auth-success-note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <p>
            Didn't receive it? Check your spam folder or
            <button type="button" class="auth-inline-btn" id="forgotResendBtn" disabled>
              resend the email
            </button>.
            <span id="forgotResendTimer">(Resend in <span id="forgotCountdown">60</span>s)</span>
          </p>
        </div>

        <a href="../login.php" class="auth-submit auth-submit--outline" style="text-decoration:none;display:flex;">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Return to Login
        </a>
      </div>

    </div><!-- /auth-card -->

    <p class="auth-page-legal">
      &copy; 2026 Trans-Nzoia County Government &middot;
      <a href="../../legal/privacy.php">Privacy Policy</a>
    </p>

  </main>

  <script src="../../assets/js/global.js"></script>
  <script src="../assets/js/forgot-password.js"></script>
</body>
</html>
