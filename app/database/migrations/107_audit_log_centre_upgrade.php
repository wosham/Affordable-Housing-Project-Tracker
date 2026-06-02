<?php

class Migration107AuditLogCentreUpgrade
{
    public function up(PDO $pdo): void
    {
        $this->addColumn($pdo, 'audit_logs', 'actor_role', "VARCHAR(80) NULL AFTER user_id");
        $this->addColumn($pdo, 'audit_logs', 'request_method', "VARCHAR(10) NULL AFTER user_agent");
        $this->addColumn($pdo, 'audit_logs', 'route', "VARCHAR(255) NULL AFTER request_method");
        $this->addColumn($pdo, 'audit_logs', 'severity', "ENUM('info','warning','critical') DEFAULT 'info' AFTER route");
        $this->addColumn($pdo, 'audit_logs', 'event_hash', "CHAR(64) NULL AFTER severity");
        $this->addColumn($pdo, 'audit_logs', 'metadata_json', "JSON NULL AFTER event_hash");

        $pdo->exec("
            UPDATE audit_logs
            SET severity = CASE
                WHEN action LIKE '%delete%' OR action LIKE '%reject%' OR action = 'login_failed' OR action = 'reset' OR module LIKE '%settings%' THEN 'critical'
                WHEN action LIKE '%cancel%' OR action LIKE '%failed%' OR action LIKE '%update-status%' OR action LIKE '%geo%' THEN 'warning'
                ELSE 'info'
            END
            WHERE severity IS NULL OR severity = 'info'
        ");

        $this->addIndex($pdo, 'audit_logs', 'idx_audit_action_created', 'action, created_at');
        $this->addIndex($pdo, 'audit_logs', 'idx_audit_module_created', 'module, created_at');
        $this->addIndex($pdo, 'audit_logs', 'idx_audit_severity_created', 'severity, created_at');
        $this->addIndex($pdo, 'audit_logs', 'idx_audit_ip_created', 'ip, created_at');
        $this->addIndex($pdo, 'audit_logs', 'idx_audit_target', 'module, target_id');
        $this->addIndex($pdo, 'audit_logs', 'idx_audit_actor_role', 'actor_role, created_at');
        $this->addIndex($pdo, 'audit_logs', 'idx_audit_event_hash', 'event_hash');
    }

    public function down(PDO $pdo): void
    {
        foreach (['idx_audit_event_hash', 'idx_audit_actor_role', 'idx_audit_target', 'idx_audit_ip_created', 'idx_audit_severity_created', 'idx_audit_module_created', 'idx_audit_action_created'] as $index) {
            $this->dropIndex($pdo, 'audit_logs', $index);
        }

        foreach (['metadata_json', 'event_hash', 'severity', 'route', 'request_method', 'actor_role'] as $column) {
            $this->dropColumn($pdo, 'audit_logs', $column);
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
