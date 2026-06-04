<?php

class ManagerAttendance
{
    public const ROLES = ['', 'clerk', 'intern', 'contractor', 'consultant'];
    public const STATUSES = ['', 'present', 'absent', 'geo-fail', 'outside-window', 'late'];

    public static function projects(int $userId, string $role): array
    {
        $projects = ProjectAssignment::managerProjects($userId, $role);
        if ($projects === []) {
            return [];
        }

        $ids = array_map(static fn (array $project): int => (int)$project['id'], $projects);
        [$in, $bindings] = self::inClause($ids);
        $metaRows = Database::fetchAll(
            "SELECT p.id,
                    COUNT(DISTINCT CASE WHEN aur.id IS NOT NULL THEN au.id END) AS expected_people,
                    COUNT(DISTINCT ag.id) AS gateway_days
             FROM projects p
             LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.status = 'active'
             LEFT JOIN users au ON au.id = pa.user_id AND au.status = 'active'
             LEFT JOIN roles aur ON aur.id = au.role_id AND aur.slug IN ('clerk','intern','contractor','consultant')
             LEFT JOIN attendance_gateways ag ON ag.project_id = p.id
             WHERE p.id IN ({$in})
             GROUP BY p.id",
            $bindings
        );

        $meta = [];
        foreach ($metaRows as $row) {
            $meta[(int)$row['id']] = $row;
        }

        foreach ($projects as &$project) {
            $row = $meta[(int)$project['id']] ?? [];
            $project['expected_people'] = (int)($row['expected_people'] ?? 0);
            $project['gateway_days'] = (int)($row['gateway_days'] ?? 0);
        }
        unset($project);

        return $projects;
    }

    public static function summary(int $userId, string $role, string $date, array $filters = []): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        [$recordWhere, $recordBindings] = self::recordFilterSql($userId, $role, array_merge($filters, ['date' => $date]));
        $projectWhere = $scopeSql !== '' ? ' WHERE ' . $scopeSql : '';
        $projectAnd = $scopeSql !== '' ? ' AND ' . $scopeSql : '';

        $assigned = Database::fetch(
            "SELECT COUNT(DISTINCT p.id) AS projects,
                    COUNT(DISTINCT CASE WHEN aur.id IS NOT NULL THEN au.id END) AS expected_people
             FROM projects p
             LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.status = 'active'
             LEFT JOIN users au ON au.id = pa.user_id AND au.status = 'active'
             LEFT JOIN roles aur ON aur.id = au.role_id AND aur.slug IN ('clerk','intern','contractor','consultant')
             {$projectWhere}",
            $scopeBindings
        ) ?: [];

        $records = Database::fetch(
            "SELECT COUNT(*) AS total_records,
                    COALESCE(SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END), 0) AS present,
                    COALESCE(SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END), 0) AS late,
                    COALESCE(SUM(CASE WHEN ar.status = 'geo-fail' THEN 1 ELSE 0 END), 0) AS geo_fail,
                    COALESCE(SUM(CASE WHEN ar.status = 'outside-window' THEN 1 ELSE 0 END), 0) AS outside_window,
                    COUNT(DISTINCT ar.project_id) AS projects_with_records
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             JOIN projects p ON p.id = ar.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             {$recordWhere}",
            $recordBindings
        ) ?: [];

        $gateways = Database::fetch(
            "SELECT COUNT(*) AS gateways_today,
                    COALESCE(SUM(CASE WHEN ag.is_open = 1 AND ag.closes_at >= NOW() THEN 1 ELSE 0 END), 0) AS open_gateways
             FROM attendance_gateways ag
             JOIN projects p ON p.id = ag.project_id
             WHERE ag.date = ?{$projectAnd}",
            array_merge([$date], $scopeBindings)
        ) ?: [];

        $expected = (int)($assigned['expected_people'] ?? 0);
        $signed = (int)($records['total_records'] ?? 0);

        return [
            'assigned_projects' => (int)($assigned['projects'] ?? 0),
            'expected_people' => $expected,
            'total_records' => $signed,
            'present' => (int)($records['present'] ?? 0),
            'late' => (int)($records['late'] ?? 0),
            'geo_fail' => (int)($records['geo_fail'] ?? 0),
            'outside_window' => (int)($records['outside_window'] ?? 0),
            'missing_signins' => max(0, $expected - $signed),
            'projects_with_records' => (int)($records['projects_with_records'] ?? 0),
            'gateways_today' => (int)($gateways['gateways_today'] ?? 0),
            'open_gateways' => (int)($gateways['open_gateways'] ?? 0),
            'attendance_percent' => $expected > 0 ? percentage(($signed / $expected) * 100) : 0,
        ];
    }

    public static function projectCoverage(int $userId, string $role, string $date, array $filters = []): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        $where = [];
        $bindings = [$date, $date];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }

        return Database::fetchAll(
            "SELECT p.id AS project_id, p.name AS project_name, c.name AS constituency_name,
                    gf.id AS geo_id, gf.status AS geo_status, gf.radius_meters,
                    ag.id AS gateway_id, ag.is_open, ag.opened_at, ag.closes_at,
                    CONCAT(opener.first_name, ' ', opener.last_name) AS opened_by_name,
                    COUNT(DISTINCT CASE WHEN aur.id IS NOT NULL THEN au.id END) AS expected_people,
                    COUNT(DISTINCT CASE WHEN aur.id IS NOT NULL THEN ar.user_id END) AS signed_people,
                    COALESCE(SUM(CASE WHEN aur.id IS NOT NULL AND ar.status = 'late' THEN 1 ELSE 0 END), 0) AS late,
                    COALESCE(SUM(CASE WHEN aur.id IS NOT NULL AND ar.status IN ('geo-fail','outside-window') THEN 1 ELSE 0 END), 0) AS gps_flags
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN geo_fences gf ON gf.project_id = p.id
             LEFT JOIN attendance_gateways ag ON ag.project_id = p.id AND ag.date = ?
             LEFT JOIN users opener ON opener.id = ag.opened_by
             LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.status = 'active'
             LEFT JOIN users au ON au.id = pa.user_id AND au.status = 'active'
             LEFT JOIN roles aur ON aur.id = au.role_id AND aur.slug IN ('clerk','intern','contractor','consultant')
             LEFT JOIN attendance_records ar ON ar.project_id = p.id AND ar.date = ? AND ar.user_id = au.id
             " . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . "
             GROUP BY p.id, p.name, c.name, gf.id, gf.status, gf.radius_meters, ag.id, ag.is_open, ag.opened_at, ag.closes_at, opened_by_name
             ORDER BY p.name ASC",
            $bindings
        );
    }

    public static function records(int $userId, string $role, array $filters, int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::recordFilterSql($userId, $role, $filters);
        return Database::fetchAll(AttendanceRecord::detailedSqlForManager() . $where . ' ORDER BY ar.date DESC, ar.signin_time DESC, ar.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countRecords(int $userId, string $role, array $filters): int
    {
        [$where, $bindings] = self::recordFilterSql($userId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             JOIN projects p ON p.id = ar.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             {$where}",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function exceptions(int $userId, string $role, string $date, array $filters = []): array
    {
        return [
            'missing' => self::missingPeople($userId, $role, $date, $filters),
            'gps' => self::gpsExceptions($userId, $role, $date, $filters),
            'gateways' => self::unsupervisedGateways($userId, $role, $date, $filters),
            'geo' => self::geoIssues($userId, $role, $filters),
            'noGateway' => self::projectsWithoutGateway($userId, $role, $date, $filters),
        ];
    }

    public static function gateways(int $userId, string $role, string $date, array $filters = []): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        $where = ['ag.date = ?'];
        $bindings = [$date];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'ag.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        return Database::fetchAll(
            "SELECT ag.*, p.name AS project_name, c.name AS constituency_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS opened_by_name
             FROM attendance_gateways ag
             JOIN projects p ON p.id = ag.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users u ON u.id = ag.opened_by
             WHERE " . implode(' AND ', $where) . '
             ORDER BY p.name ASC',
            $bindings
        );
    }

    public static function missingGeoFences(int $userId, string $role, array $filters = []): array
    {
        return self::geoIssues($userId, $role, $filters, 100);
    }

    private static function missingPeople(int $userId, string $role, string $date, array $filters): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        $where = ['pa.status = "active"', 'u.status = "active"', 'ar.id IS NULL'];
        $bindings = [$date];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['role'])) {
            $where[] = 'r.slug = ?';
            $bindings[] = (string)$filters['role'];
        }

        return Database::fetchAll(
            "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.email, r.slug AS role_slug,
                    p.name AS project_name, c.name AS constituency_name
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id
             JOIN roles r ON r.id = u.role_id
             JOIN projects p ON p.id = pa.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN attendance_records ar ON ar.user_id = u.id AND ar.project_id = p.id AND ar.date = ?
             WHERE " . implode(' AND ', $where) . '
             ORDER BY p.name ASC, r.slug ASC, u.first_name ASC
             LIMIT 12',
            $bindings
        );
    }

    private static function gpsExceptions(int $userId, string $role, string $date, array $filters): array
    {
        return self::records($userId, $role, array_merge($filters, ['date' => $date, 'gps' => 'flagged']), 12, 0);
    }

    private static function unsupervisedGateways(int $userId, string $role, string $date, array $filters): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        $where = ['ag.date = ?', 'ar.id IS NULL'];
        $bindings = [$date];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        return Database::fetchAll(
            "SELECT ag.*, p.name AS project_name, CONCAT(u.first_name, ' ', u.last_name) AS opened_by_name
             FROM attendance_gateways ag
             JOIN projects p ON p.id = ag.project_id
             JOIN users u ON u.id = ag.opened_by
             LEFT JOIN attendance_records ar ON ar.user_id = ag.opened_by AND ar.project_id = ag.project_id AND ar.date = ag.date
             WHERE " . implode(' AND ', $where) . '
             ORDER BY ag.opened_at DESC
             LIMIT 12',
            $bindings
        );
    }

    private static function geoIssues(int $userId, string $role, array $filters, int $limit = 12): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        $where = ['(gf.id IS NULL OR gf.status IN ("missing","needs-review"))'];
        $bindings = [];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        return Database::fetchAll(
            "SELECT p.id, p.name AS project_name, c.name AS constituency_name, gf.status AS geo_status, gf.radius_meters
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN geo_fences gf ON gf.project_id = p.id
             WHERE " . implode(' AND ', $where) . '
             ORDER BY p.name ASC
             LIMIT ' . max(1, $limit),
            $bindings
        );
    }

    private static function projectsWithoutGateway(int $userId, string $role, string $date, array $filters): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        $where = ['ag.id IS NULL'];
        $bindings = [$date];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        return Database::fetchAll(
            "SELECT p.id, p.name AS project_name, c.name AS constituency_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN attendance_gateways ag ON ag.project_id = p.id AND ag.date = ?
             WHERE " . implode(' AND ', $where) . '
             ORDER BY p.name ASC
             LIMIT 12',
            $bindings
        );
    }

    private static function recordFilterSql(int $userId, string $role, array $filters): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }

        if (!empty($filters['date'])) {
            $where[] = 'ar.date = ?';
            $bindings[] = (string)$filters['date'];
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }
        if (!empty($filters['role'])) {
            $where[] = 'r.slug = ?';
            $bindings[] = (string)$filters['role'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ar.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['gps']) && $filters['gps'] === 'flagged') {
            $where[] = 'ar.status IN ("geo-fail","outside-window")';
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function scopeSql(int $userId, string $role, string $projectAlias): array
    {
        if (strtolower($role) === 'superadmin') {
            return ['', []];
        }

        return [
            "EXISTS (
                SELECT 1 FROM project_assignments scope_pa
                WHERE scope_pa.project_id = {$projectAlias}.id
                  AND scope_pa.user_id = ?
                  AND scope_pa.status = 'active'
            )",
            [$userId],
        ];
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return ['0', []];
        }

        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
