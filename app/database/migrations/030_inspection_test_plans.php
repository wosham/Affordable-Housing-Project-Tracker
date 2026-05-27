<?php
class Migration_030_InspectionTestPlans
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS inspection_test_plans (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id        INT UNSIGNED NOT NULL,
            activity          VARCHAR(200) NOT NULL,
            hold_point        VARCHAR(100) NULL,
            inspection_date   DATE NULL,
            inspected_by      INT UNSIGNED NULL,
            outcome           VARCHAR(100) NULL,
            witness_required  TINYINT(1) DEFAULT 0,
            document_path     VARCHAR(255) NULL,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (inspected_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS inspection_test_plans;"); }
}
