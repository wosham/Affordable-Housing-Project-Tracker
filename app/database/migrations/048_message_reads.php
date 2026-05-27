<?php
class Migration_048_MessageReads
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS message_reads (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id  INT UNSIGNED NOT NULL,
            user_id     INT UNSIGNED NOT NULL,
            read_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_msg_user (message_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS message_reads;"); }
}
