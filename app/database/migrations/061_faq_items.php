<?php
class Migration_061_FAQItems
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS faq_items (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            question    TEXT NOT NULL,
            answer      LONGTEXT NOT NULL,
            category    VARCHAR(100) NULL,
            sort_order  SMALLINT UNSIGNED DEFAULT 0,
            is_visible  TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS faq_items;"); }
}
