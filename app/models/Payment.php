<?php

/**
 * Payment register read helpers.
 * Write path for IPC settlement remains FinancePayment::process().
 */
class Payment extends Model
{
    protected static string $table = 'payments';

    public static function forIpc(int $ipcId, int $limit = 50): array
    {
        if ($ipcId <= 0) {
            return [];
        }

        return Database::fetchAll(
            self::selectSql() . ' WHERE pay.ipc_id = ? ORDER BY pay.payment_date DESC, pay.id DESC LIMIT ' . max(1, $limit),
            [$ipcId]
        );
    }

    public static function forProject(int $projectId, int $limit = 50): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Database::fetchAll(
            self::selectSql() . ' WHERE pay.project_id = ? ORDER BY pay.payment_date DESC, pay.id DESC LIMIT ' . max(1, $limit),
            [$projectId]
        );
    }

    public static function recent(int $limit = 20): array
    {
        // Prefer FinancePayment when available (same SQL, richer joins).
        if (class_exists('FinancePayment') && method_exists('FinancePayment', 'recentPayments')) {
            return FinancePayment::recentPayments($limit);
        }

        return Database::fetchAll(
            self::selectSql() . " WHERE pay.status = 'processed' ORDER BY pay.payment_date DESC, pay.id DESC LIMIT " . max(1, $limit)
        );
    }

    public static function findDetailed(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return Database::fetch(self::selectSql() . ' WHERE pay.id = ? LIMIT 1', [$id]);
    }

    public static function totalsForProject(int $projectId): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS payment_count,
                    COALESCE(SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END), 0) AS paid_value
             FROM payments
             WHERE project_id = ?",
            [$projectId]
        ) ?: [];

        return [
            'payment_count' => (int)($row['payment_count'] ?? 0),
            'paid_value' => (float)($row['paid_value'] ?? 0),
        ];
    }

    public static function totalsForIpc(int $ipcId): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS payment_count,
                    COALESCE(SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END), 0) AS paid_value
             FROM payments
             WHERE ipc_id = ?",
            [$ipcId]
        ) ?: [];

        return [
            'payment_count' => (int)($row['payment_count'] ?? 0),
            'paid_value' => (float)($row['paid_value'] ?? 0),
        ];
    }

    public static function portfolioTotals(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS payment_count,
                    COALESCE(SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END), 0) AS paid_value
             FROM payments"
        ) ?: [];

        return [
            'payment_count' => (int)($row['payment_count'] ?? 0),
            'paid_value' => (float)($row['paid_value'] ?? 0),
        ];
    }

    private static function selectSql(): string
    {
        return "SELECT pay.*,
                       i.ipc_number,
                       i.net_amount,
                       p.name AS project_name,
                       CONCAT(processor.first_name, ' ', processor.last_name) AS processed_by_name
                FROM payments pay
                LEFT JOIN ipcs i ON i.id = pay.ipc_id
                LEFT JOIN projects p ON p.id = pay.project_id
                LEFT JOIN users processor ON processor.id = pay.processed_by";
    }
}
