<?php

class PublicApi
{
    private static ?string $requestId = null;

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            try {
                self::$requestId = bin2hex(random_bytes(8));
            } catch (Throwable) {
                self::$requestId = substr(sha1(uniqid('', true)), 0, 16);
            }
        }

        return self::$requestId;
    }

    public static function ok(array $data = [], string $message = '', array $meta = [], int $status = 200): never
    {
        self::send($status, [
            'success' => true,
            'message' => $message,
            'data' => $data !== [] ? $data : new stdClass(),
            'errors' => new stdClass(),
            'meta' => self::meta($meta),
        ]);
    }

    public static function fail(string $message, int $status = 400, array $errors = [], array $meta = []): never
    {
        self::send($status, [
            'success' => false,
            'message' => $message,
            'data' => new stdClass(),
            'errors' => $errors !== [] ? $errors : new stdClass(),
            'meta' => self::meta($meta),
        ]);
    }

    public static function validation(array $errors, string $message = 'Please correct the highlighted fields.'): never
    {
        self::fail($message, 422, $errors);
    }

    public static function fakeSuccess(string $message = 'Request received.'): never
    {
        self::ok([], $message);
    }

    public static function honeypot(array $fields = ['website', '_gotcha']): bool
    {
        foreach ($fields as $field) {
            if (trim((string)($_POST[$field] ?? '')) !== '') {
                self::log('Public API honeypot triggered', ['field' => $field]);
                return true;
            }
        }

        return false;
    }

    public static function clientIp(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
    }

    public static function userAgent(): string
    {
        return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function sourceUrl(string $fallback): ?string
    {
        $source = trim((string)($_SERVER['HTTP_REFERER'] ?? $fallback));
        if ($source === '') {
            return null;
        }

        return substr($source, 0, 500);
    }

    public static function sessionRateLimit(string $key, int $limit, int $seconds, string $message = 'Please wait a few minutes before trying again.'): void
    {
        $limit = max(1, $limit);
        $seconds = max(1, $seconds);
        $bucketKey = '_public_api_rate_' . preg_replace('/[^a-z0-9_.-]+/i', '_', $key);
        $now = time();
        $bucket = Session::get($bucketKey, []);
        $bucket = is_array($bucket) ? array_values(array_filter($bucket, static fn ($stamp): bool => (int)$stamp >= ($now - $seconds))) : [];

        if (count($bucket) >= $limit) {
            self::fail($message, 429, [], ['retry_after' => $seconds]);
        }

        $bucket[] = $now;
        Session::set($bucketKey, $bucket);
    }

    public static function databaseRateLimit(string $table, string $ipColumn, string $dateColumn, int $limit, int $minutes, string $message): void
    {
        $ip = self::clientIp();
        if ($ip === '') {
            return;
        }

        try {
            $recent = Database::fetch(
                "SELECT COUNT(*) AS total FROM {$table} WHERE {$ipColumn} = ? AND {$dateColumn} >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)",
                [$ip]
            );
            if ((int)($recent['total'] ?? 0) >= max(1, $limit)) {
                self::fail($message, 429, [], ['retry_after' => max(1, $minutes) * 60]);
            }
        } catch (Throwable $e) {
            self::log('Public API rate-limit check failed', ['table' => $table, 'error' => $e->getMessage()]);
        }
    }

    public static function safeSourcePath(?string $source): ?string
    {
        $source = trim((string)$source);
        if ($source === '') {
            return null;
        }

        $parts = parse_url($source);
        if ($parts === false) {
            return null;
        }

        $path = (string)($parts['path'] ?? '');
        $query = isset($parts['query']) ? '?' . (string)$parts['query'] : '';
        $basePath = parse_url(Url::basePath(), PHP_URL_PATH) ?: '';
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = ltrim($path, '/');

        return substr(($path !== '' ? $path : 'index.php') . $query, 0, 500);
    }

    public static function log(string $message, array $context = []): void
    {
        $context['request_id'] = self::requestId();
        if (class_exists('Logger')) {
            Logger::error($message, $context);
            return;
        }
        error_log($message . ' ' . json_encode($context));
    }

    private static function meta(array $extra = []): array
    {
        return array_merge([
            'request_id' => self::requestId(),
            'generated_at' => date(DATE_ATOM),
        ], $extra);
    }

    private static function send(int $status, array $payload): never
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-Request-ID: ' . self::requestId());
        }

        Response::json($payload, $status);
    }
}
