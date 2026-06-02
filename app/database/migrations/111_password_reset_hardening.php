<?php

class Migration111PasswordResetHardening
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'password_resets', 'email', 'VARCHAR(190) NULL AFTER user_id');
        $this->addColumn($pdo, 'password_resets', 'token_hash', 'CHAR(64) NULL AFTER token');
        $this->addColumn($pdo, 'password_resets', 'used_at', 'DATETIME NULL AFTER used');
        $this->addColumn($pdo, 'password_resets', 'created_ip', 'VARCHAR(45) NULL AFTER used_at');
        $this->addColumn($pdo, 'password_resets', 'created_user_agent', 'VARCHAR(255) NULL AFTER created_ip');
        $this->addIndex($pdo, 'password_resets', 'idx_password_resets_hash', 'token_hash');
        $this->addIndex($pdo, 'password_resets', 'idx_password_resets_user_used', 'user_id, used, expires_at');
        $this->addIndex($pdo, 'password_resets', 'idx_password_resets_email_created', 'email, created_at');
        $this->addIndex($pdo, 'password_resets', 'idx_password_resets_ip_created', 'created_ip, created_at');
    }

    public function down(PDO $pdo): void
    {
        foreach (['idx_password_resets_ip_created', 'idx_password_resets_email_created', 'idx_password_resets_user_used', 'idx_password_resets_hash'] as $index) {
            $this->dropIndex($pdo, 'password_resets', $index);
        }
        foreach (['created_user_agent', 'created_ip', 'used_at', 'token_hash', 'email'] as $column) {
            $this->dropColumn($pdo, 'password_resets', $column);
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
