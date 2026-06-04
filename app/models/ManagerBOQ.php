<?php

class ManagerBOQ
{
    public const REVIEW_STATUSES = ['pending', 'reviewed', 'needs-review', 'escalated'];
    public const RISK_STATUSES = ['normal', 'watch', 'high', 'critical'];

    public static function projects(int $userId, string $role): array
    {
        $projects = ProjectAssignment::managerProjects($userId, $role);
        if ($projects === []) {
            return [];
        }

        $ids = array_values(array_map(static fn (array $project): int => (int)$project['id'], $projects));
        [$in, $bindings] = self::inClause($ids);
        $rows = Database::fetchAll(
            "SELECT p.id, COUNT(bi.id) AS boq_items,
                    COALESCE(SUM(COALESCE(NULLIF(bi.amount, 0), COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))), 0) AS boq_value
             FROM projects p
             LEFT JOIN boq_items bi ON bi.project_id = p.id
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
            $project['boq_items'] = (int)($row['boq_items'] ?? 0);
            $project['boq_value'] = (float)($row['boq_value'] ?? 0);
        }
        unset($project);

        return $projects;
    }

    public static function list(int $userId, string $role, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $limitSql = ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY bi.section ASC, bi.item_no ASC, bi.id ASC' . $limitSql, $bindings);
    }

    public static function count(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM boq_items bi
             INNER JOIN projects p ON p.id = bi.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
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
                COUNT(*) AS total_items,
                COALESCE(SUM(COALESCE(NULLIF(bi.amount, 0), COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))), 0) AS contract_value,
                COALESCE(SUM(COALESCE(bi.certified_qty, 0) * COALESCE(bi.rate, 0)), 0) AS certified_value,
                COALESCE(SUM(COALESCE(bi.paid_qty, 0) * COALESCE(bi.rate, 0)), 0) AS paid_value,
                COALESCE(SUM(CASE WHEN COALESCE(bi.certified_qty, 0) > COALESCE(bi.quantity, 0) THEN 1 ELSE 0 END), 0) AS over_certified,
                COALESCE(SUM(CASE WHEN COALESCE(bi.paid_qty, 0) > COALESCE(bi.certified_qty, 0) THEN 1 ELSE 0 END), 0) AS overpaid,
                COALESCE(SUM(CASE WHEN COALESCE(bi.review_status, 'pending') IN ('pending','needs-review','escalated') THEN 1 ELSE 0 END), 0) AS needs_review,
                COALESCE(SUM(CASE WHEN COALESCE(bi.risk_status, 'normal') IN ('high','critical') THEN 1 ELSE 0 END), 0) AS high_risk
             FROM boq_items bi
             INNER JOIN projects p ON p.id = bi.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             {$where}",
            $bindings
        ) ?: [];

        $contract = (float)($row['contract_value'] ?? 0);
        $certified = (float)($row['certified_value'] ?? 0);
        $paid = (float)($row['paid_value'] ?? 0);

        return array_merge([
            'total_items' => 0,
            'contract_value' => 0,
            'certified_value' => 0,
            'paid_value' => 0,
            'remaining_value' => max(0, $contract - $certified),
            'payment_remaining' => max(0, $certified - $paid),
            'certified_percent' => $contract > 0 ? percentage(($certified / $contract) * 100) : 0,
            'paid_percent' => $certified > 0 ? percentage(($paid / $certified) * 100) : 0,
            'over_certified' => 0,
            'overpaid' => 0,
            'needs_review' => 0,
            'high_risk' => 0,
        ], array_map(static fn ($value) => is_numeric($value) ? (float)$value : $value, $row));
    }

    public static function sections(int $userId, string $role, int $projectId = 0): array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = [];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
        }
        if ($projectId > 0) {
            $where[] = 'bi.project_id = ?';
            $bindings[] = $projectId;
        }

        return Database::fetchAll(
            "SELECT bi.section, COUNT(*) AS total
             FROM boq_items bi
             INNER JOIN projects p ON p.id = bi.project_id
             " . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . "
             GROUP BY bi.section
             ORDER BY bi.section ASC",
            $bindings
        );
    }

    public static function insights(int $userId, string $role, array $filters = []): array
    {
        return [
            'needsReview' => array_map([self::class, 'payload'], self::list($userId, $role, array_merge($filters, ['review_status' => 'needs-review']), 5)),
            'overpaid' => array_map([self::class, 'payload'], self::list($userId, $role, array_merge($filters, ['risk' => 'overpaid']), 5)),
            'overCertified' => array_map([self::class, 'payload'], self::list($userId, $role, array_merge($filters, ['risk' => 'over-certified']), 5)),
        ];
    }

    public static function findScoped(int $id, int $userId, string $role): ?array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        array_unshift($bindings, $id);
        $where = ' WHERE bi.id = ?' . ($scopeSql !== '' ? ' AND ' . $scopeSql : '');

        return Database::fetch(self::selectSql() . $where . ' LIMIT 1', $bindings);
    }

    public static function canAccessProject(int $userId, string $role, int $projectId): bool
    {
        return ProjectAssignment::canManageProject($userId, $projectId, $role);
    }

    public static function recordReviewUpdate(int $itemId, int $projectId, int $userId, array $before, array $after, string $note = ''): void
    {
        try {
            Database::query(
                'INSERT INTO boq_review_updates
                    (boq_item_id, project_id, user_id, old_certified_qty, new_certified_qty, old_paid_qty, new_paid_qty, old_review_status, new_review_status, old_risk_status, new_risk_status, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $itemId,
                    $projectId,
                    $userId,
                    $before['certified_qty'] ?? null,
                    $after['certified_qty'] ?? null,
                    $before['paid_qty'] ?? null,
                    $after['paid_qty'] ?? null,
                    $before['review_status'] ?? null,
                    $after['review_status'] ?? null,
                    $before['risk_status'] ?? null,
                    $after['risk_status'] ?? null,
                    $note !== '' ? $note : null,
                ]
            );
        } catch (Throwable) {
        }
    }

    public static function payload(array $row): array
    {
        $item = BOQItem::payload($row);
        $reviewStatus = (string)($row['review_status'] ?? 'pending');
        $riskStatus = (string)($row['risk_status'] ?? 'normal');
        $computedRisks = $item['risks'] ?? [];

        return array_merge($item, [
            'review_status' => $reviewStatus,
            'review_status_label' => status_label($reviewStatus),
            'risk_status' => $riskStatus,
            'risk_status_label' => status_label($riskStatus),
            'manager_note' => (string)($row['manager_note'] ?? ''),
            'last_reviewed_at' => (string)($row['last_reviewed_at'] ?? ''),
            'last_reviewed_label' => format_datetime($row['last_reviewed_at'] ?? null),
            'last_reviewed_by_name' => trim((string)($row['last_reviewed_by_name'] ?? '')),
            'certified_updated_by_name' => trim((string)($row['certified_updated_by_name'] ?? '')),
            'paid_updated_by_name' => trim((string)($row['paid_updated_by_name'] ?? '')),
            'computed_risks' => $computedRisks,
            'risk_label' => $computedRisks ? implode(', ', array_map('status_label', $computedRisks)) : 'Clear',
        ]);
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                bi.*,
                p.name AS project_name,
                p.contractor_name,
                c.name AS constituency_name,
                COALESCE(CONCAT(updated.first_name, ' ', updated.last_name), '') AS updated_by_name,
                COALESCE(CONCAT(reviewed.first_name, ' ', reviewed.last_name), '') AS last_reviewed_by_name,
                COALESCE(CONCAT(certified.first_name, ' ', certified.last_name), '') AS certified_updated_by_name,
                COALESCE(CONCAT(paid.first_name, ' ', paid.last_name), '') AS paid_updated_by_name
            FROM boq_items bi
            INNER JOIN projects p ON p.id = bi.project_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN users updated ON updated.id = bi.updated_by
            LEFT JOIN users reviewed ON reviewed.id = bi.last_reviewed_by
            LEFT JOIN users certified ON certified.id = bi.certified_updated_by
            LEFT JOIN users paid ON paid.id = bi.paid_updated_by
        ";
    }

    private static function filterSql(int $userId, string $role, array $filters, bool $allowRisk = true): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }

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

        if (!empty($filters['review_status'])) {
            $where[] = 'COALESCE(bi.review_status, "pending") = ?';
            $bindings[] = (string)$filters['review_status'];
        }

        if (!empty($filters['risk_status'])) {
            $where[] = 'COALESCE(bi.risk_status, "normal") = ?';
            $bindings[] = (string)$filters['risk_status'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(bi.item_no LIKE ? OR bi.description LIKE ? OR bi.unit LIKE ? OR bi.section LIKE ? OR p.name LIKE ? OR bi.manager_note LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        $risk = (string)($filters['risk'] ?? '');
        if ($allowRisk && in_array($risk, BOQItem::riskOptions(), true)) {
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

    private static function scopeSql(int $userId, string $role, string $projectAlias): array
    {
        if ($role === 'superadmin') {
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
