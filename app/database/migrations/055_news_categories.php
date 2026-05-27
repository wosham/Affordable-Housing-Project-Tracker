<?php
class Migration_055_NewsCategories
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS news_categories (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL,
            slug        VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            color       VARCHAR(20)  DEFAULT '#163300'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS news_categories;"); }
}
