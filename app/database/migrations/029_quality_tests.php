<?php
class Migration_029_QualityTests
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS quality_tests (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id       INT UNSIGNED NOT NULL,
            test_type        VARCHAR(100) NOT NULL,
            test_date        DATE NOT NULL,
            location_on_site VARCHAR(200) NULL,
            result           VARCHAR(150) NULL,
            pass_fail        ENUM('pass','fail','pending') DEFAULT 'pending',
            lab_ref          VARCHAR(80) NULL,
            tested_by        INT UNSIGNED NOT NULL,
            document_path    VARCHAR(255) NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (tested_by)  REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS quality_tests;"); }
}
