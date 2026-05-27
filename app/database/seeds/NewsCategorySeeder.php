<?php
class NewsCategorySeeder
{
    public function run(\PDO $pdo): void
    {
        $categories = [
            ['Programme Updates',  'programme-updates', '#163300'],
            ['Site Progress',      'site-progress',     '#1a4a00'],
            ['Community Stories',  'community-stories', '#0a3d62'],
            ['Tenders & Notices',  'tenders-notices',   '#6a1a00'],
            ['Events',             'events',            '#4a3500'],
            ['Press Releases',     'press-releases',    '#1a3a4a'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO news_categories (name, slug, color) VALUES (?, ?, ?)");
        foreach ($categories as $c) { $stmt->execute($c); }
        echo "✓ News categories seeded.\n";
    }
}
