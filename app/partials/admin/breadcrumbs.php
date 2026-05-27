<?php
$pageTitle = trim((string)($pageTitle ?? 'Dashboard'));
$pageTitle = $pageTitle !== '' ? $pageTitle : 'Dashboard';
$adminRole = $adminRole ?? Auth::role() ?? 'staff';
$roleKey = strtolower((string)$adminRole);

$roleDashboardPaths = [
    'superadmin' => 'admin/superadmin/dashboard.php',
    'manager' => 'admin/manager/dashboard.php',
    'consultant' => 'admin/consultant/dashboard.php',
    'contractor' => 'admin/contractor/dashboard.php',
    'clerk' => 'admin/clerk/dashboard.php',
    'finance' => 'admin/finance/dashboard.php',
    'intern' => 'admin/intern/dashboard.php',
];

$defaultBreadcrumbs = [
    ['label' => 'Portal', 'path' => 'admin/index.php'],
    ['label' => role_label($roleKey), 'path' => $roleDashboardPaths[$roleKey] ?? 'admin/index.php'],
    ['label' => $pageTitle],
];

$breadcrumbItems = $breadcrumbs ?? $defaultBreadcrumbs;

if ($breadcrumbItems === []) {
    $breadcrumbItems = $defaultBreadcrumbs;
}

$normaliseHref = static function (array $item): string {
    if (!empty($item['href'])) {
        return (string)$item['href'];
    }

    if (!empty($item['path'])) {
        return Url::to((string)$item['path']);
    }

    return '';
};
?>
<nav class="admin-breadcrumbs" aria-label="Breadcrumb">
  <ol>
<?php foreach (array_values((array)$breadcrumbItems) as $index => $item): ?>
<?php
    if (!is_array($item)) {
        continue;
    }

    $label = trim((string)($item['label'] ?? ''));
    if ($label === '') {
        continue;
    }

    $isLast = $index === array_key_last(array_values((array)$breadcrumbItems));
    $href = $normaliseHref($item);
?>
    <li<?= $isLast ? ' aria-current="page"' : '' ?>>
<?php if (!$isLast && $href !== ''): ?>
      <a href="<?= Security::e($href) ?>"><?= Security::e($label) ?></a>
<?php else: ?>
      <span><?= Security::e($label) ?></span>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ol>
</nav>
