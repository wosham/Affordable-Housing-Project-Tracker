<?php
class Migration_018_ProgrammeTasks
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS programme_tasks (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id         INT UNSIGNED NOT NULL,
            task_name          VARCHAR(200) NOT NULL,
            start_date         DATE NULL,
            end_date           DATE NULL,
            planned_start      DATE NULL,
            planned_end        DATE NULL,
            pct_complete       TINYINT UNSIGNED DEFAULT 0,
            depends_on_task_id INT UNSIGNED NULL,
            assigned_to        INT UNSIGNED NULL,
            status             VARCHAR(40) DEFAULT 'pending',
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS programme_tasks;"); }
}
