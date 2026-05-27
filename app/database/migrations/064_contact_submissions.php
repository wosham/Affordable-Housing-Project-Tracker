<?php
class Migration_064_ContactSubmissions
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name         VARCHAR(150) NOT NULL,
            email        VARCHAR(160) NOT NULL,
            phone        VARCHAR(30)  NULL,
            subject      VARCHAR(200) NULL,
            message      TEXT NOT NULL,
            is_read      TINYINT(1) DEFAULT 0,
            assigned_to  INT UNSIGNED NULL,
            replied_at   DATETIME NULL,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS contact_submissions;"); }
}
