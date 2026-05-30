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
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
            return $path;
        }

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

    public static function currentUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? self::basePath();
        return $uri === '' ? self::basePath() : $uri;
    }

    public static function isActive(string $path): bool
    {
        return rtrim(self::currentPath(), '/') === rtrim(self::to($path), '/');
    }

    public static function query(array $params = [], ?string $path = null): string
    {
        $base = $path === null ? self::currentPath() : self::to($path);
        $current = [];

        if ($path === null) {
            parse_str((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY), $current);
        }

        $query = array_merge($current, $params);
        $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '');

        return $base . ($query === [] ? '' : '?' . http_build_query($query));
    }
}
