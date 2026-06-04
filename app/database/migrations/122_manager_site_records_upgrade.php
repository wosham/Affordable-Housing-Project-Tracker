<?php

class Migration122ManagerSiteRecordsUpgrade
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'site_meeting_minutes', 'status', "ENUM('draft','recorded','reviewed','closed') DEFAULT 'recorded'");
        $this->addColumn($pdo, 'site_meeting_minutes', 'action_status', "ENUM('none','open','in-progress','completed','overdue') DEFAULT 'none'");
        $this->addColumn($pdo, 'site_meeting_minutes', 'updated_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'site_meeting_minutes', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->addColumn($pdo, 'site_meeting_minutes', 'reviewed_at', 'DATETIME NULL');

        $this->addColumn($pdo, 'hs_incidents', 'status', "ENUM('open','investigating','action-pending','resolved','closed') DEFAULT 'open'");
        $this->addColumn($pdo, 'hs_incidents', 'follow_up_date', 'DATE NULL');
        $this->addColumn($pdo, 'hs_incidents', 'closed_at', 'DATETIME NULL');
        $this->addColumn($pdo, 'hs_incidents', 'updated_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'hs_incidents', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->addColumn($pdo, 'hs_incidents', 'attachment_path', 'VARCHAR(255) NULL');

        $this->addColumn($pdo, 'community_liaison', 'status', "ENUM('open','follow-up','resolved','closed') DEFAULT 'open'");
        $this->addColumn($pdo, 'community_liaison', 'updated_by', 'INT UNSIGNED NULL');
        $this->addColumn($pdo, 'community_liaison', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->addColumn($pdo, 'community_liaison', 'closed_at', 'DATETIME NULL');

        $this->addIndex($pdo, 'site_meeting_minutes', 'idx_smm_project_date', 'project_id, meeting_date');
        $this->addIndex($pdo, 'site_meeting_minutes', 'idx_smm_status', 'status, meeting_date');
        $this->addIndex($pdo, 'site_meeting_minutes', 'idx_smm_action_status', 'action_status, meeting_date');
        $this->addIndex($pdo, 'hs_incidents', 'idx_hs_project_date', 'project_id, incident_date');
        $this->addIndex($pdo, 'hs_incidents', 'idx_hs_status_severity', 'status, severity');
        $this->addIndex($pdo, 'hs_incidents', 'idx_hs_followup', 'follow_up_date, status');
        $this->addIndex($pdo, 'community_liaison', 'idx_cl_project_date', 'project_id, log_date');
        $this->addIndex($pdo, 'community_liaison', 'idx_cl_status_followup', 'status, follow_up_date');
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            ['community_liaison', 'idx_cl_status_followup'],
            ['community_liaison', 'idx_cl_project_date'],
            ['hs_incidents', 'idx_hs_followup'],
            ['hs_incidents', 'idx_hs_status_severity'],
            ['hs_incidents', 'idx_hs_project_date'],
            ['site_meeting_minutes', 'idx_smm_action_status'],
            ['site_meeting_minutes', 'idx_smm_status'],
            ['site_meeting_minutes', 'idx_smm_project_date'],
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
