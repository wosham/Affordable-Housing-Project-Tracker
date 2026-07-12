<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$pageTitle = 'Quality Register';
$pageDescription = 'Review quality tests, results and consultant actions across assigned projects.';
$adminRole = 'consultant';
$csrfForm = 'consultant_quality';
$contentClass = 'consultant-quality-page';
$componentCss = ['consultant-quality'];
$pageScripts = ['consultant-quality'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
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
    'description' => 'Review test results, failed items, pending laboratory records and consultant actions on your assigned projects only.',
    'peer_link' => 'admin/consultant/inspection-test-plans.php',
    'peer_icon' => 'fa-clipboard-check',
    'peer_label' => 'Inspections',
    'register_title' => 'Quality test register',
    'register_hint' => 'Filter tests and record consultant review actions.',
    'side_title' => 'Priority tests',
    'side_hint' => 'Pending and flagged quality tests.',
    'stats' => [
        ['icon' => 'fa-vials', 'key' => 'total', 'label' => 'Tests', 'hint' => 'Assigned projects', 'filter' => []],
        ['icon' => 'fa-circle-check', 'key' => 'passed', 'label' => 'Passed', 'hint' => 'Accepted results', 'filter' => ['result' => 'pass']],
        ['icon' => 'fa-triangle-exclamation', 'key' => 'failed', 'label' => 'Failed', 'hint' => 'Needs action', 'filter' => ['result' => 'fail']],
        ['icon' => 'fa-hourglass-half', 'key' => 'pending', 'label' => 'Pending', 'hint' => 'Awaiting result', 'filter' => ['result' => 'pending']],
        ['icon' => 'fa-flag', 'key' => 'flagged', 'label' => 'Flagged', 'hint' => 'Consultant attention', 'filter' => ['review_status' => 'flagged']],
    ],
]);

include __DIR__ . '/../../app/partials/admin/shell-end.php';
