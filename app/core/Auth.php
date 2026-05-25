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
        Session::regenerate();
        Session::set(self::SESSION_KEY, [
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'staff',
        ]);
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
}
