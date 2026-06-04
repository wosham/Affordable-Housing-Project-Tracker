<?php

class Migration120ManagerAttendanceIndexes
{
    public function up(PDO $pdo): void
    {
        $this->addIndex($pdo, 'attendance_records', 'idx_attendance_date_user', 'date, user_id');
        $this->addIndex($pdo, 'attendance_gateways', 'idx_gateways_project_date_open', 'project_id, date, is_open');
        $this->addIndex($pdo, 'project_assignments', 'idx_assignments_project_user_status', 'project_id, user_id, status');
    }

    public function down(PDO $pdo): void
    {
        $this->dropIndex($pdo, 'project_assignments', 'idx_assignments_project_user_status');
        $this->dropIndex($pdo, 'attendance_gateways', 'idx_gateways_project_date_open');
        $this->dropIndex($pdo, 'attendance_records', 'idx_attendance_date_user');
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

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
