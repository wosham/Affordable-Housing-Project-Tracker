<?php
class Migration_019_EquipmentRegister
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS equipment_register (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id     INT UNSIGNED NOT NULL,
            equipment_type VARCHAR(120) NOT NULL,
            registration   VARCHAR(60)  NULL,
            owner          VARCHAR(150) NULL,
            date_on_site   DATE NULL,
            date_off_site  DATE NULL,
            `condition`    VARCHAR(60)  DEFAULT 'good',
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS equipment_register;"); }
}
