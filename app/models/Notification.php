<?php

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function push(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        ?string $link = null,
        string $priority = 'normal',
        ?string $sourceModule = null,
        ?int $sourceId = null,
        array $metadata = []
    ): int {
        if ($userId <= 0 || trim($title) === '') {
            return 0;
        }

        return (int)self::create([
            'user_id' => $userId,
            'type' => trim($type) !== '' ? trim($type) : 'system',
            'priority' => $priority === 'urgent' ? 'urgent' : 'normal',
            'title' => trim($title),
            'body' => trim($body) !== '' ? trim($body) : null,
            'link' => trim((string)$link) !== '' ? trim((string)$link) : null,
            'source_module' => $sourceModule ? substr($sourceModule, 0, 80) : null,
            'source_id' => $sourceId && $sourceId > 0 ? $sourceId : null,
            'metadata_json' => $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function pushRole(string $roleSlug, string $type, string $title, string $body = '', ?string $link = null): int
    {
        $users = Database::fetchAll(
            "SELECT u.id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = ? AND u.status = 'active'",
            [$roleSlug]
        );

        return self::pushMany(array_map(static fn (array $user): int => (int)$user['id'], $users), $type, $title, $body, $link);
    }

    public static function pushMany(array $userIds, string $type, string $title, string $body = '', ?string $link = null): int
    {
        $sent = 0;
        foreach (array_values(array_unique(array_map('intval', $userIds))) as $userId) {
            if (self::push($userId, $type, $title, $body, $link) > 0) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function forUser(int $userId, int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT id, type, priority, title, body, link, source_module, source_id, is_read, read_at, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY is_read ASC, created_at DESC
             LIMIT ' . max(1, min(50, $limit)),
            [$userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
        return (int)($row['total'] ?? 0);
    }

    public static function markRead(int $id, int $userId): bool
    {
        $statement = Database::query(
            'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, NOW()) WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );

        return $statement->rowCount() > 0;
    }

    public static function markAllRead(int $userId): int
    {
        $statement = Database::query(
            'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, NOW()) WHERE user_id = ? AND is_read = 0',
            [$userId]
        );

        return $statement->rowCount();
    }

    public static function iconForType(?string $type): string
    {
        $type = strtolower(trim((string)$type));
        if (str_starts_with($type, 'ipc')) {
            return 'fa-file-invoice';
        }

        return match ($type) {
            'message' => 'fa-comments',
            'attendance' => 'fa-calendar-check',
            'programme', 'milestone' => 'fa-bullseye',
            'contact' => 'fa-inbox',
            'subscriber' => 'fa-envelope-open-text',
            'payment', 'finance' => 'fa-credit-card',
            'report' => 'fa-file-lines',
            'audit', 'security' => 'fa-shield-halved',
            'system', 'system-health' => 'fa-server',
            'rfi' => 'fa-circle-question',
            'announcement' => 'fa-bullhorn',
            'assignment' => 'fa-user-plus',
            default => 'fa-bell',
        };
    }

    public static function safeLink(?string $link): string
    {
        $link = trim((string)$link);
        if ($link === '') {
            return '#';
        }

        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        if (str_starts_with($link, Url::basePath())) {
            return $link;
        }

        if (preg_match('#^(admin|uploads|secure-uploads|legal)/#', $link) || str_ends_with($link, '.php')) {
            return Url::to($link);
        }

        return '#';
    }

    public static function payload(array $row): array
    {
        $isRead = (int)($row['is_read'] ?? 0) === 1;
        return [
            'id' => (int)($row['id'] ?? 0),
            'type' => (string)($row['type'] ?? 'system'),
            'priority' => (string)($row['priority'] ?? 'normal'),
            'title' => (string)($row['title'] ?? 'Notification'),
            'body' => (string)($row['body'] ?? ''),
            'bodyShort' => safe_truncate((string)($row['body'] ?? ''), 90),
            'link' => self::safeLink($row['link'] ?? ''),
            'icon' => self::iconForType($row['type'] ?? ''),
            'isRead' => $isRead,
            'isUnread' => !$isRead,
            'sourceModule' => (string)($row['source_module'] ?? ''),
            'sourceId' => (int)($row['source_id'] ?? 0),
            'createdAt' => (string)($row['created_at'] ?? ''),
            'createdLabel' => format_datetime($row['created_at'] ?? null),
            'timeAgo' => time_ago($row['created_at'] ?? null),
        ];
    }
}
