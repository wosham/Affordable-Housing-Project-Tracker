<?php
$basePath = $basePath ?? '';
$activePage = $activePage ?? '';
$siteName = (string)(function_exists('public_setting') ? public_setting('site_short_name', 'Trans-Nzoia AHP Tracker') : 'Trans-Nzoia AHP Tracker');
$siteSubtitle = (string)(function_exists('public_setting') ? public_setting('site_county_label', 'Trans-Nzoia County') : 'Trans-Nzoia County');
$countyLogoPath = (string)(function_exists('public_setting') ? public_setting('asset_county_logo', '') : '');
$logoPath = (string)(function_exists('public_setting') ? public_setting('asset_logo', 'uploads/logos/afforadablehousinglogo.png') : 'uploads/logos/afforadablehousinglogo.png');
$publicUserLoggedIn = class_exists('Auth') && Auth::check();
$staffVisible = function_exists('public_setting') ? public_setting('header_staff_visible', true) : true;
$staffVisible = is_bool($staffVisible) ? $staffVisible : in_array(strtolower((string)$staffVisible), ['1', 'true', 'yes', 'on'], true);
$staffVisible = $staffVisible && !$publicUserLoggedIn;
$staffLabel = (string)(function_exists('public_setting') ? public_setting('header_staff_label', 'Staff Portal') : 'Staff Portal');
$staffUrl = (string)(function_exists('public_setting') ? public_setting('header_staff_url', 'admin/login.php') : 'admin/login.php');
$staffIcon = (string)(function_exists('public_setting') ? public_setting('header_staff_icon', 'fa-lock') : 'fa-lock');
$link = static fn (string $path): string => function_exists('public_url') ? public_url($path) : $basePath . $path;
$resolveUrl = static function (string $path) use ($link): string {
  if ($path === '') {
    return '#';
  }
  return preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) === 1 || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) === 1 ? $path : $link($path);
};
if (!function_exists('nav_active_class')) {
  function nav_active_class(string $page, string $activePage): string {
    return $page === $activePage ? ' is-active' : '';
  }
}
$publicNavItems = function_exists('public_navigation_links') ? public_navigation_links('main') : [];
$brandLogos = array_values(array_unique(array_filter([$countyLogoPath, $logoPath], static fn (string $path): bool => trim($path) !== '')));
?>
<?php include __DIR__ . '/public-user-bar.php'; ?>
<div class="site-topbar" id="siteTopbar">
  <header class="navbar" id="navbar" role="banner">
    <div class="container">
      <a href="<?= htmlspecialchars($link('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="navbar-brand" aria-label="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> - Home">
        <span class="navbar-brand-logos" aria-hidden="true">
<?php foreach ($brandLogos as $brandLogo): ?>
          <img <?= public_image_attrs($brandLogo, '', ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high', 'onerror' => "this.style.display='none'"]) ?>>
<?php endforeach; ?>
        </span>
        <div class="navbar-brand-text"><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($siteSubtitle, ENT_QUOTES, 'UTF-8') ?></small></div>
      </a>
      <nav class="navbar-menu" aria-label="Main navigation">
<?php foreach ($publicNavItems as $item): ?>
<?php
  $href = (string)($item['href'] ?? '');
  $label = (string)($item['label'] ?? '');
  $key = (string)($item['page_key'] ?? pathinfo($href, PATHINFO_FILENAME));
  $url = !empty($item['is_external']) ? $href : $link($href);
?>
<?php if ($href !== '' && $label !== ''): ?>
        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="navbar-link<?= nav_active_class($key, $activePage) ?>"<?= !empty($item['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($staffVisible && $staffLabel !== ''): ?>
        <div class="navbar-cta">
          <a href="<?= htmlspecialchars($resolveUrl($staffUrl), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">
            <i class="fa-solid <?= htmlspecialchars($staffIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i> <?= htmlspecialchars($staffLabel, ENT_QUOTES, 'UTF-8') ?>
          </a>
        </div>
<?php endif; ?>
      </nav>
      <button class="navbar-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <?php include __DIR__ . '/ticker.php'; ?>
</div>
