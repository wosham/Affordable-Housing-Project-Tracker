<?php
class LeadershipProfile extends Model
{
    protected static string $table = 'leadership_profiles';

    public static function findDetailed(int|string $idOrSlug): ?array
    {
        $isId = is_int($idOrSlug) || ctype_digit((string)$idOrSlug);
        $where = $isId ? 'lp.id = ?' : 'lp.slug = ?';
        return Database::fetch(
            "SELECT lp.*, ml.path AS media_path, ml.url AS media_url
             FROM leadership_profiles lp
             LEFT JOIN media_library ml ON ml.id = lp.photo_id
             WHERE {$where}
             LIMIT 1",
            [$idOrSlug]
        );
    }

    public static function ordered(array $filters = []): array
    {
        [$where, $bindings] = self::filters($filters);

        return Database::fetchAll(
            "SELECT lp.*, ml.path AS media_path, ml.url AS media_url
             FROM leadership_profiles lp
             LEFT JOIN media_library ml ON ml.id = lp.photo_id
             {$where}
             ORDER BY lp.sort_order ASC, lp.tier ASC, lp.name ASC",
            $bindings
        );
    }

    public static function featuredSpotlight(): ?array
    {
        return Database::fetch(
            "SELECT lp.*, ml.path AS media_path, ml.url AS media_url
             FROM leadership_profiles lp
             LEFT JOIN media_library ml ON ml.id = lp.photo_id
             WHERE lp.status = 'published' AND lp.show_in_spotlight = 1
             ORDER BY lp.sort_order ASC, lp.id ASC
             LIMIT 1"
        );
    }

    public static function orgChart(): array
    {
        return self::ordered([
            'status' => 'published',
            'show_in_org_chart' => '1',
        ]);
    }

    public static function publicCards(int $limit = 6): array
    {
        return Database::fetchAll(
            "SELECT lp.*, ml.path AS media_path, ml.url AS media_url
             FROM leadership_profiles lp
             LEFT JOIN media_library ml ON ml.id = lp.photo_id
             WHERE lp.status = 'published' AND lp.show_in_cards = 1
             ORDER BY lp.sort_order ASC, lp.tier ASC, lp.name ASC
             LIMIT " . max(1, $limit)
        );
    }

    public static function stats(): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                SUM(CASE WHEN show_in_org_chart = 1 AND status = 'published' THEN 1 ELSE 0 END) AS org_nodes,
                SUM(CASE WHEN show_in_cards = 1 AND status = 'published' THEN 1 ELSE 0 END) AS cards,
                SUM(CASE WHEN show_in_spotlight = 1 AND status = 'published' THEN 1 ELSE 0 END) AS spotlight
             FROM leadership_profiles"
        ) ?: [];
    }

    public static function saveProfile(array $data, ?int $id = null): int
    {
        $name = Security::cleanString((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        $slug = $slug !== '' ? self::slug($slug) : self::slug($name);
        $initials = trim((string)($data['initials'] ?? ''));
        $initials = $initials !== '' ? strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $initials), 0, 12)) : self::initials($name);
        $responsibilities = self::normaliseResponsibilities((string)($data['responsibilities'] ?? ''));

        $payload = [
            'slug' => $slug,
            'profile_type' => Security::cleanString((string)($data['profile_type'] ?? 'national')),
            'parent_id' => ((int)($data['parent_id'] ?? 0)) > 0 ? (int)$data['parent_id'] : null,
            'tier' => max(1, (int)($data['tier'] ?? 1)),
            'icon' => Security::cleanString((string)($data['icon'] ?? 'fa-user-tie')),
            'initials' => $initials,
            'name' => $name,
            'title' => Security::cleanString((string)($data['title'] ?? '')),
            'organisation' => Security::cleanString((string)($data['organisation'] ?? '')),
            'appointment_label' => Security::cleanString((string)($data['appointment_label'] ?? '')),
            'appointment_source' => Security::cleanString((string)($data['appointment_source'] ?? '')),
            'bio' => trim((string)($data['bio'] ?? '')),
            'quote' => trim((string)($data['quote'] ?? '')),
            'responsibilities_json' => json_encode($responsibilities, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'email' => Security::cleanString((string)($data['email'] ?? '')),
            'phone' => Security::cleanString((string)($data['phone'] ?? '')),
            'office_location' => Security::cleanString((string)($data['office_location'] ?? '')),
            'photo_path' => Security::cleanString((string)($data['photo_path'] ?? '')),
            'sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            'show_in_org_chart' => !empty($data['show_in_org_chart']) ? 1 : 0,
            'show_in_cards' => !empty($data['show_in_cards']) ? 1 : 0,
            'show_in_spotlight' => !empty($data['show_in_spotlight']) ? 1 : 0,
            'status' => strtolower((string)($data['status'] ?? 'published')) === 'draft' ? 'draft' : 'published',
            'is_visible' => strtolower((string)($data['status'] ?? 'published')) === 'draft' ? 0 : 1,
        ];

        if ($id !== null && $id > 0) {
            self::update($id, $payload);
            return $id;
        }

        return self::create($payload);
    }

    public static function responsibilities(array $profile): array
    {
        $decoded = json_decode((string)($profile['responsibilities_json'] ?? ''), true);
        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    public static function slug(string $name): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        return $slug !== '' ? $slug : 'leader-' . substr(sha1($name . microtime(true)), 0, 8);
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            $clean = preg_replace('/[^a-z]/i', '', $part);
            if ($clean !== '') {
                $letters .= strtoupper(substr($clean, 0, 1));
            }
            if (strlen($letters) >= 3) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'AH';
    }

    private static function filters(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['q'])) {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(lp.name LIKE ? OR lp.title LIKE ? OR lp.organisation LIKE ? OR lp.bio LIKE ?)';
            array_push($bindings, $term, $term, $term, $term);
        }

        if (!empty($filters['profile_type'])) {
            $where[] = 'lp.profile_type = ?';
            $bindings[] = (string)$filters['profile_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'lp.status = ?';
            $bindings[] = (string)$filters['status'];
        }

        if (isset($filters['show_in_org_chart']) && (string)$filters['show_in_org_chart'] !== '') {
            $where[] = 'lp.show_in_org_chart = ?';
            $bindings[] = (int)$filters['show_in_org_chart'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function normaliseResponsibilities(string $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        return array_values(array_filter(array_map(static function (string $line): string {
            return trim($line);
        }, $lines)));
    }
}
