<?php
class Migration_028_EnvironmentalLogs
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS environmental_logs (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id       INT UNSIGNED NOT NULL,
            log_date         DATE NOT NULL,
            observation_type VARCHAR(100) NULL,
            description      TEXT NOT NULL,
            action_taken     TEXT NULL,
            recorded_by      INT UNSIGNED NOT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS environmental_logs;"); }
}
