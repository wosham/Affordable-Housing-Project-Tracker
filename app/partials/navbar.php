<?php
$basePath = $basePath ?? '';
$activePage = $activePage ?? '';
if (!function_exists('nav_active_class')) {
  function nav_active_class(string $page, string $activePage): string {
    return $page === $activePage ? ' is-active' : '';
  }
}
?>
<div class="site-topbar" id="siteTopbar">
  <header class="navbar" id="navbar" role="banner">
    <div class="container">
      <a href="<?= $basePath ?>index.php" class="navbar-brand" aria-label="Trans-Nzoia AHP Tracker &mdash; Home">
        <img src="<?= $basePath ?>uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia County" loading="eager" onerror="this.style.display='none'">
        <div class="navbar-brand-text">AHP Tracker<br><small>Trans-Nzoia County</small></div>
      </a>
      <nav class="navbar-menu" aria-label="Main navigation">
        <a href="<?= $basePath ?>index.php" class="navbar-link<?= nav_active_class('home', $activePage) ?>">Home</a>
        <a href="<?= $basePath ?>projects.php" class="navbar-link<?= nav_active_class('projects', $activePage) ?>">Projects</a>
        <a href="<?= $basePath ?>constituencies.php" class="navbar-link<?= nav_active_class('constituencies', $activePage) ?>">Constituencies</a>
        <a href="<?= $basePath ?>news.php" class="navbar-link<?= nav_active_class('news', $activePage) ?>">News</a>
        <a href="<?= $basePath ?>about.php" class="navbar-link<?= nav_active_class('about', $activePage) ?>">About</a>
        <a href="<?= $basePath ?>contact.php" class="navbar-link<?= nav_active_class('contact', $activePage) ?>">Contact</a>
        <div class="navbar-cta">
          <a href="<?= $basePath ?>auth/login.php" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-lock" aria-hidden="true"></i> Staff Portal
          </a>
        </div>
      </nav>
      <button class="navbar-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <?php include __DIR__ . '/ticker.php'; ?>
</div>
