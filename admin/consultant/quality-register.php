<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));

$pageTitle = 'Quality Register';
$pageDescription = 'Review quality tests, results and consultant actions across assigned projects.';
$adminRole = 'consultant';
$contentClass = 'consultant-quality-page';
$componentCss = ['consultant-quality'];
$pageScripts = ['consultant-quality'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant'],
    ['label' => 'Quality Register'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
require_once __DIR__ . '/../../app/partials/admin/consultant-quality-page.php';

consultant_quality_page([
    'type' => 'quality_test',
    'url' => 'admin/consultant/quality-register.php',
    'icon' => 'fa-vial-circle-check',
    'eyebrow' => 'Quality tests',
    'heading' => 'Quality Register',
    'description' => 'Review test results, failed items, pending laboratory records and consultant actions.',
    'peer_link' => 'admin/consultant/inspection-test-plans.php',
    'peer_icon' => 'fa-clipboard-check',
    'peer_label' => 'Inspections',
    'register_title' => 'Quality test register',
    'register_hint' => 'Filter tests and record consultant review actions.',
    'side_title' => 'Priority tests',
    'side_hint' => 'Pending and flagged quality tests.',
    'stats' => [
        ['icon' => 'fa-vials', 'key' => 'total', 'label' => 'Tests', 'hint' => 'Assigned projects'],
        ['icon' => 'fa-circle-check', 'key' => 'passed', 'label' => 'Passed', 'hint' => 'Accepted results'],
        ['icon' => 'fa-triangle-exclamation', 'key' => 'failed', 'label' => 'Failed', 'hint' => 'Needs action'],
        ['icon' => 'fa-hourglass-half', 'key' => 'pending', 'label' => 'Pending', 'hint' => 'Awaiting result'],
        ['icon' => 'fa-flag', 'key' => 'flagged', 'label' => 'Flagged', 'hint' => 'Consultant attention'],
    ],
]);

include __DIR__ . '/../../app/partials/admin/shell-end.php';
