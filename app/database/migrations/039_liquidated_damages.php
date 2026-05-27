<?php
class Migration_039_LiquidatedDamages
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS liquidated_damages (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id       INT UNSIGNED NOT NULL,
            rate_per_day     DECIMAL(12,2) NOT NULL,
            days_overdue     SMALLINT UNSIGNED DEFAULT 0,
            total_ld         DECIMAL(15,2) DEFAULT 0,
            applied_to_ipc_id INT UNSIGNED NULL,
            notes            TEXT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)        REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (applied_to_ipc_id) REFERENCES ipcs(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS liquidated_damages;"); }
}
