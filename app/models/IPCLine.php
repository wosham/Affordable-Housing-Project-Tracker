<?php
class IPCLine extends Model
{
    protected static string $table = 'ipc_lines';

    public static function forIPC(int $ipcId): array
    {
        return Database::fetchAll("
            SELECT il.*, bi.item_no AS item_code, bi.unit
            FROM ipc_lines il
            LEFT JOIN boq_items bi ON bi.id = il.boq_item_id
            WHERE il.ipc_id = ?
            ORDER BY il.id ASC
        ", [$ipcId]);
    }

    public static function totalsForIPC(int $ipcId): array
    {
        return Database::fetch("
            SELECT
                COUNT(*) AS line_count,
                COALESCE(SUM(amount), 0) AS total_amount,
                COALESCE(SUM(qty_this_period), 0) AS total_qty_this_period,
                COALESCE(SUM(cumulative_qty), 0) AS total_cumulative_qty
            FROM ipc_lines
            WHERE ipc_id = ?
        ", [$ipcId]) ?: [];
    }
}
