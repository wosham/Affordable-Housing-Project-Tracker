<?php

class Migration121ManagerProgrammeIndexes
{
    public function up(PDO $pdo): void
    {
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_project_critical_end', 'project_id, critical_path, planned_end');
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_project_assignee_status', 'project_id, assigned_to, status');
    }

    public function down(PDO $pdo): void
    {
        $this->dropIndex($pdo, 'programme_tasks', 'idx_programme_project_assignee_status');
        $this->dropIndex($pdo, 'programme_tasks', 'idx_programme_project_critical_end');
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
