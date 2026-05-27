<?php
class Migration_047_MessageParticipants
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS message_participants (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id   INT UNSIGNED NOT NULL,
            user_id     INT UNSIGNED NOT NULL,
            joined_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_admin    TINYINT(1) DEFAULT 0,
            FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_thread_user (thread_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS message_participants;"); }
}
