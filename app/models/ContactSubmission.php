<?php

class ContactSubmission extends Model
{
    protected static string $table = 'contact_submissions';

    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    public static function items(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY cs.created_at DESC, cs.id DESC' . $limitSql, $bindings);
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
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN cs.status = 'new' THEN 1 ELSE 0 END), 0) AS new_messages,
                COALESCE(SUM(CASE WHEN cs.is_read = 0 AND cs.status <> 'archived' THEN 1 ELSE 0 END), 0) AS unread,
                COALESCE(SUM(CASE WHEN cs.is_read = 1 AND cs.status <> 'archived' THEN 1 ELSE 0 END), 0) AS read_messages,
                COALESCE(SUM(CASE WHEN cs.status = 'replied' THEN 1 ELSE 0 END), 0) AS replied,
                COALESCE(SUM(CASE WHEN cs.status = 'archived' THEN 1 ELSE 0 END), 0) AS archived,
                COALESCE(SUM(CASE WHEN cs.attachment_path IS NOT NULL AND cs.attachment_path <> '' THEN 1 ELSE 0 END), 0) AS with_attachments,
                COALESCE(SUM(CASE WHEN DATE(cs.created_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS today,
                COALESCE(SUM(CASE WHEN cs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS this_week
             FROM contact_submissions cs
             LEFT JOIN users assignee ON assignee.id = cs.assigned_to
             {$where}",
            $bindings
        ) ?: [];
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE cs.id = ? LIMIT 1', [$id]);
    }

    public static function markRead(int $id, int $userId): bool
    {
        return self::update($id, [
            'is_read' => 1,
            'status' => 'read',
            'read_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
    }

    public static function markUnread(int $id, int $userId): bool
    {
        return self::update($id, [
            'is_read' => 0,
            'status' => 'new',
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
            $data['response_note'] = trim((string)$responseNote) !== '' ? trim((string)$responseNote) : null;
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
        return self::update($id, [
            'assigned_to' => $assignedTo,
            'updated_by' => $userId,
        ]);
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

    public static function payload(array $row): array
    {
        return array_merge($row, [
            'id' => (int)$row['id'],
            'is_read' => (int)($row['is_read'] ?? 0),
            'assigned_to' => !empty($row['assigned_to']) ? (int)$row['assigned_to'] : null,
            'has_attachment' => trim((string)($row['attachment_path'] ?? '')) !== '',
            'attachment_url' => self::attachmentUrl($row['attachment_path'] ?? null),
        ]);
    }

    public static function attachmentUrl(?string $path): string
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^secure-uploads/contact-submissions/[a-zA-Z0-9._-]+$#', $path) !== 1) {
            return '';
        }

        return Url::to($path);
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

    private static function filterSql(array $filters, bool $includeReadState = true): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'cs.status = ?';
            $bindings[] = (string)$filters['status'];
        }

        if ($includeReadState && isset($filters['read_state']) && $filters['read_state'] !== '') {
            if ($filters['read_state'] === 'unread') {
                $where[] = 'cs.is_read = 0';
            } elseif ($filters['read_state'] === 'read') {
                $where[] = 'cs.is_read = 1';
            }
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'cs.assigned_to = ?';
            $bindings[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['has_attachment'])) {
            $where[] = "cs.attachment_path IS NOT NULL AND cs.attachment_path <> ''";
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
            $where[] = '(cs.name LIKE ? OR cs.email LIKE ? OR cs.phone LIKE ? OR cs.subject LIKE ? OR cs.message LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
