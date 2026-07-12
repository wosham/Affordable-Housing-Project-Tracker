<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$submissionType = 'material';
$pageTitle = 'Material Approvals';
$pageDescription = 'Submit and track project material records.';
$adminRole = 'contractor';
$contentClass = 'contractor-submission-page';
$componentCss = ['media-library', 'contractor-submissions'];
$pageScripts = ['media-picker', 'contractor-submissions'];
$csrfForm = 'contractor_submission';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Material Approvals'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/contractor-submission-page.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
