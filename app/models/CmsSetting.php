<?php

class CmsSetting extends Model
{
    protected static string $table = 'cms_settings';

    public static function get(string $key): mixed
    {
        $row = Database::fetch('SELECT value, type FROM cms_settings WHERE `key` = ? LIMIT 1', [$key]);
        if (!$row) {
            return null;
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
