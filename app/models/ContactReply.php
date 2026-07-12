<?php

class ContactReply extends Model
{
    protected static string $table = 'contact_replies';

    public static function forSubmission(int $submissionId): array
    {
        return Database::fetchAll(
            "SELECT
                cr.*,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS sender_name,
                r.slug AS sender_role
             FROM contact_replies cr
             LEFT JOIN users u ON u.id = cr.sender_user_id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE cr.contact_submission_id = ?
             ORDER BY cr.created_at ASC, cr.id ASC",
            [$submissionId]
        );
    }

    public static function record(array $data): int
    {
        return (int)self::create([
            'contact_submission_id' => (int)$data['contact_submission_id'],
            'sender_user_id' => (int)$data['sender_user_id'],
            'recipient_email' => trim((string)$data['recipient_email']),
            'subject' => trim((string)$data['subject']),
            'body' => trim((string)$data['body']),
            'delivery_status' => (string)($data['delivery_status'] ?? 'pending'),
            'provider_message_id' => $data['provider_message_id'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ]);
    }

    public static function markDelivery(int $id, string $status, ?string $providerId = null, ?string $error = null): bool
    {
        return self::update($id, [
            'delivery_status' => $status,
            'provider_message_id' => $providerId,
            'error_message' => $error,
        ]);
    }

    public static function payload(array $row): array
    {
        $senderName = trim((string)($row['sender_name'] ?? ''));

        return [
            'id' => (int)($row['id'] ?? 0),
            'subject' => (string)($row['subject'] ?? ''),
            'body' => (string)($row['body'] ?? ''),
            'recipientEmail' => (string)($row['recipient_email'] ?? ''),
            'deliveryStatus' => (string)($row['delivery_status'] ?? ''),
            'providerMessageId' => (string)($row['provider_message_id'] ?? ''),
            'errorMessage' => (string)($row['error_message'] ?? ''),
            'senderName' => $senderName !== '' ? $senderName : 'System',
            'senderRole' => (string)($row['sender_role'] ?? ''),
            'createdAt' => (string)($row['created_at'] ?? ''),
            'createdAtFormatted' => format_datetime($row['created_at'] ?? ''),
        ];
    }
}
