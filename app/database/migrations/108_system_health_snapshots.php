<?php

class Migration108SystemHealthSnapshots
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_health_snapshots (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                status ENUM('healthy','warning','critical') DEFAULT 'healthy',
                score TINYINT UNSIGNED DEFAULT 100,
                checks_json JSON NULL,
                db_json JSON NULL,
                storage_json JSON NULL,
                workflow_json JSON NULL,
                security_json JSON NULL,
                created_by INT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_health_status_created (status, created_at),
                INDEX idx_health_created (created_at),
                INDEX idx_health_created_by (created_by, created_at),
                CONSTRAINT fk_health_snapshots_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS system_health_snapshots;');
    }
}
