<?php

class DatabaseBackup extends Model
{
    protected static string $table = 'database_backups';

    public static function recent(int $limit = 30): array
    {
        return Database::fetchAll('SELECT b.*, CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) AS created_by_name FROM database_backups b LEFT JOIN users u ON u.id = b.created_by ORDER BY b.created_at DESC LIMIT ' . max(1, min(100, $limit)));
    }

    public static function summary(): array
    {
        return Database::fetch("SELECT SUM(status = 'completed') AS completed, SUM(status = 'failed') AS failed, COALESCE(SUM(CASE WHEN status = 'completed' THEN size_bytes ELSE 0 END), 0) AS size_bytes, MAX(CASE WHEN status = 'completed' THEN completed_at END) AS last_completed_at FROM database_backups") ?: [];
    }

    public static function running(): bool
    {
        return Database::fetch("SELECT id FROM database_backups WHERE status = 'running' LIMIT 1") !== null;
    }
}
