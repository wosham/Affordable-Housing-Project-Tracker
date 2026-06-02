<?php

class Message extends Model
{
    protected static string $table = 'messages';

    public static function createForThread(int $threadId, int $senderId, string $body, ?int $parentId = null): int
    {
        $body = trim($body);
        if ($threadId <= 0 || $senderId <= 0 || $body === '') {
            throw new InvalidArgumentException('Message body is required.');
        }

        return (int)self::create([
            'thread_id' => $threadId,
            'sender_id' => $senderId,
            'parent_id' => $parentId && $parentId > 0 ? $parentId : null,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function forThread(int $threadId, int $limit = 100): array
    {
        return Database::fetchAll("
            SELECT
                m.*,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS sender_name,
                u.email AS sender_email,
                u.avatar AS sender_avatar,
                r.slug AS sender_role,
                r.name AS sender_role_name
            FROM messages m
            INNER JOIN users u ON u.id = m.sender_id
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE m.thread_id = ?
            ORDER BY m.created_at ASC, m.id ASC
            LIMIT " . max(1, $limit), [$threadId]);
    }

    public static function softDelete(int $messageId, int $userId, bool $force = false): bool
    {
        $message = self::find($messageId);
        if (!$message) {
            return false;
        }

        if (!$force && (int)$message['sender_id'] !== $userId) {
            return false;
        }

        Database::query(
            'UPDATE messages SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?',
            [$userId, $messageId]
        );

        return true;
    }

    public static function payload(array $row, array $attachments = []): array
    {
        $isDeleted = (int)($row['is_deleted'] ?? 0) === 1;
        return [
            'id' => (int)($row['id'] ?? 0),
            'threadId' => (int)($row['thread_id'] ?? 0),
            'senderId' => (int)($row['sender_id'] ?? 0),
            'senderName' => trim((string)($row['sender_name'] ?? '')) ?: 'Staff User',
            'senderEmail' => (string)($row['sender_email'] ?? ''),
            'senderRole' => (string)($row['sender_role'] ?? ''),
            'senderRoleName' => (string)($row['sender_role_name'] ?? ''),
            'body' => $isDeleted ? 'This message was deleted.' : (string)($row['body'] ?? ''),
            'isDeleted' => $isDeleted,
            'createdAt' => (string)($row['created_at'] ?? ''),
            'createdLabel' => format_datetime($row['created_at'] ?? null),
            'timeAgo' => time_ago($row['created_at'] ?? null),
            'attachments' => $attachments,
        ];
    }
}
