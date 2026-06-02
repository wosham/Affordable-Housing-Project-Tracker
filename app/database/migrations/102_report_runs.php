<?php

class Migration102ReportRuns
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS report_runs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                report_type VARCHAR(80) NOT NULL,
                format VARCHAR(20) NOT NULL,
                filters_json JSON NULL,
                row_count INT UNSIGNED DEFAULT 0,
                status ENUM('generated','failed') DEFAULT 'generated',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_report_runs_user_created (user_id, created_at),
                INDEX idx_report_runs_type_created (report_type, created_at),
                CONSTRAINT fk_report_runs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS report_runs;');
    }
}
