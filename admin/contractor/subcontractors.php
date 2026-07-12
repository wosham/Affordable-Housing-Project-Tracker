<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$recordType = 'subcontractors';
$pageTitle = 'Subcontractors';
$pageDescription = 'Subcontractor contacts, scopes, compliance and risk records.';
$adminRole = 'contractor';
$contentClass = 'contractor-site-record-page';
$componentCss = ['media-library', 'contractor-site-records'];
$pageScripts = ['media-picker', 'contractor-site-records'];
$csrfForm = 'contractor_site_records';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Subcontractors'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/contractor-site-record-page.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
