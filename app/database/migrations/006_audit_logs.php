<?php
class Migration_006_AuditLogs
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id      INT UNSIGNED NULL,
            action       VARCHAR(100) NOT NULL,
            module       VARCHAR(80)  NOT NULL,
            target_id    INT UNSIGNED DEFAULT 0,
            details_json TEXT         NULL,
            ip           VARCHAR(45)  NULL,
            user_agent   VARCHAR(255) NULL,
            created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_module (module),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS audit_logs;"); }
}
