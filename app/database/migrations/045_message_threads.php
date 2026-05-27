<?php
class Migration_045_MessageThreads
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS message_threads (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject     VARCHAR(255) NOT NULL,
            type        ENUM('direct','group','project-channel') DEFAULT 'direct',
            project_id  INT UNSIGNED NULL,
            created_by  INT UNSIGNED NOT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS message_threads;"); }
}
