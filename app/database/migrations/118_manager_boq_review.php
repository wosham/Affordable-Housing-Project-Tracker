<?php

class Migration118ManagerBOQReview
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'boq_items', 'review_status', "VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
        $this->addColumn($pdo, 'boq_items', 'risk_status', "VARCHAR(30) NOT NULL DEFAULT 'normal' AFTER review_status");
        $this->addColumn($pdo, 'boq_items', 'manager_note', 'TEXT NULL AFTER risk_status');
        $this->addColumn($pdo, 'boq_items', 'last_reviewed_by', 'INT UNSIGNED NULL AFTER manager_note');
        $this->addColumn($pdo, 'boq_items', 'last_reviewed_at', 'DATETIME NULL AFTER last_reviewed_by');
        $this->addColumn($pdo, 'boq_items', 'certified_updated_by', 'INT UNSIGNED NULL AFTER last_reviewed_at');
        $this->addColumn($pdo, 'boq_items', 'paid_updated_by', 'INT UNSIGNED NULL AFTER certified_updated_by');

        $this->addIndex($pdo, 'boq_items', 'idx_boq_project_review', 'project_id, review_status');
        $this->addIndex($pdo, 'boq_items', 'idx_boq_project_risk', 'project_id, risk_status');
        $this->addIndex($pdo, 'boq_items', 'idx_boq_reviewed_by', 'last_reviewed_by');

        $pdo->exec("CREATE TABLE IF NOT EXISTS boq_review_updates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            boq_item_id INT UNSIGNED NOT NULL,
            project_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            old_certified_qty DECIMAL(12,3) NULL,
            new_certified_qty DECIMAL(12,3) NULL,
            old_paid_qty DECIMAL(12,3) NULL,
            new_paid_qty DECIMAL(12,3) NULL,
            old_review_status VARCHAR(30) NULL,
            new_review_status VARCHAR(30) NULL,
            old_risk_status VARCHAR(30) NULL,
            new_risk_status VARCHAR(30) NULL,
            note TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (boq_item_id) REFERENCES boq_items(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_boq_review_updates_item (boq_item_id, created_at),
            INDEX idx_boq_review_updates_project (project_id, created_at),
            INDEX idx_boq_review_updates_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS boq_review_updates');

        foreach ([
            'idx_boq_reviewed_by',
            'idx_boq_project_risk',
            'idx_boq_project_review',
        ] as $index) {
            $this->dropIndex($pdo, 'boq_items', $index);
        }

        foreach ([
            'paid_updated_by',
            'certified_updated_by',
            'last_reviewed_at',
            'last_reviewed_by',
            'manager_note',
            'risk_status',
            'review_status',
        ] as $column) {
            $this->dropColumn($pdo, 'boq_items', $column);
        }
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function dropColumn(PDO $pdo, string $table, string $column): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
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
