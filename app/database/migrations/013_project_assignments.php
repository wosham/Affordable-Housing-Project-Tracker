<?php
class Migration_013_ProjectAssignments
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_assignments (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id  INT UNSIGNED NOT NULL,
            user_id     INT UNSIGNED NOT NULL,
            role        VARCHAR(60)  NOT NULL,
            assigned_by INT UNSIGNED NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
            UNIQUE KEY uq_project_user (project_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS project_assignments;"); }
}
