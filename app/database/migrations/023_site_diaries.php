<?php
class Migration_023_SiteDiaries
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_diaries (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id    INT UNSIGNED NOT NULL,
            diary_date    DATE NOT NULL,
            work_done     TEXT NULL,
            issues_raised TEXT NULL,
            next_day_plan TEXT NULL,
            recorded_by   INT UNSIGNED NOT NULL,
            approved_by   INT UNSIGNED NULL,
            approved_at   DATETIME NULL,
            created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id),
            UNIQUE KEY uq_project_date (project_id, diary_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS site_diaries;"); }
}
