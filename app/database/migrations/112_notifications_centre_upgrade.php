<?php

class Migration112NotificationsCentreUpgrade
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'notifications', 'read_at', 'DATETIME NULL AFTER is_read');
        $this->addColumn($pdo, 'notifications', 'priority', "ENUM('normal','urgent') DEFAULT 'normal' AFTER type");
        $this->addColumn($pdo, 'notifications', 'source_module', 'VARCHAR(80) NULL AFTER link');
        $this->addColumn($pdo, 'notifications', 'source_id', 'INT UNSIGNED NULL AFTER source_module');
        $this->addColumn($pdo, 'notifications', 'metadata_json', 'JSON NULL AFTER source_id');

        $this->addIndex($pdo, 'notifications', 'idx_notifications_user_created', 'user_id, created_at');
        $this->addIndex($pdo, 'notifications', 'idx_notifications_user_read_created', 'user_id, is_read, created_at');
        $this->addIndex($pdo, 'notifications', 'idx_notifications_type_created', 'type, created_at');
        $this->addIndex($pdo, 'notifications', 'idx_notifications_source', 'source_module, source_id');
    }

    public function down(PDO $pdo): void
    {
        foreach (['idx_notifications_source', 'idx_notifications_type_created', 'idx_notifications_user_read_created', 'idx_notifications_user_created'] as $index) {
            $this->dropIndex($pdo, 'notifications', $index);
        }

        foreach (['metadata_json', 'source_id', 'source_module', 'priority', 'read_at'] as $column) {
            $this->dropColumn($pdo, 'notifications', $column);
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
