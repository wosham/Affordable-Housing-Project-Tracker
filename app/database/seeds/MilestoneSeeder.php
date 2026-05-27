<?php
class MilestoneSeeder
{
    public function run(\PDO $pdo): void
    {
        $projects = $pdo->query("SELECT id FROM projects")->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($projects)) { echo "  ⚠ MilestoneSeeder: Run ProjectSeeder first.\n"; return; }

        $defaultMilestones = [
            [1, 'Site Handover'],
            [2, 'Foundation Works'],
            [3, 'Walling & Roofing'],
            [4, 'Finishing Works'],
            [5, 'Practical Completion'],
            [6, 'Defects Liability Period End'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO milestones (project_id, sequence, label, status) VALUES (?, ?, ?, 'pending')");
        foreach ($projects as $pid) {
            foreach ($defaultMilestones as [$seq, $label]) {
                $stmt->execute([$pid, $seq, $label]);
            }
        }
        echo "✓ Default milestones seeded for all projects.\n";
    }
}
