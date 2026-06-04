<?php

class ConsultantDashboard
{
    public static function summary(int $userId, string $role): array
    {
        [$scopeSql, $scopeBindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;
        $and = $scopeSql === '' ? '' : ' AND ' . $scopeSql;

        $projects = Database::fetch(
            "SELECT
                COUNT(DISTINCT p.id) AS assigned_projects,
                COALESCE(AVG(p.pct_complete), 0) AS average_progress,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END), 0) AS active_projects,
                COALESCE(SUM(CASE WHEN p.est_delivery IS NOT NULL AND p.est_delivery < CURDATE() AND p.status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END), 0) AS delayed_projects
             FROM projects p
             {$where}",
            $scopeBindings
        ) ?: [];

        $ipc = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN i.status = 'clerk-endorsed' THEN 1 ELSE 0 END), 0) AS certification_queue,
                COALESCE(SUM(CASE WHEN i.status = 'submitted' THEN 1 ELSE 0 END), 0) AS awaiting_verification,
                COALESCE(SUM(CASE WHEN i.status = 'certified' THEN 1 ELSE 0 END), 0) AS certified_waiting_manager,
                COALESCE(SUM(CASE WHEN i.status = 'clerk-endorsed' THEN i.net_amount ELSE 0 END), 0) AS certification_value
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             WHERE 1 = 1{$and}",
            $scopeBindings
        ) ?: [];

        $boq = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN bi.review_status = 'pending' THEN 1 ELSE 0 END), 0) AS boq_pending,
                COALESCE(SUM(CASE WHEN COALESCE(bi.certified_qty, 0) > COALESCE(bi.quantity, 0) OR COALESCE(bi.paid_qty, 0) > COALESCE(bi.certified_qty, 0) OR bi.risk_status <> 'normal' THEN 1 ELSE 0 END), 0) AS boq_risks
             FROM boq_items bi
             JOIN projects p ON p.id = bi.project_id
             WHERE 1 = 1{$and}",
            $scopeBindings
        ) ?: [];

        $technical = self::technicalSummary($scopeSql, $scopeBindings);
        $quality = self::qualitySummary($scopeSql, $scopeBindings);
        $programme = self::programmeSnapshot($userId, $role);

        return self::ints(array_merge([
            'assigned_projects' => 0,
            'average_progress' => 0,
            'active_projects' => 0,
            'delayed_projects' => 0,
            'certification_queue' => 0,
            'awaiting_verification' => 0,
            'certified_waiting_manager' => 0,
            'certification_value' => 0,
            'boq_pending' => 0,
            'boq_risks' => 0,
            'technical_pending' => 0,
            'quality_alerts' => 0,
            'programme_overdue' => 0,
            'unread_notifications' => Notification::unreadCount($userId),
            'unread_messages' => MessageThread::countForUser($userId, ['box' => 'unread']),
        ], $projects, $ipc, $boq, [
            'technical_pending' => $technical['pending'],
            'quality_alerts' => $quality['alerts'],
            'programme_overdue' => $programme['overdue'],
        ]));
    }

    public static function projects(int $userId, string $role, int $limit = 8): array
    {
        [$scopeSql, $bindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        return Database::fetchAll(
            "SELECT
                p.id, p.name, p.status, p.pct_complete, p.contract_sum, p.est_delivery, p.current_milestone,
                c.name AS constituency_name,
                w.name AS ward_name,
                COALESCE(CONCAT(contractor.first_name, ' ', contractor.last_name), p.contractor_name, 'Contractor') AS contractor_name,
                (SELECT m.label FROM milestones m WHERE m.project_id = p.id AND m.status <> 'done' ORDER BY m.target_date ASC, m.id ASC LIMIT 1) AS next_milestone,
                (SELECT m.target_date FROM milestones m WHERE m.project_id = p.id AND m.status <> 'done' ORDER BY m.target_date ASC, m.id ASC LIMIT 1) AS next_milestone_date,
                (SELECT COUNT(*) FROM ipcs i WHERE i.project_id = p.id AND i.status = 'clerk-endorsed') AS certification_queue,
                (SELECT COUNT(*) FROM defects d WHERE d.project_id = p.id AND d.status IN ('open','in-progress')) AS open_defects,
                (SELECT COUNT(*) FROM programme_tasks pt WHERE pt.project_id = p.id AND pt.status NOT IN ('complete','cancelled') AND pt.planned_end IS NOT NULL AND pt.planned_end < CURDATE()) AS overdue_tasks
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN users contractor ON contractor.id = p.contractor_id
             {$where}
             ORDER BY FIELD(p.status, 'active', 'planning', 'on_hold', 'stalled', 'completed', 'cancelled'), p.name ASC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function ipcQueue(int $userId, string $role, int $limit = 6): array
    {
        [$scopeSql, $bindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' AND ' . $scopeSql;

        return Database::fetchAll(
            "SELECT
                i.id, i.ipc_number, i.net_amount, i.status, i.submitted_at, i.certified_at,
                p.name AS project_name,
                COALESCE(CONCAT(contractor.first_name, ' ', contractor.last_name), 'Contractor') AS contractor_name
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             JOIN users contractor ON contractor.id = i.contractor_id
             WHERE i.status IN ('clerk-endorsed','submitted','certified'){$where}
             ORDER BY FIELD(i.status, 'clerk-endorsed', 'submitted', 'certified'), COALESCE(i.submitted_at, i.updated_at) DESC, i.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function programmeSnapshot(int $userId, string $role): array
    {
        [$scopeSql, $bindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        $row = Database::fetch(
            "SELECT
                COUNT(pt.id) AS total,
                COALESCE(SUM(CASE WHEN pt.status IN ('in-progress','active') THEN 1 ELSE 0 END), 0) AS in_progress,
                COALESCE(SUM(CASE WHEN pt.status = 'complete' THEN 1 ELSE 0 END), 0) AS completed,
                COALESCE(SUM(CASE WHEN pt.status NOT IN ('complete','cancelled') AND pt.planned_end IS NOT NULL AND pt.planned_end < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue,
                COALESCE(SUM(CASE WHEN pt.critical_path = 1 AND pt.status NOT IN ('complete','cancelled') THEN 1 ELSE 0 END), 0) AS critical,
                COALESCE(AVG(pt.pct_complete), 0) AS avg_progress
             FROM projects p
             LEFT JOIN programme_tasks pt ON pt.project_id = p.id
             {$where}",
            $bindings
        ) ?: [];

        return self::ints(array_merge([
            'total' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'critical' => 0,
            'avg_progress' => 0,
        ], $row));
    }

    public static function technicalQueue(int $userId, string $role, int $limit = 8): array
    {
        [$scopeSql, $scopeBindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '1 = 1' : $scopeSql;
        $sql = "
            SELECT 'Material approval' AS item_type, ma.id, ma.material AS title, ma.status, ma.submitted_date AS due_date, ma.submitted_date AS created_at, p.name AS project_name, 'admin/consultant/material-approvals.php' AS link
            FROM material_approvals ma JOIN projects p ON p.id = ma.project_id
            WHERE ma.status = 'pending' AND {$where}
            UNION ALL
            SELECT 'Shop drawing' AS item_type, sd.id, sd.title, sd.status, sd.submitted_date AS due_date, sd.created_at, p.name AS project_name, 'admin/consultant/shop-drawings.php' AS link
            FROM shop_drawings sd JOIN projects p ON p.id = sd.project_id
            WHERE sd.status IN ('under-review','resubmit') AND {$where}
            UNION ALL
            SELECT 'Variation' AS item_type, v.id, CONCAT('Variation #', v.vo_number) AS title, v.status, NULL AS due_date, v.created_at, p.name AS project_name, 'admin/consultant/variations.php' AS link
            FROM variations v JOIN projects p ON p.id = v.project_id
            WHERE v.status = 'pending' AND {$where}
            UNION ALL
            SELECT 'EOT request' AS item_type, e.id, CONCAT('EOT request ', e.days_requested, ' days') AS title, e.status, NULL AS due_date, e.created_at, p.name AS project_name, 'admin/consultant/eot-review.php' AS link
            FROM eot_requests e JOIN projects p ON p.id = e.project_id
            WHERE e.status = 'pending' AND {$where}
            ORDER BY created_at DESC
            LIMIT " . max(1, min(20, $limit));

        return Database::fetchAll($sql, array_merge($scopeBindings, $scopeBindings, $scopeBindings, $scopeBindings));
    }

    public static function qualityAlerts(int $userId, string $role, int $limit = 8): array
    {
        [$scopeSql, $scopeBindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '1 = 1' : $scopeSql;
        $sql = "
            SELECT 'Defect' AS item_type, d.id, d.description AS title, d.status, d.severity AS priority, d.raised_date AS item_date, d.created_at, p.name AS project_name, 'admin/consultant/defects.php' AS link
            FROM defects d JOIN projects p ON p.id = d.project_id
            WHERE d.status IN ('open','in-progress','resolved') AND {$where}
            UNION ALL
            SELECT 'NCR' AS item_type, n.id, n.description AS title, n.status, n.severity AS priority, n.raised_date AS item_date, n.created_at, p.name AS project_name, 'admin/consultant/non-conformance.php' AS link
            FROM non_conformance_reports n JOIN projects p ON p.id = n.project_id
            WHERE n.status <> 'closed' AND {$where}
            UNION ALL
            SELECT 'Quality test' AS item_type, qt.id, qt.test_type AS title, qt.pass_fail AS status, qt.pass_fail AS priority, qt.test_date AS item_date, qt.created_at, p.name AS project_name, 'admin/consultant/quality-register.php' AS link
            FROM quality_tests qt JOIN projects p ON p.id = qt.project_id
            WHERE qt.pass_fail IN ('fail','pending') AND {$where}
            UNION ALL
            SELECT 'Inspection' AS item_type, itp.id, itp.activity AS title, COALESCE(NULLIF(itp.outcome, ''), 'pending') AS status, CASE WHEN itp.witness_required = 1 THEN 'witness' ELSE 'normal' END AS priority, itp.inspection_date AS item_date, itp.created_at, p.name AS project_name, 'admin/consultant/inspection-test-plans.php' AS link
            FROM inspection_test_plans itp JOIN projects p ON p.id = itp.project_id
            WHERE (itp.outcome IS NULL OR itp.outcome = '') AND {$where}
            ORDER BY created_at DESC
            LIMIT " . max(1, min(20, $limit));

        return Database::fetchAll($sql, array_merge($scopeBindings, $scopeBindings, $scopeBindings, $scopeBindings));
    }

    public static function recentDocuments(int $userId, string $role, int $limit = 6): array
    {
        [$scopeSql, $bindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        return Database::fetchAll(
            "SELECT d.id, d.category, d.original_name, d.version, d.size, d.created_at, p.name AS project_name
             FROM documents d
             JOIN projects p ON p.id = d.project_id
             {$where}
             ORDER BY d.created_at DESC, d.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function hasConsultantUser(): bool
    {
        return Database::fetch(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'consultant' AND u.status = 'active' LIMIT 1"
        ) !== null;
    }

    private static function technicalSummary(string $scopeSql, array $scopeBindings): array
    {
        $where = $scopeSql === '' ? '1 = 1' : $scopeSql;
        $sql = "
            SELECT COALESCE(SUM(total), 0) AS pending FROM (
                SELECT COUNT(*) AS total FROM material_approvals ma JOIN projects p ON p.id = ma.project_id WHERE ma.status = 'pending' AND {$where}
                UNION ALL SELECT COUNT(*) FROM shop_drawings sd JOIN projects p ON p.id = sd.project_id WHERE sd.status IN ('under-review','resubmit') AND {$where}
                UNION ALL SELECT COUNT(*) FROM variations v JOIN projects p ON p.id = v.project_id WHERE v.status = 'pending' AND {$where}
                UNION ALL SELECT COUNT(*) FROM eot_requests e JOIN projects p ON p.id = e.project_id WHERE e.status = 'pending' AND {$where}
            ) pending_items";

        $row = Database::fetch($sql, array_merge($scopeBindings, $scopeBindings, $scopeBindings, $scopeBindings)) ?: [];
        return ['pending' => (int)($row['pending'] ?? 0)];
    }

    private static function qualitySummary(string $scopeSql, array $scopeBindings): array
    {
        $where = $scopeSql === '' ? '1 = 1' : $scopeSql;
        $sql = "
            SELECT COALESCE(SUM(total), 0) AS alerts FROM (
                SELECT COUNT(*) AS total FROM defects d JOIN projects p ON p.id = d.project_id WHERE d.status IN ('open','in-progress','resolved') AND {$where}
                UNION ALL SELECT COUNT(*) FROM non_conformance_reports n JOIN projects p ON p.id = n.project_id WHERE n.status <> 'closed' AND {$where}
                UNION ALL SELECT COUNT(*) FROM quality_tests qt JOIN projects p ON p.id = qt.project_id WHERE qt.pass_fail IN ('fail','pending') AND {$where}
                UNION ALL SELECT COUNT(*) FROM inspection_test_plans itp JOIN projects p ON p.id = itp.project_id WHERE (itp.outcome IS NULL OR itp.outcome = '') AND {$where}
            ) quality_items";

        $row = Database::fetch($sql, array_merge($scopeBindings, $scopeBindings, $scopeBindings, $scopeBindings)) ?: [];
        return ['alerts' => (int)($row['alerts'] ?? 0)];
    }

    private static function projectScopeSql(int $userId, string $role, string $projectAlias): array
    {
        if (strtolower($role) === 'superadmin') {
            return ['', []];
        }

        return [
            "({$projectAlias}.consultant_id = ? OR EXISTS (
                SELECT 1 FROM project_assignments consultant_pa
                WHERE consultant_pa.project_id = {$projectAlias}.id
                  AND consultant_pa.user_id = ?
                  AND consultant_pa.status = 'active'
            ))",
            [$userId, $userId],
        ];
    }

    private static function ints(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                $row[$key] = str_contains((string)$value, '.') ? (float)$value : (int)$value;
            }
        }

        if (isset($row['average_progress'])) {
            $row['average_progress'] = percentage((float)$row['average_progress']);
        }
        if (isset($row['avg_progress'])) {
            $row['avg_progress'] = percentage((float)$row['avg_progress']);
        }

        return $row;
    }
}
