<?php

class Migration_070_GeoAttendanceControls
{
    public function up(PDO $pdo): void
    {
        $columns = $pdo->query("SHOW COLUMNS FROM geo_fences")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('status', $columns, true)) {
            $pdo->exec("ALTER TABLE geo_fences ADD COLUMN status ENUM('configured','missing','needs-review') DEFAULT 'configured' AFTER radius_meters");
        }

        if (!in_array('verified_by', $columns, true)) {
            $pdo->exec("ALTER TABLE geo_fences ADD COLUMN verified_by INT UNSIGNED NULL AFTER created_by");
            $pdo->exec("ALTER TABLE geo_fences ADD CONSTRAINT fk_geo_fences_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL");
        }

        if (!in_array('verified_at', $columns, true)) {
            $pdo->exec("ALTER TABLE geo_fences ADD COLUMN verified_at DATETIME NULL AFTER verified_by");
        }

        if (!in_array('notes', $columns, true)) {
            $pdo->exec("ALTER TABLE geo_fences ADD COLUMN notes TEXT NULL AFTER verified_at");
        }

        $indexes = $pdo->query("SHOW INDEX FROM geo_fences")->fetchAll(PDO::FETCH_ASSOC);
        $indexNames = array_column($indexes, 'Key_name');
        if (!in_array('idx_geo_project_status', $indexNames, true)) {
            $pdo->exec("CREATE INDEX idx_geo_project_status ON geo_fences (project_id, status)");
        }

        $attendanceColumns = $pdo->query("SHOW COLUMNS FROM attendance_records")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('role_at_signin', $attendanceColumns, true)) {
            $pdo->exec("ALTER TABLE attendance_records ADD COLUMN role_at_signin VARCHAR(60) NULL AFTER project_id");
        }
        if (!in_array('accuracy_meters', $attendanceColumns, true)) {
            $pdo->exec("ALTER TABLE attendance_records ADD COLUMN accuracy_meters DECIMAL(8,1) NULL AFTER distance_from_site_m");
        }
        if (!in_array('review_status', $attendanceColumns, true)) {
            $pdo->exec("ALTER TABLE attendance_records ADD COLUMN review_status ENUM('pending','accepted','flagged') DEFAULT 'pending' AFTER status");
        }
        if (!in_array('reviewed_by', $attendanceColumns, true)) {
            $pdo->exec("ALTER TABLE attendance_records ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER review_status");
            $pdo->exec("ALTER TABLE attendance_records ADD CONSTRAINT fk_attendance_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL");
        }
        if (!in_array('reviewed_at', $attendanceColumns, true)) {
            $pdo->exec("ALTER TABLE attendance_records ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by");
        }

        $attendanceIndexes = $pdo->query("SHOW INDEX FROM attendance_records")->fetchAll(PDO::FETCH_ASSOC);
        $attendanceIndexNames = array_column($attendanceIndexes, 'Key_name');
        if (!in_array('idx_attendance_date_project_status', $attendanceIndexNames, true)) {
            $pdo->exec("CREATE INDEX idx_attendance_date_project_status ON attendance_records (date, project_id, status)");
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP INDEX IF EXISTS idx_attendance_date_project_status ON attendance_records");
        $pdo->exec("DROP INDEX IF EXISTS idx_geo_project_status ON geo_fences");
    }
}
