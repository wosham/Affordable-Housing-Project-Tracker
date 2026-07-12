<?php

class Migration164PresentationDashboardData
{
    public function up(\PDO $pdo): void
    {
        $directorId = (int)($pdo->query("SELECT id FROM users WHERE email = 'joseph.r.wenani@gmail.com' LIMIT 1")->fetchColumn() ?: 1);
        $contractorId = (int)($pdo->query("SELECT id FROM users WHERE email = 'david.barasa.ahp@gmail.com' LIMIT 1")->fetchColumn() ?: $directorId);
        $clerkId = (int)($pdo->query("SELECT id FROM users WHERE email = 'lilian.naliaka.ahp@gmail.com' LIMIT 1")->fetchColumn() ?: $directorId);

        $this->completeProjectCommercials($pdo);
        $this->seedPaidIpcs($pdo, $directorId, $contractorId);
        $this->seedAttendance($pdo, $clerkId, $directorId);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DELETE FROM attendance_records WHERE date = CURDATE() AND review_status = 'accepted' AND accuracy_meters IN (8.0, 10.0, 12.0, 14.0, 16.0)");
        $pdo->exec("DELETE FROM attendance_gateways WHERE date = CURDATE() AND notes = 'Site attendance window opened for active works.'");
        $pdo->exec("DELETE FROM payments WHERE reference_no LIKE 'IFMIS-AHP-2026-%'");
        $pdo->exec("DELETE FROM ipc_approvals WHERE comments = 'Payment certificate reviewed through the approval workflow.'");
        $pdo->exec("DELETE FROM ipcs WHERE contractor_reference LIKE 'AHP-2026-Q2-%'");
    }

    private function completeProjectCommercials(\PDO $pdo): void
    {
        $values = [
            'suam-border-estate' => [132000000, 150, 28, 'active'],
            'endebess-township-units' => [72000000, 60, 18, 'active'],
            'kiminini-ahp-phase1' => [118000000, 80, 12, 'planning'],
            'waitaluk-estate' => [64000000, 40, 10, 'planning'],
            'kwanza-township-housing' => [92000000, 80, 14, 'planning'],
        ];

        $stmt = $pdo->prepare(
            'UPDATE projects
             SET contract_sum = COALESCE(contract_sum, ?),
                 units = COALESCE(units, ?),
                 pct_complete = GREATEST(COALESCE(pct_complete, 0), ?),
                 status = CASE WHEN status IS NULL OR status = "" THEN ? ELSE status END
             WHERE slug = ?'
        );

        foreach ($values as $slug => [$contractSum, $units, $progress, $status]) {
            $stmt->execute([$contractSum, $units, $progress, $status, $slug]);
        }
    }

    private function seedPaidIpcs(\PDO $pdo, int $directorId, int $contractorId): void
    {
        $projectIds = $this->projectIds($pdo);
        $rows = [
            ['maili-tatu-estate', 44, 'AHP-2026-Q2-044', 8500000, 425000, 8075000, '-18 days'],
            ['kitale-infill-units', 45, 'AHP-2026-Q2-045', 7000000, 350000, 6650000, '-12 days'],
            ['suam-border-estate', 46, 'AHP-2026-Q2-046', 4200000, 210000, 3990000, '-7 days'],
        ];

        $insertIpc = $pdo->prepare(
            "INSERT INTO ipcs
                (project_id, contractor_id, ipc_number, contractor_reference, period_from, period_to,
                 gross_amount, retention_amount, net_amount, declaration_accepted, status,
                 current_stage, submitted_at, certified_at, certified_by, certification_comment,
                 approved_at, approved_by, paid_at, paid_by, payment_status, payment_reference, payment_comment)
             SELECT ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY),
                    ?, ?, ?, 1, 'paid',
                    'paid', DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), ?, 'Certified for measured works.',
                    DATE_SUB(NOW(), INTERVAL 8 DAY), ?, DATE_SUB(NOW(), INTERVAL 5 DAY), ?, 'paid', ?, 'Processed through finance.'
             WHERE NOT EXISTS (SELECT 1 FROM ipcs WHERE contractor_reference = ?)"
        );
        $findIpc = $pdo->prepare('SELECT id, project_id, net_amount, payment_reference FROM ipcs WHERE contractor_reference = ? LIMIT 1');
        $insertPayment = $pdo->prepare(
            "INSERT INTO payments
                (ipc_id, project_id, amount, payment_date, reference_no, voucher_no, bank, payment_method,
                 processed_by, processed_at, status, notes)
             SELECT ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 5 DAY), ?, ?, 'Central Bank Programme Account',
                    'treasury_transfer', ?, DATE_SUB(NOW(), INTERVAL 5 DAY), 'processed',
                    'Processed through county finance workflow.'
             WHERE NOT EXISTS (SELECT 1 FROM payments WHERE reference_no = ?)"
        );
        $insertApproval = $pdo->prepare(
            "INSERT INTO ipc_approvals (ipc_id, step, action_by, action, comments, actioned_at)
             SELECT ?, ?, ?, ?, 'Payment certificate reviewed through the approval workflow.', DATE_SUB(NOW(), INTERVAL ? DAY)
             WHERE NOT EXISTS (
                 SELECT 1 FROM ipc_approvals WHERE ipc_id = ? AND step = ? AND action = ?
             )"
        );

        foreach ($rows as [$slug, $number, $reference, $gross, $retention, $net, $age]) {
            if (empty($projectIds[$slug])) {
                continue;
            }

            $projectId = $projectIds[$slug];
            $paymentReference = 'IFMIS-AHP-2026-' . $number;
            $insertIpc->execute([
                $projectId,
                $contractorId,
                $number,
                $reference,
                $gross,
                $retention,
                $net,
                $directorId,
                $directorId,
                $directorId,
                $paymentReference,
                $reference,
            ]);

            $findIpc->execute([$reference]);
            $ipc = $findIpc->fetch(\PDO::FETCH_ASSOC);
            if (!$ipc) {
                continue;
            }

            $ipcId = (int)$ipc['id'];
            foreach ([[1, 'submitted', 14], [2, 'certified', 10], [3, 'endorsed', 9], [4, 'approved', 8], [5, 'paid', 5]] as [$step, $action, $days]) {
                $insertApproval->execute([$ipcId, $step, $directorId, $action, $days, $ipcId, $step, $action]);
            }

            $insertPayment->execute([
                $ipcId,
                (int)$ipc['project_id'],
                (float)$ipc['net_amount'],
                $paymentReference,
                'PV-AHP-' . $number,
                $directorId,
                $paymentReference,
            ]);
        }

        $pdo->exec(
            "UPDATE ipcs i
             JOIN payments p ON p.ipc_id = i.id AND p.status = 'processed'
             SET i.status = 'paid',
                 i.payment_status = 'paid',
                 i.paid_at = COALESCE(i.paid_at, p.processed_at, CONCAT(p.payment_date, ' 12:00:00')),
                 i.paid_by = COALESCE(i.paid_by, p.processed_by),
                 i.payment_reference = COALESCE(i.payment_reference, p.reference_no)
             WHERE i.id = 3"
        );
    }

    private function seedAttendance(\PDO $pdo, int $clerkId, int $directorId): void
    {
        $projectIds = $this->projectIds($pdo);
        $users = $this->attendanceUsers($pdo);
        $projects = [
            'maili-tatu-estate' => ['intern' => [14], 'clerk' => [12], 'contractor' => [11], 'manager' => [9]],
            'kitale-infill-units' => ['intern' => [17], 'clerk' => [15], 'contractor' => [16], 'manager' => [10]],
        ];

        $insertGateway = $pdo->prepare(
            "INSERT INTO attendance_gateways (project_id, date, opened_by, opened_at, closes_at, is_open, notes)
             SELECT ?, CURDATE(), ?, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_ADD(NOW(), INTERVAL 4 HOUR), 1,
                    'Site attendance window opened for active works.'
             WHERE NOT EXISTS (SELECT 1 FROM attendance_gateways WHERE project_id = ? AND date = CURDATE())"
        );
        $gatewayId = $pdo->prepare('SELECT id FROM attendance_gateways WHERE project_id = ? AND date = CURDATE() LIMIT 1');
        $insertAttendance = $pdo->prepare(
            "INSERT INTO attendance_records
                (user_id, project_id, role_at_signin, gateway_id, date, signin_time, latitude, longitude,
                 distance_from_site_m, accuracy_meters, status, review_status, reviewed_by, reviewed_at)
             SELECT ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, 'accepted', ?, NOW()
             WHERE NOT EXISTS (
                 SELECT 1 FROM attendance_records WHERE user_id = ? AND date = CURDATE()
             )"
        );

        $offset = 0;
        foreach ($projects as $slug => $roles) {
            if (empty($projectIds[$slug])) {
                continue;
            }

            $projectId = $projectIds[$slug];
            $insertGateway->execute([$projectId, $clerkId, $projectId]);
            $gatewayId->execute([$projectId]);
            $gateway = (int)($gatewayId->fetchColumn() ?: 0);
            if ($gateway <= 0) {
                continue;
            }

            foreach ($roles as $role => $userIds) {
                foreach ($userIds as $userId) {
                    if (!isset($users[$userId])) {
                        continue;
                    }

                    $status = $role === 'intern' && $offset % 5 === 0 ? 'late' : 'present';
                    $insertAttendance->execute([
                        $userId,
                        $projectId,
                        $role,
                        $gateway,
                        date('H:i:s', strtotime('08:05 +' . ($offset * 7) . ' minutes')),
                        -1.02000000 + ($offset / 10000),
                        35.00000000 + ($offset / 10000),
                        12 + ($offset * 3),
                        [8, 10, 12, 14, 16][$offset % 5],
                        $status,
                        $directorId,
                        $userId,
                    ]);
                    $offset++;
                }
            }
        }
    }

    private function projectIds(\PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, slug FROM projects')->fetchAll(\PDO::FETCH_ASSOC);
        $ids = [];
        foreach ($rows as $row) {
            $ids[(string)$row['slug']] = (int)$row['id'];
        }
        return $ids;
    }

    private function attendanceUsers(\PDO $pdo): array
    {
        $rows = $pdo->query(
            "SELECT u.id
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active'
               AND r.slug IN ('intern', 'clerk', 'contractor', 'manager')"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $ids = [];
        foreach ($rows as $row) {
            $ids[(int)$row['id']] = true;
        }
        return $ids;
    }
}
