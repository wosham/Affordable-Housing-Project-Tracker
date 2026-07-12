<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$pageTitle = 'Defects';
$pageDescription = 'Review defect records, assignments, due dates and closure status.';
$adminRole = 'consultant';
$csrfForm = 'consultant_quality';
$contentClass = 'consultant-quality-page';
$componentCss = ['consultant-quality'];
$pageScripts = ['consultant-quality'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Defects'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
require_once __DIR__ . '/../../app/partials/admin/consultant-quality-page.php';

consultant_quality_page([
    'type' => 'defect',
    'url' => 'admin/consultant/defects.php',
    'icon' => 'fa-magnifying-glass',
    'eyebrow' => 'Defect tracking',
    'heading' => 'Defects',
    'description' => 'Review defects, assigned corrective action, due dates and closure readiness on your assigned projects only.',
    'peer_link' => 'admin/consultant/quality-register.php',
    'peer_icon' => 'fa-vial-circle-check',
    'peer_label' => 'Quality Tests',
    'register_title' => 'Defect register',
    'register_hint' => 'Filter defects and record consultant review actions.',
    'side_title' => 'Open defects',
    'side_hint' => 'Pending and flagged defect records.',
    'stats' => [
        ['icon' => 'fa-bug', 'key' => 'total', 'label' => 'Defects', 'hint' => 'Assigned projects', 'filter' => []],
        ['icon' => 'fa-folder-open', 'key' => 'open_items', 'label' => 'Open', 'hint' => 'Needs action', 'filter' => ['status' => 'open']],
        ['icon' => 'fa-bars-progress', 'key' => 'in_progress', 'label' => 'In Progress', 'hint' => 'Being corrected', 'filter' => ['status' => 'in-progress']],
        ['icon' => 'fa-circle-check', 'key' => 'resolved', 'label' => 'Resolved', 'hint' => 'Ready to close', 'filter' => ['status' => 'resolved']],
        ['icon' => 'fa-triangle-exclamation', 'key' => 'serious', 'label' => 'Major/Critical', 'hint' => 'High attention', 'filter' => ['severity' => 'critical']],
    ],
]);

include __DIR__ . '/../../app/partials/admin/shell-end.php';
