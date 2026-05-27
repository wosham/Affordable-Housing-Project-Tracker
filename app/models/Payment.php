<?php
class Payment extends Model
{
    protected static string $table = 'payments';
    // Columns: id, ipc_id, project_id, amount, payment_date,
    //          reference_no, bank, processed_by, receipt_path
}
