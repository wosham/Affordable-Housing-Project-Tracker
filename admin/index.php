<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
Guard::auth();

$role = strtolower(trim((string)Auth::role()));
$target = RoleAccess::dashboardFor($role !== '' ? $role : null);

if ($target === 'admin/login.php') {
    Logger::log('invalid_role_redirect', 'auth', (int)(Auth::id() ?? 0), ['role' => $role]);
    Session::flash('error', 'Your account access is not ready. Contact the administrator.');
    Response::redirect(Url::to('admin/auth/unauthorised.php'));
}

Response::redirect(Url::to($target));
