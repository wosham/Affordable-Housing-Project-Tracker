<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

$root = dirname(__DIR__);
$files = [
    'admin/contractor/dashboard.php',
    'admin/contractor/my-project.php',
    'admin/contractor/progress-update.php',
    'admin/contractor/programme-of-works.php',
    'admin/contractor/boq.php',
    'app/models/ContractorProject.php',
    'app/models/ContractorDashboard.php',
    'admin/assets/js/contractor-progress.js',
];

echo "=== Phase 10 smoke ===\n";
foreach ($files as $f) {
    $path = $root . '/' . $f;
    if (!is_file($path)) {
        echo "FAIL missing {$f}\n";
        continue;
    }
    if (str_ends_with($f, '.js')) {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'AHPTC.request');
        echo ($ok ? 'OK  ' : 'FAIL') . " {$f} AHPTC.request\n";
        // BOM check not for JS needed
        continue;
    }
    $bytes = file_get_contents($path, false, null, 0, 3);
    if ($bytes === "\xEF\xBB\xBF") {
        echo "FAIL BOM {$f}\n";
    }
    $out = [];
    $code = 0;
    exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    echo ($code === 0 ? 'OK  ' : 'FAIL') . " {$f}\n";
    $src = file_get_contents($path);
    if (str_contains($f, 'admin/contractor/') && str_ends_with($f, '.php')) {
        if (!str_contains($src, "Guard::exactRole('contractor')")) {
            echo "  WARN guard not exactRole on {$f}\n";
        }
    }
}

$uid = 11;
$role = 'contractor';
echo "\n--- Live David #11 ---\n";
$summary = ContractorDashboard::summary($uid, $role);
echo 'projects=' . $summary['projects'] . ' active_ipcs=' . $summary['active_ipcs'] . ' avg=' . $summary['average_progress'] . "%\n";
$pid = ContractorProject::defaultProjectId($uid, $role, 0);
echo 'default_project=' . $pid . "\n";
if ($pid > 0) {
    $ps = ContractorProject::summary($pid, $uid, $role);
    echo 'boq_items=' . $ps['boq_items'] . ' programme=' . $ps['programme_total'] . ' overdue=' . $ps['programme_overdue'] . "\n";
    echo 'boq_count=' . ContractorProject::boqCount($pid, $uid, $role, []) . "\n";
    echo 'programme_count=' . ContractorProject::programmeCount($pid, $uid, $role, []) . "\n";
    echo 'progress_count=' . ContractorProject::progressHistoryCount($pid, $uid, $role) . "\n";
    echo 'focus=' . count(ContractorProject::programmeFocusItems($pid, $uid, $role, 8)) . "\n";
    echo 'boq_attention=' . count(ContractorProject::boqAttentionItems($pid, $uid, $role, 8)) . "\n";
    $pageItems = ContractorProject::boqItems($pid, $uid, $role, [], 10, 0);
    echo 'boq_page10=' . count($pageItems) . "\n";
    $progPage = ContractorProject::programmeList($pid, $uid, $role, [], 10, 0);
    echo 'programme_page10=' . count($progPage) . "\n";
}

$progressTotal = Database::fetch('SELECT COUNT(*) AS c FROM project_progress_updates')['c'] ?? 0;
echo "progress_updates_total={$progressTotal}\n";
echo "SMOKE OK\n";
