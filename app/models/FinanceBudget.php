<?php

class FinanceBudget
{
    public static function filters(array $input): array
    {
        $filters = [
            'q' => Security::cleanString((string)($input['q'] ?? '')),
            'project_id' => Security::cleanInt($input['project_id'] ?? 0),
            'status' => Security::cleanString((string)($input['status'] ?? '')),
            'risk' => Security::cleanString((string)($input['risk'] ?? '')),
            'date_from' => Security::cleanString((string)($input['date_from'] ?? '')),
            'date_to' => Security::cleanString((string)($input['date_to'] ?? '')),
        ];

        if (!in_array($filters['risk'], ['', 'near_limit', 'retention', 'ld', 'unpaid'], true)) {
            $filters['risk'] = '';
        }

        return array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
    }

    public static function summary(array $filters = []): array
    {
        $rows = self::projectRows($filters, 500);
        $summary = [
            'projects' => count($rows),
            'contract_value' => 0.0,
            'paid_value' => 0.0,
            'approved_unpaid' => 0.0,
            'retention_held' => 0.0,
            'ld_value' => 0.0,
            'balance_value' => 0.0,
            'near_limit' => 0,
            'risk_projects' => 0,
        ];

        foreach ($rows as $row) {
            $summary['contract_value'] += (float)$row['contract_sum'];
            $summary['paid_value'] += (float)$row['paid_amount'];
            $summary['approved_unpaid'] += (float)$row['approved_unpaid'];
            $summary['retention_held'] += (float)$row['retention_held'];
            $summary['ld_value'] += (float)$row['ld_value'];
            $summary['balance_value'] += (float)$row['balance_amount'];
            if ((float)$row['usage_pct'] >= 85) {
                $summary['near_limit']++;
            }
            if ((string)$row['risk_level'] !== 'normal') {
                $summary['risk_projects']++;
            }
        }

        return $summary;
    }

    public static function projectRows(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $bindings] = self::projectWhere($filters);
        $rows = Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.contract_sum, p.pct_complete,
                    COALESCE(pay.paid_amount, 0) AS paid_amount,
                    COALESCE(unpaid.approved_unpaid, 0) AS approved_unpaid,
                    COALESCE(ret.retention_held, 0) AS retention_held,
                    COALESCE(ld.ld_value, 0) AS ld_value,
                    GREATEST(p.contract_sum - COALESCE(pay.paid_amount, 0) - COALESCE(unpaid.approved_unpaid, 0), 0) AS balance_amount,
                    CASE WHEN p.contract_sum > 0 THEN ROUND(((COALESCE(pay.paid_amount, 0) + COALESCE(unpaid.approved_unpaid, 0)) / p.contract_sum) * 100, 1) ELSE 0 END AS usage_pct
             FROM projects p
             LEFT JOIN (
                SELECT project_id, SUM(amount) AS paid_amount
                FROM payments
                WHERE status = 'processed'
                GROUP BY project_id
             ) pay ON pay.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(net_amount) AS approved_unpaid
                FROM ipcs
                WHERE status = 'approved' AND payment_status <> 'paid'
                GROUP BY project_id
             ) unpaid ON unpaid.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(total_held - released_amount) AS retention_held
                FROM retention
                WHERE status <> 'released'
                GROUP BY project_id
             ) ret ON ret.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(total_ld) AS ld_value
                FROM liquidated_damages
                WHERE status IN ('pending','applied')
                GROUP BY project_id
             ) ld ON ld.project_id = p.id
             {$where}
             ORDER BY usage_pct DESC, p.contract_sum DESC, p.name ASC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return array_map([self::class, 'decorateProjectRow'], $rows);
    }

    public static function projectCount(array $filters = []): int
    {
        [$where, $bindings] = self::projectWhere($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM projects p
             LEFT JOIN (
                SELECT project_id, SUM(amount) AS paid_amount
                FROM payments
                WHERE status = 'processed'
                GROUP BY project_id
             ) pay ON pay.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(net_amount) AS approved_unpaid
                FROM ipcs
                WHERE status = 'approved' AND payment_status <> 'paid'
                GROUP BY project_id
             ) unpaid ON unpaid.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(total_held - released_amount) AS retention_held
                FROM retention
                WHERE status <> 'released'
                GROUP BY project_id
             ) ret ON ret.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(total_ld) AS ld_value
                FROM liquidated_damages
                WHERE status IN ('pending','applied')
                GROUP BY project_id
             ) ld ON ld.project_id = p.id
             {$where}",
            $bindings
        ) ?: [];

        return (int)($row['total'] ?? 0);
    }

    public static function retentionRows(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $bindings] = self::retentionWhere($filters);
        $rows = Database::fetchAll(
            "SELECT r.*, p.name AS project_name, i.ipc_number,
                    CONCAT(u.first_name, ' ', u.last_name) AS processed_by_name,
                    DATEDIFF(r.release_date, CURDATE()) AS release_days
             FROM retention r
             JOIN projects p ON p.id = r.project_id
             LEFT JOIN ipcs i ON i.id = r.ipc_id
             LEFT JOIN users u ON u.id = r.processed_by
             {$where}
             ORDER BY CASE WHEN r.release_date IS NULL THEN 1 ELSE 0 END, r.release_date ASC, r.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return array_map([self::class, 'decorateRetentionRow'], $rows);
    }

    public static function retentionCount(array $filters = []): int
    {
        [$where, $bindings] = self::retentionWhere($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM retention r
             JOIN projects p ON p.id = r.project_id
             LEFT JOIN ipcs i ON i.id = r.ipc_id
             {$where}",
            $bindings
        ) ?: [];
        return (int)($row['total'] ?? 0);
    }

    public static function liquidatedDamageRows(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $bindings] = self::ldWhere($filters);
        $rows = Database::fetchAll(
            "SELECT ld.*, p.name AS project_name, i.ipc_number,
                    CONCAT(calc.first_name, ' ', calc.last_name) AS calculated_by_name
             FROM liquidated_damages ld
             JOIN projects p ON p.id = ld.project_id
             LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id
             LEFT JOIN users calc ON calc.id = ld.calculated_by
             {$where}
             ORDER BY ld.updated_at DESC, ld.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return array_map([self::class, 'decorateLdRow'], $rows);
    }

    public static function liquidatedDamageCount(array $filters = []): int
    {
        [$where, $bindings] = self::ldWhere($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM liquidated_damages ld
             JOIN projects p ON p.id = ld.project_id
             LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id
             {$where}",
            $bindings
        ) ?: [];
        return (int)($row['total'] ?? 0);
    }

    public static function projects(): array
    {
        return Database::fetchAll('SELECT id, name FROM projects ORDER BY name ASC');
    }

    private static function projectWhere(array $filters): array
    {
        $where = [];
        $bindings = [];
        self::applyProjectCommon($filters, $where, $bindings, 'p');

        if (($filters['risk'] ?? '') === 'near_limit') {
            $where[] = "p.contract_sum > 0 AND ((COALESCE(pay.paid_amount, 0) + COALESCE(unpaid.approved_unpaid, 0)) / p.contract_sum) >= 0.85";
        } elseif (($filters['risk'] ?? '') === 'retention') {
            $where[] = 'COALESCE(ret.retention_held, 0) > 0';
        } elseif (($filters['risk'] ?? '') === 'ld') {
            $where[] = 'COALESCE(ld.ld_value, 0) > 0';
        } elseif (($filters['risk'] ?? '') === 'unpaid') {
            $where[] = 'COALESCE(unpaid.approved_unpaid, 0) > 0';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function retentionWhere(array $filters): array
    {
        $where = [];
        $bindings = [];
        self::applyProjectCommon($filters, $where, $bindings, 'p');
        if (!empty($filters['status'])) {
            $where[] = 'r.status = ?';
            $bindings[] = $filters['status'];
        }
        self::applyDateRange($filters, $where, $bindings, 'r.release_date');
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function ldWhere(array $filters): array
    {
        $where = [];
        $bindings = [];
        self::applyProjectCommon($filters, $where, $bindings, 'p');
        if (!empty($filters['status'])) {
            $where[] = 'ld.status = ?';
            $bindings[] = $filters['status'];
        }
        self::applyDateRange($filters, $where, $bindings, 'DATE(ld.updated_at)');
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function applyProjectCommon(array $filters, array &$where, array &$bindings, string $projectAlias): void
    {
        if (!empty($filters['project_id'])) {
            $where[] = "{$projectAlias}.id = ?";
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['status']) && in_array((string)$filters['status'], Project::statusOptions(), true)) {
            $where[] = "{$projectAlias}.status = ?";
            $bindings[] = (string)$filters['status'];
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "{$projectAlias}.name LIKE ?";
            $bindings[] = '%' . $q . '%';
        }
    }

    private static function applyDateRange(array $filters, array &$where, array &$bindings, string $column): void
    {
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $op) {
            $date = trim((string)($filters[$key] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where[] = "{$column} {$op} ?";
                $bindings[] = $date;
            }
        }
    }

    private static function decorateProjectRow(array $row): array
    {
        $usage = (float)($row['usage_pct'] ?? 0);
        $risk = 'normal';
        if ((float)($row['ld_value'] ?? 0) > 0) {
            $risk = 'ld';
        } elseif ($usage >= 85) {
            $risk = 'near_limit';
        } elseif ((float)($row['approved_unpaid'] ?? 0) > 0) {
            $risk = 'unpaid';
        } elseif ((float)($row['retention_held'] ?? 0) > 0) {
            $risk = 'retention';
        }

        $row['risk_level'] = $risk;
        $row['risk_label'] = match ($risk) {
            'near_limit' => 'Near limit',
            'retention' => 'Retention',
            'ld' => 'Damages',
            'unpaid' => 'Unpaid IPC',
            default => 'On track',
        };
        $row['risk_badge'] = $risk === 'normal' ? 'badge--success' : 'badge--warning';
        return $row;
    }

    private static function decorateRetentionRow(array $row): array
    {
        $balance = (float)($row['total_held'] ?? 0) - (float)($row['released_amount'] ?? 0);
        $days = $row['release_days'] === null ? null : (int)$row['release_days'];
        $row['balance_amount'] = $balance;
        $row['status_label'] = $days !== null && $days <= 30 && $balance > 0 && ($row['status'] ?? '') !== 'released'
            ? ($days < 0 ? 'Due' : 'Due soon')
            : status_label($row['status'] ?? 'held');
        $row['status_badge'] = $days !== null && $days <= 30 && $balance > 0 ? 'badge--warning' : 'badge--info';
        return $row;
    }

    private static function decorateLdRow(array $row): array
    {
        $row['status_badge'] = in_array((string)($row['status'] ?? ''), ['applied', 'waived'], true) ? 'badge--success' : 'badge--warning';
        return $row;
    }
}
