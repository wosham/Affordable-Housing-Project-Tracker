<?php
class Migration_052_CmsPages
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS cms_pages (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug             VARCHAR(100) NOT NULL UNIQUE,
            status           ENUM('published','draft','maintenance','hidden') DEFAULT 'published',
            seo_title        VARCHAR(255) NULL,
            seo_description  TEXT NULL,
            seo_keywords     TEXT NULL,
            og_image_id      INT UNSIGNED NULL,
            canonical_url    VARCHAR(500) NULL,
            updated_by       INT UNSIGNED NULL,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS cms_pages;"); }
}
