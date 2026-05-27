<?php
class Migration_004_UserSessions
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id       INT UNSIGNED NOT NULL,
            token         VARCHAR(255) NOT NULL UNIQUE,
            ip            VARCHAR(45)  NULL,
            last_activity DATETIME     NOT NULL,
            expires_at    DATETIME     NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS user_sessions;"); }
}
