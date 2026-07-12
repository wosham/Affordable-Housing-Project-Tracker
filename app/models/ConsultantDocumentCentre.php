<?php

class ConsultantDocumentCentre
{
    public const REVIEW_STATUSES = ['pending', 'reviewed', 'returned', 'flagged', 'closed'];
    public const DOCUMENT_CATEGORIES = ['contract', 'drawing', 'spec', 'report', 'correspondence', 'shop-drawing', 'quality-test', 'other'];

    public static function projects(int $userId, string $role): array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.pct_complete, c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}
             ORDER BY p.name ASC",
            $bindings
        );
    }

    public static function documentSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::documentFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS new_week,
                COALESCE(SUM(CASE WHEN d.category IN ('drawing','shop-drawing') THEN 1 ELSE 0 END), 0) AS drawings,
                COALESCE(SUM(CASE WHEN d.category = 'report' THEN 1 ELSE 0 END), 0) AS reports,
                COALESCE(SUM(CASE WHEN d.category IN ('contract','spec') THEN 1 ELSE 0 END), 0) AS controls,
                COALESCE(SUM(CASE WHEN COALESCE(d.consultant_review_status, 'pending') = 'pending' THEN 1 ELSE 0 END), 0) AS pending
             FROM documents d
             JOIN projects p ON p.id = d.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numbers($row, ['total', 'new_week', 'drawings', 'reports', 'controls', 'pending']);
    }

    public static function documents(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::documentFilterSql($userId, $role, $filters);

        return Database::fetchAll(
            "SELECT d.*, p.name AS project_name, c.name AS constituency_name,
                    CONCAT(COALESCE(up.first_name, ''), ' ', COALESCE(up.last_name, '')) AS uploaded_by_name,
                    CONCAT(COALESCE(rv.first_name, ''), ' ', COALESCE(rv.last_name, '')) AS reviewed_by_name
             FROM documents d
             JOIN projects p ON p.id = d.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users up ON up.id = d.uploaded_by
             LEFT JOIN users rv ON rv.id = d.consultant_reviewed_by
             {$where}
             ORDER BY FIELD(COALESCE(d.consultant_review_status, 'pending'), 'pending', 'flagged', 'returned', 'reviewed', 'closed'),
                      d.created_at DESC, d.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function documentCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::documentFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM documents d JOIN projects p ON p.id = d.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Portfolio attention queue (not limited to the current page). */
    public static function documentAttentionItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $attentionFilters = $filters;
        unset($attentionFilters['status']);
        [$where, $bindings] = self::documentFilterSql($userId, $role, $attentionFilters);
        $signal = "COALESCE(d.consultant_review_status, 'pending') IN ('pending', 'flagged', 'returned')";
        $where = $where === '' ? " WHERE {$signal}" : $where . " AND {$signal}";

        return Database::fetchAll(
            "SELECT d.id, d.original_name, d.category, d.consultant_review_status, d.created_at,
                    p.id AS project_id, p.name AS project_name
             FROM documents d
             JOIN projects p ON p.id = d.project_id
             {$where}
             ORDER BY FIELD(COALESCE(d.consultant_review_status, 'pending'), 'flagged', 'returned', 'pending', 'reviewed', 'closed'),
                      d.created_at DESC, d.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function siteReportSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::siteReportFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN s.diary_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS this_week,
                COALESCE(SUM(CASE WHEN COALESCE(s.consultant_review_status, 'pending') = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN COALESCE(s.consultant_review_status, 'pending') = 'flagged' THEN 1 ELSE 0 END), 0) AS flagged,
                COALESCE(SUM(CASE WHEN COALESCE(s.consultant_review_status, 'pending') IN ('reviewed','closed') THEN 1 ELSE 0 END), 0) AS reviewed,
                COUNT(DISTINCT s.project_id) AS projects
             FROM site_diaries s
             JOIN projects p ON p.id = s.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numbers($row, ['total', 'this_week', 'pending', 'flagged', 'reviewed', 'projects']);
    }

    public static function siteReports(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::siteReportFilterSql($userId, $role, $filters);

        return Database::fetchAll(
            "SELECT s.*, p.name AS project_name, c.name AS constituency_name,
                    CONCAT(COALESCE(rec.first_name, ''), ' ', COALESCE(rec.last_name, '')) AS recorded_by_name,
                    CONCAT(COALESCE(rv.first_name, ''), ' ', COALESCE(rv.last_name, '')) AS reviewed_by_name
             FROM site_diaries s
             JOIN projects p ON p.id = s.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users rec ON rec.id = s.recorded_by
             LEFT JOIN users rv ON rv.id = s.consultant_reviewed_by
             {$where}
             ORDER BY FIELD(COALESCE(s.consultant_review_status, 'pending'), 'pending', 'flagged', 'returned', 'reviewed', 'closed'),
                      s.diary_date DESC, s.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function siteReportCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::siteReportFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM site_diaries s JOIN projects p ON p.id = s.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Open site reports across the portfolio (not page-bound). */
    public static function siteReportAttentionItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $attentionFilters = $filters;
        unset($attentionFilters['status']);
        [$where, $bindings] = self::siteReportFilterSql($userId, $role, $attentionFilters);
        $signal = "COALESCE(s.consultant_review_status, 'pending') IN ('pending', 'flagged', 'returned')";
        $where = $where === '' ? " WHERE {$signal}" : $where . " AND {$signal}";

        return Database::fetchAll(
            "SELECT s.id, s.report_title, s.diary_date, s.consultant_review_status,
                    p.id AS project_id, p.name AS project_name
             FROM site_diaries s
             JOIN projects p ON p.id = s.project_id
             {$where}
             ORDER BY FIELD(COALESCE(s.consultant_review_status, 'pending'), 'flagged', 'returned', 'pending', 'reviewed', 'closed'),
                      s.diary_date DESC, s.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function applyAction(string $type, int $id, string $action, string $note, int $userId, string $role): array
    {
        $type = strtolower(trim($type));
        $action = strtolower(trim($action));
        $note = trim($note);

        if (!in_array($action, ['review', 'return', 'flag', 'close'], true)) {
            throw new InvalidArgumentException('Unsupported review action.');
        }

        if (in_array($action, ['return', 'flag', 'close'], true) && $note === '') {
            throw new InvalidArgumentException('A clear review note is required for this action.');
        }

        return match ($type) {
            'document' => self::applyDocumentAction($id, $action, $note, $userId, $role),
            'site_report' => self::applySiteReportAction($id, $action, $note, $userId, $role),
            default => throw new InvalidArgumentException('Unknown review type.'),
        };
    }

    public static function statusClass(?string $status): string
    {
        return match ((string)$status) {
            'reviewed', 'closed' => 'badge--success',
            'returned' => 'badge--warning',
            'flagged' => 'badge--danger',
            default => 'badge--info',
        };
    }

    public static function fileAvailable(array $document): bool
    {
        $path = trim((string)($document['filename'] ?? ''));
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        return is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\')));
    }

    private static function applyDocumentAction(int $id, string $action, string $note, int $userId, string $role): array
    {
        $row = self::findDocument($id, $userId, $role);
        if (!$row) {
            throw new RuntimeException('Document could not be found.');
        }

        $status = self::actionStatus($action);
        Database::query(
            'UPDATE documents SET consultant_review_status = ?, consultant_review_note = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW() WHERE id = ?',
            [$status, $note !== '' ? $note : null, $userId, $id]
        );

        self::afterAction('consultant_document_' . $status, 'documents', $id, (string)$row['project_name'], $action, $note, 'admin/consultant/documents.php');
        return ['message' => 'Document review saved.'];
    }

    private static function applySiteReportAction(int $id, string $action, string $note, int $userId, string $role): array
    {
        $row = self::findSiteReport($id, $userId, $role);
        if (!$row) {
            throw new RuntimeException('Site report could not be found.');
        }

        $status = self::actionStatus($action);
        Database::query(
            'UPDATE site_diaries SET consultant_review_status = ?, consultant_review_note = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW(), approved_by = CASE WHEN ? IN ("reviewed","closed") THEN ? ELSE approved_by END, approved_at = CASE WHEN ? IN ("reviewed","closed") THEN COALESCE(approved_at, NOW()) ELSE approved_at END WHERE id = ?',
            [$status, $note !== '' ? $note : null, $userId, $status, $userId, $status, $id]
        );

        self::afterAction('consultant_site_report_' . $status, 'site_diaries', $id, (string)$row['project_name'], $action, $note, 'admin/consultant/site-reports.php');
        return ['message' => 'Site report review saved.'];
    }

    private static function findDocument(int $id, int $userId, string $role): ?array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? 'd.id = ?' : 'd.id = ? AND ' . $scopeSql;
        return Database::fetch("SELECT d.*, p.name AS project_name FROM documents d JOIN projects p ON p.id = d.project_id WHERE {$where} LIMIT 1", array_merge([$id], $bindings));
    }

    private static function findSiteReport(int $id, int $userId, string $role): ?array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? 's.id = ?' : 's.id = ? AND ' . $scopeSql;
        return Database::fetch("SELECT s.*, p.name AS project_name FROM site_diaries s JOIN projects p ON p.id = s.project_id WHERE {$where} LIMIT 1", array_merge([$id], $bindings));
    }

    private static function actionStatus(string $action): string
    {
        return match ($action) {
            'return' => 'returned',
            'flag' => 'flagged',
            'close' => 'closed',
            default => 'reviewed',
        };
    }

    private static function documentFilterSql(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, $filters);
        if (!empty($filters['category']) && in_array((string)$filters['category'], self::DOCUMENT_CATEGORIES, true)) {
            $where[] = 'd.category = ?';
            $bindings[] = (string)$filters['category'];
        }
        self::exact($where, $bindings, "COALESCE(d.consultant_review_status, 'pending')", $filters['status'] ?? '');
        self::dateFilters($where, $bindings, 'DATE(d.created_at)', $filters);
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR d.original_name LIKE ? OR d.filename LIKE ? OR d.description LIKE ? OR d.category LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term);
        }
        return [' WHERE ' . implode(' AND ', $where ?: ['1=1']), $bindings];
    }

    private static function siteReportFilterSql(int $userId, string $role, array $filters, bool $withSearch = true): array
    {
        [$where, $bindings] = self::baseWhere($userId, $role, $filters);
        self::exact($where, $bindings, "COALESCE(s.consultant_review_status, 'pending')", $filters['status'] ?? '');
        self::dateFilters($where, $bindings, 's.diary_date', $filters);
        if ($withSearch && trim((string)($filters['q'] ?? '')) !== '') {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(p.name LIKE ? OR s.report_title LIKE ? OR s.work_done LIKE ? OR s.issues_raised LIKE ? OR s.next_day_plan LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term);
        }
        return [' WHERE ' . implode(' AND ', $where ?: ['1=1']), $bindings];
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

    private static function exact(array &$where, array &$bindings, string $column, mixed $value): void
    {
        $value = trim((string)$value);
        if ($value !== '' && in_array($value, self::REVIEW_STATUSES, true)) {
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

    private static function numbers(array $row, array $keys): array
    {
        foreach ($keys as $key) {
            $row[$key] = isset($row[$key]) && is_numeric($row[$key]) ? (float)$row[$key] : 0;
        }
        return $row;
    }

    private static function afterAction(string $event, string $module, int $targetId, string $projectName, string $action, string $note, string $link): void
    {
        self::audit($event, $module, $targetId, ['project' => $projectName, 'action' => $action, 'note' => $note]);
        Notification::pushRole('manager', $event, 'Consultant review updated', 'A review for ' . $projectName . ' has been updated.', $link);
        Notification::pushRole('superadmin', $event, 'Consultant review updated', 'A review for ' . $projectName . ' has been updated.', $link);
    }

    private static function audit(string $action, string $module, int $targetId, array $details): void
    {
        try {
            Database::query(
                'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [Auth::id(), $action, $module, $targetId, json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]
            );
        } catch (Throwable) {
        }
    }
}
