<?php
class MessageThread extends Model
{
    protected static string $table = 'message_threads';
    // Columns: id, subject, type, project_id, created_by, created_at
    // Type: direct, group, project-channel

    public static function forUser(int $userId): array
    {
        // TODO: Phase 5 — Return threads where user is a participant, with unread count
        return [];
    }
}
