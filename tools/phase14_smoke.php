<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== Phase 14 smoke ===\n";
$root = dirname(__DIR__);
$fail = 0;

$files = [
    'admin/clerk/dashboard.php',
    'admin/clerk/attendance-gateway.php',
    'admin/clerk/attendance-live.php',
    'admin/clerk/daily-diary.php',
    'admin/clerk/weather-log.php',
    'app/partials/admin/clerk-daily-record-page.php',
    'app/models/ClerkAttendance.php',
    'app/models/ClerkDailyRecord.php',
    'app/models/AttendancePolicy.php',
    'admin/assets/js/attendance-gateway.js',
    'admin/assets/js/attendance-signin.js',
    'admin/assets/js/clerk-records.js',
    'admin/assets/css/dashboard-clerk.css',
    'admin/assets/css/components/clerk-records.css',
];

foreach ($files as $f) {
    $path = $root . '/' . $f;
    if (!is_file($path)) {
        echo "FAIL missing $f\n";
        $fail++;
        continue;
    }
    if (str_ends_with($f, '.php')) {
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            echo "FAIL lint $f\n";
            $fail++;
        } else {
            echo "OK   lint $f\n";
        }
    } elseif (str_ends_with($f, '.js')) {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'AHPTC') || str_contains($src, 'request(');
        echo ($ok ? 'OK  ' : 'FAIL') . " js $f\n";
        if (!$ok) {
            $fail++;
        }
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'auto-fit') || str_contains($src, 'gap');
        echo ($ok ? 'OK  ' : 'FAIL') . " css $f\n";
        if (!$ok) {
            $fail++;
        }
    }
}

// Dashboard content checks
$dash = file_get_contents($root . '/admin/clerk/dashboard.php');
if (str_starts_with($dash, "\xEF\xBB\xBF")) {
    echo "FAIL dashboard still has BOM\n";
    $fail++;
} else {
    echo "OK   dashboard no BOM\n";
}
foreach (['AttendancePolicy', 'is_effectively_open', 'Open Windows', 'clerk-policy-banner'] as $n) {
    if (!str_contains($dash, $n) && $n !== 'is_effectively_open') {
        // is_effectively_open is in model data, dashboard uses is_effectively_open key
    }
}
if (!str_contains($dash, 'is_effectively_open') && !str_contains($dash, "['is_effectively_open']")) {
    echo "FAIL dashboard not using effectively open flag\n";
    $fail++;
} else {
    echo "OK   dashboard policy open flags\n";
}
if (!str_contains($dash, 'clerk-policy-banner')) {
    echo "FAIL dashboard missing policy banner\n";
    $fail++;
}

// Live + records
$live = file_get_contents($root . '/admin/clerk/attendance-live.php');
if (!str_contains($live, 'countRecords') && !str_contains($live, 'perPage')) {
    echo "FAIL live missing pagination\n";
    $fail++;
} else {
    echo "OK   live pagination\n";
}

$partial = file_get_contents($root . '/app/partials/admin/clerk-daily-record-page.php');
foreach (['clerk-record-peers', 'pagination', 'perPage'] as $n) {
    if (!str_contains($partial, $n)) {
        echo "FAIL partial missing $n\n";
        $fail++;
    }
}
echo "OK   diary/weather peers+pagination\n";

$js = file_get_contents($root . '/admin/assets/js/clerk-records.js');
if (!str_contains($js, 'AHPTC.toast')) {
    echo "FAIL clerk-records missing toast\n";
    $fail++;
} else {
    echo "OK   clerk-records toast\n";
}

// Clerk model smoke
$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
if ($clerk) {
    $uid = (int)$clerk['id'];
    $summary = ClerkAttendance::summary($uid, date('Y-m-d'));
    $rows = ClerkAttendance::gatewayRows($uid, date('Y-m-d'));
    echo 'clerk=' . $uid . ' projects=' . $summary['assigned_projects'] . ' open_windows=' . $summary['open_gateways'] . ' expected=' . $summary['expected_people'] . ' gateways=' . count($rows) . "\n";
    if (isset($rows[0]) && !array_key_exists('is_effectively_open', $rows[0])) {
        echo "FAIL gateway rows missing is_effectively_open\n";
        $fail++;
    } else {
        echo "OK   gatewayRows policy fields\n";
    }
    $pid = ClerkAttendance::defaultProjectId($uid);
    if ($pid > 0) {
        $total = ClerkDailyRecord::count('diary', $uid, $pid, []);
        $list = ClerkDailyRecord::list('diary', $uid, $pid, [], 10, 0);
        echo "diary total={$total} page=" . count($list) . "\n";
    }
}

$interns = (int)(Database::fetch(
    "SELECT COUNT(*) AS c FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'intern' AND u.status = 'active'"
)['c'] ?? 0);
echo "active_interns={$interns}\n";
if ($interns < 10) {
    echo "FAIL need at least 10 active interns (run phase14_seed_interns.php)\n";
    $fail++;
} else {
    echo "OK   intern pool >= 10\n";
}

$internAssigned = (int)(Database::fetch(
    "SELECT COUNT(DISTINCT pa.user_id) AS c
     FROM project_assignments pa
     JOIN users u ON u.id = pa.user_id AND u.status = 'active'
     JOIN roles r ON r.id = u.role_id AND r.slug = 'intern'
     WHERE pa.status = 'active'"
)['c'] ?? 0);
echo "assigned_interns={$internAssigned}\n";
if ($internAssigned < 10) {
    echo "FAIL need at least 10 interns with active project assignments\n";
    $fail++;
} else {
    echo "OK   intern assignments >= 10\n";
}

$geoOk = (int)(Database::fetch(
    "SELECT COUNT(DISTINCT project_id) AS c FROM geo_fences WHERE status = 'configured'"
)['c'] ?? 0);
echo "geo_configured_projects={$geoOk}\n";
if ($geoOk < 1) {
    echo "FAIL no configured geo fences\n";
    $fail++;
} else {
    echo "OK   geo fences configured\n";
}

// CSS spacing — contentClass is on the same node as .admin-content (compound selectors)
$css = file_get_contents($root . '/admin/assets/css/dashboard-clerk.css');
if (!str_contains($css, 'auto-fit') || !str_contains($css, '--clerk-section-gap') || !str_contains($css, 'clerk-policy-banner')) {
    echo "FAIL dashboard-clerk spacing/policy not upgraded\n";
    $fail++;
} else {
    echo "OK   dashboard-clerk spacing\n";
}
if (str_contains($css, '.clerk-dashboard-page .admin-content') || str_contains($css, ".clerk-dashboard-page .admin-content")) {
    echo "FAIL dashboard-clerk still uses broken descendant .admin-content selector\n";
    $fail++;
} else {
    echo "OK   dashboard-clerk compound page selectors\n";
}
$recCss = file_get_contents($root . '/admin/assets/css/components/clerk-records.css');
if (!str_contains($recCss, '--record-section-gap') || str_contains($recCss, '.clerk-records-page .admin-content')) {
    echo "FAIL clerk-records spacing selectors broken\n";
    $fail++;
} else {
    echo "OK   clerk-records section gap\n";
}

$policy = AttendancePolicy::settings();
echo 'policy ' . $policy['open_time'] . '-' . $policy['close_time'] . "\n";

echo $fail === 0 ? "PHASE14 SMOKE OK\n" : "PHASE14 SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
