<?php

class Migration_100_ContactInboxUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'contact_submissions', 'response_note', 'TEXT NULL AFTER replied_at');
        $this->addColumn($pdo, 'contact_submissions', 'read_at', 'DATETIME NULL AFTER response_note');
        $this->addColumn($pdo, 'contact_submissions', 'archived_at', 'DATETIME NULL AFTER read_at');
        $this->addColumn($pdo, 'contact_submissions', 'ip_address', 'VARCHAR(64) NULL AFTER archived_at');
        $this->addColumn($pdo, 'contact_submissions', 'user_agent', 'VARCHAR(255) NULL AFTER ip_address');
        $this->addColumn($pdo, 'contact_submissions', 'source_url', 'VARCHAR(500) NULL AFTER user_agent');
        $this->addColumn($pdo, 'contact_submissions', 'updated_by', 'INT UNSIGNED NULL AFTER source_url');
        $this->addColumn($pdo, 'contact_submissions', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by');

        $pdo->exec("UPDATE contact_submissions SET read_at = created_at WHERE is_read = 1 AND read_at IS NULL");
        $pdo->exec("UPDATE contact_submissions SET archived_at = created_at WHERE status = 'archived' AND archived_at IS NULL");
        $pdo->exec("UPDATE contact_submissions SET replied_at = created_at WHERE status = 'replied' AND replied_at IS NULL");

        $this->addIndex($pdo, 'contact_submissions', 'idx_contact_read_status', 'is_read, status');
        $this->addIndex($pdo, 'contact_submissions', 'idx_contact_assigned_status', 'assigned_to, status');
        $this->addIndex($pdo, 'contact_submissions', 'idx_contact_created', 'created_at');
        $this->addIndex($pdo, 'contact_submissions', 'idx_contact_email', 'email');
    }

    public function down(\PDO $pdo): void
    {
        foreach (['idx_contact_email', 'idx_contact_created', 'idx_contact_assigned_status', 'idx_contact_read_status'] as $index) {
            $this->dropIndex($pdo, 'contact_submissions', $index);
        }

        foreach (['updated_at', 'updated_by', 'source_url', 'user_agent', 'ip_address', 'archived_at', 'read_at', 'response_note'] as $column) {
            $this->dropColumn($pdo, 'contact_submissions', $column);
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
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(\PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
