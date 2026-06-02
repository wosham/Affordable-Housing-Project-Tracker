<?php

class Migration104OperationalFinanceDemoRecords
{
    public function up(PDO $pdo): void
    {
        $actorId = (int)$pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
        if ($actorId <= 0) {
            return;
        }

        $projects = $pdo->query('SELECT id FROM projects ORDER BY id LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
        if ($projects === []) {
            return;
        }

        $contractSums = [285000000, 148000000, 196000000];
        $update = $pdo->prepare('UPDATE projects SET contract_sum = COALESCE(contract_sum, ?) WHERE id = ?');
        foreach ($projects as $index => $projectId) {
            $update->execute([$contractSums[$index] ?? 125000000, (int)$projectId]);
        }

        if ((int)$pdo->query("SELECT COUNT(*) FROM payments WHERE reference_no LIKE 'DEMO-PAY-%'")->fetchColumn() === 0) {
            $payment = $pdo->prepare(
                'INSERT INTO payments (ipc_id, project_id, amount, payment_date, reference_no, bank, processed_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $approvedIpc = $pdo->query("SELECT id, project_id, net_amount FROM ipcs WHERE ipc_number LIKE 'DEMO-%' AND status = 'approved' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($approvedIpc) {
                $payment->execute([
                    (int)$approvedIpc['id'],
                    (int)$approvedIpc['project_id'],
                    (float)$approvedIpc['net_amount'],
                    date('Y-m-d'),
                    'DEMO-PAY-' . date('Ym') . '-001',
                    'Central Bank Programme Account',
                    $actorId,
                ]);
            }
        }

        if ((int)$pdo->query('SELECT COUNT(*) FROM retention')->fetchColumn() === 0) {
            $retention = $pdo->prepare(
                'INSERT INTO retention (project_id, total_held, released_amount, release_date, release_reason, processed_by, created_at)
                 VALUES (?, ?, ?, NULL, ?, ?, NOW())'
            );
            foreach ($projects as $index => $projectId) {
                $retention->execute([
                    (int)$projectId,
                    [210000, 252500, 295000][$index] ?? 150000,
                    0,
                    'Demo seed: retention held pending defect liability period.',
                    $actorId,
                ]);
            }
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM payments WHERE reference_no LIKE 'DEMO-PAY-%'");
        $pdo->exec("DELETE FROM retention WHERE release_reason LIKE 'Demo seed:%'");
    }
}
