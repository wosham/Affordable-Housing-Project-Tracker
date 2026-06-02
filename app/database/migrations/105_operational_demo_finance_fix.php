<?php

class Migration105OperationalDemoFinanceFix
{
    public function up(PDO $pdo): void
    {
        $actorId = (int)$pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
        if ($actorId <= 0) {
            return;
        }

        $demoIpcs = $pdo->query('SELECT id FROM ipcs WHERE ipc_number = 0 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $number = 1;
        $update = $pdo->prepare('UPDATE ipcs SET ipc_number = ? WHERE id = ?');
        foreach ($demoIpcs as $ipcId) {
            $update->execute([$number, (int)$ipcId]);
            $number++;
        }

        if ((int)$pdo->query("SELECT COUNT(*) FROM payments WHERE reference_no LIKE 'DEMO-PAY-%'")->fetchColumn() > 0) {
            return;
        }

        $approvedIpc = $pdo->query(
            "SELECT id, project_id, net_amount
             FROM ipcs
             WHERE status = 'approved'
             ORDER BY approved_at DESC, id DESC
             LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        if (!$approvedIpc) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO payments (ipc_id, project_id, amount, payment_date, reference_no, bank, processed_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            (int)$approvedIpc['id'],
            (int)$approvedIpc['project_id'],
            (float)$approvedIpc['net_amount'],
            date('Y-m-d'),
            'DEMO-PAY-' . date('Ym') . '-001',
            'Central Bank Programme Account',
            $actorId,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM payments WHERE reference_no LIKE 'DEMO-PAY-%'");
    }
}
