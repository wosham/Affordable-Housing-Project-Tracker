<?php

/**
 * Phase 9 consultant documents & contract demo seed.
 * Idempotent: skips rows already present by unique keys / demo tags.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 9 consultant documents & contract seed ===\n";

$consultant = Database::fetch(
    "SELECT u.id, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS name
     FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'consultant' AND u.status = 'active'
     ORDER BY u.id ASC LIMIT 1"
);
if (!$consultant) {
    fwrite(STDERR, "No consultant found.\n");
    exit(1);
}
$consultantId = (int)$consultant['id'];
echo "Consultant #{$consultantId} {$consultant['name']}\n";

$contractor = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'contractor' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
$manager = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'manager' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
$sa = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'superadmin' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);

$submitterId = (int)($contractor['id'] ?? $clerk['id'] ?? $consultantId);
$recorderId = (int)($clerk['id'] ?? $submitterId);
$managerId = (int)($manager['id'] ?? 0);
$saId = (int)($sa['id'] ?? 0);

$projects = Database::fetchAll(
    "SELECT p.id, p.name
     FROM projects p
     WHERE p.consultant_id = ?
        OR EXISTS (
            SELECT 1 FROM project_assignments pa
            WHERE pa.project_id = p.id AND pa.user_id = ? AND pa.status = 'active'
        )
     ORDER BY p.id ASC
     LIMIT 8",
    [$consultantId, $consultantId]
);
if ($projects === []) {
    fwrite(STDERR, "No assigned projects for consultant.\n");
    exit(1);
}

$projectIds = array_map(static fn ($p) => (int)$p['id'], $projects);
$pick = static function (int $i) use ($projectIds): int {
    return $projectIds[$i % count($projectIds)];
};

$uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0775, true);
}

$created = [
    'documents' => 0,
    'site_diaries' => 0,
    'materials' => 0,
    'drawings' => 0,
    'eot' => 0,
    'variations' => 0,
    'messages' => 0,
];

try {
    Database::beginTransaction();

    // --- Documents (~14) ---
    $docSpecs = [
        ['category' => 'contract', 'name' => 'P9 Contract Particular Conditions Rev B.pdf', 'status' => 'pending', 'desc' => 'Demo contract control for consultant review.'],
        ['category' => 'spec', 'name' => 'P9 Structural Specification Section 03.pdf', 'status' => 'pending', 'desc' => 'Concrete and reinforcement specification package.'],
        ['category' => 'drawing', 'name' => 'P9 Architectural Layout Level 01.pdf', 'status' => 'pending', 'desc' => 'Issued for construction layout drawing.'],
        ['category' => 'shop-drawing', 'name' => 'P9 Rebar Shop Drawing Package.pdf', 'status' => 'flagged', 'desc' => 'Shop drawing package awaiting coordination check.'],
        ['category' => 'report', 'name' => 'P9 Weekly Progress Report W24.pdf', 'status' => 'reviewed', 'desc' => 'Weekly progress narrative and photos.'],
        ['category' => 'correspondence', 'name' => 'P9 RFI-041 Foundation levels.docx', 'status' => 'returned', 'desc' => 'RFI response draft requiring clarification.'],
        ['category' => 'quality-test', 'name' => 'P9 Cube Test Certificate Batch 12.pdf', 'status' => 'pending', 'desc' => 'Concrete cube test certificate for review.'],
        ['category' => 'other', 'name' => 'P9 Method Statement Formwork.pdf', 'status' => 'pending', 'desc' => 'Method statement for vertical formwork operations.'],
        ['category' => 'contract', 'name' => 'P9 Payment Schedule Appendix C.pdf', 'status' => 'reviewed', 'desc' => 'Contract payment schedule appendix.'],
        ['category' => 'drawing', 'name' => 'P9 Electrical Single Line Diagram.pdf', 'status' => 'pending', 'desc' => 'Electrical SLD for plant room coordination.'],
        ['category' => 'spec', 'name' => 'P9 Waterproofing Spec Addendum 02.pdf', 'status' => 'flagged', 'desc' => 'Addendum affecting basement waterproofing detail.'],
        ['category' => 'report', 'name' => 'P9 Geotech Interpretation Note.pdf', 'status' => 'pending', 'desc' => 'Interpretation note for foundation bearing.'],
        ['category' => 'correspondence', 'name' => 'P9 Client Instruction CI-08.pdf', 'status' => 'closed', 'desc' => 'Client instruction closed after review.'],
        ['category' => 'shop-drawing', 'name' => 'P9 Curtain Wall Interface Detail.pdf', 'status' => 'pending', 'desc' => 'Interface detail for facade package.'],
    ];

    foreach ($docSpecs as $i => $spec) {
        $exists = Database::fetch(
            'SELECT id FROM documents WHERE original_name = ? LIMIT 1',
            [$spec['name']]
        );
        if ($exists) {
            echo "  skip doc: {$spec['name']}\n";
            continue;
        }
        $projectId = $pick($i);
        $relPath = 'uploads/documents/p9-demo-' . ($i + 1) . '.txt';
        $absPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
        if (!is_file($absPath)) {
            file_put_contents($absPath, "Phase 9 demo document placeholder\n{$spec['name']}\nProject {$projectId}\n");
        }
        $size = is_file($absPath) ? (int)filesize($absPath) : 1024;
        Database::query(
            'INSERT INTO documents (project_id, uploaded_by, category, filename, original_name, size, version, description, is_confidential, review_required, consultant_review_status, consultant_review_note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))',
            [
                $projectId,
                $submitterId,
                $spec['category'],
                $relPath,
                $spec['name'],
                $size,
                '1.' . ($i % 3),
                $spec['desc'],
                $spec['status'],
                in_array($spec['status'], ['reviewed', 'returned', 'flagged', 'closed'], true) ? 'Demo consultant note for UAT.' : null,
                $i % 12,
            ]
        );
        $created['documents']++;
        echo "  + document: {$spec['name']}\n";
    }

    // --- Site diaries (~12) — unique per project+date ---
    $diarySpecs = [
        ['title' => 'P9 Daily diary — foundation pour', 'status' => 'pending', 'weather' => 'Partly cloudy, 24C', 'work' => 'Foundation pour bay A complete. Curing blankets installed.', 'issues' => 'Minor delay on pump arrival (45 minutes).', 'next' => 'Continue wall formwork bay B.'],
        ['title' => 'P9 Daily diary — rebar inspection', 'status' => 'pending', 'weather' => 'Sunny, 26C', 'work' => 'Rebar cage inspection for ground beam GB-04.', 'issues' => 'Two missing stirrups noted and corrected on site.', 'next' => 'Pour GB-04 after consultant sign-off.'],
        ['title' => 'P9 Daily diary — formwork strike', 'status' => 'flagged', 'weather' => 'Light rain AM', 'work' => 'Struck formwork on columns C12-C16.', 'issues' => 'Honeycomb observed on C14 — photo logged.', 'next' => 'NCR follow-up and remedial patch proposal.'],
        ['title' => 'P9 Daily diary — material delivery', 'status' => 'reviewed', 'weather' => 'Clear', 'work' => 'Received 40 tonnes cement and 12 bundles rebar.', 'issues' => 'None recorded.', 'next' => 'Stock check and issue to works.'],
        ['title' => 'P9 Daily diary — safety toolbox', 'status' => 'pending', 'weather' => 'Overcast', 'work' => 'Toolbox talk on working at height; scaffold tag checks.', 'issues' => 'One incomplete scaffold tag rectified.', 'next' => 'Continue slab decking level 1.'],
        ['title' => 'P9 Daily diary — MEP first fix', 'status' => 'returned', 'weather' => 'Warm, dry', 'work' => 'Electrical first fix corridors block 2.', 'issues' => 'Coordination clash with HVAC duct — sketch attached.', 'next' => 'Coordination meeting with M&E subcontractor.'],
        ['title' => 'P9 Daily diary — waterproofing', 'status' => 'pending', 'weather' => 'Dry, ideal for membrane', 'work' => 'Basement waterproofing membrane applied zone 3.', 'issues' => 'None.', 'next' => 'Protection board install zone 3.'],
        ['title' => 'P9 Daily diary — concrete test', 'status' => 'pending', 'weather' => 'Hot afternoon', 'work' => 'Cube samples taken for pour batch 19.', 'issues' => 'Slump at upper limit — recorded.', 'next' => 'Await lab results.'],
        ['title' => 'P9 Daily diary — access control', 'status' => 'closed', 'weather' => 'Cool morning', 'work' => 'Gate access protocol refreshed for visitors.', 'issues' => 'None.', 'next' => 'Routine operations.'],
        ['title' => 'P9 Daily diary — drainage works', 'status' => 'pending', 'weather' => 'Showers PM', 'work' => 'Storm drain excavation line D-02.', 'issues' => 'Groundwater seepage — pumps deployed.', 'next' => 'Continue bedding and pipe lay.'],
        ['title' => 'P9 Daily diary — QA walkthrough', 'status' => 'flagged', 'weather' => 'Clear', 'work' => 'QA walkthrough with clerk of works.', 'issues' => 'Finishes sample board incomplete.', 'next' => 'Update sample board before client visit.'],
        ['title' => 'P9 Daily diary — night pour prep', 'status' => 'pending', 'weather' => 'Mild evening', 'work' => 'Night pour preparation complete for slab edge.', 'issues' => 'Lighting pack shortage — ordered.', 'next' => 'Night pour window 20:00-02:00.'],
    ];

    foreach ($diarySpecs as $i => $spec) {
        $projectId = $pick($i + 1);
        $diaryDate = date('Y-m-d', strtotime('-' . ($i + 1) . ' days'));
        $exists = Database::fetch(
            'SELECT id FROM site_diaries WHERE project_id = ? AND diary_date = ? LIMIT 1',
            [$projectId, $diaryDate]
        );
        if ($exists) {
            // Try alternate date on same project
            $diaryDate = date('Y-m-d', strtotime('-' . ($i + 20) . ' days'));
            $exists = Database::fetch(
                'SELECT id FROM site_diaries WHERE project_id = ? AND diary_date = ? LIMIT 1',
                [$projectId, $diaryDate]
            );
        }
        if ($exists) {
            echo "  skip diary project {$projectId} {$diaryDate}\n";
            continue;
        }
        Database::query(
            'INSERT INTO site_diaries (project_id, diary_date, report_title, weather_summary, work_done, issues_raised, next_day_plan, recorded_by, consultant_review_status, consultant_review_note, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "submitted", DATE_SUB(NOW(), INTERVAL ? DAY))',
            [
                $projectId,
                $diaryDate,
                $spec['title'],
                $spec['weather'],
                $spec['work'],
                $spec['issues'],
                $spec['next'],
                $recorderId,
                $spec['status'],
                in_array($spec['status'], ['reviewed', 'returned', 'flagged', 'closed'], true) ? 'Demo site report review note.' : null,
                $i % 10,
            ]
        );
        $created['site_diaries']++;
        echo "  + diary: {$spec['title']} ({$diaryDate})\n";
    }

    // --- Materials (~12) ---
    $matSpecs = [
        ['material' => 'P9 OPC 42.5 Cement', 'spec' => 'KS EAS 18-1 Ordinary Portland Cement 42.5N', 'status' => 'pending'],
        ['material' => 'P9 High-yield rebar Y16', 'spec' => 'BS 4449 Grade B500B deformed bars', 'status' => 'pending'],
        ['material' => 'P9 Structural steel UB 203x133', 'spec' => 'EN 10025 S275JR universal beams', 'status' => 'pending'],
        ['material' => 'P9 Waterproof membrane', 'spec' => 'APP modified bituminous membrane 4mm', 'status' => 'approved'],
        ['material' => 'P9 Formwork plywood 18mm', 'spec' => 'Film-faced marine grade plywood', 'status' => 'pending'],
        ['material' => 'P9 Ready-mix C30/37', 'spec' => 'Slump 100-140mm, max agg 20mm', 'status' => 'rejected'],
        ['material' => 'P9 Electrical cable 2.5mm', 'spec' => 'PVC insulated copper twin & earth', 'status' => 'pending'],
        ['material' => 'P9 UPVC drainage pipes', 'spec' => 'EN 1401 SN8 underground drainage', 'status' => 'pending'],
        ['material' => 'P9 Ceramic floor tiles 600x600', 'spec' => 'PEI IV, slip rating R10', 'status' => 'approved'],
        ['material' => 'P9 Aluminium window system', 'spec' => 'Thermally broken frames, powder coat RAL 7016', 'status' => 'pending'],
        ['material' => 'P9 Blockwork 200mm hollow', 'spec' => 'Concrete hollow blocks Class A', 'status' => 'pending'],
        ['material' => 'P9 Roofing sheets 28G', 'spec' => 'Pre-painted galvanised iron IT4 profile', 'status' => 'pending'],
    ];

    foreach ($matSpecs as $i => $spec) {
        $exists = Database::fetch(
            'SELECT id FROM material_approvals WHERE material = ? LIMIT 1',
            [$spec['material']]
        );
        if ($exists) {
            echo "  skip material: {$spec['material']}\n";
            continue;
        }
        $projectId = $pick($i + 2);
        $approvedBy = $spec['status'] === 'pending' ? null : $consultantId;
        $approvedDate = $spec['status'] === 'pending' ? null : date('Y-m-d', strtotime('-' . ($i % 5) . ' days'));
        $notes = $spec['status'] === 'rejected' ? 'Demo return: supply mix design certificate before resubmission.' : ($spec['status'] === 'approved' ? 'Demo approval: conforms to project specification.' : null);
        Database::query(
            'INSERT INTO material_approvals (project_id, material, specification, submitted_by, submitted_date, approved_by, approved_date, status, notes)
             VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?)',
            [
                $projectId,
                $spec['material'],
                $spec['spec'],
                $submitterId,
                $i % 14,
                $approvedBy,
                $approvedDate,
                $spec['status'],
                $notes,
            ]
        );
        $created['materials']++;
        echo "  + material: {$spec['material']}\n";
    }

    // --- Shop drawings (~12) ---
    $drawSpecs = [
        ['no' => 'P9-SD-STR-001', 'title' => 'Ground beam rebar layout', 'rev' => 'A', 'status' => 'under-review'],
        ['no' => 'P9-SD-STR-002', 'title' => 'Column starter bars schedule', 'rev' => 'B', 'status' => 'under-review'],
        ['no' => 'P9-SD-ARC-010', 'title' => 'Stair core details', 'rev' => 'A', 'status' => 'resubmit'],
        ['no' => 'P9-SD-MEP-021', 'title' => 'Plant room coordination', 'rev' => 'C', 'status' => 'under-review'],
        ['no' => 'P9-SD-ARC-015', 'title' => 'Window schedule block 1', 'rev' => 'A', 'status' => 'approved'],
        ['no' => 'P9-SD-STR-008', 'title' => 'Slab edge formwork', 'rev' => 'A', 'status' => 'under-review'],
        ['no' => 'P9-SD-CIV-003', 'title' => 'Drainage invert levels', 'rev' => 'B', 'status' => 'rejected'],
        ['no' => 'P9-SD-ARC-022', 'title' => 'Kitchen wet area details', 'rev' => 'A', 'status' => 'under-review'],
        ['no' => 'P9-SD-MEP-030', 'title' => 'Fire alarm device layout', 'rev' => 'A', 'status' => 'resubmit'],
        ['no' => 'P9-SD-STR-014', 'title' => 'Lift pit reinforcement', 'rev' => 'A', 'status' => 'under-review'],
        ['no' => 'P9-SD-ARC-031', 'title' => 'Facade panel interface', 'rev' => 'B', 'status' => 'under-review'],
        ['no' => 'P9-SD-CIV-011', 'title' => 'Road kerb setting out', 'rev' => 'A', 'status' => 'approved'],
    ];

    foreach ($drawSpecs as $i => $spec) {
        $exists = Database::fetch(
            'SELECT id FROM shop_drawings WHERE drawing_no = ? LIMIT 1',
            [$spec['no']]
        );
        if ($exists) {
            echo "  skip drawing: {$spec['no']}\n";
            continue;
        }
        $projectId = $pick($i + 3);
        $reviewedBy = in_array($spec['status'], ['approved', 'rejected', 'resubmit'], true) ? $consultantId : null;
        $reviewDate = $reviewedBy ? date('Y-m-d', strtotime('-' . ($i % 6) . ' days')) : null;
        $note = match ($spec['status']) {
            'approved' => 'Demo approval: free to use on site.',
            'rejected' => 'Demo rejection: levels conflict with IFC set.',
            'resubmit' => 'Demo resubmit: update clash marks and reissue.',
            default => null,
        };
        Database::query(
            'INSERT INTO shop_drawings (project_id, drawing_no, title, submitted_by, submitted_date, revision, status, reviewed_by, review_date, review_note)
             VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?, ?)',
            [
                $projectId,
                $spec['no'],
                $spec['title'],
                $submitterId,
                $i % 12,
                $spec['rev'],
                $spec['status'],
                $reviewedBy,
                $reviewDate,
                $note,
            ]
        );
        $created['drawings']++;
        echo "  + drawing: {$spec['no']}\n";
    }

    // --- Pending EOTs (5) ---
    $eotSpecs = [
        ['days' => 14, 'reason' => 'P9 demo: prolonged rainfall delayed foundation excavation and dewatering operations.'],
        ['days' => 21, 'reason' => 'P9 demo: delayed design information on staircase core reinforcement details.'],
        ['days' => 10, 'reason' => 'P9 demo: utility diversion by authority delayed bulk earthworks package.'],
        ['days' => 7, 'reason' => 'P9 demo: restricted site access during county road works on approach road.'],
        ['days' => 18, 'reason' => 'P9 demo: late material shipment for structural steel package.'],
    ];
    $maxEot = (int)(Database::fetch('SELECT COALESCE(MAX(eot_number), 0) AS m FROM eot_requests')['m'] ?? 0);
    foreach ($eotSpecs as $i => $spec) {
        $tag = 'P9 demo:';
        $exists = Database::fetch(
            'SELECT id FROM eot_requests WHERE reason LIKE ? LIMIT 1',
            [$spec['reason']]
        );
        if ($exists) {
            echo "  skip eot: {$spec['reason']}\n";
            continue;
        }
        $projectId = $pick($i);
        $eotNo = $maxEot + $i + 1;
        Database::query(
            'INSERT INTO eot_requests (project_id, submitted_by, eot_number, days_requested, reason, status, consultant_review_status, created_at)
             VALUES (?, ?, ?, ?, ?, "pending", "pending", DATE_SUB(NOW(), INTERVAL ? DAY))',
            [$projectId, $submitterId, $eotNo, $spec['days'], $spec['reason'], $i % 8]
        );
        $created['eot']++;
        echo "  + eot #{$eotNo}: {$spec['days']} days\n";
    }

    // --- Pending variations (5) ---
    $voSpecs = [
        ['amount' => 1850000, 'days' => 5, 'desc' => 'P9 demo VO: additional retaining wall height due to revised site levels.', 'reason' => 'Client instructed level change.'],
        ['amount' => 920000, 'days' => 3, 'desc' => 'P9 demo VO: extra waterproofing membrane to basement plant room.', 'reason' => 'Specification upgrade.'],
        ['amount' => 2450000, 'days' => 8, 'desc' => 'P9 demo VO: additional parking bay paving and kerbs.', 'reason' => 'Scope addition from employer.'],
        ['amount' => 610000, 'days' => 2, 'desc' => 'P9 demo VO: upgrade electrical distribution board capacity.', 'reason' => 'Load assessment update.'],
        ['amount' => 1320000, 'days' => 4, 'desc' => 'P9 demo VO: temporary works for deep excavation shoring.', 'reason' => 'Safety requirement.'],
    ];
    $maxVo = (int)(Database::fetch('SELECT COALESCE(MAX(vo_number), 0) AS m FROM variations')['m'] ?? 0);
    foreach ($voSpecs as $i => $spec) {
        $exists = Database::fetch(
            'SELECT id FROM variations WHERE description = ? LIMIT 1',
            [$spec['desc']]
        );
        if ($exists) {
            echo "  skip vo: {$spec['desc']}\n";
            continue;
        }
        $projectId = $pick($i + 1);
        $voNo = $maxVo + $i + 1;
        Database::query(
            'INSERT INTO variations (project_id, submitted_by, vo_number, description, reason, amount, impact_on_time_days, status, consultant_review_status, consultant_cost_impact_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, "pending", "pending", "moderate", DATE_SUB(NOW(), INTERVAL ? DAY))',
            [$projectId, $submitterId, $voNo, $spec['desc'], $spec['reason'], $spec['amount'], $spec['days'], $i % 9]
        );
        $created['variations']++;
        echo "  + vo #{$voNo}: {$spec['amount']}\n";
    }

    // --- Message threads for consultant ---
    if ($saId > 0 && class_exists('MessageThread')) {
        $subjects = [
            [
                'subject' => 'P9 Document centre — demo coordination',
                'body' => "Teddy,\n\nThis is a Phase 9 demo thread for document and site report coordination.\nPlease use Message Centre for technical clarifications on assigned projects only.",
                'reply' => "Received. Document and site report reviews are active on the consultant portfolio.",
            ],
            [
                'subject' => 'P9 Contract decisions — EOT / VO demo',
                'body' => "Leadership,\n\nDemo thread for extension of time and variation recommendations.\nPending items are seeded for UAT of consultant contract review.",
                'reply' => "Noted. We will track consultant recommendations through the contract pages.",
            ],
        ];
        foreach ($subjects as $msg) {
            $existing = Database::fetch(
                "SELECT t.id
                 FROM message_threads t
                 INNER JOIN message_participants mp ON mp.thread_id = t.id AND mp.user_id = ?
                 WHERE t.subject = ?
                 LIMIT 1",
                [$consultantId, $msg['subject']]
            );
            if ($existing) {
                echo "  skip message: {$msg['subject']}\n";
                continue;
            }
            if (!MessageThread::canMessageUser($consultantId, 'consultant', $saId, null)
                && !MessageThread::canMessageUser($saId, 'superadmin', $consultantId, null)) {
                // Superadmin can always participate; create as SA-initiated if needed
            }
            $fromId = $saId;
            $fromRole = 'superadmin';
            $toId = $consultantId;
            $toRole = 'consultant';
            if (!MessageThread::canMessageUser($fromId, $fromRole, $toId, null)) {
                $fromId = $consultantId;
                $fromRole = 'consultant';
                $toId = $saId;
                $toRole = 'superadmin';
                if (!MessageThread::canMessageUser($fromId, $fromRole, $toId, null)) {
                    echo "  skip message policy: {$msg['subject']}\n";
                    continue;
                }
            }
            $threadId = MessageThread::createThread($msg['subject'], 'direct', $fromId, null, 'normal');
            MessageParticipant::add($threadId, $fromId, $fromRole, true);
            MessageParticipant::add($threadId, $toId, $toRole, false);
            $mid = Message::createForThread($threadId, $fromId, $msg['body']);
            MessageThread::touchLastMessage($threadId, $mid);
            MessageRead::markThread($threadId, $fromId);
            if ($msg['reply'] !== '') {
                $rid = Message::createForThread($threadId, $toId, $msg['reply']);
                MessageThread::touchLastMessage($threadId, $rid);
                MessageRead::markThread($threadId, $toId);
            }
            $created['messages']++;
            echo "  + message: {$msg['subject']}\n";
        }

        // Optional project channel with manager if available
        if ($managerId > 0) {
            $project = $projects[0];
            $subject = 'P9 ' . $project['name'] . ' — technical channel';
            $existing = Database::fetch(
                "SELECT t.id FROM message_threads t
                 INNER JOIN message_participants mp ON mp.thread_id = t.id AND mp.user_id = ?
                 WHERE t.subject = ? LIMIT 1",
                [$consultantId, $subject]
            );
            if (!$existing) {
                $projectId = (int)$project['id'];
                $threadId = MessageThread::createThread($subject, 'project-channel', $consultantId, $projectId, 'normal');
                MessageParticipant::add($threadId, $consultantId, 'consultant', true);
                if (MessageThread::canMessageUser($consultantId, 'consultant', $managerId, $projectId)) {
                    MessageParticipant::add($threadId, $managerId, 'manager', false);
                }
                if (MessageThread::canMessageUser($consultantId, 'consultant', $saId, $projectId)) {
                    MessageParticipant::add($threadId, $saId, 'superadmin', false);
                }
                $body = "Team,\n\nPhase 9 demo channel for {$project['name']}.\nUse for document, material, drawing and contract clarifications.";
                $mid = Message::createForThread($threadId, $consultantId, $body);
                MessageThread::touchLastMessage($threadId, $mid);
                MessageRead::markThread($threadId, $consultantId);
                $created['messages']++;
                echo "  + channel: {$subject}\n";
            } else {
                echo "  skip channel: {$subject}\n";
            }
        }
    }

    Database::commit();
} catch (Throwable $e) {
    Database::rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nCreated summary:\n";
foreach ($created as $k => $v) {
    echo "  {$k}: {$v}\n";
}
echo "Done.\n";
