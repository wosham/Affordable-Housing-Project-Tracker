<?php
$basePath = $basePath ?? '';
$lang = $lang ?? 'en';
$pageTitle = $pageTitle ?? 'Trans-Nzoia AHP Tracker';
$pageDescription = $pageDescription ?? '';
$pageKeywords = $pageKeywords ?? '';
$pageAuthor = $pageAuthor ?? 'Trans-Nzoia County Government';
$pageRobots = $pageRobots ?? 'index, follow';
$themeColor = $themeColor ?? '#163300';
$canonicalUrl = $canonicalUrl ?? '';
$favicon = $favicon ?? $basePath . 'uploads/logos/afforadablehousinglogo.png';
$pageStyles = $pageStyles ?? [];
$headMeta = $headMeta ?? [];
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
<?php foreach ($headMeta as $meta): ?>
  <?= $meta . PHP_EOL ?>
<?php endforeach; ?>
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($canonicalUrl !== ''): ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="shortcut icon" href="<?= htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<?php foreach ($pageStyles as $style): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($style, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
</head>
