<?php

require_once __DIR__ . '/../app/core/bootstrap.php';
Guard::auth();

$role = Auth::role();
$userId = (int)(Auth::id() ?? 0);

if ($role === 'superadmin' && $userId > 0) {
    Response::redirect(Url::to('admin/superadmin/user-edit.php?id=' . $userId));
}

Session::flash('error', 'Profile editing for this role is not available yet.');
Response::redirect(Url::to('admin/index.php'));
