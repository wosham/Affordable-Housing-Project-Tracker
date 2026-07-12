<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('contractor');

$viewerId = (int)Auth::id();
$viewerRole = 'contractor';
$isContactSupervisor = false;
$contactInboxPath = 'admin/contractor/assigned-enquiries.php';
$dashboardPath = 'admin/contractor/dashboard.php';
$dashboardLabel = 'Dashboard';

$pageTitle = 'Assigned Enquiries';
$pageDescription = 'Public contact enquiries assigned to you for follow-up and email reply.';
$adminRole = 'contractor';
$csrfForm = 'contact_inbox';
$contentClass = 'sa-contact-inbox-page';
$componentCss = ['contact-inbox'];
$pageScripts = ['contact-inbox'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Assigned Enquiries'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/contact-enquiries-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
