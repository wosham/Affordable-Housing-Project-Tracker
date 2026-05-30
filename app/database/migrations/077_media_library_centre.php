<?php

class Migration_077_MediaLibraryCentre
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'media_library', 'title', "VARCHAR(180) NULL AFTER original_name");
        $this->addColumn($pdo, 'media_library', 'caption', "TEXT NULL AFTER alt_text");
        $this->addColumn($pdo, 'media_library', 'tags_json', "LONGTEXT NULL AFTER caption");
        $this->addColumn($pdo, 'media_library', 'width', "INT UNSIGNED NULL AFTER size");
        $this->addColumn($pdo, 'media_library', 'height', "INT UNSIGNED NULL AFTER width");
        $this->addColumn($pdo, 'media_library', 'extension', "VARCHAR(20) NULL AFTER height");
        $this->addColumn($pdo, 'media_library', 'source', "VARCHAR(60) NOT NULL DEFAULT 'upload' AFTER folder");
        $this->addColumn($pdo, 'media_library', 'is_protected', "TINYINT(1) NOT NULL DEFAULT 0 AFTER source");
        $this->addColumn($pdo, 'media_library', 'updated_at', "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");

        $this->addIndex($pdo, 'media_library', 'idx_media_folder', 'folder');
        $this->addIndex($pdo, 'media_library', 'idx_media_type', 'type');
        $this->addIndex($pdo, 'media_library', 'idx_media_path', 'path');

        $pdo->exec("CREATE TABLE IF NOT EXISTS media_usage (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            media_id       INT UNSIGNED NOT NULL,
            module         VARCHAR(80) NOT NULL,
            owner_type     VARCHAR(80) NOT NULL,
            owner_id       INT UNSIGNED NULL,
            field_name     VARCHAR(120) NULL,
            label          VARCHAR(180) NULL,
            url            VARCHAR(500) NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_media_usage_ref (media_id, module, owner_type, owner_id, field_name),
            KEY idx_media_usage_media (media_id),
            CONSTRAINT fk_media_usage_media FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS media_usage');
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
}
