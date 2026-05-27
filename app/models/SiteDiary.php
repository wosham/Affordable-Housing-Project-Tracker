<?php
class SiteDiary extends Model
{
    protected static string $table = 'site_diaries';
    // Columns: id, project_id, diary_date, weather_id, work_done, issues_raised,
    //          next_day_plan, recorded_by, approved_by, approved_at

    public static function todayForProject(int $projectId): ?array
    {
        // TODO: Phase 5 — SELECT WHERE project_id AND diary_date = TODAY
        return null;
    }
}
