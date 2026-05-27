<?php
class PasswordReset extends Model
{
    protected static string $table = 'password_resets';
    // Columns: id, user_id, token, expires_at, used
}
