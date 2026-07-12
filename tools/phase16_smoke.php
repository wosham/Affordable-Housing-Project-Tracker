<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== Phase 16 smoke ===\n";
$root = dirname(__DIR__);
$fail = 0;

$files = [
    'admin/clerk/documents.php',
    'admin/clerk/equipment-check.php',
    'admin/clerk/hs-incidents.php',
    'admin/clerk/labour-verification.php',
    'admin/clerk/material-delivery-log.php',
    'admin/clerk/site-meeting-minutes.php',
    'admin/clerk/messages.php',
    'app/partials/admin/clerk-daily-record-page.php',
    'app/partials/admin/clerk-quality-evidence-page.php',
    'app/partials/admin/messages-centre.php',
    'app/models/ClerkDailyRecord.php',
    'api/clerk/daily-record-detail.php',
    'api/clerk/_daily-record-handler.php',
    'admin/assets/js/clerk-records.js',
    'admin/assets/css/components/clerk-records.css',
    'admin/assets/css/components/messages.css',
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
        $ok = str_contains($src, 'AHPTC.toast') && str_contains($src, 'data-cr-edit-modal') && str_contains($src, 'data-view-record');
        echo ($ok ? 'OK  ' : 'FAIL') . " js $f\n";
        if (!$ok) {
            $fail++;
        }
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'cr-modal') || str_contains($src, 'messages-section-gap') || str_contains($src, 'clerk-record-hub');
        echo ($ok ? 'OK  ' : 'FAIL') . " css $f\n";
        if (!$ok) {
            $fail++;
        }
    }
}

$partial = file_get_contents($root . '/app/partials/admin/clerk-daily-record-page.php');
foreach (['data-cr-view-modal', 'data-cr-edit-modal', 'data-material-picker', 'data-equipment-picker', 'date_from', 'siteHub', 'data-view-record'] as $n) {
    if (!str_contains($partial, $n)) {
        echo "FAIL daily partial missing $n\n";
        $fail++;
    }
}
echo "OK   daily partial modals+pickers+filters\n";

$model = file_get_contents($root . '/app/models/ClerkDailyRecord.php');
foreach (['function find', 'materialOptions', 'equipmentOptions', 'siteHubPeers'] as $n) {
    if (!str_contains($model, $n)) {
        echo "FAIL model missing $n\n";
        $fail++;
    }
}
echo "OK   model helpers\n";

$msg = file_get_contents($root . '/admin/clerk/messages.php');
if (!str_contains($msg, 'Clerk of Works')) {
    echo "FAIL messages breadcrumb not aligned\n";
    $fail++;
} else {
    echo "OK   messages breadcrumb\n";
}

$clerk = Database::fetch(
    "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'clerk' AND u.status = 'active' ORDER BY u.id ASC LIMIT 1"
);
if ($clerk) {
    $uid = (int)$clerk['id'];
    $pid = ClerkDailyRecord::defaultProjectId($uid);
    echo "clerk=$uid project=$pid\n";
    foreach (['labour', 'materials', 'equipment', 'diary', 'weather'] as $t) {
        try {
            $c = ClerkDailyRecord::count($t, $uid, $pid, []);
            $list = ClerkDailyRecord::list($t, $uid, $pid, [], 5, 0);
            echo "$t count=$c page=" . count($list) . "\n";
        } catch (Throwable $e) {
            echo "FAIL $t " . $e->getMessage() . "\n";
            $fail++;
        }
    }
    $mats = ClerkDailyRecord::materialOptions($uid, $pid);
    $eq = ClerkDailyRecord::equipmentOptions($uid, $pid);
    echo 'material_options=' . count($mats) . ' equipment_options=' . count($eq) . "\n";
    $rec = MessageThread::recipientOptions($uid, 'clerk');
    echo 'message_recipients=' . count($rec) . "\n";
    if (count($rec) < 1) {
        echo "FAIL clerk has no message recipients\n";
        $fail++;
    } else {
        echo "OK   clerk messaging recipients\n";
    }
    if ($list = ClerkDailyRecord::list('labour', $uid, $pid, [], 1, 0)) {
        $found = ClerkDailyRecord::find('labour', $uid, (int)$list[0]['id']);
        echo $found ? "OK   find labour\n" : "FAIL find labour\n";
        if (!$found) {
            $fail++;
        }
    }
} else {
    echo "FAIL no clerk\n";
    $fail++;
}

echo $fail === 0 ? "PHASE16 SMOKE OK\n" : "PHASE16 SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
