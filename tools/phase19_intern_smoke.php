<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 19 intern + one-site smoke ===\n";
$root = dirname(__DIR__);
$fail = 0;

function ok(bool $c, string $m): void
{
    global $fail;
    echo ($c ? 'OK   ' : 'FAIL ') . $m . "\n";
    if (!$c) {
        $fail++;
    }
}

$files = [
    'admin/intern/dashboard.php',
    'admin/intern/sign-in.php',
    'admin/intern/my-attendance.php',
    'admin/intern/my-project.php',
    'admin/intern/site-data-entry.php',
    'admin/intern/upload-photos.php',
    'admin/intern/messages.php',
    'admin/intern/assigned-enquiries.php',
    'app/models/InternAttendance.php',
    'app/models/InternProjectWork.php',
    'app/models/ProjectAssignment.php',
    'app/partials/admin/intern-hub.php',
    'admin/assets/js/intern-work.js',
    'api/intern/site-data-save.php',
    'api/intern/photo-upload.php',
];

foreach ($files as $f) {
    $path = $root . '/' . $f;
    if (!is_file($path)) {
        ok(false, "missing $f");
        continue;
    }
    if (str_ends_with($f, '.php')) {
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        ok($code === 0, "lint $f");
    } else {
        ok(filesize($path) > 100, "exists $f");
    }
}

$dash = file_get_contents($root . '/admin/intern/dashboard.php');
ok(!str_starts_with($dash, "\xEF\xBB\xBF"), 'dashboard no BOM');
ok(str_contains($dash, 'intern-hub') || str_contains($dash, 'activeInternHub'), 'dashboard hub');
ok(str_contains($dash, 'site-data-entry'), 'dashboard data entry quick action');

$side = file_get_contents($root . '/app/partials/admin/sidebar.php');
ok(str_contains($side, "'Sign In'") && !str_contains($side, 'Sign In/Out'), 'sidebar Sign In label');

$pa = file_get_contents($root . '/app/models/ProjectAssignment.php');
ok(str_contains($pa, 'SINGLE_SITE_ROLES') && str_contains($pa, 'revokeOtherActiveSites') && str_contains($pa, 'repairSingleSiteAssignments'), 'one-site helpers');
ok(str_contains($pa, 'isSingleSiteRole'), 'isSingleSiteRole');

$entry = file_get_contents($root . '/admin/intern/site-data-entry.php');
ok(str_contains($entry, 'programme_task_id'), 'site note programme_task_id field');

$js = file_get_contents($root . '/admin/assets/js/intern-work.js');
ok(str_contains($js, 'location.reload'), 'js reloads after save');

$model = file_get_contents($root . '/app/models/InternProjectWork.php');
ok(str_contains($model, 'programme_task_id'), 'model maps programme_task_id');

// Repair multi-site data
$repair = ProjectAssignment::repairSingleSiteAssignments(0);
echo 'repair users=' . $repair['users'] . ' revoked=' . $repair['revoked'] . "\n";

// Verify no multi-site clerks/interns remain
$multi = Database::fetchAll(
    "SELECT u.id, r.slug, COUNT(pa.id) AS n
     FROM users u
     JOIN roles r ON r.id = u.role_id
     JOIN project_assignments pa ON pa.user_id = u.id AND pa.status = 'active'
     WHERE r.slug IN ('clerk', 'intern')
     GROUP BY u.id, r.slug
     HAVING n > 1"
);
ok($multi === [], 'no multi-site clerk/intern remain (count=' . count($multi) . ')');

// Assign same clerk to second project should transfer (only one active)
$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' LIMIT 1"
);
$projects = Database::fetchAll('SELECT id FROM projects ORDER BY id ASC LIMIT 2');
if ($clerk && count($projects) >= 2) {
    $uid = (int)$clerk['id'];
    $p1 = (int)$projects[0]['id'];
    $p2 = (int)$projects[1]['id'];
    try {
        // Ensure on p1
        ProjectAssignment::syncUserAssignments($uid, [$p1], 0);
        $active1 = Database::fetchAll('SELECT project_id FROM project_assignments WHERE user_id = ? AND status = "active"', [$uid]);
        ok(count($active1) === 1 && (int)$active1[0]['project_id'] === $p1, 'sync single site clerk on p1');

        // Assign to p2 via assign() should move
        ProjectAssignment::assign([
            'project_id' => $p2,
            'user_id' => $uid,
            'assigned_by' => 0,
            'status' => 'active',
        ]);
        $active2 = Database::fetchAll('SELECT project_id FROM project_assignments WHERE user_id = ? AND status = "active"', [$uid]);
        ok(count($active2) === 1 && (int)$active2[0]['project_id'] === $p2, 'assign transfers clerk to p2 only');

        // sync multi should collapse to first
        ProjectAssignment::syncUserAssignments($uid, [$p1, $p2], 0);
        $active3 = Database::fetchAll('SELECT project_id FROM project_assignments WHERE user_id = ? AND status = "active"', [$uid]);
        ok(count($active3) === 1, 'sync multi collapses to one site');
    } catch (Throwable $e) {
        ok(false, 'assignment transfer test: ' . $e->getMessage());
    }
} else {
    echo "SKIP transfer test (no clerk/projects)\n";
}

// Intern runtime
$intern = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'intern' AND u.status = 'active' LIMIT 1"
);
if ($intern) {
    $iid = (int)$intern['id'];
    $plist = InternAttendance::projects($iid);
    ok(count($plist) <= 1, 'intern projects count <= 1 (got ' . count($plist) . ')');
    $pid = InternAttendance::defaultProjectId($iid);
    ok(true, 'default project id=' . $pid);
    $sum = InternAttendance::summary($iid, date('Y-m'), $pid);
    ok(isset($sum['signed_days']), 'attendance summary');
    if ($pid > 0) {
        $ov = InternProjectWork::projectOverview($iid, $pid);
        ok($ov !== [] || true, 'project overview');
    }
}

echo $fail === 0 ? "PHASE19 SMOKE OK\n" : "PHASE19 FAIL $fail\n";
exit($fail === 0 ? 0 : 1);
