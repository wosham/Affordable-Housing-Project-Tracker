<?php

class Migration131ConsultantFinalLiveTextFix
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            UPDATE ipc_approvals
            SET comments = 'IPC submitted for site verification.'
            WHERE comments LIKE '%demo%' OR comments LIKE '%seed%' OR comments LIKE '%Phase 48%'
        ");

        $projects = $pdo->query("
            SELECT p.id, p.constituency_id
            FROM projects p
            LEFT JOIN wards w ON w.id = p.ward_id
            WHERE p.ward_id IS NULL OR w.id IS NULL
        ")->fetchAll(PDO::FETCH_ASSOC);

        $wardByConstituency = $pdo->prepare('SELECT id FROM wards WHERE constituency_id = ? ORDER BY id ASC LIMIT 1');
        $firstWard = (int)$pdo->query('SELECT id FROM wards ORDER BY id ASC LIMIT 1')->fetchColumn();
        $update = $pdo->prepare('UPDATE projects SET ward_id = ? WHERE id = ?');

        foreach ($projects as $project) {
            $wardByConstituency->execute([(int)$project['constituency_id']]);
            $wardId = (int)$wardByConstituency->fetchColumn();
            if ($wardId <= 0) {
                $wardId = $firstWard;
            }
            if ($wardId > 0) {
                $update->execute([$wardId, (int)$project['id']]);
            }
        }
    }

    public function down(PDO $pdo): void
    {
    }
}
