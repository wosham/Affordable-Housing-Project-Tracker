<?php

class Migration128ConsultantDemoIpcLineBalance
{
    public function up(PDO $pdo): void
    {
        $rows = $pdo->query("
            SELECT il.id, il.ipc_id, il.boq_item_id, bi.quantity, bi.rate
            FROM ipc_lines il
            JOIN boq_items bi ON bi.id = il.boq_item_id
            JOIN ipcs i ON i.id = il.ipc_id
            WHERE i.ipc_number BETWEEN 41 AND 43
              AND i.submitted_at IS NOT NULL
              AND COALESCE(bi.quantity, 0) > 0
        ")->fetchAll(PDO::FETCH_ASSOC);

        $lineUpdate = $pdo->prepare("
            UPDATE ipc_lines
            SET qty_this_period = ?, cumulative_qty = ?, rate = ?, amount = ?
            WHERE id = ?
        ");

        foreach ($rows as $row) {
            $quantity = (float)$row['quantity'];
            $rate = (float)$row['rate'];
            $qty = max(1, round($quantity * 0.20, 3));
            if ($qty > $quantity) {
                $qty = $quantity;
            }
            $amount = round($qty * $rate, 2);
            $lineUpdate->execute([$qty, $qty, $rate, $amount, (int)$row['id']]);
        }

        $ipcs = $pdo->query("
            SELECT DISTINCT i.id
            FROM ipcs i
            JOIN ipc_lines il ON il.ipc_id = i.id
            WHERE i.ipc_number BETWEEN 41 AND 43
        ")->fetchAll(PDO::FETCH_COLUMN);

        $ipcUpdate = $pdo->prepare("
            UPDATE ipcs
            SET gross_amount = ?,
                retention_amount = ?,
                net_amount = ?
            WHERE id = ?
        ");
        $totalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM ipc_lines WHERE ipc_id = ?');

        foreach ($ipcs as $ipcId) {
            $totalStmt->execute([(int)$ipcId]);
            $gross = round((float)$totalStmt->fetchColumn(), 2);
            $retention = round($gross * 0.05, 2);
            $net = round($gross - $retention, 2);
            $ipcUpdate->execute([$gross, $retention, $net, (int)$ipcId]);
        }
    }

    public function down(PDO $pdo): void
    {
    }
}
