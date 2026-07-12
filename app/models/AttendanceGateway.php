<?php

class AttendanceGateway extends Model
{
    protected static string $table = 'attendance_gateways';

    public static function todayForProject(int $projectId): ?array
    {
        return self::forProjectDate($projectId, date('Y-m-d'));
    }

    public static function forProjectDate(int $projectId, string $date): ?array
    {
        return Database::fetch(
            'SELECT ag.*, p.name AS project_name, CONCAT(u.first_name, " ", u.last_name) AS opened_by_name
             FROM attendance_gateways ag
             JOIN projects p ON p.id = ag.project_id
             LEFT JOIN users u ON u.id = ag.opened_by
             WHERE ag.project_id = ? AND ag.date = ?
             LIMIT 1',
            [$projectId, $date]
        );
    }

    public static function forWorkLocationDate(int $workLocationId, string $date): ?array
    {
        return Database::fetch(
            'SELECT ag.*, wl.name AS work_location_name, wl.location_type, CONCAT(u.first_name, " ", u.last_name) AS opened_by_name
             FROM attendance_gateways ag
             JOIN work_locations wl ON wl.id = ag.work_location_id
             LEFT JOIN users u ON u.id = ag.opened_by
             WHERE ag.work_location_id = ? AND ag.date = ?
             LIMIT 1',
            [$workLocationId, $date]
        );
    }

    public static function forTargetDate(string $targetType, int $targetId, string $date): ?array
    {
        return $targetType === 'work_location'
            ? self::forWorkLocationDate($targetId, $date)
            : self::forProjectDate($targetId, $date);
    }

    public static function openProjectByAdmin(int $actorId, int $projectId, string $date, string $closeTime, ?string $notes): int
    {
        self::validateGatewayInput($date, $closeTime);
        self::assertProjectExists($projectId);
        $existing = self::forProjectDate($projectId, $date);
        $closesAt = $date . ' ' . $closeTime . ':00';

        if ($existing) {
            Database::query(
                'UPDATE attendance_gateways SET opened_by = ?, opened_at = NOW(), closes_at = ?, is_open = 1, notes = ? WHERE id = ?',
                [$actorId, $closesAt, $notes, (int)$existing['id']]
            );
            return (int)$existing['id'];
        }

        Database::query(
            'INSERT INTO attendance_gateways (project_id, work_location_id, date, opened_by, opened_at, closes_at, is_open, notes) VALUES (?, NULL, ?, ?, NOW(), ?, 1, ?)',
            [$projectId, $date, $actorId, $closesAt, $notes]
        );
        return (int)Database::lastInsertId();
    }

    public static function closeProjectByAdmin(int $actorId, int $projectId, string $date, ?string $notes): int
    {
        self::assertProjectExists($projectId);
        $gateway = self::forProjectDate($projectId, $date);
        if (!$gateway) {
            throw new RuntimeException('No gateway exists for this project and date.');
        }

        Database::query(
            'UPDATE attendance_gateways SET is_open = 0, closes_at = NOW(), notes = COALESCE(NULLIF(?, ""), notes) WHERE id = ?',
            [$notes ?? '', (int)$gateway['id']]
        );
        return (int)$gateway['id'];
    }

    public static function openWorkLocationByAdmin(int $actorId, int $workLocationId, string $date, string $closeTime, ?string $notes): int
    {
        self::validateGatewayInput($date, $closeTime);
        self::assertWorkLocationExists($workLocationId);
        $existing = self::forWorkLocationDate($workLocationId, $date);
        $closesAt = $date . ' ' . $closeTime . ':00';

        if ($existing) {
            Database::query(
                'UPDATE attendance_gateways SET opened_by = ?, opened_at = NOW(), closes_at = ?, is_open = 1, notes = ? WHERE id = ?',
                [$actorId, $closesAt, $notes, (int)$existing['id']]
            );
            return (int)$existing['id'];
        }

        Database::query(
            'INSERT INTO attendance_gateways (project_id, work_location_id, date, opened_by, opened_at, closes_at, is_open, notes) VALUES (NULL, ?, ?, ?, NOW(), ?, 1, ?)',
            [$workLocationId, $date, $actorId, $closesAt, $notes]
        );
        return (int)Database::lastInsertId();
    }

    public static function closeWorkLocationByAdmin(int $actorId, int $workLocationId, string $date, ?string $notes): int
    {
        self::assertWorkLocationExists($workLocationId);
        $gateway = self::forWorkLocationDate($workLocationId, $date);
        if (!$gateway) {
            throw new RuntimeException('No gateway exists for this work location and date.');
        }

        Database::query(
            'UPDATE attendance_gateways SET is_open = 0, closes_at = NOW(), notes = COALESCE(NULLIF(?, ""), notes) WHERE id = ?',
            [$notes ?? '', (int)$gateway['id']]
        );
        return (int)$gateway['id'];
    }

    public static function openAllProjectsByAdmin(int $actorId, string $date, string $closeTime, ?string $notes): array
    {
        $projects = Database::fetchAll(
            'SELECT DISTINCT p.id
             FROM projects p
             JOIN project_assignments pa ON pa.project_id = p.id AND pa.status = "active"
             JOIN users u ON u.id = pa.user_id AND u.status = "active"
             JOIN roles r ON r.id = u.role_id
             WHERE r.slug IN ("clerk", "intern")
             ORDER BY p.id ASC'
        );
        $gatewayIds = [];
        foreach ($projects as $project) {
            $gatewayIds[] = self::openProjectByAdmin($actorId, (int)$project['id'], $date, $closeTime, $notes);
        }
        return $gatewayIds;
    }

    public static function closeAllProjectsByAdmin(int $actorId, string $date, ?string $notes): array
    {
        $rows = Database::fetchAll('SELECT project_id FROM attendance_gateways WHERE date = ? AND project_id IS NOT NULL', [$date]);
        $gatewayIds = [];
        foreach ($rows as $row) {
            $gatewayIds[] = self::closeProjectByAdmin($actorId, (int)$row['project_id'], $date, $notes);
        }
        return $gatewayIds;
    }

    public static function isOpen(int $projectId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM attendance_gateways
             WHERE project_id = ? AND date = CURDATE() AND is_open = 1 AND closes_at >= NOW()
             LIMIT 1',
            [$projectId]
        );

        return $row !== null;
    }

    private static function validateGatewayInput(string $date, string $closeTime): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new RuntimeException('Choose a valid attendance date.');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $closeTime)) {
            throw new RuntimeException('Choose a valid close time.');
        }

        $closesAt = $date . ' ' . $closeTime . ':00';
        if (strtotime($closesAt) <= time()) {
            throw new RuntimeException('Choose a close time that is still ahead.');
        }
    }

    private static function assertProjectExists(int $projectId): void
    {
        $project = Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId]);
        if (!$project) {
            throw new RuntimeException('The selected project was not found.');
        }
    }

    private static function assertWorkLocationExists(int $workLocationId): void
    {
        $location = Database::fetch('SELECT id FROM work_locations WHERE id = ? AND status <> "inactive" LIMIT 1', [$workLocationId]);
        if (!$location) {
            throw new RuntimeException('The selected work location was not found or is inactive.');
        }
    }
}
