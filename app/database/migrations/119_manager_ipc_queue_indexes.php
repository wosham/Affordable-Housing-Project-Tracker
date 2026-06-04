<?php

class Migration119ManagerIPCQueueIndexes
{
    public function up(PDO $pdo): void
    {
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_project_status_submitted', 'project_id, status, submitted_at');
        $this->addIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_ipc_step_actioned', 'ipc_id, step, actioned_at');
    }

    public function down(PDO $pdo): void
    {
        $this->dropIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_ipc_step_actioned');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_project_status_submitted');
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
