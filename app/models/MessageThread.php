<?php

class MessageThread extends Model
{
    protected static string $table = 'message_threads';

    public static function forUser(int $userId, array $filters = [], int $limit = 30, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($userId, $filters);
        $limitSql = ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::fetchAll(self::selectSql() . $where . "
            ORDER BY COALESCE(t.last_message_at, t.created_at) DESC, t.id DESC
            {$limitSql}
        ", $bindings);
    }

    public static function countForUser(int $userId, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($userId, $filters);
        array_shift($bindings);
        $row = Database::fetch("
            SELECT COUNT(*) AS aggregate
            FROM message_threads t
            INNER JOIN message_participants mp ON mp.thread_id = t.id
            LEFT JOIN projects p ON p.id = t.project_id
            {$where}
        ", $bindings);

        return (int)($row['aggregate'] ?? 0);
    }

    public static function findForUser(int $threadId, int $userId): ?array
    {
        return Database::fetch(self::selectSql() . "
            WHERE t.id = ? AND mp.user_id = ?
            LIMIT 1
        ", [$userId, $threadId, $userId]);
    }

    public static function createThread(string $subject, string $type, int $createdBy, ?int $projectId = null, string $priority = 'normal'): int
    {
        $subject = trim($subject) !== '' ? trim($subject) : 'New conversation';
        $type = in_array($type, ['direct', 'group', 'project-channel'], true) ? $type : 'direct';
        $priority = $priority === 'urgent' ? 'urgent' : 'normal';

        return (int)self::create([
            'subject' => substr($subject, 0, 255),
            'type' => $type,
            'priority' => $priority,
            'project_id' => $projectId && $projectId > 0 ? $projectId : null,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function touchLastMessage(int $threadId, int $messageId): void
    {
        Database::query('UPDATE message_threads SET last_message_id = ?, last_message_at = NOW() WHERE id = ?', [$messageId, $threadId]);
    }

    public static function recipientOptions(int $viewerId, string $viewerRole): array
    {
        $rows = Database::fetchAll("
            SELECT
                u.id,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
                u.email,
                r.slug AS role_slug,
                r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.status = 'active' AND u.id <> ?
            ORDER BY r.id ASC, u.first_name ASC, u.last_name ASC
        ", [$viewerId]);

        if ($viewerRole === 'superadmin') {
            return $rows;
        }

        $allowed = match ($viewerRole) {
            'manager' => ['superadmin', 'consultant', 'contractor', 'clerk', 'finance', 'intern'],
            'consultant' => ['superadmin', 'manager', 'contractor', 'clerk'],
            'contractor' => ['superadmin', 'manager', 'consultant', 'clerk', 'finance'],
            'clerk' => ['superadmin', 'manager', 'consultant', 'contractor', 'intern'],
            'finance' => ['superadmin', 'manager', 'contractor'],
            'intern' => ['superadmin', 'manager', 'clerk'],
            default => ['superadmin'],
        };

        return array_values(array_filter($rows, static fn (array $row): bool => in_array((string)($row['role_slug'] ?? ''), $allowed, true)));
    }

    public static function expandAudienceTargets(int $viewerId, string $viewerRole, array $recipientIds = [], array $targets = [], ?int $projectId = null): array
    {
        $allowedOptions = self::recipientOptions($viewerId, $viewerRole);
        $allowedIds = array_map('intval', array_column($allowedOptions, 'id'));
        $expanded = array_map('intval', $recipientIds);

        foreach (array_values(array_unique(array_map('strval', $targets))) as $target) {
            $target = trim($target);
            if ($target === '') {
                continue;
            }

            if ($target === 'all:staff') {
                $expanded = array_merge($expanded, self::activeUserIds($viewerId));
                continue;
            }

            if (str_starts_with($target, 'role:')) {
                $role = substr($target, 5);
                if (preg_match('/^[a-z0-9_-]+$/', $role)) {
                    $expanded = array_merge($expanded, self::activeUserIdsByRole($role, $viewerId));
                }
                continue;
            }

            if ($target === 'project:selected' && $projectId && $projectId > 0) {
                $expanded = array_merge($expanded, self::projectTeamUserIds($projectId, $viewerId));
                continue;
            }

            if (preg_match('/^project:(\d+)$/', $target, $match)) {
                $expanded = array_merge($expanded, self::projectTeamUserIds((int)$match[1], $viewerId));
            }
        }

        $expanded = array_values(array_unique(array_filter($expanded, static fn (int $id): bool => $id > 0 && $id !== $viewerId)));
        if ($viewerRole !== 'superadmin') {
            $expanded = array_values(array_intersect($expanded, $allowedIds));
        }

        return $expanded;
    }

    public static function audienceOptions(int $viewerId, string $viewerRole): array
    {
        $roles = ['manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'];
        $options = [];
        $allowed = self::recipientOptions($viewerId, $viewerRole);
        $allowedByRole = [];
        foreach ($allowed as $row) {
            $slug = (string)($row['role_slug'] ?? '');
            $allowedByRole[$slug] = ($allowedByRole[$slug] ?? 0) + 1;
        }

        $allCount = $viewerRole === 'superadmin'
            ? count(self::activeUserIds($viewerId))
            : count($allowed);

        if ($allCount > 0) {
            $options[] = ['value' => 'all:staff', 'label' => 'All allowed staff', 'count' => $allCount];
        }

        foreach ($roles as $role) {
            $count = $viewerRole === 'superadmin'
                ? count(self::activeUserIdsByRole($role, $viewerId))
                : (int)($allowedByRole[$role] ?? 0);
            if ($count > 0) {
                $options[] = ['value' => 'role:' . $role, 'label' => 'All ' . role_label($role), 'count' => $count];
            }
        }

        return $options;
    }

    public static function payload(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'subject' => (string)($row['subject'] ?? ''),
            'type' => (string)($row['type'] ?? 'direct'),
            'priority' => (string)($row['priority'] ?? 'normal'),
            'status' => (string)($row['status'] ?? 'open'),
            'projectId' => (int)($row['project_id'] ?? 0),
            'projectName' => (string)($row['project_name'] ?? ''),
            'lastMessage' => safe_truncate((string)($row['last_message_body'] ?? ''), 120),
            'lastSender' => trim((string)($row['last_sender_name'] ?? '')),
            'lastMessageAt' => (string)($row['last_message_at'] ?? $row['created_at'] ?? ''),
            'lastMessageLabel' => format_datetime($row['last_message_at'] ?? $row['created_at'] ?? null),
            'timeAgo' => time_ago($row['last_message_at'] ?? $row['created_at'] ?? null),
            'unreadCount' => (int)($row['unread_count'] ?? 0),
            'participantCount' => (int)($row['participant_count'] ?? 0),
            'isArchived' => (int)($row['is_archived'] ?? 0) === 1,
        ];
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                t.*,
                p.name AS project_name,
                mp.is_archived,
                lm.body AS last_message_body,
                CONCAT(COALESCE(lu.first_name, ''), ' ', COALESCE(lu.last_name, '')) AS last_sender_name,
                (SELECT COUNT(*) FROM message_participants mp2 WHERE mp2.thread_id = t.id) AS participant_count,
                (
                    SELECT COUNT(*)
                    FROM messages um
                    WHERE um.thread_id = t.id
                      AND um.sender_id <> ?
                      AND um.is_deleted = 0
                      AND NOT EXISTS (
                          SELECT 1 FROM message_reads mr WHERE mr.message_id = um.id AND mr.user_id = mp.user_id
                      )
                ) AS unread_count
            FROM message_threads t
            INNER JOIN message_participants mp ON mp.thread_id = t.id
            LEFT JOIN projects p ON p.id = t.project_id
            LEFT JOIN messages lm ON lm.id = t.last_message_id
            LEFT JOIN users lu ON lu.id = lm.sender_id
        ";
    }

    private static function activeUserIds(int $excludeUserId = 0): array
    {
        $rows = Database::fetchAll('SELECT id FROM users WHERE status = ? AND id <> ?', ['active', $excludeUserId]);
        return array_map('intval', array_column($rows, 'id'));
    }

    private static function activeUserIdsByRole(string $role, int $excludeUserId = 0): array
    {
        $rows = Database::fetchAll("
            SELECT u.id
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.status = 'active' AND u.id <> ? AND r.slug = ?
        ", [$excludeUserId, $role]);
        return array_map('intval', array_column($rows, 'id'));
    }

    private static function projectTeamUserIds(int $projectId, int $excludeUserId = 0): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $rows = Database::fetchAll("
            SELECT DISTINCT u.id
            FROM users u
            LEFT JOIN project_assignments pa ON pa.user_id = u.id AND pa.project_id = ?
            LEFT JOIN projects p ON p.id = ? AND (p.contractor_id = u.id OR p.consultant_id = u.id)
            WHERE u.status = 'active'
              AND u.id <> ?
              AND (pa.id IS NOT NULL OR p.id IS NOT NULL)
        ", [$projectId, $projectId, $excludeUserId]);

        return array_map('intval', array_column($rows, 'id'));
    }

    private static function filterSql(int $userId, array $filters): array
    {
        $where = ['mp.user_id = ?'];
        $bindings = [$userId, $userId];
        $box = trim((string)($filters['box'] ?? 'inbox'));

        if ($box === 'archived') {
            $where[] = 'mp.is_archived = 1';
        } else {
            $where[] = 'mp.is_archived = 0';
        }

        if ($box === 'sent') {
            $where[] = 'EXISTS (SELECT 1 FROM messages sm WHERE sm.thread_id = t.id AND sm.sender_id = ?)';
            $bindings[] = $userId;
        } elseif ($box === 'unread') {
            $where[] = "EXISTS (
                SELECT 1 FROM messages um
                WHERE um.thread_id = t.id
                  AND um.sender_id <> ?
                  AND um.is_deleted = 0
                  AND NOT EXISTS (SELECT 1 FROM message_reads mr WHERE mr.message_id = um.id AND mr.user_id = ?)
            )";
            array_push($bindings, $userId, $userId);
        }

        $type = trim((string)($filters['type'] ?? ''));
        if (in_array($type, ['direct', 'group', 'project-channel'], true)) {
            $where[] = 't.type = ?';
            $bindings[] = $type;
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(t.subject LIKE ? OR p.name LIKE ? OR EXISTS (SELECT 1 FROM messages qm WHERE qm.thread_id = t.id AND qm.body LIKE ?))";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term);
        }

        return [' WHERE ' . implode(' AND ', $where), $bindings];
    }
}
