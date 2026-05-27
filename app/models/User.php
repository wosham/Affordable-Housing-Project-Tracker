<?php

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE u.email = ? LIMIT 1', [$email]);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE u.id = ? LIMIT 1', [$id]);
    }

    public static function withRoles(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY u.updated_at DESC, u.id DESC' . $limitSql, $bindings);
    }

    public static function countWithFilters(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("
            SELECT COUNT(*) AS aggregate
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            {$where}
        ", $bindings);

        return (int)($row['aggregate'] ?? 0);
    }

    public static function stats(): array
    {
        return Database::fetch("
            SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended_users,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_users,
                SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) AS public_profiles
            FROM users
        ") ?: [];
    }

    public static function roleDistribution(): array
    {
        return Database::fetchAll("
            SELECT r.name, r.slug, COUNT(u.id) AS total
            FROM roles r
            LEFT JOIN users u ON u.role_id = r.id
            GROUP BY r.id, r.name, r.slug
            ORDER BY r.id ASC
        ");
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $bindings = [$email];
        $sql = 'SELECT id FROM users WHERE email = ?';
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return Database::fetch($sql . ' LIMIT 1', $bindings) !== null;
    }

    public static function activeSuperadminCount(): int
    {
        $row = Database::fetch("
            SELECT COUNT(*) AS total
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE r.slug = 'superadmin' AND u.status = 'active'
        ");

        return (int)($row['total'] ?? 0);
    }

    public static function superadminCount(?int $exceptId = null): int
    {
        $bindings = [];
        $exceptSql = '';

        if ($exceptId !== null) {
            $exceptSql = ' AND u.id <> ?';
            $bindings[] = $exceptId;
        }

        $row = Database::fetch("
            SELECT COUNT(*) AS total
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE r.slug = 'superadmin'{$exceptSql}
        ", $bindings);

        return (int)($row['total'] ?? 0);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function byRole(string $role): array
    {
        return self::withRoles(['role' => $role]);
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                u.*,
                CONCAT(u.first_name, ' ', u.last_name) AS name,
                r.name AS role_name,
                r.slug AS role_slug,
                r.color AS role_color
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
        ";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['role'])) {
            $where[] = 'r.slug = ?';
            $bindings[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'u.status = ?';
            $bindings[] = $filters['status'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.job_title LIKE ? OR u.department LIKE ? OR r.name LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
