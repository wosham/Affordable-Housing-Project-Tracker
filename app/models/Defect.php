<?php
class Defect extends Model
{
    protected static string $table = 'defects';
    // Columns: id, project_id, raised_by, raised_date, location, description,
    //          severity, photo_path, assigned_to, due_date, closed_date, status
}
