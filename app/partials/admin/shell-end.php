      <footer class="admin-footer" aria-label="Admin workspace footer">
        <p class="admin-footer-copy">&copy; <?= Security::e(date('Y')) ?> AHPTC. All rights reserved. <span>Version 1.0</span></p>
        <nav class="admin-footer-links" aria-label="Admin footer links">
          <a href="<?= Security::e(Url::to('index.php')) ?>" target="_blank" rel="noopener noreferrer">Public Website</a>
          <a href="<?= Security::e(Url::to('terms.php')) ?>">Terms</a>
          <a href="<?= Security::e(Url::to('privacy.php')) ?>">Privacy</a>
        </nav>
      </footer>
    </main>
  </div>
</div>

<?php include __DIR__ . '/scripts.php'; ?>
