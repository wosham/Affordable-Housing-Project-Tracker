<?php

class RoleMiddleware
{
    public static function handle(string|array $roles, ?string $redirectTo = null): void
    {
        AuthMiddleware::handle();

        if (Auth::hasRole($roles)) {
            return;
        }

        Response::redirect($redirectTo ?? Url::to('admin/auth/unauthorised.php'));
    }

    public static function handleApi(string|array $roles): void
    {
        AuthMiddleware::handleApi();

        if (Auth::hasRole($roles)) {
            return;
        }

        Response::json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
