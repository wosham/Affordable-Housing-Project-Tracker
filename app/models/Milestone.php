<?php

class Milestone extends Model
{
    protected static string $table = 'milestones';

    public const STATUSES = ['pending', 'current', 'done'];

    public static function forProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Database::fetchAll(
            'SELECT * FROM milestones WHERE project_id = ? ORDER BY sequence ASC, id ASC',
            [$projectId]
        );
    }

    public static function publicForProject(int $projectId): array
    {
        $rows = self::forProject($projectId);
        return array_map(static function (array $row): array {
            $status = (string)($row['status'] ?? 'pending');
            return [
                'id' => (int)($row['id'] ?? 0),
                'label' => (string)($row['label'] ?? ''),
                'status' => in_array($status, self::STATUSES, true) ? $status : 'pending',
                'target_date' => (string)($row['target_date'] ?? ''),
                'actual_date' => (string)($row['actual_date'] ?? ''),
                'sequence' => (int)($row['sequence'] ?? 0),
                'progress_percent' => (float)($row['progress_percent'] ?? 0),
            ];
        }, $rows);
    }

    public static function findDetailed(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return Database::fetch(
            "SELECT m.*, p.name AS project_name
             FROM milestones m
             LEFT JOIN projects p ON p.id = m.project_id
             WHERE m.id = ?
             LIMIT 1",
            [$id]
        );
    }

    public static function setStatus(int $id, string $status, ?int $actorId = null): bool
    {
        $status = in_array($status, self::STATUSES, true) ? $status : 'pending';
        $data = [
            'status' => $status,
            'updated_by' => $actorId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($status === 'done') {
            $data['actual_date'] = date('Y-m-d');
            $data['completed_by'] = $actorId;
            $data['progress_percent'] = 100;
        }

        return self::update($id, $data);
    }

    public static function reorder(int $projectId, array $orderedIds): void
    {
        $seq = 1;
        foreach ($orderedIds as $id) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }
            Database::query(
                'UPDATE milestones SET sequence = ?, updated_at = NOW() WHERE id = ? AND project_id = ?',
                [$seq, $id, $projectId]
            );
            $seq++;
        }
    }

    public static function countForProject(int $projectId): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0) AS done,
                    COALESCE(SUM(CASE WHEN status = 'current' THEN 1 ELSE 0 END), 0) AS current_count,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending
             FROM milestones
             WHERE project_id = ?",
            [$projectId]
        ) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'done' => (int)($row['done'] ?? 0),
            'current' => (int)($row['current_count'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
        ];
    }
}
