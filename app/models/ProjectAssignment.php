<?php

class ProjectAssignment extends Model
{
    protected static string $table = 'project_assignments';

    public static function usersForProject(int $projectId): array
    {
        return Database::fetchAll(
            'SELECT pa.*, u.first_name, u.last_name, u.email, u.phone, u.avatar, u.status AS user_status,
                    r.slug AS role_slug, r.name AS role_name,
                    CONCAT(u.first_name, " ", u.last_name) AS user_name
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE pa.project_id = ?
             ORDER BY r.slug ASC, u.first_name ASC, u.last_name ASC',
            [$projectId]
        );
    }

    public static function projectsForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT pa.*, p.name AS project_name, p.slug AS project_slug, p.status AS project_status
             FROM project_assignments pa
             JOIN projects p ON p.id = pa.project_id
             WHERE pa.user_id = ?
             ORDER BY p.name ASC',
            [$userId]
        );
    }
}
