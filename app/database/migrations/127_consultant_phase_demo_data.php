<?php

class Migration127ConsultantPhaseDemoData
{
    public function up(PDO $pdo): void
    {
        $consultantId = $this->userIdByRole($pdo, 'consultant');
        $managerId = $this->userIdByRole($pdo, 'manager') ?: $this->userIdByRole($pdo, 'superadmin') ?: $consultantId;
        $contractorId = $this->userIdByRole($pdo, 'contractor') ?: $managerId ?: $consultantId;

        if (!$consultantId || !$contractorId) {
            return;
        }

        $projects = $this->projects($pdo, 3);
        foreach ($projects as $index => $project) {
            $projectId = (int)$project['id'];
            $this->assignConsultant($pdo, $projectId, $consultantId, $managerId ?: $consultantId, $index === 0);
            $this->setProjectConsultant($pdo, $projectId, $consultantId, $contractorId);
            $boqIds = $this->ensureBoqItems($pdo, $projectId, $consultantId);
            $this->ensureIpcPack($pdo, $projectId, $contractorId, $consultantId, $boqIds, $index);
        }
    }

    public function down(PDO $pdo): void
    {
        $stmt = $pdo->prepare("SELECT id FROM ipcs WHERE rejection_reason LIKE ? OR certification_comment LIKE ?");
        $stmt->execute(['%Phase 48 demo%', '%Phase 48 demo%']);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM ipcs WHERE id IN ({$placeholders})")->execute($ids);
        }
    }

    private function userIdByRole(PDO $pdo, string $role): int
    {
        $stmt = $pdo->prepare("
            SELECT u.id
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE r.slug = ? AND u.status = 'active'
            ORDER BY u.id ASC
            LIMIT 1
        ");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    private function projects(PDO $pdo, int $limit): array
    {
        $stmt = $pdo->prepare("
            SELECT id, name, contract_sum
            FROM projects
            WHERE status IN ('active', 'planning', 'on_hold')
            ORDER BY FIELD(status, 'active', 'planning', 'on_hold'), id ASC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function assignConsultant(PDO $pdo, int $projectId, int $consultantId, int $assignedBy, bool $primary): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO project_assignments
                (project_id, user_id, role, assignment_type, scope, status, start_date, is_primary, notes, assigned_by, updated_by)
            VALUES
                (?, ?, 'consultant', 'project', 'ipc-certification', 'active', CURDATE(), ?, 'Phase 48 demo consultant assignment', ?, ?)
            ON DUPLICATE KEY UPDATE
                role = VALUES(role),
                assignment_type = VALUES(assignment_type),
                scope = VALUES(scope),
                status = 'active',
                is_primary = VALUES(is_primary),
                notes = VALUES(notes),
                updated_by = VALUES(updated_by)
        ");
        $stmt->execute([$projectId, $consultantId, $primary ? 1 : 0, $assignedBy, $assignedBy]);
    }

    private function setProjectConsultant(PDO $pdo, int $projectId, int $consultantId, int $contractorId): void
    {
        $stmt = $pdo->prepare("
            UPDATE projects
            SET consultant_id = COALESCE(consultant_id, ?),
                contractor_id = COALESCE(contractor_id, ?),
                status = CASE WHEN status = 'planning' THEN 'active' ELSE status END
            WHERE id = ?
        ");
        $stmt->execute([$consultantId, $contractorId, $projectId]);
    }

    private function ensureBoqItems(PDO $pdo, int $projectId, int $userId): array
    {
        $existing = $pdo->prepare('SELECT id FROM boq_items WHERE project_id = ? ORDER BY id ASC LIMIT 4');
        $existing->execute([$projectId]);
        $ids = array_map('intval', $existing->fetchAll(PDO::FETCH_COLUMN));
        if (count($ids) >= 3) {
            return $ids;
        }

        $items = [
            ['Substructure', 'D01', 'Foundation excavation and cart away', 'm3', 120, 3200],
            ['Frame', 'D02', 'Reinforced concrete columns and beams', 'm3', 85, 18500],
            ['Envelope', 'D03', 'Block walling and plaster preparation', 'm2', 640, 1450],
        ];
        foreach ($items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO boq_items
                    (project_id, section, item_no, description, unit, quantity, rate, amount, certified_qty, paid_qty, status, review_status, risk_status, updated_by)
                SELECT ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'active', 'reviewed', 'normal', ?
                FROM DUAL
                WHERE NOT EXISTS (SELECT 1 FROM boq_items WHERE project_id = ? AND item_no = ?)
            ");
            $amount = (float)$item[4] * (float)$item[5];
            $stmt->execute([$projectId, $item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $amount, $userId, $projectId, $item[1]]);
        }

        $existing->execute([$projectId]);
        return array_map('intval', $existing->fetchAll(PDO::FETCH_COLUMN));
    }

    private function ensureIpcPack(PDO $pdo, int $projectId, int $contractorId, int $consultantId, array $boqIds, int $index): void
    {
        if ($boqIds === []) {
            return;
        }

        $ipcNo = 40 + $index + 1;
        $status = $index === 0 ? 'clerk-endorsed' : ($index === 1 ? 'submitted' : 'certified');
        $periodFrom = date('Y-m-01', strtotime('-' . (2 - $index) . ' months'));
        $periodTo = date('Y-m-t', strtotime($periodFrom));
        $gross = 0.0;
        $linePayloads = [];

        foreach (array_slice($boqIds, 0, 3) as $lineIndex => $boqId) {
            $boq = $this->boq($pdo, $boqId);
            if (!$boq) {
                continue;
            }
            $qty = [18, 9, 75][$lineIndex] ?? 10;
            $amount = $qty * (float)$boq['rate'];
            $gross += $amount;
            $linePayloads[] = [$boqId, $boq['description'], $qty, $qty, $boq['rate'], $amount];
        }

        if ($gross <= 0) {
            return;
        }

        $retention = round($gross * 0.05, 2);
        $net = round($gross - $retention, 2);
        $existing = $pdo->prepare('SELECT id FROM ipcs WHERE project_id = ? AND ipc_number = ? LIMIT 1');
        $existing->execute([$projectId, $ipcNo]);
        $ipcId = (int)$existing->fetchColumn();

        if ($ipcId <= 0) {
            $stmt = $pdo->prepare("
                INSERT INTO ipcs
                    (project_id, contractor_id, ipc_number, period_from, period_to, gross_amount, retention_amount, net_amount, status, submitted_at, certified_at, certified_by, certification_comment)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), ?, ?, ?)
            ");
            $certifiedAt = $status === 'certified' ? date('Y-m-d H:i:s', strtotime('-10 days')) : null;
            $stmt->execute([
                $projectId,
                $contractorId,
                $ipcNo,
                $periodFrom,
                $periodTo,
                $gross,
                $retention,
                $net,
                $status,
                12 - $index,
                $certifiedAt,
                $status === 'certified' ? $consultantId : null,
                $status === 'certified' ? 'Phase 48 demo certified IPC.' : null,
            ]);
            $ipcId = (int)$pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare("
                UPDATE ipcs
                SET contractor_id = ?, gross_amount = ?, retention_amount = ?, net_amount = ?, status = ?,
                    submitted_at = COALESCE(submitted_at, DATE_SUB(NOW(), INTERVAL ? DAY))
                WHERE id = ?
            ");
            $stmt->execute([$contractorId, $gross, $retention, $net, $status, 12 - $index, $ipcId]);
        }

        foreach ($linePayloads as $line) {
            $stmt = $pdo->prepare("
                INSERT INTO ipc_lines (ipc_id, boq_item_id, description, qty_this_period, cumulative_qty, rate, amount)
                SELECT ?, ?, ?, ?, ?, ?, ?
                FROM DUAL
                WHERE NOT EXISTS (SELECT 1 FROM ipc_lines WHERE ipc_id = ? AND boq_item_id = ?)
            ");
            $stmt->execute([$ipcId, $line[0], $line[1], $line[2], $line[3], $line[4], $line[5], $ipcId, $line[0]]);
        }

        $this->ensureApprovalTrail($pdo, $ipcId, $contractorId, $consultantId, $status);
    }

    private function boq(PDO $pdo, int $boqId): ?array
    {
        $stmt = $pdo->prepare('SELECT id, description, rate FROM boq_items WHERE id = ? LIMIT 1');
        $stmt->execute([$boqId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function ensureApprovalTrail(PDO $pdo, int $ipcId, int $contractorId, int $consultantId, string $status): void
    {
        $this->approval($pdo, $ipcId, 1, $contractorId, 'endorsed', 'Phase 48 demo site verification completed.');
        if (in_array($status, ['certified', 'endorsed', 'approved', 'paid'], true)) {
            $this->approval($pdo, $ipcId, 2, $consultantId, 'certified', 'Phase 48 demo consultant certification recorded.');
        }
    }

    private function approval(PDO $pdo, int $ipcId, int $step, int $actorId, string $action, string $comments): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO ipc_approvals (ipc_id, step, action_by, action, comments, actioned_at)
            SELECT ?, ?, ?, ?, ?, NOW()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM ipc_approvals WHERE ipc_id = ? AND step = ? AND action = ?)
        ");
        $stmt->execute([$ipcId, $step, $actorId, $action, $comments, $ipcId, $step, $action]);
    }
}
