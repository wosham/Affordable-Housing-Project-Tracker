<?php
class Migration_017_BOQItems
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS boq_items (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id    INT UNSIGNED NOT NULL,
            section       VARCHAR(150) NOT NULL,
            item_no       VARCHAR(20)  NOT NULL,
            description   TEXT         NOT NULL,
            unit          VARCHAR(30)  NOT NULL,
            quantity      DECIMAL(12,3) DEFAULT 0,
            rate          DECIMAL(12,2) DEFAULT 0,
            amount        DECIMAL(15,2) DEFAULT 0,
            certified_qty DECIMAL(12,3) DEFAULT 0,
            paid_qty      DECIMAL(12,3) DEFAULT 0,
            status        VARCHAR(40)  DEFAULT 'active',
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS boq_items;"); }
}
