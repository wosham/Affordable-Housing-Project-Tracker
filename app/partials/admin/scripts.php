<?php
$loadAdminGlobal = $loadAdminGlobal ?? true;
$adminScripts = $adminScripts ?? [];
$componentScripts = $componentScripts ?? [];
$pageScripts = $pageScripts ?? [];

$normaliseScript = static function (string $script): string {
    $script = trim($script);

    if ($script === '') {
        return '';
    }

    if (str_contains($script, '/') || str_ends_with($script, '.js')) {
        return $script;
    }

    return 'admin/assets/js/' . $script . '.js';
};

$scripts = [];

if ($loadAdminGlobal) {
    $scripts[] = 'admin/assets/js/admin-global.js';
    $scripts[] = 'admin/assets/js/notifications.js';
    $scripts[] = 'admin/assets/js/portal-announcements.js';
    $scripts[] = 'admin/assets/js/data-tables.js';
    $scripts[] = 'admin/assets/js/file-uploader.js';
}

foreach ([$adminScripts, $componentScripts, $pageScripts] as $scriptGroup) {
    foreach ((array)$scriptGroup as $script) {
        $normalised = $normaliseScript((string)$script);
        if ($normalised !== '') {
            $scripts[] = $normalised;
        }
    }
}

$scripts = array_values(array_unique($scripts));
?>
<?php foreach ($scripts as $script): ?>
<?php $src = Url::isAbsolute($script) ? $script : Url::asset($script) . '?v=' . rawurlencode(asset_version($script)); ?>
  <script src="<?= Security::e($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
