<?php
class Migration_050_ProjectChannels
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_channels (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id  INT UNSIGNED NOT NULL,
            name        VARCHAR(150) NOT NULL,
            description TEXT NULL,
            created_by  INT UNSIGNED NOT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS project_channels;"); }
}
