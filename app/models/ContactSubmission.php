<?php
class ContactSubmission extends Model
{
    protected static string $table = 'contact_submissions';
    // Columns: id, name, email, phone, subject, message, attachment_path,
    //          is_read, status, assigned_to, replied_at, created_at
}
