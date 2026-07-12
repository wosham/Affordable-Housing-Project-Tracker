<?php

class ProjectAccess extends Model
{
    public const SCOPED_ROLES = ['manager', 'consultant', 'contractor', 'clerk', 'intern'];

    public static function scopedRoles(): array
    {
        return self::SCOPED_ROLES;
    }

    public static function roleIsScoped(string $role): bool
    {
        return in_array(strtolower($role), self::SCOPED_ROLES, true);
    }

    public static function visibleProjectIds(int $userId, string $role): array
    {
        $role = strtolower($role);
        if ($role === 'superadmin' || $role === 'finance') {
            return array_map('intval', Database::query('SELECT id FROM projects ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN));
        }

        if (!self::roleIsScoped($role) || $userId <= 0) {
            return [];
        }

        $rows = Database::query(
            "SELECT DISTINCT p.id
             FROM projects p
             LEFT JOIN project_assignments pa
                ON pa.project_id = p.id
               AND pa.user_id = ?
               AND pa.status = 'active'
             WHERE pa.id IS NOT NULL
                OR (? = 'consultant' AND p.consultant_id = ?)
                OR (? = 'contractor' AND p.contractor_id = ?)
             ORDER BY p.name ASC",
            [$userId, $role, $userId, $role, $userId]
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_unique(array_map('intval', $rows)));
    }

    public static function canViewProject(int $userId, string $role, int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $role = strtolower($role);
        if ($role === 'superadmin' || $role === 'finance') {
            return Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId]) !== null;
        }

        return Database::fetch(
            "SELECT p.id
             FROM projects p
             LEFT JOIN project_assignments pa
                ON pa.project_id = p.id
               AND pa.user_id = ?
               AND pa.status = 'active'
             WHERE p.id = ?
               AND (
                    pa.id IS NOT NULL
                    OR (? = 'consultant' AND p.consultant_id = ?)
                    OR (? = 'contractor' AND p.contractor_id = ?)
               )
             LIMIT 1",
            [$userId, $projectId, $role, $userId, $role, $userId]
        ) !== null;
    }

    public static function canManageProject(int $userId, string $role, int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $role = strtolower($role);
        if ($role === 'superadmin') {
            return Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId]) !== null;
        }

        if ($role === 'finance') {
            return false;
        }

        return self::canViewProject($userId, $role, $projectId);
    }

    public static function scopeSql(int $userId, string $role, string $projectAlias = 'p'): array
    {
        $role = strtolower($role);
        if ($role === 'superadmin' || $role === 'finance') {
            return ['', []];
        }

        return [
            "EXISTS (
                SELECT 1 FROM project_assignments access_pa
                WHERE access_pa.project_id = {$projectAlias}.id
                  AND access_pa.user_id = ?
                  AND access_pa.status = 'active'
            )
            OR (? = 'consultant' AND {$projectAlias}.consultant_id = ?)
            OR (? = 'contractor' AND {$projectAlias}.contractor_id = ?)",
            [$userId, $role, $userId, $role, $userId],
        ];
    }

    public static function requireProject(int $userId, string $role, int $projectId): void
    {
        if (!self::canViewProject($userId, $role, $projectId)) {
            http_response_code(403);
            if (str_starts_with((string)($_SERVER['REQUEST_URI'] ?? ''), '/api/')) {
                Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
            }
            Session::flash('error', 'You are not assigned to that project.');
            Response::redirect(Url::to('admin/index.php'));
        }
    }
}
