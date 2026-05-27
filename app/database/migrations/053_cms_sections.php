<?php
class Migration_053_CmsSections
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS cms_sections (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id       INT UNSIGNED NOT NULL,
            section_key   VARCHAR(80)  NOT NULL,
            label         VARCHAR(150) NOT NULL,
            is_visible    TINYINT(1)   DEFAULT 1,
            content_json  LONGTEXT     NULL,
            updated_by    INT UNSIGNED NULL,
            FOREIGN KEY (page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
            UNIQUE KEY uq_page_section (page_id, section_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS cms_sections;"); }
}
