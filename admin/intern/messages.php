<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('intern');

$pageTitle = 'Messages';
$pageDescription = 'Project learning conversations and direct messages.';
$adminRole = 'intern';
$csrfForm = 'messages';
$contentClass = 'messages-admin-page intern-page';
$componentCss = ['messages'];
$pageScripts = ['messages'];
$activeInternHub = 'messages';
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Intern', 'url' => Url::to('admin/intern/dashboard.php')],
    ['label' => 'Messages'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/intern-hub.php';
include __DIR__ . '/../../app/partials/admin/messages-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
