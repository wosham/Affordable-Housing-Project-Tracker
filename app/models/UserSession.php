<?php
class UserSession extends Model
{
    protected static string $table = 'user_sessions';

    public static function startForUser(int $userId, int $ttlSeconds = 7200): void
    {
        if ($userId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        self::ensureColumns();
        $token = self::currentToken();
        $expiresAt = date('Y-m-d H:i:s', time() + max(300, $ttlSeconds));
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null;
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null;

        Database::query(
            'INSERT INTO user_sessions (user_id, token, ip, user_agent, last_activity, expires_at, revoked_at)
             VALUES (?, ?, ?, ?, NOW(), ?, NULL)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                ip = VALUES(ip),
                user_agent = VALUES(user_agent),
                last_activity = NOW(),
                expires_at = VALUES(expires_at),
                revoked_at = NULL',
            [$userId, $token, $ip, $userAgent, $expiresAt]
        );
    }

    public static function touchCurrent(int $userId, int $ttlSeconds = 7200): void
    {
        if ($userId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        self::ensureColumns();
        Database::query(
            'UPDATE user_sessions SET last_activity = NOW(), expires_at = ? WHERE token = ? AND user_id = ? AND revoked_at IS NULL',
            [date('Y-m-d H:i:s', time() + max(300, $ttlSeconds)), self::currentToken(), $userId]
        );
    }

    public static function currentIsValid(int $userId): bool
    {
        if ($userId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        self::ensureColumns();
        $session = Database::fetch(
            'SELECT us.id, us.revoked_at, us.expires_at, u.status
             FROM user_sessions us
             INNER JOIN users u ON u.id = us.user_id
             WHERE us.token = ? AND us.user_id = ?
             LIMIT 1',
            [self::currentToken(), $userId]
        );

        if (!$session) {
            self::startForUser($userId);
            $session = Database::fetch(
                'SELECT us.id, us.revoked_at, us.expires_at, u.status
                 FROM user_sessions us
                 INNER JOIN users u ON u.id = us.user_id
                 WHERE us.token = ? AND us.user_id = ?
                 LIMIT 1',
                [self::currentToken(), $userId]
            );
        }

        if (!$session) {
            return false;
        }

        if ((string)($session['status'] ?? '') !== 'active') {
            return false;
        }

        if (!empty($session['revoked_at'])) {
            return false;
        }

        if (strtotime((string)($session['expires_at'] ?? '')) < time()) {
            return false;
        }

        self::touchCurrent($userId);
        return true;
    }

    public static function revokeCurrent(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        self::ensureColumns();
        Database::query('UPDATE user_sessions SET revoked_at = NOW() WHERE token = ?', [self::currentToken()]);
    }

    public static function revokeForUser(int $userId, ?string $exceptToken = null): int
    {
        if ($userId <= 0) {
            return 0;
        }

        self::ensureColumns();
        $bindings = [$userId];
        $exceptSql = '';
        if ($exceptToken !== null && $exceptToken !== '') {
            $exceptSql = ' AND token <> ?';
            $bindings[] = $exceptToken;
        }

        $statement = Database::query(
            "UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL{$exceptSql}",
            $bindings
        );

        return $statement->rowCount();
    }

    public static function currentToken(): string
    {
        return hash('sha256', session_id());
    }

    private static function ensureColumns(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        try {
            $columns = Database::fetchAll('SHOW COLUMNS FROM user_sessions');
            $names = array_map(static fn (array $row): string => strtolower((string)$row['Field']), $columns);

            if (!in_array('user_agent', $names, true)) {
                Database::query('ALTER TABLE user_sessions ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip');
            }
            if (!in_array('revoked_at', $names, true)) {
                Database::query('ALTER TABLE user_sessions ADD COLUMN revoked_at DATETIME NULL AFTER expires_at');
            }
            if (!in_array('created_at', $names, true)) {
                Database::query('ALTER TABLE user_sessions ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER revoked_at');
            }
        } catch (Throwable $exception) {
            Logger::error('User session schema check failed', ['error' => $exception->getMessage()]);
        }

        $ready = true;
    }
}
