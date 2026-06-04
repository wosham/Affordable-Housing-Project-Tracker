<?php

class ManagerContractControl
{
    public const EOT_RECOMMENDATIONS = ['pending', 'approve', 'partial', 'reject', 'clarification'];
    public const EOT_STATUSES = ['pending', 'granted', 'partially-granted', 'rejected'];
    public const LD_STATUSES = ['draft', 'pending', 'applied', 'suspended', 'waived'];
    public const SUB_STATUSES = ['active', 'pending', 'suspended', 'terminated', 'completed'];
    public const COMPLIANCE_STATUSES = ['pending', 'compliant', 'issue', 'expired'];
    public const RISK_STATUSES = ['normal', 'watch', 'high', 'critical'];

    public static function projects(int $userId, string $role): array
    {
        $projects = ProjectAssignment::managerProjects($userId, $role);
        if ($projects === []) {
            return [];
        }

        $ids = array_map(static fn (array $project): int => (int)$project['id'], $projects);
        [$in, $bindings] = self::inClause($ids);
        $rows = Database::fetchAll(
            "SELECT p.id,
                    COUNT(DISTINCT e.id) AS eot_count,
                    COUNT(DISTINCT ld.id) AS ld_count,
                    COUNT(DISTINCT s.id) AS subcontractor_count
             FROM projects p
             LEFT JOIN eot_requests e ON e.project_id = p.id
             LEFT JOIN liquidated_damages ld ON ld.project_id = p.id
             LEFT JOIN subcontractors s ON s.project_id = p.id
             WHERE p.id IN ({$in})
             GROUP BY p.id",
            $bindings
        );

        $meta = [];
        foreach ($rows as $row) {
            $meta[(int)$row['id']] = $row;
        }
        foreach ($projects as &$project) {
            $row = $meta[(int)$project['id']] ?? [];
            $project['eot_count'] = (int)($row['eot_count'] ?? 0);
            $project['ld_count'] = (int)($row['ld_count'] ?? 0);
            $project['subcontractor_count'] = (int)($row['subcontractor_count'] ?? 0);
        }
        unset($project);

        return $projects;
    }

    public static function canAccessProject(int $userId, string $role, int $projectId): bool
    {
        return $role === 'superadmin' || ProjectAssignment::canManageProject($userId, $projectId, $role);
    }

    public static function eots(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::eotWhere($userId, $role, $filters);
        return Database::fetchAll(self::eotSelect() . $where . ' ORDER BY e.created_at DESC, e.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countEots(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::eotWhere($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM eot_requests e JOIN projects p ON p.id = e.project_id JOIN users submitter ON submitter.id = e.submitted_by {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function eotSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::eotWhere($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN e.manager_recommendation = 'pending' AND e.status = 'pending' THEN 1 ELSE 0 END), 0) AS manager_pending,
                    COALESCE(SUM(CASE WHEN e.manager_recommendation IN ('approve','partial') THEN 1 ELSE 0 END), 0) AS recommended,
                    COALESCE(SUM(CASE WHEN e.manager_recommendation = 'clarification' THEN 1 ELSE 0 END), 0) AS clarification,
                    COALESCE(SUM(CASE WHEN e.status IN ('granted','partially-granted') THEN 1 ELSE 0 END), 0) AS granted,
                    COALESCE(SUM(CASE WHEN e.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                    COALESCE(SUM(e.days_requested), 0) AS requested_days,
                    COALESCE(SUM(e.granted_days), 0) AS granted_days
             FROM eot_requests e
             JOIN projects p ON p.id = e.project_id
             {$where}",
            $bindings
        ) ?: [];
        return self::numericDefaults($row, ['total', 'manager_pending', 'recommended', 'clarification', 'granted', 'rejected', 'requested_days', 'granted_days']);
    }

    public static function lds(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::ldWhere($userId, $role, $filters);
        return Database::fetchAll(self::ldSelect() . $where . ' ORDER BY ld.created_at DESC, ld.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countLds(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::ldWhere($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM liquidated_damages ld JOIN projects p ON p.id = ld.project_id LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function ldSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::ldWhere($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(total_ld), 0) AS total_value,
                    COALESCE(SUM(days_overdue), 0) AS overdue_days,
                    COALESCE(SUM(CASE WHEN applied_to_ipc_id IS NOT NULL THEN 1 ELSE 0 END), 0) AS applied,
                    COALESCE(SUM(CASE WHEN ld.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN ld.status IN ('suspended','waived') THEN 1 ELSE 0 END), 0) AS waived
             FROM liquidated_damages ld
             JOIN projects p ON p.id = ld.project_id
             {$where}",
            $bindings
        ) ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'total_value' => (float)($row['total_value'] ?? 0),
            'overdue_days' => (int)($row['overdue_days'] ?? 0),
            'applied' => (int)($row['applied'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'waived' => (int)($row['waived'] ?? 0),
        ];
    }

    public static function subcontractors(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::subWhere($userId, $role, $filters);
        return Database::fetchAll(self::subSelect() . $where . ' ORDER BY s.created_at DESC, s.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function countSubcontractors(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::subWhere($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM subcontractors s JOIN projects p ON p.id = s.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function subcontractorSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::subWhere($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END), 0) AS active,
                    COALESCE(SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN s.status IN ('suspended','terminated') THEN 1 ELSE 0 END), 0) AS stopped,
                    COALESCE(SUM(contract_value), 0) AS contract_value,
                    COALESCE(SUM(CASE WHEN s.compliance_status IN ('pending','issue','expired') THEN 1 ELSE 0 END), 0) AS compliance_flags,
                    COALESCE(SUM(CASE WHEN s.risk_status IN ('high','critical') THEN 1 ELSE 0 END), 0) AS high_risk
             FROM subcontractors s
             JOIN projects p ON p.id = s.project_id
             {$where}",
            $bindings
        ) ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'stopped' => (int)($row['stopped'] ?? 0),
            'contract_value' => (float)($row['contract_value'] ?? 0),
            'compliance_flags' => (int)($row['compliance_flags'] ?? 0),
            'high_risk' => (int)($row['high_risk'] ?? 0),
        ];
    }

    public static function find(string $type, int $id, int $userId, string $role): ?array
    {
        return match ($type) {
            'eot' => Database::fetch(self::eotSelect() . ' WHERE e.id = ?' . self::scopeAnd($userId, $role, 'p') . ' LIMIT 1', array_merge([$id], self::scopeBindings($userId, $role))),
            'ld' => Database::fetch(self::ldSelect() . ' WHERE ld.id = ?' . self::scopeAnd($userId, $role, 'p') . ' LIMIT 1', array_merge([$id], self::scopeBindings($userId, $role))),
            'subcontractor' => Database::fetch(self::subSelect() . ' WHERE s.id = ?' . self::scopeAnd($userId, $role, 'p') . ' LIMIT 1', array_merge([$id], self::scopeBindings($userId, $role))),
            default => null,
        };
    }

    public static function reviewEot(int $userId, string $role, array $data): int
    {
        $id = (int)($data['id'] ?? $data['eot_id'] ?? 0);
        $record = self::find('eot', $id, $userId, $role);
        if (!$record) {
            throw new RuntimeException('EOT request could not be found.');
        }

        $recommendation = self::option((string)($data['manager_recommendation'] ?? 'pending'), self::EOT_RECOMMENDATIONS, 'pending');
        $requested = (int)($record['days_requested'] ?? 0);
        $recommendedDays = max(0, min($requested, (int)($data['manager_recommended_days'] ?? $requested)));
        if ($recommendation === 'reject' || $recommendation === 'clarification') {
            $recommendedDays = 0;
        }

        Database::query(
            'UPDATE eot_requests
             SET manager_recommendation = ?, manager_recommended_days = ?, manager_review_note = ?, delay_category = ?, impact_summary = ?, manager_reviewed_by = ?, manager_reviewed_at = NOW()
             WHERE id = ?',
            [
                $recommendation,
                $recommendedDays > 0 ? $recommendedDays : null,
                self::nullable($data['manager_review_note'] ?? ''),
                self::nullable($data['delay_category'] ?? ''),
                self::nullable($data['impact_summary'] ?? ''),
                $userId,
                $id,
            ]
        );
        return $id;
    }

    public static function saveLd(int $userId, string $role, array $data): int
    {
        $id = max(0, (int)($data['id'] ?? 0));
        $projectId = (int)($data['project_id'] ?? 0);
        if (!self::canAccessProject($userId, $role, $projectId)) {
            throw new RuntimeException('You do not have access to this project.');
        }
        $rate = max(0, (float)($data['rate_per_day'] ?? 0));
        $days = max(0, (int)($data['days_overdue'] ?? 0));
        $total = $rate * $days;
        $status = self::option((string)($data['status'] ?? 'draft'), self::LD_STATUSES, 'draft');
        $ipcId = (int)($data['applied_to_ipc_id'] ?? 0);

        if ($id > 0) {
            if (!self::find('ld', $id, $userId, $role)) {
                throw new RuntimeException('LD record could not be found.');
            }
            Database::query(
                'UPDATE liquidated_damages SET project_id = ?, rate_per_day = ?, days_overdue = ?, total_ld = ?, applied_to_ipc_id = ?, notes = ?, status = ?, updated_by = ?, applied_at = CASE WHEN ? = "applied" AND applied_at IS NULL THEN NOW() ELSE applied_at END WHERE id = ?',
                [$projectId, $rate, $days, $total, $ipcId > 0 ? $ipcId : null, self::nullable($data['notes'] ?? ''), $status, $userId, $status, $id]
            );
            return $id;
        }

        Database::query(
            'INSERT INTO liquidated_damages (project_id, rate_per_day, days_overdue, total_ld, applied_to_ipc_id, notes, status, calculated_by, updated_by, applied_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = "applied" THEN NOW() ELSE NULL END)',
            [$projectId, $rate, $days, $total, $ipcId > 0 ? $ipcId : null, self::nullable($data['notes'] ?? ''), $status, $userId, $userId, $status]
        );
        return (int)Database::lastInsertId();
    }

    public static function saveSubcontractor(int $userId, string $role, array $data): int
    {
        $id = max(0, (int)($data['id'] ?? 0));
        $projectId = (int)($data['project_id'] ?? 0);
        if (!self::canAccessProject($userId, $role, $projectId)) {
            throw new RuntimeException('You do not have access to this project.');
        }
        $company = trim((string)($data['company'] ?? ''));
        if ($company === '') {
            throw new RuntimeException('Company name is required.');
        }
        $status = self::option((string)($data['status'] ?? 'active'), self::SUB_STATUSES, 'active');
        $compliance = self::option((string)($data['compliance_status'] ?? 'pending'), self::COMPLIANCE_STATUSES, 'pending');
        $risk = self::option((string)($data['risk_status'] ?? 'normal'), self::RISK_STATUSES, 'normal');

        $payload = [
            $projectId,
            $company,
            self::nullable($data['scope_of_work'] ?? ''),
            max(0, (float)($data['contract_value'] ?? 0)),
            $status,
            self::nullable($data['contact_person'] ?? ''),
            self::nullable($data['phone'] ?? ''),
            self::nullable($data['email'] ?? ''),
            $compliance,
            $risk,
            self::nullable($data['performance_note'] ?? ''),
            $userId,
        ];

        if ($id > 0) {
            if (!self::find('subcontractor', $id, $userId, $role)) {
                throw new RuntimeException('Subcontractor record could not be found.');
            }
            Database::query(
                'UPDATE subcontractors SET project_id = ?, company = ?, scope_of_work = ?, contract_value = ?, status = ?, contact_person = ?, phone = ?, email = ?, compliance_status = ?, risk_status = ?, performance_note = ?, updated_by = ? WHERE id = ?',
                array_merge($payload, [$id])
            );
            return $id;
        }

        Database::query(
            'INSERT INTO subcontractors (project_id, company, scope_of_work, contract_value, status, contact_person, phone, email, compliance_status, risk_status, performance_note, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $payload
        );
        return (int)Database::lastInsertId();
    }

    public static function updateStatus(string $type, int $id, int $userId, string $role, string $status): array
    {
        $record = self::find($type, $id, $userId, $role);
        if (!$record) {
            throw new RuntimeException('Record could not be found.');
        }
        if ($type === 'ld') {
            $status = self::option($status, self::LD_STATUSES, (string)$record['status']);
            Database::query('UPDATE liquidated_damages SET status = ?, updated_by = ?, applied_at = CASE WHEN ? = "applied" AND applied_at IS NULL THEN NOW() ELSE applied_at END WHERE id = ?', [$status, $userId, $status, $id]);
        } else {
            $status = self::option($status, self::SUB_STATUSES, (string)$record['status']);
            Database::query('UPDATE subcontractors SET status = ?, updated_by = ? WHERE id = ?', [$status, $userId, $id]);
        }
        return self::find($type, $id, $userId, $role) ?: [];
    }

    public static function eotPayload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'status_label' => status_label((string)($row['status'] ?? 'pending')),
            'recommendation_label' => status_label((string)($row['manager_recommendation'] ?? 'pending')),
            'created_label' => format_datetime($row['created_at'] ?? null),
            'submitted_by_name' => trim((string)($row['submitter_name'] ?? '')) ?: 'System',
        ]);
    }

    public static function ldPayload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'status_label' => status_label((string)($row['status'] ?? 'draft')),
            'total_ld' => (float)($row['total_ld'] ?? 0),
            'rate_per_day' => (float)($row['rate_per_day'] ?? 0),
            'days_overdue' => (int)($row['days_overdue'] ?? 0),
            'created_label' => format_datetime($row['created_at'] ?? null),
            'ipc_label' => (string)($row['ipc_number'] ?? ''),
        ]);
    }

    public static function subPayload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'status_label' => status_label((string)($row['status'] ?? 'active')),
            'compliance_label' => status_label((string)($row['compliance_status'] ?? 'pending')),
            'risk_label' => status_label((string)($row['risk_status'] ?? 'normal')),
            'contract_value' => (float)($row['contract_value'] ?? 0),
            'created_label' => format_datetime($row['created_at'] ?? null),
        ]);
    }

    public static function ipcsForProject(int $userId, string $role, int $projectId): array
    {
        if ($projectId <= 0 || !self::canAccessProject($userId, $role, $projectId)) {
            return [];
        }
        return Database::fetchAll('SELECT id, ipc_number, status, net_amount FROM ipcs WHERE project_id = ? ORDER BY submitted_at DESC, id DESC LIMIT 50', [$projectId]);
    }

    private static function eotSelect(): string
    {
        return "SELECT e.*, p.name AS project_name, p.est_delivery, CONCAT(submitter.first_name, ' ', submitter.last_name) AS submitter_name, CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS manager_reviewer_name
                FROM eot_requests e
                JOIN projects p ON p.id = e.project_id
                JOIN users submitter ON submitter.id = e.submitted_by
                LEFT JOIN users reviewer ON reviewer.id = e.manager_reviewed_by";
    }

    private static function ldSelect(): string
    {
        return "SELECT ld.*, p.name AS project_name, p.est_delivery, i.ipc_number
                FROM liquidated_damages ld
                JOIN projects p ON p.id = ld.project_id
                LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id";
    }

    private static function subSelect(): string
    {
        return "SELECT s.*, p.name AS project_name
                FROM subcontractors s
                JOIN projects p ON p.id = s.project_id";
    }

    private static function eotWhere(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::exact($where, $bindings, 'e.status', $filters['status'] ?? '');
        self::exact($where, $bindings, 'e.manager_recommendation', $filters['manager_recommendation'] ?? '');
        self::dateFilters($where, $bindings, 'DATE(e.created_at)', $filters);
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR e.reason LIKE ? OR CAST(e.eot_number AS CHAR) LIKE ? OR submitter.first_name LIKE ? OR submitter.last_name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function ldWhere(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::exact($where, $bindings, 'ld.status', $filters['status'] ?? '');
        if (($filters['applied'] ?? '') === 'yes') {
            $where[] = 'ld.applied_to_ipc_id IS NOT NULL';
        } elseif (($filters['applied'] ?? '') === 'no') {
            $where[] = 'ld.applied_to_ipc_id IS NULL';
        }
        self::dateFilters($where, $bindings, 'DATE(ld.created_at)', $filters);
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR ld.notes LIKE ? OR i.ipc_number LIKE ?)';
            array_push($bindings, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function subWhere(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::exact($where, $bindings, 's.status', $filters['status'] ?? '');
        self::exact($where, $bindings, 's.compliance_status', $filters['compliance_status'] ?? '');
        self::exact($where, $bindings, 's.risk_status', $filters['risk_status'] ?? '');
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR s.company LIKE ? OR s.scope_of_work LIKE ? OR s.contact_person LIKE ?)';
            array_push($bindings, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function baseWhere(int $userId, string $role, string $projectAlias, array $filters): array
    {
        $where = [];
        $bindings = [];
        if ($role !== 'superadmin') {
            $where[] = "EXISTS (SELECT 1 FROM project_assignments mpa WHERE mpa.project_id = {$projectAlias}.id AND mpa.user_id = ? AND mpa.status = 'active')";
            $bindings[] = $userId;
        }
        if (!empty($filters['project_id'])) {
            $where[] = "{$projectAlias}.id = ?";
            $bindings[] = (int)$filters['project_id'];
        }
        return [$where, $bindings];
    }

    private static function scopeAnd(int $userId, string $role, string $projectAlias): string
    {
        return $role === 'superadmin' ? '' : " AND EXISTS (SELECT 1 FROM project_assignments mpa WHERE mpa.project_id = {$projectAlias}.id AND mpa.user_id = ? AND mpa.status = 'active')";
    }

    private static function scopeBindings(int $userId, string $role): array
    {
        return $role === 'superadmin' ? [] : [$userId];
    }

    private static function exact(array &$where, array &$bindings, string $column, mixed $value): void
    {
        $value = trim((string)$value);
        if ($value !== '') {
            $where[] = "{$column} = ?";
            $bindings[] = $value;
        }
    }

    private static function dateFilters(array &$where, array &$bindings, string $column, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $where[] = "{$column} >= ?";
            $bindings[] = (string)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "{$column} <= ?";
            $bindings[] = (string)$filters['date_to'];
        }
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }

    private static function numericDefaults(array $row, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = (int)($row[$key] ?? 0);
        }
        return $out;
    }

    private static function option(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
