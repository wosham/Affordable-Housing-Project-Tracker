<?php
class ProjectChannel extends Model
{
    protected static string $table = 'project_channels';
    // Columns: id, project_id, name, description, created_by
    // Auto-created when a project is created; all assigned users join automatically
}
