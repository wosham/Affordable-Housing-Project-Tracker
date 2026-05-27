<?php
class Migration_062_LeadershipProfiles
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS leadership_profiles (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name              VARCHAR(150) NOT NULL,
            title             VARCHAR(150) NOT NULL,
            organisation      VARCHAR(200) NULL,
            photo_id          INT UNSIGNED NULL,
            bio               TEXT NULL,
            sort_order        SMALLINT UNSIGNED DEFAULT 0,
            is_visible        TINYINT(1) DEFAULT 1,
            social_links_json TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS leadership_profiles;"); }
}
