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
        $email = self::normaliseEmail($email);
        $bindings = [$email];
        $sql = 'SELECT id FROM users WHERE email = ?';
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return Database::fetch($sql . ' LIMIT 1', $bindings) !== null;
    }

    public static function phoneExists(string $phone, ?int $exceptId = null): bool
    {
        $phone = self::normalisePhone($phone);
        if ($phone === '') {
            return false;
        }

        $bindings = [$phone];
        $sql = 'SELECT id FROM users WHERE phone = ?';
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return Database::fetch($sql . ' LIMIT 1', $bindings) !== null;
    }

    public static function normaliseEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function isValidGmail(string $email): bool
    {
        $email = self::normaliseEmail($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && str_ends_with($email, '@gmail.com');
    }

    public static function normalisePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (preg_match('/^0([17]\d{8})$/', $digits, $match)) {
            return '+254' . $match[1];
        }
        if (preg_match('/^254([17]\d{8})$/', $digits, $match)) {
            return '+254' . $match[1];
        }
        if (preg_match('/^([17]\d{8})$/', $digits, $match)) {
            return '+254' . $match[1];
        }

        return $phone;
    }

    public static function isValidKenyanPhone(string $phone): bool
    {
        return (bool)preg_match('/^\+254[17][0-9]{8}$/', self::normalisePhone($phone));
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
                r.color AS role_color,
                (
                    SELECT COUNT(*)
                    FROM project_assignments pa
                    WHERE pa.user_id = u.id AND pa.status = 'active'
                ) AS active_project_count
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

        if (!empty($filters['project_id'])) {
            $where[] = '(r.slug IN ("superadmin", "finance") OR EXISTS (SELECT 1 FROM project_assignments pa_filter WHERE pa_filter.user_id = u.id AND pa_filter.project_id = ? AND pa_filter.status = "active"))';
            $bindings[] = (int)$filters['project_id'];
        }

        if (!empty($filters['constituency_id'])) {
            $where[] = '(r.slug IN ("superadmin", "finance") OR EXISTS (
                SELECT 1
                FROM project_assignments pa_filter
                INNER JOIN projects p_filter ON p_filter.id = pa_filter.project_id
                WHERE pa_filter.user_id = u.id
                  AND pa_filter.status = "active"
                  AND p_filter.constituency_id = ?
            ))';
            $bindings[] = (int)$filters['constituency_id'];
        }

        if (!empty($filters['assignment_state'])) {
            if ($filters['assignment_state'] === 'assigned') {
                $where[] = '(r.slug IN ("superadmin", "finance") OR EXISTS (SELECT 1 FROM project_assignments pa_filter WHERE pa_filter.user_id = u.id AND pa_filter.status = "active"))';
            } elseif ($filters['assignment_state'] === 'unassigned') {
                $where[] = 'r.slug NOT IN ("superadmin", "finance") AND NOT EXISTS (SELECT 1 FROM project_assignments pa_filter WHERE pa_filter.user_id = u.id AND pa_filter.status = "active")';
            }
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
