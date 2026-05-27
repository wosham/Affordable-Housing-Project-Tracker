<?php
class Migration_035_IPCLines
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ipc_lines (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ipc_id          INT UNSIGNED NOT NULL,
            boq_item_id     INT UNSIGNED NULL,
            description     TEXT NOT NULL,
            qty_this_period DECIMAL(12,3) DEFAULT 0,
            cumulative_qty  DECIMAL(12,3) DEFAULT 0,
            rate            DECIMAL(12,2) DEFAULT 0,
            amount          DECIMAL(15,2) DEFAULT 0,
            FOREIGN KEY (ipc_id)      REFERENCES ipcs(id)      ON DELETE CASCADE,
            FOREIGN KEY (boq_item_id) REFERENCES boq_items(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS ipc_lines;"); }
}
