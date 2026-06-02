<?php

class Migration_101_SubscribersCentreUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->addColumn($pdo, 'subscribers', 'source_url', 'VARCHAR(500) NULL AFTER ip');
        $this->addColumn($pdo, 'subscribers', 'user_agent', 'VARCHAR(255) NULL AFTER source_url');
        $this->addColumn($pdo, 'subscribers', 'unsubscribed_at', 'DATETIME NULL AFTER user_agent');
        $this->addColumn($pdo, 'subscribers', 'reactivated_at', 'DATETIME NULL AFTER unsubscribed_at');
        $this->addColumn($pdo, 'subscribers', 'updated_by', 'INT UNSIGNED NULL AFTER reactivated_at');
        $this->addColumn($pdo, 'subscribers', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by');

        $pdo->exec("UPDATE subscribers SET unsubscribed_at = subscribed_at WHERE status = 'unsubscribed' AND unsubscribed_at IS NULL");

        $this->addIndex($pdo, 'subscribers', 'idx_subscribers_status', 'status, subscribed_at');
        $this->addIndex($pdo, 'subscribers', 'idx_subscribers_email', 'email');
        $this->addIndex($pdo, 'subscribers', 'idx_subscribers_subscribed_at', 'subscribed_at');
        $this->addIndex($pdo, 'subscribers', 'idx_subscribers_ip', 'ip');
    }

    public function down(\PDO $pdo): void
    {
        foreach (['idx_subscribers_ip', 'idx_subscribers_subscribed_at', 'idx_subscribers_email', 'idx_subscribers_status'] as $index) {
            $this->dropIndex($pdo, 'subscribers', $index);
        }

        foreach (['updated_at', 'updated_by', 'reactivated_at', 'unsubscribed_at', 'user_agent', 'source_url'] as $column) {
            $this->dropColumn($pdo, 'subscribers', $column);
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
