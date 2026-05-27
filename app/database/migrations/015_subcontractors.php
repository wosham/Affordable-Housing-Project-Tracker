<?php
class Migration_015_Subcontractors
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS subcontractors (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id     INT UNSIGNED NOT NULL,
            company        VARCHAR(200) NOT NULL,
            scope_of_work  TEXT NULL,
            contract_value DECIMAL(15,2) NULL,
            status         VARCHAR(40) DEFAULT 'active',
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS subcontractors;"); }
}
