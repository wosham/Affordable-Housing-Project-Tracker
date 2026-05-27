<?php
class Migration_040_Payments
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ipc_id          INT UNSIGNED NOT NULL,
            project_id      INT UNSIGNED NOT NULL,
            amount          DECIMAL(15,2) NOT NULL,
            payment_date    DATE NOT NULL,
            reference_no    VARCHAR(100) NULL,
            bank            VARCHAR(150) NULL,
            processed_by    INT UNSIGNED NOT NULL,
            receipt_path    VARCHAR(255) NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ipc_id)       REFERENCES ipcs(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS payments;"); }
}
