<?php

class Migration103OperationalDemoRecords
{
    public function up(PDO $pdo): void
    {
        $actorId = (int)$pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
        if ($actorId <= 0) {
            return;
        }

        $projects = $pdo->query('SELECT id, name FROM projects ORDER BY id LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
        if ($projects === []) {
            return;
        }

        $this->seedBoq($pdo, $projects, $actorId);
        $this->seedProgramme($pdo, $projects, $actorId);
        $this->seedIpcs($pdo, $projects, $actorId);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM ipc_approvals WHERE comments LIKE 'Demo seed:%'");
        $pdo->exec("DELETE FROM ipc_lines WHERE description LIKE 'Demo seed:%'");
        $pdo->exec("DELETE FROM ipcs WHERE ipc_number LIKE 'DEMO-%'");
        $pdo->exec("DELETE FROM programme_tasks WHERE notes LIKE 'Demo seed:%'");
        $pdo->exec("DELETE FROM boq_items WHERE notes LIKE 'Demo seed:%'");
    }

    private function seedBoq(PDO $pdo, array $projects, int $actorId): void
    {
        if ((int)$pdo->query('SELECT COUNT(*) FROM boq_items')->fetchColumn() > 0) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO boq_items
                (project_id, section, item_no, description, unit, quantity, rate, amount, certified_qty, paid_qty, status, updated_by, last_certified_at, last_paid_at, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)'
        );

        foreach ($projects as $index => $project) {
            $base = $index + 1;
            $items = [
                ['Preliminaries', 'P-' . $base . '-001', 'Demo seed: site establishment and temporary works', 'LS', 1, 2500000, 2500000, 1, 1, 'active'],
                ['Substructure', 'S-' . $base . '-010', 'Demo seed: reinforced concrete strip foundations', 'm3', 180, 18500, 3330000, 120, 90, 'active'],
                ['Superstructure', 'W-' . $base . '-020', 'Demo seed: masonry walling to housing blocks', 'm2', 2200, 2800, 6160000, 980, 650, 'active'],
            ];

            foreach ($items as $item) {
                $stmt->execute([
                    (int)$project['id'],
                    $item[0],
                    $item[1],
                    $item[2],
                    $item[3],
                    $item[4],
                    $item[5],
                    $item[6],
                    $item[7],
                    $item[8],
                    $item[9],
                    $actorId,
                    'Demo seed: BOQ centre verification data.',
                ]);
            }
        }
    }

    private function seedProgramme(PDO $pdo, array $projects, int $actorId): void
    {
        if ((int)$pdo->query('SELECT COUNT(*) FROM programme_tasks')->fetchColumn() > 0) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO programme_tasks
                (project_id, task_name, start_date, end_date, planned_start, planned_end, pct_complete, depends_on_task_id, assigned_to, status, sort_order, critical_path, baseline_start, baseline_end, notes, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($projects as $index => $project) {
            $offset = $index * 7;
            $tasks = [
                ['Demo seed: mobilisation and site setup', '-45 days', '-30 days', 100, 'complete', 10, 0],
                ['Demo seed: foundation works', '-29 days', '+14 days', 62, 'in_progress', 20, 1],
                ['Demo seed: superstructure frame', '+15 days', '+70 days', 18, 'in_progress', 30, 1],
                ['Demo seed: roofing and finishes', '+71 days', '+130 days', 0, 'pending', 40, 0],
            ];

            foreach ($tasks as $task) {
                $plannedStart = date('Y-m-d', strtotime($task[1] . ' +' . $offset . ' days'));
                $plannedEnd = date('Y-m-d', strtotime($task[2] . ' +' . $offset . ' days'));
                $actualStart = $task[4] === 'pending' ? null : $plannedStart;
                $actualEnd = $task[4] === 'complete' ? $plannedEnd : null;
                $stmt->execute([
                    (int)$project['id'],
                    $task[0],
                    $actualStart,
                    $actualEnd,
                    $plannedStart,
                    $plannedEnd,
                    $task[3],
                    $actorId,
                    $task[4],
                    $task[5],
                    $task[6],
                    $plannedStart,
                    $plannedEnd,
                    'Demo seed: programme-of-works verification data.',
                    $actorId,
                ]);
            }
        }
    }

    private function seedIpcs(PDO $pdo, array $projects, int $actorId): void
    {
        if ((int)$pdo->query('SELECT COUNT(*) FROM ipcs')->fetchColumn() > 0) {
            return;
        }

        $ipcStmt = $pdo->prepare(
            'INSERT INTO ipcs
                (project_id, contractor_id, ipc_number, period_from, period_to, gross_amount, retention_amount, net_amount, status, submitted_at, certified_at, approved_at, approved_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $approvalStmt = $pdo->prepare(
            'INSERT INTO ipc_approvals (ipc_id, step, action_by, action, comments, actioned_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );

        foreach ($projects as $index => $project) {
            $gross = 4200000 + ($index * 850000);
            $retention = round($gross * 0.05, 2);
            $net = $gross - $retention;
            $status = ['certified', 'endorsed', 'approved'][$index] ?? 'certified';
            $submitted = date('Y-m-d H:i:s', strtotime('-' . (12 + $index * 3) . ' days'));
            $certified = date('Y-m-d H:i:s', strtotime('-' . (8 + $index * 2) . ' days'));
            $approved = $status === 'approved' ? date('Y-m-d H:i:s', strtotime('-2 days')) : null;

            $ipcStmt->execute([
                (int)$project['id'],
                $actorId,
                'DEMO-' . date('Ym') . '-' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT),
                date('Y-m-01', strtotime('-1 month')),
                date('Y-m-t', strtotime('-1 month')),
                $gross,
                $retention,
                $net,
                $status,
                $submitted,
                $certified,
                $approved,
                $approved ? $actorId : null,
            ]);

            $ipcId = (int)$pdo->lastInsertId();
            $approvalStmt->execute([$ipcId, 1, $actorId, 'submitted', 'Demo seed: contractor IPC submitted.']);
            $approvalStmt->execute([$ipcId, 2, $actorId, 'certified', 'Demo seed: consultant certification recorded.']);
            if (in_array($status, ['endorsed', 'approved'], true)) {
                $approvalStmt->execute([$ipcId, 3, $actorId, 'endorsed', 'Demo seed: manager endorsement recorded.']);
            }
            if ($status === 'approved') {
                $approvalStmt->execute([$ipcId, 4, $actorId, 'approved', 'Demo seed: director approval recorded.']);
            }
        }
    }
}
