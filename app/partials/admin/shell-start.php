<?php
$showBreadcrumbs = $showBreadcrumbs ?? true;
$showFlash = $showFlash ?? true;
$contentClass = trim((string)($contentClass ?? ''));
$mainId = trim((string)($mainId ?? 'main-content'));
$mainId = $mainId !== '' ? $mainId : 'main-content';

include __DIR__ . '/head.php';
?>
<a href="#<?= Security::e($mainId) ?>" class="admin-skip-link">Skip to main content</a>

<div class="admin-shell">
<?php include __DIR__ . '/sidebar.php'; ?>

  <div class="admin-main">
<?php include __DIR__ . '/header.php'; ?>

    <main class="<?= Security::e(trim('admin-content ' . $contentClass)) ?>" id="<?= Security::e($mainId) ?>">
<?php if ($showBreadcrumbs): ?>
<?php include __DIR__ . '/breadcrumbs.php'; ?>
<?php endif; ?>
<?php if ($showFlash): ?>
<?php include __DIR__ . '/flash-messages.php'; ?>
<?php endif; ?>
