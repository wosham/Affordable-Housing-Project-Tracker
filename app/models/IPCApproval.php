<?php

class IPCApproval extends Model
{
    protected static string $table = 'ipc_approvals';

    public static function forIPC(int $ipcId): array
    {
        return Database::fetchAll("
            SELECT
                ia.*,
                CONCAT(u.first_name, ' ', u.last_name) AS actor_name,
                u.email AS actor_email,
                u.avatar AS actor_avatar,
                r.name AS actor_role,
                r.slug AS actor_role_slug
            FROM ipc_approvals ia
            INNER JOIN users u ON u.id = ia.action_by
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE ia.ipc_id = ?
            ORDER BY ia.actioned_at ASC, ia.id ASC
        ", [$ipcId]);
    }

    public static function latestForIPC(int $ipcId): ?array
    {
        return Database::fetch("
            SELECT ia.*, CONCAT(u.first_name, ' ', u.last_name) AS actor_name, r.slug AS actor_role_slug
            FROM ipc_approvals ia
            INNER JOIN users u ON u.id = ia.action_by
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE ia.ipc_id = ?
            ORDER BY ia.actioned_at DESC, ia.id DESC
            LIMIT 1
        ", [$ipcId]);
    }

    public static function timeline(int $ipcId): array
    {
        return self::forIPC($ipcId);
    }

    public static function record(int $ipcId, int $step, int $actorId, string $action, string $comments = ''): int
    {
        return (int)self::create([
            'ipc_id' => $ipcId,
            'step' => $step,
            'action_by' => $actorId,
            'action' => $action,
            'comments' => $comments !== '' ? $comments : null,
            'actioned_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
