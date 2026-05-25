<?php
$basePath = $basePath ?? '';
$legalPath = $basePath === '../' ? '' : 'legal/';
?>
<footer class="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="<?= $basePath ?>uploads/logos/afforadablehousinglogo.png" alt="Trans-Nzoia County Affordable Housing Programme" onerror="this.style.display='none'">
        <p>The Trans-Nzoia County Affordable Housing Project Tracker provides transparent, real-time monitoring of construction delivery under the national AHP programme &mdash; a public accountability initiative by the County Government.</p>
        <div class="footer-social">
          <a href="#" aria-label="Follow us on X / Twitter"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
          <a href="#" aria-label="Follow us on Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
          <a href="#" aria-label="Watch us on YouTube"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.08C5.12 19.54 12 19.54 12 19.54s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2A29 29 0 0 0 23 11.75a29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg></a>
        </div>
      </div>
      <div>
        <h3 class="footer-title">Public Pages</h3>
        <ul class="footer-links">
          <li><a href="<?= $basePath ?>projects.php">All Projects</a></li>
          <li><a href="<?= $basePath ?>constituencies.php">Constituencies</a></li>
          <li><a href="<?= $basePath ?>news.php">News &amp; Announcements</a></li>
          <li><a href="<?= $basePath ?>gallery.php">Photo Gallery</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-title">Programme</h3>
        <ul class="footer-links">
          <li><a href="<?= $basePath ?>about.php">About the Programme</a></li>
          <li><a href="<?= $basePath ?>leadership.php">County Leadership</a></li>
          <li><a href="<?= $basePath ?>stakeholders.php">Stakeholders</a></li>
          <li><a href="<?= $basePath ?>faq.php">Frequently Asked Questions</a></li>
          <li><a href="<?= $basePath ?>contact.php">Contact Us</a></li>
          <li><a href="<?= $basePath ?>sitemap.php">Sitemap</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-title">Contact</h3>
        <ul class="footer-contact">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span>County Headquarters, Kitale<br>Trans-Nzoia County, Kenya</span></li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span>+254 53 000 0000</span></li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg><span>housing@transnzoia.go.ke</span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 Trans-Nzoia County Government &mdash; Dept. of Land, Housing &amp; Physical Planning. All rights reserved.</p>
      <div class="footer-bottom-links">
        <a href="<?= $legalPath ?>privacy.php">Privacy Policy</a>
        <a href="<?= $legalPath ?>terms.php">Terms of Use</a>
        <a href="<?= $legalPath ?>disclaimer.php">Disclaimer</a>
      </div>
    </div>
  </div>
</footer>
