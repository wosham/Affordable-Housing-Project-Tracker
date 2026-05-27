<?php
class ConstituencySeeder
{
    public function run(\PDO $pdo): void
    {
        $constituencies = [
            ['Kwanza',          'kwanza'],
            ['Endebess',        'endebess'],
            ['Saboti',          'saboti'],
            ['Kiminini',        'kiminini'],
            ['Cherangany',      'cherangany'],
            ['Trans-Nzoia East','trans-nzoia-east'],
            ['Trans-Nzoia West','trans-nzoia-west'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO constituencies (name, slug) VALUES (?, ?)");
        foreach ($constituencies as $c) { $stmt->execute($c); }
        echo "✓ Constituencies seeded.\n";
    }
}
