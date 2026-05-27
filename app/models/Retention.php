<?php
class Retention extends Model
{
    protected static string $table = 'retention';
    // Columns: id, project_id, total_held, released_amount, release_date,
    //          release_reason, processed_by
}
