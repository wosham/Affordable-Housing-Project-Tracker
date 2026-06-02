<?php

class Migration_089_GalleryPageCmsAndManager
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'gallery_images', 'thumbnail_media_id', "INT UNSIGNED NULL AFTER image_id");
        $this->addColumn($pdo, 'gallery_images', 'project_id', "INT UNSIGNED NULL AFTER thumbnail_media_id");
        $this->addColumn($pdo, 'gallery_images', 'constituency_id', "INT UNSIGNED NULL AFTER project_id");
        $this->addColumn($pdo, 'gallery_images', 'alt_text', "VARCHAR(255) NULL AFTER caption");
        $this->addColumn($pdo, 'gallery_images', 'credit', "VARCHAR(180) NULL AFTER alt_text");
        $this->addColumn($pdo, 'gallery_images', 'duration', "VARCHAR(30) NULL AFTER video_url");
        $this->addColumn($pdo, 'gallery_images', 'external_url', "VARCHAR(500) NULL AFTER duration");
        $this->addColumn($pdo, 'gallery_images', 'highlight_summary', "TEXT NULL AFTER external_url");
        $this->addColumn($pdo, 'gallery_images', 'is_highlight', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured");
        $this->addColumn($pdo, 'gallery_images', 'status', "ENUM('draft','published','hidden') NOT NULL DEFAULT 'published' AFTER is_highlight");
        $this->addColumn($pdo, 'gallery_images', 'updated_at', "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER sort_order");

        $this->addIndex($pdo, 'gallery_images', 'idx_gallery_status', 'status');
        $this->addIndex($pdo, 'gallery_images', 'idx_gallery_media_type', 'media_type');
        $this->addIndex($pdo, 'gallery_images', 'idx_gallery_site', 'site_key');
        $this->addIndex($pdo, 'gallery_images', 'idx_gallery_year', 'year');

        $pdo->exec("UPDATE gallery_images SET status = 'published' WHERE status IS NULL OR status = ''");
        $pdo->exec("UPDATE gallery_images SET is_highlight = is_featured WHERE is_highlight = 0 AND is_featured = 1");
        $pdo->exec("UPDATE gallery_images SET alt_text = COALESCE(NULLIF(alt_text, ''), title, caption)");

        $this->syncSchema($pdo);
        $this->seedCms($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'gallery' LIMIT 1")->fetchColumn();
        if ($pageId > 0) {
            $pdo->prepare("
                DELETE FROM cms_sections
                WHERE page_id = ?
                  AND section_key IN ('gallery_hero', 'gallery_highlights', 'gallery_archive', 'gallery_site_progress', 'gallery_videos', 'gallery_empty_states')
            ")->execute([$pageId]);
        }
    }

    private function seedCms(\PDO $pdo): void
    {
        $pdo->prepare("
            INSERT INTO cms_pages
                (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url, hero_image)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                template = VALUES(template),
                route_path = VALUES(route_path),
                status = VALUES(status),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                seo_keywords = VALUES(seo_keywords),
                canonical_url = VALUES(canonical_url),
                hero_image = VALUES(hero_image)
        ")->execute([
            'gallery',
            'media',
            'gallery.php',
            'published',
            'Photo Gallery | Trans-Nzoia County AHP Tracker',
            'Photo and video documentation of Trans-Nzoia Affordable Housing Programme construction progress, ceremonies, community engagements and site activity.',
            'Trans-Nzoia affordable housing gallery, AHP Kenya photos, construction progress videos',
            'https://housing.transnzoia.go.ke/gallery.php',
            'uploads/gallery/maili-tatu-2.jpg',
        ]);

        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'gallery' LIMIT 1")->fetchColumn();
        if ($pageId <= 0) {
            return;
        }

        $defaults = [
            ['gallery_hero', 'Gallery Hero', 'gallery_hero', 10, [
                'background_image' => 'uploads/gallery/maili-tatu-2.jpg',
                'background_alt' => 'Affordable housing construction site photography in Trans-Nzoia County',
                'breadcrumb_label' => 'Photo Gallery',
                'eyebrow' => 'Visual Documentation - Sites, Events & Progress',
                'title' => 'Programme in Pictures',
                'subtitle' => 'A visual record of every milestone - from groundbreaking ceremonies to community barazas, site inspections and construction progress across all five Trans-Nzoia constituencies.',
                'scroll_label' => 'Browse the gallery',
                'photos_label' => 'Photos Archived',
                'sites_label' => 'Sites Documented',
                'years_label' => 'Years of Coverage',
                'events_label' => 'Events Captured',
            ]],
            ['gallery_highlights', 'Featured Moments', 'gallery_highlights', 20, [
                'eyebrow' => 'Featured Moments',
                'title' => 'Programme Highlights',
                'subtitle' => 'Landmark moments captured - from official programme milestones to community handovers.',
                'empty_text' => 'Featured gallery moments will appear after they are marked as highlights.',
            ]],
            ['gallery_archive', 'Photo Archive', 'gallery_archive', 30, [
                'eyebrow' => 'Full Archive',
                'title' => 'Browse All Photos',
                'subtitle' => 'Filter by category, site or year to find specific documentation of the programme.',
                'category_label' => 'Category',
                'year_label' => 'Year',
                'all_label' => 'All',
                'showing_label' => 'Showing',
                'photos_label' => 'photos',
            ]],
            ['gallery_site_progress', 'Progress by Site', 'gallery_site_progress', 40, [
                'eyebrow' => 'By Constituency',
                'title' => 'Progress by Site',
                'subtitle' => 'Select a constituency to see its photos and current construction status.',
                'units_label' => 'Units Planned',
                'completion_label' => 'Completion',
                'photos_label' => 'Photos',
                'details_label' => 'View full site details',
            ]],
            ['gallery_videos', 'Progress Videos', 'gallery_videos', 50, [
                'eyebrow' => 'Video Updates',
                'title' => 'Progress Videos',
                'subtitle' => 'Watch construction progress reports, community barazas and official ceremony recordings.',
                'empty_text' => 'Progress videos will appear after they are published.',
            ]],
            ['gallery_empty_states', 'Empty States', 'gallery_empty_states', 60, [
                'no_results_text' => 'No photos match the selected filters.',
                'reset_label' => 'Clear filters',
                'lightbox_label' => 'Photo lightbox',
            ]],
        ];

        $section = $pdo->prepare("
            INSERT INTO cms_sections
                (page_id, section_key, label, section_type, editor_mode, sort_order, is_visible, is_locked, content_json)
            VALUES
                (?, ?, ?, ?, ?, ?, 1, 1, ?)
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                section_type = VALUES(section_type),
                editor_mode = VALUES(editor_mode),
                sort_order = VALUES(sort_order),
                is_visible = VALUES(is_visible),
                is_locked = VALUES(is_locked)
        ");

        foreach ($defaults as [$key, $label, $type, $sort, $content]) {
            $section->execute([$pageId, $key, $label, $type, $type, $sort, json_encode($content, JSON_UNESCAPED_SLASHES)]);
        }

        $pdo->prepare("
            DELETE FROM cms_sections
            WHERE page_id = ?
              AND section_key IN ('hero', 'highlights_intro', 'gallery_grid_intro', 'site_progress_intro', 'video_intro')
        ")->execute([$pageId]);
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $column): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
        }
    }

    private function syncSchema(\PDO $pdo): void
    {
        $schema = dirname(__DIR__) . '/ahptc_schema.sql';
        if (!is_file($schema) || !is_writable($schema)) {
            return;
        }

        $sql = file_get_contents($schema);
        if ($sql === false) {
            return;
        }

        $categories = <<<'SQL'
CREATE TABLE IF NOT EXISTS `gallery_categories` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL UNIQUE,
    `project_id` INT UNSIGNED NULL,
    `sort_order` SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $images = <<<'SQL'
CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NULL,
    `image_id`    INT UNSIGNED NULL COMMENT 'FK to media_library',
    `thumbnail_media_id` INT UNSIGNED NULL,
    `project_id`   INT UNSIGNED NULL,
    `constituency_id` INT UNSIGNED NULL,
    `title`       VARCHAR(180) NULL,
    `caption`     VARCHAR(255) NULL,
    `alt_text`    VARCHAR(255) NULL,
    `credit`      VARCHAR(180) NULL,
    `taken_at`    DATE NULL,
    `location`    VARCHAR(200) NULL,
    `site_key`    VARCHAR(120) NULL,
    `year`        SMALLINT UNSIGNED NULL,
    `media_type`  ENUM('image','video') NOT NULL DEFAULT 'image',
    `video_url`   VARCHAR(500) NULL,
    `duration`    VARCHAR(30) NULL,
    `external_url` VARCHAR(500) NULL,
    `highlight_summary` TEXT NULL,
    `is_featured` TINYINT(1)   DEFAULT 0,
    `is_highlight` TINYINT(1) NOT NULL DEFAULT 0,
    `status`       ENUM('draft','published','hidden') NOT NULL DEFAULT 'published',
    `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $sql = preg_replace(
            '/CREATE TABLE IF NOT EXISTS `gallery_categories` \([\s\S]*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/',
            $categories,
            $sql,
            1
        ) ?? $sql;

        $sql = preg_replace(
            '/CREATE TABLE IF NOT EXISTS `gallery_images` \([\s\S]*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/',
            $images,
            $sql,
            1
        ) ?? $sql;

        file_put_contents($schema, $sql);
    }
}
