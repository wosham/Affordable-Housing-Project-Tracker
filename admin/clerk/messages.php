<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('clerk');

$pageTitle = 'Messages';
$pageDescription = 'Project-scoped staff messaging for managers, consultants, contractors and site interns.';
$adminRole = 'clerk';
$csrfForm = 'messages';
$contentClass = 'messages-admin-page';
$componentCss = ['messages'];
$pageScripts = ['messages'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Clerk of Works', 'url' => Url::to('admin/clerk/dashboard.php')],
    ['label' => 'Messages'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/messages-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
