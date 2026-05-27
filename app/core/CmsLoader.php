<?php
// CmsLoader — loads dynamic CMS data for public pages from DB
// Falls back to static app/data/ arrays if DB unavailable
class CmsLoader
{
    public static function page(string $slug): array
    {
        // TODO: Phase 6 — Load cms_pages + cms_sections for given slug
        // Returns: ['status','seo_title','seo_description','sections' => [...]]
        return [];
    }

    public static function setting(string $key): mixed
    {
        // TODO: Phase 6 — Load single cms_settings value by key
        return null;
    }

    public static function settings(string $group): array
    {
        // TODO: Phase 6 — Load all cms_settings for a group (stats, contact, ticker)
        return [];
    }

    public static function isPageVisible(string $slug): bool
    {
        // TODO: Phase 6 — Return false if page status is 'hidden' or 'maintenance'
        return true;
    }
}
