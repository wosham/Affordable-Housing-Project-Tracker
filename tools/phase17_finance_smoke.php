<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';
SystemConfig::clear();

echo "=== Phase 17 finance smoke ===\n";
$root = dirname(__DIR__);
$fail = 0;

$files = [
    'admin/finance/dashboard.php',
    'admin/finance/approved-ipcs.php',
    'admin/finance/process-payment.php',
    'admin/finance/budget-tracker.php',
    'admin/finance/liquidated-damages.php',
    'admin/finance/retention.php',
    'app/models/FinancePayment.php',
    'app/models/FinanceBudget.php',
    'api/finance/process-payment.php',
    'api/finance/payment-detail.php',
    'admin/assets/js/finance-payments.js',
    'admin/assets/css/components/finance-payments.css',
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
        $ok = str_contains($src, 'AHPTC.toast') && str_contains($src, 'confirm_review') && str_contains($src, 'payment history');
        // case-insensitive for history
        $ok = str_contains($src, 'AHPTC.toast') && str_contains($src, 'confirm_review') && str_contains($src, 'Payment history');
        echo ($ok ? 'OK  ' : 'FAIL') . " js $f\n";
        if (!$ok) {
            $fail++;
        }
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'finance-stat--link') && str_contains($src, 'finance-hub') && str_contains($src, 'finance-split') && str_contains($src, '@media');
        echo ($ok ? 'OK  ' : 'FAIL') . " css $f\n";
        if (!$ok) {
            $fail++;
        }
    }
}

$dash = file_get_contents($root . '/admin/finance/dashboard.php');
if (str_starts_with($dash, "\xEF\xBB\xBF")) {
    echo "FAIL dashboard still has BOM\n";
    $fail++;
} else {
    echo "OK   dashboard no BOM\n";
}
foreach (['finance-stat--link', 'finance-hub', 'approved_unpaid', 'finance-split', 'finance-card--full'] as $n) {
    if (!str_contains($dash, $n)) {
        echo "FAIL dashboard missing $n\n";
        $fail++;
    }
}
echo "OK   dashboard clickable stats + hub + full budget layout\n";

$model = file_get_contents($root . '/app/models/FinancePayment.php');
foreach (['confirm_review', 'contractor_id', 'SELECT id FROM retention WHERE ipc_id', 'approved_unpaid'] as $n) {
    if (!str_contains($model, $n)) {
        echo "FAIL model missing $n\n";
        $fail++;
    }
}
if (str_contains($model, "pushRole('contractor'")) {
    echo "FAIL model still broadcasts to all contractors\n";
    $fail++;
} else {
    echo "OK   model payment safety\n";
}

// Runtime
try {
    $stats = FinancePayment::dashboard();
    $list = FinancePayment::approvedList([], 5, 0);
    $payables = FinancePayment::payableOptions(10);
    echo 'approved=' . $stats['approved_count'] . ' payables=' . count($payables) . ' list=' . count($list) . "\n";
    if ($list) {
        $detail = FinancePayment::paymentDetail((int)$list[0]['id']);
        echo $detail ? "OK   paymentDetail\n" : "FAIL paymentDetail\n";
        if (!$detail) {
            $fail++;
        }
    }
    // Guard: process without confirm_review should fail
    try {
        FinancePayment::process(1, [
            'ipc_id' => 0,
            'confirm_payment' => '1',
            'reference_no' => 'TEST',
            'amount' => 1,
        ]);
        echo "FAIL process accepted incomplete payload\n";
        $fail++;
    } catch (RuntimeException $e) {
        echo "OK   process rejects incomplete payload (" . $e->getMessage() . ")\n";
    }
    $budget = FinancePayment::budgetRows(3);
    if ($budget && !array_key_exists('approved_unpaid', $budget[0])) {
        echo "FAIL budgetRows missing approved_unpaid\n";
        $fail++;
    } else {
        echo "OK   budgetRows aligned formula\n";
    }
} catch (Throwable $e) {
    echo 'FAIL runtime ' . $e->getMessage() . "\n";
    $fail++;
}

$ret = file_get_contents($root . '/admin/finance/retention.php');
$ld = file_get_contents($root . '/admin/finance/liquidated-damages.php');
if (!str_contains($ret, 'pagination') || !str_contains($ld, 'pagination')) {
    echo "FAIL retention/LD missing pagination UI\n";
    $fail++;
} else {
    echo "OK   retention + LD pagination\n";
}

echo $fail === 0 ? "PHASE17 SMOKE OK\n" : "PHASE17 SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
