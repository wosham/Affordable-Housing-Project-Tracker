<?php
/**
 * Phase 16 light UAT seed for labour / materials / equipment on clerk projects.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 16 seed ===\n";
$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
if (!$clerk) {
    echo "FAIL no clerk\n";
    exit(1);
}
$uid = (int)$clerk['id'];
$projects = ClerkDailyRecord::projects($uid);
if ($projects === []) {
    echo "FAIL no projects\n";
    exit(1);
}

$seeded = 0;
$today = date('Y-m-d');
foreach (array_slice($projects, 0, 2) as $project) {
    $pid = (int)$project['id'];

    // Labour: ensure contractor baseline + clerk verification
    $lab = Database::fetch('SELECT id FROM labour_register WHERE project_id = ? AND diary_date = ? LIMIT 1', [$pid, $today]);
    if (!$lab) {
        Database::query(
            "INSERT INTO labour_register (project_id, diary_date, skilled_count, unskilled_count, supervisor_count, total, recorded_by, status, verification_status)
             VALUES (?, ?, 8, 20, 2, 30, ?, 'submitted', 'pending')",
            [$pid, $today, $uid]
        );
        $seeded++;
    }
    ClerkDailyRecord::save('labour', $uid, [
        'project_id' => $pid,
        'diary_date' => $today,
        'clerk_skilled_count' => 8,
        'clerk_unskilled_count' => 19,
        'clerk_supervisor_count' => 2,
        'verification_status' => 'queried',
        'verification_notes' => 'Phase 16 seed — unskilled count one short of contractor return.',
    ]);
    $seeded++;

    $matExists = Database::fetch(
        "SELECT id FROM material_deliveries WHERE project_id = ? AND material = ? AND delivery_date = ? LIMIT 1",
        [$pid, 'Cement 42.5 (UAT)', $today]
    );
    if (!$matExists) {
        ClerkDailyRecord::save('materials', $uid, [
            'project_id' => $pid,
            'delivery_date' => $today,
            'material' => 'Cement 42.5 (UAT)',
            'supplier' => 'Trans-Nzoia Building Supplies',
            'quantity' => 200,
            'verified_quantity' => 198,
            'unit' => 'bags',
            'delivery_note_no' => 'DN-P16-' . $pid,
            'condition' => 'good',
            'verification_status' => 'accepted',
            'verification_notes' => 'Phase 16 seed delivery check.',
        ]);
        $seeded++;
    }

    $eqExists = Database::fetch(
        "SELECT id FROM equipment_register WHERE project_id = ? AND equipment_type = ? LIMIT 1",
        [$pid, 'Concrete mixer (UAT)']
    );
    if (!$eqExists) {
        ClerkDailyRecord::save('equipment', $uid, [
            'project_id' => $pid,
            'equipment_type' => 'Concrete mixer (UAT)',
            'registration' => 'CM-P16-' . $pid,
            'owner' => 'Contractor plant pool',
            'date_on_site' => $today,
            'condition' => 'good',
            'status' => 'on-site',
            'check_status' => 'present',
            'check_notes' => 'Phase 16 seed equipment present and serviceable.',
        ]);
        $seeded++;
    }
}

echo "records_touched={$seeded}\n";
echo "SEED OK\n";
