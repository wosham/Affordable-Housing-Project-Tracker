<?php
$basePath = $basePath ?? '';
$activePage = $activePage ?? '';
$siteName = (string)(function_exists('public_setting') ? public_setting('mobile_brand_label', public_setting('site_short_name', 'Trans-Nzoia AHP Tracker')) : 'Trans-Nzoia AHP Tracker');
$countyLogoPath = (string)(function_exists('public_setting') ? public_setting('asset_county_logo', '') : '');
$logoPath = (string)(function_exists('public_setting') ? public_setting('asset_logo', 'uploads/logos/afforadablehousinglogo.png') : 'uploads/logos/afforadablehousinglogo.png');
$pagesHeading = (string)(function_exists('public_setting') ? public_setting('mobile_pages_heading', 'Pages') : 'Pages');
$legalHeading = (string)(function_exists('public_setting') ? public_setting('mobile_legal_heading', 'Legal') : 'Legal');
$contactPhone = (string)(function_exists('public_setting') ? public_setting('contact_phone', '') : '');
$contactPhoneHref = (string)(function_exists('public_setting') ? public_setting('contact_phone_href', '') : '');
$contactEmail = (string)(function_exists('public_setting') ? public_setting('contact_email', '') : '');
$contactEmailHref = (string)(function_exists('public_setting') ? public_setting('contact_email_href', '') : '');
$ecitizenUrl = (string)(function_exists('public_setting') ? public_setting('apply_portal_url', '') : '');
$staffVisible = function_exists('public_setting') ? public_setting('mobile_staff_visible', true) : true;
$staffVisible = is_bool($staffVisible) ? $staffVisible : in_array(strtolower((string)$staffVisible), ['1', 'true', 'yes', 'on'], true);
$staffLabel = (string)(function_exists('public_setting') ? public_setting('mobile_staff_label', 'Staff Portal Login') : 'Staff Portal Login');
$staffUrl = (string)(function_exists('public_setting') ? public_setting('mobile_staff_url', 'admin/login.php') : 'admin/login.php');
$staffIcon = (string)(function_exists('public_setting') ? public_setting('mobile_staff_icon', 'fa-lock') : 'fa-lock');
$applyVisible = function_exists('public_setting') ? public_setting('mobile_apply_visible', true) : true;
$applyVisible = is_bool($applyVisible) ? $applyVisible : in_array(strtolower((string)$applyVisible), ['1', 'true', 'yes', 'on'], true);
$applyLabel = (string)(function_exists('public_setting') ? public_setting('mobile_apply_label', 'Apply via eCitizen') : 'Apply via eCitizen');
$applyIcon = (string)(function_exists('public_setting') ? public_setting('mobile_apply_icon', 'fa-arrow-up-right-from-square') : 'fa-arrow-up-right-from-square');
$contactVisible = function_exists('public_setting') ? public_setting('mobile_contact_visible', true) : true;
$contactVisible = is_bool($contactVisible) ? $contactVisible : in_array(strtolower((string)$contactVisible), ['1', 'true', 'yes', 'on'], true);
$link = static fn (string $path): string => function_exists('public_url') ? public_url($path) : $basePath . $path;
$resolveUrl = static function (string $path) use ($link): string {
  if ($path === '') {
    return '#';
  }
  return preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) === 1 || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) === 1 ? $path : $link($path);
};
if (!function_exists('mob_active_class')) {
  function mob_active_class(string $page, string $activePage): string {
    return $page === $activePage ? ' is-active' : '';
  }
}
$mobileLinks = function_exists('public_navigation_links') ? public_navigation_links('mobile') : [];
$legalLinks = function_exists('public_navigation_links') ? public_navigation_links('legal') : [];
$brandLogos = array_values(array_unique(array_filter([$countyLogoPath, $logoPath], static fn (string $path): bool => trim($path) !== '')));
?>
<div class="navbar-mobile" id="mobile-menu" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Navigation menu">
  <div class="mob-menu-inner">
    <div class="mob-menu-top">
      <a href="<?= htmlspecialchars($link('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="mob-brand">
        <span class="mob-brand-logos" aria-hidden="true">
<?php foreach ($brandLogos as $brandLogo): ?>
          <img <?= public_image_attrs($brandLogo, '', ['loading' => 'eager', 'decoding' => 'async', 'onerror' => "this.style.display='none'"]) ?>>
<?php endforeach; ?>
        </span>
        <span><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
      <button class="mob-close" id="mob-close-btn" aria-label="Close navigation menu">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <nav class="mob-nav" aria-label="Mobile navigation">
      <span class="mob-nav-label"><?= htmlspecialchars($pagesHeading, ENT_QUOTES, 'UTF-8') ?></span>
<?php foreach ($mobileLinks as $item): ?>
<?php
  $href = (string)($item['href'] ?? '');
  $label = (string)($item['label'] ?? '');
  $key = (string)($item['page_key'] ?? pathinfo($href, PATHINFO_FILENAME));
  $url = !empty($item['is_external']) ? $href : $link($href);
?>
<?php if ($href !== '' && $label !== ''): ?>
      <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="mob-nav-link<?= mob_active_class($key, $activePage) ?>"<?= !empty($item['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><span class="mob-nav-icon"><i class="fa-solid fa-link" aria-hidden="true"></i></span> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php endif; ?>
<?php endforeach; ?>
      <span class="mob-nav-label" style="margin-top:var(--space-4)"><?= htmlspecialchars($legalHeading, ENT_QUOTES, 'UTF-8') ?></span>
<?php foreach ($legalLinks as $item): ?>
<?php
  $href = (string)($item['href'] ?? '');
  $label = (string)($item['label'] ?? '');
  $key = (string)($item['page_key'] ?? pathinfo($href, PATHINFO_FILENAME));
  $url = !empty($item['is_external']) ? $href : $link($href);
?>
<?php if ($href !== '' && $label !== ''): ?>
      <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="mob-nav-link<?= mob_active_class($key, $activePage) ?>"<?= !empty($item['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><span class="mob-nav-icon"><i class="fa-solid fa-link" aria-hidden="true"></i></span> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php endif; ?>
<?php endforeach; ?>
    </nav>
<?php if (($staffVisible && $staffLabel !== '') || ($applyVisible && $applyLabel !== '')): ?>
    <div class="mob-menu-ctas">
<?php if ($staffVisible && $staffLabel !== ''): ?>
      <a href="<?= htmlspecialchars($resolveUrl($staffUrl), ENT_QUOTES, 'UTF-8') ?>" class="mob-cta-staff"><i class="fa-solid <?= htmlspecialchars($staffIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i> <?= htmlspecialchars($staffLabel, ENT_QUOTES, 'UTF-8') ?></a>
<?php endif; ?>
<?php if ($applyVisible && $applyLabel !== ''): ?>
      <a href="<?= htmlspecialchars($resolveUrl($ecitizenUrl), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mob-cta-ecitizen"><i class="fa-solid <?= htmlspecialchars($applyIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i> <?= htmlspecialchars($applyLabel, ENT_QUOTES, 'UTF-8') ?></a>
<?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($contactVisible && ($contactPhone !== '' || $contactEmail !== '')): ?>
    <div class="mob-contact-strip">
<?php if ($contactPhone !== ''): ?>
      <a href="<?= htmlspecialchars($contactPhoneHref !== '' ? $contactPhoneHref : '#', ENT_QUOTES, 'UTF-8') ?>" class="mob-contact-item"><i class="fa-solid fa-phone" aria-hidden="true"></i><span><?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></span></a>
<?php endif; ?>
<?php if ($contactEmail !== ''): ?>
      <a href="<?= htmlspecialchars($contactEmailHref !== '' ? $contactEmailHref : '#', ENT_QUOTES, 'UTF-8') ?>" class="mob-contact-item"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></span></a>
<?php endif; ?>
    </div>
<?php endif; ?>
  </div>
</div>
<div class="navbar-mobile-backdrop" id="mob-backdrop" aria-hidden="true"></div>