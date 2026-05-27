<?php

class CsrfMiddleware
{
    private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public static function handle(string $form = 'default', bool $json = false): void
    {
        if (!self::shouldVerify()) {
            return;
        }

        if (Csrf::verify(Csrf::fromRequest(), $form)) {
            return;
        }

        if ($json) {
            Response::json([
                'success' => false,
                'message' => 'CSRF token mismatch. Please refresh the page and try again.',
            ], 419);
        }

        Response::abort(419, 'CSRF token mismatch. Please refresh the page and try again.');
    }

    public static function handleApi(string $form = 'default'): void
    {
        self::handle($form, true);
    }

    public static function shouldVerify(): bool
    {
        return in_array(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), self::STATE_CHANGING_METHODS, true);
    }
}
