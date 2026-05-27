<?php
class Migration_032_ShopDrawings
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_drawings (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id      INT UNSIGNED NOT NULL,
            drawing_no      VARCHAR(60)  NOT NULL,
            title           VARCHAR(200) NOT NULL,
            submitted_by    INT UNSIGNED NOT NULL,
            submitted_date  DATE NOT NULL,
            revision        VARCHAR(10)  DEFAULT 'A',
            status          ENUM('under-review','approved','rejected','resubmit') DEFAULT 'under-review',
            reviewed_by     INT UNSIGNED NULL,
            review_date     DATE NULL,
            document_path   VARCHAR(255) NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES users(id),
            FOREIGN KEY (reviewed_by)  REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS shop_drawings;"); }
}
