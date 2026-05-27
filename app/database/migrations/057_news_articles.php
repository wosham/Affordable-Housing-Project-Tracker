<?php
class Migration_057_NewsArticles
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS news_articles (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id       INT UNSIGNED NULL,
            author_id         INT UNSIGNED NOT NULL,
            title             VARCHAR(255) NOT NULL,
            slug              VARCHAR(255) NOT NULL UNIQUE,
            excerpt           TEXT NULL,
            body              LONGTEXT NOT NULL,
            featured_image_id INT UNSIGNED NULL,
            status            ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
            published_at      DATETIME NULL,
            scheduled_for     DATETIME NULL,
            views             INT UNSIGNED DEFAULT 0,
            seo_title         VARCHAR(255) NULL,
            seo_description   TEXT NULL,
            og_image_id       INT UNSIGNED NULL,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES news_categories(id) ON DELETE SET NULL,
            FOREIGN KEY (author_id)   REFERENCES users(id),
            INDEX idx_status (status),
            INDEX idx_published (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS news_articles;"); }
}
