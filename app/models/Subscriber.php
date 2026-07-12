<?php

class Subscriber extends Model
{
    protected static string $table = 'subscribers';

    public const STATUSES = ['active', 'unsubscribed'];

    public static function items(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY s.subscribed_at DESC, s.id DESC' . $limitSql, $bindings);
    }

    public static function countItems(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM subscribers s {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function summary(array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($filters, false);
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END), 0) AS active,
                COALESCE(SUM(CASE WHEN s.status = 'unsubscribed' THEN 1 ELSE 0 END), 0) AS unsubscribed,
                COALESCE(SUM(CASE WHEN DATE(s.subscribed_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS today,
                COALESCE(SUM(CASE WHEN s.subscribed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS this_week,
                COALESCE(SUM(CASE WHEN s.subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS this_month,
                COALESCE(SUM(CASE WHEN s.source_url IS NOT NULL AND s.source_url <> '' THEN 1 ELSE 0 END), 0) AS with_source
             FROM subscribers s
             {$where}",
            $bindings
        ) ?: [];
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE s.email = ? LIMIT 1', [strtolower(trim($email))]);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE s.id = ? LIMIT 1', [$id]);
    }

    public static function subscribe(string $email, ?string $name, array $meta = []): array
    {
        $email = strtolower(trim($email));
        $existing = self::findByEmail($email);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $updates = [
                'name' => trim((string)$name) !== '' ? trim((string)$name) : ($existing['name'] ?? null),
                'status' => 'active',
                'source_url' => $meta['source_url'] ?? ($existing['source_url'] ?? null),
                'user_agent' => $meta['user_agent'] ?? ($existing['user_agent'] ?? null),
                'ip' => $meta['ip'] ?? ($existing['ip'] ?? null),
            ];

            if (($existing['status'] ?? '') === 'unsubscribed') {
                $updates['reactivated_at'] = $now;
                $updates['unsubscribed_at'] = null;
            }

            self::update((int)$existing['id'], $updates);
            return ['subscriber' => self::findDetailed((int)$existing['id']), 'created' => false, 'reactivated' => ($existing['status'] ?? '') === 'unsubscribed'];
        }

        $id = self::create([
            'email' => $email,
            'name' => trim((string)$name) !== '' ? trim((string)$name) : null,
            'status' => 'active',
            'ip' => $meta['ip'] ?? null,
            'source_url' => $meta['source_url'] ?? null,
            'user_agent' => $meta['user_agent'] ?? null,
        ]);

        return ['subscriber' => self::findDetailed((int)$id), 'created' => true, 'reactivated' => false];
    }

    public static function unsubscribe(int $id, int $userId = 0): bool
    {
        return self::update($id, [
            'status' => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId > 0 ? $userId : null,
        ]);
    }

    public static function reactivate(int $id, int $userId = 0): bool
    {
        return self::update($id, [
            'status' => 'active',
            'reactivated_at' => date('Y-m-d H:i:s'),
            'unsubscribed_at' => null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);
    }

    public static function exportRows(array $filters = []): array
    {
        return self::items($filters, 0, 0);
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function payload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)$row['id'],
            'has_source' => trim((string)($row['source_url'] ?? '')) !== '',
            'unsubscribe_url' => self::unsubscribeUrl($row),
        ]);
    }

    public static function unsubscribeUrl(array $row): string
    {
        $id = (int)($row['id'] ?? 0);
        $email = strtolower(trim((string)($row['email'] ?? '')));
        if ($id <= 0 || $email === '') {
            return '';
        }

        return Url::canonical('api/public/unsubscribe.php?id=' . $id . '&token=' . rawurlencode(self::unsubscribeToken($id, $email)));
    }

    public static function unsubscribeToken(int $id, string $email): string
    {
        $secret = (string)(getenv('APP_KEY') ?: getenv('RESEND_API_KEY') ?: Url::canonicalBase());
        return hash_hmac('sha256', $id . '|' . strtolower(trim($email)), $secret);
    }

    public static function findByUnsubscribeToken(int $id, string $token): ?array
    {
        $row = self::findDetailed($id);
        if (!$row) {
            return null;
        }

        $expected = self::unsubscribeToken($id, (string)($row['email'] ?? ''));
        return hash_equals($expected, $token) ? $row : null;
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                s.*,
                COALESCE(CONCAT(up.first_name, ' ', up.last_name), '') AS updated_by_name
            FROM subscribers s
            LEFT JOIN users up ON up.id = s.updated_by
        ";
    }

    private static function filterSql(array $filters, bool $includeSource = true): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $bindings[] = (string)$filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(s.subscribed_at) >= ?';
            $bindings[] = (string)$filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(s.subscribed_at) <= ?';
            $bindings[] = (string)$filters['date_to'];
        }

        if ($includeSource && isset($filters['source_state']) && $filters['source_state'] !== '') {
            if ($filters['source_state'] === 'with') {
                $where[] = "s.source_url IS NOT NULL AND s.source_url <> ''";
            } elseif ($filters['source_state'] === 'without') {
                $where[] = "(s.source_url IS NULL OR s.source_url = '')";
            }
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(s.email LIKE ? OR s.name LIKE ? OR s.ip LIKE ? OR s.source_url LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
