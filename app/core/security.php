<?php

class Security
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function cleanString(string $value): string
    {
        return trim(strip_tags($value));
    }

    public static function cleanEmail(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
    }

    public static function cleanInt(mixed $value, int $default = 0): int
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered === false ? $default : (int)$filtered;
    }

    public static function cleanFloat(mixed $value, float $default = 0.0): float
    {
        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $filtered === false ? $default : (float)$filtered;
    }

    public static function methodIs(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }

    public static function isPost(): bool
    {
        return self::methodIs('POST');
    }

    public static function isGet(): bool
    {
        return self::methodIs('GET');
    }

    public static function jsonInput(): array
    {
        static $input = null;

        if (is_array($input)) {
            return $input;
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            $input = [];
            return $input;
        }

        $decoded = json_decode($raw, true);
        $input = is_array($decoded) ? $decoded : [];
        return $input;
    }

    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }

    public static function isSafeRedirect(string $target, array $allowedHosts = []): bool
    {
        if ($target === '' || str_starts_with($target, '//')) {
            return false;
        }

        $parts = parse_url($target);
        if ($parts === false) {
            return false;
        }

        if (!isset($parts['host'])) {
            return str_starts_with($target, '/') || !preg_match('#^[a-z][a-z0-9+.-]*:#i', $target);
        }

        return in_array(strtolower($parts['host']), array_map('strtolower', $allowedHosts), true);
    }

    public static function extensionAllowed(string $filename, array $allowed): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $ext !== '' && in_array($ext, array_map('strtolower', $allowed), true);
    }
}
