<?php
/**
 * Phase 15: light UAT seed for clerk quality/evidence on assigned projects.
 * Does not invent payments or mark IPCs paid.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 15 seed ===\n";

$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
if (!$clerk) {
    echo "FAIL no clerk\n";
    exit(1);
}
$uid = (int)$clerk['id'];
$projects = ClerkQualityEvidence::projects($uid);
if ($projects === []) {
    echo "FAIL clerk has no projects\n";
    exit(1);
}

$seeded = 0;
foreach (array_slice($projects, 0, 3) as $project) {
    $pid = (int)$project['id'];
    $today = date('Y-m-d');

    // Quality test if none today
    $exists = Database::fetch(
        "SELECT id FROM quality_tests WHERE project_id = ? AND test_date = ? AND test_type = ? LIMIT 1",
        [$pid, $today, 'Cube strength (UAT)']
    );
    if (!$exists) {
        ClerkQualityEvidence::save('quality', $uid, [
            'project_id' => $pid,
            'test_type' => 'Cube strength (UAT)',
            'test_date' => $today,
            'location_on_site' => 'Block A foundation',
            'required_result' => '>= 25 MPa',
            'actual_result' => '27.4 MPa',
            'lab_ref' => 'UAT-LAB-001',
            'verification_status' => 'passed',
            'clerk_observation' => 'Phase 15 seed — within specification.',
        ]);
        $seeded++;
    }

    $exists = Database::fetch(
        "SELECT id FROM inspection_test_plans WHERE project_id = ? AND activity = ? AND inspection_date = ? LIMIT 1",
        [$pid, 'Formwork readiness (UAT)', $today]
    );
    if (!$exists) {
        ClerkQualityEvidence::save('itp', $uid, [
            'project_id' => $pid,
            'activity' => 'Formwork readiness (UAT)',
            'inspection_date' => $today,
            'inspection_area' => 'Ground floor slab',
            'hold_point' => 'HP-01',
            'outcome' => 'Ready for pour',
            'inspection_status' => 'passed',
            'clerk_notes' => 'Phase 15 seed inspection.',
        ]);
        $seeded++;
    }

    $exists = Database::fetch(
        "SELECT id FROM non_conformance_reports WHERE project_id = ? AND description LIKE ? LIMIT 1",
        [$pid, 'Phase 15 seed NCR%']
    );
    if (!$exists) {
        ClerkQualityEvidence::save('ncr', $uid, [
            'project_id' => $pid,
            'raised_date' => $today,
            'location_on_site' => 'Site store',
            'description' => 'Phase 15 seed NCR — material batch label incomplete.',
            'severity' => 'minor',
            'status' => 'open',
            'root_cause' => 'Supplier packing omission',
            'corrective_action' => 'Request re-label before use',
        ]);
        $seeded++;
    }

    $exists = Database::fetch(
        "SELECT id FROM defects WHERE project_id = ? AND description LIKE ? LIMIT 1",
        [$pid, 'Phase 15 seed defect%']
    );
    if (!$exists) {
        ClerkQualityEvidence::save('defect', $uid, [
            'project_id' => $pid,
            'raised_date' => $today,
            'location' => 'Block B — stair nosing',
            'description' => 'Phase 15 seed defect — chipped edge on precast unit.',
            'severity' => 'minor',
            'status' => 'open',
            'rectification_notes' => 'Await contractor repair proposal.',
        ]);
        $seeded++;
    }
}

echo "records_seeded={$seeded}\n";
echo "SEED OK\n";
