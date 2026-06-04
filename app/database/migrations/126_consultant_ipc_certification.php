<?php

class Migration126ConsultantIpcCertification
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'ipcs', 'certified_by', 'INT UNSIGNED NULL AFTER certified_at');
        $this->addColumn($pdo, 'ipcs', 'certification_comment', 'TEXT NULL AFTER certified_by');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_project_status_certified', 'project_id, status, certified_at');
        $this->addIndex($pdo, 'ipc_lines', 'idx_ipc_lines_ipc_boq', 'ipc_id, boq_item_id');
        $this->addIndex($pdo, 'boq_items', 'idx_boq_project_item_no', 'project_id, item_no');
    }

    public function down(PDO $pdo): void
    {
        $this->dropIndex($pdo, 'boq_items', 'idx_boq_project_item_no');
        $this->dropIndex($pdo, 'ipc_lines', 'idx_ipc_lines_ipc_boq');
        $this->dropIndex($pdo, 'ipcs', 'idx_ipcs_project_status_certified');
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
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

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
