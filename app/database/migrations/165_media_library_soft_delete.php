<?php

class Migration_165_MediaLibrarySoftDelete
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'media_library', 'deleted_at', 'DATETIME NULL AFTER updated_at');
        $this->addIndex($pdo, 'media_library', 'idx_media_deleted_at', 'deleted_at');
    }

    public function down(\PDO $pdo): void
    {
        $this->dropIndex($pdo, 'media_library', 'idx_media_deleted_at');
        $this->dropColumn($pdo, 'media_library', 'deleted_at');
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $column): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
        }
    }

    private function dropIndex(\PDO $pdo, string $table, string $index): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }

    private function dropColumn(\PDO $pdo, string $table, string $column): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }
}
