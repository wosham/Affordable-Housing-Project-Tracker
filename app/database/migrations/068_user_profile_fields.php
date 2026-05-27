<?php

class Migration_068_UserProfileFields
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'users', 'job_title', "VARCHAR(150) NULL AFTER phone");
        $this->addColumn($pdo, 'users', 'department', "VARCHAR(150) NULL AFTER job_title");
        $this->addColumn($pdo, 'users', 'bio', "TEXT NULL AFTER department");
        $this->addColumn($pdo, 'users', 'is_public', "TINYINT(1) DEFAULT 0 AFTER avatar");
    }

    public function down(\PDO $pdo): void
    {
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}
