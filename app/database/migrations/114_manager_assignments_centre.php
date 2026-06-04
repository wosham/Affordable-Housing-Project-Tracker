<?php

class Migration114ManagerAssignmentsCentre
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'project_assignments', 'assignment_type', "VARCHAR(40) NOT NULL DEFAULT 'site' AFTER role");
        $this->addColumn($pdo, 'project_assignments', 'scope', "VARCHAR(80) NOT NULL DEFAULT 'general' AFTER assignment_type");
        $this->addColumn($pdo, 'project_assignments', 'status', "VARCHAR(30) NOT NULL DEFAULT 'active' AFTER scope");
        $this->addColumn($pdo, 'project_assignments', 'start_date', 'DATE NULL AFTER status');
        $this->addColumn($pdo, 'project_assignments', 'end_date', 'DATE NULL AFTER start_date');
        $this->addColumn($pdo, 'project_assignments', 'is_primary', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER end_date');
        $this->addColumn($pdo, 'project_assignments', 'notes', 'TEXT NULL AFTER is_primary');
        $this->addColumn($pdo, 'project_assignments', 'revoked_by', 'INT UNSIGNED NULL AFTER notes');
        $this->addColumn($pdo, 'project_assignments', 'revoked_at', 'DATETIME NULL AFTER revoked_by');
        $this->addColumn($pdo, 'project_assignments', 'updated_by', 'INT UNSIGNED NULL AFTER revoked_at');
        $this->addColumn($pdo, 'project_assignments', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by');

        $pdo->exec("UPDATE project_assignments SET status = 'active' WHERE status IS NULL OR status = ''");

        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_project_role_status', 'project_id, role, status');
        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_user_status', 'user_id, status');
        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_status_dates', 'status, start_date, end_date');
        $this->addIndex($pdo, 'project_assignments', 'idx_project_assignments_primary', 'project_id, role, is_primary');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            'idx_project_assignments_primary',
            'idx_project_assignments_status_dates',
            'idx_project_assignments_user_status',
            'idx_project_assignments_project_role_status',
        ] as $index) {
            $this->dropIndex($pdo, 'project_assignments', $index);
        }

        foreach ([
            'updated_at',
            'updated_by',
            'revoked_at',
            'revoked_by',
            'notes',
            'is_primary',
            'end_date',
            'start_date',
            'status',
            'scope',
            'assignment_type',
        ] as $column) {
            $this->dropColumn($pdo, 'project_assignments', $column);
        }
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
