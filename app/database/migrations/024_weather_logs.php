<?php
class Migration_024_WeatherLogs
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS weather_logs (
            id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id           INT UNSIGNED NOT NULL,
            log_date             DATE NOT NULL,
            morning_condition    VARCHAR(60) NULL,
            afternoon_condition  VARCHAR(60) NULL,
            rainfall_mm          DECIMAL(5,1) DEFAULT 0,
            working_hours        DECIMAL(4,1) DEFAULT 8,
            remarks              TEXT NULL,
            recorded_by          INT UNSIGNED NOT NULL,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS weather_logs;"); }
}
