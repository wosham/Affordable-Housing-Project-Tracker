<?php

class StakeholderMilestone extends Model
{
    protected static string $table = 'stakeholder_milestones';

    public static function ordered(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'm.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['group_id'] ?? '') !== '') {
            $where[] = 'm.group_id = ?';
            $params[] = (int)$filters['group_id'];
        }

        return Database::fetchAll(
            "SELECT m.*, g.name AS group_name, g.icon AS group_icon
             FROM stakeholder_milestones m
             LEFT JOIN stakeholder_groups g ON g.id = m.group_id"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY m.sort_order ASC, COALESCE(m.milestone_date, CURRENT_DATE) ASC, m.id ASC',
            $params
        );
    }

    public static function findDetailed(int $id): ?array
    {
        return self::find($id);
    }

    public static function saveMilestone(array $input, ?int $id = null): int
    {
        $data = [
            'group_id' => Security::cleanInt($input['group_id'] ?? 0) ?: null,
            'milestone_date' => self::dateOrNull((string)($input['milestone_date'] ?? '')),
            'date_label' => Security::cleanString((string)($input['date_label'] ?? '')),
            'title' => Security::cleanString((string)($input['title'] ?? '')),
            'summary' => trim((string)($input['summary'] ?? '')),
            'icon' => Security::cleanString((string)($input['icon'] ?? '')),
            'badge_label' => Security::cleanString((string)($input['badge_label'] ?? '')),
            'status' => strtolower((string)($input['status'] ?? 'published')) === 'draft' ? 'draft' : 'published',
            'sort_order' => max(0, Security::cleanInt($input['sort_order'] ?? 0)),
        ];

        if ($id) {
            self::update($id, $data);
            return $id;
        }

        return (int)self::create($data);
    }

    public static function stats(): array
    {
        return Database::fetch("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published FROM stakeholder_milestones") ?: [];
    }

    private static function dateOrNull(string $value): ?string
    {
        $value = trim($value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
