<?php

class Url
{
    public static function basePath(): string
    {
        $config = $GLOBALS['app_config'] ?? require dirname(__DIR__) . '/config/app.php';
        return rtrim($config['base_url'] ?? '', '/');
    }

    public static function to(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return self::basePath() . ($path === '' ? '' : '/' . $path);
    }

    public static function asset(string $path): string
    {
        return self::to($path);
    }

    public static function currentPath(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }
}
