<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 10 F smoke ===\n";
$files = [
    'admin/contractor/dashboard.php',
    'admin/contractor/my-project.php',
    'admin/contractor/progress-update.php',
    'admin/contractor/boq.php',
    'app/partials/admin/contractor-progress-timeline.php',
    'admin/assets/js/contractor-progress-history.js',
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
        echo (str_contains($src, 'data-progress-open') || str_contains($src, 'openDetail') ? 'OK  ' : 'FAIL') . " $f\n";
    }
}

$s = ContractorProject::summary(1, 11, 'contractor');
echo "open: rfi={$s['rfis']} mats={$s['materials']} draws={$s['drawings']} eot={$s['eots']} vo={$s['variations']}\n";
$h = ContractorProject::progressHistory(1, 11, 'contractor', 5);
echo 'history=' . count($h) . "\n";
if ($h) {
    $p = (string)($h[0]['photo_path'] ?? '');
    $abs = dirname(__DIR__) . '/' . str_replace(['/', '\\'], '/', $p);
    echo 'photo=' . $p . ' exists=' . (is_file($abs) ? 'yes' : 'no') . ' image=' . (preg_match('/\.(jpe?g|png|webp)$/i', $p) ? 'yes' : 'no') . "\n";
    echo 'milestone=' . ($h[0]['current_milestone'] ?? '') . "\n";
    $demo = stripos((string)($h[0]['note'] ?? ''), 'demo') !== false || stripos((string)($h[0]['work_summary'] ?? ''), 'demo') !== false;
    echo 'has_demo_text=' . ($demo ? 'YES-BAD' : 'no') . "\n";
}
$bad = Database::fetch("SELECT COUNT(*) AS c FROM project_progress_updates WHERE photo_path LIKE '%.txt' OR note LIKE '%demo%' OR note LIKE '%UAT%' OR current_milestone LIKE '%P10%'")['c'] ?? 0;
echo "bad_progress_rows={$bad}\n";
echo "SMOKE OK\n";
