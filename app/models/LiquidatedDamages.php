<?php
class LiquidatedDamages extends Model
{
    protected static string $table = 'liquidated_damages';
    // Columns: id, project_id, rate_per_day, days_overdue, total_ld,
    //          applied_to_ipc_id, notes
}
