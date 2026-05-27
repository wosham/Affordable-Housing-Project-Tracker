<?php
class Migration_042_RFIs
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS rfis (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id     INT UNSIGNED NOT NULL,
            raised_by      INT UNSIGNED NOT NULL,
            rfi_number     SMALLINT UNSIGNED NOT NULL,
            subject        VARCHAR(200) NOT NULL,
            description    TEXT NOT NULL,
            urgency        ENUM('low','normal','urgent') DEFAULT 'normal',
            responded_by   INT UNSIGNED NULL,
            response       TEXT NULL,
            raised_date    DATE NOT NULL,
            response_date  DATE NULL,
            status         ENUM('open','answered','closed') DEFAULT 'open',
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (raised_by)    REFERENCES users(id),
            FOREIGN KEY (responded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS rfis;"); }
}
