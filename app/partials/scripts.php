<?php $pageScripts = $pageScripts ?? []; ?>
<?php foreach ($pageScripts as $script): ?>
  <script src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
