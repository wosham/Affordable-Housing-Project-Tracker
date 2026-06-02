<?php

class Migration_098_BOQCentreUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'boq_items', 'updated_by', 'INT UNSIGNED NULL AFTER status');
        $this->addColumn($pdo, 'boq_items', 'last_certified_at', 'DATETIME NULL AFTER updated_by');
        $this->addColumn($pdo, 'boq_items', 'last_paid_at', 'DATETIME NULL AFTER last_certified_at');
        $this->addColumn($pdo, 'boq_items', 'notes', 'TEXT NULL AFTER last_paid_at');
        $this->addColumn($pdo, 'boq_items', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER notes');
        $this->addColumn($pdo, 'boq_items', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $pdo->exec('UPDATE boq_items SET amount = ROUND(COALESCE(quantity, 0) * COALESCE(rate, 0), 2) WHERE COALESCE(amount, 0) = 0');

        $this->addIndex($pdo, 'boq_items', 'idx_boq_project', 'project_id');
        $this->addIndex($pdo, 'boq_items', 'idx_boq_project_section', 'project_id, section');
        $this->addIndex($pdo, 'boq_items', 'idx_boq_project_status', 'project_id, status');
        $this->addIndex($pdo, 'boq_items', 'idx_boq_item_no', 'item_no');
    }

    public function down(\PDO $pdo): void
    {
        $this->dropIndex($pdo, 'boq_items', 'idx_boq_item_no');
        $this->dropIndex($pdo, 'boq_items', 'idx_boq_project_status');
        $this->dropIndex($pdo, 'boq_items', 'idx_boq_project_section');
        $this->dropIndex($pdo, 'boq_items', 'idx_boq_project');

        foreach (['updated_at', 'created_at', 'notes', 'last_paid_at', 'last_certified_at', 'updated_by'] as $column) {
            $this->dropColumn($pdo, 'boq_items', $column);
        }
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function dropColumn(\PDO $pdo, string $table, string $column): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
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
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(\PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
