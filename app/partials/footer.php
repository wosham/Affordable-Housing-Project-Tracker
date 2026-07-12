<?php
$basePath = $basePath ?? '';
$siteName = (string)(function_exists('public_setting') ? public_setting('site_name', 'Trans-Nzoia AHP Tracker') : 'Trans-Nzoia AHP Tracker');
$footerText = (string)(function_exists('public_setting') ? public_setting('footer_description', 'The Trans-Nzoia County Affordable Housing Project Tracker provides transparent, real-time monitoring of construction delivery under the national AHP programme.') : '');
$countyLogoPath = (string)(function_exists('public_setting') ? public_setting('asset_county_logo', '') : '');
$logoPath = (string)(function_exists('public_setting') ? public_setting('asset_logo', 'uploads/logos/afforadablehousinglogo.png') : 'uploads/logos/afforadablehousinglogo.png');
$footerLogoPrimary = (string)(function_exists('public_setting') ? public_setting('asset_footer_logo_primary', $countyLogoPath) : $countyLogoPath);
$footerLogoSecondary = (string)(function_exists('public_setting') ? public_setting('asset_footer_logo_secondary', $logoPath) : $logoPath);
$footerPublicHeading = (string)(function_exists('public_setting') ? public_setting('footer_public_heading', 'Public Pages') : 'Public Pages');
$footerProgrammeHeading = (string)(function_exists('public_setting') ? public_setting('footer_programme_heading', 'Programme') : 'Programme');
$footerContactHeading = (string)(function_exists('public_setting') ? public_setting('footer_contact_heading', 'Contact') : 'Contact');
$copyright = (string)(function_exists('public_setting') ? public_setting('footer_copyright', '© {year} Trans-Nzoia County Government — Dept. of Land, Housing & Physical Planning. All rights reserved.') : '© {year} Trans-Nzoia County Government — Dept. of Land, Housing & Physical Planning. All rights reserved.');
$copyright = str_replace('{year}', date('Y'), $copyright);
$contactAddress = (string)(function_exists('public_setting') ? public_setting('contact_address', '') : '');
$contactPhone = (string)(function_exists('public_setting') ? public_setting('contact_phone', '') : '');
$contactPhoneHref = (string)(function_exists('public_setting') ? public_setting('contact_phone_href', '') : '');
$contactEmail = (string)(function_exists('public_setting') ? public_setting('contact_email', '') : '');
$contactEmailHref = (string)(function_exists('public_setting') ? public_setting('contact_email_href', '') : '');
$socials = [
  ['social_twitter', 'Follow us on X / Twitter', 'fa-brands fa-x-twitter'],
  ['social_facebook', 'Follow us on Facebook', 'fa-brands fa-facebook-f'],
  ['social_youtube', 'Watch us on YouTube', 'fa-brands fa-youtube'],
  ['social_instagram', 'Follow us on Instagram', 'fa-brands fa-instagram'],
];
$link = static fn (string $path): string => function_exists('public_url') ? public_url($path) : $basePath . $path;
$publicLinks = function_exists('public_navigation_links') ? public_navigation_links('footer_public_pages') : [];
$programmeLinks = function_exists('public_navigation_links') ? public_navigation_links('footer_programme') : [];
$legalLinks = function_exists('public_navigation_links') ? public_navigation_links('legal') : [];
$footerLogos = array_values(array_unique(array_filter([$footerLogoPrimary, $footerLogoSecondary], static fn (string $path): bool => trim($path) !== '')));
?>
<footer class="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-brand-logos" aria-hidden="true">
<?php foreach ($footerLogos as $footerLogo): ?>
          <img <?= public_image_attrs($footerLogo, '', ['loading' => 'lazy', 'decoding' => 'async', 'onerror' => "this.style.display='none'"]) ?>>
<?php endforeach; ?>
        </div>
        <p><?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="footer-social">
<?php foreach ($socials as [$key, $label, $icon]): $url = (string)(function_exists('public_setting') ? public_setting($key, '') : ''); ?>
<?php if ($url !== ''): ?>
          <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"><i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></a>
<?php endif; ?>
<?php endforeach; ?>
        </div>
      </div>
      <div>
        <h3 class="footer-title"><?= htmlspecialchars($footerPublicHeading, ENT_QUOTES, 'UTF-8') ?></h3>
        <ul class="footer-links">
<?php foreach ($publicLinks as $footerLink): ?>
<?php $href = (string)($footerLink['href'] ?? ''); $url = !empty($footerLink['is_external']) ? $href : $link($href); ?>
<?php if ($href !== '' && (string)($footerLink['label'] ?? '') !== ''): ?>
          <li><a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"<?= !empty($footerLink['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= htmlspecialchars((string)$footerLink['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h3 class="footer-title"><?= htmlspecialchars($footerProgrammeHeading, ENT_QUOTES, 'UTF-8') ?></h3>
        <ul class="footer-links">
<?php foreach ($programmeLinks as $footerLink): ?>
<?php $href = (string)($footerLink['href'] ?? ''); $url = !empty($footerLink['is_external']) ? $href : $link($href); ?>
<?php if ($href !== '' && (string)($footerLink['label'] ?? '') !== ''): ?>
          <li><a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"<?= !empty($footerLink['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= htmlspecialchars((string)$footerLink['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h3 class="footer-title"><?= htmlspecialchars($footerContactHeading, ENT_QUOTES, 'UTF-8') ?></h3>
        <ul class="footer-contact">
<?php if ($contactAddress !== ''): ?>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span><?= nl2br(htmlspecialchars($contactAddress, ENT_QUOTES, 'UTF-8')) ?></span></li>
<?php endif; ?>
<?php if ($contactPhone !== ''): ?>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><a href="<?= htmlspecialchars($contactPhoneHref !== '' ? $contactPhoneHref : '#', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></a></li>
<?php endif; ?>
<?php if ($contactEmail !== ''): ?>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg><a href="<?= htmlspecialchars($contactEmailHref !== '' ? $contactEmailHref : '#', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></a></li>
<?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p><?= htmlspecialchars($copyright, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="footer-bottom-links">
<?php foreach ($legalLinks as $legalLink): ?>
<?php
  $href = (string)($legalLink['href'] ?? '#');
  $url = !empty($legalLink['is_external']) ? $href : $link($href);
?>
        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"<?= !empty($legalLink['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= htmlspecialchars((string)($legalLink['label'] ?? 'Legal'), ENT_QUOTES, 'UTF-8') ?></a>
<?php endforeach; ?>
      </div>
    </div>
  </div>
</footer>