<?php
// Logger — writes to audit_logs table and optionally to file
class Logger
{
    public static function log(string $action, string $module, int $targetId = 0, array $details = []): void
    {
        // TODO: Phase 1 — Insert into audit_logs: user_id, action, module, target_id, details_json, ip, user_agent
    }

    public static function error(string $message, array $context = []): void
    {
        // TODO: Phase 1 — Write to app/storage/logs/error.log
    }

    public static function info(string $message, array $context = []): void
    {
        // TODO: Phase 1 — Write to app/storage/logs/app.log
    }
}
