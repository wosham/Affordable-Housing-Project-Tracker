<?php
class NonConformanceReport extends Model
{
    protected static string $table = 'non_conformance_reports';
    // Columns: id, project_id, raised_by, raised_date, description, severity,
    //          root_cause, corrective_action, closed_by, closed_date, status
}
