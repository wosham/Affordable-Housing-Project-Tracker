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

    public static function methodIs(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
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
