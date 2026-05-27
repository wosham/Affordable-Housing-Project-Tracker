<?php
class MaterialDelivery extends Model
{
    protected static string $table = 'material_deliveries';
    // Columns: id, project_id, material, supplier, delivery_date, quantity,
    //          unit, delivery_note_no, received_by, condition, approved
}
