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

    /**
     * Roles this viewer may contact (before project-graph filtering).
     *
     * @return list<string>
     */
    public static function allowedRoleSlugs(string $viewerRole): array
    {
        $viewerRole = strtolower(trim($viewerRole));

        return match ($viewerRole) {
            'superadmin' => ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'],
            'manager' => ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'],
            'consultant' => ['superadmin', 'manager', 'contractor', 'clerk'],
            'contractor' => ['superadmin', 'manager', 'consultant', 'clerk', 'finance'],
            'clerk' => ['superadmin', 'manager', 'consultant', 'contractor', 'intern'],
            'finance' => ['superadmin', 'manager', 'contractor'],
            'intern' => ['superadmin', 'manager', 'clerk'],
            default => ['superadmin'],
        };
    }

    /**
     * Whether viewer may message a specific user (optionally in project context).
     */
    public static function canMessageUser(int $viewerId, string $viewerRole, int $targetUserId, ?int $projectId = null): bool
    {
        if ($viewerId <= 0 || $targetUserId <= 0 || $viewerId === $targetUserId) {
            return false;
        }

        $allowed = self::recipientOptions($viewerId, $viewerRole, $projectId);
        $ids = array_map('intval', array_column($allowed, 'id'));

        return in_array($targetUserId, $ids, true);
    }

    /**
     * Directory of people this viewer may message.
     * Superadmin: all active staff.
     * Others: allowed roles ∩ (shared project graph ∪ always-reachable superadmins).
     * Optional $projectId narrows to that project team.
     *
     * @return list<array<string, mixed>>
     */
    public static function recipientOptions(int $viewerId, string $viewerRole, ?int $projectId = null): array
    {
        $viewerRole = strtolower(trim($viewerRole));
        $projectId = $projectId && $projectId > 0 ? (int)$projectId : null;

        if ($viewerRole === 'superadmin') {
            if ($projectId !== null && !self::canUseProjectAudience($viewerId, $viewerRole, $projectId)) {
                return [];
            }
            if ($projectId !== null) {
                return self::usersByIds(self::projectTeamUserIds($projectId, $viewerId));
            }

            return self::usersByIds(self::activeUserIds($viewerId));
        }

        $allowedRoles = self::allowedRoleSlugs($viewerRole);
        $candidateIds = self::reachableUserIds($viewerId, $viewerRole, $projectId);
        if ($candidateIds === []) {
            // Always allow contacting county leadership (superadmin).
            $candidateIds = self::activeUserIdsByRole('superadmin', $viewerId);
        }

        $users = self::usersByIds($candidateIds);
        $users = array_values(array_filter(
            $users,
            static fn (array $row): bool => in_array((string)($row['role_slug'] ?? ''), $allowedRoles, true)
                || (string)($row['role_slug'] ?? '') === 'superadmin'
        ));

        return $users;
    }

    public static function expandAudienceTargets(int $viewerId, string $viewerRole, array $recipientIds = [], array $targets = [], ?int $projectId = null): array
    {
        $viewerRole = strtolower(trim($viewerRole));
        $projectId = $projectId && $projectId > 0 ? (int)$projectId : null;
        $allowedOptions = self::recipientOptions($viewerId, $viewerRole, $projectId);
        $allowedIds = array_map('intval', array_column($allowedOptions, 'id'));
        $expanded = array_map('intval', $recipientIds);

        foreach (array_values(array_unique(array_map('strval', $targets))) as $target) {
            $target = trim($target);
            if ($target === '') {
                continue;
            }

            if ($target === 'all:staff') {
                $expanded = array_merge($expanded, $allowedIds);
                continue;
            }

            if (str_starts_with($target, 'role:')) {
                $role = substr($target, 5);
                if (preg_match('/^[a-z0-9_-]+$/', $role)) {
                    if ($viewerRole === 'superadmin') {
                        if ($projectId !== null) {
                            $team = self::projectTeamUserIds($projectId, $viewerId);
                            $byRole = self::activeUserIdsByRole($role, $viewerId);
                            $expanded = array_merge($expanded, array_values(array_intersect($team, $byRole)));
                        } else {
                            $expanded = array_merge($expanded, self::activeUserIdsByRole($role, $viewerId));
                        }
                    } else {
                        // Non-director: only that role among people already reachable (project graph).
                        $byRole = [];
                        foreach ($allowedOptions as $row) {
                            if ((string)($row['role_slug'] ?? '') === $role) {
                                $byRole[] = (int)$row['id'];
                            }
                        }
                        $expanded = array_merge($expanded, $byRole);
                    }
                }
                continue;
            }

            if ($target === 'project:selected' && $projectId !== null) {
                if (self::canUseProjectAudience($viewerId, $viewerRole, $projectId)) {
                    $expanded = array_merge($expanded, self::projectTeamUserIds($projectId, $viewerId));
                }
                continue;
            }

            if (preg_match('/^project:(\d+)$/', $target, $match)) {
                $targetProjectId = (int)$match[1];
                if (self::canUseProjectAudience($viewerId, $viewerRole, $targetProjectId)) {
                    $expanded = array_merge($expanded, self::projectTeamUserIds($targetProjectId, $viewerId));
                }
            }
        }

        $expanded = array_values(array_unique(array_filter(
            $expanded,
            static fn (int $id): bool => $id > 0 && $id !== $viewerId
        )));

        // Final hard gate: every ID must be in the policy directory for this context.
        $expanded = array_values(array_intersect($expanded, $allowedIds));

        return $expanded;
    }

    public static function audienceOptions(int $viewerId, string $viewerRole, ?int $projectId = null): array
    {
        $viewerRole = strtolower(trim($viewerRole));
        $roles = self::allowedRoleSlugs($viewerRole);
        $options = [];
        $allowed = self::recipientOptions($viewerId, $viewerRole, $projectId);
        $allowedByRole = [];
        foreach ($allowed as $row) {
            $slug = (string)($row['role_slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $allowedByRole[$slug] = ($allowedByRole[$slug] ?? 0) + 1;
        }

        $allCount = count($allowed);
        if ($allCount > 0) {
            $options[] = [
                'value' => 'all:staff',
                'label' => $viewerRole === 'superadmin'
                    ? ($projectId ? 'All staff on selected project' : 'All active staff')
                    : ($projectId ? 'Everyone on selected project (allowed roles)' : 'Everyone on my projects (allowed roles)'),
                'count' => $allCount,
            ];
        }

        foreach ($roles as $role) {
            if ($role === 'superadmin') {
                continue;
            }
            $count = (int)($allowedByRole[$role] ?? 0);
            if ($count > 0) {
                $options[] = [
                    'value' => 'role:' . $role,
                    'label' => ($projectId ? 'On project: ' : 'On my projects: ') . 'All ' . role_label($role),
                    'count' => $count,
                ];
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

    /**
     * Users this viewer can reach via shared project assignments / project links.
     *
     * @return list<int>
     */
    private static function reachableUserIds(int $viewerId, string $viewerRole, ?int $projectId = null): array
    {
        $viewerRole = strtolower(trim($viewerRole));
        $ids = self::activeUserIdsByRole('superadmin', $viewerId);

        if ($projectId !== null) {
            if (!self::canUseProjectAudience($viewerId, $viewerRole, $projectId)) {
                return array_values(array_unique($ids));
            }

            return array_values(array_unique(array_merge($ids, self::projectTeamUserIds($projectId, $viewerId))));
        }

        // Finance may have few/no project assignments — still reach managers + contractors on any active project.
        if ($viewerRole === 'finance') {
            $rows = Database::fetchAll("
                SELECT DISTINCT u.id
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.status = 'active'
                  AND u.id <> ?
                  AND (
                    r.slug IN ('superadmin', 'manager')
                    OR r.slug = 'contractor'
                    OR EXISTS (
                        SELECT 1 FROM projects p
                        WHERE p.contractor_id = u.id OR p.consultant_id = u.id
                    )
                  )
            ", [$viewerId]);

            return array_values(array_unique(array_merge($ids, array_map('intval', array_column($rows, 'id')))));
        }

        // People on any project where I have an active assignment, plus project contractor/consultant.
        $rows = Database::fetchAll("
            SELECT DISTINCT u.id
            FROM users u
            WHERE u.status = 'active'
              AND u.id <> ?
              AND (
                EXISTS (
                    SELECT 1
                    FROM project_assignments mine
                    INNER JOIN project_assignments theirs
                        ON theirs.project_id = mine.project_id
                       AND theirs.status = 'active'
                       AND theirs.user_id = u.id
                    WHERE mine.user_id = ?
                      AND mine.status = 'active'
                )
                OR EXISTS (
                    SELECT 1
                    FROM project_assignments mine
                    INNER JOIN projects p ON p.id = mine.project_id
                    WHERE mine.user_id = ?
                      AND mine.status = 'active'
                      AND (p.contractor_id = u.id OR p.consultant_id = u.id)
                )
              )
        ", [$viewerId, $viewerId, $viewerId]);

        return array_values(array_unique(array_merge($ids, array_map('intval', array_column($rows, 'id')))));
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    private static function usersByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll("
            SELECT
                u.id,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
                u.email,
                r.slug AS role_slug,
                r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.status = 'active' AND u.id IN ({$placeholders})
            ORDER BY r.id ASC, u.first_name ASC, u.last_name ASC
        ", $ids);

        return $rows;
    }

    private static function projectTeamUserIds(int $projectId, int $excludeUserId = 0): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $rows = Database::fetchAll("
            SELECT DISTINCT u.id
            FROM users u
            LEFT JOIN project_assignments pa ON pa.user_id = u.id AND pa.project_id = ? AND pa.status = 'active'
            LEFT JOIN projects p ON p.id = ? AND (p.contractor_id = u.id OR p.consultant_id = u.id)
            WHERE u.status = 'active'
              AND u.id <> ?
              AND (pa.id IS NOT NULL OR p.id IS NOT NULL)
        ", [$projectId, $projectId, $excludeUserId]);

        return array_map('intval', array_column($rows, 'id'));
    }

    private static function canUseProjectAudience(int $viewerId, string $viewerRole, int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }
        if ($viewerRole === 'superadmin') {
            return true;
        }

        // Finance: any existing project.
        if ($viewerRole === 'finance') {
            return Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId]) !== null;
        }

        return ProjectAssignment::canManageProject($viewerId, $projectId, $viewerRole)
            || ProjectAccess::canViewProject($viewerId, $viewerRole, $projectId);
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
