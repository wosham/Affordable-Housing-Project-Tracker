<?php

class Migration_160_ContactPublicFlowHardening
{
    public function up(\PDO $pdo): void
    {
        $this->column($pdo, 'contact_submissions', 'attachment_path', 'VARCHAR(255) NULL AFTER message');
        $this->column($pdo, 'contact_submissions', 'status', "ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new' AFTER is_read");
        $this->column($pdo, 'contact_submissions', 'assigned_to', 'INT UNSIGNED NULL AFTER status');
        $this->column($pdo, 'contact_submissions', 'replied_at', 'DATETIME NULL AFTER assigned_to');
        $this->column($pdo, 'contact_submissions', 'response_note', 'TEXT NULL AFTER replied_at');
        $this->column($pdo, 'contact_submissions', 'read_at', 'DATETIME NULL AFTER response_note');
        $this->column($pdo, 'contact_submissions', 'archived_at', 'DATETIME NULL AFTER read_at');
        $this->column($pdo, 'contact_submissions', 'ip_address', 'VARCHAR(64) NULL AFTER archived_at');
        $this->column($pdo, 'contact_submissions', 'user_agent', 'VARCHAR(255) NULL AFTER ip_address');
        $this->column($pdo, 'contact_submissions', 'source_url', 'VARCHAR(500) NULL AFTER user_agent');
        $this->column($pdo, 'contact_submissions', 'updated_by', 'INT UNSIGNED NULL AFTER source_url');
        $this->column($pdo, 'contact_submissions', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $this->column($pdo, 'contact_departments', 'subject_key', 'VARCHAR(80) NULL AFTER name');
        $this->column($pdo, 'contact_departments', 'icon', 'VARCHAR(80) NULL AFTER phone');
        $this->column($pdo, 'contact_departments', 'accent', 'VARCHAR(30) NULL AFTER icon');

        $this->index($pdo, 'contact_submissions', 'idx_contact_submissions_status', 'status, created_at');
        $this->index($pdo, 'contact_submissions', 'idx_contact_read_status', 'is_read, status');
        $this->index($pdo, 'contact_submissions', 'idx_contact_assigned_status', 'assigned_to, status');
        $this->index($pdo, 'contact_submissions', 'idx_contact_created', 'created_at');
        $this->index($pdo, 'contact_submissions', 'idx_contact_email', 'email');
        $this->index($pdo, 'contact_submissions', 'idx_contact_ip_created', 'ip_address, created_at');
        $this->index($pdo, 'contact_departments', 'idx_contact_departments_visible', 'is_visible, sort_order');
        $this->index($pdo, 'contact_departments', 'idx_contact_departments_subject', 'subject_key');
    }

    public function down(\PDO $pdo): void
    {
        foreach (['idx_contact_departments_subject', 'idx_contact_ip_created'] as $index) {
            $this->dropIndex($pdo, str_starts_with($index, 'idx_contact_departments') ? 'contact_departments' : 'contact_submissions', $index);
        }
    }

    private function column(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function index(\PDO $pdo, string $table, string $index, string $columns): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
        }
    }

    private function dropIndex(\PDO $pdo, string $table, string $index): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
}
