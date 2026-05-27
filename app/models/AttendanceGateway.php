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
}
