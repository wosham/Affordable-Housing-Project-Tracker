<?php

class BOQItem extends Model
{
    protected static string $table = 'boq_items';

    public const STATUSES = ['active', 'inactive', 'complete', 'flagged', 'closed'];
    public const RISKS = ['over-certified', 'overpaid', 'unpaid-certified', 'not-certified', 'amount-mismatch'];

    public static function forProject(int $projectId): array
    {
        return self::items(['project_id' => $projectId], 0, 0);
    }

    public static function items(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY bi.section ASC, bi.item_no ASC, bi.id ASC' . $limitSql, $bindings);
    }

    public static function countItems(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM boq_items bi
             JOIN projects p ON p.id = bi.project_id
             {$where}",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE bi.id = ? LIMIT 1', [$id]);
    }

    public static function summary(array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($filters, false);
        return Database::fetch(
            "SELECT
                COUNT(*) AS total_items,
                COALESCE(SUM(COALESCE(NULLIF(bi.amount, 0), COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))), 0) AS contract_value,
                COALESCE(SUM(COALESCE(bi.quantity, 0)), 0) AS contract_qty,
                COALESCE(SUM(COALESCE(bi.certified_qty, 0)), 0) AS certified_qty,
                COALESCE(SUM(COALESCE(bi.paid_qty, 0)), 0) AS paid_qty,
                COALESCE(SUM(COALESCE(bi.certified_qty, 0) * COALESCE(bi.rate, 0)), 0) AS certified_value,
                COALESCE(SUM(COALESCE(bi.paid_qty, 0) * COALESCE(bi.rate, 0)), 0) AS paid_value,
                COALESCE(SUM(CASE WHEN COALESCE(bi.certified_qty, 0) > COALESCE(bi.quantity, 0) THEN 1 ELSE 0 END), 0) AS over_certified,
                COALESCE(SUM(CASE WHEN COALESCE(bi.paid_qty, 0) > COALESCE(bi.certified_qty, 0) THEN 1 ELSE 0 END), 0) AS overpaid,
                COALESCE(SUM(CASE WHEN COALESCE(bi.certified_qty, 0) > 0 AND COALESCE(bi.paid_qty, 0) < COALESCE(bi.certified_qty, 0) THEN 1 ELSE 0 END), 0) AS unpaid_certified,
                COALESCE(SUM(CASE WHEN ABS(COALESCE(bi.amount, 0) - (COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))) > 1 THEN 1 ELSE 0 END), 0) AS amount_mismatch
             FROM boq_items bi
             JOIN projects p ON p.id = bi.project_id
             {$where}",
            $bindings
        ) ?: [];
    }

    public static function sections(?int $projectId = null): array
    {
        $bindings = [];
        $where = '';
        if ($projectId !== null && $projectId > 0) {
            $where = ' WHERE project_id = ?';
            $bindings[] = $projectId;
        }

        return Database::fetchAll(
            "SELECT section, COUNT(*) AS total
             FROM boq_items
             {$where}
             GROUP BY section
             ORDER BY section ASC",
            $bindings
        );
    }

    public static function projectOptions(): array
    {
        return Database::fetchAll(
            "SELECT
                p.id,
                p.name,
                p.contractor_name,
                c.name AS constituency_name,
                COUNT(bi.id) AS boq_items,
                COALESCE(SUM(COALESCE(NULLIF(bi.amount, 0), COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))), 0) AS boq_value
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN boq_items bi ON bi.project_id = p.id
             GROUP BY p.id, p.name, p.contractor_name, c.name
             ORDER BY p.name ASC"
        );
    }

    public static function usageByIPC(int $boqItemId): array
    {
        return Database::fetchAll(
            "SELECT
                il.id,
                il.qty_this_period,
                il.cumulative_qty,
                il.amount,
                i.id AS ipc_id,
                i.ipc_number,
                i.status AS ipc_status,
                i.submitted_at,
                i.approved_at,
                p.name AS project_name
             FROM ipc_lines il
             JOIN ipcs i ON i.id = il.ipc_id
             JOIN projects p ON p.id = i.project_id
             WHERE il.boq_item_id = ?
             ORDER BY COALESCE(i.submitted_at, i.created_at) DESC, i.id DESC",
            [$boqItemId]
        );
    }

    public static function updateQuantities(int $id, array $data, int $userId): bool
    {
        $current = self::findDetailed($id);
        if (!$current) {
            return false;
        }

        $certifiedQty = max(0, (float)($data['certified_qty'] ?? $current['certified_qty'] ?? 0));
        $paidQty = max(0, (float)($data['paid_qty'] ?? $current['paid_qty'] ?? 0));
        $status = (string)($data['status'] ?? $current['status'] ?? 'active');
        $notes = trim((string)($data['notes'] ?? $current['notes'] ?? ''));

        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $updates = [
            'certified_qty' => $certifiedQty,
            'paid_qty' => $paidQty,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $userId,
        ];

        if (abs($certifiedQty - (float)($current['certified_qty'] ?? 0)) > 0.0001) {
            $updates['last_certified_at'] = date('Y-m-d H:i:s');
        }

        if (abs($paidQty - (float)($current['paid_qty'] ?? 0)) > 0.0001) {
            $updates['last_paid_at'] = date('Y-m-d H:i:s');
        }

        return self::update($id, $updates);
    }

    public static function risks(array $item): array
    {
        $quantity = (float)($item['quantity'] ?? 0);
        $rate = (float)($item['rate'] ?? 0);
        $amount = (float)($item['amount'] ?? 0);
        $certifiedQty = (float)($item['certified_qty'] ?? 0);
        $paidQty = (float)($item['paid_qty'] ?? 0);
        $risks = [];

        if ($certifiedQty > $quantity && $quantity >= 0) {
            $risks[] = 'over-certified';
        }
        if ($paidQty > $certifiedQty) {
            $risks[] = 'overpaid';
        }
        if ($certifiedQty > 0 && $paidQty < $certifiedQty) {
            $risks[] = 'unpaid-certified';
        }
        if ($certifiedQty <= 0 && $quantity > 0) {
            $risks[] = 'not-certified';
        }
        if (abs($amount - ($quantity * $rate)) > 1) {
            $risks[] = 'amount-mismatch';
        }

        return $risks;
    }

    public static function payload(array $item): array
    {
        $quantity = (float)($item['quantity'] ?? 0);
        $rate = (float)($item['rate'] ?? 0);
        $amount = (float)($item['amount'] ?? 0);
        $certifiedQty = (float)($item['certified_qty'] ?? 0);
        $paidQty = (float)($item['paid_qty'] ?? 0);
        $contractValue = $amount > 0 ? $amount : $quantity * $rate;
        $certifiedValue = $certifiedQty * $rate;
        $paidValue = $paidQty * $rate;

        return array_merge($item, [
            'id' => (int)$item['id'],
            'project_id' => (int)$item['project_id'],
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $contractValue,
            'certified_qty' => $certifiedQty,
            'paid_qty' => $paidQty,
            'remaining_qty' => max(0, $quantity - $certifiedQty),
            'certified_value' => $certifiedValue,
            'paid_value' => $paidValue,
            'remaining_value' => max(0, $contractValue - $certifiedValue),
            'payment_remaining' => max(0, $certifiedValue - $paidValue),
            'certified_percent' => $quantity > 0 ? percentage(($certifiedQty / $quantity) * 100) : 0,
            'paid_percent' => $certifiedQty > 0 ? percentage(($paidQty / $certifiedQty) * 100) : 0,
            'risks' => self::risks($item),
        ]);
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function riskOptions(): array
    {
        return self::RISKS;
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                bi.*,
                p.name AS project_name,
                p.contractor_name,
                c.name AS constituency_name,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), '') AS updated_by_name
            FROM boq_items bi
            JOIN projects p ON p.id = bi.project_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN users u ON u.id = bi.updated_by
        ";
    }

    private static function filterSql(array $filters, bool $allowRisk = true): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['project_id'])) {
            $where[] = 'bi.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        if (!empty($filters['section'])) {
            $where[] = 'bi.section = ?';
            $bindings[] = (string)$filters['section'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'bi.status = ?';
            $bindings[] = (string)$filters['status'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(bi.item_no LIKE ? OR bi.description LIKE ? OR bi.unit LIKE ? OR bi.section LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        $risk = (string)($filters['risk'] ?? '');
        if ($allowRisk && in_array($risk, self::RISKS, true)) {
            $where[] = match ($risk) {
                'over-certified' => 'COALESCE(bi.certified_qty, 0) > COALESCE(bi.quantity, 0)',
                'overpaid' => 'COALESCE(bi.paid_qty, 0) > COALESCE(bi.certified_qty, 0)',
                'unpaid-certified' => 'COALESCE(bi.certified_qty, 0) > 0 AND COALESCE(bi.paid_qty, 0) < COALESCE(bi.certified_qty, 0)',
                'not-certified' => 'COALESCE(bi.certified_qty, 0) <= 0 AND COALESCE(bi.quantity, 0) > 0',
                'amount-mismatch' => 'ABS(COALESCE(bi.amount, 0) - (COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))) > 1',
                default => '1 = 1',
            };
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
