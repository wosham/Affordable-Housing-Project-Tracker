<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$pageTitle = 'Non-Conformance';
$pageDescription = 'Review NCRs, corrective actions and closure status across assigned projects.';
$adminRole = 'consultant';
$csrfForm = 'consultant_quality';
$contentClass = 'consultant-quality-page';
$componentCss = ['consultant-quality'];
$pageScripts = ['consultant-quality'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Non-Conformance'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
require_once __DIR__ . '/../../app/partials/admin/consultant-quality-page.php';

consultant_quality_page([
    'type' => 'ncr',
    'url' => 'admin/consultant/non-conformance.php',
    'icon' => 'fa-triangle-exclamation',
    'eyebrow' => 'NCR review',
    'heading' => 'Non-Conformance',
    'description' => 'Review non-conformance records, severity, root causes and corrective actions on your assigned projects only.',
    'peer_link' => 'admin/consultant/defects.php',
    'peer_icon' => 'fa-magnifying-glass',
    'peer_label' => 'Defects',
    'register_title' => 'NCR register',
    'register_hint' => 'Filter NCRs and record consultant review actions.',
    'side_title' => 'Open NCRs',
    'side_hint' => 'Pending and flagged non-conformance records.',
    'stats' => [
        ['icon' => 'fa-list-check', 'key' => 'total', 'label' => 'NCRs', 'hint' => 'Assigned projects', 'filter' => []],
        ['icon' => 'fa-folder-open', 'key' => 'open_items', 'label' => 'Open', 'hint' => 'Needs action', 'filter' => ['status' => 'open']],
        ['icon' => 'fa-bars-progress', 'key' => 'in_progress', 'label' => 'In Progress', 'hint' => 'Being corrected', 'filter' => ['status' => 'in-progress']],
        ['icon' => 'fa-triangle-exclamation', 'key' => 'serious', 'label' => 'Major/Critical', 'hint' => 'High attention', 'filter' => ['severity' => 'major']],
        ['icon' => 'fa-clock', 'key' => 'overdue', 'label' => 'Overdue', 'hint' => 'Older than 14 days', 'filter' => ['status' => 'open']],
    ],
]);

include __DIR__ . '/../../app/partials/admin/shell-end.php';
