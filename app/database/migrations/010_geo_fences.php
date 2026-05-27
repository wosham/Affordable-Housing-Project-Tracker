<?php
class Migration_010_GeoFences
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS geo_fences (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id     INT UNSIGNED NOT NULL,
            site_name      VARCHAR(150) NOT NULL,
            latitude       DECIMAL(10,8) NOT NULL,
            longitude      DECIMAL(11,8) NOT NULL,
            radius_meters  INT UNSIGNED DEFAULT 200,
            created_by     INT UNSIGNED NOT NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS geo_fences;"); }
}
