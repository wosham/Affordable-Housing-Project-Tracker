<?php

class Milestone extends Model
{
    protected static string $table = 'milestones';

    public static function forProject(int $projectId): array
    {
        return Database::fetchAll(
            'SELECT * FROM milestones WHERE project_id = ? ORDER BY sequence ASC, id ASC',
            [$projectId]
        );
    }
}
