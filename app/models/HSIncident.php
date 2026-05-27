<?php
class HSIncident extends Model
{
    protected static string $table = 'hs_incidents';
    // Columns: id, project_id, incident_date, incident_type, description,
    //          persons_involved, cause, corrective_action, reported_by, severity
    // incident_type: near-miss, first-aid, medical, fatality
    // severity: low, medium, high, critical
}
