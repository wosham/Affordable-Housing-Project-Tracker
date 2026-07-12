<?php

class Url
{
    public static function basePath(): string
    {
        $config = $GLOBALS['app_config'] ?? require dirname(__DIR__) . '/config/app.php';
        return rtrim($config['base_url'] ?? '', '/');
    }

    public static function isAbsolute(string $url): bool
    {
        return preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $url) === 1 || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1;
    }

    public static function normalizePath(string $path = ''): string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || $path === '/') {
            return '';
        }

        $parts = parse_url($path);
        $cleanPath = ltrim((string)($parts['path'] ?? $path), '/');
        $cleanPath = preg_replace('#/+#', '/', $cleanPath) ?? $cleanPath;

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return $cleanPath . $query . $fragment;
    }

    public static function canonicalBase(): string
    {
        $config = $GLOBALS['app_config'] ?? require dirname(__DIR__) . '/config/app.php';
        $configured = (string)(getenv('APP_CANONICAL_URL') ?: getenv('PUBLIC_SITE_URL') ?: ($config['canonical_url'] ?? ''));
        $base = trim($configured) !== '' ? $configured : self::basePath();

        if (!self::isAbsolute($base)) {
            $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
                || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
            $scheme = $https ? 'https' : 'http';
            $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
            $base = $scheme . '://' . $host . '/' . ltrim($base, '/');
        }

        return rtrim($base, '/');
    }

    public static function canonical(string $path = ''): string
    {
        if (self::isAbsolute($path)) {
            return $path;
        }

        $path = self::normalizePath($path);
        return self::canonicalBase() . ($path === '' ? '/' : '/' . $path);
    }

    public static function to(string $path = ''): string
    {
        if (self::isAbsolute($path)) {
            return $path;
        }

        $path = self::normalizePath($path);
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
        $targetPath = parse_url(self::to($path), PHP_URL_PATH) ?: self::to($path);
        return rtrim(self::currentPath(), '/') === rtrim((string)$targetPath, '/');
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
