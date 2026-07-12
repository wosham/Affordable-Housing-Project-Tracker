<?php

class Auth
{
    private const SESSION_KEY = 'auth_user';
    private static bool $validated = false;

    public static function check(): bool
    {
        $user = Session::get(self::SESSION_KEY);
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        if (self::$validated) {
            return true;
        }

        try {
            if (!UserSession::currentIsValid((int)$user['id'])) {
                self::logout(false);
                return false;
            }
        } catch (Throwable) {
            // Session validation must fail closed when its backing store is unavailable.
            self::logout(false);
            return false;
        }

        self::$validated = true;
        return true;
    }

    public static function user(): ?array
    {
        $user = Session::get(self::SESSION_KEY);
        return is_array($user) ? $user : null;
    }

    public static function id(): int|string|null
    {
        return self::user()['id'] ?? null;
    }

    public static function login(array $user): void
    {
        $firstName = $user['first_name'] ?? '';
        $lastName = $user['last_name'] ?? '';
        $name = trim((string)($user['name'] ?? trim($firstName . ' ' . $lastName)));

        Session::regenerate();
        Session::set(self::SESSION_KEY, [
            'id' => $user['id'] ?? null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $name,
            'email' => $user['email'] ?? '',
            'avatar' => $user['avatar'] ?? '',
            'job_title' => $user['job_title'] ?? '',
            'role' => $user['role'] ?? 'staff',
            'role_name' => $user['role_name'] ?? $user['role'] ?? 'staff',
        ]);
        self::$validated = true;
        UserSession::startForUser((int)($user['id'] ?? 0));
    }

    public static function refresh(array $user): void
    {
        if (!self::check()) {
            return;
        }

        $current = self::user() ?? [];
        self::login(array_merge($current, $user));
    }

    public static function logout(bool $revoke = true): void
    {
        if ($revoke) {
            try {
                UserSession::revokeCurrent();
            } catch (Throwable) {
            }
        }
        Session::remove(self::SESSION_KEY);
        self::$validated = false;
        Session::regenerate();
    }

    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user['role'] ?? '', $roles, true);
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function name(): string
    {
        $user = self::user();
        return is_array($user) ? (string)($user['name'] ?? '') : '';
    }

    public static function isGuest(): bool
    {
        return !self::check();
    }
}
