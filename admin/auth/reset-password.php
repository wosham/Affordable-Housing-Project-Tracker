<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create a new password — Trans-Nzoia AHP Tracker">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#163300">
  <title>Reset Password | Trans-Nzoia AHP Tracker</title>
  <link rel="icon" type="image/png" href="../../uploads/logos/afforadablehousinglogo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="../../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/reset-password.css">
</head>
<body>
  <div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>
  <div class="cursor-ring" id="cursorRing" aria-hidden="true"></div>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <main class="auth-page" id="main-content">
    <div class="auth-page-bg" aria-hidden="true">
      <div class="auth-page-grid"></div>
      <div class="auth-page-glow"></div>
    </div>

    <a href="../login.php" class="auth-page-back">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Login
    </a>

    <div class="auth-card" role="region" aria-label="Reset your password">

      <div class="auth-card-logo">
        <img src="../../uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia AHP" onerror="this.style.display='none'" height="44" width="44">
      </div>

      <!-- === INVALID TOKEN STATE === -->
      <div id="resetInvalidState" hidden>
        <div class="auth-card-icon auth-card-icon--red" aria-hidden="true">
          <i class="fa-solid fa-link-slash"></i>
        </div>
        <h1 class="auth-card-title">Link Expired</h1>
        <p class="auth-card-sub">This password reset link is invalid or has expired. Reset links are valid for 30 minutes.</p>
        <a href="forgot-password.php" class="auth-submit" style="text-decoration:none;display:flex;">
          <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Request a New Link
        </a>
        <div class="auth-card-links">
          <a href="../login.php" class="auth-back-link-sm">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to login
          </a>
        </div>
      </div>

      <!-- === FORM STATE === -->
      <div id="resetFormState" hidden>
        <div class="auth-card-icon auth-card-icon--primary" aria-hidden="true">
          <i class="fa-solid fa-key"></i>
        </div>
        <h1 class="auth-card-title">New Password</h1>
        <p class="auth-card-sub">Create a strong new password for your staff account.</p>

        <div class="auth-alert auth-alert--error" id="resetErrorAlert" hidden role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <span id="resetErrorMsg">Something went wrong. Please try again.</span>
        </div>

        <form class="auth-form" id="resetForm" novalidate>

          <!-- New password -->
          <div class="auth-field">
            <label class="auth-label" for="resetNewPassword">
              New Password <span class="auth-required" aria-hidden="true">*</span>
            </label>
            <div class="auth-input-wrap">
              <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
              <input type="password" id="resetNewPassword" name="password" class="auth-input auth-input--pw"
                     placeholder="Create a strong password" autocomplete="new-password" required aria-required="true"
                     aria-describedby="resetStrengthLabel resetReqs">
              <button type="button" class="auth-pwd-toggle" id="resetNewPwdToggle"
                      aria-label="Show new password" aria-pressed="false">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <!-- Strength meter -->
            <div class="rp-strength" aria-live="polite">
              <div class="rp-strength-bar">
                <div class="rp-strength-fill" id="resetStrengthFill"></div>
              </div>
              <span class="rp-strength-label" id="resetStrengthLabel"></span>
            </div>
            <span class="auth-field-error" id="resetNewPasswordErr" role="alert" aria-live="polite"></span>
          </div>

          <!-- Requirements checklist -->
          <ul class="rp-reqs" id="resetReqs" aria-label="Password requirements">
            <li class="rp-req" id="reqLength" data-req="length">
              <i class="fa-solid fa-circle rp-req-icon" aria-hidden="true"></i>
              <span>At least 8 characters</span>
            </li>
            <li class="rp-req" id="reqUpper" data-req="upper">
              <i class="fa-solid fa-circle rp-req-icon" aria-hidden="true"></i>
              <span>One uppercase letter (A&ndash;Z)</span>
            </li>
            <li class="rp-req" id="reqLower" data-req="lower">
              <i class="fa-solid fa-circle rp-req-icon" aria-hidden="true"></i>
              <span>One lowercase letter (a&ndash;z)</span>
            </li>
            <li class="rp-req" id="reqNumber" data-req="number">
              <i class="fa-solid fa-circle rp-req-icon" aria-hidden="true"></i>
              <span>One number (0&ndash;9)</span>
            </li>
            <li class="rp-req" id="reqSpecial" data-req="special">
              <i class="fa-solid fa-circle rp-req-icon" aria-hidden="true"></i>
              <span>One special character (!@#$&hellip;)</span>
            </li>
          </ul>

          <!-- Confirm password -->
          <div class="auth-field">
            <label class="auth-label" for="resetConfirmPassword">
              Confirm Password <span class="auth-required" aria-hidden="true">*</span>
            </label>
            <div class="auth-input-wrap">
              <i class="fa-solid fa-lock-open auth-input-icon" aria-hidden="true"></i>
              <input type="password" id="resetConfirmPassword" name="confirm_password" class="auth-input auth-input--pw"
                     placeholder="Re-enter your password" autocomplete="new-password" required aria-required="true">
              <button type="button" class="auth-pwd-toggle" id="resetConfirmPwdToggle"
                      aria-label="Show confirm password" aria-pressed="false">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <span class="auth-field-error" id="resetConfirmPasswordErr" role="alert" aria-live="polite"></span>
          </div>

          <button type="submit" class="auth-submit" id="resetSubmitBtn">
            <span class="auth-submit-text">
              <i class="fa-solid fa-shield-check" aria-hidden="true"></i> Update Password
            </span>
            <span class="auth-submit-loading" aria-hidden="true">
              <i class="fa-solid fa-circle-notch fa-spin"></i> Updating&hellip;
            </span>
          </button>
        </form>
      </div>

      <!-- === SUCCESS STATE === -->
      <div id="resetSuccessState" hidden>
        <div class="auth-card-icon auth-card-icon--success auth-card-icon--anim" aria-hidden="true">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 class="auth-card-title">Password Updated!</h2>
        <p class="auth-card-sub">Your password has been changed successfully. You can now sign in with your new password.</p>
        <div class="rp-redirect-notice">
          <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
          <span>Redirecting to login in <strong id="resetCountdown">5</strong>s&hellip;</span>
        </div>
        <a href="../login.php" class="auth-submit" style="text-decoration:none;display:flex;" id="resetGoLogin">
          <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Sign In Now
        </a>
        <button type="button" class="auth-back-link-sm rp-stay-btn" id="resetStayBtn">
          Cancel redirect
        </button>
      </div>

    </div><!-- /auth-card -->

    <p class="auth-page-legal">
      &copy; 2026 Trans-Nzoia County Government &middot;
      <a href="../../legal/privacy.php">Privacy Policy</a>
    </p>

  </main>

  <script src="../../assets/js/global.js"></script>
  <script src="../assets/js/reset-password.js"></script>
</body>
</html>
