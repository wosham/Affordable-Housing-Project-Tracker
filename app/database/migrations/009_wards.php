<?php
class Migration_009_Wards
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS wards (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            constituency_id   INT UNSIGNED NOT NULL,
            name              VARCHAR(100) NOT NULL,
            slug              VARCHAR(100) NOT NULL,
            FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS wards;"); }
}
