<?php

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    public const SEVERITIES = ['info', 'warning', 'critical'];

    public static function record(int $userId, string $action, string $module, int $targetId = 0, array $details = []): void
    {
        Logger::log($action, $module, $targetId, $details + ['recorded_by' => 'AuditLog::record']);
    }

    public static function items(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY a.created_at DESC, a.id DESC' . $limitSql, $bindings);
    }

    public static function countItems(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             {$where}",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function summary(array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($filters, false);
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN DATE(a.created_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS today,
                COALESCE(SUM(CASE WHEN a.action = 'login_failed' THEN 1 ELSE 0 END), 0) AS failed_logins,
                COALESCE(SUM(CASE WHEN COALESCE(a.severity, 'info') = 'critical' THEN 1 ELSE 0 END), 0) AS critical,
                COALESCE(SUM(CASE WHEN a.action LIKE '%delete%' OR a.action = 'delete' THEN 1 ELSE 0 END), 0) AS destructive,
                COALESCE(SUM(CASE WHEN a.action IN ('approve','reject','certify','endorse') THEN 1 ELSE 0 END), 0) AS approvals,
                COUNT(DISTINCT a.user_id) AS unique_actors,
                COUNT(DISTINCT a.ip) AS unique_ips
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             {$where}",
            $bindings
        ) ?: [];
    }

    public static function findDetailed(int $id): ?array
    {
        $row = Database::fetch(self::selectSql() . ' WHERE a.id = ? LIMIT 1', [$id]);
        return $row ? self::payload($row) : null;
    }

    public static function exportRows(array $filters = []): array
    {
        return array_map(static function (array $row): array {
            $payload = self::payload($row);
            return [
                'id' => $payload['id'],
                'created_at' => $payload['created_at'],
                'actor' => $payload['actor_name'],
                'email' => $payload['actor_email'],
                'role' => $payload['actor_role'],
                'action' => $payload['action'],
                'module' => $payload['module'],
                'target_id' => $payload['target_id'],
                'severity' => $payload['severity'],
                'ip' => $payload['ip'],
                'route' => $payload['route'],
                'request_method' => $payload['request_method'],
                'details' => json_encode($payload['details'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }, self::items($filters, 0, 0));
    }

    public static function payload(array $row): array
    {
        $details = self::parseJson($row['details_json'] ?? null);
        $metadata = self::parseJson($row['metadata_json'] ?? null);
        $actorName = trim((string)($row['actor_name'] ?? ''));
        if ($actorName === '') {
            $actorName = !empty($row['actor_email']) ? (string)$row['actor_email'] : 'System / Unknown';
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'user_id' => (int)($row['user_id'] ?? 0),
            'actor_name' => $actorName,
            'actor_email' => (string)($row['actor_email'] ?? ''),
            'actor_role' => (string)($row['actor_role'] ?? $row['role_slug'] ?? ''),
            'action' => (string)($row['action'] ?? ''),
            'module' => (string)($row['module'] ?? ''),
            'target_id' => (int)($row['target_id'] ?? 0),
            'details' => $details,
            'details_json' => (string)($row['details_json'] ?? ''),
            'metadata' => $metadata,
            'ip' => (string)($row['ip'] ?? ''),
            'user_agent' => (string)($row['user_agent'] ?? ''),
            'request_method' => (string)($row['request_method'] ?? ''),
            'route' => (string)($row['route'] ?? ''),
            'severity' => (string)($row['severity'] ?? self::severityFor((string)($row['action'] ?? ''), (string)($row['module'] ?? ''))),
            'event_hash' => (string)($row['event_hash'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'summary' => self::summaryText($details),
        ];
    }

    public static function actionOptions(): array
    {
        return Database::fetchAll('SELECT action, COUNT(*) AS total FROM audit_logs GROUP BY action ORDER BY action ASC');
    }

    public static function moduleOptions(): array
    {
        return Database::fetchAll('SELECT module, COUNT(*) AS total FROM audit_logs GROUP BY module ORDER BY module ASC');
    }

    public static function userOptions(): array
    {
        return Database::fetchAll(
            "SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.email, r.slug AS role_slug
             FROM audit_logs a
             JOIN users u ON u.id = a.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             ORDER BY u.first_name ASC, u.last_name ASC"
        );
    }

    public static function severityOptions(): array
    {
        return self::SEVERITIES;
    }

    public static function severityFor(string $action, string $module = ''): string
    {
        $action = strtolower($action);
        $module = strtolower($module);
        if (str_contains($action, 'delete') || str_contains($action, 'reject') || $action === 'login_failed') {
            return 'critical';
        }
        if ($action === 'reset' || str_contains($module, 'settings') || str_contains($action, 'cancel') || str_contains($action, 'failed') || str_contains($action, 'update-status') || str_contains($action, 'geo')) {
            return 'warning';
        }
        return 'info';
    }

    public static function parseJson(mixed $json): array
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : ['raw' => $json];
    }

    private static function summaryText(array $details): string
    {
        if ($details === []) {
            return 'No details recorded';
        }
        foreach (['name', 'email', 'project', 'ipc_number', 'reason', 'key', 'status', 'path'] as $key) {
            if (!empty($details[$key]) && is_scalar($details[$key])) {
                return (string)$details[$key];
            }
        }
        $parts = [];
        foreach ($details as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = status_label((string)$key) . ': ' . (string)$value;
            }
            if (count($parts) >= 2) {
                break;
            }
        }
        return $parts ? implode(' · ', $parts) : 'Structured details available';
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                a.*,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS actor_name,
                u.email AS actor_email,
                COALESCE(a.actor_role, r.slug) AS role_slug
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN roles r ON r.id = u.role_id
        ";
    }

    private static function filterSql(array $filters, bool $includeQuick = true): array
    {
        $where = [];
        $bindings = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(a.action LIKE ? OR a.module LIKE ? OR a.details_json LIKE ? OR a.ip LIKE ? OR a.route LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term, $term, $term);
        }

        foreach (['action' => 'a.action', 'module' => 'a.module', 'severity' => 'a.severity', 'ip' => 'a.ip'] as $key => $column) {
            if (!empty($filters[$key])) {
                $where[] = $column . ' = ?';
                $bindings[] = (string)$filters[$key];
            }
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = ?';
            $bindings[] = (int)$filters['user_id'];
        }

        if (!empty($filters['role'])) {
            $where[] = 'COALESCE(a.actor_role, r.slug) = ?';
            $bindings[] = (string)$filters['role'];
        }

        if (!empty($filters['target_id'])) {
            $where[] = 'a.target_id = ?';
            $bindings[] = (int)$filters['target_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(a.created_at) >= ?';
            $bindings[] = (string)$filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(a.created_at) <= ?';
            $bindings[] = (string)$filters['date_to'];
        }

        if ($includeQuick && !empty($filters['quick'])) {
            match ((string)$filters['quick']) {
                'failed-login' => $where[] = "a.action = 'login_failed'",
                'destructive' => $where[] = "(a.action LIKE '%delete%' OR a.action = 'delete')",
                'approvals' => $where[] = "a.action IN ('approve','reject','certify','endorse')",
                'critical' => $where[] = "COALESCE(a.severity, 'info') = 'critical'",
                default => null,
            };
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
