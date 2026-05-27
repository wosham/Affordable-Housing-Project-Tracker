<?php
class Migration_021_MaterialApprovals
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS material_approvals (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id      INT UNSIGNED NOT NULL,
            material        VARCHAR(150) NOT NULL,
            specification   TEXT NULL,
            submitted_by    INT UNSIGNED NOT NULL,
            submitted_date  DATE NOT NULL,
            approved_by     INT UNSIGNED NULL,
            approved_date   DATE NULL,
            status          ENUM('pending','approved','rejected') DEFAULT 'pending',
            notes           TEXT NULL,
            FOREIGN KEY (project_id)   REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES users(id),
            FOREIGN KEY (approved_by)  REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS material_approvals;"); }
}
