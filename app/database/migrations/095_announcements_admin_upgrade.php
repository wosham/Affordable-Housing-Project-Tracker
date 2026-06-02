<?php

class Migration_095_AnnouncementsAdminUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'announcements', 'type', "VARCHAR(60) DEFAULT 'info' AFTER body");
        $this->addColumn($pdo, 'announcements', 'status', "ENUM('draft','published','archived') DEFAULT 'draft' AFTER type");
        $this->addColumn($pdo, 'announcements', 'priority', "ENUM('low','normal','high','urgent') DEFAULT 'normal' AFTER status");
        $this->addColumn($pdo, 'announcements', 'cta_label', "VARCHAR(120) NULL AFTER is_pinned");
        $this->addColumn($pdo, 'announcements', 'cta_url', "VARCHAR(255) NULL AFTER cta_label");
        $this->addColumn($pdo, 'announcements', 'published_at', "DATETIME NULL AFTER created_at");
        $this->addColumn($pdo, 'announcements', 'updated_at', "DATETIME NULL AFTER published_at");
        $this->addColumn($pdo, 'announcements', 'archived_at', "DATETIME NULL AFTER expires_at");
        $this->addColumn($pdo, 'announcements', 'metadata_json', "LONGTEXT NULL AFTER archived_at");

        $this->addIndex($pdo, 'announcements', 'idx_announcements_status', 'status');
        $this->addIndex($pdo, 'announcements', 'idx_announcements_type', 'type');
        $this->addIndex($pdo, 'announcements', 'idx_announcements_priority', 'priority');
        $this->addIndex($pdo, 'announcements', 'idx_announcements_pinned', 'is_pinned');
        $this->addIndex($pdo, 'announcements', 'idx_announcements_published_at', 'published_at');
        $this->addIndex($pdo, 'announcements', 'idx_announcements_expires_at', 'expires_at');
        $this->addIndex($pdo, 'announcements', 'idx_announcements_author', 'author_id');

        $pdo->exec("UPDATE announcements SET status = 'published' WHERE status IS NULL OR status = ''");
        $pdo->exec("UPDATE announcements SET published_at = created_at WHERE status = 'published' AND published_at IS NULL");
    }

    public function down(\PDO $pdo): void
    {
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_author');
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_expires_at');
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_published_at');
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_pinned');
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_priority');
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_type');
        $this->dropIndex($pdo, 'announcements', 'idx_announcements_status');
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $column): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
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
