<?php
class Migration_038_EOTRequests
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS eot_requests (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id          INT UNSIGNED NOT NULL,
            submitted_by        INT UNSIGNED NOT NULL,
            eot_number          SMALLINT UNSIGNED NOT NULL,
            days_requested      SMALLINT UNSIGNED NOT NULL,
            reason              TEXT NOT NULL,
            supporting_evidence VARCHAR(255) NULL,
            status              ENUM('pending','granted','partially-granted','rejected') DEFAULT 'pending',
            granted_days        SMALLINT UNSIGNED NULL,
            approved_by         INT UNSIGNED NULL,
            approved_at         DATETIME NULL,
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES users(id),
            FOREIGN KEY (approved_by)  REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS eot_requests;"); }
}
