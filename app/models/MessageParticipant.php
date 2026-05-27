<?php
class MessageParticipant extends Model
{
    protected static string $table = 'message_participants';
    // Columns: id, thread_id, user_id, joined_at, is_admin
}
