<?php
class LeadershipSeeder
{
    public function run(\PDO $pdo): void
    {
        $profiles = [
            [1, 'H.E. George Natembeya', 'Governor, Trans-Nzoia County',               'Trans-Nzoia County Government'],
            [2, 'Moses Awuor',           'County Director — Affordable Housing',         'Trans-Nzoia County Government'],
            [3, 'CS Charles Hinga',      'Cabinet Secretary — Housing & Urban Dev.',     'State Department for Housing'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO leadership_profiles (sort_order, name, title, organisation) VALUES (?, ?, ?, ?)");
        foreach ($profiles as $p) { $stmt->execute($p); }
        echo "✓ Leadership profiles seeded.\n";
    }
}
