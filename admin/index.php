<?php
// AHPTC Admin Dashboard — Entry Router
// Redirects authenticated user to their role-specific dashboard
require_once __DIR__ . '/../app/core/bootstrap.php';
Guard::auth();

$role = Auth::role();

$roleDashboards = [
    'superadmin'  => 'superadmin/dashboard.php',
    'manager'     => 'manager/dashboard.php',
    'consultant'  => 'consultant/dashboard.php',
    'contractor'  => 'contractor/dashboard.php',
    'clerk'       => 'clerk/dashboard.php',
    'finance'     => 'finance/dashboard.php',
    'intern'      => 'intern/dashboard.php',
];

if (!is_string($role) || !isset($roleDashboards[$role])) {
    Session::flash('error', 'Your account role is not configured. Contact the administrator.');
    Response::redirect(Url::to('admin/auth/unauthorised.php'));
}

$target = $roleDashboards[$role];
Response::redirect(Url::to('admin/' . $target));
