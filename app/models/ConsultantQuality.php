<?php

class ConsultantQuality
{
    public const REVIEW_STATUSES = ['pending', 'reviewed', 'returned', 'flagged', 'closed'];
    public const ISSUE_STATUSES = ['open', 'in-progress', 'resolved', 'closed'];
    public const SEVERITIES = ['minor', 'major', 'critical'];

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

    public static function summary(string $type, int $userId, string $role, array $filters = []): array
    {
        return match ($type) {
            'quality_test' => self::qualitySummary($userId, $role, $filters),
            'inspection' => self::inspectionSummary($userId, $role, $filters),
            'ncr' => self::ncrSummary($userId, $role, $filters),
            'defect' => self::defectSummary($userId, $role, $filters),
            default => [],
        };
    }

    public static function items(string $type, int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        return match ($type) {
            'quality_test' => self::qualityTests($userId, $role, $filters, $limit, $offset),
            'inspection' => self::inspections($userId, $role, $filters, $limit, $offset),
            'ncr' => self::ncrs($userId, $role, $filters, $limit, $offset),
            'defect' => self::defects($userId, $role, $filters, $limit, $offset),
            default => [],
        };
    }

    public static function count(string $type, int $userId, string $role, array $filters = []): int
    {
        [$table, $alias, $joinUser] = self::tableMeta($type);
        [$where, $bindings] = self::filterSql($type, $userId, $role, $filters);
        $sql = "SELECT COUNT(*) AS total FROM {$table} {$alias} JOIN projects p ON p.id = {$alias}.project_id {$joinUser} {$where}";
        $row = Database::fetch($sql, $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Priority / pending / flagged records for side panel (portfolio-wide). */
    public static function priorityItems(string $type, int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $priorityFilters = $filters;
        // Prefer pending/flagged unless a review filter already set.
        if (empty($priorityFilters['review_status'])) {
            // Pull pending+flagged by ordering; filter in SQL.
        }
        $items = self::items($type, $userId, $role, $priorityFilters, max(30, $limit * 3), 0);
        $priority = array_values(array_filter(
            $items,
            static function (array $item): bool {
                $review = (string)($item['consultant_review_status'] ?? 'pending');
                return in_array($review, ['pending', 'flagged', 'returned'], true);
            }
        ));
        return array_slice($priority, 0, max(1, min(20, $limit)));
    }

    public static function applyAction(string $type, int $id, string $action, array $payload, int $userId, string $role): array
    {
        $record = self::findScoped($type, $id, $userId, $role);
        if (!$record) {
            throw new RuntimeException('Quality record could not be found or is outside your project scope.');
        }

        $reviewStatus = self::actionReviewStatus($action);
        $note = trim(strip_tags((string)($payload['note'] ?? '')));
        if (in_array($reviewStatus, ['returned', 'flagged', 'closed'], true) && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }
        $severity = self::option((string)($payload['severity'] ?? ($record['severity'] ?? 'minor')), self::SEVERITIES, (string)($record['severity'] ?? 'minor'));
        $docsChecked = !empty($payload['documents_checked']) ? 1 : 0;

        match ($type) {
            'quality_test' => self::updateQualityTest($id, $reviewStatus, $note, $severity, $docsChecked, $userId),
            'inspection' => self::updateInspection($id, $reviewStatus, $note, $severity, $docsChecked, $userId),
            'ncr' => self::updateNcr($id, $action, $reviewStatus, $note, $severity, $docsChecked, $userId),
            'defect' => self::updateDefect($id, $action, $reviewStatus, $note, $severity, $docsChecked, $userId),
            default => throw new InvalidArgumentException('Unknown quality record type.'),
        };

        self::afterAction('consultant_quality_review', self::tableMeta($type)[0], $id, (string)$record['project_name'], $action, $note);
        return ['message' => 'Quality review saved.'];
    }

    public static function statusClass(string $status): string
    {
        return match ($status) {
            'pass', 'reviewed', 'closed', 'resolved', 'complete' => 'badge--success',
            'fail', 'returned', 'flagged', 'critical', 'rejected' => 'badge--danger',
            'pending', 'open', 'in-progress', 'major' => 'badge--warning',
            default => 'badge--info',
        };
    }

    private static function qualitySummary(int $userId, string $role, array $filters): array
    {
        [$where, $bindings] = self::filterSql('quality_test', $userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(q.pass_fail = 'pass'), 0) AS passed,
                    COALESCE(SUM(q.pass_fail = 'fail'), 0) AS failed,
                    COALESCE(SUM(q.pass_fail = 'pending'), 0) AS pending,
                    COALESCE(SUM(q.consultant_review_status = 'flagged'), 0) AS flagged,
                    COALESCE(SUM(q.test_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS this_month
             FROM quality_tests q JOIN projects p ON p.id = q.project_id LEFT JOIN users actor ON actor.id = q.tested_by {$where}",
            $bindings
        ) ?: [];
        return self::numbers($row, ['total', 'passed', 'failed', 'pending', 'flagged', 'this_month']);
    }

    private static function inspectionSummary(int $userId, string $role, array $filters): array
    {
        [$where, $bindings] = self::filterSql('inspection', $userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(i.witness_required = 1 AND COALESCE(i.outcome, '') = ''), 0) AS pending_witness,
                    COALESCE(SUM(LOWER(COALESCE(i.outcome, '')) IN ('pass','passed','accepted','complete','completed')), 0) AS accepted,
                    COALESCE(SUM(LOWER(COALESCE(i.outcome, '')) IN ('fail','failed','rejected','returned')), 0) AS returned,
                    COALESCE(SUM(i.witness_required = 1), 0) AS hold_points,
                    COALESCE(SUM(i.inspection_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)), 0) AS due_week
             FROM inspection_test_plans i JOIN projects p ON p.id = i.project_id LEFT JOIN users actor ON actor.id = i.inspected_by {$where}",
            $bindings
        ) ?: [];
        return self::numbers($row, ['total', 'pending_witness', 'accepted', 'returned', 'hold_points', 'due_week']);
    }

    private static function ncrSummary(int $userId, string $role, array $filters): array
    {
        [$where, $bindings] = self::filterSql('ncr', $userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(n.status = 'open'), 0) AS open_items,
                    COALESCE(SUM(n.status = 'in-progress'), 0) AS in_progress,
                    COALESCE(SUM(n.severity IN ('major','critical')), 0) AS serious,
                    COALESCE(SUM(n.status = 'closed'), 0) AS closed,
                    COALESCE(SUM(n.status <> 'closed' AND n.raised_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY)), 0) AS overdue
             FROM non_conformance_reports n JOIN projects p ON p.id = n.project_id LEFT JOIN users actor ON actor.id = n.raised_by {$where}",
            $bindings
        ) ?: [];
        return self::numbers($row, ['total', 'open_items', 'in_progress', 'serious', 'closed', 'overdue']);
    }

    private static function defectSummary(int $userId, string $role, array $filters): array
    {
        [$where, $bindings] = self::filterSql('defect', $userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(d.status = 'open'), 0) AS open_items,
                    COALESCE(SUM(d.status = 'in-progress'), 0) AS in_progress,
                    COALESCE(SUM(d.status = 'resolved'), 0) AS resolved,
                    COALESCE(SUM(d.status = 'closed'), 0) AS closed,
                    COALESCE(SUM(d.severity IN ('major','critical')), 0) AS serious
             FROM defects d JOIN projects p ON p.id = d.project_id LEFT JOIN users actor ON actor.id = d.raised_by LEFT JOIN users assigned ON assigned.id = d.assigned_to {$where}",
            $bindings
        ) ?: [];
        return self::numbers($row, ['total', 'open_items', 'in_progress', 'resolved', 'closed', 'serious']);
    }

    private static function qualityTests(int $userId, string $role, array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = self::filterSql('quality_test', $userId, $role, $filters);
        return Database::fetchAll(
            self::qualitySelect() . $where . " ORDER BY FIELD(COALESCE(q.consultant_review_status,'pending'),'pending','flagged','returned','reviewed','closed'), q.test_date DESC, q.id DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    private static function inspections(int $userId, string $role, array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = self::filterSql('inspection', $userId, $role, $filters);
        return Database::fetchAll(
            self::inspectionSelect() . $where . " ORDER BY FIELD(COALESCE(i.consultant_review_status,'pending'),'pending','flagged','returned','reviewed','closed'), COALESCE(i.inspection_date, i.created_at) DESC, i.id DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    private static function ncrs(int $userId, string $role, array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = self::filterSql('ncr', $userId, $role, $filters);
        return Database::fetchAll(
            self::ncrSelect() . $where . " ORDER BY FIELD(n.severity,'critical','major','minor'), FIELD(COALESCE(n.consultant_review_status,'pending'),'pending','flagged','returned','reviewed','closed'), n.raised_date DESC, n.id DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    private static function defects(int $userId, string $role, array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = self::filterSql('defect', $userId, $role, $filters);
        return Database::fetchAll(
            self::defectSelect() . $where . " ORDER BY FIELD(d.severity,'critical','major','minor'), FIELD(COALESCE(d.consultant_review_status,'pending'),'pending','flagged','returned','reviewed','closed'), COALESCE(d.due_date, d.raised_date) ASC, d.id DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    private static function updateQualityTest(int $id, string $reviewStatus, string $note, string $severity, int $docsChecked, int $userId): void
    {
        Database::query('UPDATE quality_tests SET consultant_review_status = ?, consultant_review_note = ?, consultant_severity = ?, consultant_documents_checked = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW() WHERE id = ?', [$reviewStatus, $note ?: null, $severity, $docsChecked, $userId, $id]);
    }

    private static function updateInspection(int $id, string $reviewStatus, string $note, string $severity, int $docsChecked, int $userId): void
    {
        Database::query('UPDATE inspection_test_plans SET consultant_review_status = ?, consultant_review_note = ?, consultant_severity = ?, consultant_documents_checked = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW() WHERE id = ?', [$reviewStatus, $note ?: null, $severity, $docsChecked, $userId, $id]);
    }

    private static function updateNcr(int $id, string $action, string $reviewStatus, string $note, string $severity, int $docsChecked, int $userId): void
    {
        $statusSql = in_array($action, ['close', 'closed'], true) ? ", status = 'closed', closed_by = ?, closed_date = CURDATE()" : '';
        $bindings = [$reviewStatus, $note ?: null, $severity, $docsChecked, $userId];
        if ($statusSql !== '') {
            $bindings[] = $userId;
        }
        $bindings[] = $id;
        Database::query("UPDATE non_conformance_reports SET consultant_review_status = ?, consultant_review_note = ?, consultant_severity = ?, consultant_documents_checked = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW() {$statusSql} WHERE id = ?", $bindings);
    }

    private static function updateDefect(int $id, string $action, string $reviewStatus, string $note, string $severity, int $docsChecked, int $userId): void
    {
        $statusSql = match ($action) {
            'resolve', 'resolved' => ", status = 'resolved'",
            'close', 'closed' => ", status = 'closed', closed_date = CURDATE()",
            'reopen' => ", status = 'open', closed_date = NULL",
            default => '',
        };
        Database::query("UPDATE defects SET consultant_review_status = ?, consultant_review_note = ?, consultant_severity = ?, consultant_documents_checked = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW() {$statusSql} WHERE id = ?", [$reviewStatus, $note ?: null, $severity, $docsChecked, $userId, $id]);
    }

    private static function actionReviewStatus(string $action): string
    {
        return match ($action) {
            'review', 'accept', 'resolve', 'resolved' => 'reviewed',
            'return' => 'returned',
            'flag' => 'flagged',
            'close', 'closed' => 'closed',
            'reopen' => 'pending',
            default => throw new InvalidArgumentException('Unsupported quality action.'),
        };
    }

    private static function findScoped(string $type, int $id, int $userId, string $role): ?array
    {
        $select = match ($type) {
            'quality_test' => self::qualitySelect(),
            'inspection' => self::inspectionSelect(),
            'ncr' => self::ncrSelect(),
            'defect' => self::defectSelect(),
            default => throw new InvalidArgumentException('Unknown quality record type.'),
        };
        $alias = self::alias($type);
        return Database::fetch($select . " WHERE {$alias}.id = ?" . self::scopeAnd($userId, $role, 'p') . ' LIMIT 1', array_merge([$id], self::scopeBindings($userId, $role)));
    }

    private static function qualitySelect(): string
    {
        return "SELECT q.*, p.name AS project_name, CONCAT(actor.first_name, ' ', actor.last_name) AS actor_name, CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS reviewer_name FROM quality_tests q JOIN projects p ON p.id = q.project_id LEFT JOIN users actor ON actor.id = q.tested_by LEFT JOIN users reviewer ON reviewer.id = q.consultant_reviewed_by";
    }

    private static function inspectionSelect(): string
    {
        return "SELECT i.*, p.name AS project_name, CONCAT(actor.first_name, ' ', actor.last_name) AS actor_name, CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS reviewer_name FROM inspection_test_plans i JOIN projects p ON p.id = i.project_id LEFT JOIN users actor ON actor.id = i.inspected_by LEFT JOIN users reviewer ON reviewer.id = i.consultant_reviewed_by";
    }

    private static function ncrSelect(): string
    {
        return "SELECT n.*, p.name AS project_name, CONCAT(actor.first_name, ' ', actor.last_name) AS actor_name, CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS reviewer_name FROM non_conformance_reports n JOIN projects p ON p.id = n.project_id LEFT JOIN users actor ON actor.id = n.raised_by LEFT JOIN users reviewer ON reviewer.id = n.consultant_reviewed_by";
    }

    private static function defectSelect(): string
    {
        return "SELECT d.*, p.name AS project_name, CONCAT(actor.first_name, ' ', actor.last_name) AS actor_name, CONCAT(assigned.first_name, ' ', assigned.last_name) AS assigned_name, CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS reviewer_name FROM defects d JOIN projects p ON p.id = d.project_id LEFT JOIN users actor ON actor.id = d.raised_by LEFT JOIN users assigned ON assigned.id = d.assigned_to LEFT JOIN users reviewer ON reviewer.id = d.consultant_reviewed_by";
    }

    private static function filterSql(string $type, int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, $filters);
        $alias = self::alias($type);
        self::exact($where, $bindings, "{$alias}.consultant_review_status", $filters['review_status'] ?? '');
        if (in_array($type, ['quality_test'], true)) {
            self::exact($where, $bindings, 'q.pass_fail', $filters['result'] ?? '');
            self::dateFilters($where, $bindings, 'q.test_date', $filters);
        } elseif ($type === 'inspection') {
            self::exact($where, $bindings, 'i.outcome', $filters['outcome'] ?? '');
            if (($filters['witness'] ?? '') !== '') {
                $where[] = 'i.witness_required = ?';
                $bindings[] = (int)$filters['witness'];
            }
            self::dateFilters($where, $bindings, 'i.inspection_date', $filters);
        } elseif ($type === 'ncr') {
            self::exact($where, $bindings, 'n.status', $filters['status'] ?? '');
            self::exact($where, $bindings, 'n.severity', $filters['severity'] ?? '');
            self::dateFilters($where, $bindings, 'n.raised_date', $filters);
        } else {
            self::exact($where, $bindings, 'd.status', $filters['status'] ?? '');
            self::exact($where, $bindings, 'd.severity', $filters['severity'] ?? '');
            self::dateFilters($where, $bindings, 'd.raised_date', $filters);
        }
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = self::searchSql($type);
            array_push($bindings, ...array_fill(0, substr_count(self::searchSql($type), '?'), $term));
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function searchSql(string $type): string
    {
        return match ($type) {
            'quality_test' => '(p.name LIKE ? OR q.test_type LIKE ? OR q.location_on_site LIKE ? OR q.result LIKE ? OR q.lab_ref LIKE ?)',
            'inspection' => '(p.name LIKE ? OR i.activity LIKE ? OR i.hold_point LIKE ? OR i.outcome LIKE ?)',
            'ncr' => '(p.name LIKE ? OR n.description LIKE ? OR n.root_cause LIKE ? OR n.corrective_action LIKE ?)',
            'defect' => '(p.name LIKE ? OR d.description LIKE ? OR d.location LIKE ? OR assigned.first_name LIKE ? OR assigned.last_name LIKE ?)',
            default => '(p.name LIKE ?)',
        };
    }

    private static function tableMeta(string $type): array
    {
        return match ($type) {
            'quality_test' => ['quality_tests', 'q', 'LEFT JOIN users actor ON actor.id = q.tested_by'],
            'inspection' => ['inspection_test_plans', 'i', 'LEFT JOIN users actor ON actor.id = i.inspected_by'],
            'ncr' => ['non_conformance_reports', 'n', 'LEFT JOIN users actor ON actor.id = n.raised_by'],
            'defect' => ['defects', 'd', 'LEFT JOIN users actor ON actor.id = d.raised_by LEFT JOIN users assigned ON assigned.id = d.assigned_to'],
            default => throw new InvalidArgumentException('Unknown quality record type.'),
        };
    }

    private static function alias(string $type): string
    {
        return self::tableMeta($type)[1];
    }

    private static function baseWhere(int $userId, string $role, array $filters): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'p.id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        return [$where, $bindings];
    }

    private static function scopeSql(int $userId, string $role, string $projectAlias): array
    {
        if (strtolower($role) === 'superadmin') {
            return ['', []];
        }
        return ["({$projectAlias}.consultant_id = ? OR EXISTS (SELECT 1 FROM project_assignments cpa WHERE cpa.project_id = {$projectAlias}.id AND cpa.user_id = ? AND cpa.status = 'active'))", [$userId, $userId]];
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

    private static function numbers(array $row, array $keys): array
    {
        foreach ($keys as $key) {
            $row[$key] = isset($row[$key]) && is_numeric($row[$key]) ? (float)$row[$key] : 0;
        }
        return $row;
    }

    private static function afterAction(string $event, string $module, int $targetId, string $projectName, string $action, string $note): void
    {
        self::audit($event, $module, $targetId, ['project' => $projectName, 'action' => $action, 'note' => $note]);
        Notification::pushRole('manager', $event, 'Consultant quality review updated', 'A quality review for ' . $projectName . ' has been updated.', 'admin/manager/reports.php');
        Notification::pushRole('superadmin', $event, 'Consultant quality review updated', 'A quality review for ' . $projectName . ' has been updated.', 'admin/superadmin/dashboard.php');
    }

    private static function audit(string $action, string $module, int $targetId, array $details): void
    {
        try {
            Database::query('INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)', [Auth::id(), $action, $module, $targetId, json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
        } catch (Throwable) {
        }
    }
}
