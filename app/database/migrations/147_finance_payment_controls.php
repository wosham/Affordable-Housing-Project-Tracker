<?php

class Migration147FinancePaymentControls
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'payments', 'status', "ENUM('processed','void','reversed') NOT NULL DEFAULT 'processed' AFTER receipt_path");
        $this->addColumn($pdo, 'payments', 'payment_method', "VARCHAR(60) NOT NULL DEFAULT 'bank_transfer' AFTER bank");
        $this->addColumn($pdo, 'payments', 'voucher_no', 'VARCHAR(100) NULL AFTER reference_no');
        $this->addColumn($pdo, 'payments', 'notes', 'TEXT NULL AFTER status');
        $this->addColumn($pdo, 'payments', 'processed_at', 'DATETIME NULL AFTER processed_by');
        $this->addColumn($pdo, 'payments', 'voided_by', 'INT UNSIGNED NULL AFTER notes');
        $this->addColumn($pdo, 'payments', 'voided_at', 'DATETIME NULL AFTER voided_by');
        $this->addColumn($pdo, 'payments', 'void_reason', 'TEXT NULL AFTER voided_at');

        $this->addColumn($pdo, 'ipcs', 'paid_by', 'INT UNSIGNED NULL AFTER paid_at');
        $this->addColumn($pdo, 'ipcs', 'payment_status', "VARCHAR(30) NOT NULL DEFAULT 'unpaid' AFTER paid_by");
        $this->addColumn($pdo, 'ipcs', 'payment_reference', 'VARCHAR(100) NULL AFTER payment_status');
        $this->addColumn($pdo, 'ipcs', 'payment_comment', 'TEXT NULL AFTER payment_reference');

        $this->addColumn($pdo, 'retention', 'ipc_id', 'INT UNSIGNED NULL AFTER project_id');
        $this->addColumn($pdo, 'retention', 'status', "VARCHAR(30) NOT NULL DEFAULT 'held' AFTER release_reason");
        $this->addColumn($pdo, 'retention', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $this->addIndex($pdo, 'payments', 'idx_payments_ipc_status', 'ipc_id, status');
        $this->addIndex($pdo, 'payments', 'idx_payments_reference', 'reference_no');
        $this->addIndex($pdo, 'payments', 'idx_payments_status_date', 'status, payment_date');
        $this->addIndex($pdo, 'ipcs', 'idx_ipcs_payment_status', 'payment_status, paid_at');
        $this->addIndex($pdo, 'retention', 'idx_retention_ipc', 'ipc_id');
        $this->addIndex($pdo, 'retention', 'idx_retention_status_release', 'status, release_date');
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

    private function addIndex(PDO $pdo, string $table, string $name, string $columns): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
        $stmt->execute([$table, $name]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$name} ({$columns})");
        }
    }
}
