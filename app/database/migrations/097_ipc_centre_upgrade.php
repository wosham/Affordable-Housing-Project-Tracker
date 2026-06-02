<?php

class Migration_097_IPCCentreUpgrade
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE ipcs MODIFY status ENUM('draft','submitted','clerk-endorsed','certified','endorsed','approved','rejected','paid') DEFAULT 'draft'");

        $this->addColumn($pdo, 'ipcs', 'approved_by', 'INT UNSIGNED NULL AFTER approved_at');
        $this->addColumn($pdo, 'ipcs', 'rejected_by', 'INT UNSIGNED NULL AFTER approved_by');
        $this->addColumn($pdo, 'ipcs', 'rejected_at', 'DATETIME NULL AFTER rejected_by');
        $this->addColumn($pdo, 'ipcs', 'rejection_reason', 'TEXT NULL AFTER rejected_at');

        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_status', 'status');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_project_status', 'project_id, status');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_contractor_status', 'contractor_id, status');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_submitted_at', 'submitted_at');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_approved_at', 'approved_at');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_paid_at', 'paid_at');

        $this->addIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_ipc_step', 'ipc_id, step');
        $this->addIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_action', 'action');
        $this->addIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_actioned_at', 'actioned_at');

        $this->addIndex($pdo, 'ipc_lines', 'idx_ipc_lines_ipc', 'ipc_id');
        $this->addIndex($pdo, 'ipc_lines', 'idx_ipc_lines_boq', 'boq_item_id');
    }

    public function down(\PDO $pdo): void
    {
        $this->dropIndex($pdo, 'ipc_lines', 'idx_ipc_lines_boq');
        $this->dropIndex($pdo, 'ipc_lines', 'idx_ipc_lines_ipc');
        $this->dropIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_actioned_at');
        $this->dropIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_action');
        $this->dropIndex($pdo, 'ipc_approvals', 'idx_ipc_approvals_ipc_step');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_paid_at');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_approved_at');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_submitted_at');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_contractor_status');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_project_status');
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $columns): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
    }

    private function dropIndex(\PDO $pdo, string $table, string $index): void
    {
        if (!$this->indexExists($pdo, $table, $index)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
    }

    private function columnExists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(\PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);

        return (int)$stmt->fetchColumn() > 0;
    }
}
