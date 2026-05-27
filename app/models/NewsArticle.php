<?php
class NewsArticle extends Model
{
    protected static string $table = 'news_articles';
    // Columns: id, category_id, author_id, title, slug, excerpt, body,
    //          featured_image_id, status, published_at, scheduled_for, views,
    //          seo_title, seo_description, og_image_id
    // Status: draft, published, scheduled, archived

    public static function published(int $limit = 10): array
    {
        // TODO: Phase 6 — SELECT WHERE status='published' ORDER BY published_at DESC
        return [];
    }

    public static function findBySlug(string $slug): ?array
    {
        // TODO: Phase 6 — SELECT WHERE slug = :slug AND status = 'published'
        return null;
    }
}
