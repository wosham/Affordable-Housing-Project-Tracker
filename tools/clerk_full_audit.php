<?php
/**
 * Full clerk portal readiness audit (read-only; no data mutations).
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== CLERK FULL AUDIT ===\n";
$root = dirname(__DIR__);
$fail = 0;
$warn = 0;

function fail(string $msg): void
{
    global $fail;
    echo "FAIL  $msg\n";
    $fail++;
}
function ok(string $msg): void
{
    echo "OK    $msg\n";
}
function warn(string $msg): void
{
    global $warn;
    echo "WARN  $msg\n";
    $warn++;
}

// ── 1. Page inventory ─────────────────────────────────────────────
$pages = [
    'admin/clerk/dashboard.php',
    'admin/clerk/attendance-gateway.php',
    'admin/clerk/attendance-live.php',
    'admin/clerk/daily-diary.php',
    'admin/clerk/weather-log.php',
    'admin/clerk/labour-verification.php',
    'admin/clerk/material-delivery-log.php',
    'admin/clerk/equipment-check.php',
    'admin/clerk/quality-tests.php',
    'admin/clerk/inspection-test-plans.php',
    'admin/clerk/hs-incidents.php',
    'admin/clerk/non-conformance.php',
    'admin/clerk/defects.php',
    'admin/clerk/site-meeting-minutes.php',
    'admin/clerk/documents.php',
    'admin/clerk/photos.php',
    'admin/clerk/ipc-verify.php',
    'admin/clerk/assigned-enquiries.php',
    'admin/clerk/messages.php',
];

$apis = [
    'api/clerk/daily-diary-save.php',
    'api/clerk/weather-log-save.php',
    'api/clerk/labour-verification-save.php',
    'api/clerk/material-delivery-save.php',
    'api/clerk/equipment-check-save.php',
    'api/clerk/daily-record-detail.php',
    'api/clerk/quality-test-save.php',
    'api/clerk/itp-save.php',
    'api/clerk/hs-incident-save.php',
    'api/clerk/ncr-save.php',
    'api/clerk/defect-save.php',
    'api/clerk/site-meeting-save.php',
    'api/clerk/document-save.php',
    'api/clerk/photo-save.php',
    'api/clerk/ipc-verify.php',
    'api/clerk/quality-evidence-detail.php',
    'api/attendance/sign-in.php',
    'api/attendance/gateway-open.php',
    'api/attendance/gateway-status.php',
];

$assets = [
    'admin/assets/css/dashboard-clerk.css',
    'admin/assets/css/components/clerk-records.css',
    'admin/assets/css/components/clerk-quality.css',
    'admin/assets/css/components/attendance.css',
    'admin/assets/css/components/messages.css',
    'admin/assets/js/clerk-records.js',
    'admin/assets/js/clerk-quality.js',
    'admin/assets/js/attendance-gateway.js',
    'admin/assets/js/attendance-signin.js',
    'admin/assets/js/messages.js',
];

$models = [
    'app/models/ClerkAttendance.php',
    'app/models/ClerkDailyRecord.php',
    'app/models/ClerkQualityEvidence.php',
    'app/models/AttendancePolicy.php',
    'app/models/MessageThread.php',
];

$partials = [
    'app/partials/admin/clerk-daily-record-page.php',
    'app/partials/admin/clerk-quality-evidence-page.php',
    'app/partials/admin/messages-centre.php',
];

$all = array_merge($pages, $apis, $assets, $models, $partials);

foreach ($all as $rel) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) {
        fail("missing $rel");
        continue;
    }
    if (str_ends_with($rel, '.php')) {
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            fail("lint $rel :: " . implode(' ', $out));
        }
    }
}
ok('inventory: ' . count($pages) . ' pages, ' . count($apis) . ' APIs, assets+models present & lint clean');

// ── 2. CSS gap bugs (compound selectors) ──────────────────────────
$cssChecks = [
    'admin/assets/css/dashboard-clerk.css' => [
        'must' => ['--clerk-section-gap', 'clerk-policy-banner'],
        'must_not' => ['.clerk-dashboard-page .admin-content'],
    ],
    'admin/assets/css/components/clerk-records.css' => [
        'must' => ['--record-section-gap', 'cr-modal', 'clerk-record-hub'],
        'must_not' => ['.clerk-records-page .admin-content'],
    ],
    'admin/assets/css/components/clerk-quality.css' => [
        'must' => ['--quality-section-gap', 'cq-modal', 'clerk-quality-hub'],
        'must_not' => ['.clerk-quality-page .admin-content'],
    ],
    'admin/assets/css/components/messages.css' => [
        'must' => ['--messages-section-gap'],
        'must_not' => [],
    ],
];
foreach ($cssChecks as $file => $rules) {
    $src = file_get_contents($root . '/' . $file);
    foreach ($rules['must'] as $n) {
        if (!str_contains($src, $n)) {
            fail("$file missing $n");
        }
    }
    foreach ($rules['must_not'] as $n) {
        if (str_contains($src, $n)) {
            fail("$file still has broken selector $n");
        }
    }
}
ok('CSS spacing selectors (no broken descendant gaps)');

// ── 3. JS feature markers ─────────────────────────────────────────
$js = file_get_contents($root . '/admin/assets/js/clerk-records.js');
foreach (['AHPTC.toast', 'data-cr-edit-modal', 'data-view-record', 'data-material-picker'] as $n) {
    if (!str_contains($js, $n)) {
        fail("clerk-records.js missing $n");
    }
}
$jsq = file_get_contents($root . '/admin/assets/js/clerk-quality.js');
foreach (['AHPTC.toast', 'data-cq-edit-modal', 'data-view-record', 'data-cq-open-photo'] as $n) {
    if (!str_contains($jsq, $n)) {
        fail("clerk-quality.js missing $n");
    }
}
ok('JS toast + modals + pickers');

// ── 4. Partial feature markers ────────────────────────────────────
$daily = file_get_contents($root . '/app/partials/admin/clerk-daily-record-page.php');
foreach (['data-cr-view-modal', 'data-cr-edit-modal', 'date_from', 'siteHub', 'data-material-picker', 'pagination'] as $n) {
    if (!str_contains($daily, $n)) {
        fail("daily partial missing $n");
    }
}
$qual = file_get_contents($root . '/app/partials/admin/clerk-quality-evidence-page.php');
foreach (['data-cq-view-modal', 'data-cq-edit-modal', 'cq_media', 'pendingIpcs', 'pagination', 'clerk-quality-hub'] as $n) {
    if (!str_contains($qual, $n)) {
        fail("quality partial missing $n");
    }
}
$dash = file_get_contents($root . '/admin/clerk/dashboard.php');
if (str_starts_with($dash, "\xEF\xBB\xBF")) {
    fail('dashboard has BOM');
}
foreach (['clerk-policy-banner', 'is_effectively_open', 'Open Windows'] as $n) {
    if (!str_contains($dash, $n)) {
        fail("dashboard missing $n");
    }
}
ok('partials + dashboard policy parity');

// ── 5. IPC safety ─────────────────────────────────────────────────
$modelQ = file_get_contents($root . '/app/models/ClerkQualityEvidence.php');
if (!str_contains($modelQ, "status !== 'submitted'") && !str_contains($modelQ, "status === 'submitted'")) {
    // check for submitted-only guard
    if (!str_contains($modelQ, "Only submitted IPCs")) {
        fail('IPC verify missing submitted-only guard text');
    }
}
if (!str_contains($modelQ, 'beginTransaction')) {
    fail('IPC verify missing transaction');
}
ok('IPC verify safety markers');

// ── 6. Sidebar coverage ───────────────────────────────────────────
$side = file_get_contents($root . '/app/partials/admin/sidebar.php');
foreach ($pages as $p) {
    $short = str_replace('admin/clerk/', '', $p);
    if (!str_contains($side, $short) && $short !== 'dashboard.php') {
        // dashboard path is dashboard.php under clerk
    }
    if (!str_contains($side, $p) && !str_contains($side, basename($p))) {
        warn("sidebar may not list " . basename($p));
    }
}
// stricter: all sidebar paths exist
if (preg_match_all("/'path'\\s*=>\\s*'admin\\/clerk\\/([^']+)'/", $side, $m)) {
    foreach ($m[1] as $file) {
        if (!is_file($root . '/admin/clerk/' . $file)) {
            fail("sidebar dead link admin/clerk/$file");
        }
    }
    ok('sidebar clerk links resolve (' . count($m[1]) . ')');
}

// ── 7. Runtime: clerk user + models ───────────────────────────────
$clerk = Database::fetch(
    "SELECT u.id, u.email, u.status FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'clerk' AND u.status = 'active' AND u.email = 'lilian.naliaka.ahp@gmail.com'
     LIMIT 1"
);
if (!$clerk) {
    $clerk = Database::fetch(
        "SELECT u.id, u.email, u.status FROM users u
         JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id LIMIT 1"
    );
}
if (!$clerk) {
    fail('no active clerk user');
} else {
    $uid = (int)$clerk['id'];
    ok("clerk user id={$uid} email={$clerk['email']}");

    $pid = ClerkAttendance::defaultProjectId($uid);
    if ($pid <= 0) {
        fail('clerk has no default project');
    } else {
        ok("default project=$pid");
    }

    // Attendance
    try {
        $summary = ClerkAttendance::summary($uid, date('Y-m-d'));
        $gates = ClerkAttendance::gatewayRows($uid, date('Y-m-d'));
        ok('attendance summary projects=' . $summary['assigned_projects'] . ' gateways=' . count($gates));
        if ($gates && !array_key_exists('is_effectively_open', $gates[0])) {
            fail('gateway rows missing is_effectively_open');
        }
    } catch (Throwable $e) {
        fail('attendance runtime: ' . $e->getMessage());
    }

    // Daily records
    foreach (['diary', 'weather', 'labour', 'materials', 'equipment'] as $t) {
        try {
            $c = ClerkDailyRecord::count($t, $uid, $pid, []);
            $list = ClerkDailyRecord::list($t, $uid, $pid, [], 5, 0);
            ok("daily $t count=$c list=" . count($list));
            if ($list) {
                $found = ClerkDailyRecord::find($t, $uid, (int)$list[0]['id']);
                if (!$found) {
                    fail("daily find failed $t");
                }
            }
        } catch (Throwable $e) {
            fail("daily $t: " . $e->getMessage());
        }
    }

    // Quality
    foreach (['quality', 'itp', 'hs', 'ncr', 'defect', 'meeting', 'document', 'photo', 'ipc'] as $t) {
        try {
            $c = ClerkQualityEvidence::count($t, $uid, $pid, []);
            $list = ClerkQualityEvidence::list($t, $uid, $pid, [], 5, 0);
            ok("quality $t count=$c list=" . count($list));
            if ($list && $t !== 'ipc') {
                $found = ClerkQualityEvidence::find($t, $uid, (int)$list[0]['id']);
                if (!$found) {
                    fail("quality find failed $t");
                }
            }
        } catch (Throwable $e) {
            fail("quality $t: " . $e->getMessage());
        }
    }

    // IPC pending only submitted
    try {
        $pending = ClerkQualityEvidence::pendingIpcs($uid, $pid);
        foreach ($pending as $row) {
            if (($row['status'] ?? '') !== 'submitted') {
                fail('pendingIpcs returned non-submitted id=' . $row['id']);
            }
        }
        ok('pendingIpcs count=' . count($pending) . ' (all submitted)');
    } catch (Throwable $e) {
        fail('pendingIpcs: ' . $e->getMessage());
    }

    // Messages
    try {
        $recs = MessageThread::recipientOptions($uid, 'clerk');
        ok('message recipients=' . count($recs));
        if (count($recs) < 1) {
            warn('clerk has zero message recipients');
        }
        $allowed = ['superadmin', 'manager', 'consultant', 'contractor', 'intern'];
        foreach ($recs as $r) {
            $slug = (string)($r['role_slug'] ?? '');
            if ($slug !== 'superadmin' && !in_array($slug, $allowed, true)) {
                // superadmin always ok; finance should not appear
                if ($slug === 'finance') {
                    fail('clerk can message finance (should not)');
                }
            }
        }
    } catch (Throwable $e) {
        fail('messages: ' . $e->getMessage());
    }

    // Password check for Lilian if present
    if (($clerk['email'] ?? '') === 'lilian.naliaka.ahp@gmail.com') {
        $hash = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        $h = (string)($hash['password_hash'] ?? '');
        $p1 = password_verify('Password123!', $h);
        $p2 = password_verify('Intern@2026', $h);
        if ($p1) {
            ok('Lilian password verifies Password123!');
        } elseif ($p2) {
            warn('Lilian still on Intern@2026 (not Password123!) — login works only with that hash');
        } else {
            warn('Lilian password matches neither Password123! nor Intern@2026');
        }
    }
}

// ── 8. Policy ─────────────────────────────────────────────────────
try {
    $policy = AttendancePolicy::settings();
    ok('attendance policy ' . ($policy['open_time'] ?? '?') . '-' . ($policy['close_time'] ?? '?'));
} catch (Throwable $e) {
    fail('policy: ' . $e->getMessage());
}

// ── 9. Assigned enquiries page basic ──────────────────────────────
$enq = file_get_contents($root . '/admin/clerk/assigned-enquiries.php');
if (!str_contains($enq, 'Guard::exactRole') && !str_contains($enq, "exactRole('clerk')")) {
    warn('assigned-enquiries may not lock to clerk role');
} else {
    ok('assigned-enquiries role guard present');
}

// ── 10. External phase smokes if present ──────────────────────────
foreach (['phase14_smoke.php', 'phase15_smoke.php', 'phase16_smoke.php', 'attendance_policy_smoke.php'] as $sm) {
    $path = $root . '/tools/' . $sm;
    if (!is_file($path)) {
        warn("smoke missing tools/$sm");
        continue;
    }
    $out = [];
    $code = 0;
    exec('C:\\xampp\\php\\php.exe ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $tail = implode("\n", array_slice($out, -3));
    if ($code !== 0) {
        fail("$sm exit=$code :: $tail");
    } else {
        ok("$sm passed");
    }
}

echo "\n=== SUMMARY ===\n";
echo "fails=$fail warns=$warn\n";
if ($fail === 0) {
    echo "CLERK AUDIT READY\n";
} else {
    echo "CLERK AUDIT NOT READY\n";
}
exit($fail === 0 ? 0 : 1);
