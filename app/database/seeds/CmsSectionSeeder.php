<?php
class CmsSectionSeeder
{
    public function run(\PDO $pdo): void
    {
        // Default sections for the home page (all visible by default)
        $homeId = $pdo->query("SELECT id FROM cms_pages WHERE slug='home'")->fetchColumn();
        if (!$homeId) { echo "  ⚠ CmsSectionSeeder: Run CmsPageSeeder first.\n"; return; }

        $sections = [
            [$homeId, 'hero',         'Hero Banner',           1],
            [$homeId, 'ticker',       'News Ticker',           1],
            [$homeId, 'stats',        'Statistics Bar',        1],
            [$homeId, 'about',        'About Section',         1],
            [$homeId, 'projects',     'Featured Projects',     1],
            [$homeId, 'map',          'Project Map',           1],
            [$homeId, 'news',         'Latest News',           1],
            [$homeId, 'gallery',      'Photo Gallery',         1],
            [$homeId, 'cta',          'Call to Action',        1],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO cms_sections (page_id, section_key, label, is_visible) VALUES (?, ?, ?, ?)");
        foreach ($sections as $s) { $stmt->execute($s); }
        echo "✓ CMS sections seeded for home page.\n";
    }
}
