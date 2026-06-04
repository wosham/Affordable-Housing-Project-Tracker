<?php

class ManagerProject
{
    public static function idsFor(int $userId, string $role): array
    {
        return ManagerDashboard::projectIds($userId, $role);
    }

    public static function canView(int $projectId, int $userId, string $role): bool
    {
        return ProjectAssignment::canManageProject($userId, $projectId, $role);
    }

    public static function summary(int $userId, string $role): array
    {
        $base = ManagerDashboard::summary($userId, $role);
        $coverage = ProjectAssignment::summaryForManager($userId, $role);

        return array_merge($base, [
            'missing_clerks' => (int)($coverage['missing_clerks'] ?? 0),
            'missing_interns' => (int)($coverage['missing_interns'] ?? 0),
        ]);
    }

    public static function list(int $userId, string $role, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);

        return Database::fetchAll(self::selectSql() . $where . self::orderSql((string)($filters['risk'] ?? '')) . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function count(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}",
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }

    public static function detail(int $projectId, int $userId, string $role): ?array
    {
        if (!self::canView($projectId, $userId, $role)) {
            return null;
        }

        $project = Database::fetch(self::selectSql() . ' WHERE p.id = ? LIMIT 1', [$projectId]);
        if (!$project) {
            return null;
        }

        return [
            'project' => self::payload($project),
            'team' => array_map([ProjectAssignment::class, 'payload'], ProjectAssignment::usersForProject($projectId)),
            'milestones' => self::milestones($projectId, 6),
            'programme' => self::programme($projectId),
            'ipcs' => self::ipcs($projectId, 5),
            'boq' => self::boq($projectId),
            'attendance' => self::attendanceToday($projectId),
            'notes' => self::notes($projectId, $userId, $role, 6),
            'risks' => self::risks($projectId, 6),
        ];
    }

    public static function notes(int $projectId, int $userId, string $role, int $limit = 10): array
    {
        if (!self::canView($projectId, $userId, $role)) {
            return [];
        }

        try {
            return Database::fetchAll(
                "SELECT mpn.*, CONCAT(u.first_name, ' ', u.last_name) AS manager_name
                 FROM manager_project_notes mpn
                 LEFT JOIN users u ON u.id = mpn.manager_id
                 WHERE mpn.project_id = ?
                 ORDER BY FIELD(mpn.severity, 'critical','warning','normal'), mpn.created_at DESC
                 LIMIT " . max(1, min(50, $limit)),
                [$projectId]
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function saveNote(int $projectId, int $managerId, array $data): int
    {
        Database::query(
            "INSERT INTO manager_project_notes (project_id, manager_id, note_type, title, body, severity, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $projectId,
                $managerId,
                self::option((string)($data['note_type'] ?? 'monitoring'), ['monitoring', 'risk', 'delivery', 'attendance', 'finance'], 'monitoring'),
                trim((string)($data['title'] ?? 'Project note')),
                trim((string)($data['body'] ?? '')),
                self::option((string)($data['severity'] ?? 'normal'), ['normal', 'warning', 'critical'], 'normal'),
                self::option((string)($data['status'] ?? 'open'), ['open', 'watching', 'closed'], 'open'),
            ]
        );

        return (int)Database::lastInsertId();
    }

    public static function payload(array $row): array
    {
        $progress = percentage($row['pct_complete'] ?? 0);
        $risk = self::riskLevel($row);

        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'slug' => (string)($row['slug'] ?? ''),
            'status' => (string)($row['status'] ?? ''),
            'progress' => $progress,
            'contract_sum' => (float)($row['contract_sum'] ?? 0),
            'start_date' => (string)($row['start_date'] ?? ''),
            'est_delivery' => (string)($row['est_delivery'] ?? ''),
            'current_milestone' => (string)($row['current_milestone'] ?? ''),
            'next_milestone' => (string)($row['next_milestone'] ?? ''),
            'next_milestone_date' => (string)($row['next_milestone_date'] ?? ''),
            'constituency_name' => (string)($row['constituency_name'] ?? ''),
            'ward_name' => (string)($row['ward_name'] ?? ''),
            'location_label' => (string)($row['location_label'] ?? ''),
            'contractor_name' => trim((string)($row['contractor_name'] ?? '')) ?: (string)($row['contractor_label'] ?? ''),
            'consultant_name' => trim((string)($row['consultant_name'] ?? '')),
            'site_engineer' => (string)($row['site_engineer'] ?? ''),
            'units' => (int)($row['units'] ?? 0),
            'open_ipcs' => (int)($row['open_ipcs'] ?? 0),
            'overdue_tasks' => (int)($row['overdue_tasks'] ?? 0),
            'due_milestones' => (int)($row['due_milestones'] ?? 0),
            'present_today' => (int)($row['present_today'] ?? 0),
            'clerk_count' => (int)($row['clerk_count'] ?? 0),
            'intern_count' => (int)($row['intern_count'] ?? 0),
            'latest_note' => (string)($row['latest_note'] ?? ''),
            'risk' => $risk,
            'risk_label' => status_label($risk),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                p.*,
                p.contractor_name AS contractor_label,
                c.name AS constituency_name,
                w.name AS ward_name,
                CONCAT(COALESCE(contractor.first_name, ''), ' ', COALESCE(contractor.last_name, '')) AS contractor_name,
                CONCAT(COALESCE(consultant.first_name, ''), ' ', COALESCE(consultant.last_name, '')) AS consultant_name,
                (SELECT m.label FROM milestones m WHERE m.project_id = p.id AND m.status <> 'done' ORDER BY m.target_date IS NULL ASC, m.target_date ASC, m.sequence ASC LIMIT 1) AS next_milestone,
                (SELECT m.target_date FROM milestones m WHERE m.project_id = p.id AND m.status <> 'done' ORDER BY m.target_date IS NULL ASC, m.target_date ASC, m.sequence ASC LIMIT 1) AS next_milestone_date,
                (SELECT COUNT(*) FROM milestones m WHERE m.project_id = p.id AND m.status <> 'done' AND m.target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)) AS due_milestones,
                (SELECT COUNT(*) FROM programme_tasks pt WHERE pt.project_id = p.id AND COALESCE(pt.status, '') NOT IN ('done','completed','complete') AND COALESCE(pt.end_date, pt.planned_end) < CURDATE()) AS overdue_tasks,
                (SELECT COUNT(*) FROM ipcs i WHERE i.project_id = p.id AND i.status IN ('submitted','clerk-endorsed','certified')) AS open_ipcs,
                (SELECT COUNT(*) FROM attendance_records ar WHERE ar.project_id = p.id AND ar.date = CURDATE() AND ar.status = 'present') AS present_today,
                (SELECT COUNT(*) FROM project_assignments pa WHERE pa.project_id = p.id AND pa.role = 'clerk' AND pa.status = 'active') AS clerk_count,
                (SELECT COUNT(*) FROM project_assignments pa WHERE pa.project_id = p.id AND pa.role = 'intern' AND pa.status = 'active') AS intern_count,
                (SELECT mpn.title FROM manager_project_notes mpn WHERE mpn.project_id = p.id AND mpn.status <> 'closed' ORDER BY FIELD(mpn.severity, 'critical','warning','normal'), mpn.created_at DESC LIMIT 1) AS latest_note
            FROM projects p
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN users contractor ON contractor.id = p.contractor_id
            LEFT JOIN users consultant ON consultant.id = p.consultant_id
        ";
    }

    private static function filterSql(int $userId, string $role, array $filters): array
    {
        $where = [];
        $bindings = [];
        if ($role !== 'superadmin') {
            $where[] = "EXISTS (SELECT 1 FROM project_assignments mpa WHERE mpa.project_id = p.id AND mpa.user_id = ? AND mpa.status = 'active')";
            $bindings[] = $userId;
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }
        if (!empty($filters['coverage'])) {
            if ($filters['coverage'] === 'missing-clerk') {
                $where[] = "NOT EXISTS (SELECT 1 FROM project_assignments pa WHERE pa.project_id = p.id AND pa.role = 'clerk' AND pa.status = 'active')";
            } elseif ($filters['coverage'] === 'missing-intern') {
                $where[] = "NOT EXISTS (SELECT 1 FROM project_assignments pa WHERE pa.project_id = p.id AND pa.role = 'intern' AND pa.status = 'active')";
            }
        }
        if (!empty($filters['risk'])) {
            if ($filters['risk'] === 'delayed') {
                $where[] = "p.est_delivery IS NOT NULL AND p.est_delivery < CURDATE() AND p.status NOT IN ('completed','cancelled')";
            } elseif ($filters['risk'] === 'attention') {
                $where[] = "(p.status IN ('on_hold','stalled') OR EXISTS (SELECT 1 FROM programme_tasks pt WHERE pt.project_id = p.id AND COALESCE(pt.status, '') NOT IN ('done','completed','complete') AND COALESCE(pt.end_date, pt.planned_end) < CURDATE()))";
            }
        }
        if (!empty($filters['progress'])) {
            match ($filters['progress']) {
                '0-25' => $where[] = 'COALESCE(p.pct_complete, 0) BETWEEN 0 AND 25',
                '26-50' => $where[] = 'COALESCE(p.pct_complete, 0) BETWEEN 26 AND 50',
                '51-75' => $where[] = 'COALESCE(p.pct_complete, 0) BETWEEN 51 AND 75',
                '76-100' => $where[] = 'COALESCE(p.pct_complete, 0) BETWEEN 76 AND 100',
                default => null,
            };
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.slug LIKE ? OR p.location_label LIKE ? OR p.contractor_name LIKE ? OR p.site_engineer LIKE ? OR c.name LIKE ? OR w.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function orderSql(string $risk): string
    {
        if ($risk !== '') {
            return ' ORDER BY p.est_delivery IS NULL ASC, p.est_delivery ASC, p.updated_at DESC';
        }

        return " ORDER BY FIELD(p.status, 'active','planning','on_hold','stalled','completed','cancelled'), p.updated_at DESC, p.id DESC";
    }

    private static function milestones(int $projectId, int $limit): array
    {
        return Database::fetchAll(
            "SELECT id, label, target_date, actual_date, status, sequence
             FROM milestones
             WHERE project_id = ?
             ORDER BY status = 'done' ASC, target_date IS NULL ASC, target_date ASC, sequence ASC
             LIMIT " . max(1, $limit),
            [$projectId]
        );
    }

    private static function programme(int $projectId): array
    {
        return Database::fetch('
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN status IN ("done","completed","complete") THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN COALESCE(status, "") NOT IN ("done","completed","complete") AND COALESCE(end_date, planned_end) < CURDATE() THEN 1 ELSE 0 END) AS overdue,
                   ROUND(AVG(COALESCE(pct_complete, 0))) AS avg_progress
            FROM programme_tasks
            WHERE project_id = ?
        ', [$projectId]) ?: ['total' => 0, 'completed' => 0, 'overdue' => 0, 'avg_progress' => 0];
    }

    private static function ipcs(int $projectId, int $limit): array
    {
        return Database::fetchAll(
            "SELECT id, ipc_number, net_amount, status, submitted_at, certified_at
             FROM ipcs
             WHERE project_id = ?
             ORDER BY submitted_at DESC, id DESC
             LIMIT " . max(1, $limit),
            [$projectId]
        );
    }

    private static function boq(int $projectId): array
    {
        return Database::fetch(
            'SELECT COUNT(*) AS items, COALESCE(SUM(amount), 0) AS contract_value,
                    COALESCE(SUM(certified_qty * rate), 0) AS certified_value,
                    COALESCE(SUM(paid_qty * rate), 0) AS paid_value
             FROM boq_items
             WHERE project_id = ?',
            [$projectId]
        ) ?: ['items' => 0, 'contract_value' => 0, 'certified_value' => 0, 'paid_value' => 0];
    }

    private static function attendanceToday(int $projectId): array
    {
        return Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN status IN ('late','geo-fail','outside-window','absent') THEN 1 ELSE 0 END) AS flags
             FROM attendance_records
             WHERE project_id = ? AND date = CURDATE()",
            [$projectId]
        ) ?: ['total' => 0, 'present' => 0, 'flags' => 0];
    }

    private static function risks(int $projectId, int $limit): array
    {
        $items = [];
        foreach ([
            ['hs_incidents', 'HS Incident', 'description', 'severity', 'created_at'],
            ['non_conformance_reports', 'Non-Conformance', 'description', 'severity', 'created_at'],
            ['defects', 'Defect', 'description', 'severity', 'created_at'],
            ['eot_requests', 'EOT Request', 'reason', 'status', 'created_at'],
            ['rfis', 'RFI', 'subject', 'urgency', 'created_at'],
        ] as [$table, $type, $titleColumn, $statusColumn, $dateColumn]) {
            try {
                $items = array_merge($items, Database::fetchAll(
                    "SELECT '{$type}' AS item_type, {$titleColumn} AS title, {$statusColumn} AS status, {$dateColumn} AS created_at
                     FROM {$table}
                     WHERE project_id = ?
                     ORDER BY {$dateColumn} DESC
                     LIMIT " . max(1, $limit),
                    [$projectId]
                ));
            } catch (Throwable) {
            }
        }
        usort($items, static fn (array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return array_slice($items, 0, $limit);
    }

    private static function riskLevel(array $row): string
    {
        if ((string)($row['status'] ?? '') === 'stalled') {
            return 'critical';
        }
        if (!empty($row['est_delivery']) && strtotime((string)$row['est_delivery']) < strtotime(date('Y-m-d')) && !in_array((string)$row['status'], ['completed', 'cancelled'], true)) {
            return 'delayed';
        }
        if ((int)($row['overdue_tasks'] ?? 0) > 0 || (int)($row['clerk_count'] ?? 0) === 0) {
            return 'attention';
        }
        return 'normal';
    }

    private static function option(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
