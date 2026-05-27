<?php
class CmsPageSeeder
{
    public function run(\PDO $pdo): void
    {
        $pages = [
            ['home',                  'published', 'Trans-Nzoia Affordable Housing Programme Tracker'],
            ['about',                 'published', 'About the Programme | AHPTC'],
            ['projects',              'published', 'All Projects | AHPTC'],
            ['constituencies',        'published', 'Constituencies | AHPTC'],
            ['news',                  'published', 'News & Updates | AHPTC'],
            ['gallery',               'published', 'Gallery | AHPTC'],
            ['contact',               'published', 'Contact Us | AHPTC'],
            ['faq',                   'published', 'Frequently Asked Questions | AHPTC'],
            ['leadership',            'published', 'Leadership | AHPTC'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO cms_pages (slug, status, seo_title) VALUES (?, ?, ?)");
        foreach ($pages as $p) { $stmt->execute($p); }
        echo "✓ CMS pages seeded.\n";
    }
}
