<?php

class Migration_099_ProgrammeOfWorksUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'programme_tasks', 'sort_order', 'INT UNSIGNED DEFAULT 0 AFTER status');
        $this->addColumn($pdo, 'programme_tasks', 'critical_path', 'TINYINT(1) DEFAULT 0 AFTER sort_order');
        $this->addColumn($pdo, 'programme_tasks', 'baseline_start', 'DATE NULL AFTER critical_path');
        $this->addColumn($pdo, 'programme_tasks', 'baseline_end', 'DATE NULL AFTER baseline_start');
        $this->addColumn($pdo, 'programme_tasks', 'notes', 'TEXT NULL AFTER baseline_end');
        $this->addColumn($pdo, 'programme_tasks', 'updated_by', 'INT UNSIGNED NULL AFTER notes');
        $this->addColumn($pdo, 'programme_tasks', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by');
        $this->addColumn($pdo, 'programme_tasks', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $pdo->exec("UPDATE programme_tasks SET planned_start = COALESCE(planned_start, start_date), planned_end = COALESCE(planned_end, end_date)");

        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_project', 'project_id');
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_project_status', 'project_id, status');
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_project_dates', 'project_id, planned_start, planned_end');
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_assigned_to', 'assigned_to');
        $this->addIndex($pdo, 'programme_tasks', 'idx_programme_dependency', 'depends_on_task_id');
    }

    public function down(\PDO $pdo): void
    {
        foreach (['idx_programme_dependency', 'idx_programme_assigned_to', 'idx_programme_project_dates', 'idx_programme_project_status', 'idx_programme_project'] as $index) {
            $this->dropIndex($pdo, 'programme_tasks', $index);
        }

        foreach (['updated_at', 'created_at', 'updated_by', 'notes', 'baseline_end', 'baseline_start', 'critical_path', 'sort_order'] as $column) {
            $this->dropColumn($pdo, 'programme_tasks', $column);
        }
    }

    private function addColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function dropColumn(\PDO $pdo, string $table, string $column): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    private function addIndex(\PDO $pdo, string $table, string $index, string $columns): void
    {
        if ($this->indexExists($pdo, $table, $index)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
    }

    private function dropIndex(\PDO $pdo, string $table, string $index): void
    {
        if (!$this->indexExists($pdo, $table, $index)) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} DROP INDEX {$index}");
    }

    private function columnExists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(\PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
