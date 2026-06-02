<?php

class SystemConfig
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $fallback = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::fetchAll('SELECT setting_key, value, type FROM system_settings') as $row) {
                    self::$cache[(string)$row['setting_key']] = SystemSetting::cast((string)($row['value'] ?? ''), (string)($row['type'] ?? 'text'));
                }
            } catch (Throwable) {
                self::$cache = [];
            }
        }

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $definition = SystemSetting::definitionMap()[$key] ?? null;
        return $definition ? SystemSetting::cast((string)$definition['default'], (string)$definition['type']) : $fallback;
    }

    public static function text(string $key, string $fallback = ''): string
    {
        $value = self::get($key, $fallback);
        return is_scalar($value) ? (string)$value : $fallback;
    }

    public static function number(string $key, float $fallback = 0): float
    {
        $value = self::get($key, $fallback);
        return is_numeric($value) ? (float)$value : $fallback;
    }

    public static function int(string $key, int $fallback = 0): int
    {
        return (int)round(self::number($key, $fallback));
    }

    public static function bool(string $key, bool $fallback = false): bool
    {
        $value = self::get($key, $fallback);
        return is_bool($value) ? $value : in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function json(string $key, array $fallback = []): array
    {
        $value = self::get($key, $fallback);
        return is_array($value) ? $value : $fallback;
    }

    public static function clear(): void
    {
        self::$cache = null;
    }
}
