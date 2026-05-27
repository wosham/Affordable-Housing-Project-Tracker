<?php
class Migration_026_CommunityLiaison
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS community_liaison (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id       INT UNSIGNED NOT NULL,
            log_date         DATE NOT NULL,
            engagement_type  VARCHAR(100) NULL,
            community_rep    VARCHAR(150) NULL,
            issues_raised    TEXT NULL,
            resolution       TEXT NULL,
            follow_up_date   DATE NULL,
            recorded_by      INT UNSIGNED NOT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS community_liaison;"); }
}
