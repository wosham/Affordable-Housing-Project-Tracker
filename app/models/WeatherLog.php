<?php
class WeatherLog extends Model
{
    protected static string $table = 'weather_logs';
    // Columns: id, project_id, log_date, morning_condition, afternoon_condition,
    //          rainfall_mm, working_hours, remarks, recorded_by
}
