<?php

class ManagerIPCQueue
{
    public const VIEWS = ['ready', 'incoming', 'endorsed', 'rejected', 'all'];

    public static function projects(int $userId, string $role): array
    {
        $projects = ProjectAssignment::managerProjects($userId, $role);
        if ($projects === []) {
            return [];
        }

        $ids = array_values(array_map(static fn (array $project): int => (int)$project['id'], $projects));
        [$in, $bindings] = self::inClause($ids);
        $rows = Database::fetchAll(
            "SELECT project_id, COUNT(*) AS ipc_count,
                    COALESCE(SUM(CASE WHEN status = 'certified' THEN 1 ELSE 0 END), 0) AS ready_count,
                    COALESCE(SUM(net_amount), 0) AS net_value
             FROM ipcs
             WHERE project_id IN ({$in})
             GROUP BY project_id",
            $bindings
        );

        $meta = [];
        foreach ($rows as $row) {
            $meta[(int)$row['project_id']] = $row;
        }

        foreach ($projects as &$project) {
            $row = $meta[(int)$project['id']] ?? [];
            $project['ipc_count'] = (int)($row['ipc_count'] ?? 0);
            $project['ready_count'] = (int)($row['ready_count'] ?? 0);
            $project['net_value'] = (float)($row['net_value'] ?? 0);
        }
        unset($project);

        return $projects;
    }

    public static function list(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $limitSql = ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY i.submitted_at DESC, i.updated_at DESC, i.id DESC' . $limitSql, $bindings);
    }

    public static function count(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM ipcs i
             INNER JOIN projects p ON p.id = i.project_id
             INNER JOIN users contractor ON contractor.id = i.contractor_id
             {$where}",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function summary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN i.status = 'certified' THEN 1 ELSE 0 END), 0) AS ready,
                COALESCE(SUM(CASE WHEN i.status IN ('submitted','clerk-endorsed') THEN 1 ELSE 0 END), 0) AS incoming,
                COALESCE(SUM(CASE WHEN i.status = 'endorsed' THEN 1 ELSE 0 END), 0) AS endorsed,
                COALESCE(SUM(CASE WHEN i.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                COALESCE(SUM(CASE WHEN i.status = 'certified' THEN i.net_amount ELSE 0 END), 0) AS ready_value,
                COALESCE(SUM(CASE WHEN i.status = 'certified' AND i.net_amount >= ? THEN 1 ELSE 0 END), 0) AS high_value,
                COALESCE(SUM(i.net_amount), 0) AS net_value
             FROM ipcs i
             INNER JOIN projects p ON p.id = i.project_id
             INNER JOIN users contractor ON contractor.id = i.contractor_id
             {$where}",
            array_merge([self::highValueThreshold()], $bindings)
        ) ?: [];

        return array_merge([
            'total' => 0,
            'ready' => 0,
            'incoming' => 0,
            'endorsed' => 0,
            'rejected' => 0,
            'ready_value' => 0,
            'high_value' => 0,
            'net_value' => 0,
        ], array_map(static fn ($value) => is_numeric($value) ? (float)$value : $value, $row));
    }

    public static function findScoped(int $ipcId, int $userId, string $role): ?array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        array_unshift($bindings, $ipcId);
        $where = ' WHERE i.id = ?' . ($scopeSql !== '' ? ' AND ' . $scopeSql : '');

        return Database::fetch(self::selectSql() . $where . ' LIMIT 1', $bindings);
    }

    public static function insights(int $userId, string $role, array $filters = []): array
    {
        return [
            'ready' => array_map([self::class, 'payload'], self::list($userId, $role, array_merge($filters, ['status' => 'certified']), 5)),
            'incoming' => array_map([self::class, 'payload'], self::list($userId, $role, array_merge($filters, ['view' => 'incoming']), 5)),
            'endorsed' => array_map([self::class, 'payload'], self::list($userId, $role, array_merge($filters, ['status' => 'endorsed']), 5)),
        ];
    }

    public static function payload(array $ipc): array
    {
        $warnings = IPC::warnings($ipc);
        $workflow = IPC::workflowState($ipc);
        $status = (string)($ipc['status'] ?? 'draft');

        return array_merge($ipc, [
            'id' => (int)($ipc['id'] ?? 0),
            'project_id' => (int)($ipc['project_id'] ?? 0),
            'contractor_id' => (int)($ipc['contractor_id'] ?? 0),
            'ipc_number' => (string)($ipc['ipc_number'] ?? ''),
            'status' => $status,
            'status_label' => status_label($status),
            'status_badge' => status_badge_class($status),
            'gross_amount' => (float)($ipc['gross_amount'] ?? 0),
            'retention_amount' => (float)($ipc['retention_amount'] ?? 0),
            'net_amount' => (float)($ipc['net_amount'] ?? 0),
            'submitted_label' => format_datetime($ipc['submitted_at'] ?? $ipc['created_at'] ?? null),
            'period_label' => format_date($ipc['period_from'] ?? null) . ' - ' . format_date($ipc['period_to'] ?? null),
            'last_action_label' => !empty($ipc['last_action'])
                ? status_label((string)$ipc['last_action']) . ' by ' . (trim((string)($ipc['last_action_by'] ?? '')) ?: 'staff')
                : 'No action yet',
            'warnings' => $warnings,
            'warning_count' => count($warnings),
            'workflow' => $workflow,
            'is_ready_for_manager' => $status === 'certified',
            'is_high_value' => (float)($ipc['net_amount'] ?? 0) >= self::highValueThreshold(),
        ]);
    }

    public static function highValueThreshold(): float
    {
        return max(0, (float)SystemConfig::get('ipc.manager_high_value_threshold', 50000000));
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                i.*,
                p.name AS project_name,
                p.slug AS project_slug,
                p.contract_sum,
                p.pct_complete,
                p.status AS project_status,
                CONCAT(contractor.first_name, ' ', contractor.last_name) AS contractor_name,
                contractor.email AS contractor_email,
                c.name AS constituency_name,
                w.name AS ward_name,
                latest.action AS last_action,
                latest.comments AS last_comment,
                latest.actioned_at AS last_actioned_at,
                CONCAT(actor.first_name, ' ', actor.last_name) AS last_action_by
            FROM ipcs i
            INNER JOIN projects p ON p.id = i.project_id
            INNER JOIN users contractor ON contractor.id = i.contractor_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN ipc_approvals latest ON latest.id = (
                SELECT ia.id FROM ipc_approvals ia
                WHERE ia.ipc_id = i.id
                ORDER BY ia.actioned_at DESC, ia.id DESC
                LIMIT 1
            )
            LEFT JOIN users actor ON actor.id = latest.action_by
        ";
    }

    private static function filterSql(int $userId, string $role, array $filters, bool $allowView = true): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }

        $view = (string)($filters['view'] ?? 'ready');
        if ($allowView && $view !== '' && in_array($view, self::VIEWS, true)) {
            if ($view === 'ready') {
                $where[] = "i.status = 'certified'";
            } elseif ($view === 'incoming') {
                $where[] = "i.status IN ('submitted','clerk-endorsed')";
            } elseif ($view === 'endorsed') {
                $where[] = "i.status = 'endorsed'";
            } elseif ($view === 'rejected') {
                $where[] = "i.status = 'rejected'";
            }
        }

        if (!empty($filters['status'])) {
            $where[] = 'i.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'i.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(i.submitted_at) >= ?';
            $bindings[] = (string)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(i.submitted_at) <= ?';
            $bindings[] = (string)$filters['date_to'];
        }
        if (!empty($filters['amount_min'])) {
            $where[] = 'i.net_amount >= ?';
            $bindings[] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $where[] = 'i.net_amount <= ?';
            $bindings[] = (float)$filters['amount_max'];
        }
        if (!empty($filters['high_value'])) {
            $where[] = 'i.net_amount >= ?';
            $bindings[] = self::highValueThreshold();
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR contractor.first_name LIKE ? OR contractor.last_name LIKE ? OR contractor.email LIKE ? OR CAST(i.ipc_number AS CHAR) LIKE ?)";
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
