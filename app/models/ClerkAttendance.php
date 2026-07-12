<?php

class ClerkAttendance
{
    public static function projects(int $userId): array
    {
        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.pct_complete, p.est_delivery,
                    c.name AS constituency_name, w.name AS ward_name,
                    gf.latitude, gf.longitude, gf.radius_meters, gf.status AS geo_status
             FROM project_assignments pa
             JOIN projects p ON p.id = pa.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN geo_fences gf ON gf.project_id = p.id
             WHERE pa.user_id = ? AND pa.status = 'active'
             ORDER BY p.name ASC",
            [$userId]
        );
    }

    public static function defaultProjectId(int $userId, int $requestedId = 0): int
    {
        if ($requestedId > 0 && self::canAccessProject($userId, $requestedId)) {
            return $requestedId;
        }

        $project = self::projects($userId)[0] ?? null;
        return (int)($project['id'] ?? 0);
    }

    public static function canAccessProject(int $userId, int $projectId): bool
    {
        return Database::fetch(
            'SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? AND status = "active" LIMIT 1',
            [$userId, $projectId]
        ) !== null;
    }

    public static function project(int $userId, int $projectId): ?array
    {
        foreach (self::projects($userId) as $project) {
            if ((int)$project['id'] === $projectId) {
                return $project;
            }
        }
        return null;
    }

    public static function summary(int $userId, string $date, int $projectId = 0): array
    {
        $projects = self::projects($userId);
        $ids = array_map(static fn (array $project): int => (int)$project['id'], $projects);
        if ($projectId > 0 && in_array($projectId, $ids, true)) {
            $ids = [$projectId];
        }
        if ($ids === []) {
            return self::emptySummary();
        }

        [$in, $bindings] = self::inClause($ids);
        $recordBindings = array_merge([$date], $bindings);
        $gatewayBindings = array_merge([$date], $bindings);
        // Attendance coverage = people who can actually sign in (intern + clerk).
        $expected = Database::fetch(
            "SELECT COUNT(DISTINCT pa.user_id) AS total
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id AND u.status = 'active'
             JOIN roles r ON r.id = u.role_id
             WHERE pa.status = 'active' AND r.slug IN ('intern','clerk') AND pa.project_id IN ({$in})",
            $bindings
        ) ?: [];
        $records = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END), 0) AS present,
                    COALESCE(SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END), 0) AS late,
                    COALESCE(SUM(CASE WHEN ar.status IN ('geo-fail','outside-window') THEN 1 ELSE 0 END), 0) AS flagged,
                    COALESCE(SUM(CASE WHEN ar.review_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_review
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE ar.date = ? AND ar.project_id IN ({$in}) AND r.slug IN ('intern','clerk')",
            $recordBindings
        ) ?: [];

        // Ensure policy windows then count effectively open sites.
        $openCount = 0;
        $gatewayDays = 0;
        foreach ($ids as $pid) {
            $gw = AttendancePolicy::ensureProjectWindow((int)$pid, $date);
            if ($gw) {
                $gatewayDays++;
            }
            if (AttendancePolicy::isEffectivelyOpen($gw)) {
                $openCount++;
            }
        }

        $expectedCount = (int)($expected['total'] ?? 0);
        $signed = (int)($records['total'] ?? 0);
        return [
            'assigned_projects' => count($ids),
            'expected_people' => $expectedCount,
            'signed_in' => $signed,
            'present' => (int)($records['present'] ?? 0),
            'late' => (int)($records['late'] ?? 0),
            'flagged' => (int)($records['flagged'] ?? 0),
            'pending_review' => (int)($records['pending_review'] ?? 0),
            'missing' => max(0, $expectedCount - $signed),
            'gateway_days' => $gatewayDays,
            'open_gateways' => $openCount,
            'attendance_percent' => $expectedCount > 0 ? percentage(($signed / $expectedCount) * 100) : 0,
        ];
    }

    public static function gatewayRows(int $userId, string $date): array
    {
        $ids = array_map(static fn (array $project): int => (int)$project['id'], self::projects($userId));
        if ($ids === []) {
            return [];
        }
        foreach ($ids as $pid) {
            AttendancePolicy::ensureProjectWindow((int)$pid, $date);
        }
        [$in, $bindings] = self::inClause($ids);
        $rows = Database::fetchAll(
            "SELECT p.id AS project_id, p.name AS project_name, c.name AS constituency_name, w.name AS ward_name,
                    gf.radius_meters, gf.status AS geo_status,
                    ag.id AS gateway_id, ag.is_open, ag.opened_at, ag.closes_at, ag.notes,
                    ag.open_mode, ag.clerk_confirmed_at, ag.clerk_confirmed_by, ag.policy_open_time, ag.policy_close_time,
                    CONCAT(u.first_name, ' ', u.last_name) AS opened_by_name,
                    COUNT(ar.id) AS signed_in,
                    COALESCE(SUM(CASE WHEN ar.status IN ('geo-fail','outside-window') THEN 1 ELSE 0 END), 0) AS flags
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN geo_fences gf ON gf.project_id = p.id
             LEFT JOIN attendance_gateways ag ON ag.project_id = p.id AND ag.date = ?
             LEFT JOIN users u ON u.id = ag.opened_by
             LEFT JOIN attendance_records ar ON ar.project_id = p.id AND ar.date = ?
             WHERE p.id IN ({$in})
             GROUP BY p.id, p.name, c.name, w.name, gf.radius_meters, gf.status, ag.id, ag.is_open, ag.opened_at, ag.closes_at, ag.notes,
                      ag.open_mode, ag.clerk_confirmed_at, ag.clerk_confirmed_by, ag.policy_open_time, ag.policy_close_time, opened_by_name
             ORDER BY p.name ASC",
            array_merge([$date, $date], $bindings)
        );
        return array_map(static function (array $row): array {
            $row['is_effectively_open'] = AttendancePolicy::isEffectivelyOpen([
                'is_open' => $row['is_open'] ?? 0,
                'closes_at' => $row['closes_at'] ?? null,
            ]) ? 1 : 0;
            $row['clerk_confirmed'] = !empty($row['clerk_confirmed_at']) ? 1 : 0;
            return $row;
        }, $rows);
    }

    public static function records(int $userId, string $date, int $projectId = 0, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $ids = array_map(static fn (array $project): int => (int)$project['id'], self::projects($userId));
        if ($projectId > 0 && in_array($projectId, $ids, true)) {
            $ids = [$projectId];
        }
        if ($ids === []) {
            return [];
        }
        [$in, $bindings] = self::inClause($ids);
        $where = ["ar.date = ?", "ar.project_id IN ({$in})"];
        $params = array_merge([$date], $bindings);

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'ar.status = ?';
            $params[] = $status;
        }
        $role = trim((string)($filters['role'] ?? ''));
        if ($role !== '') {
            $where[] = 'r.slug = ?';
            $params[] = $role;
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($params, $term, $term, $term, $term);
        }

        return Database::fetchAll(
            AttendanceRecord::detailedSqlForManager() . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY ar.signin_time DESC, ar.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function countRecords(int $userId, string $date, int $projectId = 0, array $filters = []): int
    {
        $ids = array_map(static fn (array $project): int => (int)$project['id'], self::projects($userId));
        if ($projectId > 0 && in_array($projectId, $ids, true)) {
            $ids = [$projectId];
        }
        if ($ids === []) {
            return 0;
        }
        [$in, $bindings] = self::inClause($ids);
        $where = ["ar.date = ?", "ar.project_id IN ({$in})"];
        $params = array_merge([$date], $bindings);

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'ar.status = ?';
            $params[] = $status;
        }
        $role = trim((string)($filters['role'] ?? ''));
        if ($role !== '') {
            $where[] = 'r.slug = ?';
            $params[] = $role;
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($params, $term, $term, $term, $term);
        }

        $row = Database::fetch(
            'SELECT COUNT(*) AS total
             FROM attendance_records ar
             JOIN users u ON u.id = ar.user_id
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN projects p ON p.id = ar.project_id
             WHERE ' . implode(' AND ', $where),
            $params
        );
        return (int)($row['total'] ?? 0);
    }

    public static function expectedPeople(int $userId, int $projectId, string $date): array
    {
        if (!self::canAccessProject($userId, $projectId)) {
            return [];
        }

        return Database::fetchAll(
            "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.email, u.phone,
                    r.slug AS role_slug, r.name AS role_name,
                    ar.id AS attendance_id, ar.signin_time, ar.status, ar.review_status
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id AND u.status = 'active'
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN attendance_records ar ON ar.user_id = u.id AND ar.date = ?
             WHERE pa.project_id = ? AND pa.status = 'active' AND r.slug IN ('intern','clerk')
             ORDER BY FIELD(r.slug, 'clerk', 'intern'), u.first_name ASC",
            [$date, $projectId]
        );
    }

    /**
     * Clerk confirms site open inside County Director policy window.
     * Clerks cannot set open/close times.
     */
    public static function openGateway(int $userId, int $projectId, string $date, string $closeTime = '', ?string $notes = null): int
    {
        if (!self::canAccessProject($userId, $projectId)) {
            throw new RuntimeException('You are not assigned to this project.');
        }
        // Close time from policy only (ignore client-supplied close time).
        return AttendancePolicy::clerkConfirm($userId, $projectId, $date, $notes);
    }

    public static function closeGateway(int $userId, int $projectId, string $date, ?string $notes): int
    {
        if (!self::canAccessProject($userId, $projectId)) {
            throw new RuntimeException('You are not assigned to this project.');
        }
        $gateway = AttendanceGateway::forProjectDate($projectId, $date);
        if (!$gateway || (int)$gateway['is_open'] !== 1) {
            throw new RuntimeException('No open attendance gateway was found for this project today.');
        }

        // Emergency early close only — does not change County Director policy times for future days.
        Database::query(
            'UPDATE attendance_gateways SET is_open = 0, closes_at = NOW(), notes = COALESCE(NULLIF(?, ""), notes) WHERE id = ?',
            [$notes ?? 'Closed early by site clerk.', (int)$gateway['id']]
        );
        return (int)$gateway['id'];
    }

    private static function emptySummary(): array
    {
        return ['assigned_projects' => 0, 'expected_people' => 0, 'signed_in' => 0, 'present' => 0, 'late' => 0, 'flagged' => 0, 'pending_review' => 0, 'missing' => 0, 'gateway_days' => 0, 'open_gateways' => 0, 'attendance_percent' => 0];
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
