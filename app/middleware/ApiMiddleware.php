<?php

class ApiMiddleware
{
    private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public static function handle(array $options = []): void
    {
        self::jsonHeader();

        $options = array_merge([
            'auth' => false,
            'roles' => [],
            'csrf' => true,
            'csrf_form' => 'default',
            'methods' => [],
        ], $options);

        if ($options['methods'] !== []) {
            self::method($options['methods']);
        }

        if ((bool)$options['csrf'] && self::isStateChanging()) {
            CsrfMiddleware::handleApi((string)$options['csrf_form']);
        }

        $roles = $options['roles'];
        if ($roles !== [] && $roles !== '' && $roles !== null) {
            RoleMiddleware::handleApi($roles);
            return;
        }

        if ((bool)$options['auth']) {
            AuthMiddleware::handleApi();
        }
    }

    public static function method(array|string $methods): void
    {
        $allowed = array_map('strtoupper', (array)$methods);
        $current = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($current, $allowed, true)) {
            return;
        }

        header('Allow: ' . implode(', ', $allowed));
        Response::json([
            'success' => false,
            'message' => 'Method not allowed.',
            'data' => new stdClass(),
            'errors' => new stdClass(),
            'allowed_methods' => $allowed,
            'meta' => [
                'request_id' => class_exists('PublicApi') ? PublicApi::requestId() : substr(sha1(uniqid('', true)), 0, 16),
                'generated_at' => date(DATE_ATOM),
                'allowed_methods' => $allowed,
            ],
        ], 405);
    }

    public static function isStateChanging(): bool
    {
        return in_array(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), self::STATE_CHANGING_METHODS, true);
    }

    public static function jsonHeader(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
    }
}
