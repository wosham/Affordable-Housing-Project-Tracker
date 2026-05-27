<?php
class ProjectCategorySeeder
{
    public function run(\PDO $pdo): void
    {
        $categories = [
            ['Affordable Housing',   'affordable-housing',   'fa-home',     '#163300'],
            ['Markets',              'markets',              'fa-store',    '#1a4a00'],
            ['Institutional',        'institutional',        'fa-building', '#0a3d62'],
            ['Schools',              'schools',              'fa-school',   '#6a1a00'],
            ['Infrastructure',       'infrastructure',       'fa-road',     '#4a3500'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO project_categories (name, slug, icon, color) VALUES (?, ?, ?, ?)");
        foreach ($categories as $c) { $stmt->execute($c); }
        echo "✓ Project categories seeded.\n";
    }
}
