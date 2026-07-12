<?php
$basePath = $basePath ?? '';
$lang = $lang ?? 'en';
$siteName = (string)(function_exists('public_setting') ? public_setting('site_short_name') : '');
$pageTitle = $pageTitle ?? $siteName;
$pageDescription = $pageDescription ?? (string)(function_exists('public_setting') ? public_setting('site_tagline', '') : '');
$pageKeywords = $pageKeywords ?? '';
$pageAuthor = $pageAuthor ?? 'Trans-Nzoia County Government';
$pageRobots = $pageRobots ?? 'index, follow';
$themeColor = $themeColor ?? '#163300';
$defaultRoute = ltrim(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$baseForRoute = trim((string)parse_url(class_exists('Url') ? Url::basePath() : $basePath, PHP_URL_PATH), '/');
if ($baseForRoute !== '' && str_starts_with($defaultRoute, $baseForRoute . '/')) {
    $defaultRoute = substr($defaultRoute, strlen($baseForRoute) + 1);
}
$canonicalUrl = $canonicalUrl ?? (class_exists('Url') ? Url::to($defaultRoute !== '' ? $defaultRoute : 'index.php') : '');
$favicon = $favicon ?? (string)(function_exists('public_setting') ? public_setting('asset_logo') : '');
$ogImage = $ogImage ?? (string)(function_exists('public_setting') ? public_setting('asset_logo') : '');
$pageStyles = $pageStyles ?? [];
$pageStyles = (array)$pageStyles;
$hasGlobalCss = false;
foreach ($pageStyles as $style) {
    if (str_ends_with(str_replace('\\', '/', (string)$style), 'assets/css/global.css')) {
        $hasGlobalCss = true;
        break;
    }
}
$pageStyles = array_values(array_unique(array_merge($hasGlobalCss ? [] : ['assets/css/global.css'], $pageStyles)));
$headMeta = $headMeta ?? [];
$appConfig = $GLOBALS['app_config'] ?? [];
$appEnv = (string)($appConfig['env'] ?? getenv('APP_ENV') ?: 'local');
$assetHref = static function (string $path) use ($basePath): string {
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
$plainAsset = static function (string $path) use ($basePath): string {
    $url = function_exists('public_asset') ? public_asset($path) : $basePath . ltrim($path, '/');
    return $url;
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($pageDescription !== ''): ?>
  <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if ($pageKeywords !== ''): ?>
  <meta name="keywords" content="<?= htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if ($pageAuthor !== ''): ?>
  <meta name="author" content="<?= htmlspecialchars($pageAuthor, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
  <meta name="theme-color" content="<?= htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="robots" content="<?= htmlspecialchars($pageRobots, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="app-base-url" content="<?= htmlspecialchars(class_exists('Url') ? Url::basePath() : $basePath, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="app-env" content="<?= htmlspecialchars($appEnv, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:type" content="<?= htmlspecialchars($ogType ?? 'website', ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($pageDescription !== ''): ?>
  <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
  <meta property="og:image" content="<?= htmlspecialchars($plainAsset($ogImage), ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:card" content="summary_large_image">
<?php foreach ($headMeta as $meta): ?>
  <?= $meta . PHP_EOL ?>
<?php endforeach; ?>
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($canonicalUrl !== ''): ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($plainAsset($favicon), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="shortcut icon" href="<?= htmlspecialchars($plainAsset($favicon), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($plainAsset($favicon), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<?php foreach ($pageStyles as $style): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($assetHref((string)$style), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
</head>
