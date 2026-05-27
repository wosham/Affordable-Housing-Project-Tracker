<?php

class Constituency extends Model
{
    protected static string $table = 'constituencies';

    public static function withProjectCounts(): array
    {
        return Database::fetchAll("
            SELECT
                c.*,
                COUNT(p.id) AS live_project_count,
                COALESCE(SUM(p.units), 0) AS live_total_units,
                COALESCE(AVG(p.pct_complete), 0) AS live_avg_completion
            FROM constituencies c
            LEFT JOIN projects p ON p.constituency_id = c.id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
    }
}
