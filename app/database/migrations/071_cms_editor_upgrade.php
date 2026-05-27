<?php

class Migration_071_CmsEditorUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'cms_pages', 'template', "VARCHAR(80) NULL AFTER slug");
        $this->addColumn($pdo, 'cms_pages', 'route_path', "VARCHAR(255) NULL AFTER template");
        $this->addColumn($pdo, 'cms_pages', 'hero_image', "VARCHAR(255) NULL AFTER canonical_url");

        $this->addColumn($pdo, 'cms_sections', 'section_type', "VARCHAR(60) DEFAULT 'rich_text' AFTER label");
        $this->addColumn($pdo, 'cms_sections', 'sort_order', "SMALLINT UNSIGNED DEFAULT 0 AFTER section_type");
        $this->addColumn($pdo, 'cms_sections', 'editor_mode', "VARCHAR(40) DEFAULT 'structured' AFTER sort_order");
        $this->addColumn($pdo, 'cms_sections', 'is_locked', "TINYINT(1) DEFAULT 0 AFTER is_visible");
        $this->addColumn($pdo, 'cms_sections', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by");

        $pdo->exec("CREATE TABLE IF NOT EXISTS cms_revisions (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id        INT UNSIGNED NOT NULL,
            section_id     INT UNSIGNED NULL,
            revision_type  ENUM('page','section','setting') DEFAULT 'section',
            target_key     VARCHAR(120) NULL,
            snapshot_json  LONGTEXT NOT NULL,
            created_by     INT UNSIGNED NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (section_id) REFERENCES cms_sections(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_cms_revisions_page_created (page_id, created_at),
            INDEX idx_cms_revisions_target (revision_type, target_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->seedPageRoutes($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS cms_revisions;");
    }

    private function seedPageRoutes(\PDO $pdo): void
    {
        $pages = [
            ['home', 'landing', 'index.php'],
            ['about', 'content', 'about.php'],
            ['projects', 'listing', 'projects.php'],
            ['project-detail', 'template', 'project-detail.php'],
            ['constituencies', 'listing', 'constituencies.php'],
            ['constituency-detail', 'template', 'constituency-detail.php'],
            ['news', 'listing', 'news.php'],
            ['news-article', 'template', 'news-article.php'],
            ['gallery', 'media', 'gallery.php'],
            ['faq', 'content', 'faq.php'],
            ['leadership', 'content', 'leadership.php'],
            ['stakeholders', 'content', 'stakeholders.php'],
            ['contact', 'contact', 'contact.php'],
            ['privacy', 'legal', 'legal/privacy.php'],
            ['terms', 'legal', 'legal/terms.php'],
            ['disclaimer', 'legal', 'legal/disclaimer.php'],
            ['not-found', 'system', '404.php'],
            ['server-error', 'system', '500.php'],
            ['maintenance', 'system', 'maintenance.php'],
            ['offline', 'system', 'offline.php'],
            ['sitemap', 'system', 'sitemap.php'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO cms_pages (slug, template, route_path, status, seo_title)
             VALUES (?, ?, ?, "published", ?)
             ON DUPLICATE KEY UPDATE template = VALUES(template), route_path = VALUES(route_path)'
        );

        foreach ($pages as [$slug, $template, $route]) {
            $stmt->execute([$slug, $template, $route, ucwords(str_replace('-', ' ', $slug)) . ' | AHPTC']);
        }
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
}
