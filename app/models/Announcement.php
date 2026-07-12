<?php

class Announcement extends Model
{
    protected static string $table = 'announcements';

    public const TYPES = [
        'info' => ['label' => 'Information', 'icon' => 'fa-circle-info'],
        'success' => ['label' => 'Success', 'icon' => 'fa-circle-check'],
        'warning' => ['label' => 'Warning', 'icon' => 'fa-triangle-exclamation'],
        'critical' => ['label' => 'Critical', 'icon' => 'fa-circle-exclamation'],
        'maintenance' => ['label' => 'Maintenance', 'icon' => 'fa-screwdriver-wrench'],
        'deadline' => ['label' => 'Deadline', 'icon' => 'fa-calendar-day'],
        'policy' => ['label' => 'Policy', 'icon' => 'fa-file-shield'],
        'event' => ['label' => 'Event', 'icon' => 'fa-calendar-check'],
    ];

    public const STATUSES = ['draft', 'published', 'archived'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const ROLES = ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'];
    /** Stored in target_roles_json to mean every active staff role. */
    public const AUDIENCE_ALL = 'all';

    public static function adminList(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::adminWhere($filters);
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return Database::fetchAll(
            self::selectSql() . $where . '
             ORDER BY a.is_pinned DESC,
                      FIELD(a.priority, "urgent", "high", "normal", "low"),
                      COALESCE(a.published_at, a.created_at) DESC,
                      a.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $bindings
        );
    }

    public static function adminCount(array $filters = []): int
    {
        [$where, $bindings] = self::adminWhere($filters);
        $row = Database::fetch(
            'SELECT COUNT(*) AS aggregate FROM announcements a LEFT JOIN users u ON u.id = a.author_id' . $where,
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }

    public static function adminStats(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
                    SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) AS pinned,
                    SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN priority = 'urgent' OR type = 'critical' THEN 1 ELSE 0 END) AS urgent
             FROM announcements"
        );

        return array_map('intval', $row ?: []);
    }

    public static function findAdmin(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE a.id = ? LIMIT 1', [$id]);
    }

    public static function saveFromAdmin(array $input, ?int $id = null): int
    {
        $audience = self::normalizeAudienceFromInput($input);
        $status = self::normalizeStatus((string)($input['status'] ?? 'draft'));
        if ($status === 'published' && $audience === []) {
            throw new RuntimeException('Choose “All staff” or at least one target role before publishing.');
        }
        $publishedAt = self::normalizeDateTime((string)($input['published_at'] ?? ''));
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $payload = [
            'author_id' => (int)(Auth::id() ?: ($input['author_id'] ?? 1)),
            'title' => trim((string)($input['title'] ?? '')),
            'body' => trim((string)($input['body'] ?? '')),
            'type' => self::normalizeType((string)($input['type'] ?? 'info')),
            'status' => $status,
            'priority' => self::normalizePriority((string)($input['priority'] ?? 'normal')),
            // Always store explicit JSON: ["all"] or role list. Never silent null for published.
            'target_roles_json' => $audience === []
                ? json_encode([], JSON_UNESCAPED_SLASHES)
                : json_encode($audience, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'is_pinned' => !empty($input['is_pinned']) ? 1 : 0,
            'cta_label' => self::nullableText($input['cta_label'] ?? null),
            'cta_url' => self::nullableUrl($input['cta_url'] ?? null),
            'published_at' => $publishedAt,
            'expires_at' => self::normalizeDateTime((string)($input['expires_at'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
            'archived_at' => $status === 'archived' ? date('Y-m-d H:i:s') : null,
        ];

        if ($id !== null && $id > 0) {
            self::update($id, $payload);
            if (!empty($input['notify_again'])) {
                self::clearNotificationMarker($id);
            }
            self::notifyTargetUsersIfNeeded($id);
            return $id;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $newId = (int)self::create($payload);
        self::notifyTargetUsersIfNeeded($newId);
        return $newId;
    }

    public static function quickStatus(int $id, string $status): void
    {
        $status = self::normalizeStatus($status);
        $payload = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'archived_at' => $status === 'archived' ? date('Y-m-d H:i:s') : null,
        ];

        if ($status === 'published') {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        self::update($id, $payload);
        self::notifyTargetUsersIfNeeded($id);
    }

    public static function togglePinned(int $id): void
    {
        Database::query('UPDATE announcements SET is_pinned = 1 - COALESCE(is_pinned, 0), updated_at = NOW() WHERE id = ?', [$id]);
    }

    public static function activeForRole(?string $role, int $limit = 5, ?int $userId = null): array
    {
        $role = strtolower(trim((string)$role));
        $limit = max(1, $limit);
        $bindings = [];
        $audienceSql = '';

        // Superadmin dashboard can see every published notice (management view).
        // All other roles: only explicit "all" or their role token — never blank/null.
        if ($role !== 'superadmin') {
            $audienceSql = "
               AND (
                   JSON_CONTAINS(COALESCE(a.target_roles_json, '[]'), JSON_QUOTE(?))
                   OR JSON_CONTAINS(COALESCE(a.target_roles_json, '[]'), JSON_QUOTE(?))
               )";
            $bindings[] = self::AUDIENCE_ALL;
            $bindings[] = $role !== '' ? $role : '__none__';
        }

        $dismissSql = '';
        if ($userId !== null && $userId > 0) {
            self::ensureUserStateTable();
            $dismissSql = '
               AND NOT EXISTS (
                   SELECT 1 FROM announcement_user_state aus
                   WHERE aus.announcement_id = a.id
                     AND aus.user_id = ?
                     AND aus.dismissed_at IS NOT NULL
               )';
            $bindings[] = $userId;
        }

        return Database::fetchAll(
            self::selectSql() . "
             WHERE a.status = 'published'
               AND (a.published_at IS NULL OR a.published_at <= NOW())
               AND (a.expires_at IS NULL OR a.expires_at >= NOW())" . $audienceSql . $dismissSql . "
             ORDER BY a.is_pinned DESC,
                      FIELD(a.priority, 'urgent', 'high', 'normal', 'low'),
                      COALESCE(a.published_at, a.created_at) DESC
             LIMIT " . $limit,
            $bindings
        );
    }

    /**
     * Whether a stored audience JSON is visible to a portal role.
     */
    public static function audienceIncludesRole(?string $json, string $role): bool
    {
        $role = strtolower(trim($role));
        if ($role === 'superadmin') {
            return true;
        }
        $tokens = self::decodeAudience($json);
        if ($tokens === []) {
            return false;
        }
        return in_array(self::AUDIENCE_ALL, $tokens, true) || in_array($role, $tokens, true);
    }

    /**
     * @return list<string>
     */
    public static function decodeAudience(?string $json): array
    {
        $decoded = json_decode((string)$json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $token) {
            $token = strtolower(trim((string)$token));
            if ($token === self::AUDIENCE_ALL || in_array($token, self::ROLES, true)) {
                $out[] = $token;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Parse create/edit form into stored audience tokens.
     *
     * @return list<string> ["all"] or role slugs; empty only for incomplete drafts
     */
    public static function normalizeAudienceFromInput(array $input): array
    {
        $mode = strtolower(trim((string)($input['audience_mode'] ?? '')));
        if ($mode === '' && !empty($input['target_all'])) {
            $mode = 'all';
        }
        // Legacy: unchecked roles meant all — only honor if mode explicitly all.
        if ($mode === 'all') {
            return [self::AUDIENCE_ALL];
        }

        $roles = self::normalizeRoles($input['target_roles'] ?? []);
        // If author checked every operational role, still store as explicit list (not forced to all).
        return $roles;
    }

    public static function isAllAudience(?string $json): bool
    {
        return in_array(self::AUDIENCE_ALL, self::decodeAudience($json), true);
    }

    /** Active user count that would receive this audience. */
    public static function audienceReachCount(?string $json): int
    {
        $tokens = self::decodeAudience($json);
        if ($tokens === [] || in_array(self::AUDIENCE_ALL, $tokens, true)) {
            if ($tokens === []) {
                return 0;
            }
            $row = Database::fetch("SELECT COUNT(*) AS c FROM users WHERE status = 'active'");
            return (int)($row['c'] ?? 0);
        }
        $roles = array_values(array_filter($tokens, static fn (string $t): bool => $t !== self::AUDIENCE_ALL));
        if ($roles === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $row = Database::fetch(
            "SELECT COUNT(DISTINCT u.id) AS c
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active' AND r.slug IN ({$placeholders})",
            $roles
        );
        return (int)($row['c'] ?? 0);
    }

    public static function audienceModeFromJson(?string $json): string
    {
        return self::isAllAudience($json) ? 'all' : 'roles';
    }


    public static function markRead(int $announcementId, int $userId): void
    {
        if ($announcementId <= 0 || $userId <= 0) {
            return;
        }
        self::ensureUserStateTable();
        Database::query(
            'INSERT INTO announcement_user_state (announcement_id, user_id, read_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                read_at = COALESCE(read_at, NOW()),
                updated_at = NOW()',
            [$announcementId, $userId]
        );
    }

    public static function dismissForUser(int $announcementId, int $userId): bool
    {
        if ($announcementId <= 0 || $userId <= 0) {
            return false;
        }
        self::ensureUserStateTable();

        $announcement = self::find($announcementId);
        if (!$announcement || (string)($announcement['status'] ?? '') !== 'published') {
            return false;
        }

        Database::query(
            'INSERT INTO announcement_user_state (announcement_id, user_id, read_at, dismissed_at)
             VALUES (?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                read_at = COALESCE(read_at, NOW()),
                dismissed_at = NOW(),
                updated_at = NOW()',
            [$announcementId, $userId]
        );

        return true;
    }

    public static function ensureUserStateTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        try {
            Database::query(
                'CREATE TABLE IF NOT EXISTS announcement_user_state (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    announcement_id INT UNSIGNED NOT NULL,
                    user_id INT UNSIGNED NOT NULL,
                    read_at DATETIME NULL,
                    dismissed_at DATETIME NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_announcement_user (announcement_id, user_id),
                    KEY idx_aus_user_dismissed (user_id, dismissed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (Throwable) {
            // Table may already exist with FKs; continue.
        }

        $ready = true;
    }

    public static function roleLabels(?string $json): array
    {
        $tokens = self::decodeAudience($json);
        if ($tokens === []) {
            return ['No audience set'];
        }
        if (in_array(self::AUDIENCE_ALL, $tokens, true)) {
            return ['All staff'];
        }

        return array_map(static fn (string $role): string => role_label($role), $tokens);
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type]['label'] ?? status_label($type);
    }

    public static function typeIcon(string $type): string
    {
        return self::TYPES[$type]['icon'] ?? 'fa-bullhorn';
    }

    private static function selectSql(): string
    {
        return 'SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS author_name, u.email AS author_email
                FROM announcements a
                LEFT JOIN users u ON u.id = a.author_id';
    }

    private static function adminWhere(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(a.title LIKE ? OR a.body LIKE ?)';
            $bindings[] = '%' . $filters['q'] . '%';
            $bindings[] = '%' . $filters['q'] . '%';
        }

        foreach (['status', 'type', 'priority'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = 'a.' . $field . ' = ?';
                $bindings[] = $filters[$field];
            }
        }

        if (($filters['role'] ?? '') !== '') {
            $role = strtolower(trim((string)$filters['role']));
            if ($role === self::AUDIENCE_ALL) {
                $where[] = 'JSON_CONTAINS(COALESCE(a.target_roles_json, \'[]\'), JSON_QUOTE(?))';
                $bindings[] = self::AUDIENCE_ALL;
            } else {
                $where[] = '(JSON_CONTAINS(COALESCE(a.target_roles_json, \'[]\'), JSON_QUOTE(?)) OR JSON_CONTAINS(COALESCE(a.target_roles_json, \'[]\'), JSON_QUOTE(?)))';
                $bindings[] = self::AUDIENCE_ALL;
                $bindings[] = $role;
            }
        }

        if (($filters['pinned'] ?? '') === '1') {
            $where[] = 'a.is_pinned = 1';
        }

        if (($filters['visibility'] ?? '') === 'active') {
            $where[] = "a.status = 'published' AND (a.published_at IS NULL OR a.published_at <= NOW()) AND (a.expires_at IS NULL OR a.expires_at >= NOW())";
        } elseif (($filters['visibility'] ?? '') === 'expired') {
            $where[] = 'a.expires_at IS NOT NULL AND a.expires_at < NOW()';
        }

        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $bindings];
    }

    private static function normalizeRoles(mixed $roles): array
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $roles = array_values(array_unique(array_filter(array_map(
            static fn ($role): string => strtolower(trim((string)$role)),
            $roles
        ))));

        return array_values(array_intersect($roles, self::ROLES));
    }

    private static function normalizeType(string $type): string
    {
        return array_key_exists($type, self::TYPES) ? $type : 'info';
    }

    private static function normalizeStatus(string $status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'draft';
    }

    private static function normalizePriority(string $priority): string
    {
        return in_array($priority, self::PRIORITIES, true) ? $priority : 'normal';
    }

    private static function normalizeDateTime(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private static function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private static function nullableUrl(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^(?:https?://|mailto:|tel:)#i', $value) === 1 || preg_match('#^(?:/|admin/|api/|[a-z0-9][a-z0-9._/-]*\.php(?:[?#].*)?)#i', $value) === 1) {
            return $value;
        }

        throw new RuntimeException('CTA URL must be a valid http, https, mailto, tel or internal portal path.');
    }

    private static function clearNotificationMarker(int $id): void
    {
        $announcement = self::findAdmin($id);
        $metadata = json_decode((string)($announcement['metadata_json'] ?? ''), true);
        $metadata = is_array($metadata) ? $metadata : [];
        unset($metadata['notifications_sent_at'], $metadata['notifications_sent_count'], $metadata['notifications_target_roles']);
        $metadata['notifications_reset_at'] = date('Y-m-d H:i:s');
        self::storeMetadata($id, $metadata);
    }

    private static function notifyTargetUsersIfNeeded(int $id): void
    {
        $announcement = self::findAdmin($id);
        if (!$announcement || (string)($announcement['status'] ?? '') !== 'published') {
            return;
        }

        $publishedAt = (string)($announcement['published_at'] ?? '');
        if ($publishedAt !== '' && strtotime($publishedAt) !== false && strtotime($publishedAt) > time()) {
            return;
        }

        $metadata = json_decode((string)($announcement['metadata_json'] ?? ''), true);
        $metadata = is_array($metadata) ? $metadata : [];
        if (!empty($metadata['notifications_sent_at'])) {
            return;
        }

        $audience = self::decodeAudience($announcement['target_roles_json'] ?? '[]');
        $users = self::notificationUsers($audience);
        if ($users === []) {
            $metadata['notifications_sent_at'] = date('Y-m-d H:i:s');
            $metadata['notifications_sent_count'] = 0;
            self::storeMetadata($id, $metadata);
            return;
        }

        foreach ($users as $user) {
            Database::query(
                'INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at)
                 VALUES (?, "announcement", ?, ?, ?, 0, NOW())',
                [
                    (int)$user['id'],
                    'Announcement: ' . (string)$announcement['title'],
                    safe_truncate((string)$announcement['body'], 220),
                    'admin/index.php',
                ]
            );
        }

        $metadata['notifications_sent_at'] = date('Y-m-d H:i:s');
        $metadata['notifications_sent_count'] = count($users);
        $metadata['notifications_target_roles'] = $audience === [] ? [] : $audience;
        self::storeMetadata($id, $metadata);
    }

    /**
     * @param list<string> $audience tokens: all and/or role slugs
     * @return list<array{id:int}>
     */
    private static function notificationUsers(array $audience): array
    {
        if ($audience === []) {
            return [];
        }
        if (in_array(self::AUDIENCE_ALL, $audience, true)) {
            return Database::fetchAll(
                "SELECT u.id
                 FROM users u
                 WHERE u.status = 'active'
                 ORDER BY u.id ASC"
            );
        }

        $roles = array_values(array_filter(
            $audience,
            static fn (string $t): bool => $t !== self::AUDIENCE_ALL && in_array($t, self::ROLES, true)
        ));
        if ($roles === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        return Database::fetchAll(
            "SELECT DISTINCT u.id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active'
               AND r.slug IN ({$placeholders})
             ORDER BY u.id ASC",
            $roles
        );
    }

    private static function storeMetadata(int $id, array $metadata): void
    {
        Database::query(
            'UPDATE announcements SET metadata_json = ?, updated_at = COALESCE(updated_at, NOW()) WHERE id = ?',
            [json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $id]
        );
    }
}
