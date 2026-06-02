<?php

class ManagerDashboard
{
    public static function projectIds(int $userId, string $role): array
    {
        if ($role === 'superadmin') {
            $rows = Database::fetchAll('SELECT id FROM projects ORDER BY id ASC');
            return array_map('intval', array_column($rows, 'id'));
        }

        $rows = Database::fetchAll(
            "SELECT DISTINCT p.id
             FROM projects p
             INNER JOIN project_assignments pa ON pa.project_id = p.id
             WHERE pa.user_id = ?
             ORDER BY p.id ASC",
            [$userId]
        );

        return array_map('intval', array_column($rows, 'id'));
    }

    public static function summary(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [
                'assigned_projects' => 0,
                'active_projects' => 0,
                'delayed_projects' => 0,
                'average_progress' => 0,
                'ipc_queue' => 0,
                'milestones_due' => 0,
                'programme_overdue' => 0,
                'attendance_today' => 0,
                'attendance_flags' => 0,
                'unread_notifications' => Notification::unreadCount($userId),
            ];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $project = Database::fetch("
            SELECT
                COUNT(*) AS assigned_projects,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_projects,
                SUM(CASE WHEN est_delivery IS NOT NULL AND est_delivery < CURDATE() AND status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) AS delayed_projects,
                ROUND(AVG(COALESCE(pct_complete, 0))) AS average_progress
            FROM projects
            WHERE id IN ({$in})
        ", $bindings) ?: [];

        $ipc = Database::fetch("
            SELECT COUNT(*) AS ipc_queue
            FROM ipcs
            WHERE project_id IN ({$in}) AND status IN ('submitted','clerk-endorsed','certified')
        ", $bindings) ?: [];

        $milestones = Database::fetch("
            SELECT COUNT(*) AS milestones_due
            FROM milestones
            WHERE project_id IN ({$in})
              AND status <> 'done'
              AND target_date IS NOT NULL
              AND target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ", array_merge($bindings, [SystemConfig::int('programme.due_soon_days', 7)])) ?: [];

        $programme = Database::fetch("
            SELECT COUNT(*) AS programme_overdue
            FROM programme_tasks
            WHERE project_id IN ({$in})
              AND COALESCE(status, '') NOT IN ('done','completed','complete')
              AND COALESCE(end_date, planned_end) IS NOT NULL
              AND COALESCE(end_date, planned_end) < CURDATE()
        ", $bindings) ?: [];

        $attendance = Database::fetch("
            SELECT
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS attendance_today,
                SUM(CASE WHEN status IN ('late','geo-fail','outside-window','absent') THEN 1 ELSE 0 END) AS attendance_flags
            FROM attendance_records
            WHERE project_id IN ({$in}) AND date = CURDATE()
        ", $bindings) ?: [];

        return [
            'assigned_projects' => (int)($project['assigned_projects'] ?? 0),
            'active_projects' => (int)($project['active_projects'] ?? 0),
            'delayed_projects' => (int)($project['delayed_projects'] ?? 0),
            'average_progress' => (int)($project['average_progress'] ?? 0),
            'ipc_queue' => (int)($ipc['ipc_queue'] ?? 0),
            'milestones_due' => (int)($milestones['milestones_due'] ?? 0),
            'programme_overdue' => (int)($programme['programme_overdue'] ?? 0),
            'attendance_today' => (int)($attendance['attendance_today'] ?? 0),
            'attendance_flags' => (int)($attendance['attendance_flags'] ?? 0),
            'unread_notifications' => Notification::unreadCount($userId),
        ];
    }

    public static function projects(int $userId, string $role, int $limit = 8): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return Database::fetchAll("
            SELECT
                p.id,
                p.name,
                p.status,
                p.pct_complete,
                p.contract_sum,
                p.est_delivery,
                p.location_label,
                c.name AS constituency_name,
                w.name AS ward_name,
                contractor.email AS contractor_email,
                CONCAT(COALESCE(contractor.first_name, ''), ' ', COALESCE(contractor.last_name, '')) AS contractor_name,
                consultant.email AS consultant_email,
                CONCAT(COALESCE(consultant.first_name, ''), ' ', COALESCE(consultant.last_name, '')) AS consultant_name,
                (
                    SELECT m.label
                    FROM milestones m
                    WHERE m.project_id = p.id AND m.status <> 'done'
                    ORDER BY m.target_date IS NULL ASC, m.target_date ASC, m.sequence ASC
                    LIMIT 1
                ) AS next_milestone,
                (
                    SELECT m.target_date
                    FROM milestones m
                    WHERE m.project_id = p.id AND m.status <> 'done'
                    ORDER BY m.target_date IS NULL ASC, m.target_date ASC, m.sequence ASC
                    LIMIT 1
                ) AS next_milestone_date
            FROM projects p
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN users contractor ON contractor.id = p.contractor_id
            LEFT JOIN users consultant ON consultant.id = p.consultant_id
            WHERE p.id IN ({$in})
            ORDER BY p.status = 'active' DESC, p.updated_at DESC
            LIMIT " . max(1, $limit), $bindings);
    }

    public static function milestoneAlerts(int $userId, string $role, int $limit = 8): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return Database::fetchAll("
            SELECT m.*, p.name AS project_name
            FROM milestones m
            INNER JOIN projects p ON p.id = m.project_id
            WHERE m.project_id IN ({$in})
              AND m.status <> 'done'
              AND m.target_date IS NOT NULL
            ORDER BY
              CASE WHEN m.target_date < CURDATE() THEN 0 ELSE 1 END,
              m.target_date ASC
            LIMIT " . max(1, $limit), $bindings);
    }

    public static function ipcQueue(int $userId, string $role, int $limit = 6): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return Database::fetchAll("
            SELECT
                i.*,
                p.name AS project_name,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS contractor_name
            FROM ipcs i
            INNER JOIN projects p ON p.id = i.project_id
            LEFT JOIN users u ON u.id = i.contractor_id
            WHERE i.project_id IN ({$in}) AND i.status IN ('submitted','clerk-endorsed','certified')
            ORDER BY i.submitted_at DESC, i.id DESC
            LIMIT " . max(1, $limit), $bindings);
    }

    public static function programmeSnapshot(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return ['total' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0, 'avg_progress' => 0];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $row = Database::fetch("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status IN ('in_progress','active') THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status IN ('done','completed','complete') THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN COALESCE(status, '') NOT IN ('done','completed','complete') AND COALESCE(end_date, planned_end) < CURDATE() THEN 1 ELSE 0 END) AS overdue,
                ROUND(AVG(COALESCE(pct_complete, 0))) AS avg_progress
            FROM programme_tasks
            WHERE project_id IN ({$in})
        ", $bindings) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
            'overdue' => (int)($row['overdue'] ?? 0),
            'avg_progress' => (int)($row['avg_progress'] ?? 0),
        ];
    }

    public static function riskFeed(int $userId, string $role, int $limit = 8): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $queries = [
            ["hs_incidents", "HS Incident", "description", "severity", "created_at"],
            ["non_conformance_reports", "Non-Conformance", "description", "status", "created_at"],
            ["defects", "Defect", "description", "status", "created_at"],
            ["eot_requests", "EOT Request", "reason", "status", "created_at"],
            ["rfis", "RFI", "subject", "status", "created_at"],
        ];

        $items = [];
        foreach ($queries as [$table, $type, $titleColumn, $statusColumn, $dateColumn]) {
            try {
                $rows = Database::fetchAll("
                    SELECT '{$type}' AS item_type, {$titleColumn} AS title, {$statusColumn} AS status, {$dateColumn} AS created_at, p.name AS project_name
                    FROM {$table} x
                    INNER JOIN projects p ON p.id = x.project_id
                    WHERE x.project_id IN ({$in})
                    ORDER BY {$dateColumn} DESC
                    LIMIT " . max(1, $limit), $bindings);
                $items = array_merge($items, $rows);
            } catch (Throwable) {
            }
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return array_slice($items, 0, $limit);
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
