<?php

class Auth
{
    private const SESSION_KEY = 'auth_user';

    public static function check(): bool
    {
        return is_array(Session::get(self::SESSION_KEY));
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
    }

    public static function refresh(array $user): void
    {
        if (!self::check()) {
            return;
        }

        $current = self::user() ?? [];
        self::login(array_merge($current, $user));
    }

    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
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
