<?php

class Migration109MessagesCentreUpgrade
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'message_threads', 'status', "ENUM('open','archived') DEFAULT 'open' AFTER type");
        $this->addColumn($pdo, 'message_threads', 'priority', "ENUM('normal','urgent') DEFAULT 'normal' AFTER status");
        $this->addColumn($pdo, 'message_threads', 'last_message_id', 'INT UNSIGNED NULL AFTER created_at');
        $this->addColumn($pdo, 'message_threads', 'last_message_at', 'DATETIME NULL AFTER last_message_id');
        $this->addColumn($pdo, 'message_threads', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER last_message_at');

        $this->addColumn($pdo, 'messages', 'parent_id', 'INT UNSIGNED NULL AFTER sender_id');
        $this->addColumn($pdo, 'messages', 'deleted_by', 'INT UNSIGNED NULL AFTER is_deleted');
        $this->addColumn($pdo, 'messages', 'deleted_at', 'DATETIME NULL AFTER deleted_by');
        $this->addColumn($pdo, 'messages', 'metadata_json', 'JSON NULL AFTER deleted_at');

        $this->addColumn($pdo, 'message_participants', 'role_at_join', 'VARCHAR(50) NULL AFTER user_id');
        $this->addColumn($pdo, 'message_participants', 'last_read_at', 'DATETIME NULL AFTER joined_at');
        $this->addColumn($pdo, 'message_participants', 'is_muted', 'TINYINT(1) DEFAULT 0 AFTER is_admin');
        $this->addColumn($pdo, 'message_participants', 'is_archived', 'TINYINT(1) DEFAULT 0 AFTER is_muted');
        $this->addColumn($pdo, 'message_participants', 'archived_at', 'DATETIME NULL AFTER is_archived');

        $this->modifyColumn($pdo, 'message_attachments', 'message_id', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'message_attachments', 'uploaded_by', 'INT UNSIGNED NULL AFTER message_id');
        $this->addColumn($pdo, 'message_attachments', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER path');
        $this->addColumn($pdo, 'message_attachments', 'mime_type', 'VARCHAR(120) NULL AFTER type');
        $this->addColumn($pdo, 'message_attachments', 'download_count', 'INT UNSIGNED DEFAULT 0 AFTER created_at');
        $this->addColumn($pdo, 'message_attachments', 'checksum', 'VARCHAR(128) NULL AFTER download_count');
        $this->addColumn($pdo, 'message_attachments', 'upload_token', 'VARCHAR(64) NULL AFTER checksum');

        $this->addIndex($pdo, 'message_threads', 'idx_threads_last_message', 'last_message_at');
        $this->addIndex($pdo, 'message_threads', 'idx_threads_project', 'project_id');
        $this->addIndex($pdo, 'messages', 'idx_messages_thread_created', 'thread_id, created_at');
        $this->addIndex($pdo, 'messages', 'idx_messages_sender', 'sender_id');
        $this->addIndex($pdo, 'message_participants', 'idx_participants_user', 'user_id, is_archived');
        $this->addIndex($pdo, 'message_participants', 'idx_participants_thread', 'thread_id');
        $this->addIndex($pdo, 'message_reads', 'idx_reads_user', 'user_id');
        $this->addIndex($pdo, 'message_attachments', 'idx_attachments_message', 'message_id');
        $this->addIndex($pdo, 'message_attachments', 'idx_attachments_token', 'upload_token');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            ['message_attachments', 'idx_attachments_token'],
            ['message_attachments', 'idx_attachments_message'],
            ['message_reads', 'idx_reads_user'],
            ['message_participants', 'idx_participants_thread'],
            ['message_participants', 'idx_participants_user'],
            ['messages', 'idx_messages_sender'],
            ['messages', 'idx_messages_thread_created'],
            ['message_threads', 'idx_threads_project'],
            ['message_threads', 'idx_threads_last_message'],
        ] as [$table, $index]) {
            $this->dropIndex($pdo, $table, $index);
        }

        foreach (['upload_token', 'checksum', 'download_count', 'mime_type', 'created_at', 'uploaded_by'] as $column) {
            $this->dropColumn($pdo, 'message_attachments', $column);
        }
        $this->modifyColumn($pdo, 'message_attachments', 'message_id', 'INT UNSIGNED NOT NULL');

        foreach (['archived_at', 'is_archived', 'is_muted', 'last_read_at', 'role_at_join'] as $column) {
            $this->dropColumn($pdo, 'message_participants', $column);
        }

        foreach (['metadata_json', 'deleted_at', 'deleted_by', 'parent_id'] as $column) {
            $this->dropColumn($pdo, 'messages', $column);
        }

        foreach (['updated_at', 'last_message_at', 'last_message_id', 'priority', 'status'] as $column) {
            $this->dropColumn($pdo, 'message_threads', $column);
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

    private function modifyColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} MODIFY COLUMN {$column} {$definition}");
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
