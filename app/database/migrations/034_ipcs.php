<?php
class Migration_034_IPCs
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ipcs (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id        INT UNSIGNED NOT NULL,
            contractor_id     INT UNSIGNED NOT NULL,
            ipc_number        SMALLINT UNSIGNED NOT NULL,
            period_from       DATE NOT NULL,
            period_to         DATE NOT NULL,
            gross_amount      DECIMAL(15,2) DEFAULT 0,
            retention_amount  DECIMAL(15,2) DEFAULT 0,
            net_amount        DECIMAL(15,2) DEFAULT 0,
            status            ENUM('draft','submitted','clerk-endorsed','certified','approved','paid') DEFAULT 'draft',
            submitted_at      DATETIME NULL,
            certified_at      DATETIME NULL,
            approved_at       DATETIME NULL,
            paid_at           DATETIME NULL,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)    REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (contractor_id) REFERENCES users(id),
            UNIQUE KEY uq_project_ipc_no (project_id, ipc_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS ipcs;"); }
}
