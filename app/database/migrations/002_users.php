<?php
class Migration_002_Users
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name    VARCHAR(80)  NOT NULL,
            last_name     VARCHAR(80)  NOT NULL,
            email         VARCHAR(160) NOT NULL UNIQUE,
            phone         VARCHAR(30)  NULL,
            password_hash VARCHAR(255) NOT NULL,
            avatar        VARCHAR(255) NULL,
            role_id       INT UNSIGNED NOT NULL,
            status        ENUM('active','inactive','suspended') DEFAULT 'active',
            assigned_projects_json TEXT NULL,
            last_login    DATETIME     NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS users;"); }
}
