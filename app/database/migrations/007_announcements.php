<?php
class Migration_007_Announcements
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id         INT UNSIGNED NOT NULL,
            title             VARCHAR(255) NOT NULL,
            body              TEXT         NOT NULL,
            target_roles_json TEXT         NULL,
            is_pinned         TINYINT(1)   DEFAULT 0,
            created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            expires_at        DATETIME     NULL,
            FOREIGN KEY (author_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS announcements;"); }
}
