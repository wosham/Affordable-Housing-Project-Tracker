<?php
class ProgrammeTask extends Model
{
    protected static string $table = 'programme_tasks';
    // Columns: id, project_id, task_name, start_date, end_date, planned_start,
    //          planned_end, pct_complete, depends_on_task_id, assigned_to, status

    public static function forProject(int $projectId): array
    {
        // TODO: Phase 5 — SELECT WHERE project_id ORDER BY planned_start for Gantt render
        return [];
    }
}
