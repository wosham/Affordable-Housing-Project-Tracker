<?php
class Announcement extends Model
{
    protected static string $table = 'announcements';
    // Columns: id, author_id, title, body, target_roles_json, is_pinned, created_at, expires_at
}
