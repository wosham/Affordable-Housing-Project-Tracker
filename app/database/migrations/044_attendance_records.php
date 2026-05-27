<?php
class Migration_044_AttendanceRecords
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_records (
            id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id               INT UNSIGNED NOT NULL,
            project_id            INT UNSIGNED NOT NULL,
            gateway_id            INT UNSIGNED NOT NULL,
            date                  DATE NOT NULL,
            signin_time           TIME NULL,
            latitude              DECIMAL(10,8) NULL,
            longitude             DECIMAL(11,8) NULL,
            distance_from_site_m  DECIMAL(8,1) NULL,
            status                ENUM('present','absent','geo-fail','outside-window','late') DEFAULT 'absent',
            created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id)     REFERENCES users(id)                ON DELETE CASCADE,
            FOREIGN KEY (project_id)  REFERENCES projects(id)             ON DELETE CASCADE,
            FOREIGN KEY (gateway_id)  REFERENCES attendance_gateways(id)  ON DELETE CASCADE,
            UNIQUE KEY uq_user_date (user_id, date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS attendance_records;"); }
}
