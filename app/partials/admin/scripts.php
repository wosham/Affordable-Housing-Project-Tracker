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
  <script src="<?= Security::e(Url::asset($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
