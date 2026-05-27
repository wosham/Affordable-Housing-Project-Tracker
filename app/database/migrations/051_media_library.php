<?php
class Migration_051_MediaLibrary
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS media_library (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename       VARCHAR(255) NOT NULL,
            original_name  VARCHAR(255) NOT NULL,
            path           VARCHAR(255) NOT NULL,
            url            VARCHAR(500) NOT NULL,
            type           VARCHAR(100) NOT NULL,
            size           INT UNSIGNED NOT NULL,
            alt_text       VARCHAR(255) NULL,
            uploaded_by    INT UNSIGNED NOT NULL,
            folder         VARCHAR(100) DEFAULT 'general',
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS media_library;"); }
}
