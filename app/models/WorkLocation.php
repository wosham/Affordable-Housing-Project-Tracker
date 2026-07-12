<?php

class WorkLocation extends Model
{
    protected static string $table = 'work_locations';

    public const STATUSES = ['configured', 'missing', 'needs-review', 'inactive'];
    public const TYPES = ['office', 'field-office', 'store', 'yard', 'other'];

    public static function active(): array
    {
        return Database::fetchAll(
            "SELECT * FROM work_locations WHERE status <> 'inactive' ORDER BY is_public ASC, name ASC"
        );
    }

    public static function visibleForAdmin(): array
    {
        return Database::fetchAll(
            "SELECT wl.*,
                    COUNT(DISTINCT CASE WHEN wla.status = 'active' THEN wla.user_id END) AS assigned_people
             FROM work_locations wl
             LEFT JOIN work_location_assignments wla ON wla.work_location_id = wl.id
             GROUP BY wl.id, wl.name, wl.slug, wl.location_type, wl.address, wl.latitude, wl.longitude,
                      wl.radius_meters, wl.status, wl.is_public, wl.notes, wl.created_by, wl.updated_by,
                      wl.created_at, wl.updated_at
             ORDER BY wl.status = 'inactive' ASC, wl.is_public ASC, wl.name ASC"
        );
    }

    public static function headquarters(): ?array
    {
        return Database::fetch("SELECT * FROM work_locations WHERE slug = 'headquarters-office' LIMIT 1");
    }

    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            "SELECT wla.*, wl.name AS work_location_name, wl.slug AS work_location_slug, wl.location_type,
                    wl.address, wl.latitude, wl.longitude, wl.radius_meters, wl.status AS work_location_status
             FROM work_location_assignments wla
             INNER JOIN work_locations wl ON wl.id = wla.work_location_id
             WHERE wla.user_id = ? AND wla.status = 'active' AND wl.status <> 'inactive'
             ORDER BY wla.is_primary DESC, wl.name ASC",
            [$userId]
        );
    }

    public static function findForUser(int $userId, int $locationId): ?array
    {
        return Database::fetch(
            "SELECT wla.*, wl.name AS work_location_name, wl.slug AS work_location_slug, wl.location_type,
                    wl.address, wl.latitude, wl.longitude, wl.radius_meters, wl.status AS work_location_status,
                    wl.notes AS work_location_notes
             FROM work_location_assignments wla
             INNER JOIN work_locations wl ON wl.id = wla.work_location_id
             WHERE wla.user_id = ? AND wla.work_location_id = ? AND wla.status = 'active' AND wl.status <> 'inactive'
             LIMIT 1",
            [$userId, $locationId]
        );
    }

    public static function canAccess(int $userId, int $locationId): bool
    {
        return self::findForUser($userId, $locationId) !== null;
    }

    public static function syncUserAssignments(int $userId, array $locationIds, string $role, int $actorId): void
    {
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds), static fn (int $id): bool => $id > 0)));
        $existing = Database::fetchAll('SELECT id, work_location_id, status FROM work_location_assignments WHERE user_id = ?', [$userId]);
        $existingByLocation = [];
        foreach ($existing as $assignment) {
            $existingByLocation[(int)$assignment['work_location_id']] = $assignment;
        }

        foreach ($locationIds as $locationId) {
            self::assign($locationId, $userId, $role, $actorId, ['assignment_type' => 'office', 'scope' => 'attendance', 'status' => 'active']);
        }

        foreach ($existingByLocation as $locationId => $assignment) {
            if (!in_array((int)$locationId, $locationIds, true) && (string)($assignment['status'] ?? '') === 'active') {
                self::revokeAssignment((int)$assignment['id'], $actorId);
            }
        }
    }

    public static function syncLocationUsers(int $locationId, array $userIds, int $actorId): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        $existing = Database::fetchAll('SELECT id, user_id, status FROM work_location_assignments WHERE work_location_id = ?', [$locationId]);
        $existingByUser = [];
        foreach ($existing as $assignment) {
            $existingByUser[(int)$assignment['user_id']] = $assignment;
        }

        foreach ($userIds as $userId) {
            $user = User::findDetailed($userId);
            $role = (string)($user['role_slug'] ?? 'intern');
            self::assign($locationId, $userId, $role, $actorId, ['assignment_type' => 'office', 'scope' => 'attendance', 'status' => 'active']);
        }

        foreach ($existingByUser as $userId => $assignment) {
            if (!in_array((int)$userId, $userIds, true) && (string)($assignment['status'] ?? '') === 'active') {
                self::revokeAssignment((int)$assignment['id'], $actorId);
            }
        }
    }

    public static function assign(int $locationId, int $userId, string $role, int $actorId, array $options = []): int
    {
        $role = strtolower(trim($role));
        $existing = Database::fetch(
            'SELECT id FROM work_location_assignments WHERE work_location_id = ? AND user_id = ? LIMIT 1',
            [$locationId, $userId]
        );

        $payload = [
            'role' => $role,
            'assignment_type' => (string)($options['assignment_type'] ?? 'office'),
            'scope' => (string)($options['scope'] ?? 'attendance'),
            'status' => (string)($options['status'] ?? 'active'),
            'start_date' => self::dateOrNull($options['start_date'] ?? null),
            'end_date' => self::dateOrNull($options['end_date'] ?? null),
            'is_primary' => !empty($options['is_primary']) ? 1 : 0,
            'notes' => trim((string)($options['notes'] ?? '')) ?: null,
            'updated_by' => $actorId ?: null,
            'revoked_by' => null,
            'revoked_at' => null,
        ];

        if ($existing) {
            self::updateAssignment((int)$existing['id'], $payload);
            return (int)$existing['id'];
        }

        Database::query(
            'INSERT INTO work_location_assignments
                (work_location_id, user_id, role, assignment_type, scope, status, start_date, end_date, is_primary, notes, assigned_by, updated_by, revoked_by, revoked_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $locationId,
                $userId,
                $payload['role'],
                $payload['assignment_type'],
                $payload['scope'],
                $payload['status'],
                $payload['start_date'],
                $payload['end_date'],
                $payload['is_primary'],
                $payload['notes'],
                $actorId ?: null,
                $payload['updated_by'],
                $payload['revoked_by'],
                $payload['revoked_at'],
            ]
        );

        return (int)Database::lastInsertId();
    }

    public static function revokeAssignment(int $assignmentId, int $actorId): bool
    {
        return self::updateAssignment($assignmentId, [
            'status' => 'revoked',
            'is_primary' => 0,
            'updated_by' => $actorId ?: null,
            'revoked_by' => $actorId ?: null,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function updateAssignment(int $assignmentId, array $payload): bool
    {
        $sets = [];
        $bindings = [];
        foreach ($payload as $column => $value) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string)$column)) {
                throw new InvalidArgumentException('Invalid assignment column.');
            }
            $sets[] = '`' . $column . '` = ?';
            $bindings[] = $value;
        }

        if ($sets === []) {
            return false;
        }

        $bindings[] = $assignmentId;
        $statement = Database::query('UPDATE work_location_assignments SET ' . implode(', ', $sets) . ' WHERE id = ?', $bindings);
        return $statement->rowCount() > 0;
    }

    private static function dateOrNull(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
