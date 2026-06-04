<?php

class Migration117ManagerMilestoneControls
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'milestones', 'description', 'TEXT NULL AFTER label');
        $this->addColumn($pdo, 'milestones', 'progress_percent', 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER status');
        $this->addColumn($pdo, 'milestones', 'priority', "VARCHAR(20) NOT NULL DEFAULT 'normal' AFTER progress_percent");
        $this->addColumn($pdo, 'milestones', 'updated_by', 'INT UNSIGNED NULL AFTER sequence');
        $this->addColumn($pdo, 'milestones', 'completed_by', 'INT UNSIGNED NULL AFTER updated_by');
        $this->addColumn($pdo, 'milestones', 'notes', 'TEXT NULL AFTER completed_by');
        $this->addColumn($pdo, 'milestones', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER notes');
        $this->addColumn($pdo, 'milestones', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

        $pdo->exec("UPDATE milestones SET progress_percent = CASE WHEN status = 'done' THEN 100 WHEN status = 'current' AND progress_percent = 0 THEN 50 ELSE progress_percent END");

        $this->addIndex($pdo, 'milestones', 'idx_milestones_project_status', 'project_id, status');
        $this->addIndex($pdo, 'milestones', 'idx_milestones_target_date', 'target_date');
        $this->addIndex($pdo, 'milestones', 'idx_milestones_priority', 'priority');
        $this->addIndex($pdo, 'milestones', 'idx_milestones_updated_by', 'updated_by');

        $pdo->exec("CREATE TABLE IF NOT EXISTS milestone_updates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            milestone_id INT UNSIGNED NOT NULL,
            project_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            old_status VARCHAR(30) NULL,
            new_status VARCHAR(30) NULL,
            old_target_date DATE NULL,
            new_target_date DATE NULL,
            old_progress_percent TINYINT UNSIGNED NULL,
            new_progress_percent TINYINT UNSIGNED NULL,
            note TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_milestone_updates_milestone (milestone_id, created_at),
            INDEX idx_milestone_updates_project (project_id, created_at),
            INDEX idx_milestone_updates_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS milestone_updates');

        foreach ([
            'idx_milestones_updated_by',
            'idx_milestones_priority',
            'idx_milestones_target_date',
            'idx_milestones_project_status',
        ] as $index) {
            $this->dropIndex($pdo, 'milestones', $index);
        }

        foreach ([
            'updated_at',
            'created_at',
            'notes',
            'completed_by',
            'updated_by',
            'priority',
            'progress_percent',
            'description',
        ] as $column) {
            $this->dropColumn($pdo, 'milestones', $column);
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
