<?php

class FinancePayment
{
    public const METHODS = ['bank_transfer', 'treasury_transfer', 'cheque', 'mobile_money'];

    public static function summary(array $filters = []): array
    {
        [$where, $bindings] = self::approvedWhere($filters);
        $approved = Database::fetch(
            "SELECT COUNT(*) AS count_value,
                    COALESCE(SUM(i.net_amount - COALESCE(pay.paid_amount, 0)), 0) AS amount_value,
                    COALESCE(SUM(i.retention_amount), 0) AS retention_value,
                    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(i.approved_at)) > 14 THEN 1 ELSE 0 END), 0) AS overdue_count
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             JOIN users contractor ON contractor.id = i.contractor_id
             LEFT JOIN (" . self::paidSubquery() . ") pay ON pay.ipc_id = i.id
             {$where}",
            $bindings
        ) ?: [];
        $month = Database::fetch(
            "SELECT COUNT(*) AS count_value, COALESCE(SUM(amount), 0) AS amount_value
             FROM payments
             WHERE status = 'processed' AND payment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        ) ?: [];

        return [
            'approved_count' => (int)($approved['count_value'] ?? 0),
            'approved_value' => (float)($approved['amount_value'] ?? 0),
            'retention_value' => (float)($approved['retention_value'] ?? 0),
            'overdue_count' => (int)($approved['overdue_count'] ?? 0),
            'paid_month_count' => (int)($month['count_value'] ?? 0),
            'paid_month_value' => (float)($month['amount_value'] ?? 0),
        ];
    }

    public static function approvedList(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::approvedWhere($filters);
        return Database::fetchAll(
            self::approvedSelect() . " {$where}
             ORDER BY i.approved_at ASC, i.id ASC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function approvedCount(array $filters = []): int
    {
        [$where, $bindings] = self::approvedWhere($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             JOIN users contractor ON contractor.id = i.contractor_id
             LEFT JOIN (" . self::paidSubquery() . ") pay ON pay.ipc_id = i.id
             {$where}",
            $bindings
        ) ?: [];
        return (int)($row['aggregate'] ?? 0);
    }

    public static function payableOptions(int $limit = 120): array
    {
        return self::approvedList([], $limit, 0);
    }

    public static function findPayable(int $ipcId): ?array
    {
        $rows = self::approvedList(['ipc_id' => $ipcId], 1, 0);
        return $rows[0] ?? null;
    }

    public static function recentPayments(int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT pay.*, i.ipc_number, i.net_amount, p.name AS project_name,
                    CONCAT(contractor.first_name, ' ', contractor.last_name) AS contractor_name,
                    CONCAT(processor.first_name, ' ', processor.last_name) AS processed_by_name
             FROM payments pay
             JOIN ipcs i ON i.id = pay.ipc_id
             JOIN projects p ON p.id = pay.project_id
             JOIN users contractor ON contractor.id = i.contractor_id
             JOIN users processor ON processor.id = pay.processed_by
             ORDER BY pay.payment_date DESC, pay.id DESC
             LIMIT " . max(1, $limit)
        );
    }

    public static function dashboard(): array
    {
        $projects = Database::fetch(
            "SELECT COUNT(*) AS project_count,
                    COALESCE(SUM(contract_sum), 0) AS contract_value,
                    COALESCE(AVG(pct_complete), 0) AS avg_progress
             FROM projects"
        ) ?: [];
        $paid = Database::fetch(
            "SELECT COUNT(*) AS payment_count, COALESCE(SUM(amount), 0) AS paid_value
             FROM payments
             WHERE status = 'processed'"
        ) ?: [];
        $retention = Database::fetch(
            "SELECT COALESCE(SUM(total_held - released_amount), 0) AS held_value
             FROM retention
             WHERE status <> 'released'"
        ) ?: [];
        $ld = Database::fetch(
            "SELECT COALESCE(SUM(total_ld), 0) AS value, COUNT(*) AS count_value
             FROM liquidated_damages
             WHERE status IN ('pending','applied')"
        ) ?: [];

        return array_merge(self::summary(), [
            'project_count' => (int)($projects['project_count'] ?? 0),
            'contract_value' => (float)($projects['contract_value'] ?? 0),
            'avg_progress' => (float)($projects['avg_progress'] ?? 0),
            'payment_count' => (int)($paid['payment_count'] ?? 0),
            'paid_value' => (float)($paid['paid_value'] ?? 0),
            'retention_held_value' => (float)($retention['held_value'] ?? 0),
            'ld_value' => (float)($ld['value'] ?? 0),
            'ld_count' => (int)($ld['count_value'] ?? 0),
        ]);
    }

    public static function budgetRows(int $limit = 25): array
    {
        // Balance matches Budget Tracker: contract − paid − approved_unpaid.
        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.contract_sum, p.pct_complete,
                    COALESCE(pay.paid_amount, 0) AS paid_amount,
                    COALESCE(unpaid.approved_unpaid, 0) AS approved_unpaid,
                    COALESCE(ret.held_amount, 0) AS retention_held,
                    GREATEST(p.contract_sum - COALESCE(pay.paid_amount, 0) - COALESCE(unpaid.approved_unpaid, 0), 0) AS balance_amount
             FROM projects p
             LEFT JOIN (
                SELECT project_id, SUM(amount) AS paid_amount
                FROM payments
                WHERE status = 'processed'
                GROUP BY project_id
             ) pay ON pay.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(net_amount) AS approved_unpaid
                FROM ipcs
                WHERE status = 'approved' AND payment_status <> 'paid'
                GROUP BY project_id
             ) unpaid ON unpaid.project_id = p.id
             LEFT JOIN (
                SELECT project_id, SUM(total_held - released_amount) AS held_amount
                FROM retention
                WHERE status <> 'released'
                GROUP BY project_id
             ) ret ON ret.project_id = p.id
             ORDER BY p.contract_sum DESC, p.name ASC
             LIMIT " . max(1, $limit)
        );
    }

    public static function retentionRows(int $limit = 30): array
    {
        return Database::fetchAll(
            "SELECT r.*, p.name AS project_name, i.ipc_number,
                    CONCAT(u.first_name, ' ', u.last_name) AS processed_by_name
             FROM retention r
             JOIN projects p ON p.id = r.project_id
             LEFT JOIN ipcs i ON i.id = r.ipc_id
             LEFT JOIN users u ON u.id = r.processed_by
             ORDER BY COALESCE(r.release_date, r.created_at) ASC, r.id DESC
             LIMIT " . max(1, $limit)
        );
    }

    public static function liquidatedDamageRows(int $limit = 30): array
    {
        return Database::fetchAll(
            "SELECT ld.*, p.name AS project_name, i.ipc_number
             FROM liquidated_damages ld
             JOIN projects p ON p.id = ld.project_id
             LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id
             ORDER BY ld.updated_at DESC, ld.id DESC
             LIMIT " . max(1, $limit)
        );
    }

    public static function paymentDetail(int $ipcId): ?array
    {
        $ipc = self::findPayable($ipcId);
        if (!$ipc) {
            $ipc = Database::fetch(self::approvedSelect() . ' WHERE i.id = ? LIMIT 1', [$ipcId]);
        }
        if (!$ipc) {
            return null;
        }
        $payments = Database::fetchAll(
            "SELECT pay.*, CONCAT(u.first_name, ' ', u.last_name) AS processed_by_name
             FROM payments pay
             LEFT JOIN users u ON u.id = pay.processed_by
             WHERE pay.ipc_id = ?
             ORDER BY pay.payment_date DESC, pay.id DESC",
            [$ipcId]
        );
        $ipc['payments'] = $payments;
        $ipc['warnings'] = self::warnings($ipc);
        return $ipc;
    }

    public static function process(int $userId, array $input): int
    {
        $ipcId = Security::cleanInt($input['ipc_id'] ?? 0);
        $reference = self::required($input, 'reference_no', 100);
        $paymentDate = self::date($input, 'payment_date', date('Y-m-d'));
        $amount = round(Security::cleanFloat($input['amount'] ?? 0), 2);
        $method = self::choice($input, 'payment_method', self::METHODS, 'bank_transfer');
        $bank = self::text($input, 'bank', 150);
        $voucher = self::text($input, 'voucher_no', 100);
        $receipt = self::text($input, 'receipt_path', 255);
        $notes = self::text($input, 'notes');

        if (empty($input['confirm_payment'])) {
            throw new RuntimeException('Confirm the payment before processing.');
        }
        if (empty($input['confirm_review'])) {
            throw new RuntimeException('Confirm you have checked the payable amount, reference and payment date.');
        }
        if ($ipcId <= 0) {
            throw new RuntimeException('Choose an approved IPC.');
        }
        // Payment date: not empty (validated), not more than 1 day in the future.
        $today = date('Y-m-d');
        if ($paymentDate > date('Y-m-d', strtotime($today . ' +1 day'))) {
            throw new RuntimeException('Payment date cannot be more than one day in the future.');
        }

        Database::beginTransaction();
        try {
            $ipc = self::findPayableForUpdate($ipcId);
            if (!$ipc) {
                throw new RuntimeException('This IPC is not available for payment.');
            }
            $outstanding = round((float)$ipc['outstanding_amount'], 2);
            if ($outstanding <= 0 || (string)$ipc['status'] !== 'approved') {
                throw new RuntimeException('This IPC has already been paid or is not approved.');
            }
            if (abs($amount - $outstanding) > 0.01) {
                throw new RuntimeException('Payment amount must match the approved net payable amount.');
            }
            if (self::referenceExists($reference)) {
                throw new RuntimeException('That payment reference has already been used.');
            }

            Database::query(
                "INSERT INTO payments (ipc_id, project_id, amount, payment_date, reference_no, voucher_no, bank, payment_method, processed_by, processed_at, receipt_path, status, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'processed', ?, NOW())",
                [$ipcId, (int)$ipc['project_id'], $amount, $paymentDate, $reference, $voucher, $bank, $method, $userId, $receipt, $notes]
            );
            $paymentId = (int)Database::lastInsertId();

            Database::query(
                "UPDATE ipcs
                 SET status = 'paid', current_stage = 'paid', paid_at = NOW(), paid_by = ?, payment_status = 'paid',
                     payment_reference = ?, payment_comment = ?, updated_at = NOW()
                 WHERE id = ? AND status = 'approved'",
                [$userId, $reference, $notes, $ipcId]
            );
            if (method_exists('Database', 'affectedRows')) {
                // ensure we only mark paid if still approved
            }
            $still = Database::fetch("SELECT status FROM ipcs WHERE id = ? LIMIT 1", [$ipcId]);
            if (!$still || (string)($still['status'] ?? '') !== 'paid') {
                throw new RuntimeException('IPC could not be marked paid. It may have been processed already.');
            }

            if ((float)$ipc['retention_amount'] > 0) {
                $existingRetention = Database::fetch(
                    'SELECT id FROM retention WHERE ipc_id = ? LIMIT 1',
                    [$ipcId]
                );
                if (!$existingRetention) {
                    Database::query(
                        "INSERT INTO retention (project_id, ipc_id, total_held, released_amount, release_date, release_reason, status, processed_by, created_at)
                         VALUES (?, ?, ?, 0, DATE_ADD(CURDATE(), INTERVAL 365 DAY), ?, 'held', ?, NOW())",
                        [(int)$ipc['project_id'], $ipcId, (float)$ipc['retention_amount'], 'Retention held from IPC #' . (int)$ipc['ipc_number'], $userId]
                    );
                }
            }

            Database::query(
                "UPDATE boq_items bi
                 JOIN ipc_lines il ON il.boq_item_id = bi.id
                 SET bi.paid_qty = GREATEST(COALESCE(bi.paid_qty, 0), COALESCE(il.cumulative_qty, 0)),
                     bi.paid_updated_by = ?,
                     bi.last_paid_at = NOW(),
                     bi.updated_by = ?,
                     bi.updated_at = NOW()
                 WHERE il.ipc_id = ?",
                [$userId, $userId, $ipcId]
            );

            Database::query(
                "INSERT INTO ipc_approvals (ipc_id, step, action_by, action, comments, actioned_at)
                 VALUES (?, 5, ?, 'paid', ?, NOW())",
                [$ipcId, $userId, 'Payment processed: ' . $reference]
            );

            Logger::log('process_payment', 'payments', $paymentId, ['ipc_id' => $ipcId, 'amount' => $amount, 'reference_no' => $reference]);
            self::notifyPayment($ipc, $reference, $amount);
            Database::commit();
            return $paymentId;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function warnings(array $ipc): array
    {
        $warnings = [];
        if ((string)($ipc['status'] ?? '') !== 'approved') {
            $warnings[] = 'Only approved IPCs can be paid.';
        }
        if ((float)($ipc['outstanding_amount'] ?? $ipc['net_amount'] ?? 0) <= 0) {
            $warnings[] = 'No unpaid net amount remains.';
        }
        if (empty($ipc['approved_at'])) {
            $warnings[] = 'Approval date is missing.';
        }
        if ((float)($ipc['net_amount'] ?? 0) <= 0) {
            $warnings[] = 'Net payable amount is missing.';
        }
        return $warnings;
    }

    private static function approvedSelect(): string
    {
        return self::detailSelect() . " LEFT JOIN (" . self::paidSubquery() . ") pay ON pay.ipc_id = i.id";
    }

    private static function detailSelect(): string
    {
        return "SELECT i.*, p.name AS project_name, p.contract_sum, p.pct_complete,
                    CONCAT(contractor.first_name, ' ', contractor.last_name) AS contractor_name,
                    contractor.email AS contractor_email,
                    CONCAT(approver.first_name, ' ', approver.last_name) AS approved_by_name,
                    COALESCE(pay.paid_amount, 0) AS paid_amount,
                    GREATEST(i.net_amount - COALESCE(pay.paid_amount, 0), 0) AS outstanding_amount,
                    DATEDIFF(CURDATE(), DATE(i.approved_at)) AS approval_age
                FROM ipcs i
                JOIN projects p ON p.id = i.project_id
                JOIN users contractor ON contractor.id = i.contractor_id
                LEFT JOIN users approver ON approver.id = i.approved_by";
    }

    private static function paidSubquery(): string
    {
        return "SELECT ipc_id, COALESCE(SUM(amount), 0) AS paid_amount FROM payments WHERE status = 'processed' GROUP BY ipc_id";
    }

    private static function approvedWhere(array $filters): array
    {
        $where = ["i.status = 'approved'", 'GREATEST(i.net_amount - COALESCE(pay.paid_amount, 0), 0) > 0'];
        $bindings = [];

        if (!empty($filters['ipc_id'])) {
            $where[] = 'i.id = ?';
            $bindings[] = (int)$filters['ipc_id'];
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'i.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR contractor.first_name LIKE ? OR contractor.last_name LIKE ? OR contractor.email LIKE ? OR CAST(i.ipc_number AS CHAR) LIKE ? OR i.contractor_reference LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }
        foreach (['approved_from' => '>=', 'approved_to' => '<='] as $key => $op) {
            $date = trim((string)($filters[$key] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where[] = "DATE(i.approved_at) {$op} ?";
                $bindings[] = $date;
            }
        }
        if (!empty($filters['age'])) {
            if ($filters['age'] === 'overdue') {
                $where[] = 'DATEDIFF(CURDATE(), DATE(i.approved_at)) > 14';
            } elseif ($filters['age'] === 'fresh') {
                $where[] = 'DATEDIFF(CURDATE(), DATE(i.approved_at)) <= 14';
            }
        }

        return [' WHERE ' . implode(' AND ', $where), $bindings];
    }

    private static function findPayableForUpdate(int $ipcId): ?array
    {
        return Database::fetch(
            "SELECT i.*, p.name AS project_name, COALESCE(pay.paid_amount, 0) AS paid_amount,
                    GREATEST(i.net_amount - COALESCE(pay.paid_amount, 0), 0) AS outstanding_amount
             FROM ipcs i
             JOIN projects p ON p.id = i.project_id
             LEFT JOIN (" . self::paidSubquery() . ") pay ON pay.ipc_id = i.id
             WHERE i.id = ?
             FOR UPDATE",
            [$ipcId]
        );
    }

    private static function referenceExists(string $reference): bool
    {
        return Database::fetch("SELECT id FROM payments WHERE reference_no = ? AND status = 'processed' LIMIT 1", [$reference]) !== null;
    }

    private static function notifyPayment(array $ipc, string $reference, float $amount): void
    {
        try {
            $message = 'Payment ' . $reference . ' for IPC #' . (int)$ipc['ipc_number'] . ' has been processed for ' . format_money($amount) . '.';
            Notification::pushRole('superadmin', 'payment', 'IPC payment processed', $message, 'admin/superadmin/financials.php');
            Notification::pushRole('manager', 'payment', 'IPC payment processed', $message, 'admin/manager/ipc-queue.php');
            // Only the IPC contractor — never all contractors.
            $contractorId = (int)($ipc['contractor_id'] ?? 0);
            if ($contractorId > 0) {
                Notification::push($contractorId, 'payment', 'IPC payment processed', $message, 'admin/contractor/payment-history.php');
            }
        } catch (Throwable) {
        }
    }

    private static function required(array $input, string $key, int $limit = 0): string
    {
        $value = self::text($input, $key, $limit);
        if ($value === null || $value === '') {
            throw new RuntimeException('Please complete all required fields.');
        }
        return $value;
    }

    private static function text(array $input, string $key, int $limit = 0): ?string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        return $limit > 0 ? substr($value, 0, $limit) : $value;
    }

    private static function date(array $input, string $key, string $fallback): string
    {
        $value = trim((string)($input[$key] ?? $fallback));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
    }

    private static function choice(array $input, string $key, array $allowed, string $fallback): string
    {
        $value = trim((string)($input[$key] ?? $fallback));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
