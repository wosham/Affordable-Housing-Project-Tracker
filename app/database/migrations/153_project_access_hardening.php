<?php

class Migration153ProjectAccessHardening
{
    public function up(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'project_assignments') || !$this->tableExists($pdo, 'users') || !$this->tableExists($pdo, 'projects')) {
            return;
        }

        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_user_role_status', 'user_id, role, status');
        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_project_status', 'project_id, status');

        $actorId = $this->superadminId($pdo) ?: $this->firstUserId($pdo);
        if ($actorId <= 0) {
            return;
        }

        $users = $pdo->query("
            SELECT u.id, r.slug AS role_slug
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.status = 'active'
              AND r.slug IN ('manager', 'consultant', 'contractor', 'clerk', 'intern')
            ORDER BY u.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $allProjects = $pdo->query("
            SELECT id FROM projects
            WHERE status NOT IN ('archived', 'cancelled')
            ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        if ($allProjects === []) {
            return;
        }

        foreach ($users as $user) {
            $userId = (int)$user['id'];
            $role = (string)$user['role_slug'];
            $projectIds = $this->projectIdsForUser($pdo, $userId, $role, $allProjects);

            foreach ($projectIds as $projectId) {
                $this->upsertAssignment($pdo, (int)$projectId, $userId, $role, $actorId);
            }
        }
    }

    public function down(PDO $pdo): void
    {
        $this->dropIndex($pdo, 'project_assignments', 'idx_project_assignments_project_status');
        $this->dropIndex($pdo, 'project_assignments', 'idx_project_assignments_user_role_status');
    }

    private function projectIdsForUser(PDO $pdo, int $userId, string $role, array $allProjects): array
    {
        if ($role === 'manager') {
            return array_map('intval', $allProjects);
        }

        if ($role === 'consultant') {
            $ids = $this->directProjects($pdo, 'consultant_id', $userId);
            return $ids !== [] ? $ids : array_slice(array_map('intval', $allProjects), 0, 3);
        }

        if ($role === 'contractor') {
            $ids = $this->directProjects($pdo, 'contractor_id', $userId);
            return $ids !== [] ? $ids : array_slice(array_map('intval', $allProjects), 0, 1);
        }

        if ($role === 'clerk') {
            return array_slice(array_map('intval', $allProjects), 0, 4);
        }

        if ($role === 'intern') {
            return array_slice(array_map('intval', $allProjects), 0, 2);
        }

        return [];
    }

    private function directProjects(PDO $pdo, string $column, int $userId): array
    {
        if (!$this->columnExists($pdo, 'projects', $column)) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT id FROM projects WHERE {$column} = ? ORDER BY id ASC");
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function upsertAssignment(PDO $pdo, int $projectId, int $userId, string $role, int $actorId): void
    {
        $existing = $pdo->prepare('SELECT id, status FROM project_assignments WHERE project_id = ? AND user_id = ? LIMIT 1');
        $existing->execute([$projectId, $userId]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        $type = $this->defaultType($role);
        $scope = $this->defaultScope($role);

        if ($row) {
            if ((string)$row['status'] !== 'active') {
                $stmt = $pdo->prepare("
                    UPDATE project_assignments
                    SET role = ?, assignment_type = ?, scope = ?, status = 'active',
                        revoked_by = NULL, revoked_at = NULL, updated_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([$role, $type, $scope, $actorId, (int)$row['id']]);
            }
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO project_assignments
                (project_id, user_id, role, assignment_type, scope, status, is_primary, notes, assigned_by, updated_by)
            VALUES
                (?, ?, ?, ?, ?, 'active', 0, 'Assigned by system administrator.', ?, ?)
        ");
        $stmt->execute([$projectId, $userId, $role, $type, $scope, $actorId, $actorId]);
    }

    private function defaultType(string $role): string
    {
        return match ($role) {
            'manager' => 'project-oversight',
            'clerk' => 'attendance-supervision',
            'intern' => 'reporting',
            'consultant' => 'ipc-verification',
            default => 'site',
        };
    }

    private function defaultScope(string $role): string
    {
        return match ($role) {
            'consultant' => 'quality',
            'contractor' => 'programme',
            'clerk' => 'attendance',
            'intern' => 'reporting',
            default => 'general',
        };
    }

    private function superadminId(PDO $pdo): int
    {
        $row = $pdo->query("
            SELECT u.id
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE r.slug = 'superadmin'
            ORDER BY u.id ASC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        return (int)($row['id'] ?? 0);
    }

    private function firstUserId(PDO $pdo): int
    {
        return (int)$pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function addIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        if (!$this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
        }
    }

    private function dropIndex(PDO $pdo, string $table, string $index): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
