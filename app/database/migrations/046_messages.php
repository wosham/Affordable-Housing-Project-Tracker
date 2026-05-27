<?php
class Migration_046_Messages
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id   INT UNSIGNED NOT NULL,
            sender_id   INT UNSIGNED NOT NULL,
            body        TEXT NOT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            edited_at   DATETIME NULL,
            is_deleted  TINYINT(1) DEFAULT 0,
            FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS messages;"); }
}
