<?php
class Migration_022_LabourRegister
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS labour_register (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id        INT UNSIGNED NOT NULL,
            diary_date        DATE NOT NULL,
            skilled_count     SMALLINT UNSIGNED DEFAULT 0,
            unskilled_count   SMALLINT UNSIGNED DEFAULT 0,
            supervisor_count  SMALLINT UNSIGNED DEFAULT 0,
            total             SMALLINT UNSIGNED DEFAULT 0,
            recorded_by       INT UNSIGNED NOT NULL,
            FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id),
            UNIQUE KEY uq_project_date (project_id, diary_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    public function down(\PDO $pdo): void { $pdo->exec("DROP TABLE IF EXISTS labour_register;"); }
}
