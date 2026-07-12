<?php

class Migration143ContractorFormalSubmissionsHardening
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'rfis', 'updated_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        $this->addColumn($pdo, 'material_approvals', 'updated_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER notes");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contractor_submission_attachments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                submission_type ENUM('eot','variation','rfi','shop_drawing','material') NOT NULL,
                submission_id INT UNSIGNED NOT NULL,
                project_id INT UNSIGNED NOT NULL,
                media_id INT UNSIGNED NULL,
                path VARCHAR(255) NULL,
                title VARCHAR(180) NULL,
                uploaded_by INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_csa_submission (submission_type, submission_id),
                INDEX idx_csa_project (project_id, submission_type, created_at),
                INDEX idx_csa_media (media_id),
                INDEX idx_csa_user (uploaded_by, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->addForeignKey(
            $pdo,
            'contractor_submission_attachments',
            'fk_csa_project',
            'FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE'
        );
        $this->addForeignKey(
            $pdo,
            'contractor_submission_attachments',
            'fk_csa_media',
            'FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE SET NULL'
        );
        $this->addForeignKey(
            $pdo,
            'contractor_submission_attachments',
            'fk_csa_user',
            'FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE'
        );
    }

    public function down(PDO $pdo): void
    {
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        $stmt->execute([$table, $column]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addForeignKey(PDO $pdo, string $table, string $name, string $definition): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1');
        $stmt->execute([$table, $name]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT {$name} {$definition}");
        }
    }
}
