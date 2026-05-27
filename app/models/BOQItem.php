<?php
class BOQItem extends Model
{
    protected static string $table = 'boq_items';
    // Columns: id, project_id, section, item_no, description, unit, quantity,
    //          rate, amount, certified_qty, paid_qty, status

    public static function forProject(int $projectId): array
    {
        // TODO: Phase 4 — SELECT WHERE project_id = :id ORDER BY section, item_no
        return [];
    }
}
