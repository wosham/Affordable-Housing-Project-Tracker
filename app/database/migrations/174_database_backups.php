<?php

class Migration_174_DatabaseBackups
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS database_backups (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            storage_key VARCHAR(255) NOT NULL UNIQUE,
            checksum_sha256 CHAR(64) NULL,
            size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'running',
            origin VARCHAR(30) NOT NULL DEFAULT 'manual',
            created_by INT UNSIGNED NULL,
            error_message VARCHAR(1000) NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_database_backups_status_created (status, created_at),
            INDEX idx_database_backups_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(PDO $pdo): void {}
}
