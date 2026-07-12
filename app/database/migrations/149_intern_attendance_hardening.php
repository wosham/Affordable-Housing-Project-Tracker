<?php

class Migration149InternAttendanceHardening
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'attendance_records', 'role_at_signin', 'VARCHAR(50) NULL AFTER project_id');
        $this->addColumn($pdo, 'attendance_records', 'accuracy_meters', 'DECIMAL(8,1) NULL AFTER distance_from_site_m');
        $this->addColumn($pdo, 'attendance_records', 'review_status', "ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending' AFTER status");
        $this->addIndex($pdo, 'attendance_records', 'idx_attendance_user_project_date', 'user_id, project_id, date');
        $this->addIndex($pdo, 'attendance_records', 'idx_attendance_user_status_date', 'user_id, status, date');
        $this->addIndex($pdo, 'project_assignments', 'idx_assignments_user_status_project', 'user_id, status, project_id');
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
