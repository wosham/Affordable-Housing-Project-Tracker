<?php
class Migration_016_Documents
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id      INT UNSIGNED NOT NULL,
            uploaded_by     INT UNSIGNED NOT NULL,
            category        ENUM('contract','drawing','spec','report','correspondence','shop-drawing','quality-test','other') DEFAULT 'other',
            filename        VARCHAR(255) NOT NULL,
            original_name   VARCHAR(255) NOT NULL,
            size            INT UNSIGNED NOT NULL,
            version         VARCHAR(20)  DEFAULT '1.0',
            description     TEXT NULL,
            is_confidential TINYINT(1)   DEFAULT 0,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS documents;"); }
}
