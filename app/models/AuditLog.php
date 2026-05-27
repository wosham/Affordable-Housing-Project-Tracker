<?php
class AuditLog extends Model
{
    protected static string $table = 'audit_logs';
    // Columns: id, user_id, action, module, target_id, details_json, ip, user_agent, created_at

    public static function record(int $userId, string $action, string $module, int $targetId = 0, array $details = []): void
    {
        // TODO: Phase 1 — Insert audit log entry
    }
}
