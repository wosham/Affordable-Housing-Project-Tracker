<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('contractor'));

$pageTitle = 'Messages';
$pageDescription = 'Site delivery conversations and direct messages.';
$adminRole = 'contractor';
$csrfForm = 'messages';
$contentClass = 'messages-admin-page';
$componentCss = ['messages'];
$pageScripts = ['messages'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Contractor', 'url' => Url::to('admin/contractor/dashboard.php')],
    ['label' => 'Messages'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/messages-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
