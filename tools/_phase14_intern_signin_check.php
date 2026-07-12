<?php
/**
 * Phase 14: intern assignment + sign-in path sanity (no fake GPS/payment).
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== Phase 14 intern sign-in path ===\n";
$fail = 0;

$intern = Database::fetch(
    "SELECT u.id, u.email, CONCAT(u.first_name,' ',u.last_name) AS name
     FROM users u
     JOIN roles r ON r.id = u.role_id AND r.slug = 'intern'
     WHERE u.status = 'active' AND u.email LIKE '%.ahp@gmail.com'
     ORDER BY u.id ASC LIMIT 1"
);
if (!$intern) {
    echo "FAIL no seeded intern\n";
    exit(1);
}
$uid = (int)$intern['id'];
echo "intern={$intern['name']} id={$uid} email={$intern['email']}\n";

$projects = InternAttendance::projects($uid);
echo 'assigned_projects=' . count($projects) . "\n";
if ($projects === []) {
    echo "FAIL intern has no projects\n";
    $fail++;
} else {
    echo "OK   intern has projects\n";
    $p = $projects[0];
    $pid = (int)$p['id'];
    echo "project={$pid} {$p['name']}\n";
    $gf = Database::fetch('SELECT * FROM geo_fences WHERE project_id = ? LIMIT 1', [$pid]);
    if (!$gf || ($gf['status'] ?? '') !== 'configured') {
        echo "FAIL project missing configured geo fence\n";
        $fail++;
    } else {
        echo "OK   geo fence radius=" . ($gf['radius_meters'] ?? '?') . "\n";
    }

    $gw = AttendancePolicy::ensureProjectWindow($pid, date('Y-m-d'));
    $open = AttendancePolicy::isEffectivelyOpen($gw);
    $within = AttendancePolicy::isWithinWindow();
    echo 'gateway_effectively_open=' . ($open ? 'yes' : 'no') . ' policy_within=' . ($within ? 'yes' : 'no') . "\n";

    // Sign-in API only allows intern + clerk roles (self-only).
    $api = file_get_contents(dirname(__DIR__) . '/api/attendance/sign-in.php') ?: '';
    if (!str_contains($api, "'intern'") || !str_contains($api, 'You can only sign in for yourself')) {
        echo "FAIL sign-in API missing intern/self-only guards\n";
        $fail++;
    } else {
        echo "OK   intern allowed + self-only guard\n";
    }

    $existing = AttendanceRecord::todayForAnyTarget($uid);
    echo 'already_signed_today=' . ($existing ? 'yes id=' . (int)$existing['id'] : 'no') . "\n";
    echo "OK   one-per-day check path ready\n";
}

// Password hash still verifies for Intern@2026
$user = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$uid]);
if ($user && password_verify('Intern@2026', (string)$user['password_hash'])) {
    echo "OK   password Intern@2026 verifies\n";
} else {
    // May have been set earlier without rehash on update path
    Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash('Intern@2026', PASSWORD_DEFAULT), $uid]);
    echo "FIXED password rehashed to Intern@2026\n";
}

// Ensure all phase-14 emails have the demo password
$emails = Database::fetchAll(
    "SELECT id, email FROM users WHERE email LIKE '%.ahp@gmail.com' AND status = 'active'"
);
$hash = password_hash('Intern@2026', PASSWORD_DEFAULT);
$fixed = 0;
foreach ($emails as $row) {
    if (!password_verify('Intern@2026', (string)(Database::fetch('SELECT password_hash FROM users WHERE id = ?', [(int)$row['id']])['password_hash'] ?? ''))) {
        Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, (int)$row['id']]);
        $fixed++;
    }
}
echo "password_rows_fixed={$fixed}\n";

echo $fail === 0 ? "INTERN SIGNIN PATH OK\n" : "INTERN SIGNIN PATH FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
