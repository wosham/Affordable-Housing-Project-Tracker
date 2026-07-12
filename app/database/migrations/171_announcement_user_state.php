<?php

class Migration171AnnouncementUserState
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS announcement_user_state (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                announcement_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                read_at DATETIME NULL,
                dismissed_at DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_announcement_user (announcement_id, user_id),
                KEY idx_aus_user_dismissed (user_id, dismissed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS announcement_user_state');
    }
}
