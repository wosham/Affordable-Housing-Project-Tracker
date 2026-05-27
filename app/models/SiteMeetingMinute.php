<?php
class SiteMeetingMinute extends Model
{
    protected static string $table = 'site_meeting_minutes';
    // Columns: id, project_id, meeting_date, venue, attendees_json, agenda,
    //          minutes_text, action_items_json, document_path, recorded_by
}
