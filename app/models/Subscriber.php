<?php
class Subscriber extends Model
{
    protected static string $table = 'subscribers';
    // Columns: id, email, name, status, subscribed_at, ip
    // Status: active, unsubscribed
}
