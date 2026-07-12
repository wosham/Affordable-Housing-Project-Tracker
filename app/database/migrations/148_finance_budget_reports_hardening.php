<?php

class Migration148FinanceBudgetReportsHardening
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS retention_release_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                retention_id INT UNSIGNED NOT NULL,
                project_id INT UNSIGNED NOT NULL,
                ipc_id INT UNSIGNED NULL,
                released_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
                balance_after DECIMAL(15,2) NOT NULL DEFAULT 0,
                release_date DATE NOT NULL,
                reason TEXT NULL,
                processed_by INT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_rrh_retention_created (retention_id, created_at),
                INDEX idx_rrh_project_date (project_id, release_date),
                CONSTRAINT fk_rrh_retention FOREIGN KEY (retention_id) REFERENCES retention(id) ON DELETE CASCADE,
                CONSTRAINT fk_rrh_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                CONSTRAINT fk_rrh_ipc FOREIGN KEY (ipc_id) REFERENCES ipcs(id) ON DELETE SET NULL,
                CONSTRAINT fk_rrh_user FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->addIndex($pdo, 'payments', 'idx_payments_project_status_date', 'project_id, status, payment_date');
        $this->addIndex($pdo, 'retention', 'idx_retention_project_status_release', 'project_id, status, release_date');
        $this->addIndex($pdo, 'liquidated_damages', 'idx_ld_status_updated', 'status, updated_at');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_status_payment_approved', 'status, payment_status, approved_at');
    }

    public function down(PDO $pdo): void
    {
    }

    private function addIndex(PDO $pdo, string $table, string $name, string $columns): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
        $stmt->execute([$table, $name]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$name} ({$columns})");
        }
    }
}
