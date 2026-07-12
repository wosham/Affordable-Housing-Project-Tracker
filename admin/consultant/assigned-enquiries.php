<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('consultant');

$viewerId = (int)Auth::id();
$viewerRole = 'consultant';
$isContactSupervisor = false;
$contactInboxPath = 'admin/consultant/assigned-enquiries.php';
$dashboardPath = 'admin/consultant/dashboard.php';
$dashboardLabel = 'Dashboard';

$pageTitle = 'Assigned Enquiries';
$pageDescription = 'Public contact enquiries assigned to you for follow-up and email reply.';
$adminRole = 'consultant';
$csrfForm = 'contact_inbox';
$contentClass = 'sa-contact-inbox-page';
$componentCss = ['contact-inbox'];
$pageScripts = ['contact-inbox'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Consultant', 'url' => Url::to('admin/consultant/dashboard.php')],
    ['label' => 'Assigned Enquiries'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/contact-enquiries-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
