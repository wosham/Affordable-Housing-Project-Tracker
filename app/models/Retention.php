<?php

class Retention extends Model
{
    protected static string $table = 'retention';

    public const STATUSES = ['held', 'released', 'waived'];

    public static function forProject(int $projectId, int $limit = 100): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Database::fetchAll(
            self::selectSql() . ' WHERE r.project_id = ? ORDER BY r.created_at DESC, r.id DESC LIMIT ' . max(1, $limit),
            [$projectId]
        );
    }

    public static function forIpc(int $ipcId): ?array
    {
        if ($ipcId <= 0) {
            return null;
        }

        return Database::fetch(self::selectSql() . ' WHERE r.ipc_id = ? LIMIT 1', [$ipcId]);
    }

    public static function openBalance(int $projectId = 0): float
    {
        $where = "r.status <> 'released'";
        $bindings = [];
        if ($projectId > 0) {
            $where .= ' AND r.project_id = ?';
            $bindings[] = $projectId;
        }

        $row = Database::fetch(
            "SELECT COALESCE(SUM(r.total_held - r.released_amount), 0) AS balance
             FROM retention r
             WHERE {$where}",
            $bindings
        ) ?: [];

        return (float)($row['balance'] ?? 0);
    }

    public static function portfolioSummary(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS records,
                    COALESCE(SUM(total_held), 0) AS held,
                    COALESCE(SUM(released_amount), 0) AS released,
                    COALESCE(SUM(total_held - released_amount), 0) AS balance
             FROM retention
             WHERE status <> 'released'"
        ) ?: [];

        return [
            'records' => (int)($row['records'] ?? 0),
            'held' => (float)($row['held'] ?? 0),
            'released' => (float)($row['released'] ?? 0),
            'balance' => (float)($row['balance'] ?? 0),
        ];
    }

    private static function selectSql(): string
    {
        return "SELECT r.*,
                       p.name AS project_name,
                       i.ipc_number,
                       CONCAT(u.first_name, ' ', u.last_name) AS processed_by_name
                FROM retention r
                LEFT JOIN projects p ON p.id = r.project_id
                LEFT JOIN ipcs i ON i.id = r.ipc_id
                LEFT JOIN users u ON u.id = r.processed_by";
    }
}
