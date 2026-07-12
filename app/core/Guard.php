<?php

class Guard
{
    public static function guest(?string $redirectTo = null): void
    {
        if (Auth::check()) {
            Response::redirect($redirectTo ?? Url::to('admin/index.php'));
        }
    }

    public static function auth(?string $redirectTo = null): void
    {
        if (!Auth::check()) {
            Response::redirect($redirectTo ?? Url::to('admin/login.php'));
        }
    }

    public static function role(string|array $roles, ?string $redirectTo = null): void
    {
        self::auth();

        if (!Auth::hasRole($roles)) {
            Response::redirect($redirectTo ?? Url::to('admin/auth/unauthorised.php'));
        }
    }

    public static function exactRole(string $role, ?string $redirectTo = null): void
    {
        self::auth();

        if ((string)Auth::role() !== $role) {
            Response::redirect($redirectTo ?? Url::to('admin/auth/unauthorised.php'));
        }
    }
}
