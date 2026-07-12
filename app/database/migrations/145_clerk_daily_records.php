<?php

class Migration145ClerkDailyRecords
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'site_diaries', 'safety_observations', 'TEXT NULL AFTER issues_raised');
        $this->addColumn($pdo, 'site_diaries', 'visitors_instructions', 'TEXT NULL AFTER safety_observations');
        $this->addColumn($pdo, 'site_diaries', 'status', "ENUM('draft','submitted','reviewed') NOT NULL DEFAULT 'submitted' AFTER consultant_reviewed_at");
        $this->addColumn($pdo, 'site_diaries', 'updated_by', 'INT UNSIGNED NULL AFTER status');

        $this->addColumn($pdo, 'weather_logs', 'temperature_min', 'DECIMAL(4,1) NULL AFTER rainfall_mm');
        $this->addColumn($pdo, 'weather_logs', 'temperature_max', 'DECIMAL(4,1) NULL AFTER temperature_min');
        $this->addColumn($pdo, 'weather_logs', 'working_hours_lost', 'DECIMAL(4,1) NOT NULL DEFAULT 0 AFTER working_hours');
        $this->addColumn($pdo, 'weather_logs', 'impact_level', "ENUM('none','minor','moderate','severe') NOT NULL DEFAULT 'none' AFTER working_hours_lost");
        $this->addColumn($pdo, 'weather_logs', 'updated_by', 'INT UNSIGNED NULL AFTER recorded_by');
        $this->addColumn($pdo, 'weather_logs', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by');
        $this->addColumn($pdo, 'weather_logs', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $this->addColumn($pdo, 'labour_register', 'clerk_skilled_count', 'SMALLINT UNSIGNED NULL AFTER total');
        $this->addColumn($pdo, 'labour_register', 'clerk_unskilled_count', 'SMALLINT UNSIGNED NULL AFTER clerk_skilled_count');
        $this->addColumn($pdo, 'labour_register', 'clerk_supervisor_count', 'SMALLINT UNSIGNED NULL AFTER clerk_unskilled_count');
        $this->addColumn($pdo, 'labour_register', 'clerk_total', 'SMALLINT UNSIGNED NULL AFTER clerk_supervisor_count');
        $this->addColumn($pdo, 'labour_register', 'variance_total', 'SMALLINT NULL AFTER clerk_total');
        $this->addColumn($pdo, 'labour_register', 'verification_status', "ENUM('pending','verified','queried') NOT NULL DEFAULT 'pending' AFTER status");
        $this->addColumn($pdo, 'labour_register', 'verified_by', 'INT UNSIGNED NULL AFTER verification_status');
        $this->addColumn($pdo, 'labour_register', 'verified_at', 'DATETIME NULL AFTER verified_by');
        $this->addColumn($pdo, 'labour_register', 'verification_notes', 'TEXT NULL AFTER verified_at');

        $this->addColumn($pdo, 'material_deliveries', 'verified_quantity', 'DECIMAL(12,3) NULL AFTER quantity');
        $this->addColumn($pdo, 'material_deliveries', 'verification_status', "ENUM('pending','accepted','queried','rejected') NOT NULL DEFAULT 'pending' AFTER status");
        $this->addColumn($pdo, 'material_deliveries', 'verified_by', 'INT UNSIGNED NULL AFTER verification_status');
        $this->addColumn($pdo, 'material_deliveries', 'verified_at', 'DATETIME NULL AFTER verified_by');
        $this->addColumn($pdo, 'material_deliveries', 'verification_notes', 'TEXT NULL AFTER verified_at');

        $this->addColumn($pdo, 'equipment_register', 'check_status', "ENUM('pending','present','missing','off-site','maintenance','queried') NOT NULL DEFAULT 'pending' AFTER status");
        $this->addColumn($pdo, 'equipment_register', 'checked_by', 'INT UNSIGNED NULL AFTER check_status');
        $this->addColumn($pdo, 'equipment_register', 'checked_at', 'DATETIME NULL AFTER checked_by');
        $this->addColumn($pdo, 'equipment_register', 'check_notes', 'TEXT NULL AFTER checked_at');

        $this->addIndex($pdo, 'site_diaries', 'idx_site_diaries_project_status_date', 'project_id, status, diary_date');
        $this->addIndex($pdo, 'weather_logs', 'idx_weather_project_date', 'project_id, log_date');
        $this->addIndex($pdo, 'labour_register', 'idx_labour_verification', 'project_id, verification_status, diary_date');
        $this->addIndex($pdo, 'material_deliveries', 'idx_material_verification', 'project_id, verification_status, delivery_date');
        $this->addIndex($pdo, 'equipment_register', 'idx_equipment_check', 'project_id, check_status, status');
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
