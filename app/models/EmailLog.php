<?php

class EmailLog extends Model
{
    protected static string $table = 'email_logs';

    public static function record(array $data): int
    {
        try {
            return (int)self::create([
                'provider' => (string)($data['provider'] ?? 'resend'),
                'provider_message_id' => $data['provider_message_id'] ?? null,
                'recipient_email' => (string)($data['recipient_email'] ?? ''),
                'recipient_user_id' => !empty($data['recipient_user_id']) ? (int)$data['recipient_user_id'] : null,
                'subject' => (string)($data['subject'] ?? ''),
                'template_key' => $data['template_key'] ?? null,
                'status' => (string)($data['status'] ?? 'queued'),
                'error_message' => $data['error_message'] ?? null,
                'payload_json' => isset($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'sent_at' => ($data['status'] ?? '') === 'sent' ? date('Y-m-d H:i:s') : null,
            ]);
        } catch (Throwable) {
            return 0;
        }
    }
}
