<?php

class ContactSubmission extends Model
{
    protected static string $table = 'contact_submissions';

    public const STATUSES = ['new', 'read', 'in_progress', 'replied', 'archived'];
    public const SLA_HOURS = 48;

    public static function items(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $order = self::orderSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . $order . $limitSql, $bindings);
    }

    public static function countItems(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM contact_submissions cs
             LEFT JOIN users assignee ON assignee.id = cs.assigned_to
             {$where}",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function summary(array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($filters, false);
        $slaHours = self::SLA_HOURS;
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN cs.status = 'new' THEN 1 ELSE 0 END), 0) AS new_messages,
                COALESCE(SUM(CASE WHEN cs.is_read = 0 AND cs.status <> 'archived' THEN 1 ELSE 0 END), 0) AS unread,
                COALESCE(SUM(CASE WHEN cs.is_read = 1 AND cs.status <> 'archived' THEN 1 ELSE 0 END), 0) AS read_messages,
                COALESCE(SUM(CASE WHEN cs.status = 'in_progress' THEN 1 ELSE 0 END), 0) AS in_progress,
                COALESCE(SUM(CASE WHEN cs.status = 'replied' THEN 1 ELSE 0 END), 0) AS replied,
                COALESCE(SUM(CASE WHEN cs.status = 'archived' THEN 1 ELSE 0 END), 0) AS archived,
                COALESCE(SUM(CASE WHEN cs.status <> 'archived' THEN 1 ELSE 0 END), 0) AS open_cases,
                COALESCE(SUM(CASE WHEN cs.attachment_path IS NOT NULL AND cs.attachment_path <> '' THEN 1 ELSE 0 END), 0) AS with_attachments,
                COALESCE(SUM(CASE WHEN DATE(cs.created_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS today,
                COALESCE(SUM(CASE WHEN cs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS this_week,
                COALESCE(SUM(CASE
                    WHEN cs.status NOT IN ('replied','archived')
                     AND (
                        (cs.follow_up_at IS NOT NULL AND cs.follow_up_at < CURDATE())
                        OR (cs.follow_up_at IS NULL AND cs.created_at < DATE_SUB(NOW(), INTERVAL {$slaHours} HOUR))
                     )
                    THEN 1 ELSE 0 END), 0) AS overdue,
                COALESCE(SUM(CASE WHEN cs.follow_up_at IS NOT NULL AND cs.follow_up_at = CURDATE() AND cs.status <> 'archived' THEN 1 ELSE 0 END), 0) AS due_today
             FROM contact_submissions cs
             LEFT JOIN users assignee ON assignee.id = cs.assigned_to
             {$where}",
            $bindings
        ) ?: [];

        return $row;
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE cs.id = ? LIMIT 1', [$id]);
    }

    public static function markRead(int $id, int $userId): bool
    {
        $current = self::find($id);
        if (!$current) {
            return false;
        }
        $status = (string)($current['status'] ?? 'new');
        // Do not downgrade workflow status when simply opening the case.
        if (in_array($status, ['new', ''], true)) {
            $status = 'read';
        }

        return self::update($id, [
            'is_read' => 1,
            'status' => $status,
            'read_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
    }

    public static function markUnread(int $id, int $userId): bool
    {
        $current = self::find($id);
        if (!$current) {
            return false;
        }
        $status = (string)($current['status'] ?? 'new');
        if (in_array($status, ['read', 'new', ''], true)) {
            $status = 'new';
        }

        return self::update($id, [
            'is_read' => 0,
            'status' => $status,
            'read_at' => null,
            'updated_by' => $userId,
        ]);
    }

    public static function updateStatus(int $id, string $status, int $userId, ?string $responseNote = null): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $data = [
            'status' => $status,
            'is_read' => $status === 'new' ? 0 : 1,
            'updated_by' => $userId,
        ];

        if ($status !== 'new') {
            $data['read_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'replied') {
            $data['replied_at'] = date('Y-m-d H:i:s');
            if ($responseNote !== null && trim($responseNote) !== '') {
                $data['response_note'] = trim($responseNote);
            }
        }
        if ($status === 'archived') {
            $data['archived_at'] = date('Y-m-d H:i:s');
        }
        if ($status !== 'archived') {
            $data['archived_at'] = null;
        }

        return self::update($id, $data);
    }

    public static function assign(int $id, ?int $assignedTo, int $userId): bool
    {
        $data = [
            'assigned_to' => $assignedTo,
            'updated_by' => $userId,
        ];
        if ($assignedTo) {
            $data['assigned_at'] = date('Y-m-d H:i:s');
        } else {
            $data['assigned_at'] = null;
        }

        return self::update($id, $data);
    }

    public static function saveCaseFields(int $id, int $userId, array $fields): bool
    {
        $data = ['updated_by' => $userId];

        if (array_key_exists('internal_note', $fields)) {
            $note = trim((string)$fields['internal_note']);
            $data['internal_note'] = $note !== '' ? $note : null;
        }
        if (array_key_exists('follow_up_at', $fields)) {
            $date = trim((string)$fields['follow_up_at']);
            if ($date === '') {
                $data['follow_up_at'] = null;
            } else {
                $ts = strtotime($date);
                $data['follow_up_at'] = $ts ? date('Y-m-d', $ts) : null;
            }
        }
        if (array_key_exists('priority', $fields)) {
            $priority = strtolower(trim((string)$fields['priority']));
            $data['priority'] = $priority === 'urgent' ? 'urgent' : 'normal';
        }
        if (array_key_exists('status', $fields)) {
            $status = strtolower(trim((string)$fields['status']));
            if (in_array($status, self::STATUSES, true)) {
                $data['status'] = $status;
                $data['is_read'] = $status === 'new' ? 0 : 1;
                if ($status === 'archived') {
                    $data['archived_at'] = date('Y-m-d H:i:s');
                } elseif ($status !== 'archived') {
                    $data['archived_at'] = null;
                }
                if ($status === 'replied') {
                    $data['replied_at'] = date('Y-m-d H:i:s');
                }
            }
        }

        if (count($data) <= 1) {
            return false;
        }

        return self::update($id, $data);
    }

    public static function linkInternalThread(int $id, int $threadId, int $userId): bool
    {
        if ($id <= 0 || $threadId <= 0) {
            return false;
        }

        return self::update($id, [
            'internal_thread_id' => $threadId,
            'updated_by' => $userId,
        ]);
    }

    public static function canAccess(array $message, int $userId, string $role): bool
    {
        $role = strtolower(trim($role));
        if ($role === 'superadmin') {
            return true;
        }

        // Non-directors: only their own assigned cases. Unassigned never leaks.
        return $userId > 0 && (int)($message['assigned_to'] ?? 0) === $userId;
    }

    /**
     * Scope list/summary filters. Non-superadmin always forced to own assignment
     * (client cannot override assigned_to via query string).
     */
    public static function scopedFilters(array $filters, int $userId, string $role): array
    {
        $role = strtolower(trim($role));
        if ($role === 'superadmin') {
            return $filters;
        }

        // Hard override — never trust request for assignee scope.
        unset($filters['assigned_to']);
        $filters['assigned_to'] = max(0, $userId);
        // Safety: if no valid user, return impossible filter so nothing lists.
        if ($userId <= 0) {
            $filters['assigned_to'] = -1;
        }

        return $filters;
    }

    public static function recordResponse(int $id, string $note, int $userId): bool
    {
        return self::update($id, [
            'status' => 'replied',
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
            'replied_at' => date('Y-m-d H:i:s'),
            'response_note' => trim($note) !== '' ? trim($note) : null,
            'updated_by' => $userId,
        ]);
    }

    public static function assignableUsers(): array
    {
        return Database::fetchAll(
            "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, r.slug AS role
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active'
             ORDER BY r.slug, u.first_name, u.last_name"
        );
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function isOverdue(array $row): bool
    {
        $status = (string)($row['status'] ?? '');
        if (in_array($status, ['replied', 'archived'], true)) {
            return false;
        }
        $followUp = trim((string)($row['follow_up_at'] ?? ''));
        if ($followUp !== '') {
            return $followUp < date('Y-m-d');
        }
        $created = strtotime((string)($row['created_at'] ?? ''));
        if (!$created) {
            return false;
        }
        return $created < (time() - self::SLA_HOURS * 3600);
    }

    public static function payload(array $row): array
    {
        $overdue = self::isOverdue($row);
        return array_merge($row, [
            'id' => (int)$row['id'],
            'is_read' => (int)($row['is_read'] ?? 0),
            'assigned_to' => !empty($row['assigned_to']) ? (int)$row['assigned_to'] : null,
            'internal_thread_id' => !empty($row['internal_thread_id']) ? (int)$row['internal_thread_id'] : null,
            'has_attachment' => trim((string)($row['attachment_path'] ?? '')) !== '',
            'attachment_url' => self::attachmentUrl($row['attachment_path'] ?? null, (int)($row['id'] ?? 0)),
            'priority' => ((string)($row['priority'] ?? 'normal')) === 'urgent' ? 'urgent' : 'normal',
            'internal_note' => (string)($row['internal_note'] ?? ''),
            'follow_up_at' => (string)($row['follow_up_at'] ?? ''),
            'is_overdue' => $overdue ? 1 : 0,
            'is_due_today' => (trim((string)($row['follow_up_at'] ?? '')) === date('Y-m-d')) ? 1 : 0,
        ]);
    }

    public static function attachmentUrl(?string $path, int $messageId = 0): string
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^secure-uploads/contact-submissions/[a-zA-Z0-9._-]+$#', $path) !== 1) {
            return '';
        }

        if ($messageId <= 0) {
            return '';
        }

        return Url::to('api/contact/download-attachment.php?id=' . $messageId);
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                cs.*,
                COALESCE(CONCAT(assignee.first_name, ' ', assignee.last_name), '') AS assignee_name,
                ar.slug AS assignee_role,
                COALESCE(CONCAT(updater.first_name, ' ', updater.last_name), '') AS updated_by_name
            FROM contact_submissions cs
            LEFT JOIN users assignee ON assignee.id = cs.assigned_to
            LEFT JOIN roles ar ON ar.id = assignee.role_id
            LEFT JOIN users updater ON updater.id = cs.updated_by
        ";
    }

    private static function orderSql(array $filters): string
    {
        $sort = strtolower(trim((string)($filters['sort'] ?? 'newest')));
        return match ($sort) {
            'oldest' => ' ORDER BY cs.created_at ASC, cs.id ASC',
            'unread' => ' ORDER BY cs.is_read ASC, cs.created_at DESC, cs.id DESC',
            'overdue' => ' ORDER BY (CASE WHEN cs.status NOT IN (\'replied\',\'archived\') AND ((cs.follow_up_at IS NOT NULL AND cs.follow_up_at < CURDATE()) OR (cs.follow_up_at IS NULL AND cs.created_at < DATE_SUB(NOW(), INTERVAL ' . self::SLA_HOURS . ' HOUR))) THEN 0 ELSE 1 END) ASC, cs.created_at ASC, cs.id ASC',
            default => ' ORDER BY cs.created_at DESC, cs.id DESC',
        };
    }

    private static function filterSql(array $filters, bool $includeReadState = true): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'cs.status = ?';
            $bindings[] = (string)$filters['status'];
        } elseif (!empty($filters['exclude_archived'])) {
            $where[] = "cs.status <> 'archived'";
        }

        if (!empty($filters['box'])) {
            $box = (string)$filters['box'];
            if ($box === 'open') {
                $where[] = "cs.status <> 'archived'";
            } elseif ($box === 'overdue') {
                $where[] = "cs.status NOT IN ('replied','archived') AND (
                    (cs.follow_up_at IS NOT NULL AND cs.follow_up_at < CURDATE())
                    OR (cs.follow_up_at IS NULL AND cs.created_at < DATE_SUB(NOW(), INTERVAL " . self::SLA_HOURS . " HOUR))
                )";
            } elseif ($box === 'due_today') {
                $where[] = "cs.follow_up_at = CURDATE() AND cs.status <> 'archived'";
            }
        }

        if ($includeReadState && isset($filters['read_state']) && $filters['read_state'] !== '') {
            if ($filters['read_state'] === 'unread') {
                $where[] = 'cs.is_read = 0';
            } elseif ($filters['read_state'] === 'read') {
                $where[] = 'cs.is_read = 1';
            }
        }

        if (array_key_exists('assigned_to', $filters) && $filters['assigned_to'] !== '' && $filters['assigned_to'] !== null) {
            // Supports hard-lock value -1 (no rows) for invalid sessions.
            $where[] = 'cs.assigned_to = ?';
            $bindings[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['has_attachment'])) {
            $where[] = "cs.attachment_path IS NOT NULL AND cs.attachment_path <> ''";
        }

        if (!empty($filters['priority'])) {
            $where[] = 'cs.priority = ?';
            $bindings[] = (string)$filters['priority'] === 'urgent' ? 'urgent' : 'normal';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(cs.created_at) >= ?';
            $bindings[] = (string)$filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(cs.created_at) <= ?';
            $bindings[] = (string)$filters['date_to'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(cs.name LIKE ? OR cs.email LIKE ? OR cs.phone LIKE ? OR cs.subject LIKE ? OR cs.message LIKE ? OR cs.internal_note LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
