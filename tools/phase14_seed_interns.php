<?php
/**
 * Phase 14: seed 10 interns across clerk projects + rewrite clerk attendance announcements.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 14 intern seed ===\n";

$role = Database::fetch("SELECT id FROM roles WHERE slug = 'intern' LIMIT 1");
if (!$role) {
    echo "FAIL no intern role\n";
    exit(1);
}
$roleId = (int)$role['id'];

// Password: Intern@2026 (same style as other demos if hash helper exists)
$hash = password_hash('Intern@2026', PASSWORD_DEFAULT);

$interns = [
    ['Amina', 'Wanjiru', 'amina.wanjiru.ahp@gmail.com', '0711001001'],
    ['Brian', 'Kipchoge', 'brian.kipchoge.ahp@gmail.com', '0711001002'],
    ['Cynthia', 'Achieng', 'cynthia.achieng.ahp@gmail.com', '0711001003'],
    ['Daniel', 'Mutiso', 'daniel.mutiso.ahp@gmail.com', '0711001004'],
    ['Esther', 'Njeri', 'esther.njeri.ahp@gmail.com', '0711001005'],
    ['Felix', 'Odhiambo', 'felix.odhiambo.ahp@gmail.com', '0711001006'],
    ['Grace', 'Chebet', 'grace.chebet.ahp@gmail.com', '0711001007'],
    ['Hassan', 'Ali', 'hassan.ali.ahp@gmail.com', '0711001008'],
    ['Irene', 'Wambui', 'irene.wambui.ahp@gmail.com', '0711001009'],
    ['Joseph', 'Kamau', 'joseph.kamau.ahp@gmail.com', '0711001010'],
];

$created = [];
foreach ($interns as $i => [$first, $last, $email, $phone]) {
    $existing = Database::fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
    if ($existing) {
        $uid = (int)$existing['id'];
        Database::query(
            'UPDATE users SET first_name = ?, last_name = ?, phone = ?, password_hash = ?, role_id = ?, status = ?, job_title = ?, department = ? WHERE id = ?',
            [$first, $last, $phone, $hash, $roleId, 'active', 'Site Intern', 'Field Operations', $uid]
        );
    } else {
        Database::query(
            'INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id, status, job_title, department, is_public)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
            [$first, $last, $email, $phone, $hash, $roleId, 'active', 'Site Intern', 'Field Operations']
        );
        $uid = (int)Database::lastInsertId();
    }
    $created[] = $uid;
    echo "intern {$email} id={$uid}\n";
}

// Clerk projects (Samuel Kibet and all clerks)
$clerkProjects = Database::fetchAll(
    "SELECT DISTINCT pa.project_id, pa.user_id AS clerk_id
     FROM project_assignments pa
     JOIN users u ON u.id = pa.user_id AND u.status = 'active'
     JOIN roles r ON r.id = u.role_id AND r.slug = 'clerk'
     WHERE pa.status = 'active'
     ORDER BY pa.project_id ASC"
);
$projectIds = array_values(array_unique(array_map(static fn ($r) => (int)$r['project_id'], $clerkProjects)));
if ($projectIds === []) {
    $projectIds = array_map(static fn ($r) => (int)$r['id'], Database::fetchAll('SELECT id FROM projects ORDER BY id ASC LIMIT 8'));
}

// Assign each intern to 1–2 projects round-robin (schema: role, not role_on_project)
$actor = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug IN ('superadmin','manager') AND u.status = 'active' ORDER BY FIELD(r.slug,'superadmin','manager'), u.id ASC LIMIT 1"
);
$actorId = (int)($actor['id'] ?? 1);
$assigned = 0;
foreach ($created as $i => $uid) {
    $p1 = $projectIds[$i % count($projectIds)];
    $p2 = $projectIds[($i + 3) % count($projectIds)];
    foreach (array_unique([$p1, $p2]) as $pid) {
        $exists = Database::fetch(
            'SELECT id, status FROM project_assignments WHERE user_id = ? AND project_id = ? LIMIT 1',
            [$uid, $pid]
        );
        if ($exists) {
            Database::query(
                'UPDATE project_assignments
                 SET role = ?, assignment_type = ?, scope = ?, status = ?, revoked_by = NULL, revoked_at = NULL, updated_by = ?, updated_at = NOW()
                 WHERE id = ?',
                ['intern', 'reporting', 'reporting', 'active', $actorId, (int)$exists['id']]
            );
        } else {
            Database::query(
                'INSERT INTO project_assignments
                    (project_id, user_id, role, assignment_type, scope, status, start_date, is_primary, notes, assigned_by, assigned_at, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW(), ?)',
                [
                    $pid,
                    $uid,
                    'intern',
                    'reporting',
                    'reporting',
                    'active',
                    date('Y-m-d'),
                    'Phase 14 intern seed',
                    $actorId,
                    $actorId,
                ]
            );
        }
        $assigned++;
    }
}
echo "assignments_upserted={$assigned}\n";

// Ensure geo fences configured for sign-in demos where missing
foreach ($projectIds as $pid) {
    $gf = Database::fetch('SELECT id, status FROM geo_fences WHERE project_id = ? LIMIT 1', [$pid]);
    if (!$gf) {
        Database::query(
            'INSERT INTO geo_fences (project_id, site_name, latitude, longitude, radius_meters, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$pid, 'Site fence ' . $pid, 1.02 + ($pid * 0.001), 35.0 + ($pid * 0.001), 150, 'configured', $actorId]
        );
    } elseif (($gf['status'] ?? '') !== 'configured') {
        Database::query('UPDATE geo_fences SET status = ? WHERE id = ?', ['configured', (int)$gf['id']]);
    }
}

// Rewrite outdated clerk attendance announcements
Database::query(
    "UPDATE announcements SET title = ?, body = ?, updated_at = NOW()
     WHERE title LIKE '%Confirm attendance gateway times%' OR body LIKE '%Open and close attendance gateways%'",
    [
        'Clerks: Confirm site open daily',
        'Attendance times are set by the County Director (07:00–08:30 Mon–Fri). Confirm site open on the Attendance Gateway page. Interns can still sign in when the policy window is open.',
    ]
);

// Seed a few diary/weather rows for empty UAT
$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
$clerkId = (int)($clerk['id'] ?? 0);
if ($clerkId > 0 && $projectIds !== []) {
    $pid = $projectIds[0];
    $d = date('Y-m-d');
    if (!Database::fetch('SELECT id FROM site_diaries WHERE project_id = ? AND diary_date = ? LIMIT 1', [$pid, $d])) {
        Database::query(
            'INSERT INTO site_diaries (project_id, diary_date, report_title, weather_summary, work_done, issues_raised, safety_observations, next_day_plan, recorded_by, status, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$pid, $d, 'Daily site diary', 'Clear morning', 'Foundation and formwork progress on Block A.', 'None material', 'PPE compliance good.', 'Continue ground beams.', $clerkId, 'submitted', $clerkId]
        );
    }
    if (!Database::fetch('SELECT id FROM weather_logs WHERE project_id = ? AND log_date = ? LIMIT 1', [$pid, $d])) {
        Database::query(
            'INSERT INTO weather_logs (project_id, log_date, morning_condition, afternoon_condition, rainfall_mm, temperature_min, temperature_max, working_hours, working_hours_lost, impact_level, remarks, recorded_by, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$pid, $d, 'clear', 'partly-cloudy', 0, 16, 27, 8, 0, 'none', 'Normal working conditions.', $clerkId, $clerkId]
        );
    }
}

$internCount = (int)(Database::fetch(
    "SELECT COUNT(*) AS c FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'intern' AND u.status = 'active'"
)['c'] ?? 0);
echo "active_interns={$internCount}\n";
echo "SEED OK\n";
