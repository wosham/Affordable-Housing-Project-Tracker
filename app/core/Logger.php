<?php

class Logger
{
    /** Maximum log file size in bytes before rotation (5 MB). */
    private const MAX_LOG_BYTES = 5242880;

    /** Number of rotated log files to keep per filename. */
    private const ROTATE_KEEP = 5;

    public static function log(
        string $action,
        string $module,
        int $targetId = 0,
        array $details = [],
    ): void {
        $action = substr(
            trim($action) !== "" ? trim($action) : "event",
            0,
            100,
        );
        $module = substr(
            trim($module) !== "" ? trim($module) : "system",
            0,
            80,
        );
        $ip = substr((string) ($_SERVER["REMOTE_ADDR"] ?? ""), 0, 45) ?: null;
        $userAgent =
            substr((string) ($_SERVER["HTTP_USER_AGENT"] ?? ""), 0, 255) ?:
            null;
        $route =
            substr(
                (string) ($_SERVER["REQUEST_URI"] ??
                    ($_SERVER["SCRIPT_NAME"] ?? "cli")),
                0,
                255,
            ) ?:
            null;
        $method =
            substr(
                (string) ($_SERVER["REQUEST_METHOD"] ??
                    (PHP_SAPI === "cli" ? "CLI" : "")),
                0,
                10,
            ) ?:
            null;
        $actorRole = substr((string) (Auth::role() ?? ""), 0, 80) ?: null;
        $severity = self::severityFor($action, $module);
        $detailsJson =
            $details === []
                ? null
                : json_encode(
                    $details,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                );
        $metadata = [
            "session" =>
                session_status() === PHP_SESSION_ACTIVE ? session_id() : null,
            "referer" =>
                substr((string) ($_SERVER["HTTP_REFERER"] ?? ""), 0, 500) ?:
                null,
        ];
        $eventHash = hash(
            "sha256",
            implode("|", [
                (string) (Auth::id() ?: 0),
                $action,
                $module,
                (string) $targetId,
                (string) $ip,
                substr((string) $detailsJson, 0, 500),
            ]),
        );

        try {
            Database::query(
                'INSERT INTO audit_logs
                    (user_id, actor_role, action, module, target_id, details_json, ip, user_agent, request_method, route, severity, event_hash, metadata_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    Auth::id() ?: null,
                    $actorRole,
                    $action,
                    $module,
                    $targetId,
                    $detailsJson,
                    $ip,
                    $userAgent,
                    $method,
                    $route,
                    $severity,
                    $eventHash,
                    json_encode(
                        $metadata,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    ),
                ],
            );
        } catch (Throwable $exception) {
            try {
                Database::query(
                    'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        Auth::id() ?: null,
                        $action,
                        $module,
                        $targetId,
                        $detailsJson,
                        $ip,
                        $userAgent,
                    ],
                );
                return;
            } catch (Throwable) {
            }

            self::writeFile("error.log", "Audit log failed", [
                "error" => $exception->getMessage(),
                "action" => $action,
                "module" => $module,
                "target_id" => $targetId,
            ]);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::writeFile("error.log", $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::writeFile("app.log", $message, $context);
    }

    private static function severityFor(string $action, string $module): string
    {
        $action = strtolower($action);
        $module = strtolower($module);
        if (
            str_contains($action, "delete") ||
            str_contains($action, "reject") ||
            $action === "login_failed"
        ) {
            return "critical";
        }
        if (
            $action === "reset" ||
            str_contains($module, "settings") ||
            str_contains($action, "cancel") ||
            str_contains($action, "failed") ||
            str_contains($action, "update-status") ||
            str_contains($action, "geo")
        ) {
            return "warning";
        }
        return "info";
    }

    private static function writeFile(
        string $filename,
        string $message,
        array $context = [],
    ): void {
        try {
            $dir = dirname(__DIR__) . "/storage/logs";
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $path = $dir . "/" . $filename;

            // Rotate if the file has grown beyond the max size.
            self::rotateIfNeeded($path);

            $line = "[" . date("Y-m-d H:i:s") . "] " . $message;
            if ($context !== []) {
                $line .=
                    " " .
                    (json_encode(
                        $context,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    ) ?:
                        "");
            }

            file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
            // Logging should never interrupt a user-facing workflow.
        }
    }

    /**
     * Rotate a log file when it exceeds MAX_LOG_BYTES.
     * Keeps up to ROTATE_KEEP numbered backups (.1, .2, … .N) then discards oldest.
     */
    private static function rotateIfNeeded(string $path): void
    {
        if (!is_file($path) || filesize($path) < self::MAX_LOG_BYTES) {
            return;
        }

        // Shift existing rotated files: .4 → gone, .3 → .4, .2 → .3, .1 → .2
        for ($i = self::ROTATE_KEEP - 1; $i >= 1; $i--) {
            $old = $path . "." . $i;
            $new = $path . "." . ($i + 1);
            if (is_file($old)) {
                if ($i === self::ROTATE_KEEP - 1 && is_file($new)) {
                    @unlink($new);
                }
                @rename($old, $new);
            }
        }

        // Move current log → .1
        @rename($path, $path . ".1");
    }
}
