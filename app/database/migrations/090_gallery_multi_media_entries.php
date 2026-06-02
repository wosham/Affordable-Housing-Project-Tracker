<?php

class Migration_090_GalleryMultiMediaEntries
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gallery_media (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                gallery_id INT UNSIGNED NOT NULL,
                media_id INT UNSIGNED NULL,
                thumbnail_media_id INT UNSIGNED NULL,
                media_type ENUM('image','video') NOT NULL DEFAULT 'image',
                video_url VARCHAR(500) NULL,
                caption VARCHAR(255) NULL,
                alt_text VARCHAR(255) NULL,
                sort_order SMALLINT UNSIGNED DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_gallery_media_gallery (gallery_id),
                INDEX idx_gallery_media_type (media_type),
                INDEX idx_gallery_media_media (media_id),
                CONSTRAINT fk_gallery_media_gallery FOREIGN KEY (gallery_id) REFERENCES gallery_images(id) ON DELETE CASCADE,
                CONSTRAINT fk_gallery_media_media FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE SET NULL,
                CONSTRAINT fk_gallery_media_thumb FOREIGN KEY (thumbnail_media_id) REFERENCES media_library(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $rows = $pdo->query("
            SELECT id, image_id, thumbnail_media_id, media_type, video_url, caption, alt_text, sort_order
            FROM gallery_images
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $insert = $pdo->prepare("
            INSERT INTO gallery_media
                (gallery_id, media_id, thumbnail_media_id, media_type, video_url, caption, alt_text, sort_order)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            $exists = $pdo->prepare('SELECT id FROM gallery_media WHERE gallery_id = ? LIMIT 1');
            $exists->execute([(int)$row['id']]);
            if ($exists->fetchColumn()) {
                continue;
            }

            if (empty($row['image_id']) && empty($row['video_url'])) {
                continue;
            }

            $insert->execute([
                (int)$row['id'],
                !empty($row['image_id']) ? (int)$row['image_id'] : null,
                !empty($row['thumbnail_media_id']) ? (int)$row['thumbnail_media_id'] : null,
                (string)($row['media_type'] ?: 'image'),
                $row['video_url'] ?: null,
                $row['caption'] ?: null,
                $row['alt_text'] ?: null,
                (int)($row['sort_order'] ?? 0),
            ]);
        }

        $this->syncSchema();
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS gallery_media');
    }

    private function syncSchema(): void
    {
        $schema = dirname(__DIR__) . '/ahptc_schema.sql';
        if (!is_file($schema) || !is_writable($schema)) {
            return;
        }

        $sql = file_get_contents($schema);
        if ($sql === false || str_contains($sql, 'CREATE TABLE IF NOT EXISTS `gallery_media`')) {
            return;
        }

        $media = <<<'SQL'

-- ============================================================
-- 60B. GALLERY MEDIA ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery_media` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `gallery_id` INT UNSIGNED NOT NULL,
    `media_id`   INT UNSIGNED NULL,
    `thumbnail_media_id` INT UNSIGNED NULL,
    `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
    `video_url`  VARCHAR(500) NULL,
    `caption`    VARCHAR(255) NULL,
    `alt_text`   VARCHAR(255) NULL,
    `sort_order` SMALLINT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`gallery_id`) REFERENCES `gallery_images`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_id`) REFERENCES `media_library`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`thumbnail_media_id`) REFERENCES `media_library`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $needle = "-- ============================================================\n-- 61. FAQ CATEGORIES + ITEMS";
        $sql = str_replace($needle, $media . "\n\n" . $needle, $sql);
        file_put_contents($schema, $sql);
    }
}
