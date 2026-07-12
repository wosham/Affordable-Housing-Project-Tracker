<?php

class ConsultantContractDecision
{
    public const REVIEW_STATUSES = ['pending', 'recommended', 'returned', 'rejected', 'flagged'];
    public const EOT_STATUSES = ['pending', 'granted', 'partially-granted', 'rejected'];
    public const VARIATION_STATUSES = ['pending', 'approved', 'rejected'];
    public const DELAY_CATEGORIES = ['weather', 'design', 'utilities', 'access', 'materials', 'labour', 'authority', 'other'];
    public const COST_IMPACTS = ['none', 'low', 'moderate', 'high', 'critical'];

    public static function projects(int $userId, string $role): array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}
             ORDER BY p.name ASC",
            $bindings
        );
    }

    public static function eotSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::eotFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN COALESCE(e.consultant_review_status, 'pending') = 'pending' THEN 1 ELSE 0 END), 0) AS pending_review,
                COALESCE(SUM(CASE WHEN e.consultant_review_status = 'recommended' THEN 1 ELSE 0 END), 0) AS recommended,
                COALESCE(SUM(CASE WHEN e.consultant_review_status IN ('returned','rejected') THEN 1 ELSE 0 END), 0) AS returned_count,
                COALESCE(SUM(CASE WHEN e.consultant_review_status = 'flagged' THEN 1 ELSE 0 END), 0) AS flagged,
                COALESCE(SUM(e.days_requested), 0) AS requested_days,
                COALESCE(SUM(COALESCE(e.consultant_recommended_days, 0)), 0) AS recommended_days
             FROM eot_requests e
             JOIN projects p ON p.id = e.project_id
             JOIN users submitter ON submitter.id = e.submitted_by
             {$where}",
            $bindings
        ) ?: [];

        return self::numericDefaults($row, ['total', 'pending_review', 'recommended', 'returned_count', 'flagged', 'requested_days', 'recommended_days']);
    }

    public static function eots(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::eotFilterSql($userId, $role, $filters);
        return Database::fetchAll(
            self::eotSelect() . $where . "
             ORDER BY FIELD(COALESCE(e.consultant_review_status, 'pending'), 'pending', 'flagged', 'returned', 'rejected', 'recommended'),
                      e.created_at DESC, e.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function eotCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::eotFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM eot_requests e JOIN projects p ON p.id = e.project_id JOIN users submitter ON submitter.id = e.submitted_by {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Pending EOT reviews across portfolio (not page-bound). */
    public static function eotPendingItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $queueFilters = $filters;
        $queueFilters['review_status'] = 'pending';
        [$where, $bindings] = self::eotFilterSql($userId, $role, $queueFilters);

        return Database::fetchAll(
            self::eotSelect() . $where . "
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function variationSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::variationFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN COALESCE(v.consultant_review_status, 'pending') = 'pending' THEN 1 ELSE 0 END), 0) AS pending_review,
                COALESCE(SUM(CASE WHEN v.consultant_review_status = 'recommended' THEN 1 ELSE 0 END), 0) AS recommended,
                COALESCE(SUM(CASE WHEN v.consultant_review_status IN ('returned','rejected') THEN 1 ELSE 0 END), 0) AS returned_count,
                COALESCE(SUM(CASE WHEN v.consultant_review_status = 'flagged' THEN 1 ELSE 0 END), 0) AS flagged,
                COALESCE(SUM(v.amount), 0) AS requested_value,
                COALESCE(SUM(COALESCE(v.consultant_recommended_amount, 0)), 0) AS recommended_value
             FROM variations v
             JOIN projects p ON p.id = v.project_id
             JOIN users submitter ON submitter.id = v.submitted_by
             {$where}",
            $bindings
        ) ?: [];

        return self::numericDefaults($row, ['total', 'pending_review', 'recommended', 'returned_count', 'flagged', 'requested_value', 'recommended_value']);
    }

    public static function variations(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::variationFilterSql($userId, $role, $filters);
        return Database::fetchAll(
            self::variationSelect() . $where . "
             ORDER BY FIELD(COALESCE(v.consultant_review_status, 'pending'), 'pending', 'flagged', 'returned', 'rejected', 'recommended'),
                      v.created_at DESC, v.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function variationCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::variationFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM variations v JOIN projects p ON p.id = v.project_id JOIN users submitter ON submitter.id = v.submitted_by {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Pending variation reviews across portfolio (not page-bound). */
    public static function variationPendingItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $queueFilters = $filters;
        $queueFilters['review_status'] = 'pending';
        [$where, $bindings] = self::variationFilterSql($userId, $role, $queueFilters);

        return Database::fetchAll(
            self::variationSelect() . $where . "
             ORDER BY v.created_at DESC, v.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function applyAction(string $type, int $id, string $action, array $payload, int $userId, string $role): array
    {
        return match ($type) {
            'eot' => self::applyEotAction($id, $action, $payload, $userId, $role),
            'variation' => self::applyVariationAction($id, $action, $payload, $userId, $role),
            default => throw new InvalidArgumentException('Unknown contract item.'),
        };
    }

    public static function statusClass(string $status): string
    {
        return match ($status) {
            'recommended', 'approved', 'granted', 'partially-granted' => 'badge--success',
            'returned', 'rejected', 'flagged', 'critical' => 'badge--danger',
            'pending', 'moderate', 'high' => 'badge--warning',
            default => 'badge--info',
        };
    }

    private static function applyEotAction(int $id, string $action, array $payload, int $userId, string $role): array
    {
        $record = Database::fetch(self::eotSelect() . ' WHERE e.id = ?' . self::scopeAnd($userId, $role, 'p') . ' LIMIT 1', array_merge([$id], self::scopeBindings($userId, $role)));
        if (!$record) {
            throw new RuntimeException('EOT request could not be found.');
        }

        $reviewStatus = self::actionStatus($action);
        $note = trim(strip_tags((string)($payload['note'] ?? '')));
        if ($reviewStatus !== 'recommended' && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }
        $requestedDays = (int)($record['days_requested'] ?? 0);
        $recommendedDays = max(0, min($requestedDays, (int)($payload['recommended_days'] ?? $requestedDays)));
        if ($reviewStatus !== 'recommended') {
            $recommendedDays = 0;
        }
        $category = self::option((string)($payload['delay_category'] ?? 'other'), self::DELAY_CATEGORIES, 'other');
        $docsChecked = !empty($payload['documents_checked']) ? 1 : 0;

        Database::query(
            'UPDATE eot_requests
             SET consultant_review_status = ?, consultant_review_note = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW(),
                 consultant_recommended_days = ?, consultant_delay_category = ?, consultant_documents_checked = ?
             WHERE id = ?',
            [$reviewStatus, $note !== '' ? $note : null, $userId, $recommendedDays > 0 ? $recommendedDays : null, $category, $docsChecked, $id]
        );
        self::afterAction('consultant_eot_review', 'eot_requests', $id, $record['project_name'], $action, $note, 'admin/manager/eot-requests.php');

        return ['message' => 'EOT review saved.'];
    }

    private static function applyVariationAction(int $id, string $action, array $payload, int $userId, string $role): array
    {
        $record = Database::fetch(self::variationSelect() . ' WHERE v.id = ?' . self::scopeAnd($userId, $role, 'p') . ' LIMIT 1', array_merge([$id], self::scopeBindings($userId, $role)));
        if (!$record) {
            throw new RuntimeException('Variation could not be found.');
        }

        $reviewStatus = self::actionStatus($action);
        $note = trim(strip_tags((string)($payload['note'] ?? '')));
        if ($reviewStatus !== 'recommended' && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }
        $requestedAmount = (float)($record['amount'] ?? 0);
        $recommendedAmount = max(0, min($requestedAmount, (float)($payload['recommended_amount'] ?? $requestedAmount)));
        if ($reviewStatus !== 'recommended') {
            $recommendedAmount = 0;
        }
        $timeDays = max(0, min(365, (int)($payload['time_impact_days'] ?? $record['impact_on_time_days'] ?? 0)));
        $costImpact = self::option((string)($payload['cost_impact_status'] ?? 'moderate'), self::COST_IMPACTS, 'moderate');
        $docsChecked = !empty($payload['documents_checked']) ? 1 : 0;

        Database::query(
            'UPDATE variations
             SET consultant_review_status = ?, consultant_review_note = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW(),
                 consultant_recommended_amount = ?, consultant_time_impact_days = ?, consultant_cost_impact_status = ?, consultant_documents_checked = ?
             WHERE id = ?',
            [$reviewStatus, $note !== '' ? $note : null, $userId, $recommendedAmount > 0 ? $recommendedAmount : null, $timeDays, $costImpact, $docsChecked, $id]
        );
        self::afterAction('consultant_variation_review', 'variations', $id, $record['project_name'], $action, $note, 'admin/manager/reports.php');

        return ['message' => 'Variation review saved.'];
    }

    private static function actionStatus(string $action): string
    {
        return match ($action) {
            'recommend', 'approve', 'review' => 'recommended',
            'return' => 'returned',
            'reject' => 'rejected',
            'flag' => 'flagged',
            default => throw new InvalidArgumentException('Unsupported review action.'),
        };
    }

    private static function eotSelect(): string
    {
        return "SELECT e.*, p.name AS project_name, p.est_delivery,
                       CONCAT(submitter.first_name, ' ', submitter.last_name) AS submitter_name,
                       CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS consultant_reviewer_name
                FROM eot_requests e
                JOIN projects p ON p.id = e.project_id
                JOIN users submitter ON submitter.id = e.submitted_by
                LEFT JOIN users reviewer ON reviewer.id = e.consultant_reviewed_by";
    }

    private static function variationSelect(): string
    {
        return "SELECT v.*, p.name AS project_name,
                       CONCAT(submitter.first_name, ' ', submitter.last_name) AS submitter_name,
                       CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS consultant_reviewer_name
                FROM variations v
                JOIN projects p ON p.id = v.project_id
                JOIN users submitter ON submitter.id = v.submitted_by
                LEFT JOIN users reviewer ON reviewer.id = v.consultant_reviewed_by";
    }

    private static function eotFilterSql(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::exact($where, $bindings, 'e.status', $filters['status'] ?? '');
        self::exact($where, $bindings, 'e.consultant_review_status', $filters['review_status'] ?? '');
        self::exact($where, $bindings, 'e.consultant_delay_category', $filters['delay_category'] ?? '');
        self::dateFilters($where, $bindings, 'DATE(e.created_at)', $filters);
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR e.reason LIKE ? OR CAST(e.eot_number AS CHAR) LIKE ? OR submitter.first_name LIKE ? OR submitter.last_name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function variationFilterSql(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, 'p', $filters);
        self::exact($where, $bindings, 'v.status', $filters['status'] ?? '');
        self::exact($where, $bindings, 'v.consultant_review_status', $filters['review_status'] ?? '');
        self::exact($where, $bindings, 'v.consultant_cost_impact_status', $filters['cost_impact'] ?? '');
        self::dateFilters($where, $bindings, 'DATE(v.created_at)', $filters);
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR v.description LIKE ? OR v.reason LIKE ? OR CAST(v.vo_number AS CHAR) LIKE ? OR submitter.first_name LIKE ? OR submitter.last_name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function baseWhere(int $userId, string $role, string $projectAlias, array $filters): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, $projectAlias);
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = "{$projectAlias}.id = ?";
            $bindings[] = (int)$filters['project_id'];
        }
        return [$where, $bindings];
    }

    private static function scopeSql(int $userId, string $role, string $projectAlias): array
    {
        if (strtolower($role) === 'superadmin') {
            return ['', []];
        }
        return [
            "({$projectAlias}.consultant_id = ? OR EXISTS (
                SELECT 1 FROM project_assignments cpa
                WHERE cpa.project_id = {$projectAlias}.id
                  AND cpa.user_id = ?
                  AND cpa.status = 'active'
            ))",
            [$userId, $userId],
        ];
    }

    private static function scopeAnd(int $userId, string $role, string $projectAlias): string
    {
        [$sql] = self::scopeSql($userId, $role, $projectAlias);
        return $sql === '' ? '' : ' AND ' . $sql;
    }

    private static function scopeBindings(int $userId, string $role): array
    {
        return strtolower($role) === 'superadmin' ? [] : [$userId, $userId];
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
        if (!empty($filters['from'])) {
            $where[] = "{$column} >= ?";
            $bindings[] = (string)$filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = "{$column} <= ?";
            $bindings[] = (string)$filters['to'];
        }
    }

    private static function option(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function numericDefaults(array $row, array $keys): array
    {
        foreach ($keys as $key) {
            $row[$key] = isset($row[$key]) && is_numeric($row[$key]) ? (float)$row[$key] : 0;
        }
        return $row;
    }

    private static function afterAction(string $event, string $module, int $targetId, string $projectName, string $action, string $note, string $managerLink): void
    {
        self::audit($event, $module, $targetId, ['project' => $projectName, 'action' => $action, 'note' => $note]);
        Notification::pushRole('manager', $event, 'Consultant contract review updated', 'A consultant contract review for ' . $projectName . ' has been updated.', $managerLink);
        Notification::pushRole('superadmin', $event, 'Consultant contract review updated', 'A consultant contract review for ' . $projectName . ' has been updated.', 'admin/superadmin/dashboard.php');
    }

    private static function audit(string $action, string $module, int $targetId, array $details): void
    {
        try {
            Database::query(
                'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    Auth::id(),
                    $action,
                    $module,
                    $targetId,
                    json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]
            );
        } catch (Throwable) {
        }
    }
}
