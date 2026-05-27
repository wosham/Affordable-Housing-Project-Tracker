<?php
class Migration_060_GalleryImages
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_images (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id  INT UNSIGNED NULL,
            image_id     INT UNSIGNED NULL COMMENT 'FK to media_library',
            caption      VARCHAR(255) NULL,
            taken_at     DATE NULL,
            location     VARCHAR(200) NULL,
            is_featured  TINYINT(1) DEFAULT 0,
            sort_order   SMALLINT UNSIGNED DEFAULT 0,
            FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS gallery_images;"); }
}
