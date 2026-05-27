<?php
class Migration_036_IPCApprovals
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ipc_approvals (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ipc_id       INT UNSIGNED NOT NULL,
            step         TINYINT UNSIGNED NOT NULL COMMENT '1=Clerk,2=Consultant,3=Manager,4=Director',
            action_by    INT UNSIGNED NOT NULL,
            action       ENUM('endorsed','certified','approved','rejected') NOT NULL,
            comments     TEXT NULL,
            actioned_at  DATETIME NOT NULL,
            FOREIGN KEY (ipc_id)    REFERENCES ipcs(id) ON DELETE CASCADE,
            FOREIGN KEY (action_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS ipc_approvals;"); }
}
