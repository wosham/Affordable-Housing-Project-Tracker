<?php
$lang = $lang ?? 'en';
$pageTitle = $pageTitle ?? 'Dashboard';
$pageDescription = $pageDescription ?? 'AHPTC staff portal dashboard.';
$pageRobots = $pageRobots ?? 'noindex, nofollow';
$themeColor = $themeColor ?? '#163300';
$adminRole = $adminRole ?? Auth::role() ?? 'staff';
$componentCss = $componentCss ?? [];
$pageStyles = $pageStyles ?? [];
$bodyClass = $bodyClass ?? '';
$csrfForm = $csrfForm ?? 'default';

$roleClass = strtolower((string)$adminRole);
$roleClass = preg_replace('/[^a-z0-9_-]+/', '-', $roleClass) ?: 'staff';
$adminCss = $adminCss ?? 'admin/assets/css/dashboard-' . $roleClass . '.css';
$fullTitle = $pageTitle . ' | ' . role_label((string)$adminRole) . ' | Trans-Nzoia AHP Tracker';
$favicon = Url::asset('uploads/logos/afforadablehousinglogo.png');

$componentStyles = [];
foreach ((array)$componentCss as $style) {
    $style = trim((string)$style);
    if ($style === '') {
        continue;
    }

    $componentStyles[] = str_contains($style, '/')
        ? $style
        : 'admin/assets/css/components/' . $style . '.css';
}

$stylesheets = array_merge(
    ['admin/assets/css/admin-global.css', $adminCss],
    $componentStyles,
    (array)$pageStyles
);

$bodyClasses = trim('admin-body admin-body--' . $roleClass . ' ' . (string)$bodyClass);
?><!DOCTYPE html>
<html lang="<?= Security::e($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= Security::e($pageDescription) ?>">
  <meta name="robots" content="<?= Security::e($pageRobots) ?>">
  <meta name="theme-color" content="<?= Security::e($themeColor) ?>">
  <meta name="application-name" content="Trans-Nzoia AHP Tracker">
  <meta name="format-detection" content="telephone=no">
  <meta name="csrf-token" content="<?= Security::e(Csrf::token($csrfForm)) ?>">
  <meta name="csrf-token-name" content="<?= Security::e(Csrf::tokenName()) ?>">
  <meta name="csrf-form" content="<?= Security::e($csrfForm) ?>">
  <meta name="app-base-url" content="<?= Security::e(Url::basePath()) ?>">
  <meta name="admin-role" content="<?= Security::e((string)$adminRole) ?>">
  <title><?= Security::e($fullTitle) ?></title>
  <link rel="icon" type="image/png" href="<?= Security::e($favicon) ?>">
  <link rel="shortcut icon" href="<?= Security::e($favicon) ?>">
  <link rel="apple-touch-icon" href="<?= Security::e($favicon) ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<?php foreach ($stylesheets as $stylesheet): ?>
<?php $stylesheet = trim((string)$stylesheet); ?>
<?php if ($stylesheet !== ''): ?>
  <link rel="stylesheet" href="<?= Security::e(Url::asset($stylesheet)) ?>">
<?php endif; ?>
<?php endforeach; ?>
</head>
<body class="<?= Security::e($bodyClasses) ?>">
