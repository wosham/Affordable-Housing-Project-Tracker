<?php

class AuthMiddleware
{
    public static function handle(?string $redirectTo = null): void
    {
        if (Auth::check()) {
            return;
        }

        Response::redirect($redirectTo ?? Url::to('admin/login.php'));
    }

    public static function handleApi(): void
    {
        if (Auth::check()) {
            return;
        }

        Response::json([
            'success' => false,
            'message' => 'Authentication required.',
        ], 401);
    }

    public static function check(): bool
    {
        return Auth::check();
    }
}
