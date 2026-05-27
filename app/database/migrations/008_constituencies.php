<?php
class Migration_008_Constituencies
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS constituencies (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name            VARCHAR(100) NOT NULL,
            slug            VARCHAR(100) NOT NULL UNIQUE,
            mp              VARCHAR(120) NULL,
            mp_photo        VARCHAR(255) NULL,
            description     TEXT         NULL,
            total_units     INT UNSIGNED DEFAULT 0,
            total_projects  INT UNSIGNED DEFAULT 0,
            hero_image      VARCHAR(255) NULL,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS constituencies;"); }
}
