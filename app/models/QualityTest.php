<?php
class QualityTest extends Model
{
    protected static string $table = 'quality_tests';
    // Columns: id, project_id, test_type, test_date, location_on_site,
    //          result, pass_fail, lab_ref, tested_by, document_path
}
