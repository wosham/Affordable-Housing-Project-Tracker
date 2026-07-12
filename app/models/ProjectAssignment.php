<?php

class ProjectAssignment extends Model
{
    protected static string $table = 'project_assignments';

    public const ASSIGNABLE_ROLES = ['manager', 'consultant', 'contractor', 'clerk', 'intern'];
    /** Clerks and interns may hold only one active project/site assignment at a time. */
    public const SINGLE_SITE_ROLES = ['clerk', 'intern'];
    public const STATUSES = ['active', 'inactive', 'revoked'];
    public const TYPES = ['site', 'project-oversight', 'attendance-supervision', 'ipc-verification', 'reporting'];
    public const SCOPES = ['general', 'attendance', 'boq', 'programme', 'quality', 'safety', 'reporting', 'finance-visibility'];

    public static function isSingleSiteRole(string $role): bool
    {
        return in_array(strtolower(trim($role)), self::SINGLE_SITE_ROLES, true);
    }

    public static function usersForProject(int $projectId): array
    {
        return Database::fetchAll(
            'SELECT pa.*, u.first_name, u.last_name, u.email, u.phone, u.avatar, u.status AS user_status,
                    r.slug AS role_slug, r.name AS role_name,
                    CONCAT(u.first_name, " ", u.last_name) AS user_name
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE pa.project_id = ?
             ORDER BY r.slug ASC, u.first_name ASC, u.last_name ASC',
            [$projectId]
        );
    }

    public static function projectsForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT pa.*, p.name AS project_name, p.slug AS project_slug, p.status AS project_status
             FROM project_assignments pa
             JOIN projects p ON p.id = pa.project_id
             WHERE pa.user_id = ?
             ORDER BY p.name ASC',
            [$userId]
        );
    }

    public static function assignableRoles(): array
    {
        return self::ASSIGNABLE_ROLES;
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function typeOptions(): array
    {
        return self::TYPES;
    }

    public static function scopeOptions(): array
    {
        return self::SCOPES;
    }

    public static function managerProjects(int $userId, string $role): array
    {
        $role = strtolower($role);
        if ($role === 'superadmin') {
            return Database::fetchAll(
                "SELECT p.id, p.name, p.slug, p.status, p.constituency_id, c.name AS constituency_name, w.name AS ward_name
                 FROM projects p
                 LEFT JOIN constituencies c ON c.id = p.constituency_id
                 LEFT JOIN wards w ON w.id = p.ward_id
                 ORDER BY p.name ASC"
            );
        }

        return Database::fetchAll(
            "SELECT DISTINCT p.id, p.name, p.slug, p.status, p.constituency_id, c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             INNER JOIN project_assignments pa ON pa.project_id = p.id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             WHERE pa.user_id = ? AND pa.status = 'active'
             ORDER BY p.name ASC",
            [$userId]
        );
    }

    public static function canManageProject(int $managerId, int $projectId, string $role): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        if (strtolower($role) === 'superadmin') {
            return Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId]) !== null;
        }

        return ProjectAccess::canManageProject($managerId, $role, $projectId);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE pa.id = ? LIMIT 1', [$id]);
    }

    public static function listForManager(int $managerId, string $role, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($managerId, $role, $filters);
        $limitSql = ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY pa.assigned_at DESC, pa.id DESC' . $limitSql, $bindings);
    }

    public static function countForManager(int $managerId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($managerId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM project_assignments pa
             INNER JOIN projects p ON p.id = pa.project_id
             INNER JOIN users u ON u.id = pa.user_id
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             {$where}",
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }

    public static function summaryForManager(int $managerId, string $role): array
    {
        [$scopeSql, $scopeBindings] = self::managerScopeSql($managerId, $role, 'p');
        $scopeWhere = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;
        $assignmentWhere = $scopeSql === '' ? '' : ' AND ' . $scopeSql;

        $summary = Database::fetch(
            "SELECT
                COUNT(DISTINCT p.id) AS projects,
                COUNT(pa.id) AS assignments,
                SUM(CASE WHEN pa.status = 'active' THEN 1 ELSE 0 END) AS active_assignments,
                SUM(CASE WHEN r.slug = 'clerk' AND pa.status = 'active' THEN 1 ELSE 0 END) AS clerks,
                SUM(CASE WHEN r.slug = 'intern' AND pa.status = 'active' THEN 1 ELSE 0 END) AS interns,
                SUM(CASE WHEN r.slug = 'consultant' AND pa.status = 'active' THEN 1 ELSE 0 END) AS consultants,
                SUM(CASE WHEN r.slug = 'contractor' AND pa.status = 'active' THEN 1 ELSE 0 END) AS contractors
             FROM projects p
             LEFT JOIN project_assignments pa ON pa.project_id = p.id
             LEFT JOIN users u ON u.id = pa.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             {$scopeWhere}",
            $scopeBindings
        ) ?: [];

        $missingClerks = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM projects p
             WHERE NOT EXISTS (
                SELECT 1 FROM project_assignments pa
                INNER JOIN users u ON u.id = pa.user_id
                INNER JOIN roles r ON r.id = u.role_id
                WHERE pa.project_id = p.id AND pa.status = 'active' AND r.slug = 'clerk'
             ){$assignmentWhere}",
            $scopeBindings
        );
        $missingInterns = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM projects p
             WHERE NOT EXISTS (
                SELECT 1 FROM project_assignments pa
                INNER JOIN users u ON u.id = pa.user_id
                INNER JOIN roles r ON r.id = u.role_id
                WHERE pa.project_id = p.id AND pa.status = 'active' AND r.slug = 'intern'
             ){$assignmentWhere}",
            $scopeBindings
        );

        return array_merge([
            'projects' => 0,
            'assignments' => 0,
            'active_assignments' => 0,
            'clerks' => 0,
            'interns' => 0,
            'consultants' => 0,
            'contractors' => 0,
            'missing_clerks' => (int)($missingClerks['total'] ?? 0),
            'missing_interns' => (int)($missingInterns['total'] ?? 0),
        ], array_map('intval', $summary));
    }

    public static function availableUsers(string $roleSlug = '', int $projectId = 0, string $q = '', int $limit = 80): array
    {
        $where = ["u.status = 'active'", "r.slug IN ('manager','consultant','contractor','clerk','intern')"];
        $bindings = [];

        if ($roleSlug !== '' && in_array($roleSlug, self::ASSIGNABLE_ROLES, true)) {
            $where[] = 'r.slug = ?';
            $bindings[] = $roleSlug;
        }

        if ($projectId > 0) {
            $where[] = 'NOT EXISTS (SELECT 1 FROM project_assignments pa WHERE pa.user_id = u.id AND pa.project_id = ? AND pa.status <> "revoked")';
            $bindings[] = $projectId;
        }

        $q = trim($q);
        if ($q !== '') {
            $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.job_title LIKE ? OR r.name LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        return Database::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.email, u.phone, u.job_title, r.slug AS role_slug, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE " . implode(' AND ', $where) . '
             ORDER BY r.slug ASC, u.first_name ASC, u.last_name ASC
             LIMIT ' . max(1, min(150, $limit)),
            $bindings
        );
    }

    public static function assign(array $payload): int
    {
        $projectId = (int)($payload['project_id'] ?? 0);
        $userId = (int)($payload['user_id'] ?? 0);
        $user = User::findDetailed($userId);
        if (!$user || (string)($user['status'] ?? '') !== 'active') {
            throw new InvalidArgumentException('Assigned user must be an active staff account.');
        }

        $role = (string)($user['role_slug'] ?? '');
        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            throw new InvalidArgumentException('This user role cannot be assigned from the manager centre.');
        }

        $actorId = (int)($payload['assigned_by'] ?? 0);
        $status = self::cleanOption((string)($payload['status'] ?? 'active'), self::STATUSES, 'active');

        // Clerks/interns: only one active project site. Transfer by revoking other active sites first.
        if ($status === 'active' && self::isSingleSiteRole($role)) {
            self::revokeOtherActiveSites($userId, $projectId, $actorId);
        }

        $existing = Database::fetch('SELECT id, status FROM project_assignments WHERE project_id = ? AND user_id = ? LIMIT 1', [$projectId, $userId]);
        if ($existing && (string)($existing['status'] ?? '') === 'active') {
            throw new InvalidArgumentException('This user is already assigned to the selected project.');
        }
        if ($existing) {
            self::update((int)$existing['id'], [
                'role' => $role,
                'assignment_type' => self::cleanOption((string)($payload['assignment_type'] ?? 'site'), self::TYPES, 'site'),
                'scope' => self::cleanOption((string)($payload['scope'] ?? 'general'), self::SCOPES, 'general'),
                'status' => $status,
                'start_date' => self::dateOrNull($payload['start_date'] ?? null),
                'end_date' => self::dateOrNull($payload['end_date'] ?? null),
                'is_primary' => !empty($payload['is_primary']) ? 1 : 0,
                'notes' => trim((string)($payload['notes'] ?? '')) ?: null,
                'assigned_by' => $actorId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'revoked_by' => null,
                'revoked_at' => null,
                'updated_by' => $actorId ?: null,
            ]);
            return (int)$existing['id'];
        }

        $isPrimary = !empty($payload['is_primary']) ? 1 : 0;
        if ($isPrimary === 1) {
            self::clearPrimary($projectId, $role);
        }

        return (int)self::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role,
            'assignment_type' => self::cleanOption((string)($payload['assignment_type'] ?? 'site'), self::TYPES, 'site'),
            'scope' => self::cleanOption((string)($payload['scope'] ?? 'general'), self::SCOPES, 'general'),
            'status' => $status,
            'start_date' => self::dateOrNull($payload['start_date'] ?? null),
            'end_date' => self::dateOrNull($payload['end_date'] ?? null),
            'is_primary' => $isPrimary,
            'notes' => trim((string)($payload['notes'] ?? '')) ?: null,
            'assigned_by' => $actorId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorId ?: null,
        ]);
    }

    public static function updateAssignment(int $id, array $payload): bool
    {
        $assignment = self::findDetailed($id);
        if (!$assignment) {
            throw new InvalidArgumentException('Assignment could not be found.');
        }

        $isPrimary = !empty($payload['is_primary']) ? 1 : 0;
        $roleSlug = (string)($assignment['role_slug'] ?? $assignment['role'] ?? '');
        if ($isPrimary === 1) {
            self::clearPrimary((int)$assignment['project_id'], $roleSlug, $id);
        }

        $status = self::cleanOption((string)($payload['status'] ?? $assignment['assignment_status']), self::STATUSES, 'active');
        $actorId = (int)($payload['updated_by'] ?? 0);

        if ($status === 'active' && self::isSingleSiteRole($roleSlug)) {
            self::revokeOtherActiveSites((int)$assignment['user_id'], (int)$assignment['project_id'], $actorId);
        }

        $data = [
            'assignment_type' => self::cleanOption((string)($payload['assignment_type'] ?? $assignment['assignment_type']), self::TYPES, 'site'),
            'scope' => self::cleanOption((string)($payload['scope'] ?? $assignment['scope']), self::SCOPES, 'general'),
            'status' => $status,
            'start_date' => self::dateOrNull($payload['start_date'] ?? null),
            'end_date' => self::dateOrNull($payload['end_date'] ?? null),
            'is_primary' => $isPrimary,
            'notes' => trim((string)($payload['notes'] ?? '')) ?: null,
            'updated_by' => $actorId ?: null,
        ];

        if ($status === 'revoked') {
            $data['revoked_by'] = $actorId ?: null;
            $data['revoked_at'] = $assignment['revoked_at'] ?: date('Y-m-d H:i:s');
            $data['is_primary'] = 0;
        } elseif ($status === 'active') {
            $data['revoked_by'] = null;
            $data['revoked_at'] = null;
        }

        return self::update($id, $data);
    }

    public static function revoke(int $id, int $userId): bool
    {
        return self::update($id, [
            'status' => 'revoked',
            'is_primary' => 0,
            'revoked_by' => $userId,
            'revoked_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
    }

    public static function reactivate(int $id, int $userId): bool
    {
        $assignment = self::findDetailed($id);
        if (!$assignment) {
            throw new InvalidArgumentException('Assignment could not be found.');
        }

        $role = (string)($assignment['role_slug'] ?? $assignment['role'] ?? '');
        if (self::isSingleSiteRole($role)) {
            self::revokeOtherActiveSites((int)$assignment['user_id'], (int)$assignment['project_id'], $userId);
        }

        return self::update($id, [
            'status' => 'active',
            'revoked_by' => null,
            'revoked_at' => null,
            'updated_by' => $userId,
        ]);
    }

    public static function payload(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'project_name' => (string)($row['project_name'] ?? ''),
            'project_status' => (string)($row['project_status'] ?? ''),
            'constituency_name' => (string)($row['constituency_name'] ?? ''),
            'ward_name' => (string)($row['ward_name'] ?? ''),
            'user_id' => (int)($row['user_id'] ?? 0),
            'user_name' => trim((string)($row['user_name'] ?? '')) ?: 'Staff User',
            'email' => (string)($row['email'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'job_title' => (string)($row['job_title'] ?? ''),
            'role_slug' => (string)($row['role_slug'] ?? $row['role'] ?? ''),
            'role_name' => (string)($row['role_name'] ?? role_label((string)($row['role'] ?? ''))),
            'assignment_type' => (string)($row['assignment_type'] ?? 'site'),
            'scope' => (string)($row['scope'] ?? 'general'),
            'status' => (string)($row['assignment_status'] ?? $row['status'] ?? 'active'),
            'start_date' => (string)($row['start_date'] ?? ''),
            'end_date' => (string)($row['end_date'] ?? ''),
            'is_primary' => (int)($row['is_primary'] ?? 0) === 1,
            'notes' => (string)($row['notes'] ?? ''),
            'assigned_by_name' => trim((string)($row['assigned_by_name'] ?? '')) ?: 'System',
            'assigned_at' => (string)($row['assigned_at'] ?? ''),
            'revoked_at' => (string)($row['revoked_at'] ?? ''),
        ];
    }

    public static function syncUserAssignments(int $userId, array $projectIds, int $actorId): void
    {
        $user = User::findDetailed($userId);
        if (!$user) {
            throw new InvalidArgumentException('User account could not be found.');
        }

        $role = (string)($user['role_slug'] ?? '');
        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            $existing = Database::fetchAll('SELECT id FROM project_assignments WHERE user_id = ? AND status = "active"', [$userId]);
            foreach ($existing as $row) {
                self::revoke((int)$row['id'], $actorId);
            }
            return;
        }

        $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), fn ($id) => $id > 0)));

        // Clerks and interns: at most one active project site.
        if (self::isSingleSiteRole($role) && count($projectIds) > 1) {
            $projectIds = [ (int)$projectIds[0] ];
        }

        $existing = Database::fetchAll('SELECT id, project_id, status FROM project_assignments WHERE user_id = ?', [$userId]);
        $existingByProject = [];
        foreach ($existing as $row) {
            $existingByProject[(int)$row['project_id']] = $row;
        }

        foreach ($projectIds as $projectId) {
            if (!Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId])) {
                continue;
            }

            if (isset($existingByProject[$projectId])) {
                $row = $existingByProject[$projectId];
                if ((string)($row['status'] ?? '') !== 'active') {
                    self::update((int)$row['id'], [
                        'role' => $role,
                        'status' => 'active',
                        'revoked_by' => null,
                        'revoked_at' => null,
                        'updated_by' => $actorId,
                    ]);
                } else {
                    self::update((int)$row['id'], ['role' => $role, 'updated_by' => $actorId]);
                }
                continue;
            }

            self::assign([
                'project_id' => $projectId,
                'user_id' => $userId,
                'assignment_type' => self::defaultTypeForRole($role),
                'scope' => self::defaultScopeForRole($role),
                'status' => 'active',
                'assigned_by' => $actorId,
                'notes' => 'Assigned by system administrator.',
            ]);
        }

        foreach ($existingByProject as $projectId => $row) {
            if (!in_array($projectId, $projectIds, true) && (string)($row['status'] ?? '') === 'active') {
                self::revoke((int)$row['id'], $actorId);
            }
        }
    }

    public static function defaultTypeForRole(string $role): string
    {
        return match ($role) {
            'manager' => 'project-oversight',
            'clerk' => 'attendance-supervision',
            'intern' => 'reporting',
            'consultant' => 'ipc-verification',
            default => 'site',
        };
    }

    public static function defaultScopeForRole(string $role): string
    {
        return match ($role) {
            'manager' => 'general',
            'consultant' => 'quality',
            'contractor' => 'programme',
            'clerk' => 'attendance',
            'intern' => 'reporting',
            default => 'general',
        };
    }

    /**
     * Revoke every other active project assignment for a clerk/intern (one-site policy).
     */
    public static function revokeOtherActiveSites(int $userId, int $keepProjectId, int $actorId = 0): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $rows = Database::fetchAll(
            'SELECT id FROM project_assignments
             WHERE user_id = ? AND status = "active" AND project_id <> ?
             ORDER BY assigned_at DESC, id DESC',
            [$userId, $keepProjectId]
        );

        $count = 0;
        foreach ($rows as $row) {
            if (self::revoke((int)$row['id'], $actorId)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Repair multi-site clerk/intern rows: keep the newest active assignment, revoke the rest.
     *
     * @return array{users:int,revoked:int}
     */
    public static function repairSingleSiteAssignments(int $actorId = 0): array
    {
        $users = Database::fetchAll(
            "SELECT u.id AS user_id, r.slug AS role_slug
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug IN ('clerk', 'intern')
               AND (
                 SELECT COUNT(*) FROM project_assignments pa
                 WHERE pa.user_id = u.id AND pa.status = 'active'
               ) > 1"
        );

        $revoked = 0;
        foreach ($users as $user) {
            $keep = Database::fetch(
                'SELECT project_id FROM project_assignments
                 WHERE user_id = ? AND status = "active"
                 ORDER BY assigned_at DESC, id DESC
                 LIMIT 1',
                [(int)$user['user_id']]
            );
            if (!$keep) {
                continue;
            }
            $revoked += self::revokeOtherActiveSites((int)$user['user_id'], (int)$keep['project_id'], $actorId);
        }

        return ['users' => count($users), 'revoked' => $revoked];
    }

    private static function selectSql(): string
    {
        return "
            SELECT pa.*,
                   pa.status AS assignment_status,
                   p.name AS project_name, p.slug AS project_slug, p.status AS project_status,
                   c.name AS constituency_name, w.name AS ward_name,
                   u.email, u.phone, u.job_title, u.status AS user_status,
                   CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                   r.slug AS role_slug, r.name AS role_name,
                   CONCAT(ab.first_name, ' ', ab.last_name) AS assigned_by_name
            FROM project_assignments pa
            INNER JOIN projects p ON p.id = pa.project_id
            INNER JOIN users u ON u.id = pa.user_id
            INNER JOIN roles r ON r.id = u.role_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN users ab ON ab.id = pa.assigned_by
        ";
    }

    private static function filterSql(int $managerId, string $role, array $filters): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::managerScopeSql($managerId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'pa.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['role'])) {
            $where[] = 'r.slug = ?';
            $bindings[] = (string)$filters['role'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'pa.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.name LIKE ? OR pa.scope LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function managerScopeSql(int $managerId, string $role, string $projectAlias): array
    {
        if (strtolower($role) === 'superadmin') {
            return ['', []];
        }

        return [
            "EXISTS (SELECT 1 FROM project_assignments manager_pa WHERE manager_pa.project_id = {$projectAlias}.id AND manager_pa.user_id = ? AND manager_pa.status = 'active')",
            [$managerId],
        ];
    }

    private static function clearPrimary(int $projectId, string $roleSlug, ?int $exceptId = null): void
    {
        $bindings = [$projectId, $roleSlug];
        $exceptSql = '';
        if ($exceptId !== null) {
            $exceptSql = ' AND id <> ?';
            $bindings[] = $exceptId;
        }
        Database::query("UPDATE project_assignments SET is_primary = 0 WHERE project_id = ? AND role = ?{$exceptSql}", $bindings);
    }

    private static function cleanOption(string $value, array $options, string $fallback): string
    {
        return in_array($value, $options, true) ? $value : $fallback;
    }

    private static function dateOrNull(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
