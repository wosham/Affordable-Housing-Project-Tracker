<?php
class Migration_020_MaterialDeliveries
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS material_deliveries (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id       INT UNSIGNED NOT NULL,
            material         VARCHAR(150) NOT NULL,
            supplier         VARCHAR(150) NULL,
            delivery_date    DATE NOT NULL,
            quantity         DECIMAL(12,3) NOT NULL,
            unit             VARCHAR(30) NOT NULL,
            delivery_note_no VARCHAR(60) NULL,
            received_by      INT UNSIGNED NOT NULL,
            `condition`      VARCHAR(60) DEFAULT 'good',
            approved         TINYINT(1) DEFAULT 0,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (received_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS material_deliveries;"); }
}
