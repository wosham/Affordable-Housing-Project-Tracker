<?php
class ShopDrawing extends Model
{
    protected static string $table = 'shop_drawings';
    // Columns: id, project_id, drawing_no, title, submitted_by, submitted_date,
    //          revision, status, reviewed_by, review_date, document_path
    // Status: under-review, approved, rejected, resubmit
}
