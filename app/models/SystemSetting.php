<?php

class SystemSetting extends Model
{
    protected static string $table = 'system_settings';

    public const GROUPS = [
        'general' => ['label' => 'General', 'icon' => 'fa-sliders'],
        'attendance' => ['label' => 'Attendance & Geo', 'icon' => 'fa-location-crosshairs'],
        'ipc' => ['label' => 'IPC Workflow', 'icon' => 'fa-file-signature'],
        'finance' => ['label' => 'Finance & Retention', 'icon' => 'fa-coins'],
        'boq' => ['label' => 'BOQ Controls', 'icon' => 'fa-list-check'],
        'programme' => ['label' => 'Programme', 'icon' => 'fa-bars-progress'],
        'public' => ['label' => 'Public Forms', 'icon' => 'fa-globe'],
        'reports' => ['label' => 'Reports', 'icon' => 'fa-file-lines'],
        'email' => ['label' => 'Email Delivery', 'icon' => 'fa-paper-plane'],
        'security' => ['label' => 'Uploads & Security', 'icon' => 'fa-shield-halved'],
        'advanced' => ['label' => 'Advanced JSON', 'icon' => 'fa-code'],
    ];

    public static function definitions(): array
    {
        return [
            ['key' => 'general.site_name', 'group' => 'general', 'label' => 'System name', 'description' => 'Name shown in exports and operational records.', 'type' => 'text', 'default' => 'Trans-Nzoia AHP Tracker', 'public' => true, 'sort' => 10],
            ['key' => 'general.support_email', 'group' => 'general', 'label' => 'Support email', 'description' => 'Fallback email for admin and public workflow messages.', 'type' => 'email', 'default' => 'support@transnzoia.go.ke', 'public' => true, 'sort' => 20],
            ['key' => 'general.app_url', 'group' => 'general', 'label' => 'Public app URL', 'description' => 'Optional production base URL used in emailed links. Leave blank locally to use the current request domain.', 'type' => 'url', 'default' => '', 'public' => false, 'sort' => 30],
            ['key' => 'general.maintenance_mode', 'group' => 'general', 'label' => 'Maintenance mode', 'description' => 'Reserve for frontend maintenance banners and access restrictions.', 'type' => 'boolean', 'default' => '0', 'public' => true, 'sort' => 40],

            ['key' => 'attendance.default_geo_radius_m', 'group' => 'attendance', 'label' => 'Default geo radius', 'description' => 'Default site fence radius used when creating and validating attendance controls.', 'type' => 'number', 'default' => '150', 'sort' => 10, 'options' => ['min' => 20, 'max' => 3000, 'step' => 1, 'suffix' => 'm']],
            ['key' => 'attendance.gps_required', 'group' => 'attendance', 'label' => 'GPS required', 'description' => 'Require latitude and longitude for attendance sign-in.', 'type' => 'boolean', 'default' => '1', 'sort' => 20],
            ['key' => 'attendance.min_accuracy_m', 'group' => 'attendance', 'label' => 'Maximum GPS accuracy drift', 'description' => 'Attendance is flagged when device accuracy is weaker than this value.', 'type' => 'number', 'default' => '50', 'sort' => 30, 'options' => ['min' => 5, 'max' => 1000, 'step' => 1, 'suffix' => 'm']],
            ['key' => 'attendance.signin_start', 'group' => 'attendance', 'label' => 'Sign-in opens', 'description' => 'Earliest Mon–Fri time interns and clerks may sign in. Controlled by County Director only.', 'type' => 'time', 'default' => '07:00', 'sort' => 40],
            ['key' => 'attendance.signin_end', 'group' => 'attendance', 'label' => 'Sign-in closes', 'description' => 'Attendance window closes. No normal sign-in after this time (Mon–Fri).', 'type' => 'time', 'default' => '08:30', 'sort' => 50],
            ['key' => 'attendance.expected_time', 'group' => 'attendance', 'label' => 'Expected report time', 'description' => 'Target report time (e.g. 08:00). Later sign-ins until close are marked late.', 'type' => 'time', 'default' => '08:00', 'sort' => 55],
            ['key' => 'attendance.late_after', 'group' => 'attendance', 'label' => 'Late after', 'description' => 'Sign-ins after this time (within the open window) are marked late.', 'type' => 'time', 'default' => '08:00', 'sort' => 60],
            ['key' => 'attendance.active_weekdays', 'group' => 'attendance', 'label' => 'Active weekdays', 'description' => 'Comma-separated weekdays when attendance is allowed (1=Mon … 5=Fri). Default Mon–Fri.', 'type' => 'text', 'default' => '1,2,3,4,5', 'sort' => 65],
            ['key' => 'attendance.auto_open', 'group' => 'attendance', 'label' => 'Auto-open gateway', 'description' => 'Automatically open the policy window so early arrivals are not blocked if a clerk is late to confirm.', 'type' => 'boolean', 'default' => '1', 'sort' => 68],
            ['key' => 'attendance.confirm_escalation_minutes', 'group' => 'attendance', 'label' => 'Clerk confirm escalation', 'description' => 'Minutes after auto-open before County Director is notified if clerk has not confirmed site open.', 'type' => 'number', 'default' => '30', 'sort' => 72, 'options' => ['min' => 5, 'max' => 180, 'step' => 5, 'suffix' => 'min']],
            ['key' => 'attendance.gateway_default_minutes', 'group' => 'attendance', 'label' => 'Gateway default duration', 'description' => 'Legacy fallback duration. Policy open/close times take priority.', 'type' => 'number', 'default' => '90', 'sort' => 80, 'options' => ['min' => 30, 'max' => 1440, 'step' => 15, 'suffix' => 'min']],

            ['key' => 'ipc.retention_percent', 'group' => 'ipc', 'label' => 'Default retention percent', 'description' => 'Reference retention percentage for IPC and finance workflows.', 'type' => 'number', 'default' => '5', 'sort' => 10, 'options' => ['min' => 0, 'max' => 30, 'step' => 0.5, 'suffix' => '%']],
            ['key' => 'ipc.rejection_reason_required', 'group' => 'ipc', 'label' => 'Require rejection reason', 'description' => 'Blocks IPC rejection without a clear reason.', 'type' => 'boolean', 'default' => '1', 'sort' => 20],
            ['key' => 'ipc.finance_requires_approval', 'group' => 'ipc', 'label' => 'Finance requires final approval', 'description' => 'Finance payment queues should only treat approved IPCs as payable.', 'type' => 'boolean', 'default' => '1', 'sort' => 30],

            ['key' => 'finance.retention_release_days', 'group' => 'finance', 'label' => 'Retention release days', 'description' => 'Default defect-liability period before retention review.', 'type' => 'number', 'default' => '365', 'sort' => 10, 'options' => ['min' => 0, 'max' => 1825, 'step' => 1, 'suffix' => 'days']],
            ['key' => 'finance.payment_alert_days', 'group' => 'finance', 'label' => 'Payment alert days', 'description' => 'Age threshold for highlighting approved IPCs awaiting payment.', 'type' => 'number', 'default' => '14', 'sort' => 20, 'options' => ['min' => 1, 'max' => 120, 'step' => 1, 'suffix' => 'days']],

            ['key' => 'boq.block_paid_above_certified', 'group' => 'boq', 'label' => 'Block paid above certified', 'description' => 'Prevents saving paid quantities higher than certified quantities.', 'type' => 'boolean', 'default' => '1', 'sort' => 10],
            ['key' => 'boq.warn_certified_above_contract', 'group' => 'boq', 'label' => 'Warn certified above contract', 'description' => 'Requires confirmation when certified quantity exceeds contract quantity.', 'type' => 'boolean', 'default' => '1', 'sort' => 20],
            ['key' => 'boq.amount_mismatch_tolerance', 'group' => 'boq', 'label' => 'Amount mismatch tolerance', 'description' => 'Difference allowed between BOQ amount and quantity x rate before warning.', 'type' => 'number', 'default' => '1', 'sort' => 30, 'options' => ['min' => 0, 'max' => 100000, 'step' => 1, 'suffix' => 'KES']],

            ['key' => 'programme.due_soon_days', 'group' => 'programme', 'label' => 'Due soon window', 'description' => 'Number of days used by programme dashboards for due-soon tasks.', 'type' => 'number', 'default' => '7', 'sort' => 10, 'options' => ['min' => 1, 'max' => 90, 'step' => 1, 'suffix' => 'days']],
            ['key' => 'programme.overdue_alert_enabled', 'group' => 'programme', 'label' => 'Overdue alerts', 'description' => 'Enables overdue programme task alerts for dashboards and notifications.', 'type' => 'boolean', 'default' => '1', 'sort' => 20],

            ['key' => 'public.contact_rate_limit_10m', 'group' => 'public', 'label' => 'Contact limit per 10 minutes', 'description' => 'Maximum public contact messages from one IP in 10 minutes.', 'type' => 'number', 'default' => '5', 'sort' => 10, 'options' => ['min' => 1, 'max' => 50, 'step' => 1]],
            ['key' => 'public.subscribe_rate_limit_10m', 'group' => 'public', 'label' => 'Subscribe limit per 10 minutes', 'description' => 'Maximum newsletter submissions from one IP in 10 minutes.', 'type' => 'number', 'default' => '5', 'sort' => 20, 'options' => ['min' => 1, 'max' => 50, 'step' => 1]],
            ['key' => 'public.contact_attachment_max_mb', 'group' => 'public', 'label' => 'Contact attachment max', 'description' => 'Maximum file size for public contact attachments.', 'type' => 'number', 'default' => '5', 'sort' => 30, 'options' => ['min' => 1, 'max' => 25, 'step' => 1, 'suffix' => 'MB']],

            ['key' => 'reports.default_range', 'group' => 'reports', 'label' => 'Default report range', 'description' => 'Default report date range preset used by report builders.', 'type' => 'select', 'default' => '30_days', 'sort' => 10, 'options' => ['choices' => ['7_days' => '7 days', '30_days' => '30 days', 'month_to_date' => 'Month to date', 'quarter_to_date' => 'Quarter to date']]],
            ['key' => 'reports.footer_note', 'group' => 'reports', 'label' => 'Report footer note', 'description' => 'Printed under exported HTML reports.', 'type' => 'text', 'default' => 'Generated from live AHPTC information. Verify figures against approved source documents before statutory filing.', 'sort' => 20],
            ['key' => 'reports.include_print_toolbar', 'group' => 'reports', 'label' => 'Print toolbar', 'description' => 'Show the print button on printable HTML reports.', 'type' => 'boolean', 'default' => '1', 'sort' => 30],

            ['key' => 'email.provider', 'group' => 'email', 'label' => 'Email provider', 'description' => 'Transactional email provider used for message notifications.', 'type' => 'select', 'default' => 'resend', 'sort' => 10, 'options' => ['choices' => ['resend' => 'Resend']]],
            ['key' => 'email.enabled', 'group' => 'email', 'label' => 'Email notifications', 'description' => 'Send external email alerts for new internal messages when provider credentials are available.', 'type' => 'boolean', 'default' => '0', 'sort' => 20],
            ['key' => 'email.resend_api_key', 'group' => 'email', 'label' => 'Resend API key', 'description' => 'Leave blank to use RESEND_API_KEY from the server environment. Rotate exposed keys before saving.', 'type' => 'text', 'default' => '', 'sensitive' => true, 'sort' => 30],
            ['key' => 'email.from_email', 'group' => 'email', 'label' => 'From email', 'description' => 'Verified sender address in Resend, for example notifications@yourdomain.com.', 'type' => 'email', 'default' => '', 'sort' => 40],
            ['key' => 'email.from_name', 'group' => 'email', 'label' => 'From name', 'description' => 'Display name used for system emails.', 'type' => 'text', 'default' => 'AHP Tracker', 'sort' => 50],
            ['key' => 'email.message_alerts', 'group' => 'email', 'label' => 'Message email alerts', 'description' => 'Send email alerts when users receive a new direct, group or project-channel message.', 'type' => 'boolean', 'default' => '1', 'sort' => 60],

            ['key' => 'security.max_upload_mb', 'group' => 'security', 'label' => 'General upload max', 'description' => 'Shared upload ceiling for admin media workflows.', 'type' => 'number', 'default' => '20', 'sort' => 10, 'options' => ['min' => 1, 'max' => 100, 'step' => 1, 'suffix' => 'MB']],
            ['key' => 'security.login_attempt_limit', 'group' => 'security', 'label' => 'Login attempt limit', 'description' => 'Future authentication lockout threshold.', 'type' => 'number', 'default' => '5', 'sort' => 20, 'options' => ['min' => 3, 'max' => 20, 'step' => 1]],
            ['key' => 'security.session_idle_minutes', 'group' => 'security', 'label' => 'Idle session minutes', 'description' => 'Reference timeout for protected admin sessions.', 'type' => 'number', 'default' => '60', 'sort' => 30, 'options' => ['min' => 5, 'max' => 480, 'step' => 5, 'suffix' => 'min']],

            ['key' => 'advanced.notification_channels', 'group' => 'advanced', 'label' => 'Notification channels', 'description' => 'JSON flags for future notification delivery routes.', 'type' => 'json', 'default' => '{"database":true,"email":false,"sms":false}', 'sort' => 10],
            ['key' => 'advanced.report_columns', 'group' => 'advanced', 'label' => 'Report column preferences', 'description' => 'JSON object reserved for report column visibility defaults.', 'type' => 'json', 'default' => '{}', 'sort' => 20],
        ];
    }

    public static function definitionMap(): array
    {
        $map = [];
        foreach (self::definitions() as $definition) {
            $map[$definition['key']] = $definition;
        }
        return $map;
    }

    public static function syncDefinitions(): void
    {
        foreach (self::definitions() as $definition) {
            $options = isset($definition['options']) ? json_encode($definition['options'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
            Database::query(
                "INSERT INTO system_settings
                    (setting_key, setting_group, label, description, value, default_value, type, options_json, is_sensitive, is_public, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    setting_group = VALUES(setting_group),
                    label = VALUES(label),
                    description = VALUES(description),
                    default_value = VALUES(default_value),
                    type = VALUES(type),
                    options_json = VALUES(options_json),
                    is_sensitive = VALUES(is_sensitive),
                    is_public = VALUES(is_public),
                    sort_order = VALUES(sort_order)",
                [
                    $definition['key'],
                    $definition['group'],
                    $definition['label'],
                    $definition['description'] ?? null,
                    (string)$definition['default'],
                    (string)$definition['default'],
                    $definition['type'],
                    $options,
                    !empty($definition['sensitive']) ? 1 : 0,
                    !empty($definition['public']) ? 1 : 0,
                    (int)($definition['sort'] ?? 100),
                ]
            );
        }
    }

    public static function grouped(): array
    {
        self::ensureTableReady();
        $rows = Database::fetchAll(
            "SELECT s.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS updated_by_name
             FROM system_settings s
             LEFT JOIN users u ON u.id = s.updated_by
             ORDER BY s.setting_group ASC, s.sort_order ASC, s.label ASC"
        );

        $grouped = [];
        foreach (self::GROUPS as $key => $meta) {
            $grouped[$key] = ['meta' => $meta + ['key' => $key], 'items' => []];
        }

        foreach ($rows as $row) {
            $group = (string)($row['setting_group'] ?? 'advanced');
            if (!isset($grouped[$group])) {
                $grouped[$group] = ['meta' => ['key' => $group, 'label' => status_label($group), 'icon' => 'fa-sliders'], 'items' => []];
            }
            $grouped[$group]['items'][] = self::payload($row);
        }

        return array_filter($grouped, static fn (array $group): bool => $group['items'] !== []);
    }

    public static function summary(): array
    {
        self::ensureTableReady();
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN value <> default_value THEN 1 ELSE 0 END) AS customised,
                SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) AS public_settings,
                SUM(CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS changed_week
             FROM system_settings"
        ) ?: ['total' => 0, 'customised' => 0, 'public_settings' => 0, 'changed_week' => 0];
    }

    public static function getRaw(string $key): ?array
    {
        self::ensureTableReady(false);
        return Database::fetch('SELECT * FROM system_settings WHERE setting_key = ? LIMIT 1', [$key]);
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        $row = self::getRaw($key);
        if (!$row) {
            $definition = self::definitionMap()[$key] ?? null;
            return $definition ? self::cast((string)$definition['default'], (string)$definition['type']) : $fallback;
        }

        return self::cast((string)($row['value'] ?? $row['default_value'] ?? ''), (string)($row['type'] ?? 'text'));
    }

    public static function setValue(string $key, mixed $value, int $userId = 0): array
    {
        self::ensureTableReady();
        $row = self::getRaw($key);
        if (!$row) {
            throw new InvalidArgumentException('Unknown system setting.');
        }

        $newValue = self::normaliseValue($value, (string)$row['type'], self::decodeOptions($row['options_json'] ?? null));
        $oldValue = (string)($row['value'] ?? '');

        Database::query(
            'UPDATE system_settings SET value = ?, updated_by = ? WHERE setting_key = ?',
            [$newValue, $userId > 0 ? $userId : null, $key]
        );

        self::recordRevision($key, $oldValue, $newValue, $userId);
        Logger::log('update', 'system_settings', (int)$row['id'], ['key' => $key, 'old' => $oldValue, 'new' => $newValue]);
        SystemConfig::clear();

        return self::payload(self::getRaw($key) ?: []);
    }

    public static function resetKey(string $key, int $userId = 0): array
    {
        self::ensureTableReady();
        $row = self::getRaw($key);
        if (!$row) {
            throw new InvalidArgumentException('Unknown system setting.');
        }

        $oldValue = (string)($row['value'] ?? '');
        $newValue = (string)($row['default_value'] ?? '');
        Database::query('UPDATE system_settings SET value = ?, updated_by = ? WHERE setting_key = ?', [$newValue, $userId > 0 ? $userId : null, $key]);
        self::recordRevision($key, $oldValue, $newValue, $userId);
        Logger::log('reset', 'system_settings', (int)$row['id'], ['key' => $key]);
        SystemConfig::clear();

        return self::payload(self::getRaw($key) ?: []);
    }

    public static function resetGroup(string $group, int $userId = 0): int
    {
        self::ensureTableReady();
        $rows = Database::fetchAll('SELECT * FROM system_settings WHERE setting_group = ?', [$group]);
        foreach ($rows as $row) {
            self::resetKey((string)$row['setting_key'], $userId);
        }
        return count($rows);
    }

    public static function publicSettings(): array
    {
        self::ensureTableReady(false);
        $rows = Database::fetchAll('SELECT setting_key, value, type FROM system_settings WHERE is_public = 1 ORDER BY setting_key ASC');
        $settings = [];
        foreach ($rows as $row) {
            $settings[(string)$row['setting_key']] = self::cast((string)($row['value'] ?? ''), (string)($row['type'] ?? 'text'));
        }
        return $settings;
    }

    public static function payload(array $row): array
    {
        $options = self::decodeOptions($row['options_json'] ?? null);
        $value = (string)($row['value'] ?? '');
        $default = (string)($row['default_value'] ?? '');
        $isSensitive = (int)($row['is_sensitive'] ?? 0) === 1;
        return [
            'id' => (int)($row['id'] ?? 0),
            'key' => (string)($row['setting_key'] ?? ''),
            'group' => (string)($row['setting_group'] ?? ''),
            'label' => (string)($row['label'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'value' => $isSensitive ? '' : $value,
            'default' => $isSensitive ? '' : $default,
            'cast_value' => $isSensitive ? null : self::cast($value, (string)($row['type'] ?? 'text')),
            'type' => (string)($row['type'] ?? 'text'),
            'options' => $options,
            'is_sensitive' => $isSensitive,
            'has_value' => $isSensitive && $value !== '',
            'is_public' => (int)($row['is_public'] ?? 0) === 1,
            'is_custom' => $value !== $default,
            'updated_by_name' => trim((string)($row['updated_by_name'] ?? '')),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    public static function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'number' => is_numeric($value) ? (float)$value : 0.0,
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'json' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    public static function normaliseValue(mixed $value, string $type, array $options = []): string
    {
        $raw = is_scalar($value) || $value === null ? trim((string)$value) : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($type === 'boolean') {
            return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
        }

        if ($type === 'number') {
            if (!is_numeric($raw)) {
                throw new InvalidArgumentException('Value must be numeric.');
            }
            $number = (float)$raw;
            if (isset($options['min']) && $number < (float)$options['min']) {
                throw new InvalidArgumentException('Value is below the allowed minimum.');
            }
            if (isset($options['max']) && $number > (float)$options['max']) {
                throw new InvalidArgumentException('Value is above the allowed maximum.');
            }
            return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
        }

        if ($type === 'time') {
            if (!preg_match('/^\d{2}:\d{2}$/', $raw)) {
                throw new InvalidArgumentException('Value must use HH:MM time format.');
            }
            return $raw;
        }

        if ($type === 'email' && $raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Value must be a valid email address.');
        }

        if ($type === 'url' && $raw !== '' && !filter_var($raw, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Value must be a valid URL.');
        }

        if ($type === 'select') {
            $choices = $options['choices'] ?? [];
            if ($choices !== [] && !array_key_exists($raw, $choices)) {
                throw new InvalidArgumentException('Value must be one of the allowed choices.');
            }
            return $raw;
        }

        if ($type === 'json') {
            json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Value must be valid JSON.');
            }
            return $raw;
        }

        return substr($raw, 0, 5000);
    }

    private static function decodeOptions(mixed $json): array
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function recordRevision(string $key, string $oldValue, string $newValue, int $userId): void
    {
        try {
            Database::query(
                'INSERT INTO system_setting_revisions (setting_key, old_value, new_value, changed_by, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $key,
                    $oldValue,
                    $newValue,
                    $userId > 0 ? $userId : null,
                    substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                    substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
                ]
            );
        } catch (Throwable) {
        }
    }

    private static function ensureTableReady(bool $sync = true): void
    {
        static $ready = false;
        static $synced = false;

        if ($ready && (!$sync || $synced)) {
            return;
        }

        $row = Database::fetch("SHOW TABLES LIKE 'system_settings'");
        if (!$row) {
            throw new RuntimeException('System settings table is missing. Run migration 106.');
        }

        $ready = true;
        if ($sync && !$synced) {
            self::syncDefinitions();
            $synced = true;
        }
    }
}
