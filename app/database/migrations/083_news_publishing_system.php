<?php

class Migration_083_NewsPublishingSystem
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'news_articles', 'post_format', "VARCHAR(40) NOT NULL DEFAULT 'article' AFTER id");
        $this->addColumn($pdo, 'news_articles', 'attachment_id', "INT UNSIGNED NULL AFTER inline_image_id");
        $this->addColumn($pdo, 'news_articles', 'external_url', "VARCHAR(500) NULL AFTER attachment_id");
        $this->addColumn($pdo, 'news_articles', 'is_visible', "TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured");
        $this->addColumn($pdo, 'news_articles', 'metadata_json', "LONGTEXT NULL AFTER og_image_id");
        $this->addColumn($pdo, 'news_articles', 'deleted_at', "DATETIME NULL AFTER updated_at");

        $this->addIndex($pdo, 'news_articles', 'idx_news_format', 'post_format');
        $this->addIndex($pdo, 'news_articles', 'idx_news_visibility', 'is_visible');
        $this->addIndex($pdo, 'news_articles', 'idx_news_deleted', 'deleted_at');

        $this->seedCategories($pdo);
        $this->backfillArticleFormats($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE news_articles DROP INDEX idx_news_format");
        $pdo->exec("ALTER TABLE news_articles DROP INDEX idx_news_visibility");
        $pdo->exec("ALTER TABLE news_articles DROP INDEX idx_news_deleted");
    }

    private function seedCategories(\PDO $pdo): void
    {
        $categories = [
            ['Programme Updates', 'programme-updates', '#7ee35f', 10],
            ['Groundbreaking', 'groundbreaking', '#f5b21a', 20],
            ['Construction', 'construction', '#4f8cff', 30],
            ['Policy', 'policy', '#a55cff', 40],
            ['Community', 'community', '#25c9a6', 50],
            ['Official', 'official', '#6b7280', 60],
            ['Field Reports', 'field-reports', '#f58a2a', 70],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO news_categories (name, slug, color, sort_order)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color), sort_order = VALUES(sort_order)
        ");

        foreach ($categories as $category) {
            $stmt->execute($category);
        }
    }

    private function backfillArticleFormats(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE news_articles na
            LEFT JOIN news_categories nc ON nc.id = na.category_id
            SET na.post_format = CASE
                WHEN nc.slug = 'field-reports' THEN 'field_report'
                WHEN nc.slug = 'policy' THEN 'policy'
                WHEN nc.slug = 'official' THEN 'announcement'
                WHEN nc.slug = 'construction' THEN 'progress_report'
                ELSE 'article'
            END
            WHERE na.post_format IS NULL OR na.post_format = '' OR na.post_format = 'article'
        ");
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $columns): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
        }
    }
}
