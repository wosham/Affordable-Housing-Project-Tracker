<?php
class Stakeholder extends Model
{
    protected static string $table = 'stakeholders';

    public static function ordered(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['group_id'] ?? '') !== '') {
            $where[] = 's.group_id = ?';
            $params[] = (int)$filters['group_id'];
        }
        if (array_key_exists('featured_on_stakeholders', $filters) && $filters['featured_on_stakeholders'] !== '') {
            $where[] = 's.featured_on_stakeholders = ?';
            $params[] = (int)$filters['featured_on_stakeholders'];
        }
        if (array_key_exists('is_formal_partner', $filters) && $filters['is_formal_partner'] !== '') {
            $where[] = 's.is_formal_partner = ?';
            $params[] = (int)$filters['is_formal_partner'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(s.organisation LIKE ? OR s.role LIKE ? OR s.description LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }

        return Database::fetchAll(
            "SELECT s.*, g.name AS group_name, g.icon AS group_icon
             FROM stakeholders s
             LEFT JOIN stakeholder_groups g ON g.id = s.group_id"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY s.sort_order ASC, s.organisation ASC',
            $params
        );
    }

    public static function leadershipPartners(int $limit = 12): array
    {
        return Database::fetchAll(
            "SELECT *
             FROM stakeholders
             WHERE status = 'published'
               AND featured_on_leadership = 1
             ORDER BY sort_order ASC, organisation ASC
             LIMIT " . max(1, $limit)
        );
    }

    public static function formalPartners(int $limit = 12): array
    {
        return Database::fetchAll(
            "SELECT s.*, g.name AS group_name, g.icon AS group_icon
             FROM stakeholders s
             LEFT JOIN stakeholder_groups g ON g.id = s.group_id
             WHERE s.status = 'published'
               AND s.is_formal_partner = 1
             ORDER BY s.sort_order ASC, s.organisation ASC
             LIMIT " . max(1, $limit)
        );
    }

    public static function findDetailed(int|string $idOrSlug): ?array
    {
        if (is_numeric($idOrSlug)) {
            return Database::fetch('SELECT * FROM stakeholders WHERE id = ? LIMIT 1', [(int)$idOrSlug]);
        }

        return Database::fetch('SELECT * FROM stakeholders WHERE slug = ? LIMIT 1', [(string)$idOrSlug]);
    }

    public static function saveStakeholder(array $input, ?int $id = null): int
    {
        $organisation = Security::cleanString((string)($input['organisation'] ?? ''));
        $slug = StakeholderGroup::slug((string)($input['slug'] ?? $organisation));
        self::ensureUniqueSlug($slug, $id);
        $data = [
            'group_id' => Security::cleanInt($input['group_id'] ?? 0) ?: null,
            'slug' => $slug,
            'organisation' => $organisation,
            'role' => Security::cleanString((string)($input['role'] ?? '')),
            'category' => Security::cleanString((string)($input['category'] ?? 'partner')),
            'partner_type' => Security::cleanString((string)($input['partner_type'] ?? '')),
            'agreement_type' => Security::cleanString((string)($input['agreement_type'] ?? '')),
            'icon' => Security::cleanString((string)($input['icon'] ?? '')),
            'description' => trim((string)($input['description'] ?? '')),
            'mandate' => trim((string)($input['mandate'] ?? '')),
            'logo_path' => Security::cleanString((string)($input['logo_path'] ?? '')),
            'website' => self::nullableUrl($input['website'] ?? null),
            'sort_order' => max(0, Security::cleanInt($input['sort_order'] ?? 0)),
            'is_visible' => 1,
            'status' => strtolower((string)($input['status'] ?? 'published')) === 'draft' ? 'draft' : 'published',
            'featured_on_leadership' => isset($input['featured_on_leadership']) ? 1 : 0,
            'is_formal_partner' => isset($input['is_formal_partner']) ? 1 : 0,
            'featured_on_stakeholders' => isset($input['featured_on_stakeholders']) ? 1 : 0,
        ];

        if ($id) {
            self::update($id, $data);
            return $id;
        }

        return (int)self::create($data);
    }

    public static function stats(): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN featured_on_leadership = 1 AND status = 'published' THEN 1 ELSE 0 END) AS leadership_partners,
                SUM(CASE WHEN is_formal_partner = 1 AND status = 'published' THEN 1 ELSE 0 END) AS formal_partners,
                SUM(CASE WHEN featured_on_stakeholders = 1 AND status = 'published' THEN 1 ELSE 0 END) AS stakeholder_page
             FROM stakeholders"
        ) ?: [];
    }

    private static function nullableUrl(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }

        throw new RuntimeException('Stakeholder website must start with http:// or https://.');
    }

    private static function ensureUniqueSlug(string $slug, ?int $id = null): void
    {
        $existing = Database::fetch(
            'SELECT id FROM stakeholders WHERE slug = ? AND (? IS NULL OR id <> ?) LIMIT 1',
            [$slug, $id, $id]
        );

        if ($existing) {
            throw new RuntimeException('This stakeholder slug is already in use.');
        }
    }
}
