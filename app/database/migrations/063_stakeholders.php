<?php
class Migration_063_Stakeholders
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS stakeholders (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organisation  VARCHAR(200) NOT NULL,
            role          VARCHAR(150) NULL,
            logo_id       INT UNSIGNED NULL,
            website       VARCHAR(255) NULL,
            sort_order    SMALLINT UNSIGNED DEFAULT 0,
            is_visible    TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS stakeholders;"); }
}
