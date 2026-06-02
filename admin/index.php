<?php
// AHPTC Admin Dashboard - Entry Router
// Redirects authenticated users to their role-specific dashboard.
require_once __DIR__ . '/../app/core/bootstrap.php';
Guard::auth();

$role = Auth::role();
$target = RoleAccess::dashboardFor(is_string($role) ? $role : null);

if ($target === 'admin/login.php') {
    Session::flash('error', 'Your account role is not configured. Contact the administrator.');
    Response::redirect(Url::to('admin/auth/unauthorised.php'));
}

Response::redirect(Url::to($target));
