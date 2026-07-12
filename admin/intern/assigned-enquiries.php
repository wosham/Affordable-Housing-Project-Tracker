<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$viewerId = (int)Auth::id();
$viewerRole = 'intern';
$isContactSupervisor = false;
$contactInboxPath = 'admin/intern/assigned-enquiries.php';
$dashboardPath = 'admin/intern/dashboard.php';
$dashboardLabel = 'Dashboard';

$pageTitle = 'Assigned Enquiries';
$pageDescription = 'Public contact enquiries assigned to you for follow-up and email reply.';
$adminRole = 'intern';
$csrfForm = 'contact_inbox';
$contentClass = 'sa-contact-inbox-page intern-page';
$componentCss = ['contact-inbox'];
$pageScripts = ['contact-inbox'];
$activeInternHub = 'enquiries';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'Assigned Enquiries'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/intern-hub.php';
include __DIR__ . '/../../app/partials/admin/contact-enquiries-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
