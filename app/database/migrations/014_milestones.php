<?php
class Migration_014_Milestones
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS milestones (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id   INT UNSIGNED NOT NULL,
            label        VARCHAR(200) NOT NULL,
            target_date  DATE NULL,
            actual_date  DATE NULL,
            status       ENUM('pending','current','done') DEFAULT 'pending',
            sequence     SMALLINT UNSIGNED DEFAULT 0,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project_seq (project_id, sequence)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS milestones;"); }
}
