<?php

class ContractorDashboard
{
    public static function projectIds(int $userId, string $role): array
    {
        if (strtolower($role) === 'superadmin') {
            $rows = Database::fetchAll('SELECT id FROM projects ORDER BY id ASC');
            return array_map('intval', array_column($rows, 'id'));
        }

        $rows = Database::fetchAll(
            "SELECT DISTINCT p.id
             FROM projects p
             LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = ? AND pa.status = 'active'
             WHERE p.contractor_id = ? OR pa.id IS NOT NULL
             ORDER BY p.id ASC",
            [$userId, $userId]
        );

        return array_map('intval', array_column($rows, 'id'));
    }

    public static function summary(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [
                'projects' => 0,
                'average_progress' => 0,
                'active_ipcs' => 0,
                'approved_unpaid_value' => 0,
                'paid_value' => 0,
                'pending_tasks' => 0,
                'boq_certified_value' => 0,
                'open_rfis' => 0,
                'pending_materials' => 0,
                'unread_notifications' => Notification::unreadCount($userId),
                'unread_messages' => self::unreadMessages($userId),
            ];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $project = Database::fetch(
            "SELECT COUNT(*) AS projects, ROUND(AVG(COALESCE(pct_complete, 0))) AS average_progress
             FROM projects
             WHERE id IN ({$in})",
            $bindings
        ) ?: [];

        $ipc = Database::fetch(
            "SELECT
                COUNT(*) AS total_ipcs,
                COALESCE(SUM(CASE WHEN status NOT IN ('paid','rejected') THEN 1 ELSE 0 END), 0) AS active_ipcs,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN net_amount ELSE 0 END), 0) AS approved_unpaid_value,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END), 0) AS paid_value
             FROM ipcs
             WHERE contractor_id = ? AND project_id IN ({$in})",
            array_merge([$userId], $bindings)
        ) ?: [];

        $programme = Database::fetch(
            "SELECT COALESCE(SUM(CASE WHEN COALESCE(status, '') NOT IN ('done','completed','complete','cancelled') THEN 1 ELSE 0 END), 0) AS pending_tasks
             FROM programme_tasks
             WHERE project_id IN ({$in})",
            $bindings
        ) ?: [];

        $boq = Database::fetch(
            "SELECT COALESCE(SUM(COALESCE(certified_qty, 0) * COALESCE(rate, 0)), 0) AS boq_certified_value
             FROM boq_items
             WHERE project_id IN ({$in})",
            $bindings
        ) ?: [];

        $rfi = Database::fetch(
            "SELECT COALESCE(SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END), 0) AS open_rfis
             FROM rfis
             WHERE project_id IN ({$in}) AND raised_by = ?",
            array_merge($bindings, [$userId])
        ) ?: [];

        $materials = Database::fetch(
            "SELECT COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_materials
             FROM material_approvals
             WHERE project_id IN ({$in}) AND submitted_by = ?",
            array_merge($bindings, [$userId])
        ) ?: [];

        return [
            'projects' => (int)($project['projects'] ?? 0),
            'average_progress' => (int)($project['average_progress'] ?? 0),
            'active_ipcs' => (int)($ipc['active_ipcs'] ?? 0),
            'approved_unpaid_value' => (float)($ipc['approved_unpaid_value'] ?? 0),
            'paid_value' => (float)($ipc['paid_value'] ?? 0),
            'pending_tasks' => (int)($programme['pending_tasks'] ?? 0),
            'boq_certified_value' => (float)($boq['boq_certified_value'] ?? 0),
            'open_rfis' => (int)($rfi['open_rfis'] ?? 0),
            'pending_materials' => (int)($materials['pending_materials'] ?? 0),
            'unread_notifications' => Notification::unreadCount($userId),
            'unread_messages' => self::unreadMessages($userId),
        ];
    }

    public static function projects(int $userId, string $role, int $limit = 8): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return Database::fetchAll(
            "SELECT
                p.id,
                p.name,
                p.status,
                p.pct_complete,
                p.contract_sum,
                p.est_delivery,
                p.location_label,
                c.name AS constituency_name,
                w.name AS ward_name,
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
             LEFT JOIN users consultant ON consultant.id = p.consultant_id
             WHERE p.id IN ({$in})
             ORDER BY p.status = 'active' DESC, p.updated_at DESC
             LIMIT " . max(1, $limit),
            $bindings
        );
    }

    public static function ipcSnapshot(int $userId, string $role, int $limit = 6): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return Database::fetchAll(
            "SELECT i.*, p.name AS project_name
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             WHERE i.contractor_id = ? AND i.project_id IN ({$in})
             ORDER BY i.updated_at DESC, i.id DESC
             LIMIT " . max(1, $limit),
            array_merge([$userId], $bindings)
        );
    }

    public static function programmeSnapshot(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return ['total' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0, 'critical' => 0, 'avg_progress' => 0];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status IN ('in_progress','active') THEN 1 ELSE 0 END), 0) AS in_progress,
                COALESCE(SUM(CASE WHEN status IN ('done','completed','complete') THEN 1 ELSE 0 END), 0) AS completed,
                COALESCE(SUM(CASE WHEN COALESCE(status, '') NOT IN ('done','completed','complete','cancelled') AND COALESCE(planned_end, end_date) < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue,
                COALESCE(SUM(CASE WHEN critical_path = 1 THEN 1 ELSE 0 END), 0) AS critical,
                ROUND(AVG(COALESCE(pct_complete, 0))) AS avg_progress
             FROM programme_tasks
             WHERE project_id IN ({$in})",
            $bindings
        ) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
            'overdue' => (int)($row['overdue'] ?? 0),
            'critical' => (int)($row['critical'] ?? 0),
            'avg_progress' => (int)($row['avg_progress'] ?? 0),
        ];
    }

    public static function siteSnapshot(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return ['labour' => 0, 'equipment' => 0, 'deliveries' => 0, 'incidents' => 0];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return [
            'labour' => self::scalar("SELECT COUNT(*) AS total FROM labour_register WHERE project_id IN ({$in}) AND diary_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", $bindings),
            'equipment' => self::scalar("SELECT COUNT(*) AS total FROM equipment_register WHERE project_id IN ({$in}) AND (date_off_site IS NULL OR date_off_site >= CURDATE())", $bindings),
            'deliveries' => self::scalar("SELECT COUNT(*) AS total FROM material_deliveries WHERE project_id IN ({$in}) AND delivery_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", $bindings),
            'incidents' => self::scalar("SELECT COUNT(*) AS total FROM hs_incidents WHERE project_id IN ({$in}) AND status <> 'closed'", $bindings),
        ];
    }

    public static function technicalSnapshot(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return ['materials' => 0, 'drawings' => 0, 'rfis' => 0, 'documents' => 0];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return [
            'materials' => self::scalar("SELECT COUNT(*) AS total FROM material_approvals WHERE project_id IN ({$in}) AND submitted_by = ? AND status = 'pending'", array_merge($bindings, [$userId])),
            'drawings' => self::scalar("SELECT COUNT(*) AS total FROM shop_drawings WHERE project_id IN ({$in}) AND submitted_by = ? AND status IN ('under-review','resubmit')", array_merge($bindings, [$userId])),
            'rfis' => self::scalar("SELECT COUNT(*) AS total FROM rfis WHERE project_id IN ({$in}) AND raised_by = ? AND status = 'open'", array_merge($bindings, [$userId])),
            'documents' => self::scalar("SELECT COUNT(*) AS total FROM documents WHERE project_id IN ({$in})", $bindings),
        ];
    }

    public static function commercialSnapshot(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return ['eots' => 0, 'variations' => 0, 'approved_unpaid' => 0, 'payments' => 0];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return [
            'eots' => self::scalar("SELECT COUNT(*) AS total FROM eot_requests WHERE project_id IN ({$in}) AND submitted_by = ? AND status = 'pending'", array_merge($bindings, [$userId])),
            'variations' => self::scalar("SELECT COUNT(*) AS total FROM variations WHERE project_id IN ({$in}) AND submitted_by = ? AND status = 'pending'", array_merge($bindings, [$userId])),
            'approved_unpaid' => self::scalar("SELECT COALESCE(SUM(net_amount), 0) AS total FROM ipcs WHERE project_id IN ({$in}) AND contractor_id = ? AND status = 'approved'", array_merge($bindings, [$userId])),
            'payments' => self::scalar("SELECT COALESCE(SUM(pay.amount), 0) AS total FROM payments pay JOIN ipcs i ON i.id = pay.ipc_id WHERE pay.project_id IN ({$in}) AND i.contractor_id = ?", array_merge($bindings, [$userId])),
        ];
    }

    public static function recentActivity(int $userId, string $role, int $limit = 8): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        $items = [];
        $queries = [
            ["IPC", "CONCAT('IPC #', i.ipc_number)", "i.status", "i.updated_at", "ipcs i", "i.project_id", "i.contractor_id = ?"],
            ["Material", "ma.material", "ma.status", "ma.submitted_date", "material_approvals ma", "ma.project_id", "ma.submitted_by = ?"],
            ["Shop Drawing", "sd.title", "sd.status", "sd.submitted_date", "shop_drawings sd", "sd.project_id", "sd.submitted_by = ?"],
            ["RFI", "r.subject", "r.status", "r.raised_date", "rfis r", "r.project_id", "r.raised_by = ?"],
            ["EOT", "CONCAT('EOT #', e.eot_number)", "e.status", "e.created_at", "eot_requests e", "e.project_id", "e.submitted_by = ?"],
            ["Variation", "CONCAT('Variation #', v.vo_number)", "v.status", "v.created_at", "variations v", "v.project_id", "v.submitted_by = ?"],
        ];

        foreach ($queries as [$type, $title, $status, $date, $table, $projectColumn, $ownerSql]) {
            try {
                $rows = Database::fetchAll(
                    "SELECT '{$type}' AS item_type, {$title} AS title, {$status} AS status, {$date} AS created_at, p.name AS project_name
                     FROM {$table}
                     JOIN projects p ON p.id = {$projectColumn}
                     WHERE {$projectColumn} IN ({$in}) AND {$ownerSql}
                     ORDER BY {$date} DESC
                     LIMIT " . max(1, $limit),
                    array_merge($bindings, [$userId])
                );
                $items = array_merge($items, $rows);
            } catch (Throwable) {
            }
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return array_slice($items, 0, $limit);
    }

    private static function unreadMessages(int $userId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM messages m
             JOIN message_participants mp ON mp.thread_id = m.thread_id AND mp.user_id = ?
             WHERE m.sender_id <> ?
               AND m.is_deleted = 0
               AND NOT EXISTS (SELECT 1 FROM message_reads mr WHERE mr.message_id = m.id AND mr.user_id = ?)",
            [$userId, $userId, $userId]
        );

        return (int)($row['total'] ?? 0);
    }

    private static function scalar(string $sql, array $bindings): int|float
    {
        try {
            $row = Database::fetch($sql, $bindings) ?: [];
            return is_numeric($row['total'] ?? null) ? (float)$row['total'] : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
