<?php
class Migration_031_NonConformanceReports
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS non_conformance_reports (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id        INT UNSIGNED NOT NULL,
            raised_by         INT UNSIGNED NOT NULL,
            raised_date       DATE NOT NULL,
            description       TEXT NOT NULL,
            severity          ENUM('minor','major','critical') DEFAULT 'minor',
            root_cause        TEXT NULL,
            corrective_action TEXT NULL,
            closed_by         INT UNSIGNED NULL,
            closed_date       DATE NULL,
            status            ENUM('open','in-progress','closed') DEFAULT 'open',
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (raised_by)  REFERENCES users(id),
            FOREIGN KEY (closed_by)  REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS non_conformance_reports;"); }
}
