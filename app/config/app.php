<?php

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    return $value === false || $value === "" ? $default : $value;
};

$bool = static function (mixed $value, bool $default = false): bool {
    if ($value === null || $value === "") {
        return $default;
    }

    return in_array(
        strtolower((string) $value),
        ["1", "true", "yes", "on"],
        true,
    );
};

$environment = strtolower(trim((string) $env("APP_ENV", "local")));
$isProduction = in_array($environment, ["production", "prod"], true);

$appUrl = rtrim(
    (string) $env("APP_URL", "/Trans-Nzoia-Affordable-Housing"),
    "/",
);
$configuredAllowedHosts = array_filter(
    array_map(
        "trim",
        explode(
            ",",
            (string) $env("ALLOWED_REDIRECT_HOSTS", "localhost,127.0.0.1"),
        ),
    ),
);
$derivedAllowedHosts = [];

$appHost = parse_url($appUrl, PHP_URL_HOST);
if (is_string($appHost) && $appHost !== "") {
    $derivedAllowedHosts[] = $appHost;
}

$requestHost =
    (string) ($_SERVER["HTTP_HOST"] ?? ($_SERVER["SERVER_NAME"] ?? ""));
$requestHost = preg_replace('/:\d+$/', "", $requestHost) ?: "";
if ($requestHost !== "") {
    $derivedAllowedHosts[] = $requestHost;
}

$allowedRedirectHosts = array_values(
    array_unique(
        array_filter(
            array_map(
                static fn(string $host): string => strtolower(trim($host)),
                array_merge($configuredAllowedHosts, $derivedAllowedHosts),
            ),
        ),
    ),
);

return [
    "name" => (string) $env("APP_NAME", "Trans-Nzoia AHP Tracker"),
    "env" => $environment,
    "debug" => !$isProduction && $bool($env("APP_DEBUG", "false"), false),
    "timezone" => (string) $env("APP_TIMEZONE", "Africa/Nairobi"),
    "base_path" => dirname(__DIR__, 2),
    "base_url" => $appUrl,
    "canonical_url" => rtrim(
        (string) $env("APP_CANONICAL_URL", $env("PUBLIC_SITE_URL", $appUrl)),
        "/",
    ),
    "session" => [
        "name" => "tnah_session",
        "lifetime" => 7200,
        "save_path" => dirname(__DIR__, 2) . "/app/storage/sessions",
        "secure" => $isProduction || $bool($env("SESSION_SECURE", "false"), false),
        "httponly" => true,
        "samesite" => (string) $env("SESSION_SAMESITE", "Lax"),
    ],
    "csrf" => [
        "token_name" => "_csrf_token",
        "session_key" => "_csrf_tokens",
        "ttl" => 7200,
    ],
    "security" => [
        "allowed_redirect_hosts" => $allowedRedirectHosts,
        "max_upload_bytes" => 52428800,
        "allowed_upload_extensions" => [
            "pdf",
            "jpg",
            "jpeg",
            "png",
            "webp",
            "gif",
            "mp4",
            "webm",
            "mov",
        ],
    ],
];
