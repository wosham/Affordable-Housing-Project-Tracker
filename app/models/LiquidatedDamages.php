<?php

class LiquidatedDamages extends Model
{
    protected static string $table = 'liquidated_damages';

    public const STATUSES = ['draft', 'pending', 'applied', 'suspended', 'waived'];

    public static function forProject(int $projectId, int $limit = 100): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Database::fetchAll(
            self::selectSql() . ' WHERE ld.project_id = ? ORDER BY ld.updated_at DESC, ld.id DESC LIMIT ' . max(1, $limit),
            [$projectId]
        );
    }

    public static function findDetailed(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return Database::fetch(self::selectSql() . ' WHERE ld.id = ? LIMIT 1', [$id]);
    }

    public static function activeTotal(int $projectId = 0): float
    {
        $where = "ld.status IN ('pending','applied')";
        $bindings = [];
        if ($projectId > 0) {
            $where .= ' AND ld.project_id = ?';
            $bindings[] = $projectId;
        }

        $row = Database::fetch(
            "SELECT COALESCE(SUM(ld.total_ld), 0) AS total
             FROM liquidated_damages ld
             WHERE {$where}",
            $bindings
        ) ?: [];

        return (float)($row['total'] ?? 0);
    }

    public static function portfolioSummary(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS records,
                    COALESCE(SUM(CASE WHEN status IN ('pending','applied') THEN total_ld ELSE 0 END), 0) AS active_value,
                    COALESCE(SUM(total_ld), 0) AS total_value
             FROM liquidated_damages"
        ) ?: [];

        return [
            'records' => (int)($row['records'] ?? 0),
            'active_value' => (float)($row['active_value'] ?? 0),
            'total_value' => (float)($row['total_value'] ?? 0),
        ];
    }

    private static function selectSql(): string
    {
        return "SELECT ld.*,
                       p.name AS project_name,
                       i.ipc_number,
                       CONCAT(u.first_name, ' ', u.last_name) AS calculated_by_name
                FROM liquidated_damages ld
                LEFT JOIN projects p ON p.id = ld.project_id
                LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id
                LEFT JOIN users u ON u.id = ld.calculated_by";
    }
}
