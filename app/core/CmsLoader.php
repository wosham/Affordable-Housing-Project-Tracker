<?php

class CmsLoader
{
    private const HIDDEN_PUBLIC_SECTIONS = [
        'source_snapshot',
        'current_frontend_snapshot',
        'frontend_source_snapshot',
    ];

    private static array $pageCache = [];
    private static array $settingCache = [];

    public static function page(string $slug): array
    {
        return self::publicPage($slug);
    }

    public static function editablePage(string $slug): array
    {
        return self::loadPage($slug, ['published', 'draft']);
    }

    public static function publicPage(string $slug): array
    {
        return self::loadPage($slug, ['published']);
    }

    public static function pageByRoute(string $routePath, bool $publicOnly = true): array
    {
        $page = $publicOnly ? CmsPage::publicByRoute($routePath) : CmsPage::findByRoute($routePath);
        if (!$page) {
            return [];
        }

        return self::withSections($page);
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

    public static function setting(string $key, mixed $fallback = null): mixed
    {
        if (!array_key_exists($key, self::$settingCache)) {
            self::$settingCache[$key] = CmsSetting::get($key, $fallback);
        }

        $value = self::$settingCache[$key];
        return ($value === null || $value === '') ? $fallback : $value;
    }

    public static function settings(string $group): array
    {
        return CmsSetting::getGroup($group);
    }

    public static function settingText(string $key, string $fallback = ''): string
    {
        return CmsSetting::text($key, $fallback);
    }

    public static function settingUrl(string $key, string $fallback = ''): string
    {
        return CmsSetting::url($key, $fallback);
    }

    public static function settingJson(string $key, array $fallback = []): array
    {
        return CmsSetting::json($key, $fallback);
    }

    public static function settingBool(string $key, bool $fallback = false): bool
    {
        return CmsSetting::bool($key, $fallback);
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
        return self::mergeContentDefaults($defaults, $content);
    }

    public static function text(array $content, string $key, string $default = ''): string
    {
        return trim((string)($content[$key] ?? ''));
    }

    public static function bool(array $content, string $key, bool $default = true): bool
    {
        if (!array_key_exists($key, $content)) {
            return $default;
        }

        return in_array(strtolower((string)$content[$key]), ['1', 'true', 'yes', 'on'], true);
    }

    public static function pageContract(string $slug): array
    {
        $contracts = self::contracts();
        return $contracts[$slug] ?? [
            'route' => $slug === 'home' ? 'index.php' : $slug . '.php',
            'cms_sections' => [],
            'models' => [],
            'fallback' => 'snapshot',
        ];
    }

    public static function contracts(): array
    {
        return self::settingJson('frontend_data_contract', []);
    }

    public static function publicPayload(string $slug, array $modelData = [], array $fallback = []): array
    {
        $page = self::publicPage($slug);

        return [
            'slug' => $slug,
            'page' => [
                'title' => $page['seo_title'] ?? '',
                'description' => $page['seo_description'] ?? '',
                'route' => $page['route_path'] ?? self::pageContract($slug)['route'] ?? '',
            ],
            'sections' => self::sectionContentMap($page),
            'models' => $modelData,
            'contract' => self::pageContract($slug),
            'fallback' => [],
        ];
    }

    public static function sectionContentMap(array $page): array
    {
        $content = [];
        foreach (($page['sections'] ?? []) as $key => $section) {
            $content[(string)$key] = is_array($section['content'] ?? null) ? $section['content'] : [];
        }

        return $content;
    }

    public static function jsonScript(string $id, array $payload): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '-', $id) ?: 'page-data';
        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        return '<script type="application/json" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . ($json ?: '{}') . '</script>';
    }

    private static function loadPage(string $slug, array $statuses): array
    {
        $cacheKey = $slug . ':' . implode(',', $statuses);
        if (isset(self::$pageCache[$cacheKey])) {
            return self::$pageCache[$cacheKey];
        }

        $page = CmsPage::findBySlug($slug);
        if (!$page || !in_array((string)$page['status'], $statuses, true)) {
            return self::$pageCache[$cacheKey] = [];
        }

        return self::$pageCache[$cacheKey] = self::withSections($page);
    }

    private static function mergeContentDefaults(array $defaults, array $content): array
    {
        if ($defaults === []) {
            return $content;
        }

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $content)) {
                $content[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($content[$key])) {
                $content[$key] = self::mergeContentDefaults($value, $content[$key]);
            }
        }

        return $content;
    }

    private static function withSections(array $page): array
    {
        $sections = [];
        foreach (CmsSection::forPage((int)$page['id']) as $section) {
            $sectionKey = (string)($section['section_key'] ?? '');
            if ((int)($section['is_visible'] ?? 0) !== 1 || in_array($sectionKey, self::HIDDEN_PUBLIC_SECTIONS, true)) {
                continue;
            }

            $sections[$sectionKey] = $section;
        }

        $page['sections'] = $sections;
        return $page;
    }
}
