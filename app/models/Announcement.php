<?php

class Announcement extends Model
{
    protected static string $table = 'announcements';

    public const TYPES = [
        'info' => ['label' => 'Information', 'icon' => 'fa-circle-info'],
        'success' => ['label' => 'Success', 'icon' => 'fa-circle-check'],
        'warning' => ['label' => 'Warning', 'icon' => 'fa-triangle-exclamation'],
        'critical' => ['label' => 'Critical', 'icon' => 'fa-circle-exclamation'],
        'maintenance' => ['label' => 'Maintenance', 'icon' => 'fa-screwdriver-wrench'],
        'deadline' => ['label' => 'Deadline', 'icon' => 'fa-calendar-day'],
        'policy' => ['label' => 'Policy', 'icon' => 'fa-file-shield'],
        'event' => ['label' => 'Event', 'icon' => 'fa-calendar-check'],
    ];

    public const STATUSES = ['draft', 'published', 'archived'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const ROLES = ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'];

    public static function adminList(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::adminWhere($filters);
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return Database::fetchAll(
            self::selectSql() . $where . '
             ORDER BY a.is_pinned DESC,
                      FIELD(a.priority, "urgent", "high", "normal", "low"),
                      COALESCE(a.published_at, a.created_at) DESC,
                      a.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $bindings
        );
    }

    public static function adminCount(array $filters = []): int
    {
        [$where, $bindings] = self::adminWhere($filters);
        $row = Database::fetch(
            'SELECT COUNT(*) AS aggregate FROM announcements a LEFT JOIN users u ON u.id = a.author_id' . $where,
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }

    public static function adminStats(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
                    SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) AS pinned,
                    SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN priority = 'urgent' OR type = 'critical' THEN 1 ELSE 0 END) AS urgent
             FROM announcements"
        );

        return array_map('intval', $row ?: []);
    }

    public static function findAdmin(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE a.id = ? LIMIT 1', [$id]);
    }

    public static function saveFromAdmin(array $input, ?int $id = null): int
    {
        $roles = self::normalizeRoles($input['target_roles'] ?? []);
        $status = self::normalizeStatus((string)($input['status'] ?? 'draft'));
        $publishedAt = self::normalizeDateTime((string)($input['published_at'] ?? ''));
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $payload = [
            'author_id' => (int)(Auth::id() ?: ($input['author_id'] ?? 1)),
            'title' => trim((string)($input['title'] ?? '')),
            'body' => trim((string)($input['body'] ?? '')),
            'type' => self::normalizeType((string)($input['type'] ?? 'info')),
            'status' => $status,
            'priority' => self::normalizePriority((string)($input['priority'] ?? 'normal')),
            'target_roles_json' => $roles === [] ? null : json_encode($roles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'is_pinned' => !empty($input['is_pinned']) ? 1 : 0,
            'cta_label' => self::nullableText($input['cta_label'] ?? null),
            'cta_url' => self::nullableText($input['cta_url'] ?? null),
            'published_at' => $publishedAt,
            'expires_at' => self::normalizeDateTime((string)($input['expires_at'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
            'archived_at' => $status === 'archived' ? date('Y-m-d H:i:s') : null,
        ];

        if ($id !== null && $id > 0) {
            self::update($id, $payload);
            self::notifyTargetUsersIfNeeded($id);
            return $id;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $newId = (int)self::create($payload);
        self::notifyTargetUsersIfNeeded($newId);
        return $newId;
    }

    public static function quickStatus(int $id, string $status): void
    {
        $status = self::normalizeStatus($status);
        $payload = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'archived_at' => $status === 'archived' ? date('Y-m-d H:i:s') : null,
        ];

        if ($status === 'published') {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        self::update($id, $payload);
        self::notifyTargetUsersIfNeeded($id);
    }

    public static function togglePinned(int $id): void
    {
        Database::query('UPDATE announcements SET is_pinned = 1 - COALESCE(is_pinned, 0), updated_at = NOW() WHERE id = ?', [$id]);
    }

    public static function activeForRole(?string $role, int $limit = 5): array
    {
        $role = strtolower(trim((string)$role));
        $limit = max(1, $limit);

        return Database::fetchAll(
            self::selectSql() . "
             WHERE a.status = 'published'
               AND (a.published_at IS NULL OR a.published_at <= NOW())
               AND (a.expires_at IS NULL OR a.expires_at >= NOW())
               AND (
                   a.target_roles_json IS NULL
                   OR a.target_roles_json = ''
                   OR JSON_CONTAINS(a.target_roles_json, JSON_QUOTE(?))
               )
             ORDER BY a.is_pinned DESC,
                      FIELD(a.priority, 'urgent', 'high', 'normal', 'low'),
                      COALESCE(a.published_at, a.created_at) DESC
             LIMIT " . $limit,
            [$role]
        );
    }

    public static function roleLabels(?string $json): array
    {
        $roles = json_decode((string)$json, true);
        if (!is_array($roles) || $roles === []) {
            return ['All staff'];
        }

        return array_map(static fn ($role): string => role_label((string)$role), $roles);
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type]['label'] ?? status_label($type);
    }

    public static function typeIcon(string $type): string
    {
        return self::TYPES[$type]['icon'] ?? 'fa-bullhorn';
    }

    private static function selectSql(): string
    {
        return 'SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS author_name, u.email AS author_email
                FROM announcements a
                LEFT JOIN users u ON u.id = a.author_id';
    }

    private static function adminWhere(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(a.title LIKE ? OR a.body LIKE ?)';
            $bindings[] = '%' . $filters['q'] . '%';
            $bindings[] = '%' . $filters['q'] . '%';
        }

        foreach (['status', 'type', 'priority'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = 'a.' . $field . ' = ?';
                $bindings[] = $filters[$field];
            }
        }

        if (($filters['role'] ?? '') !== '') {
            $where[] = '(a.target_roles_json IS NULL OR a.target_roles_json = "" OR JSON_CONTAINS(a.target_roles_json, JSON_QUOTE(?)))';
            $bindings[] = $filters['role'];
        }

        if (($filters['pinned'] ?? '') === '1') {
            $where[] = 'a.is_pinned = 1';
        }

        if (($filters['visibility'] ?? '') === 'active') {
            $where[] = "a.status = 'published' AND (a.published_at IS NULL OR a.published_at <= NOW()) AND (a.expires_at IS NULL OR a.expires_at >= NOW())";
        } elseif (($filters['visibility'] ?? '') === 'expired') {
            $where[] = 'a.expires_at IS NOT NULL AND a.expires_at < NOW()';
        }

        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $bindings];
    }

    private static function normalizeRoles(mixed $roles): array
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $roles = array_values(array_unique(array_filter(array_map(
            static fn ($role): string => strtolower(trim((string)$role)),
            $roles
        ))));

        return array_values(array_intersect($roles, self::ROLES));
    }

    private static function normalizeType(string $type): string
    {
        return array_key_exists($type, self::TYPES) ? $type : 'info';
    }

    private static function normalizeStatus(string $status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'draft';
    }

    private static function normalizePriority(string $priority): string
    {
        return in_array($priority, self::PRIORITIES, true) ? $priority : 'normal';
    }

    private static function normalizeDateTime(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private static function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private static function notifyTargetUsersIfNeeded(int $id): void
    {
        $announcement = self::findAdmin($id);
        if (!$announcement || (string)($announcement['status'] ?? '') !== 'published') {
            return;
        }

        $publishedAt = (string)($announcement['published_at'] ?? '');
        if ($publishedAt !== '' && strtotime($publishedAt) !== false && strtotime($publishedAt) > time()) {
            return;
        }

        $metadata = json_decode((string)($announcement['metadata_json'] ?? ''), true);
        $metadata = is_array($metadata) ? $metadata : [];
        if (!empty($metadata['notifications_sent_at'])) {
            return;
        }

        $roles = self::normalizeRoles(json_decode((string)($announcement['target_roles_json'] ?? '[]'), true) ?: []);
        $users = self::notificationUsers($roles);
        if ($users === []) {
            $metadata['notifications_sent_at'] = date('Y-m-d H:i:s');
            $metadata['notifications_sent_count'] = 0;
            self::storeMetadata($id, $metadata);
            return;
        }

        foreach ($users as $user) {
            Database::query(
                'INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at)
                 VALUES (?, "announcement", ?, ?, ?, 0, NOW())',
                [
                    (int)$user['id'],
                    'Announcement: ' . (string)$announcement['title'],
                    safe_truncate((string)$announcement['body'], 220),
                    'admin/index.php',
                ]
            );
        }

        $metadata['notifications_sent_at'] = date('Y-m-d H:i:s');
        $metadata['notifications_sent_count'] = count($users);
        $metadata['notifications_target_roles'] = $roles === [] ? ['all'] : $roles;
        self::storeMetadata($id, $metadata);
    }

    private static function notificationUsers(array $roles): array
    {
        if ($roles === []) {
            return Database::fetchAll(
                "SELECT u.id
                 FROM users u
                 WHERE u.status = 'active'
                 ORDER BY u.id ASC"
            );
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        return Database::fetchAll(
            "SELECT DISTINCT u.id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active'
               AND r.slug IN ({$placeholders})
             ORDER BY u.id ASC",
            $roles
        );
    }

    private static function storeMetadata(int $id, array $metadata): void
    {
        Database::query(
            'UPDATE announcements SET metadata_json = ?, updated_at = COALESCE(updated_at, NOW()) WHERE id = ?',
            [json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $id]
        );
    }
}
