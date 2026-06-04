<?php

class Migration141ContractorProgressMediaReference
{
    public function up(PDO $pdo): void
    {
        if (!$this->columnExists($pdo, 'project_progress_updates', 'media_id')) {
            $pdo->exec("ALTER TABLE project_progress_updates ADD COLUMN media_id INT UNSIGNED NULL AFTER photo_path");
        }

        $this->addIndex($pdo, 'project_progress_updates', 'idx_progress_media', 'media_id');
        $this->addForeignKey(
            $pdo,
            'project_progress_updates',
            'fk_progress_media',
            'FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE SET NULL'
        );
    }

    public function down(PDO $pdo): void
    {
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }

    private function addIndex(PDO $pdo, string $table, string $name, string $column): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
        $stmt->execute([$table, $name]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$name} ({$column})");
        }
    }

    private function addForeignKey(PDO $pdo, string $table, string $name, string $definition): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1');
        $stmt->execute([$table, $name]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT {$name} {$definition}");
        }
    }
}
