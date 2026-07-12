<?php
/**
 * Final contractor portal production audit.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Contractor final audit ===\n";
$root = dirname(__DIR__);
$fail = 0;

$pages = glob($root . '/admin/contractor/*.php') ?: [];
$expected = [
    'assigned-enquiries.php', 'boq.php', 'dashboard.php', 'documents.php', 'eot-request.php',
    'equipment-register.php', 'hs-incidents.php', 'ipc-history.php', 'ipc-submit.php', 'labour-register.php',
    'material-approval-submit.php', 'material-deliveries.php', 'messages.php', 'my-project.php',
    'payment-history.php', 'programme-of-works.php', 'progress-update.php', 'rfis.php',
    'shop-drawing-submit.php', 'subcontractors.php', 'variation-request.php',
];

$found = array_map('basename', $pages);
sort($found);
sort($expected);
foreach ($expected as $page) {
    if (!in_array($page, $found, true)) {
        echo "FAIL missing page {$page}\n";
        $fail++;
    }
}
foreach ($found as $page) {
    if (!in_array($page, $expected, true)) {
        echo "WARN extra page {$page}\n";
    }
}

// Sidebar paths
$sidebar = file_get_contents($root . '/app/partials/admin/sidebar.php');
foreach ($expected as $page) {
    if (!str_contains($sidebar, 'admin/contractor/' . $page)) {
        echo "FAIL sidebar missing {$page}\n";
        $fail++;
    }
}
echo 'sidebar_contractor_links_ok=' . ($fail === 0 ? 'yes' : 'check') . "\n";

// Guard + lint
foreach ($pages as $path) {
    $src = file_get_contents($path);
    $base = basename($path);
    if (!str_contains($src, "Guard::exactRole('contractor')")) {
        echo "FAIL no guard {$base}\n";
        $fail++;
    }
    $out = [];
    $code = 0;
    exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    if ($code !== 0) {
        echo "FAIL lint {$base}\n";
        $fail++;
    }
}
echo "pages_linted=" . count($pages) . "\n";

// Critical JS patterns
$jsChecks = [
    'admin/assets/js/contractor-site-records.js' => ['AHPTC.request', 'data-csr-edit-modal', 'data-csr-detail-modal'],
    'admin/assets/js/contractor-submissions.js' => ['AHPTC.request', 'data-submission-open', 'data-submission-detail-modal'],
    'admin/assets/js/contractor-progress.js' => ['AHPTC.request'],
    'admin/assets/js/contractor-ipc-history.js' => ['AHPTC.request', 'data-ipc-open'],
    'admin/assets/js/ipc-form.js' => ['AHPTC.request'],
];
foreach ($jsChecks as $file => $needles) {
    $path = $root . '/' . $file;
    if (!is_file($path)) {
        echo "FAIL missing {$file}\n";
        $fail++;
        continue;
    }
    $src = file_get_contents($path);
    foreach ($needles as $n) {
        if (!str_contains($src, $n)) {
            echo "FAIL {$file} missing {$n}\n";
            $fail++;
        }
    }
    if (str_contains($src, 'scrollIntoView') && str_contains($file, 'site-records')) {
        echo "FAIL site-records still scrolls on edit\n";
        $fail++;
    }
}
echo "js_checks_ok\n";

// Media CSRF
$upload = file_get_contents($root . '/api/media/upload.php');
foreach (['contractor_progress', 'contractor_submission', 'contractor_site_records'] as $form) {
    if (!str_contains($upload, $form)) {
        echo "FAIL media upload missing csrf form {$form}\n";
        $fail++;
    }
}

// Model smoke for Mercy (16) and David (11)
foreach ([11, 16] as $uid) {
    $role = 'contractor';
    $projects = ContractorProject::projectIds($uid, $role);
    echo "user_{$uid}_projects=" . count($projects) . "\n";
    if ($projects === []) {
        echo "WARN user {$uid} has no projects\n";
        continue;
    }
    $pid = $projects[0];

    // Dashboard
    if (class_exists('ContractorDashboard')) {
        try {
            $dash = method_exists('ContractorDashboard', 'summary')
                ? ContractorDashboard::summary($uid, $role)
                : null;
            echo "user_{$uid}_dashboard=" . ($dash !== null ? 'ok' : 'n/a') . "\n";
        } catch (Throwable $e) {
            echo "FAIL dashboard user {$uid}: {$e->getMessage()}\n";
            $fail++;
        }
    }

    // IPC
    try {
        $stats = ContractorIPC::stats($uid, $role);
        echo "user_{$uid}_ipc_total=" . (int)($stats['total'] ?? 0) . "\n";
    } catch (Throwable $e) {
        echo "FAIL ipc stats {$uid}: {$e->getMessage()}\n";
        $fail++;
    }

    // Submissions
    foreach (array_keys(ContractorSubmission::types()) as $type) {
        try {
            $s = ContractorSubmission::stats($type, $uid, $role, ['project_id' => $pid]);
            $c = ContractorSubmission::count($type, $uid, $role, ['project_id' => $pid]);
            echo "user_{$uid}_{$type}_stats={$s['total']}/count={$c}\n";
        } catch (Throwable $e) {
            echo "FAIL submission {$type} user {$uid}: {$e->getMessage()}\n";
            $fail++;
        }
    }

    // Site records
    foreach (array_keys(ContractorSiteRecord::types()) as $type) {
        try {
            $s = ContractorSiteRecord::stats($type, $pid, $uid, $role);
            $c = ContractorSiteRecord::count($type, $pid, $uid, $role, []);
            echo "user_{$uid}_site_{$type}={$s['total']}/count={$c}\n";
        } catch (Throwable $e) {
            echo "FAIL site {$type} user {$uid}: {$e->getMessage()}\n";
            $fail++;
        }
    }
}

// Demo hygiene
$p9Docs = (int)(Database::fetch("SELECT COUNT(*) AS c FROM documents WHERE original_name LIKE '%P9%' OR filename LIKE '%p9-demo%'")['c'] ?? 0);
$p9Eot = (int)(Database::fetch("SELECT COUNT(*) AS c FROM eot_requests WHERE reason LIKE '%P9 demo%'")['c'] ?? 0);
$p9Vo = (int)(Database::fetch("SELECT COUNT(*) AS c FROM variations WHERE description LIKE '%P9 demo%'")['c'] ?? 0);
echo "p9_docs={$p9Docs} p9_eot={$p9Eot} p9_vo={$p9Vo}\n";
if ($p9Docs + $p9Eot + $p9Vo > 0) {
    echo "FAIL p9 demo strings remain\n";
    $fail++;
}

// Empty critical tables?
foreach (['equipment_register', 'labour_register', 'material_deliveries'] as $t) {
    $c = (int)(Database::fetch("SELECT COUNT(*) AS c FROM `{$t}`")['c'] ?? 0);
    echo "{$t}={$c}\n";
    if ($c <= 0) {
        echo "FAIL {$t} empty\n";
        $fail++;
    }
}

// API endpoints exist
$apis = [
    'api/contractor/document-save.php',
    'api/contractor/equipment-save.php',
    'api/contractor/hs-incident-save.php',
    'api/contractor/labour-save.php',
    'api/contractor/material-delivery-save.php',
    'api/contractor/subcontractor-save.php',
    'api/contractor/site-record-detail.php',
    'api/contractor/rfi-submit.php',
    'api/contractor/eot-submit.php',
    'api/contractor/variation-submit.php',
    'api/contractor/shop-drawing-submit.php',
    'api/contractor/material-approval-submit.php',
    'api/contractor/submission-detail.php',
    'api/contractor/ipc-detail.php',
    'api/ipcs/submit.php',
];
foreach ($apis as $api) {
    if (!is_file($root . '/' . $api)) {
        echo "FAIL missing api {$api}\n";
        $fail++;
    }
}

// Payments still out of CSR types
if (isset(ContractorSiteRecord::types()['payments'])) {
    echo "FAIL payments back in CSR types\n";
    $fail++;
}

echo $fail === 0 ? "CONTRACTOR AUDIT OK\n" : "CONTRACTOR AUDIT FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
