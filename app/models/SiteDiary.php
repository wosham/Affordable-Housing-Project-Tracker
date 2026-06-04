<?php

class SiteDiary extends Model
{
    protected static string $table = 'site_diaries';

    public static function todayForProject(int $projectId): ?array
    {
        if ($projectId <= 0) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM site_diaries WHERE project_id = ? AND diary_date = CURDATE() LIMIT 1',
            [$projectId]
        ) ?: null;
    }
}
