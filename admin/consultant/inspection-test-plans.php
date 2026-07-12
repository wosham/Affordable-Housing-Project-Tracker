<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$pageTitle = 'Inspection Test Plans';
$pageDescription = 'Review inspections, witness requirements and hold points across assigned projects.';
$adminRole = 'consultant';
$csrfForm = 'consultant_quality';
$contentClass = 'consultant-quality-page';
$componentCss = ['consultant-quality'];
$pageScripts = ['consultant-quality'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Inspection Test Plans'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
require_once __DIR__ . '/../../app/partials/admin/consultant-quality-page.php';

consultant_quality_page([
    'type' => 'inspection',
    'url' => 'admin/consultant/inspection-test-plans.php',
    'icon' => 'fa-clipboard-check',
    'eyebrow' => 'Inspection review',
    'heading' => 'Inspection Test Plans',
    'description' => 'Review inspections, hold points, witness requirements and outcomes on your assigned projects only.',
    'peer_link' => 'admin/consultant/non-conformance.php',
    'peer_icon' => 'fa-triangle-exclamation',
    'peer_label' => 'NCRs',
    'register_title' => 'Inspection register',
    'register_hint' => 'Filter inspections and record consultant review actions.',
    'side_title' => 'Witness priorities',
    'side_hint' => 'Pending and flagged inspection records.',
    'stats' => [
        ['icon' => 'fa-clipboard-list', 'key' => 'total', 'label' => 'Inspections', 'hint' => 'Assigned projects', 'filter' => []],
        ['icon' => 'fa-eye', 'key' => 'pending_witness', 'label' => 'Pending Witness', 'hint' => 'Needs attendance', 'filter' => ['witness' => '1', 'review_status' => 'pending']],
        ['icon' => 'fa-circle-check', 'key' => 'accepted', 'label' => 'Accepted', 'hint' => 'Passed checks', 'filter' => ['review_status' => 'reviewed']],
        ['icon' => 'fa-rotate-left', 'key' => 'returned', 'label' => 'Returned', 'hint' => 'Needs correction', 'filter' => ['review_status' => 'returned']],
        ['icon' => 'fa-calendar-week', 'key' => 'due_week', 'label' => 'Due This Week', 'hint' => 'Upcoming checks', 'filter' => []],
    ],
]);

include __DIR__ . '/../../app/partials/admin/shell-end.php';
