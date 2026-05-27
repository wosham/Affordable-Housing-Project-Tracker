<?php
class UserSession extends Model
{
    protected static string $table = 'user_sessions';
    // Tracks online status: user_id, token, ip, last_activity, expires_at
}
