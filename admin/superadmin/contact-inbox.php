<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$viewerId = (int)Auth::id();
$viewerRole = 'superadmin';
$isContactSupervisor = true;
$contactInboxPath = 'admin/superadmin/contact-inbox.php';
$dashboardPath = 'admin/superadmin/dashboard.php';
$dashboardLabel = 'Dashboard';

$pageTitle = 'Contact Inbox';
$pageDescription = 'Public contact submissions, read status, assignment, archive and email response tracking.';
$adminRole = 'superadmin';
$csrfForm = 'contact_inbox';
$contentClass = 'sa-contact-inbox-page';
$componentCss = ['contact-inbox'];
$pageScripts = ['contact-inbox'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Contact Inbox'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/contact-enquiries-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
