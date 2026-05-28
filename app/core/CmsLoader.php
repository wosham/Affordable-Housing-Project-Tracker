<?php

class CmsLoader
{
    public static function page(string $slug): array
    {
        $page = CmsPage::findBySlug($slug);
        if (!$page || !in_array((string)$page['status'], ['published', 'draft'], true)) {
            return [];
        }

        $sections = [];
        foreach (CmsSection::forPage((int)$page['id']) as $section) {
            if ((int)($section['is_visible'] ?? 0) !== 1 || ($section['section_key'] ?? '') === 'source_snapshot') {
                continue;
            }

            $sections[(string)$section['section_key']] = $section;
        }

        $page['sections'] = $sections;
        return $page;
    }

    public static function section(array $page, string $sectionKey): ?array
    {
        return $page['sections'][$sectionKey] ?? null;
    }

    public static function visible(array $page, string $sectionKey): bool
    {
        $section = self::section($page, $sectionKey);
        return $section !== null && (int)($section['is_visible'] ?? 0) === 1;
    }

    public static function setting(string $key): mixed
    {
        return CmsSetting::get($key);
    }

    public static function settings(string $group): array
    {
        $settings = [];
        foreach (CmsSetting::getGroup($group) as $setting) {
            $settings[(string)$setting['key']] = $setting['cast_value'] ?? $setting['value'] ?? null;
        }

        return $settings;
    }

    public static function isPageVisible(string $slug): bool
    {
        $page = CmsPage::findBySlug($slug);
        return $page !== null && (string)($page['status'] ?? '') === 'published';
    }

    public static function content(array $page, string $sectionKey, array $defaults = []): array
    {
        $section = $page['sections'][$sectionKey] ?? null;
        $content = is_array($section['content'] ?? null) ? $section['content'] : [];
        return array_merge($defaults, $content);
    }

    public static function text(array $content, string $key, string $default = ''): string
    {
        $value = $content[$key] ?? $default;
        return trim((string)$value) !== '' ? (string)$value : $default;
    }

    public static function bool(array $content, string $key, bool $default = true): bool
    {
        if (!array_key_exists($key, $content)) {
            return $default;
        }

        return in_array(strtolower((string)$content[$key]), ['1', 'true', 'yes', 'on'], true);
    }
}
