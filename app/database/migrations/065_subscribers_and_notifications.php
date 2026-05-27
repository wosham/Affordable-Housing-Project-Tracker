<?php
class Migration_065_SubscribersAndNotifications
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email          VARCHAR(160) NOT NULL UNIQUE,
            name           VARCHAR(150) NULL,
            status         ENUM('active','unsubscribed') DEFAULT 'active',
            subscribed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip             VARCHAR(45) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id     INT UNSIGNED NOT NULL,
            type        VARCHAR(80)  NOT NULL,
            title       VARCHAR(255) NOT NULL,
            body        TEXT NULL,
            link        VARCHAR(500) NULL,
            is_read     TINYINT(1)   DEFAULT 0,
            created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS notifications;");
        $pdo->exec("DROP TABLE IF EXISTS subscribers;");
    }
}
