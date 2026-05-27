<?php
class Migration_001_Roles
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(60)  NOT NULL,
            slug        VARCHAR(60)  NOT NULL UNIQUE,
            color       VARCHAR(20)  NOT NULL DEFAULT '#163300',
            permissions_json TEXT    NULL,
            created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS roles;"); }
}
