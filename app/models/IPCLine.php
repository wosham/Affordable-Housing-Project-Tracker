<?php
class IPCLine extends Model
{
    protected static string $table = 'ipc_lines';
    // Columns: id, ipc_id, boq_item_id, description, qty_this_period,
    //          cumulative_qty, rate, amount
}
