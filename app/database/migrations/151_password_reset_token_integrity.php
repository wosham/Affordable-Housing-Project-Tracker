<?php

class Migration151PasswordResetTokenIntegrity
{
    public function up(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'password_resets')) {
            return;
        }

        if (!$this->columnExists($pdo, 'password_resets', 'token_hash')) {
            $pdo->exec('ALTER TABLE password_resets ADD COLUMN token_hash CHAR(64) NULL AFTER token');
        }

        $pdo->exec("
            UPDATE password_resets
            SET token_hash = SHA2(token, 256)
            WHERE token_hash IS NULL
              AND token IS NOT NULL
              AND token <> ''
        ");

        $pdo->exec("
            UPDATE password_resets
            SET token = COALESCE(NULLIF(token_hash, ''), SHA2(CONCAT('legacy-reset:', id, ':', COALESCE(email, ''), ':', created_at), 256))
            WHERE token IS NULL
               OR token = ''
        ");

        $pdo->exec('ALTER TABLE password_resets MODIFY token CHAR(64) NOT NULL');

        if (!$this->indexExists($pdo, 'password_resets', 'idx_password_resets_hash')) {
            $pdo->exec('ALTER TABLE password_resets ADD INDEX idx_password_resets_hash (token_hash)');
        }
    }

    public function down(PDO $pdo): void
    {
        if ($this->tableExists($pdo, 'password_resets')) {
            $pdo->exec('ALTER TABLE password_resets MODIFY token VARCHAR(255) NOT NULL');
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
}
