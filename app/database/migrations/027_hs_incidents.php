<?php
class Migration_027_HSIncidents
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hs_incidents (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id          INT UNSIGNED NOT NULL,
            incident_date       DATE NOT NULL,
            incident_type       ENUM('near-miss','first-aid','medical','fatality') NOT NULL,
            description         TEXT NOT NULL,
            persons_involved    TEXT NULL,
            cause               TEXT NULL,
            corrective_action   TEXT NULL,
            reported_by         INT UNSIGNED NOT NULL,
            severity            ENUM('low','medium','high','critical') DEFAULT 'medium',
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS hs_incidents;"); }
}
