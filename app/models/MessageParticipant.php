<?php

class MessageParticipant extends Model
{
    protected static string $table = 'message_participants';

    public static function add(int $threadId, int $userId, string $role = '', bool $admin = false): void
    {
        if ($threadId <= 0 || $userId <= 0) {
            return;
        }

        Database::query(
            "INSERT INTO message_participants (thread_id, user_id, role_at_join, is_admin)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE is_archived = 0, archived_at = NULL",
            [$threadId, $userId, $role !== '' ? $role : null, $admin ? 1 : 0]
        );
    }

    public static function forThread(int $threadId): array
    {
        return Database::fetchAll("
            SELECT
                mp.*,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
                u.email,
                r.slug AS role_slug,
                r.name AS role_name
            FROM message_participants mp
            INNER JOIN users u ON u.id = mp.user_id
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE mp.thread_id = ?
            ORDER BY mp.is_admin DESC, u.first_name ASC, u.last_name ASC
        ", [$threadId]);
    }

    public static function userIds(int $threadId): array
    {
        return array_map('intval', array_column(self::forThread($threadId), 'user_id'));
    }

    public static function archiveForUser(int $threadId, int $userId, bool $archived): void
    {
        Database::query(
            'UPDATE message_participants SET is_archived = ?, archived_at = ? WHERE thread_id = ? AND user_id = ?',
            [$archived ? 1 : 0, $archived ? date('Y-m-d H:i:s') : null, $threadId, $userId]
        );
    }

    public static function canAccess(int $threadId, int $userId): bool
    {
        return Database::fetch('SELECT id FROM message_participants WHERE thread_id = ? AND user_id = ? LIMIT 1', [$threadId, $userId]) !== null;
    }
}
