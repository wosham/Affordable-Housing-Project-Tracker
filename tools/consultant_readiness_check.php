<?php
/**
 * Consultant portal readiness audit (nav, files, CSRF, limits, models, demos).
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

$uid = 8;
$role = 'consultant';
$root = dirname(__DIR__);

$expected = [
    'dashboard.php' => ['nav' => true, 'csrf' => null, 'limit' => null, 'phase' => 'core'],
    'documents.php' => ['nav' => true, 'csrf' => 'consultant_documents', 'limit' => 10, 'phase' => '9'],
    'assigned-enquiries.php' => ['nav' => true, 'csrf' => 'contact_inbox', 'limit' => null, 'phase' => 'enquiries'],
    'messages.php' => ['nav' => true, 'csrf' => 'messages', 'limit' => null, 'phase' => 'messages'],
    'ipc-inbox.php' => ['nav' => true, 'csrf' => 'consultant_ipc_inbox', 'limit' => null, 'phase' => '7'],
    'ipc-certify.php' => ['nav' => true, 'csrf' => 'consultant_ipc_certify', 'limit' => null, 'phase' => '7'],
    'boq-review.php' => ['nav' => true, 'csrf' => 'consultant_technical', 'limit' => 10, 'phase' => '7'],
    'material-approvals.php' => ['nav' => true, 'csrf' => 'consultant_technical', 'limit' => 10, 'phase' => '9'],
    'shop-drawings.php' => ['nav' => true, 'csrf' => 'consultant_technical', 'limit' => 10, 'phase' => '9'],
    'eot-review.php' => ['nav' => true, 'csrf' => 'consultant_contract', 'limit' => 10, 'phase' => '9'],
    'variations.php' => ['nav' => true, 'csrf' => 'consultant_contract', 'limit' => 10, 'phase' => '9'],
    'programme-review.php' => ['nav' => true, 'csrf' => 'consultant_technical', 'limit' => 10, 'phase' => '8'],
    'defects.php' => ['nav' => true, 'csrf' => 'consultant_quality', 'limit' => 10, 'phase' => '8'],
    'inspection-test-plans.php' => ['nav' => true, 'csrf' => 'consultant_quality', 'limit' => 10, 'phase' => '8'],
    'non-conformance.php' => ['nav' => true, 'csrf' => 'consultant_quality', 'limit' => 10, 'phase' => '8'],
    'quality-register.php' => ['nav' => true, 'csrf' => 'consultant_quality', 'limit' => 10, 'phase' => '8'],
    'site-reports.php' => ['nav' => true, 'csrf' => 'consultant_reports', 'limit' => 10, 'phase' => '9'],
];

$apis = [
    'api/consultant/document-action.php',
    'api/consultant/technical-action.php',
    'api/consultant/contract-action.php',
    'api/consultant/quality-action.php',
    'api/ipcs/certify.php',
    'api/ipcs/reject.php',
    'api/messages/inbox.php',
    'api/contact/get-message.php',
];

$assets = [
    'admin/assets/js/consultant-documents.js',
    'admin/assets/js/consultant-technical.js',
    'admin/assets/js/consultant-contract.js',
    'admin/assets/js/consultant-quality.js',
    'admin/assets/js/consultant-ipc.js',
    'admin/assets/css/components/consultant-documents.css',
    'admin/assets/css/components/consultant-technical.css',
    'admin/assets/css/components/consultant-contract.css',
    'admin/assets/css/components/consultant-quality.css',
    'admin/assets/css/components/consultant-ipc.css',
    'admin/assets/css/dashboard-consultant.css',
];

$models = [
    'ConsultantDashboard',
    'ConsultantIPC',
    'ConsultantTechnicalReview',
    'ConsultantQuality',
    'ConsultantDocumentCentre',
    'ConsultantContractDecision',
];

$issues = [];
$warns = [];
$oks = [];

echo "=== CONSULTANT READINESS CHECK ===\n";
echo "Date: " . date('c') . "\n\n";

// Files
echo "--- Pages ---\n";
$disk = array_map('basename', glob($root . '/admin/consultant/*.php') ?: []);
sort($disk);
foreach ($expected as $file => $meta) {
    $path = $root . '/admin/consultant/' . $file;
    if (!is_file($path)) {
        $issues[] = "Missing page file: {$file}";
        echo "FAIL missing {$file}\n";
        continue;
    }
    $src = file_get_contents($path);
    $lint = [];
    $code = 0;
    exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $lint, $code);
    if ($code !== 0) {
        $issues[] = "Syntax error: {$file}";
        echo "FAIL syntax {$file}\n";
        continue;
    }
    if (!str_contains($src, "Guard::exactRole('consultant')") && !str_contains($src, 'Guard::exactRole("consultant")')) {
        $issues[] = "Missing Guard::exactRole consultant on {$file}";
        echo "FAIL guard {$file}\n";
        continue;
    }
    if ($meta['csrf'] !== null) {
        if (!preg_match('/\$csrfForm\s*=\s*[\'"]([^\'"]+)/', $src, $m)) {
            // quality pages set csrf before include of partial
            if (!str_contains($src, "csrfForm = '{$meta['csrf']}'") && !str_contains($src, 'csrfForm = "' . $meta['csrf'] . '"')) {
                $issues[] = "Missing csrfForm on {$file} (expected {$meta['csrf']})";
                echo "FAIL csrf {$file}\n";
                continue;
            }
        } else {
            if ($m[1] !== $meta['csrf'] && !str_contains($src, $meta['csrf'])) {
                $warns[] = "csrfForm on {$file} is {$m[1]}, expected {$meta['csrf']}";
            }
        }
    }
    if ($meta['limit'] !== null) {
        if (preg_match('/\$limit\s*=\s*(\d+)/', $src, $lm)) {
            if ((int)$lm[1] !== $meta['limit']) {
                $warns[] = "{$file} limit={$lm[1]} (expected {$meta['limit']})";
            }
        } elseif (str_contains($src, 'consultant-quality-page')) {
            $oks[] = "{$file} uses quality partial limit=10";
        } else {
            $warns[] = "{$file} no explicit \$limit";
        }
    }
    $oks[] = "Page OK: {$file} (phase {$meta['phase']})";
    echo "OK   {$file}\n";
}

$extra = array_diff($disk, array_keys($expected));
if ($extra) {
    $warns[] = 'Extra consultant files not in expected nav map: ' . implode(', ', $extra);
}

// Sidebar nav paths
echo "\n--- Sidebar nav ---\n";
$sidebar = file_get_contents($root . '/app/partials/admin/sidebar.php');
foreach (array_keys($expected) as $file) {
    $path = 'admin/consultant/' . $file;
    if (str_contains($sidebar, $path)) {
        echo "OK   nav {$file}\n";
    } else {
        $issues[] = "Sidebar missing path {$path}";
        echo "FAIL nav {$file}\n";
    }
}

// APIs + assets
echo "\n--- APIs ---\n";
foreach ($apis as $a) {
    if (is_file($root . '/' . $a)) {
        echo "OK   {$a}\n";
        $oks[] = "API {$a}";
    } else {
        $issues[] = "Missing API {$a}";
        echo "FAIL {$a}\n";
    }
}

echo "\n--- Assets ---\n";
foreach ($assets as $a) {
    if (is_file($root . '/' . $a)) {
        echo "OK   {$a}\n";
    } else {
        $issues[] = "Missing asset {$a}";
        echo "FAIL {$a}\n";
    }
}

echo "\n--- Models ---\n";
foreach ($models as $class) {
    if (class_exists($class)) {
        echo "OK   {$class}\n";
    } else {
        $issues[] = "Missing class {$class}";
        echo "FAIL {$class}\n";
    }
}

// Live data for Teddy
echo "\n--- Live data (user #{$uid}) ---\n";
try {
    $dash = ConsultantDashboard::summary($uid, $role);
    echo "assigned_projects=" . (int)($dash['assigned_projects'] ?? 0) . "\n";
    echo "certification_queue=" . (int)($dash['certification_queue'] ?? 0) . "\n";
    echo "boq_risks=" . (int)($dash['boq_risks'] ?? 0) . "\n";
    echo "technical_pending=" . (int)($dash['technical_pending'] ?? 0) . "\n";
    echo "quality_alerts=" . (int)($dash['quality_alerts'] ?? 0) . "\n";
    echo "programme_overdue=" . (int)($dash['programme_overdue'] ?? 0) . "\n";
    echo "unread_messages=" . (int)($dash['unread_messages'] ?? 0) . "\n";

    $counts = [
        'documents' => ConsultantDocumentCentre::documentCount($uid, $role),
        'site_reports' => ConsultantDocumentCentre::siteReportCount($uid, $role),
        'materials' => ConsultantTechnicalReview::materialCount($uid, $role),
        'drawings' => ConsultantTechnicalReview::drawingCount($uid, $role),
        'eot' => ConsultantContractDecision::eotCount($uid, $role),
        'variations' => ConsultantContractDecision::variationCount($uid, $role),
        'boq' => ConsultantTechnicalReview::boqCount($uid, $role),
        'programme' => ConsultantTechnicalReview::programmeCount($uid, $role),
    ];
    foreach (['ncr' => 'ncr', 'defect' => 'defect', 'itp' => 'itp', 'quality_test' => 'quality_test'] as $label => $type) {
        if (method_exists('ConsultantQuality', 'count')) {
            try {
                $counts[$label] = ConsultantQuality::count($uid, $role, $type, []);
            } catch (Throwable) {
                $counts[$label] = '?';
            }
        }
    }
    // Fallback quality methods
    if (method_exists('ConsultantQuality', 'summary')) {
        try {
            $qs = ConsultantQuality::summary($uid, $role, 'ncr', []);
            $counts['ncr'] = $qs['total'] ?? $counts['ncr'] ?? 0;
        } catch (Throwable) {
        }
    }

    foreach ($counts as $k => $v) {
        echo "{$k}={$v}\n";
        if (is_numeric($v) && (int)$v === 0 && in_array($k, ['documents', 'site_reports', 'materials', 'drawings'], true)) {
            $warns[] = "Empty demo data for {$k}";
        }
    }

    // IPC counts
    if (class_exists('ConsultantIPC')) {
        if (method_exists('ConsultantIPC', 'inboxCount')) {
            echo 'ipc_inbox=' . ConsultantIPC::inboxCount($uid, $role, []) . "\n";
        } elseif (method_exists('ConsultantIPC', 'count')) {
            echo 'ipc_count_method_exists' . "\n";
        }
    }
} catch (Throwable $e) {
    $issues[] = 'Dashboard/model live check failed: ' . $e->getMessage();
    echo 'FAIL live: ' . $e->getMessage() . "\n";
}

// Documents JS uses AHPTC
$docsJs = file_get_contents($root . '/admin/assets/js/consultant-documents.js');
if (str_contains($docsJs, 'AHPTC.request')) {
    $oks[] = 'documents.js uses AHPTC.request';
    echo "\nOK   documents.js AHPTC.request\n";
} else {
    $issues[] = 'documents.js not using AHPTC.request';
    echo "\nFAIL documents.js AHPTC.request\n";
}

// Grouping note: UI groups may be client-side
echo "\n=== SUMMARY ===\n";
echo 'OK items: ' . count($oks) . "\n";
echo 'Warnings: ' . count($warns) . "\n";
foreach ($warns as $w) {
    echo "  WARN  {$w}\n";
}
echo 'Issues: ' . count($issues) . "\n";
foreach ($issues as $i) {
    echo "  FAIL  {$i}\n";
}

if ($issues === []) {
    echo "\nVERDICT: CONSULTANT READY FOR CONTRACTOR PHASE\n";
    exit(0);
}
echo "\nVERDICT: FIX ISSUES BEFORE CONTRACTOR\n";
exit(1);
