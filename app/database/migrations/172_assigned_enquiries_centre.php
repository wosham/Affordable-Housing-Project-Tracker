<?php

class Migration_172_AssignedEnquiriesCentre
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'contact_submissions', 'priority', "ENUM('normal','urgent') NOT NULL DEFAULT 'normal' AFTER status");
        $this->addColumn($pdo, 'contact_submissions', 'internal_note', 'TEXT NULL AFTER response_note');
        $this->addColumn($pdo, 'contact_submissions', 'follow_up_at', 'DATE NULL AFTER internal_note');
        $this->addColumn($pdo, 'contact_submissions', 'assigned_at', 'DATETIME NULL AFTER assigned_to');

        // Expand status support for workflow (keep existing values; app also uses in_progress).
        try {
            $pdo->exec("ALTER TABLE contact_submissions MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'new'");
        } catch (Throwable) {
            // Column may already be free-form.
        }

        $this->addIndex($pdo, 'contact_submissions', 'idx_contact_follow_up', 'follow_up_at');
        $this->addIndex($pdo, 'contact_submissions', 'idx_contact_priority', 'priority');

        $pdo->exec("UPDATE contact_submissions SET assigned_at = COALESCE(updated_at, created_at) WHERE assigned_to IS NOT NULL AND assigned_at IS NULL");
    }

    public function down(\PDO $pdo): void
    {
        foreach (['idx_contact_priority', 'idx_contact_follow_up'] as $index) {
            $this->dropIndex($pdo, 'contact_submissions', $index);
        }
        foreach (['assigned_at', 'follow_up_at', 'internal_note', 'priority'] as $column) {
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
