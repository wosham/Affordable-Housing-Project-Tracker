<?php

class AttendanceRecord extends Model
{
    protected static string $table = 'attendance_records';

    public static function todayForUser(int $userId, int $projectId): ?array
    {
        return Database::fetch(
            'SELECT * FROM attendance_records WHERE user_id = ? AND project_id = ? AND date = CURDATE() LIMIT 1',
            [$userId, $projectId]
        );
    }

    public static function todayForWorkLocation(int $userId, int $workLocationId): ?array
    {
        return Database::fetch(
            'SELECT * FROM attendance_records WHERE user_id = ? AND work_location_id = ? AND date = CURDATE() LIMIT 1',
            [$userId, $workLocationId]
        );
    }

    public static function todayForAnyTarget(int $userId): ?array
    {
        return Database::fetch(
            'SELECT ar.*, COALESCE(p.name, wl.name, "Attendance location") AS target_name,
                    CASE WHEN ar.work_location_id IS NOT NULL THEN "work_location" ELSE "project" END AS target_type
             FROM attendance_records ar
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             WHERE ar.user_id = ? AND ar.date = CURDATE()
             LIMIT 1',
            [$userId]
        );
    }

    public static function monthlyForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT ar.*, COALESCE(p.name, wl.name) AS project_name
             FROM attendance_records ar
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             WHERE ar.user_id = ? AND ar.date >= DATE_SUB(CURDATE(), INTERVAL 31 DAY)
             ORDER BY ar.date DESC, ar.signin_time DESC',
            [$userId]
        );
    }

    public static function detailed(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY ar.date DESC, ar.signin_time DESC, ar.id DESC' . $limitSql, $bindings);
    }

    public static function countDetailed(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch(
            'SELECT COUNT(*) AS aggregate
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             ' . $where,
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }

    public static function stats(string $date, array $filters = []): array
    {
        [$where, $bindings] = self::filterSql(array_merge($filters, ['date' => $date]));
        return Database::fetch(
            'SELECT
                COUNT(*) AS total_records,
                SUM(CASE WHEN ar.status = "present" THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN ar.status = "absent" THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN ar.status = "geo-fail" THEN 1 ELSE 0 END) AS geo_fail,
                SUM(CASE WHEN ar.status = "late" THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN ar.status = "outside-window" THEN 1 ELSE 0 END) AS outside_window,
                COUNT(DISTINCT ar.project_id) AS projects_with_records
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN projects p ON p.id = ar.project_id
             LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             ' . $where,
            $bindings
        ) ?: [];
    }

    public static function projectSummaries(string $date, array $filters = []): array
    {
        [$where, $bindings] = self::filterSql(array_merge($filters, ['date' => $date]));
        return Database::fetchAll(
            'SELECT p.id AS project_id, p.name AS project_name, c.name AS constituency_name,
                    COUNT(ar.id) AS total_records,
                    SUM(CASE WHEN ar.status = "present" THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN ar.status = "absent" THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN ar.status = "geo-fail" THEN 1 ELSE 0 END) AS geo_fail,
                    SUM(CASE WHEN ar.status = "late" THEN 1 ELSE 0 END) AS late,
                    SUM(CASE WHEN ar.status = "outside-window" THEN 1 ELSE 0 END) AS outside_window
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             JOIN projects p ON p.id = ar.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             ' . $where . '
             GROUP BY p.id, p.name, c.name
             ORDER BY total_records DESC, p.name ASC',
            $bindings
        );
    }

    public static function detailedSqlForManager(): string
    {
        return self::selectSql();
    }

    private static function selectSql(): string
    {
        return 'SELECT ar.*, COALESCE(p.name, wl.name) AS project_name, p.slug AS project_slug,
                    wl.name AS work_location_name, wl.slug AS work_location_slug, wl.location_type,
                    c.name AS constituency_name, w.name AS ward_name,
                    CONCAT(u.first_name, " ", u.last_name) AS user_name,
                    u.email, u.phone, u.avatar,
                    r.slug AS role_slug, r.name AS role_name,
                    ag.is_open AS gateway_is_open, ag.opened_at, ag.closes_at,
                    COALESCE(gf.site_name, wl.name) AS site_name,
                    COALESCE(gf.radius_meters, wl.radius_meters) AS radius_meters,
                    COALESCE(gf.status, wl.status) AS geo_status
                FROM attendance_records ar
                JOIN users u ON u.id = ar.user_id
                JOIN roles r ON r.id = u.role_id
                LEFT JOIN projects p ON p.id = ar.project_id
                LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
                LEFT JOIN constituencies c ON c.id = p.constituency_id
                LEFT JOIN wards w ON w.id = p.ward_id
                LEFT JOIN attendance_gateways ag ON ag.id = ar.gateway_id
                LEFT JOIN geo_fences gf ON gf.project_id = p.id';
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['date'])) {
            $where[] = 'ar.date = ?';
            $bindings[] = $filters['date'];
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['work_location_id'])) {
            $where[] = 'wl.id = ?';
            $bindings[] = (int)$filters['work_location_id'];
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }
        if (!empty($filters['role'])) {
            $where[] = 'r.slug = ?';
            $bindings[] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ar.status = ?';
            $bindings[] = $filters['status'];
        }
        if (!empty($filters['review_status'])) {
            $where[] = 'ar.review_status = ?';
            $bindings[] = $filters['review_status'];
        }
        if (!empty($filters['gps']) && $filters['gps'] === 'flagged') {
            $where[] = 'ar.status IN ("geo-fail", "outside-window")';
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR wl.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
