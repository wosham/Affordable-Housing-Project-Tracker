<?php
class Migration_025_SiteMeetingMinutes
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_meeting_minutes (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id       INT UNSIGNED NOT NULL,
            meeting_date     DATE NOT NULL,
            venue            VARCHAR(200) NULL,
            attendees_json   TEXT NULL,
            agenda           TEXT NULL,
            minutes_text     LONGTEXT NULL,
            action_items_json TEXT NULL,
            document_path    VARCHAR(255) NULL,
            recorded_by      INT UNSIGNED NOT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS site_meeting_minutes;"); }
}
