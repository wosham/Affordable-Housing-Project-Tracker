<?php
$basePath = $basePath ?? '';
$activePage = $activePage ?? '';
$legalPath = $basePath === '../' ? '' : 'legal/';
if (!function_exists('mob_active_class')) {
  function mob_active_class(string $page, string $activePage): string {
    return $page === $activePage ? ' is-active' : '';
  }
}
?>
<div class="navbar-mobile" id="mobile-menu" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Navigation menu">
  <div class="mob-menu-inner">
    <div class="mob-menu-top">
      <a href="<?= $basePath ?>index.php" class="mob-brand">
        <img src="<?= $basePath ?>uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia AHP" height="36" onerror="this.style.display='none'">
        <span>AHP Tracker</span>
      </a>
      <button class="mob-close" id="mob-close-btn" aria-label="Close navigation menu">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <nav class="mob-nav" aria-label="Mobile navigation">
      <span class="mob-nav-label">Pages</span>
      <a href="<?= $basePath ?>index.php" class="mob-nav-link<?= mob_active_class('home', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span> Home</a>
      <a href="<?= $basePath ?>projects.php" class="mob-nav-link<?= mob_active_class('projects', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span> Projects</a>
      <a href="<?= $basePath ?>constituencies.php" class="mob-nav-link<?= mob_active_class('constituencies', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span> Constituencies</a>
      <a href="<?= $basePath ?>news.php" class="mob-nav-link<?= mob_active_class('news', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span> News &amp; Updates</a>
      <a href="<?= $basePath ?>gallery.php" class="mob-nav-link<?= mob_active_class('gallery', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-images" aria-hidden="true"></i></span> Photo Gallery</a>
      <a href="<?= $basePath ?>about.php" class="mob-nav-link<?= mob_active_class('about', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span> About the Programme</a>
      <a href="<?= $basePath ?>leadership.php" class="mob-nav-link<?= mob_active_class('leadership', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></span> Leadership</a>
      <a href="<?= $basePath ?>stakeholders.php" class="mob-nav-link<?= mob_active_class('stakeholders', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-people-group" aria-hidden="true"></i></span> Stakeholders</a>
      <a href="<?= $basePath ?>contact.php" class="mob-nav-link<?= mob_active_class('contact', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span> Contact Us</a>
      <span class="mob-nav-label" style="margin-top:var(--space-4)">Legal</span>
      <a href="<?= $legalPath ?>privacy.php" class="mob-nav-link<?= mob_active_class('privacy', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span> Privacy Policy</a>
      <a href="<?= $legalPath ?>terms.php" class="mob-nav-link<?= mob_active_class('terms', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-file-contract" aria-hidden="true"></i></span> Terms of Use</a>
      <a href="<?= $legalPath ?>disclaimer.php" class="mob-nav-link<?= mob_active_class('disclaimer', $activePage) ?>"><span class="mob-nav-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span> Disclaimer</a>
    </nav>
    <div class="mob-menu-ctas">
      <a href="<?= $basePath ?>auth/login.php" class="mob-cta-staff"><i class="fa-solid fa-lock" aria-hidden="true"></i> Staff Portal Login</a>
      <a href="https://ecitizen.go.ke" target="_blank" rel="noopener noreferrer" class="mob-cta-ecitizen"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Apply via eCitizen</a>
    </div>
    <div class="mob-contact-strip">
      <a href="tel:+254530000000" class="mob-contact-item"><i class="fa-solid fa-phone" aria-hidden="true"></i><span>+254 53 000 0000</span></a>
      <a href="mailto:housing@transnzoia.go.ke" class="mob-contact-item"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span>housing@transnzoia.go.ke</span></a>
    </div>
  </div>
</div>
<div class="navbar-mobile-backdrop" id="mob-backdrop" aria-hidden="true"></div>
