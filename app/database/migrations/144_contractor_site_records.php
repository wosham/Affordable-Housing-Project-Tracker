<?php

class Migration144ContractorSiteRecords
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'equipment_register', 'status', "ENUM('on-site','off-site','maintenance') NOT NULL DEFAULT 'on-site' AFTER `condition`");
        $this->addColumn($pdo, 'equipment_register', 'updated_by', 'INT UNSIGNED NULL AFTER status');
        $this->addColumn($pdo, 'equipment_register', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by');

        $this->addColumn($pdo, 'labour_register', 'status', "ENUM('submitted','reviewed') NOT NULL DEFAULT 'submitted' AFTER recorded_by");
        $this->addColumn($pdo, 'labour_register', 'updated_by', 'INT UNSIGNED NULL AFTER status');
        $this->addColumn($pdo, 'labour_register', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by');

        $this->addColumn($pdo, 'material_deliveries', 'status', "ENUM('submitted','accepted','queried') NOT NULL DEFAULT 'submitted' AFTER approved");
        $this->addColumn($pdo, 'material_deliveries', 'updated_by', 'INT UNSIGNED NULL AFTER status');
        $this->addColumn($pdo, 'material_deliveries', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by');

        $this->addIndex($pdo, 'equipment_register', 'idx_equipment_project_status', 'project_id, status');
        $this->addIndex($pdo, 'labour_register', 'idx_labour_project_status_date', 'project_id, status, diary_date');
        $this->addIndex($pdo, 'material_deliveries', 'idx_material_deliveries_project_status_date', 'project_id, status, delivery_date');
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
