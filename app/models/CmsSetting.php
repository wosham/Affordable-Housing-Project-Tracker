<?php

class CmsSetting extends Model
{
    protected static string $table = 'cms_settings';

    public static function get(string $key, mixed $fallback = null): mixed
    {
        $row = Database::fetch('SELECT value, type FROM cms_settings WHERE `key` = ? LIMIT 1', [$key]);
        if (!$row) {
            return $fallback;
        }

        return self::castValue((string)($row['value'] ?? ''), (string)($row['type'] ?? 'text'));
    }

    public static function getGroup(string $group): array
    {
        $rows = Database::fetchAll('SELECT * FROM cms_settings WHERE `group` = ? ORDER BY label ASC', [$group]);
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['key']] = self::castValue((string)($row['value'] ?? ''), (string)($row['type'] ?? 'text'));
        }

        return $settings;
    }

    public static function grouped(): array
    {
        $rows = Database::fetchAll('SELECT * FROM cms_settings ORDER BY `group` ASC, label ASC');
        $groups = [];

        foreach ($rows as $row) {
            $groups[$row['group']][] = $row;
        }

        return $groups;
    }

    public static function text(string $key, string $fallback = ''): string
    {
        $value = self::get($key, $fallback);
        $value = trim((string)$value);
        return $value !== '' ? $value : $fallback;
    }

    public static function url(string $key, string $fallback = ''): string
    {
        $value = self::text($key, $fallback);
        if ($value === '') {
            return $fallback;
        }

        return filter_var($value, FILTER_VALIDATE_URL) || str_starts_with($value, '/') ? $value : $fallback;
    }

    public static function number(string $key, float|int $fallback = 0): float|int
    {
        $value = self::get($key, $fallback);
        return is_numeric($value) ? $value + 0 : $fallback;
    }

    public static function bool(string $key, bool $fallback = false): bool
    {
        $value = self::get($key, $fallback);
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $fallback;
        }

        return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function json(string $key, array $fallback = []): array
    {
        $value = self::get($key, $fallback);
        return is_array($value) ? $value : $fallback;
    }

    public static function groupedPublic(array $groups = ['site', 'contact', 'social', 'frontend']): array
    {
        $public = [];
        foreach ($groups as $group) {
            $public[$group] = self::getGroup((string)$group);
        }

        return $public;
    }

    public static function upsert(string $key, mixed $value, string $type = 'text', string $label = '', string $group = 'global'): void
    {
        $stored = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string)$value;
        Database::query(
            'INSERT INTO cms_settings (`key`, value, type, label, `group`, updated_by)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), type = VALUES(type), label = VALUES(label),
                                     `group` = VALUES(`group`), updated_by = VALUES(updated_by)',
            [$key, $stored, $type, $label !== '' ? $label : ucwords(str_replace('_', ' ', $key)), $group, (int)Auth::id()]
        );
    }

    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'number' => is_numeric($value) ? (float)$value : 0,
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'json' => json_decode($value, true) ?: [],
            default => $value,
        };
    }
}
