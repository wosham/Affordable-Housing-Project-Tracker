<?php
class Document extends Model
{
    protected static string $table = 'documents';
    // Columns: id, project_id, uploaded_by, category, filename, original_name,
    //          size, version, description, is_confidential, created_at
    // Categories: contract, drawing, spec, report, correspondence, shop-drawing, quality-test
}
