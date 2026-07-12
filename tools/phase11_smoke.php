<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 11 smoke ===\n";
$files = [
    'admin/contractor/ipc-submit.php',
    'admin/contractor/ipc-history.php',
    'admin/contractor/payment-history.php',
    'api/ipcs/submit.php',
    'api/contractor/ipc-detail.php',
    'app/models/ContractorIPC.php',
    'admin/assets/js/ipc-form.js',
    'admin/assets/js/contractor-ipc-history.js',
];
foreach ($files as $f) {
    $path = dirname(__DIR__) . '/' . $f;
    if (!is_file($path)) {
        echo "FAIL missing $f\n";
        continue;
    }
    if (str_ends_with($f, '.php')) {
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        echo ($code === 0 ? 'OK  ' : 'FAIL') . " $f\n";
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'AHPTC.request') || str_contains($src, 'data-ipc-open');
        echo ($ok ? 'OK  ' : 'FAIL') . " $f\n";
    }
}

$uid = 11;
$role = 'contractor';
$stats = ContractorIPC::stats($uid, $role);
echo "david_ipcs total={$stats['total']} submitted={$stats['submitted']} clerk={$stats['clerk_endorsed']} certified={$stats['certified']} endorsed={$stats['endorsed']} approved={$stats['approved']} paid={$stats['paid']} rejected={$stats['rejected']}\n";

$pay = ContractorIPC::paymentSummary($uid, $role, []);
echo "payments total={$pay['total']} paid_count={$pay['paid_count']} paid_value={$pay['paid_value']} unpaid={$pay['unpaid_count']} retention={$pay['retention']}\n";
$rows = ContractorIPC::paymentRows($uid, $role, [], 10, 0);
echo 'payment_rows=' . count($rows) . "\n";

$ipc = Database::fetch('SELECT id FROM ipcs WHERE contractor_id = ? ORDER BY id DESC LIMIT 1', [$uid]);
if ($ipc) {
    $detail = ContractorIPC::claimDetail((int)$ipc['id'], $uid, $role);
    echo 'detail_ok=' . ($detail ? 'yes' : 'no') . ' lines=' . count($detail['lines'] ?? []) . "\n";
}

$js = file_get_contents(dirname(__DIR__) . '/admin/assets/js/ipc-form.js');
echo 'ipc-form AHPTC=' . (str_contains($js, 'AHPTC.request') ? 'yes' : 'no') . "\n";
echo 'ipc-form blocks exceed=' . (str_contains($js, 'warningCount') ? 'yes' : 'no') . "\n";

$notes = Database::fetch("SELECT COUNT(*) AS c FROM payments WHERE notes LIKE '%mock%' OR notes LIKE '%Phase 2%'")['c'] ?? 0;
echo "mock_payment_notes={$notes}\n";
echo "SMOKE OK\n";
