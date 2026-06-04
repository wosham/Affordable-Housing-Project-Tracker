<?php

class Migration123ManagerContractControlsUpgrade
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'eot_requests', 'manager_recommendation', "ENUM('pending','approve','partial','reject','clarification') DEFAULT 'pending'");
        $this->addColumn($pdo, 'eot_requests', 'manager_recommended_days', 'SMALLINT UNSIGNED NULL');
        $this->addColumn($pdo, 'eot_requests', 'manager_review_note', 'TEXT NULL');
        $this->addColumn($pdo, 'eot_requests', 'manager_reviewed_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'eot_requests', 'manager_reviewed_at', 'DATETIME NULL');
        $this->addColumn($pdo, 'eot_requests', 'delay_category', 'VARCHAR(80) NULL');
        $this->addColumn($pdo, 'eot_requests', 'impact_summary', 'TEXT NULL');
        $this->addColumn($pdo, 'eot_requests', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->addColumn($pdo, 'liquidated_damages', 'status', "ENUM('draft','pending','applied','suspended','waived') DEFAULT 'draft'");
        $this->addColumn($pdo, 'liquidated_damages', 'calculated_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'liquidated_damages', 'updated_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'liquidated_damages', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->addColumn($pdo, 'liquidated_damages', 'applied_at', 'DATETIME NULL');

        $this->addColumn($pdo, 'subcontractors', 'contact_person', 'VARCHAR(150) NULL');
        $this->addColumn($pdo, 'subcontractors', 'phone', 'VARCHAR(60) NULL');
        $this->addColumn($pdo, 'subcontractors', 'email', 'VARCHAR(180) NULL');
        $this->addColumn($pdo, 'subcontractors', 'compliance_status', "ENUM('pending','compliant','issue','expired') DEFAULT 'pending'");
        $this->addColumn($pdo, 'subcontractors', 'risk_status', "ENUM('normal','watch','high','critical') DEFAULT 'normal'");
        $this->addColumn($pdo, 'subcontractors', 'performance_note', 'TEXT NULL');
        $this->addColumn($pdo, 'subcontractors', 'updated_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'subcontractors', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->addIndex($pdo, 'eot_requests', 'idx_eot_project_status', 'project_id, status');
        $this->addIndex($pdo, 'eot_requests', 'idx_eot_manager_review', 'manager_recommendation, manager_reviewed_at');
        $this->addIndex($pdo, 'liquidated_damages', 'idx_ld_project_status', 'project_id, status');
        $this->addIndex($pdo, 'liquidated_damages', 'idx_ld_ipc', 'applied_to_ipc_id');
        $this->addIndex($pdo, 'subcontractors', 'idx_subcontractors_project_status', 'project_id, status');
        $this->addIndex($pdo, 'subcontractors', 'idx_subcontractors_compliance', 'compliance_status, risk_status');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            ['subcontractors', 'idx_subcontractors_compliance'],
            ['subcontractors', 'idx_subcontractors_project_status'],
            ['liquidated_damages', 'idx_ld_ipc'],
            ['liquidated_damages', 'idx_ld_project_status'],
            ['eot_requests', 'idx_eot_manager_review'],
            ['eot_requests', 'idx_eot_project_status'],
        ] as [$table, $index]) {
            $this->dropIndex($pdo, $table, $index);
        }
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        if (!$this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
        }
    }

    private function dropIndex(PDO $pdo, string $table, string $index): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
