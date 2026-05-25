<?php

class Guard
{
    public static function guest(string $redirectTo = '../auth/dashboard.php'): void
    {
        if (Auth::check()) {
            Response::redirect($redirectTo);
        }
    }

    public static function auth(string $redirectTo = 'login.php'): void
    {
        if (!Auth::check()) {
            Response::redirect($redirectTo);
        }
    }

    public static function role(string|array $roles, string $redirectTo = 'unauthorised.php'): void
    {
        self::auth();

        if (!Auth::hasRole($roles)) {
            Response::redirect($redirectTo);
        }
    }
}
