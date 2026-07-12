<?php
$basePath = $basePath ?? '';
$pageScripts = (array)($pageScripts ?? []);
$hasGlobalJs = false;
$hasDataJs = false;
foreach ($pageScripts as $script) {
    $normalizedScript = str_replace('\\', '/', (string)$script);
    if (str_ends_with($normalizedScript, 'assets/js/global.js')) {
        $hasGlobalJs = true;
    }
    if (str_ends_with($normalizedScript, 'assets/js/data.js')) {
        $hasDataJs = true;
    }
}
$pageScripts = array_values(array_unique(array_merge(
    $hasGlobalJs ? [] : ['assets/js/global.js'],
    $hasDataJs ? [] : ['assets/js/data.js'],
    $pageScripts
)));
$scriptSrc = static function (string $path) use ($basePath): string {
    $versionPath = $path;
    if (str_starts_with($path, '../')) {
        $versionPath = substr($path, 3);
        $url = $basePath . $versionPath;
    } else {
        $url = function_exists('public_asset') ? public_asset($path) : $basePath . ltrim($path, '/');
    }
    if (!preg_match('#^(?:https?:)?//#i', $url) && !str_starts_with($url, '/')) {
        $url = $basePath . ltrim($path, '/');
    }

    $version = function_exists('asset_version') ? asset_version($versionPath) : '';
    return $version !== '' && !str_contains($url, '?') ? $url . '?v=' . rawurlencode($version) : $url;
};
?>
<?php foreach ($pageScripts as $script): ?>
  <script src="<?= htmlspecialchars($scriptSrc((string)$script), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endforeach; ?>
