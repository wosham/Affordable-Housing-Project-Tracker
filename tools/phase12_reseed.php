<?php
/**
 * Phase 12: professional contractor submission demo data.
 * Removes P9 demo wording, renumbers RFIs, diversifies statuses, adds support refs.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 12 reseed ===\n";

$contractors = Database::fetchAll(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'contractor' ORDER BY u.id"
);
if ($contractors === []) {
    echo "FAIL no contractors\n";
    exit(1);
}

// --- Clean P9 / demo wording -------------------------------------------------
$cleanMaps = [
    'eot_requests' => [
        'reason' => [
            'P9 demo: prolonged rainfall delayed foundation excavation and temporary drainage works.' =>
                'Prolonged rainfall delayed foundation excavation and temporary drainage works.',
            'P9 demo: delayed design information on staircase core reinforcement details.' =>
                'Delayed design information on staircase core reinforcement details.',
            'P9 demo: utility diversion by authority delayed bulk earthworks on the access road.' =>
                'Utility diversion by the authority delayed bulk earthworks on the access road.',
            'P9 demo: restricted site access during county road works on the approach road.' =>
                'Restricted site access during county road works on the approach road.',
            'P9 demo: late material shipment for structural steel package delayed superstructure.' =>
                'Late material shipment for the structural steel package delayed superstructure works.',
        ],
        'impact_summary' => [
            'P9 demo impact on programme critical path.' =>
                'Critical path activities on the foundation and superstructure programme were affected.',
        ],
    ],
    'variations' => [
        'description' => [
            'P9 demo VO: additional retaining wall height due to revised levels.' =>
                'Additional retaining wall height required due to revised site levels.',
            'P9 demo VO: extra waterproofing membrane to basement plant room.' =>
                'Extra waterproofing membrane to basement plant room.',
            'P9 demo VO: additional parking bay paving and kerbs.' =>
                'Additional parking bay paving and kerbs.',
            'P9 demo VO: upgrade electrical distribution board capacity.' =>
                'Upgrade electrical distribution board capacity.',
            'P9 demo VO: temporary works for deep excavation shoring.' =>
                'Temporary works for deep excavation shoring.',
        ],
        'reason' => [
            'P9 demo reason for variation.' =>
                'Site condition and design clarification required a formal variation.',
        ],
    ],
];

foreach ($cleanMaps as $table => $columns) {
    foreach ($columns as $column => $pairs) {
        foreach ($pairs as $from => $to) {
            Database::query("UPDATE `{$table}` SET `{$column}` = ? WHERE `{$column}` = ?", [$to, $from]);
        }
        Database::query(
            "UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, 'P9 demo: ', '') WHERE `{$column}` LIKE '%P9 demo%'"
        );
        Database::query(
            "UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, 'P9 demo VO: ', '') WHERE `{$column}` LIKE '%P9 demo%'"
        );
        Database::query(
            "UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, 'P9 demo', '') WHERE `{$column}` LIKE '%P9%'"
        );
    }
}

// Generic cleanup across submission narrative fields
$genericTables = [
    'shop_drawings' => ['title', 'document_path'],
    'material_approvals' => ['material', 'specification', 'notes'],
    'rfis' => ['subject', 'description'],
    'eot_requests' => ['reason', 'impact_summary', 'supporting_evidence'],
    'variations' => ['description', 'reason'],
];
foreach ($genericTables as $table => $cols) {
    foreach ($cols as $col) {
        Database::query("UPDATE `{$table}` SET `{$col}` = REPLACE(`{$col}`, 'P9 demo', '') WHERE `{$col}` LIKE '%P9%'");
        Database::query("UPDATE `{$table}` SET `{$col}` = REPLACE(`{$col}`, 'P9 ', '') WHERE `{$col}` LIKE '%P9%'");
        Database::query("UPDATE `{$table}` SET `{$col}` = TRIM(`{$col}`)");
    }
}

// Normalize delay categories (empty + legacy free text)
$delayRows = Database::fetchAll('SELECT id, delay_category, reason, impact_summary FROM eot_requests');
foreach ($delayRows as $row) {
    $slug = strtolower(trim((string)($row['delay_category'] ?? '')));
    $reason = strtolower((string)($row['reason'] ?? ''));
    if ($slug === '' || !in_array($slug, ['weather', 'access', 'design', 'materials', 'utilities', 'labour', 'authority', 'other'], true)) {
        if (str_contains($reason, 'rain') || str_contains($reason, 'weather')) {
            $slug = 'weather';
        } elseif (str_contains($reason, 'access') || str_contains($reason, 'road')) {
            $slug = 'access';
        } elseif (str_contains($reason, 'design') || str_contains($reason, 'reinforcement')) {
            $slug = 'design';
        } elseif (str_contains($reason, 'material') || str_contains($reason, 'steel') || str_contains($reason, 'shipment')) {
            $slug = 'materials';
        } elseif (str_contains($reason, 'utilit')) {
            $slug = 'utilities';
        } elseif (str_contains($reason, 'authority') || str_contains($reason, 'county')) {
            $slug = 'authority';
        } else {
            $slug = 'other';
        }
        Database::query('UPDATE eot_requests SET delay_category = ? WHERE id = ?', [$slug, (int)$row['id']]);
    }
    if (trim((string)($row['impact_summary'] ?? '')) === '') {
        Database::query(
            'UPDATE eot_requests SET impact_summary = ? WHERE id = ?',
            ['Programme impact recorded against the active critical path.', (int)$row['id']]
        );
    }
}

// --- Diversify RFIs + renumber per project -----------------------------------
$rfiSubjects = [
    ['Lintel reinforcement clarification - Block A openings', 'Please confirm bar schedule at lintel level for window openings A2-A4 against structural drawing S-204.', 'urgent', 'open'],
    ['Foundation blinding level at Block B', 'Site levels differ from the setting-out drawing by approximately 80mm. Confirm adjusted blinding level.', 'normal', 'answered'],
    ['Door ironmongery schedule conflict', 'Architectural schedule specifies lever handles while BOQ lists knob sets for Type D doors. Confirm approved type.', 'normal', 'closed'],
    ['Stormwater outfall invert at plot boundary', 'Please confirm invert level for the eastern outfall connection to the county drain.', 'urgent', 'open'],
    ['Staircase flight width - Block C', 'Confirm clear width at mid-landing for flight C2 against architectural detail A-312.', 'low', 'answered'],
    ['MEP sleeve positions through ground beam', 'Confirm coordinated sleeve locations for water and power services through GB-03 and GB-04.', 'normal', 'open'],
    ['Waterproofing termination detail at lift pit', 'Request approved termination detail where membrane meets lift pit wall construction joint.', 'urgent', 'closed'],
    ['External paving joint layout near entrance', 'Confirm expansion joint spacing for concrete paving to main entrance plaza.', 'low', 'answered'],
];

$rfis = Database::fetchAll('SELECT id, project_id, raised_by FROM rfis ORDER BY project_id, id');
$idx = 0;
$projectCounters = [];
foreach ($rfis as $rfi) {
    $sample = $rfiSubjects[$idx % count($rfiSubjects)];
    $pid = (int)$rfi['project_id'];
    $projectCounters[$pid] = ($projectCounters[$pid] ?? 0) + 1;
    $number = $projectCounters[$pid];
    $response = in_array($sample[3], ['answered', 'closed'], true)
        ? 'Response issued: proceed as per revised consultant instruction CI-' . str_pad((string)$number, 2, '0', STR_PAD_LEFT) . '.'
        : null;
    $responseDate = $response ? date('Y-m-d', strtotime('-' . (($idx % 10) + 1) . ' days')) : null;
    Database::query(
        'UPDATE rfis SET rfi_number = ?, subject = ?, description = ?, urgency = ?, status = ?, response = ?, response_date = ? WHERE id = ?',
        [$number, $sample[0], $sample[1], $sample[2], $sample[3], $response, $responseDate, (int)$rfi['id']]
    );
    $idx++;
}

// --- Status variety for shop drawings & materials ---------------------------
$shopStatuses = ['under-review', 'approved', 'rejected', 'resubmit', 'under-review', 'approved'];
$shops = Database::fetchAll('SELECT id, project_id, drawing_no, title FROM shop_drawings ORDER BY id');
$shopTitles = [
    'Ground beam reinforcement layout - Block A',
    'Column starter bars - Grid line 1-4',
    'Staircase flight reinforcement - Block B',
    'Roof truss connection plate detail',
    'Window aluminium frame shop drawing - Type W2',
    'Lift shaft wall reinforcement elevation',
    'Drainage manhole schedule - Plot network',
    'Balcony edge beam detail - Level 2',
];
foreach ($shops as $i => $row) {
    $status = $shopStatuses[$i % count($shopStatuses)];
    $title = $shopTitles[$i % count($shopTitles)];
    $path = 'drawings/' . strtolower(str_replace(' ', '-', (string)$row['drawing_no'])) . '-rev-a.pdf';
    Database::query(
        'UPDATE shop_drawings SET title = ?, status = ?, document_path = COALESCE(NULLIF(document_path, \'\'), ?) WHERE id = ?',
        [$title, $status, $path, (int)$row['id']]
    );
}

$matStatuses = ['pending', 'approved', 'rejected', 'pending', 'approved', 'pending'];
$materials = Database::fetchAll('SELECT id FROM material_approvals ORDER BY id');
$matNames = [
    'OPC 42.5N cement - Block A package',
    'High-yield reinforcement bars Y12/Y16',
    'River sand for structural concrete',
    'Hardcore fill - selected graded stone',
    'Damp-proof membrane 1000 gauge',
    'Hollow concrete blocks 200mm',
    'Structural steel sections - secondary beams',
    'Waterproofing bituminous membrane',
];
foreach ($materials as $i => $row) {
    $status = $matStatuses[$i % count($matStatuses)];
    $name = $matNames[$i % count($matNames)];
    $spec = 'Supplier datasheet and Kenya Standard compliance certificates attached for consultant review.';
    Database::query(
        'UPDATE material_approvals SET material = ?, specification = ?, status = ?, notes = ? WHERE id = ?',
        [$name, $spec, $status, 'Batch certificates available on request.', (int)$row['id']]
    );
}

// EOT / VO status mix (keep consultant queue healthy with some pending)
$eots = Database::fetchAll('SELECT id FROM eot_requests ORDER BY id');
$eotStatuses = ['pending', 'pending', 'granted', 'partially-granted', 'rejected', 'pending'];
foreach ($eots as $i => $row) {
    $status = $eotStatuses[$i % count($eotStatuses)];
    $granted = match ($status) {
        'granted' => null, // fill after read days
        'partially-granted' => null,
        default => 0,
    };
    $days = (int)(Database::fetch('SELECT days_requested FROM eot_requests WHERE id = ?', [(int)$row['id']])['days_requested'] ?? 7);
    $grantedDays = match ($status) {
        'granted' => $days,
        'partially-granted' => max(1, (int)floor($days / 2)),
        default => 0,
    };
    $crs = match ($status) {
        'pending' => 'pending',
        'rejected' => 'not-recommended',
        default => 'recommended',
    };
    Database::query(
        'UPDATE eot_requests SET status = ?, granted_days = ?, consultant_review_status = ? WHERE id = ?',
        [$status, $grantedDays, $crs, (int)$row['id']]
    );
}

$vos = Database::fetchAll('SELECT id FROM variations ORDER BY id');
$voStatuses = ['pending', 'pending', 'approved', 'rejected', 'pending', 'approved'];
foreach ($vos as $i => $row) {
    $status = $voStatuses[$i % count($voStatuses)];
    $crs = match ($status) {
        'pending' => 'pending',
        'rejected' => 'not-recommended',
        default => 'recommended',
    };
    Database::query(
        'UPDATE variations SET status = ?, consultant_review_status = ? WHERE id = ?',
        [$status, $crs, (int)$row['id']]
    );
    // Ensure reason is professional
    Database::query(
        "UPDATE variations SET reason = 'Site condition and design clarification required a formal variation.' WHERE id = ? AND (reason IS NULL OR reason = '' OR reason LIKE '%P9%' OR reason LIKE '%demo%')",
        [(int)$row['id']]
    );
}

// --- Support attachments (reference only; no fake binary files) -------------
Database::query('DELETE FROM contractor_submission_attachments WHERE title LIKE \'Phase 12%\' OR title LIKE \'Support ref%\'');

$attachPlans = [
    ['rfi', 'rfis', 'RFI clarification sketch reference'],
    ['eot', 'eot_requests', 'Weather delay site diary reference'],
    ['variation', 'variations', 'Variation sketch / cost build-up reference'],
    ['shop_drawing', 'shop_drawings', 'Drawing PDF / media reference'],
    ['material', 'material_approvals', 'Material datasheet reference'],
];

foreach ($attachPlans as [$type, $table, $title]) {
    $ownerCol = $type === 'rfi' ? 'raised_by' : 'submitted_by';
    $rows = Database::fetchAll("SELECT id, project_id, {$ownerCol} AS owner_id FROM `{$table}` ORDER BY id LIMIT 12");
    foreach ($rows as $i => $row) {
        if ($i % 2 === 1) {
            continue; // attach to every other record
        }
        Database::query(
            'INSERT INTO contractor_submission_attachments
                (submission_type, submission_id, project_id, media_id, path, title, uploaded_by)
             VALUES (?, ?, ?, NULL, ?, ?, ?)',
            [
                $type,
                (int)$row['id'],
                (int)$row['project_id'],
                'refs/' . $type . '-' . (int)$row['id'] . '.pdf',
                'Support ref: ' . $title,
                (int)$row['owner_id'],
            ]
        );
    }
}

// Verify clean
$p9 = 0;
foreach (['eot_requests' => 'reason', 'variations' => 'description', 'shop_drawings' => 'title', 'material_approvals' => 'material', 'rfis' => 'subject'] as $t => $c) {
    $p9 += (int)(Database::fetch("SELECT COUNT(*) AS c FROM `{$t}` WHERE `{$c}` LIKE '%P9%' OR `{$c}` LIKE '%demo%'")['c'] ?? 0);
}
$attach = (int)(Database::fetch('SELECT COUNT(*) AS c FROM contractor_submission_attachments')['c'] ?? 0);
$rfiZero = (int)(Database::fetch('SELECT COUNT(*) AS c FROM rfis WHERE rfi_number = 0 OR rfi_number IS NULL')['c'] ?? 0);
$emptyDelay = (int)(Database::fetch("SELECT COUNT(*) AS c FROM eot_requests WHERE delay_category IS NULL OR delay_category = ''")['c'] ?? 0);

echo "p9_demo_left={$p9}\n";
echo "attachments={$attach}\n";
echo "rfi_number_zero={$rfiZero}\n";
echo "empty_delay_category={$emptyDelay}\n";
echo "RESEED OK\n";
