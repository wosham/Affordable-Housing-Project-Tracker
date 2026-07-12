<?php
/**
 * Phase 13: professional contractor site-record demo data.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 13 reseed ===\n";

$contractors = Database::fetchAll(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'contractor' ORDER BY u.id"
);
if ($contractors === []) {
    echo "FAIL no contractors\n";
    exit(1);
}

// Project assignments per contractor
function contractor_projects(int $userId): array
{
    $ids = ContractorProject::projectIds($userId, 'contractor');
    if ($ids !== []) {
        return $ids;
    }
    // Fallback: projects where user is project contractor
    $rows = Database::fetchAll('SELECT id FROM projects WHERE contractor_id = ? ORDER BY id', [$userId]);
    return array_map(static fn ($r) => (int)$r['id'], $rows);
}

// --- Clean P9 documents ---
Database::query("UPDATE documents SET original_name = REPLACE(original_name, 'P9 ', '') WHERE original_name LIKE 'P9 %'");
Database::query("UPDATE documents SET original_name = REPLACE(original_name, 'P9', '') WHERE original_name LIKE '%P9%'");
Database::query("UPDATE documents SET description = REPLACE(description, 'P9 ', '') WHERE description LIKE '%P9%'");

$docTitles = [
    ['drawing', 'Block A Setting-Out Plan Rev C', 'Structural setting-out for foundation and grid lines.'],
    ['spec', 'Concrete Mix Specification C25/30', 'Approved mix design for structural concrete packages.'],
    ['report', 'Weekly Site Progress Summary', 'Progress summary covering substructure and drainage works.'],
    ['correspondence', 'Client Instruction CI-12 Access Route', 'Instruction for temporary access route diversion.'],
    ['contract', 'Subcontract Award Notice - Electrical', 'Award notice for electrical installation package.'],
    ['quality-test', 'Cube Test Certificate Batch 18', '28-day compressive strength results for batch 18.'],
    ['shop-drawing', 'Window Frame Shop Drawing Type W2', 'Aluminium frame shop drawing for Type W2 openings.'],
    ['other', 'Delivery Note Register Extract', 'Extract of recent material delivery notes for audit.'],
];

$docs = Database::fetchAll('SELECT id, project_id, uploaded_by, category FROM documents ORDER BY id');
foreach ($docs as $i => $doc) {
    $sample = $docTitles[$i % count($docTitles)];
    $path = 'uploads/contractor-documents/doc-' . (int)$doc['id'] . '-' . $sample[0] . '.pdf';
    Database::query(
        'UPDATE documents SET category = ?, original_name = ?, filename = ?, description = ?, version = ?, consultant_review_status = ?, size = ? WHERE id = ?',
        [
            $sample[0],
            $sample[1],
            $path,
            $sample[2],
            '1.' . ($i % 3),
            ['pending', 'flagged', 'returned', 'pending', 'reviewed'][$i % 5],
            128000 + ($i * 1024),
            (int)$doc['id'],
        ]
    );
}

// Ensure consultant_review statuses are valid
Database::query("UPDATE documents SET consultant_review_status = 'pending' WHERE consultant_review_status IS NULL OR consultant_review_status = ''");

// --- Equipment ---
$equipRows = Database::fetchAll('SELECT id FROM equipment_register');
if (count($equipRows) < 8) {
    $equipCatalog = [
        ['Excavator 20T', 'EXC-20-041', 'Hired plant', 'good', 'on-site'],
        ['Mobile crane 25T', 'CRN-25-008', 'Hired plant', 'serviceable', 'on-site'],
        ['Concrete mixer 400L', 'MIX-400-12', 'Contractor owned', 'good', 'on-site'],
        ['Scaffold tower set', 'SCF-TWR-03', 'Subcontractor', 'good', 'on-site'],
        ['Plate compactor', 'PLT-CMP-07', 'Contractor owned', 'maintenance', 'maintenance'],
        ['Water bowser 5000L', 'WTR-5000-02', 'Hired plant', 'good', 'off-site'],
        ['Generator 100kVA', 'GEN-100-05', 'Contractor owned', 'serviceable', 'on-site'],
        ['Dumper 3T', 'DMP-3T-19', 'Hired plant', 'good', 'on-site'],
    ];
    foreach ($contractors as $c) {
        $uid = (int)$c['id'];
        $pids = contractor_projects($uid);
        foreach ($pids as $pi => $pid) {
            $item = $equipCatalog[$pi % count($equipCatalog)];
            $exists = Database::fetch(
                'SELECT id FROM equipment_register WHERE project_id = ? AND registration = ? LIMIT 1',
                [$pid, $item[1]]
            );
            if ($exists) {
                continue;
            }
            Database::query(
                'INSERT INTO equipment_register (project_id, equipment_type, registration, owner, date_on_site, date_off_site, `condition`, status, updated_by)
                 VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?)',
                [
                    $pid,
                    $item[0],
                    $item[1] . '-P' . $pid,
                    $item[2],
                    10 + ($pi % 20),
                    $item[4] === 'off-site' ? date('Y-m-d', strtotime('-2 days')) : null,
                    $item[3],
                    $item[4],
                    $uid,
                ]
            );
        }
    }
}

// --- Labour ---
foreach ($contractors as $c) {
    $uid = (int)$c['id'];
    $pids = contractor_projects($uid);
    foreach (array_slice($pids, 0, 4) as $pi => $pid) {
        for ($d = 0; $d < 4; $d++) {
            $date = date('Y-m-d', strtotime('-' . ($d + $pi) . ' days'));
            $exists = Database::fetch('SELECT id FROM labour_register WHERE project_id = ? AND diary_date = ? LIMIT 1', [$pid, $date]);
            $skilled = 12 + (($pi + $d) % 8);
            $unskilled = 28 + (($pi + $d) % 12);
            $sup = 2 + (($pi + $d) % 2);
            $total = $skilled + $unskilled + $sup;
            if ($exists) {
                Database::query(
                    'UPDATE labour_register SET skilled_count = ?, unskilled_count = ?, supervisor_count = ?, total = ?, recorded_by = ?, status = ?, updated_by = ? WHERE id = ?',
                    [$skilled, $unskilled, $sup, $total, $uid, 'submitted', $uid, (int)$exists['id']]
                );
            } else {
                Database::query(
                    'INSERT INTO labour_register (project_id, diary_date, skilled_count, unskilled_count, supervisor_count, total, recorded_by, status, updated_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$pid, $date, $skilled, $unskilled, $sup, $total, $uid, 'submitted', $uid]
                );
            }
        }
    }
}

// --- Material deliveries ---
$matCatalog = [
    ['OPC 42.5N cement', 'Bamburi Cement', 400, 'bags', 'good', 'submitted'],
    ['Y16 high-yield bars', 'Steel Masters Ltd', 12.5, 'tonnes', 'good', 'submitted'],
    ['River sand', 'Trans-Nzoia Aggregates', 45, 'm3', 'pending-check', 'queried'],
    ['Hardcore fill', 'Kitale Quarries', 80, 'm3', 'good', 'accepted'],
    ['200mm hollow blocks', 'County Blocks Co', 2500, 'pcs', 'good', 'submitted'],
    ['Damp-proof membrane', 'BuildSafe Supplies', 18, 'rolls', 'damaged', 'queried'],
];
foreach ($contractors as $c) {
    $uid = (int)$c['id'];
    $pids = contractor_projects($uid);
    foreach ($pids as $pi => $pid) {
        $item = $matCatalog[$pi % count($matCatalog)];
        $note = 'DN-' . str_pad((string)($pid * 10 + $pi + 1), 4, '0', STR_PAD_LEFT);
        $exists = Database::fetch(
            'SELECT id FROM material_deliveries WHERE project_id = ? AND delivery_note_no = ? LIMIT 1',
            [$pid, $note]
        );
        $date = date('Y-m-d', strtotime('-' . (($pi % 12) + 1) . ' days'));
        if ($exists) {
            Database::query(
                'UPDATE material_deliveries SET material = ?, supplier = ?, delivery_date = ?, quantity = ?, unit = ?, `condition` = ?, status = ?, received_by = ?, updated_by = ? WHERE id = ?',
                [$item[0], $item[1], $date, $item[2], $item[3], $item[4], $item[5], $uid, $uid, (int)$exists['id']]
            );
        } else {
            Database::query(
                'INSERT INTO material_deliveries (project_id, material, supplier, delivery_date, quantity, unit, delivery_note_no, received_by, `condition`, approved, status, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$pid, $item[0], $item[1], $date, $item[2], $item[3], $note, $uid, $item[4], $item[5] === 'accepted' ? 1 : 0, $item[5], $uid]
            );
        }
        // second delivery variety
        $item2 = $matCatalog[($pi + 2) % count($matCatalog)];
        $note2 = 'DN-' . str_pad((string)($pid * 10 + $pi + 50), 4, '0', STR_PAD_LEFT);
        if (!Database::fetch('SELECT id FROM material_deliveries WHERE project_id = ? AND delivery_note_no = ? LIMIT 1', [$pid, $note2])) {
            Database::query(
                'INSERT INTO material_deliveries (project_id, material, supplier, delivery_date, quantity, unit, delivery_note_no, received_by, `condition`, approved, status, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
                [$pid, $item2[0], $item2[1], date('Y-m-d', strtotime('-' . (($pi % 7) + 3) . ' days')), $item2[2], $item2[3], $note2, $uid, $item2[4], 'submitted', $uid]
            );
        }
    }
}

// --- HS incidents: ensure contractor-facing demos on assigned projects ---
$incidentTemplates = [
    ['near-miss', 'medium', 'open', 'Scaffold sole board shifted during wind gust near Block A stair core.', 'Re-seated sole boards and added extra ties.', 'Weather and incomplete edge protection.'],
    ['first-aid', 'low', 'investigating', 'Minor cut to left hand while handling steel mesh at cutting bay.', 'First aid applied; cut-resistant gloves enforced.', 'Gloves not worn during mesh handling.'],
    ['near-miss', 'high', 'action-pending', 'Excavator swing radius entered pedestrian walkway without banksman.', 'Banksman assigned; walkway barricaded.', 'Inadequate exclusion zone control.'],
    ['medical', 'high', 'investigating', 'Worker reported lower back strain after lifting formwork panels.', 'Medical referral; two-person lift rule rebriefed.', 'Manual handling without mechanical aid.'],
];
foreach ($contractors as $c) {
    $uid = (int)$c['id'];
    $pids = contractor_projects($uid);
    foreach (array_slice($pids, 0, 3) as $pi => $pid) {
        $t = $incidentTemplates[$pi % count($incidentTemplates)];
        $exists = Database::fetch(
            'SELECT id FROM hs_incidents WHERE project_id = ? AND reported_by = ? AND description = ? LIMIT 1',
            [$pid, $uid, $t[3]]
        );
        if ($exists) {
            continue;
        }
        Database::query(
            'INSERT INTO hs_incidents (project_id, incident_date, incident_type, description, persons_involved, cause, corrective_action, reported_by, severity, status, follow_up_date, updated_by, attachment_path)
             VALUES (?, DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 5 DAY), ?, ?)',
            [
                $pid,
                3 + $pi,
                $t[0],
                $t[3],
                'Site crew / ' . ($pi + 1) . ' persons',
                $t[5],
                $t[4],
                $uid,
                $t[1],
                $t[2],
                $uid,
                'refs/hs-incident-p' . $pid . '.pdf',
            ]
        );
    }
}

// --- Subcontractors professional names ---
$subCatalog = [
    ['Rift Valley Electricals Ltd', 'Electrical installation package', 4850000, 'active', 'compliant', 'normal', 'James Otieno', '0722001100', 'ops@rvelectricals.co.ke'],
    ['Highland Plumbing Works', 'Internal plumbing and drainage', 3120000, 'active', 'pending', 'watch', 'Mercy Chebet', '0711556677', 'info@highlandplumbing.co.ke'],
    ['Kitale Formwork Specialists', 'Formwork and falsework supply', 2680000, 'pending', 'pending', 'normal', 'Peter Wanyama', '0700112233', 'forms@kfs.co.ke'],
    ['Greenline Scaffolding', 'Scaffold hire and inspection', 1450000, 'active', 'compliant', 'normal', 'Anne Nafula', '0744221100', 'hire@greenlinescaffold.co.ke'],
    ['North Rift Steel Fixers', 'Reinforcement fixing labour', 1980000, 'suspended', 'issue', 'high', 'Brian Simiyu', '0799001122', 'steel@nrsf.co.ke'],
    ['Trans-Nzoia Waterproofing Co', 'Basement waterproofing works', 3560000, 'active', 'compliant', 'normal', 'Faith Nekesa', '0712889900', 'works@tnwaterproof.co.ke'],
];
$subs = Database::fetchAll('SELECT id, project_id FROM subcontractors ORDER BY id');
foreach ($subs as $i => $row) {
    $s = $subCatalog[$i % count($subCatalog)];
    Database::query(
        'UPDATE subcontractors SET company = ?, scope_of_work = ?, contract_value = ?, status = ?, contact_person = ?, phone = ?, email = ?, compliance_status = ?, risk_status = ?, performance_note = ? WHERE id = ?',
        [
            $s[0],
            $s[1],
            $s[2],
            $s[3],
            $s[6],
            $s[7],
            $s[8],
            $s[4],
            $s[5],
            'Performance tracked under site quality and programme reviews.',
            (int)$row['id'],
        ]
    );
}
// Fill missing projects for contractors without subs
foreach ($contractors as $c) {
    $uid = (int)$c['id'];
    foreach (contractor_projects($uid) as $pi => $pid) {
        $count = (int)(Database::fetch('SELECT COUNT(*) AS c FROM subcontractors WHERE project_id = ?', [$pid])['c'] ?? 0);
        if ($count > 0) {
            continue;
        }
        $s = $subCatalog[$pi % count($subCatalog)];
        Database::query(
            'INSERT INTO subcontractors (project_id, company, scope_of_work, contract_value, status, contact_person, phone, email, compliance_status, risk_status, performance_note, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$pid, $s[0], $s[1], $s[2], $s[3], $s[6], $s[7], $s[8], $s[4], $s[5], 'Mobilised against approved programme package.', $uid]
        );
    }
}

// Counts
foreach (['documents', 'equipment_register', 'hs_incidents', 'labour_register', 'material_deliveries', 'subcontractors'] as $t) {
    $c = Database::fetch("SELECT COUNT(*) AS c FROM `{$t}`");
    echo "{$t}=" . (int)($c['c'] ?? 0) . "\n";
}
$p9 = (int)(Database::fetch("SELECT COUNT(*) AS c FROM documents WHERE original_name LIKE '%P9%' OR filename LIKE '%p9-demo%'")['c'] ?? 0);
echo "p9_docs_left={$p9}\n";
echo "RESEED OK\n";
