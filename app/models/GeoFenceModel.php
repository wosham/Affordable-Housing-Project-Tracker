<?php

// Note: Named GeoFenceModel to avoid collision with GeoFence utility class in core.
class GeoFenceModel extends Model
{
    protected static string $table = 'geo_fences';

    public static function forProject(int $projectId): ?array
    {
        return Database::fetch(
            'SELECT gf.*, CONCAT(v.first_name, " ", v.last_name) AS verified_by_name
             FROM geo_fences gf
             LEFT JOIN users v ON v.id = gf.verified_by
             WHERE gf.project_id = ?
             ORDER BY gf.id DESC
             LIMIT 1',
            [$projectId]
        );
    }
}
