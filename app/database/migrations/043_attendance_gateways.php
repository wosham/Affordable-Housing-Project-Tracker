<?php
class Migration_043_AttendanceGateways
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_gateways (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id  INT UNSIGNED NOT NULL,
            date        DATE NOT NULL,
            opened_by   INT UNSIGNED NOT NULL,
            opened_at   DATETIME NOT NULL,
            closes_at   DATETIME NOT NULL,
            is_open     TINYINT(1) DEFAULT 1,
            notes       TEXT NULL,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (opened_by)  REFERENCES users(id),
            UNIQUE KEY uq_project_date (project_id, date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS attendance_gateways;"); }
}
