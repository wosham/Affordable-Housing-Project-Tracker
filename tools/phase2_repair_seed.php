<?php
declare(strict_types=1);

define('APP_SKIP_SESSION', true);
require_once dirname(__DIR__) . '/app/core/bootstrap.php';

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function one(string $sql, array $bindings = []): ?array
{
    return Database::fetch($sql, $bindings);
}

function allRows(string $sql, array $bindings = []): array
{
    return Database::fetchAll($sql, $bindings);
}

function execSql(string $sql, array $bindings = []): void
{
    Database::query($sql, $bindings);
}

function constraintExists(string $table, string $constraint): bool
{
    $row = one(
        'SELECT COUNT(*) AS total
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?',
        [$table, $constraint]
    );

    return (int)($row['total'] ?? 0) > 0;
}

function addForeignKey(string $table, string $constraint, string $sql): void
{
    if (!constraintExists($table, $constraint)) {
        execSql($sql);
    }
}

function roleUser(string $roleSlug, int $offset = 0): int
{
    $rows = allRows(
        "SELECT u.id
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE r.slug = ? AND u.status = 'active'
         ORDER BY u.id",
        [$roleSlug]
    );
    if ($rows === []) {
        throw new RuntimeException("No active {$roleSlug} user exists.");
    }

    return (int)$rows[$offset % count($rows)]['id'];
}

function dateAddDays(string $date, int $days): string
{
    return date('Y-m-d', strtotime($date . ' +' . $days . ' days'));
}

function insertRow(string $table, array $data): int
{
    $columns = array_keys($data);
    $quoted = array_map(static fn (string $column): string => '`' . $column . '`', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    execSql(
        'INSERT INTO `' . $table . '` (' . implode(', ', $quoted) . ') VALUES (' . $placeholders . ')',
        array_values($data)
    );

    return (int)Database::lastInsertId();
}

function upsertAssignment(int $projectId, int $userId, string $role, int $assignedBy): void
{
    $existing = one(
        'SELECT id FROM project_assignments WHERE project_id = ? AND user_id = ? LIMIT 1',
        [$projectId, $userId]
    );

    if ($existing) {
        execSql(
            "UPDATE project_assignments
             SET role = ?, assignment_type = 'site', scope = 'phase-2-demo',
                 status = 'active', is_primary = 1, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$role, $assignedBy, (int)$existing['id']]
        );
        return;
    }

    insertRow('project_assignments', [
        'project_id' => $projectId,
        'user_id' => $userId,
        'role' => $role,
        'assignment_type' => 'site',
        'scope' => 'phase-2-demo',
        'status' => 'active',
        'start_date' => date('Y-m-d'),
        'end_date' => null,
        'is_primary' => 1,
        'notes' => 'Phase 2 aligned mock workflow assignment.',
        'updated_by' => $assignedBy,
        'assigned_by' => $assignedBy,
    ]);
}

function programmeStatus(int $taskProgress, ?string $plannedEnd): string
{
    if ($taskProgress >= 100) {
        return 'complete';
    }
    if ($taskProgress > 0) {
        return $plannedEnd && $plannedEnd < date('Y-m-d') ? 'delayed' : 'in_progress';
    }

    return $plannedEnd && $plannedEnd < date('Y-m-d') ? 'delayed' : 'not_started';
}

$directorId = roleUser('superadmin');
$consultantId = roleUser('consultant');
$financeId = roleUser('finance');
$managerIds = [roleUser('manager', 0), roleUser('manager', 1)];
$contractorIds = [roleUser('contractor', 0), roleUser('contractor', 1)];
$clerkIds = [roleUser('clerk', 0), roleUser('clerk', 1)];
$internIds = allRows(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'intern' AND u.status = 'active' ORDER BY u.id"
);

$projects = allRows(
    'SELECT id, name, slug, status, pct_complete, contract_sum, start_date, est_delivery, contractor_name, constituency_id, ward_id
     FROM projects
     ORDER BY id'
);
if ($projects === []) {
    throw new RuntimeException('No projects found. Phase 2 seed requires existing projects.');
}

$boqTemplate = [
    ['section' => 'Preliminaries', 'item' => 'A.01', 'description' => 'Mobilisation, site establishment and insurances', 'unit' => 'LS', 'qty' => 1, 'share' => 0.060],
    ['section' => 'Site Works', 'item' => 'B.01', 'description' => 'Site clearance, hoarding and temporary access', 'unit' => 'LS', 'qty' => 1, 'share' => 0.050],
    ['section' => 'Substructure', 'item' => 'C.01', 'description' => 'Excavation, foundation concrete and ground beams', 'unit' => 'M3', 'qty' => 450, 'share' => 0.160],
    ['section' => 'Superstructure', 'item' => 'D.01', 'description' => 'Reinforced concrete frame, columns and slabs', 'unit' => 'M2', 'qty' => 3200, 'share' => 0.240],
    ['section' => 'Masonry', 'item' => 'E.01', 'description' => 'Walling, partitions and lintels', 'unit' => 'M2', 'qty' => 5200, 'share' => 0.110],
    ['section' => 'Roofing', 'item' => 'F.01', 'description' => 'Roof structure, covering and rainwater goods', 'unit' => 'M2', 'qty' => 2600, 'share' => 0.080],
    ['section' => 'Finishes', 'item' => 'G.01', 'description' => 'Plaster, screed, painting, doors and windows', 'unit' => 'M2', 'qty' => 7400, 'share' => 0.140],
    ['section' => 'MEP Services', 'item' => 'H.01', 'description' => 'Electrical, plumbing, drainage and fire services', 'unit' => 'LS', 'qty' => 1, 'share' => 0.100],
    ['section' => 'External Works', 'item' => 'I.01', 'description' => 'Roads, parking, landscaping and utility connections', 'unit' => 'LS', 'qty' => 1, 'share' => 0.060],
];

$programmeTemplate = [
    'Site handover and baseline survey',
    'Detailed design confirmation',
    'Procurement and mobilisation',
    'Earthworks and foundations',
    'Substructure completion',
    'Superstructure frame',
    'Roofing and envelope',
    'MEP rough-in and services',
    'Finishes and external works',
    'Testing, inspection and handover',
];

try {
    execSql("ALTER TABLE ipc_approvals MODIFY action ENUM('endorsed','certified','approved','paid','rejected') NOT NULL");

    Database::beginTransaction();

    foreach ([
        'retention_release_history',
        'payments',
        'retention',
        'liquidated_damages',
        'ipc_attachments',
        'ipc_approvals',
        'ipc_lines',
        'ipcs',
        'boq_review_updates',
        'boq_items',
        'programme_tasks',
        'variations',
        'eot_requests',
    ] as $table) {
        execSql('DELETE FROM `' . $table . '`');
    }

    $projectBoq = [];
    foreach ($projects as $index => $project) {
        $projectId = (int)$project['id'];
        $progress = max(0, min(100, (int)$project['pct_complete']));
        $contractSum = max(1.0, (float)$project['contract_sum']);
        $start = $project['start_date'] ?: date('Y-m-d', strtotime('-180 days'));
        $end = $project['est_delivery'] ?: dateAddDays($start, 540);
        $totalDays = max(240, (int)floor((strtotime($end) - strtotime($start)) / 86400));

        $managerId = $managerIds[$index % count($managerIds)];
        $contractorId = $contractorIds[$index % count($contractorIds)];
        $clerkId = $clerkIds[$index % count($clerkIds)];

        execSql(
            'UPDATE projects SET contractor_id = ?, consultant_id = ? WHERE id = ?',
            [$contractorId, $consultantId, $projectId]
        );
        upsertAssignment($projectId, $managerId, 'manager', $directorId);
        upsertAssignment($projectId, $consultantId, 'consultant', $directorId);
        upsertAssignment($projectId, $contractorId, 'contractor', $directorId);
        upsertAssignment($projectId, $clerkId, 'clerk', $directorId);
        if ($internIds !== []) {
            $internId = (int)$internIds[$index % count($internIds)]['id'];
            upsertAssignment($projectId, $internId, 'intern', $directorId);
        }

        foreach ($boqTemplate as $boq) {
            $amount = round($contractSum * (float)$boq['share'], 2);
            $qty = (float)$boq['qty'];
            $rate = round($amount / max(1, $qty), 2);
            $boqId = insertRow('boq_items', [
                'project_id' => $projectId,
                'section' => $boq['section'],
                'item_no' => $boq['item'],
                'description' => $boq['description'],
                'unit' => $boq['unit'],
                'quantity' => $qty,
                'rate' => $rate,
                'amount' => $amount,
                'certified_qty' => 0,
                'paid_qty' => 0,
                'status' => 'active',
                'review_status' => $progress >= 50 ? 'approved' : 'review',
                'risk_status' => $progress < 15 ? 'normal' : 'watch',
                'manager_note' => 'Seeded from contract value for Phase 2 workflow testing.',
                'last_reviewed_by' => $managerId,
                'last_reviewed_at' => date('Y-m-d H:i:s'),
                'updated_by' => $directorId,
            ]);
            $projectBoq[$projectId][] = [
                'id' => $boqId,
                'description' => $boq['item'] . ' - ' . $boq['description'],
                'qty' => $qty,
                'rate' => $rate,
            ];
        }

        $taskCount = count($programmeTemplate);
        foreach ($programmeTemplate as $taskIndex => $taskName) {
            $plannedStart = dateAddDays($start, (int)floor(($totalDays / $taskCount) * $taskIndex));
            $plannedEnd = dateAddDays($start, (int)floor(($totalDays / $taskCount) * ($taskIndex + 1)) - 1);
            $taskProgress = max(0, min(100, (int)round(($progress - ($taskIndex * 10)) * 10)));
            $status = programmeStatus($taskProgress, $plannedEnd);
            insertRow('programme_tasks', [
                'project_id' => $projectId,
                'task_name' => $taskName,
                'start_date' => $taskProgress > 0 ? $plannedStart : null,
                'end_date' => $taskProgress >= 100 ? $plannedEnd : null,
                'planned_start' => $plannedStart,
                'planned_end' => $plannedEnd,
                'pct_complete' => $taskProgress,
                'depends_on_task_id' => null,
                'assigned_to' => $taskIndex % 3 === 0 ? $managerId : ($taskIndex % 3 === 1 ? $consultantId : $clerkId),
                'status' => $status,
                'sort_order' => ($taskIndex + 1) * 10,
                'critical_path' => in_array($taskIndex, [3, 4, 5], true) ? 1 : 0,
                'notes' => 'Aligned mock programme task for ' . $project['name'] . '.',
                'updated_by' => $directorId,
            ]);
        }
    }

    foreach ($projects as $index => $project) {
        $projectId = (int)$project['id'];
        $progress = max(0, min(100, (int)$project['pct_complete']));
        $contractorId = $contractorIds[$index % count($contractorIds)];
        $managerId = $managerIds[$index % count($managerIds)];
        $clerkId = $clerkIds[$index % count($clerkIds)];
        $start = $project['start_date'] ?: date('Y-m-d', strtotime('-180 days'));

        $statuses = $progress >= 55
            ? ['paid', 'paid', 'approved', 'endorsed']
            : ($progress >= 30 ? ['paid', 'approved', 'certified'] : ($progress >= 12 ? ['certified', 'submitted'] : ['submitted']));

        $previousByBoq = [];
        foreach ($statuses as $ipcIndex => $status) {
            $claimRatio = max(0.02, min($progress / 100, (($ipcIndex + 1) / count($statuses)) * max(0.06, $progress / 100)));
            $periodFrom = dateAddDays($start, $ipcIndex * 45);
            $periodTo = dateAddDays($periodFrom, 44);
            $gross = 0.0;
            $lines = [];

            foreach (array_slice($projectBoq[$projectId], 0, 5) as $lineIndex => $boq) {
                $targetCumulative = round($boq['qty'] * $claimRatio, 3);
                $previous = (float)($previousByBoq[$boq['id']] ?? 0);
                $thisQty = max(0, round($targetCumulative - $previous, 3));
                if ($thisQty <= 0) {
                    continue;
                }
                $amount = round($thisQty * (float)$boq['rate'], 2);
                $gross += $amount;
                $previousByBoq[$boq['id']] = $targetCumulative;
                $lines[] = [
                    'boq_item_id' => $boq['id'],
                    'description' => $boq['description'],
                    'qty_this_period' => $thisQty,
                    'cumulative_qty' => $targetCumulative,
                    'rate' => $boq['rate'],
                    'amount' => $amount,
                ];
            }

            if ($gross <= 0 || $lines === []) {
                continue;
            }

            $retention = round($gross * ContractorIPC::RETENTION_RATE, 2);
            $net = round($gross - $retention, 2);
            $submittedAt = date('Y-m-d H:i:s', strtotime($periodTo . ' +2 days'));
            $ipcId = insertRow('ipcs', [
                'project_id' => $projectId,
                'contractor_id' => $contractorId,
                'ipc_number' => $ipcIndex + 1,
                'contractor_reference' => 'TNZ-' . strtoupper(substr((string)$project['slug'], 0, 8)) . '-IPC-' . str_pad((string)($ipcIndex + 1), 3, '0', STR_PAD_LEFT),
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'gross_amount' => $gross,
                'retention_amount' => $retention,
                'net_amount' => $net,
                'declaration_accepted' => 1,
                'status' => $status,
                'current_stage' => $status,
                'submitted_at' => $submittedAt,
                'certified_at' => in_array($status, ['certified', 'endorsed', 'approved', 'paid'], true) ? date('Y-m-d H:i:s', strtotime($submittedAt . ' +4 days')) : null,
                'certified_by' => in_array($status, ['certified', 'endorsed', 'approved', 'paid'], true) ? $consultantId : null,
                'certification_comment' => in_array($status, ['certified', 'endorsed', 'approved', 'paid'], true) ? 'Quantities checked against BOQ and supporting records.' : null,
                'clerk_verification_comment' => in_array($status, ['clerk-endorsed', 'certified', 'endorsed', 'approved', 'paid'], true) ? 'Site records verified.' : null,
                'clerk_verified_by' => in_array($status, ['clerk-endorsed', 'certified', 'endorsed', 'approved', 'paid'], true) ? $clerkId : null,
                'clerk_verified_at' => in_array($status, ['clerk-endorsed', 'certified', 'endorsed', 'approved', 'paid'], true) ? date('Y-m-d H:i:s', strtotime($submittedAt . ' +2 days')) : null,
                'approved_at' => in_array($status, ['approved', 'paid'], true) ? date('Y-m-d H:i:s', strtotime($submittedAt . ' +8 days')) : null,
                'approved_by' => in_array($status, ['approved', 'paid'], true) ? $directorId : null,
                'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s', strtotime($submittedAt . ' +12 days')) : null,
                'paid_by' => $status === 'paid' ? $financeId : null,
                'payment_status' => $status === 'paid' ? 'paid' : 'unpaid',
                'payment_reference' => $status === 'paid' ? 'TNZ-AHP-PAY-2026-' . str_pad((string)($projectId * 10 + $ipcIndex + 1), 4, '0', STR_PAD_LEFT) : null,
                'payment_comment' => $status === 'paid' ? 'Mock payment processed for Phase 2 finance testing.' : null,
            ]);

            foreach ($lines as $line) {
                insertRow('ipc_lines', array_merge(['ipc_id' => $ipcId], $line));
                if (in_array($status, ['certified', 'endorsed', 'approved', 'paid'], true)) {
                    execSql(
                        'UPDATE boq_items
                         SET certified_qty = GREATEST(COALESCE(certified_qty, 0), ?),
                             certified_updated_by = ?, last_certified_at = COALESCE(last_certified_at, NOW()), updated_by = ?
                         WHERE id = ?',
                        [$line['cumulative_qty'], $consultantId, $directorId, $line['boq_item_id']]
                    );
                }
                if ($status === 'paid') {
                    execSql(
                        'UPDATE boq_items
                         SET paid_qty = GREATEST(COALESCE(paid_qty, 0), ?),
                             paid_updated_by = ?, last_paid_at = COALESCE(last_paid_at, NOW()), updated_by = ?
                         WHERE id = ?',
                        [$line['cumulative_qty'], $financeId, $directorId, $line['boq_item_id']]
                    );
                }
            }

            if (in_array($status, ['clerk-endorsed', 'certified', 'endorsed', 'approved', 'paid'], true)) {
                insertRow('ipc_approvals', ['ipc_id' => $ipcId, 'step' => 1, 'action_by' => $clerkId, 'action' => 'endorsed', 'comments' => 'Clerk verification completed.', 'actioned_at' => date('Y-m-d H:i:s', strtotime($submittedAt . ' +2 days'))]);
            }
            if (in_array($status, ['certified', 'endorsed', 'approved', 'paid'], true)) {
                insertRow('ipc_approvals', ['ipc_id' => $ipcId, 'step' => 2, 'action_by' => $consultantId, 'action' => 'certified', 'comments' => 'Consultant certification completed.', 'actioned_at' => date('Y-m-d H:i:s', strtotime($submittedAt . ' +4 days'))]);
            }
            if (in_array($status, ['endorsed', 'approved', 'paid'], true)) {
                insertRow('ipc_approvals', ['ipc_id' => $ipcId, 'step' => 3, 'action_by' => $managerId, 'action' => 'endorsed', 'comments' => 'Programme manager endorsement completed.', 'actioned_at' => date('Y-m-d H:i:s', strtotime($submittedAt . ' +6 days'))]);
            }
            if (in_array($status, ['approved', 'paid'], true)) {
                insertRow('ipc_approvals', ['ipc_id' => $ipcId, 'step' => 4, 'action_by' => $directorId, 'action' => 'approved', 'comments' => 'Director final approval granted.', 'actioned_at' => date('Y-m-d H:i:s', strtotime($submittedAt . ' +8 days'))]);
            }
            if ($status === 'paid') {
                $reference = 'TNZ-AHP-PAY-2026-' . str_pad((string)($projectId * 10 + $ipcIndex + 1), 4, '0', STR_PAD_LEFT);
                $paymentId = insertRow('payments', [
                    'ipc_id' => $ipcId,
                    'project_id' => $projectId,
                    'amount' => $net,
                    'payment_date' => date('Y-m-d', strtotime($submittedAt . ' +12 days')),
                    'reference_no' => $reference,
                    'voucher_no' => 'VCH-' . str_pad((string)($projectId * 10 + $ipcIndex + 1), 5, '0', STR_PAD_LEFT),
                    'bank' => 'Central Bank Development Account',
                    'payment_method' => 'treasury_transfer',
                    'processed_by' => $financeId,
                    'processed_at' => date('Y-m-d H:i:s', strtotime($submittedAt . ' +12 days')),
                    'receipt_path' => null,
                    'status' => 'processed',
                    'notes' => 'Phase 2 mock payment aligned to IPC workflow.',
                ]);
                insertRow('retention', [
                    'project_id' => $projectId,
                    'ipc_id' => $ipcId,
                    'total_held' => $retention,
                    'released_amount' => 0,
                    'release_date' => date('Y-m-d', strtotime($submittedAt . ' +377 days')),
                    'release_reason' => 'Retention held from IPC #' . ($ipcIndex + 1),
                    'status' => 'held',
                    'processed_by' => $financeId,
                ]);
                insertRow('ipc_approvals', ['ipc_id' => $ipcId, 'step' => 5, 'action_by' => $financeId, 'action' => 'paid', 'comments' => 'Payment processed: ' . $reference, 'actioned_at' => date('Y-m-d H:i:s', strtotime($submittedAt . ' +12 days'))]);
                insertRow('audit_logs', [
                    'user_id' => $financeId,
                    'actor_role' => 'finance',
                    'action' => 'phase2_payment_seed',
                    'module' => 'payments',
                    'target_id' => $paymentId,
                    'details_json' => json_encode(['ipc_id' => $ipcId, 'project_id' => $projectId, 'amount' => $net], JSON_UNESCAPED_SLASHES),
                    'severity' => 'info',
                ]);
            }
        }
    }

    foreach ($projects as $index => $project) {
        $projectId = (int)$project['id'];
        $contractorId = $contractorIds[$index % count($contractorIds)];
        if ($index % 3 === 0) {
            insertRow('variations', [
                'project_id' => $projectId,
                'submitted_by' => $contractorId,
                'vo_number' => 1,
                'description' => 'Adjustment for approved scope clarification and site condition response.',
                'reason' => 'Field condition variation for Phase 2 workflow testing.',
                'amount' => round((float)$project['contract_sum'] * 0.012, 2),
                'impact_on_time_days' => 14,
                'status' => $index === 0 ? 'approved' : 'pending',
                'approved_by' => $index === 0 ? $directorId : null,
                'approved_at' => $index === 0 ? date('Y-m-d H:i:s', strtotime('-30 days')) : null,
                'consultant_review_status' => 'recommended',
                'consultant_review_note' => 'Consultant reviewed cost and time implication.',
                'consultant_reviewed_by' => $consultantId,
                'consultant_reviewed_at' => date('Y-m-d H:i:s', strtotime('-35 days')),
                'consultant_recommended_amount' => round((float)$project['contract_sum'] * 0.011, 2),
                'consultant_time_impact_days' => 10,
                'consultant_documents_checked' => 1,
            ]);
        }
        if ($index % 4 === 1) {
            insertRow('eot_requests', [
                'project_id' => $projectId,
                'submitted_by' => $contractorId,
                'eot_number' => 1,
                'days_requested' => 21,
                'reason' => 'Weather and access delays affected critical path activities.',
                'supporting_evidence' => null,
                'status' => $index === 1 ? 'granted' : 'pending',
                'granted_days' => $index === 1 ? 14 : null,
                'approved_by' => $index === 1 ? $directorId : null,
                'approved_at' => $index === 1 ? date('Y-m-d H:i:s', strtotime('-18 days')) : null,
                'manager_recommendation' => 'approve',
                'manager_recommended_days' => 14,
                'manager_review_note' => 'Delay substantiated from site records.',
                'manager_reviewed_by' => $managerIds[$index % count($managerIds)],
                'manager_reviewed_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
                'delay_category' => 'weather',
                'impact_summary' => 'Critical path pushed by intermittent heavy rainfall.',
                'consultant_review_status' => 'recommended',
                'consultant_review_note' => 'Supporting records checked.',
                'consultant_reviewed_by' => $consultantId,
                'consultant_reviewed_at' => date('Y-m-d H:i:s', strtotime('-19 days')),
                'consultant_recommended_days' => 14,
                'consultant_delay_category' => 'weather',
                'consultant_documents_checked' => 1,
            ]);
        }
        if ((int)$project['pct_complete'] >= 35 && strtotime((string)$project['est_delivery']) < time()) {
            insertRow('liquidated_damages', [
                'project_id' => $projectId,
                'rate_per_day' => 25000,
                'days_overdue' => 12,
                'total_ld' => 300000,
                'applied_to_ipc_id' => null,
                'notes' => 'Indicative LD exposure for delayed delivery review.',
                'status' => 'pending',
                'calculated_by' => $managerIds[$index % count($managerIds)],
                'updated_by' => $directorId,
            ]);
        }
    }

    insertRow('audit_logs', [
        'user_id' => $directorId,
        'actor_role' => 'superadmin',
        'action' => 'phase2_repair_seed',
        'module' => 'phase2',
        'target_id' => 0,
        'details_json' => json_encode(['projects' => count($projects), 'scope' => 'programme,boq,ipc,finance,approvals'], JSON_UNESCAPED_SLASHES),
        'severity' => 'info',
    ]);

    Database::commit();

    addForeignKey('ipc_lines', 'fk_ipc_lines_ipc', 'ALTER TABLE ipc_lines ADD CONSTRAINT fk_ipc_lines_ipc FOREIGN KEY (ipc_id) REFERENCES ipcs(id) ON DELETE CASCADE');
    addForeignKey('ipc_lines', 'fk_ipc_lines_boq', 'ALTER TABLE ipc_lines ADD CONSTRAINT fk_ipc_lines_boq FOREIGN KEY (boq_item_id) REFERENCES boq_items(id) ON DELETE SET NULL');
    addForeignKey('payments', 'fk_payments_ipc', 'ALTER TABLE payments ADD CONSTRAINT fk_payments_ipc FOREIGN KEY (ipc_id) REFERENCES ipcs(id) ON DELETE CASCADE');
    addForeignKey('payments', 'fk_payments_project', 'ALTER TABLE payments ADD CONSTRAINT fk_payments_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE');
    addForeignKey('payments', 'fk_payments_processed_by', 'ALTER TABLE payments ADD CONSTRAINT fk_payments_processed_by FOREIGN KEY (processed_by) REFERENCES users(id)');
    addForeignKey('retention', 'fk_retention_project', 'ALTER TABLE retention ADD CONSTRAINT fk_retention_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE');
    addForeignKey('retention', 'fk_retention_ipc', 'ALTER TABLE retention ADD CONSTRAINT fk_retention_ipc FOREIGN KEY (ipc_id) REFERENCES ipcs(id) ON DELETE SET NULL');
    addForeignKey('liquidated_damages', 'fk_ld_project', 'ALTER TABLE liquidated_damages ADD CONSTRAINT fk_ld_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE');
    addForeignKey('liquidated_damages', 'fk_ld_ipc', 'ALTER TABLE liquidated_damages ADD CONSTRAINT fk_ld_ipc FOREIGN KEY (applied_to_ipc_id) REFERENCES ipcs(id) ON DELETE SET NULL');
    addForeignKey('programme_tasks', 'fk_programme_tasks_project', 'ALTER TABLE programme_tasks ADD CONSTRAINT fk_programme_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE');
    addForeignKey('programme_tasks', 'fk_programme_tasks_assigned_to', 'ALTER TABLE programme_tasks ADD CONSTRAINT fk_programme_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL');
    addForeignKey('programme_tasks', 'fk_programme_tasks_dependency', 'ALTER TABLE programme_tasks ADD CONSTRAINT fk_programme_tasks_dependency FOREIGN KEY (depends_on_task_id) REFERENCES programme_tasks(id) ON DELETE SET NULL');
    addForeignKey('variations', 'fk_variations_project', 'ALTER TABLE variations ADD CONSTRAINT fk_variations_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE');
    addForeignKey('variations', 'fk_variations_submitted_by', 'ALTER TABLE variations ADD CONSTRAINT fk_variations_submitted_by FOREIGN KEY (submitted_by) REFERENCES users(id)');
} catch (Throwable $e) {
    if (Database::inTransaction()) {
        Database::rollBack();
    }
    fwrite(STDERR, 'Phase 2 repair failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$summary = [
    'projects' => count($projects),
    'programme_tasks' => (int)(one('SELECT COUNT(*) total FROM programme_tasks')['total'] ?? 0),
    'boq_items' => (int)(one('SELECT COUNT(*) total FROM boq_items')['total'] ?? 0),
    'ipcs' => (int)(one('SELECT COUNT(*) total FROM ipcs')['total'] ?? 0),
    'ipc_lines' => (int)(one('SELECT COUNT(*) total FROM ipc_lines')['total'] ?? 0),
    'payments' => (int)(one('SELECT COUNT(*) total FROM payments')['total'] ?? 0),
    'retention' => (int)(one('SELECT COUNT(*) total FROM retention')['total'] ?? 0),
    'variations' => (int)(one('SELECT COUNT(*) total FROM variations')['total'] ?? 0),
    'eot_requests' => (int)(one('SELECT COUNT(*) total FROM eot_requests')['total'] ?? 0),
    'liquidated_damages' => (int)(one('SELECT COUNT(*) total FROM liquidated_damages')['total'] ?? 0),
];

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
