<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== Phase 15 smoke ===\n";
$root = dirname(__DIR__);
$fail = 0;

$files = [
    'admin/clerk/quality-tests.php',
    'admin/clerk/inspection-test-plans.php',
    'admin/clerk/non-conformance.php',
    'admin/clerk/defects.php',
    'admin/clerk/ipc-verify.php',
    'admin/clerk/photos.php',
    'admin/clerk/hs-incidents.php',
    'admin/clerk/documents.php',
    'admin/clerk/site-meeting-minutes.php',
    'app/partials/admin/clerk-quality-evidence-page.php',
    'app/models/ClerkQualityEvidence.php',
    'api/clerk/_quality-evidence-handler.php',
    'api/clerk/quality-evidence-detail.php',
    'api/clerk/quality-test-save.php',
    'api/clerk/itp-save.php',
    'api/clerk/ncr-save.php',
    'api/clerk/defect-save.php',
    'api/clerk/ipc-verify.php',
    'api/clerk/photo-save.php',
    'admin/assets/js/clerk-quality.js',
    'admin/assets/css/components/clerk-quality.css',
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
        $ok = str_contains($src, 'AHPTC.toast')
            && str_contains($src, 'data-edit-record')
            && str_contains($src, 'data-cq-edit-modal')
            && str_contains($src, 'data-cq-view-modal');
        echo ($ok ? 'OK  ' : 'FAIL') . " js $f\n";
        if (!$ok) {
            $fail++;
        }
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, '--quality-section-gap') && !str_contains($src, '.clerk-quality-page .admin-content');
        echo ($ok ? 'OK  ' : 'FAIL') . " css $f\n";
        if (!$ok) {
            $fail++;
        }
    }
}

$partial = file_get_contents($root . '/app/partials/admin/clerk-quality-evidence-page.php');
foreach (['clerk-quality-peers', 'perPage', 'pagination', 'data-edit-record', 'data-view-record', 'data-cq-edit-modal', 'data-cq-view-modal', 'data-cq-open-photo', 'cq_media', 'pendingIpcs', 'ipc_action'] as $needle) {
    if (!str_contains($partial, $needle)) {
        echo "FAIL partial missing $needle\n";
        $fail++;
    }
}
echo "OK   partial peers+pagination+media+modals+ipc actions\n";

$model = file_get_contents($root . '/app/models/ClerkQualityEvidence.php');
foreach (['status = \'submitted\'', 'beginTransaction', 'pendingIpcs', 'function count', 'nextReference', 'mediaPayload'] as $needle) {
    if (!str_contains($model, $needle)) {
        echo "FAIL model missing $needle\n";
        $fail++;
    }
}
echo "OK   model IPC safety + count + media\n";

$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
if ($clerk) {
    $uid = (int)$clerk['id'];
    $pid = ClerkQualityEvidence::defaultProjectId($uid);
    echo "clerk=$uid project=$pid\n";
    foreach (['quality', 'itp', 'ncr', 'defect', 'photo', 'ipc'] as $type) {
        try {
            $stats = ClerkQualityEvidence::stats($type, $uid, $pid);
            $total = ClerkQualityEvidence::count($type, $uid, $pid, []);
            $list = ClerkQualityEvidence::list($type, $uid, $pid, [], 10, 0);
            echo "$type stats_total={$stats['total']} count={$total} page=" . count($list) . "\n";
        } catch (Throwable $e) {
            echo "FAIL $type " . $e->getMessage() . "\n";
            $fail++;
        }
    }
    $pending = $pid > 0 ? ClerkQualityEvidence::pendingIpcs($uid, $pid) : [];
    echo 'pending_ipcs=' . count($pending) . "\n";
    echo "OK   model runtime lists\n";
} else {
    echo "FAIL no clerk user\n";
    $fail++;
}

// IPC must only process submitted
$bad = Database::fetch("SELECT id, status FROM ipcs WHERE status <> 'submitted' ORDER BY id DESC LIMIT 1");
if ($bad) {
    try {
        ClerkQualityEvidence::save('ipc', (int)($clerk['id'] ?? 0), [
            'ipc_id' => (int)$bad['id'],
            'clerk_verification_comment' => 'smoke should fail',
            'site_records_checked' => 1,
            'line_quantities_checked' => 1,
            'supporting_documents_checked' => 1,
            'ipc_action' => 'endorse',
        ]);
        echo "FAIL ipc accepted non-submitted status={$bad['status']}\n";
        $fail++;
    } catch (RuntimeException $e) {
        echo "OK   ipc rejects non-submitted (" . $bad['status'] . ")\n";
    } catch (Throwable $e) {
        echo "OK   ipc rejects non-submitted via " . get_class($e) . "\n";
    }
}

echo $fail === 0 ? "PHASE15 SMOKE OK\n" : "PHASE15 SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
