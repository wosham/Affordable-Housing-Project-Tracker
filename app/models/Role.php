<?php
class Role extends Model
{
    protected static string $table = 'roles';

    public static function allOrdered(): array
    {
        return Database::fetchAll('SELECT * FROM roles ORDER BY id ASC');
    }

    /** Alias for callers that expect Role::all(). */
    public static function all(): array
    {
        return self::allOrdered();
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM roles WHERE slug = ? LIMIT 1', [strtolower(trim($slug))]);
    }

    public static function optionsForSelect(): array
    {
        return array_map(static fn (array $role): array => [
            'id' => (int)$role['id'],
            'slug' => (string)$role['slug'],
            'name' => (string)$role['name'],
            'label' => (string)($role['name'] ?? $role['slug']),
        ], self::allOrdered());
    }

    public static function permissions(array $role): array
    {
        $decoded = json_decode((string)($role['permissions_json'] ?? ''), true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function hasPermission(string $roleSlug, string $permission): bool
    {
        $roleSlug = strtolower(trim($roleSlug));
        if ($roleSlug === 'superadmin') {
            return true;
        }

        $role = self::findBySlug($roleSlug);
        if (!$role) {
            return false;
        }

        $permissions = self::permissions($role);
        return in_array($permission, $permissions, true) || !empty($permissions[$permission]);
    }
}
