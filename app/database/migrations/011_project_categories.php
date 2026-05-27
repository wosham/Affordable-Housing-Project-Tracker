<?php
class Migration_011_ProjectCategories
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_categories (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100) NOT NULL,
            slug       VARCHAR(100) NOT NULL UNIQUE,
            icon       VARCHAR(80)  NULL,
            color      VARCHAR(20)  NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS project_categories;"); }
}
