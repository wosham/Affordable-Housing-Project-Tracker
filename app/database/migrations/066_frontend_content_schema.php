<?php

class Migration_066_FrontendContentSchema
{
    public function up(\PDO $pdo): void
    {
        $this->changeProjectStatusEnum($pdo);

        $this->addColumn($pdo, 'projects', 'site_engineer', "VARCHAR(150) NULL AFTER lead_agency");
        $this->addColumn($pdo, 'projects', 'location_label', "VARCHAR(200) NULL AFTER slug");
        $this->addColumn($pdo, 'projects', 'current_milestone', "VARCHAR(255) NULL AFTER est_delivery");

        $this->addColumn($pdo, 'constituencies', 'population', "INT UNSIGNED NULL AFTER description");
        $this->addColumn($pdo, 'constituencies', 'avg_completion', "TINYINT UNSIGNED DEFAULT 0 AFTER total_projects");
        $this->addColumn($pdo, 'constituencies', 'status', "ENUM('planning','active','completed') DEFAULT 'planning' AFTER avg_completion");

        $this->addColumn($pdo, 'news_articles', 'read_time', "VARCHAR(50) NULL AFTER excerpt");
        $this->addColumn($pdo, 'news_articles', 'image_caption', "VARCHAR(500) NULL AFTER featured_image_id");
        $this->addColumn($pdo, 'news_articles', 'inline_image_id', "INT UNSIGNED NULL AFTER image_caption");
        $this->addColumn($pdo, 'news_articles', 'is_featured', "TINYINT(1) DEFAULT 0 AFTER status");

        $this->addColumn($pdo, 'gallery_images', 'title', "VARCHAR(255) NULL AFTER image_id");
        $this->addColumn($pdo, 'gallery_images', 'site_key', "VARCHAR(100) NULL AFTER location");
        $this->addColumn($pdo, 'gallery_images', 'year', "SMALLINT UNSIGNED NULL AFTER site_key");
        $this->addColumn($pdo, 'gallery_images', 'media_type', "ENUM('image','video') DEFAULT 'image' AFTER year");
        $this->addColumn($pdo, 'gallery_images', 'video_url', "VARCHAR(500) NULL AFTER media_type");

        $this->addColumn($pdo, 'news_categories', 'sort_order', "SMALLINT UNSIGNED DEFAULT 0 AFTER color");
        $this->addColumn($pdo, 'gallery_categories', 'sort_order', "SMALLINT UNSIGNED DEFAULT 0 AFTER project_id");

        $this->addColumn($pdo, 'stakeholders', 'category', "VARCHAR(100) NULL AFTER organisation");
        $this->addColumn($pdo, 'stakeholders', 'description', "TEXT NULL AFTER role");
        $this->addColumn($pdo, 'stakeholders', 'metadata_json', "LONGTEXT NULL AFTER website");

        $this->addColumn($pdo, 'announcements', 'type', "VARCHAR(60) DEFAULT 'info' AFTER body");
        $this->addColumn($pdo, 'announcements', 'status', "ENUM('draft','published','archived') DEFAULT 'published' AFTER type");
        $this->addColumn($pdo, 'announcements', 'published_at', "DATETIME NULL AFTER created_at");
        $this->addUniqueIndex($pdo, 'announcements', 'uq_announcements_title', 'title');

        $pdo->exec("CREATE TABLE IF NOT EXISTS navigation_links (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            area        VARCHAR(80) NOT NULL DEFAULT 'main',
            label       VARCHAR(120) NOT NULL,
            href        VARCHAR(255) NOT NULL,
            page_key    VARCHAR(80) NULL,
            is_external TINYINT(1) DEFAULT 0,
            sort_order  SMALLINT UNSIGNED DEFAULT 0,
            is_visible  TINYINT(1) DEFAULT 1,
            UNIQUE KEY uq_nav_area_href (area, href)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_departments (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(150) NOT NULL,
            role        VARCHAR(255) NULL,
            email       VARCHAR(150) NULL,
            phone       VARCHAR(50) NULL,
            sort_order  SMALLINT UNSIGNED DEFAULT 0,
            is_visible  TINYINT(1) DEFAULT 1,
            UNIQUE KEY uq_contact_department_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS contact_departments;");
        $pdo->exec("DROP TABLE IF EXISTS navigation_links;");
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function columnExists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function changeProjectStatusEnum(\PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE projects MODIFY status ENUM('planning','active','on_hold','stalled','completed','cancelled') DEFAULT 'planning'");
    }

    private function addUniqueIndex(\PDO $pdo, string $table, string $index, string $column): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD UNIQUE KEY {$index} ({$column})");
    }
}
