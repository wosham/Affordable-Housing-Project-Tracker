<?php
class Migration_012_Projects
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id      INT UNSIGNED NOT NULL,
            constituency_id  INT UNSIGNED NOT NULL,
            ward_id          INT UNSIGNED NULL,
            name             VARCHAR(200) NOT NULL,
            slug             VARCHAR(200) NOT NULL UNIQUE,
            status           ENUM('planning','active','on_hold','stalled','completed','cancelled') DEFAULT 'planning',
            pct_complete     TINYINT UNSIGNED DEFAULT 0,
            contract_sum     DECIMAL(15,2) NULL,
            start_date       DATE NULL,
            est_delivery     DATE NULL,
            contractor_id    INT UNSIGNED NULL,
            consultant_id    INT UNSIGNED NULL,
            description      TEXT NULL,
            hero_image       VARCHAR(255) NULL,
            images_json      TEXT NULL,
            funding_source   VARCHAR(150) NULL,
            lead_agency      VARCHAR(150) NULL,
            units            INT UNSIGNED NULL COMMENT 'Housing units / market stalls / bed capacity',
            is_featured      TINYINT(1) DEFAULT 0,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id)     REFERENCES project_categories(id),
            FOREIGN KEY (constituency_id) REFERENCES constituencies(id),
            INDEX idx_status (status),
            INDEX idx_featured (is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS projects;"); }
}
