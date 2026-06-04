<?php

class Migration142ContractorIpcSubmission
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'ipcs', 'contractor_reference', "VARCHAR(100) NULL AFTER ipc_number");
        $this->addColumn($pdo, 'ipcs', 'declaration_accepted', "TINYINT(1) NOT NULL DEFAULT 0 AFTER net_amount");
        $this->addColumn($pdo, 'ipcs', 'current_stage', "VARCHAR(60) NOT NULL DEFAULT 'contractor_submission' AFTER status");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ipc_attachments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ipc_id INT UNSIGNED NOT NULL,
                media_id INT UNSIGNED NULL,
                path VARCHAR(255) NULL,
                title VARCHAR(180) NULL,
                uploaded_by INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ipc_attachments_ipc (ipc_id),
                INDEX idx_ipc_attachments_media (media_id),
                INDEX idx_ipc_attachments_user (uploaded_by, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
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

}
