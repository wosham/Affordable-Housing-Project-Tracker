<?php
class Migration_037_Variations
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS variations (
            id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id           INT UNSIGNED NOT NULL,
            submitted_by         INT UNSIGNED NOT NULL,
            vo_number            SMALLINT UNSIGNED NOT NULL,
            description          TEXT NOT NULL,
            reason               TEXT NULL,
            amount               DECIMAL(15,2) DEFAULT 0,
            impact_on_time_days  SMALLINT DEFAULT 0,
            status               ENUM('pending','approved','rejected') DEFAULT 'pending',
            approved_by          INT UNSIGNED NULL,
            approved_at          DATETIME NULL,
            created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES users(id),
            FOREIGN KEY (approved_by)  REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS variations;"); }
}
