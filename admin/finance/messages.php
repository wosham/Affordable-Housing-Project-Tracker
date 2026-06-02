<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('finance'));

$pageTitle = 'Messages';
$pageDescription = 'Finance workflow conversations and direct messages.';
$adminRole = 'finance';
$csrfForm = 'messages';
$contentClass = 'messages-admin-page';
$componentCss = ['messages'];
$pageScripts = ['messages'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Finance', 'url' => Url::to('admin/finance/dashboard.php')],
    ['label' => 'Messages'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/messages-centre.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';
