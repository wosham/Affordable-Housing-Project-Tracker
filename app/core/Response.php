<?php

class Response
{
    public static function redirect(string $target, int $status = 302): never
    {
        $config = $GLOBALS['app_config'] ?? require dirname(__DIR__) . '/config/app.php';
        $allowed = $config['security']['allowed_redirect_hosts'] ?? [];

        if (!Security::isSafeRedirect($target, $allowed)) {
            $target = Url::to('index.php');
        }

        header('Location: ' . $target, true, $status);
        exit;
    }

    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function abort(int $status = 404, string $message = ''): never
    {
        http_response_code($status);
        $message = $message !== '' ? $message : 'Request could not be completed.';
        echo Security::e($message);
        exit;
    }
}
