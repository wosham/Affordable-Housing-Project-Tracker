<?php

class Migration168WorkLocations
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS work_locations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL UNIQUE,
            location_type VARCHAR(60) NOT NULL DEFAULT 'office',
            address VARCHAR(255) NULL,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            radius_meters INT UNSIGNED NOT NULL DEFAULT 150,
            status ENUM('configured','missing','needs-review','inactive') NOT NULL DEFAULT 'missing',
            is_public TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_work_locations_status (status),
            INDEX idx_work_locations_type (location_type)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS work_location_assignments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            work_location_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            role VARCHAR(60) NOT NULL,
            assignment_type VARCHAR(40) NOT NULL DEFAULT 'office',
            scope VARCHAR(80) NOT NULL DEFAULT 'attendance',
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            start_date DATE NULL,
            end_date DATE NULL,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            assigned_by INT UNSIGNED NULL,
            assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by INT UNSIGNED NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            revoked_by INT UNSIGNED NULL,
            revoked_at DATETIME NULL,
            UNIQUE KEY uniq_work_location_user (work_location_id, user_id),
            INDEX idx_wla_user_status (user_id, status),
            INDEX idx_wla_location_status (work_location_id, status)
        )");

        $this->addColumn($pdo, 'attendance_records', 'work_location_id', 'INT UNSIGNED NULL AFTER project_id');
        $this->addIndex($pdo, 'attendance_records', 'idx_attendance_work_location_date', 'work_location_id, date');
        $this->addColumn($pdo, 'attendance_gateways', 'work_location_id', 'INT UNSIGNED NULL AFTER project_id');
        $this->addIndex($pdo, 'attendance_gateways', 'idx_gateways_work_location_date', 'work_location_id, date');
        $this->makeNullable($pdo, 'attendance_records', 'project_id', 'INT UNSIGNED NULL');
        $this->makeNullable($pdo, 'attendance_gateways', 'project_id', 'INT UNSIGNED NULL');

        $stmt = $pdo->prepare("INSERT INTO work_locations (name, slug, location_type, address, status, is_public, notes)
            SELECT 'Headquarters Office', 'headquarters-office', 'office', 'Trans-Nzoia County Government Offices, Kitale', 'missing', 0,
                   'Internal headquarters work location for staff and intern attendance. Configure coordinates before enforcing geo-fence.'
            WHERE NOT EXISTS (SELECT 1 FROM work_locations WHERE slug = 'headquarters-office')");
        $stmt->execute();
    }

    public function down(PDO $pdo): void
    {
        $this->dropIndex($pdo, 'attendance_gateways', 'idx_gateways_work_location_date');
        $this->dropColumn($pdo, 'attendance_gateways', 'work_location_id');
        $this->dropIndex($pdo, 'attendance_records', 'idx_attendance_work_location_date');
        $this->dropColumn($pdo, 'attendance_records', 'work_location_id');
        $pdo->exec('DROP TABLE IF EXISTS work_location_assignments');
        $pdo->exec('DROP TABLE IF EXISTS work_locations');
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function dropColumn(PDO $pdo, string $table, string $column): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
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

    private function makeNullable(PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            $stmt = $pdo->prepare('SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute([$table, $column]);
            if ((string)$stmt->fetchColumn() === 'NO') {
                $pdo->exec("ALTER TABLE {$table} MODIFY {$column} {$definition}");
            }
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