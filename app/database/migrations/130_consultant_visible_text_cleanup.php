<?php

class Migration130ConsultantVisibleTextCleanup
{
    public function up(PDO $pdo): void
    {
        $this->cleanDescriptions($pdo);
        $this->cleanNotes($pdo);
        $this->fillMissingWards($pdo);
    }

    public function down(PDO $pdo): void
    {
    }

    private function cleanDescriptions(PDO $pdo): void
    {
        $map = [
            '%site establishment and temporary works%' => 'Site establishment and temporary works',
            '%reinforced concrete strip foundations%' => 'Reinforced concrete strip foundations',
            '%masonry walling to housing blocks%' => 'Masonry walling to housing blocks',
        ];

        foreach (['boq_items', 'ipc_lines'] as $table) {
            foreach ($map as $pattern => $value) {
                $stmt = $pdo->prepare("UPDATE {$table} SET description = ? WHERE LOWER(description) LIKE LOWER(?)");
                $stmt->execute([$value, $pattern]);
            }
        }

        $pdo->exec("UPDATE boq_items SET description = REPLACE(REPLACE(REPLACE(description, 'Demo seed: ', ''), 'demo seed: ', ''), 'Phase 48 ', '')");
        $pdo->exec("UPDATE ipc_lines SET description = REPLACE(REPLACE(REPLACE(description, 'Demo seed: ', ''), 'demo seed: ', ''), 'Phase 48 ', '')");
    }

    private function cleanNotes(PDO $pdo): void
    {
        $pdo->exec("
            UPDATE project_assignments
            SET notes = CASE
                WHEN role = 'manager' THEN 'Project manager assigned for delivery oversight.'
                WHEN role = 'consultant' THEN 'Consultant assigned for technical and IPC certification review.'
                ELSE 'Project role assignment is active.'
            END
            WHERE notes LIKE '%demo%' OR notes LIKE '%seed%' OR notes LIKE '%Phase 48%'
        ");

        $pdo->exec("
            UPDATE ipc_approvals
            SET comments = CASE
                WHEN action = 'endorsed' THEN 'Site verification completed and quantities checked against site records.'
                WHEN action = 'certified' THEN 'Certified after review of quantities, amounts and supporting records.'
                WHEN action = 'approved' THEN 'Approved for onward processing.'
                WHEN action = 'rejected' THEN 'Returned for correction after review.'
                ELSE comments
            END
            WHERE comments LIKE '%demo%' OR comments LIKE '%seed%' OR comments LIKE '%Phase 48%'
        ");
    }

    private function fillMissingWards(PDO $pdo): void
    {
        $projects = $pdo->query('SELECT id, constituency_id FROM projects WHERE ward_id IS NULL')->fetchAll(PDO::FETCH_ASSOC);
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
}
