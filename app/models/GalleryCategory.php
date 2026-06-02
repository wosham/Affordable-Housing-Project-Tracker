<?php

class GalleryCategory extends Model
{
    protected static string $table = 'gallery_categories';

    public static function allOrdered(): array
    {
        return Database::fetchAll('
            SELECT gc.*, COUNT(DISTINCT gi.id) AS item_count
            FROM gallery_categories gc
            LEFT JOIN gallery_images gi ON gi.category_id = gc.id
            GROUP BY gc.id
            ORDER BY gc.sort_order ASC, gc.name ASC
        ');
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM gallery_categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    public static function slugFromName(string $name): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        return $slug !== '' ? $slug : 'gallery-category';
    }

    public static function saveFromAdmin(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $name = Security::cleanString((string)($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }

        $slug = Security::cleanString((string)($data['slug'] ?? ''));
        $slug = $slug !== '' ? self::slugFromName($slug) : self::slugFromName($name);
        $sortOrder = max(0, (int)($data['sort_order'] ?? 0));

        $existing = Database::fetch('SELECT id FROM gallery_categories WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $id]);
        if ($existing) {
            throw new RuntimeException('A gallery category with this slug already exists.');
        }

        if ($id > 0) {
            Database::query(
                'UPDATE gallery_categories SET name = ?, slug = ?, sort_order = ? WHERE id = ?',
                [$name, $slug, $sortOrder, $id]
            );
            return $id;
        }

        Database::query(
            'INSERT INTO gallery_categories (name, slug, sort_order) VALUES (?, ?, ?)',
            [$name, $slug, $sortOrder]
        );

        return (int)Database::lastInsertId();
    }
}
