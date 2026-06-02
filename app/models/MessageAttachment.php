<?php

class MessageAttachment extends Model
{
    protected static string $table = 'message_attachments';

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'];

    public static function createPending(array $file, int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Attachment upload failed.');
        }

        $original = basename((string)($file['name'] ?? 'attachment'));
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('This file type is not allowed.');
        }

        $maxBytes = max(1, SystemConfig::int('security.max_upload_mb', 20)) * 1024 * 1024;
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('Attachment is too large.');
        }

        $folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure-uploads' . DIRECTORY_SEPARATOR . 'message-attachments';
        if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new RuntimeException('Attachment storage is not available.');
        }

        $token = bin2hex(random_bytes(16));
        $filename = 'msg_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $folder . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            throw new RuntimeException('Attachment could not be saved.');
        }

        $mime = mime_content_type($target) ?: (string)($file['type'] ?? 'application/octet-stream');
        $path = 'secure-uploads/message-attachments/' . $filename;
        $id = (int)self::create([
            'message_id' => null,
            'uploaded_by' => $userId,
            'filename' => $filename,
            'original_name' => substr($original, 0, 255),
            'size' => $size,
            'type' => $mime,
            'mime_type' => $mime,
            'path' => $path,
            'checksum' => hash_file('sha256', $target),
            'upload_token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return self::payload(self::find($id) ?: []);
    }

    public static function attachTokens(int $messageId, int $userId, array $tokens): void
    {
        $tokens = array_values(array_filter(array_unique(array_map('strval', $tokens))));
        foreach ($tokens as $token) {
            Database::query(
                'UPDATE message_attachments SET message_id = ?, upload_token = NULL WHERE upload_token = ? AND uploaded_by = ? AND message_id IS NULL',
                [$messageId, $token, $userId]
            );
        }
    }

    public static function forMessages(array $messageIds): array
    {
        $messageIds = array_values(array_filter(array_map('intval', $messageIds)));
        if ($messageIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $rows = Database::fetchAll("SELECT * FROM message_attachments WHERE message_id IN ({$placeholders}) ORDER BY id ASC", $messageIds);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['message_id']][] = self::payload($row);
        }
        return $grouped;
    }

    public static function payload(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'messageId' => (int)($row['message_id'] ?? 0),
            'token' => (string)($row['upload_token'] ?? ''),
            'name' => (string)($row['original_name'] ?? $row['filename'] ?? ''),
            'size' => (int)($row['size'] ?? 0),
            'type' => (string)($row['mime_type'] ?? $row['type'] ?? ''),
            'url' => Url::to((string)($row['path'] ?? '')),
        ];
    }
}
