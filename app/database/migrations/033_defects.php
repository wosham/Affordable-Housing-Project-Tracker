<?php
class Migration_033_Defects
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS defects (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id   INT UNSIGNED NOT NULL,
            raised_by    INT UNSIGNED NOT NULL,
            raised_date  DATE NOT NULL,
            location     VARCHAR(200) NULL,
            description  TEXT NOT NULL,
            severity     ENUM('minor','major','critical') DEFAULT 'minor',
            photo_path   VARCHAR(255) NULL,
            assigned_to  INT UNSIGNED NULL,
            due_date     DATE NULL,
            closed_date  DATE NULL,
            status       ENUM('open','in-progress','resolved','closed') DEFAULT 'open',
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (raised_by)   REFERENCES users(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS defects;"); }
}
