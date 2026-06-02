<?php

declare(strict_types=1);

final class RoleAccess
{
    private const ROLE_DASHBOARDS = [
        'superadmin' => 'admin/superadmin/dashboard.php',
        'manager' => 'admin/manager/dashboard.php',
        'consultant' => 'admin/consultant/dashboard.php',
        'contractor' => 'admin/contractor/dashboard.php',
        'clerk' => 'admin/clerk/dashboard.php',
        'finance' => 'admin/finance/dashboard.php',
        'intern' => 'admin/intern/dashboard.php',
    ];

    public static function area(string $role): array
    {
        $role = strtolower(trim($role));

        if ($role === 'superadmin') {
            return ['superadmin'];
        }

        return ['superadmin', $role];
    }

    public static function dashboardFor(?string $role): string
    {
        $role = strtolower((string) $role);

        return self::ROLE_DASHBOARDS[$role] ?? 'admin/login.php';
    }
}
