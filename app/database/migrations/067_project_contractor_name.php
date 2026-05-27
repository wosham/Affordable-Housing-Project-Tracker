<?php

class Migration_067_ProjectContractorName
{
    public function up(\PDO $pdo): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'projects'
              AND COLUMN_NAME = 'contractor_name'
        ");
        $stmt->execute();

        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN contractor_name VARCHAR(180) NULL AFTER current_milestone");
        }
    }

    public function down(\PDO $pdo): void
    {
    }
}
