<?php

class PasswordReset extends Model
{
    protected static string $table = 'password_resets';

    public static function createForUser(array $user, int $ttlMinutes = 30): array
    {
        $userId = (int)($user['id'] ?? 0);
        $email = strtolower(trim((string)($user['email'] ?? '')));
        if ($userId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid user is required.');
        }

        self::invalidateForUser($userId);
        $token = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $hash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + max(5, $ttlMinutes) * 60);

        $id = (int)self::create([
            'user_id' => $userId,
            'email' => $email,
            'token' => '',
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
            'used' => 0,
            'created_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            'created_user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['id' => $id, 'token' => $token, 'expires_at' => $expiresAt];
    }

    public static function validByToken(string $token): ?array
    {
        $hash = hash('sha256', trim($token));
        return Database::fetch("
            SELECT pr.*, u.email, u.first_name, u.last_name, u.status
            FROM password_resets pr
            INNER JOIN users u ON u.id = pr.user_id
            WHERE pr.token_hash = ?
              AND pr.used = 0
              AND pr.used_at IS NULL
              AND pr.expires_at >= NOW()
              AND u.status = 'active'
            LIMIT 1
        ", [$hash]);
    }

    public static function markUsed(int $id): void
    {
        Database::query('UPDATE password_resets SET used = 1, used_at = NOW() WHERE id = ?', [$id]);
    }

    public static function invalidateForUser(int $userId): void
    {
        Database::query('UPDATE password_resets SET used = 1, used_at = COALESCE(used_at, NOW()) WHERE user_id = ? AND used = 0', [$userId]);
    }

    public static function requestCount(string $email, string $ip, int $minutes = 10): int
    {
        $row = Database::fetch("
            SELECT COUNT(*) AS total
            FROM password_resets
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
              AND (email = ? OR created_ip = ?)
        ", [$minutes, strtolower(trim($email)), $ip]);

        return (int)($row['total'] ?? 0);
    }
}
