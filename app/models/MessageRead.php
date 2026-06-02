<?php

class MessageRead extends Model
{
    protected static string $table = 'message_reads';

    public static function markThread(int $threadId, int $userId): int
    {
        $messages = Database::fetchAll(
            'SELECT id FROM messages WHERE thread_id = ? AND sender_id <> ? AND is_deleted = 0',
            [$threadId, $userId]
        );

        $count = 0;
        foreach ($messages as $message) {
            Database::query(
                "INSERT INTO message_reads (message_id, user_id, read_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)",
                [(int)$message['id'], $userId]
            );
            $count++;
        }

        Database::query('UPDATE message_participants SET last_read_at = NOW() WHERE thread_id = ? AND user_id = ?', [$threadId, $userId]);
        return $count;
    }
}
