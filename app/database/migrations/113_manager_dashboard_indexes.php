<?php

class Migration113ManagerDashboardIndexes
{
    public function up(PDO $pdo): void
    {
        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_user_project', 'user_id, project_id');
        $this->addIndex($pdo, 'milestones', 'idx_milestones_project_status_target', 'project_id, status, target_date');
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_project_status_end', 'project_id, status, end_date');
        $this->addIndex($pdo, 'attendance_records', 'idx_attendance_project_date_status', 'project_id, date, status');
        $this->addIndex($pdo, 'notifications', 'idx_notifications_user_read_created_mgr', 'user_id, is_read, created_at');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            ['notifications', 'idx_notifications_user_read_created_mgr'],
            ['attendance_records', 'idx_attendance_project_date_status'],
            ['programme_tasks', 'idx_programme_project_status_end'],
            ['milestones', 'idx_milestones_project_status_target'],
            ['project_assignments', 'idx_project_assignments_user_project'],
        ] as [$table, $index]) {
            $this->dropIndex($pdo, $table, $index);
        }
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
