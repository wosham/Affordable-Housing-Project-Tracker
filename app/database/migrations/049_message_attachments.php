<?php
class Migration_049_MessageAttachments
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS message_attachments (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id     INT UNSIGNED NOT NULL,
            filename       VARCHAR(255) NOT NULL,
            original_name  VARCHAR(255) NOT NULL,
            size           INT UNSIGNED NOT NULL,
            type           VARCHAR(100) NOT NULL,
            path           VARCHAR(255) NOT NULL,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS message_attachments;"); }
}
