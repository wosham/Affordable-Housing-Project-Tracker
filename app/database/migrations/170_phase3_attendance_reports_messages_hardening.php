<?php

class Migration170Phase3AttendanceReportsMessagesHardening
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'attendance_records', 'review_notes', 'TEXT NULL AFTER review_status');
        $this->addUnique($pdo, 'attendance_gateways', 'uq_work_location_date', 'work_location_id, date');

        $this->addForeignKey($pdo, 'work_location_assignments', 'fk_wla_location', 'work_location_id', 'work_locations', 'id', 'CASCADE');
        $this->addForeignKey($pdo, 'work_location_assignments', 'fk_wla_user', 'user_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey($pdo, 'attendance_gateways', 'fk_gateways_work_location', 'work_location_id', 'work_locations', 'id', 'SET NULL');
        $this->addForeignKey($pdo, 'attendance_records', 'fk_attendance_work_location', 'work_location_id', 'work_locations', 'id', 'SET NULL');

        $this->addForeignKey($pdo, 'message_threads', 'fk_message_threads_created_by', 'created_by', 'users', 'id', 'SET NULL');
        $this->addForeignKey($pdo, 'message_threads', 'fk_message_threads_project', 'project_id', 'projects', 'id', 'SET NULL');
        $this->addForeignKey($pdo, 'messages', 'fk_messages_thread', 'thread_id', 'message_threads', 'id', 'CASCADE');
        $this->addForeignKey($pdo, 'messages', 'fk_messages_sender', 'sender_id', 'users', 'id', 'SET NULL');
        $this->addForeignKey($pdo, 'message_participants', 'fk_participants_thread', 'thread_id', 'message_threads', 'id', 'CASCADE');
        $this->addForeignKey($pdo, 'message_participants', 'fk_participants_user', 'user_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey($pdo, 'message_attachments', 'fk_attachments_message', 'message_id', 'messages', 'id', 'CASCADE');
        $this->addForeignKey($pdo, 'message_attachments', 'fk_attachments_uploader', 'uploaded_by', 'users', 'id', 'SET NULL');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            ['message_attachments', 'fk_attachments_uploader'],
            ['message_attachments', 'fk_attachments_message'],
            ['message_participants', 'fk_participants_user'],
            ['message_participants', 'fk_participants_thread'],
            ['messages', 'fk_messages_sender'],
            ['messages', 'fk_messages_thread'],
            ['message_threads', 'fk_message_threads_project'],
            ['message_threads', 'fk_message_threads_created_by'],
            ['attendance_records', 'fk_attendance_work_location'],
            ['attendance_gateways', 'fk_gateways_work_location'],
            ['work_location_assignments', 'fk_wla_user'],
            ['work_location_assignments', 'fk_wla_location'],
        ] as [$table, $constraint]) {
            $this->dropForeignKey($pdo, $table, $constraint);
        }

        $this->dropIndex($pdo, 'attendance_gateways', 'uq_work_location_date');
        $this->dropColumn($pdo, 'attendance_records', 'review_notes');
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

    private function addUnique(PDO $pdo, string $table, string $index, string $columns): void
    {
        if (!$this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} ADD UNIQUE KEY {$index} ({$columns})");
        }
    }

    private function dropIndex(PDO $pdo, string $table, string $index): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }

    private function addForeignKey(PDO $pdo, string $table, string $constraint, string $column, string $refTable, string $refColumn, string $onDelete): void
    {
        if (!$this->tableExists($pdo, $table) || !$this->tableExists($pdo, $refTable) || !$this->columnExists($pdo, $table, $column)) {
            return;
        }
        if ($this->foreignKeyExists($pdo, $table, $constraint)) {
            return;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} t LEFT JOIN {$refTable} r ON r.{$refColumn} = t.{$column} WHERE t.{$column} IS NOT NULL AND r.{$refColumn} IS NULL");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$refTable} ({$refColumn}) ON DELETE {$onDelete}");
    }

    private function dropForeignKey(PDO $pdo, string $table, string $constraint): void
    {
        if ($this->foreignKeyExists($pdo, $table, $constraint)) {
            $pdo->exec("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
        }
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
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

    private function foreignKeyExists(PDO $pdo, string $table, string $constraint): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY"');
        $stmt->execute([$table, $constraint]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
