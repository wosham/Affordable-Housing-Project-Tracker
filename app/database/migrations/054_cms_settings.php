<?php
class Migration_054_CmsSettings
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS cms_settings (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key`       VARCHAR(100) NOT NULL UNIQUE,
            value       LONGTEXT NULL,
            type        ENUM('text','number','boolean','json','image','color') DEFAULT 'text',
            label       VARCHAR(150) NOT NULL,
            `group`     VARCHAR(80)  NOT NULL DEFAULT 'global',
            updated_by  INT UNSIGNED NULL,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS cms_settings;"); }
}
