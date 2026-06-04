<?php

class ConsultantIPC
{
    public const CERTIFIABLE_STATUS = 'clerk-endorsed';
    public const REVIEW_CHECKLIST = ['lines', 'quantities', 'amounts', 'supporting_records'];

    public static function projects(int $userId, string $role): array
    {
        [$scopeSql, $bindings] = self::projectScopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        return Database::fetchAll(
            "SELECT DISTINCT p.id, p.name, p.status, c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}
             ORDER BY p.name ASC",
            $bindings
        );
    }

    public static function summary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN i.status = 'clerk-endorsed' THEN 1 ELSE 0 END), 0) AS certifiable,
                COALESCE(SUM(CASE WHEN i.status = 'submitted' THEN 1 ELSE 0 END), 0) AS submitted,
                COALESCE(SUM(CASE WHEN i.status = 'certified' THEN 1 ELSE 0 END), 0) AS certified,
                COALESCE(SUM(CASE WHEN i.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                COALESCE(SUM(CASE WHEN i.status = 'clerk-endorsed' THEN i.net_amount ELSE 0 END), 0) AS certifiable_value,
                COALESCE(SUM(CASE WHEN i.status = 'certified' AND i.certified_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END), 0) AS certified_this_month
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             JOIN users contractor ON contractor.id = i.contractor_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numeric(array_merge([
            'total' => 0,
            'certifiable' => 0,
            'submitted' => 0,
            'certified' => 0,
            'rejected' => 0,
            'certifiable_value' => 0,
            'certified_this_month' => 0,
        ], $row));
    }

    public static function statusTabs(int $userId, string $role, array $filters = []): array
    {
        $baseFilters = $filters;
        unset($baseFilters['status']);
        [$where, $bindings] = self::filterSql($userId, $role, $baseFilters, false);
        $rows = Database::fetchAll(
            "SELECT i.status, COUNT(*) AS total
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             JOIN users contractor ON contractor.id = i.contractor_id
             {$where}
             GROUP BY i.status",
            $bindings
        );

        $counts = array_fill_keys(['all', 'submitted', 'clerk-endorsed', 'certified', 'rejected'], 0);
        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');
            if (isset($counts[$status])) {
                $counts[$status] = (int)$row['total'];
            }
            $counts['all'] += (int)$row['total'];
        }

        return $counts;
    }

    public static function items(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $rows = Database::fetchAll(
            self::selectSql() . $where . "
             ORDER BY FIELD(i.status, 'clerk-endorsed', 'submitted', 'certified', 'endorsed', 'approved', 'paid', 'rejected', 'draft'),
                      COALESCE(i.submitted_at, i.updated_at) DESC,
                      i.id DESC
             LIMIT " . max(1, min(100, $limit)) . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return array_map([self::class, 'payload'], $rows);
    }

    public static function count(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total FROM (
                SELECT i.id,
                       i.gross_amount,
                       COUNT(il.id) AS line_count,
                       COALESCE(SUM(il.amount), 0) AS line_total,
                       COALESCE(SUM(CASE WHEN bi.id IS NOT NULL AND il.cumulative_qty > bi.quantity THEN 1 ELSE 0 END), 0) AS warnings_count
                FROM ipcs i
                JOIN projects p ON p.id = i.project_id
                JOIN users contractor ON contractor.id = i.contractor_id
                LEFT JOIN ipc_lines il ON il.ipc_id = i.id
                LEFT JOIN boq_items bi ON bi.id = il.boq_item_id
                {$where}
            ) counted_ipcs",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function findForUser(int $ipcId, int $userId, string $role): ?array
    {
        if ($ipcId <= 0) {
            return null;
        }

        [$scopeSql, $bindings] = self::projectScopeSql($userId, $role, 'p');
        $where = ' WHERE i.id = ?';
        $queryBindings = [$ipcId];
        if ($scopeSql !== '') {
            $where .= ' AND ' . $scopeSql;
            array_push($queryBindings, ...$bindings);
        }

        $row = Database::fetch(self::selectSql() . $where . ' LIMIT 1', $queryBindings);
        return $row ? self::payload($row) : null;
    }

    public static function lineItems(int $ipcId): array
    {
        $rows = Database::fetchAll(
            "SELECT il.*, bi.item_no, bi.section, bi.quantity AS boq_quantity, bi.certified_qty AS boq_certified_qty, bi.paid_qty AS boq_paid_qty, bi.rate AS boq_rate,
                    GREATEST(COALESCE(bi.certified_qty, 0) - COALESCE(il.qty_this_period, 0), 0) AS previous_certified_qty,
                    GREATEST(COALESCE(bi.quantity, 0) - COALESCE(il.cumulative_qty, 0), 0) AS remaining_qty,
                    CASE WHEN COALESCE(bi.rate, 0) > 0 THEN COALESCE(il.qty_this_period, 0) * COALESCE(bi.rate, 0) ELSE COALESCE(il.amount, 0) END AS expected_amount
             FROM ipc_lines il
             LEFT JOIN boq_items bi ON bi.id = il.boq_item_id
             WHERE il.ipc_id = ?
             ORDER BY COALESCE(bi.section, ''), COALESCE(bi.item_no, ''), il.id ASC",
            [$ipcId]
        );

        return array_map(static function (array $line): array {
            $line['amount_variance'] = (float)($line['amount'] ?? 0) - (float)($line['expected_amount'] ?? 0);
            $line['over_boq'] = (float)($line['boq_quantity'] ?? 0) > 0
                && (float)($line['cumulative_qty'] ?? 0) > (float)$line['boq_quantity'];
            return $line;
        }, $rows);
    }

    public static function approvalHistory(int $ipcId): array
    {
        return Database::fetchAll(
            "SELECT ia.*, CONCAT(u.first_name, ' ', u.last_name) AS actor_name, r.name AS actor_role
             FROM ipc_approvals ia
             JOIN users u ON u.id = ia.action_by
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE ia.ipc_id = ?
             ORDER BY ia.actioned_at ASC, ia.id ASC",
            [$ipcId]
        );
    }

    public static function warnings(array $ipc, array $lines): array
    {
        $warnings = IPC::warnings($ipc);
        $gross = (float)($ipc['gross_amount'] ?? 0);
        $lineTotal = 0.0;

        foreach ($lines as $line) {
            $amount = (float)($line['amount'] ?? 0);
            $lineTotal += $amount;
            $description = trim((string)($line['description'] ?? 'Line item'));
            $boqQuantity = (float)($line['boq_quantity'] ?? 0);
            $cumulative = (float)($line['cumulative_qty'] ?? 0);
            $qtyThisPeriod = (float)($line['qty_this_period'] ?? 0);

            if ($boqQuantity > 0 && $cumulative > $boqQuantity) {
                $warnings[] = safe_truncate($description, 80) . ' exceeds BOQ quantity.';
            }
            if ($qtyThisPeriod < 0 || $cumulative < 0) {
                $warnings[] = safe_truncate($description, 80) . ' has a negative quantity.';
            }
            if ($amount <= 0 && $qtyThisPeriod > 0) {
                $warnings[] = safe_truncate($description, 80) . ' has quantity but no amount.';
            }
        }

        if ($lines !== [] && abs($lineTotal - $gross) > 1) {
            $warnings[] = 'Line item total does not match the IPC gross amount.';
        }

        return array_values(array_unique($warnings));
    }

    public static function reviewMetrics(array $ipc, array $lines, array $warnings): array
    {
        $lineTotal = 0.0;
        $overBoq = 0;
        $amountVariance = 0.0;
        $zeroAmountLines = 0;
        $negativeQuantityLines = 0;

        foreach ($lines as $line) {
            $lineTotal += (float)($line['amount'] ?? 0);
            $amountVariance += abs((float)($line['amount_variance'] ?? 0));
            if (!empty($line['over_boq'])) {
                $overBoq++;
            }
            if ((float)($line['amount'] ?? 0) <= 0 && (float)($line['qty_this_period'] ?? 0) > 0) {
                $zeroAmountLines++;
            }
            if ((float)($line['qty_this_period'] ?? 0) < 0 || (float)($line['cumulative_qty'] ?? 0) < 0) {
                $negativeQuantityLines++;
            }
        }

        return [
            'line_count' => count($lines),
            'line_total' => $lineTotal,
            'gross_variance' => abs($lineTotal - (float)($ipc['gross_amount'] ?? 0)),
            'amount_variance' => $amountVariance,
            'over_boq' => $overBoq,
            'zero_amount_lines' => $zeroAmountLines,
            'negative_quantity_lines' => $negativeQuantityLines,
            'warning_count' => count($warnings),
        ];
    }

    public static function certificationChecklist(): array
    {
        return [
            'lines' => 'Line items reviewed',
            'quantities' => 'Quantities verified against BOQ',
            'amounts' => 'Amounts and retention checked',
            'supporting_records' => 'Supporting site records considered',
        ];
    }

    public static function hasCriticalWarnings(array $warnings): bool
    {
        foreach ($warnings as $warning) {
            $warning = strtolower((string)$warning);
            if (str_contains($warning, 'no ipc line') || str_contains($warning, 'exceeds boq') || str_contains($warning, 'does not match')) {
                return true;
            }
        }

        return false;
    }

    public static function canAccessProject(int $userId, string $role, int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }
        if (strtolower($role) === 'superadmin') {
            return true;
        }

        $project = Project::findDetailed($projectId);
        if ((int)($project['consultant_id'] ?? 0) === $userId) {
            return true;
        }

        return ProjectAssignment::canManageProject($userId, $projectId, 'consultant');
    }

    public static function payload(array $row): array
    {
        $row['workflow'] = IPC::workflowState($row);
        $row['warnings_count'] = (int)($row['warnings_count'] ?? 0);
        $row['line_count'] = (int)($row['line_count'] ?? 0);
        $row['line_total'] = (float)($row['line_total'] ?? 0);
        $row['can_certify'] = (string)($row['status'] ?? '') === self::CERTIFIABLE_STATUS;
        return $row;
    }

    private static function selectSql(): string
    {
        return "
            SELECT i.*, p.name AS project_name, p.contract_sum, p.pct_complete, p.status AS project_status,
                   c.name AS constituency_name, w.name AS ward_name,
                   CONCAT(contractor.first_name, ' ', contractor.last_name) AS contractor_name,
                   contractor.email AS contractor_email,
                   CONCAT(certifier.first_name, ' ', certifier.last_name) AS certified_by_name,
                   latest.action AS last_action, latest.comments AS last_comment, latest.actioned_at AS last_actioned_at,
                   CONCAT(actor.first_name, ' ', actor.last_name) AS last_action_by,
                   COUNT(il.id) AS line_count,
                   COALESCE(SUM(il.amount), 0) AS line_total,
                   COALESCE(SUM(CASE WHEN bi.id IS NOT NULL AND il.cumulative_qty > bi.quantity THEN 1 ELSE 0 END), 0) AS warnings_count
            FROM ipcs i
            JOIN projects p ON p.id = i.project_id
            JOIN users contractor ON contractor.id = i.contractor_id
            LEFT JOIN users certifier ON certifier.id = i.certified_by
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN ipc_lines il ON il.ipc_id = i.id
            LEFT JOIN boq_items bi ON bi.id = il.boq_item_id
            LEFT JOIN ipc_approvals latest ON latest.id = (
                SELECT ia.id FROM ipc_approvals ia
                WHERE ia.ipc_id = i.id
                ORDER BY ia.actioned_at DESC, ia.id DESC
                LIMIT 1
            )
            LEFT JOIN users actor ON actor.id = latest.action_by
        ";
    }

    private static function filterSql(int $userId, string $role, array $filters, bool $grouped = true): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::projectScopeSql($userId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'i.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'i.status = ?';
            $bindings[] = (string)$filters['status'];
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

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR contractor.first_name LIKE ? OR contractor.last_name LIKE ? OR contractor.email LIKE ? OR CAST(i.ipc_number AS CHAR) LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        $having = '';
        if ($grouped && !empty($filters['warnings'])) {
            $having = ' HAVING warnings_count > 0 OR ABS(line_total - i.gross_amount) > 1 OR line_count = 0';
        }

        return [
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ($grouped ? ' GROUP BY i.id' . $having : ''),
            $bindings,
        ];
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

    private static function numeric(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                $row[$key] = str_contains((string)$value, '.') ? (float)$value : (int)$value;
            }
        }

        return $row;
    }
}
