<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 12 smoke ===\n";
$root = dirname(__DIR__);
$files = [
    'app/models/ContractorSubmission.php',
    'app/partials/admin/contractor-submission-page.php',
    'admin/contractor/rfis.php',
    'admin/contractor/eot-request.php',
    'admin/contractor/variation-request.php',
    'admin/contractor/shop-drawing-submit.php',
    'admin/contractor/material-approval-submit.php',
    'api/contractor/_submission-handler.php',
    'api/contractor/submission-detail.php',
    'api/contractor/rfi-submit.php',
    'api/contractor/eot-submit.php',
    'api/contractor/variation-submit.php',
    'api/contractor/shop-drawing-submit.php',
    'api/contractor/material-approval-submit.php',
    'admin/assets/js/contractor-submissions.js',
    'admin/assets/css/components/contractor-submissions.css',
];

$fail = 0;
foreach ($files as $f) {
    $path = $root . '/' . $f;
    if (!is_file($path)) {
        echo "FAIL missing {$f}\n";
        $fail++;
        continue;
    }
    if (str_ends_with($f, '.php')) {
        $out = [];
        $code = 0;
        exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            echo 'FAIL lint ' . $f . ' ' . implode(' ', $out) . "\n";
            $fail++;
        } else {
            echo "OK   lint {$f}\n";
        }
    } elseif (str_ends_with($f, '.js')) {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'AHPTC.request') && str_contains($src, 'data-submission-open');
        echo ($ok ? 'OK  ' : 'FAIL') . " js {$f}\n";
        if (!$ok) {
            $fail++;
        }
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'auto-fit') && str_contains($src, 'contractor-submission-overlay');
        echo ($ok ? 'OK  ' : 'FAIL') . " css {$f}\n";
        if (!$ok) {
            $fail++;
        }
    }
}

$uid = 11;
$role = 'contractor';
$types = array_keys(ContractorSubmission::types());
foreach ($types as $type) {
    $stats = ContractorSubmission::stats($type, $uid, $role, []);
    $count = ContractorSubmission::count($type, $uid, $role, []);
    $page = ContractorSubmission::list($type, $uid, $role, [], 10, 0);
    $pending = ContractorSubmission::count($type, $uid, $role, ['bucket' => 'pending']);
    echo "{$type}: total={$stats['total']} pending={$stats['pending']} accepted={$stats['accepted']} returned={$stats['returned']} closed={$stats['closed']} impact={$stats['impact']} count={$count} page_rows=" . count($page) . " bucket_pending={$pending}\n";

    // RFI: returned must be 0 (no rejected status)
    if ($type === 'rfi' && (float)$stats['returned'] !== 0.0) {
        echo "FAIL rfi returned should be 0\n";
        $fail++;
    }
    // shop: pending = under-review only (not resubmit); assignment-scoped
    if ($type === 'shop_drawing') {
        $projectIds = ContractorProject::projectIds($uid, $role);
        if ($projectIds !== []) {
            $in = implode(',', array_fill(0, count($projectIds), '?'));
            $bindings = array_merge($projectIds, [$uid]);
            $under = (int)(Database::fetch(
                "SELECT COUNT(*) AS c FROM shop_drawings WHERE project_id IN ({$in}) AND submitted_by = ? AND status = 'under-review'",
                $bindings
            )['c'] ?? 0);
            $returned = (int)(Database::fetch(
                "SELECT COUNT(*) AS c FROM shop_drawings WHERE project_id IN ({$in}) AND submitted_by = ? AND status IN ('rejected','resubmit')",
                $bindings
            )['c'] ?? 0);
            if ((int)$stats['pending'] !== $under) {
                echo "FAIL shop pending expected {$under} got {$stats['pending']}\n";
                $fail++;
            }
            if ((int)$stats['returned'] !== $returned) {
                echo "FAIL shop returned expected {$returned} got {$stats['returned']}\n";
                $fail++;
            }
        }
    }

    if ($page !== []) {
        $detail = ContractorSubmission::detailForUser($type, (int)$page[0]['id'], $uid, $role);
        if (!$detail) {
            echo "FAIL detail {$type}\n";
            $fail++;
        } else {
            echo "  detail_ok id={$page[0]['id']} attachments=" . count($detail['attachments']) . "\n";
        }
    }
}

// Project-scoped stats differ from portfolio when multi-project
$projects = ContractorProject::projectIds($uid, $role);
if (count($projects) > 1) {
    $all = ContractorSubmission::stats('rfi', $uid, $role, []);
    $one = ContractorSubmission::stats('rfi', $uid, $role, ['project_id' => (int)$projects[0]]);
    echo "rfi portfolio_total={$all['total']} project_{$projects[0]}_total={$one['total']}\n";
    if ((float)$one['total'] > (float)$all['total']) {
        echo "FAIL project stats exceed portfolio\n";
        $fail++;
    }
}

$p9 = 0;
foreach (['eot_requests' => 'reason', 'variations' => 'description', 'shop_drawings' => 'title', 'material_approvals' => 'material'] as $t => $c) {
    $p9 += (int)(Database::fetch("SELECT COUNT(*) AS c FROM `{$t}` WHERE `{$c}` LIKE '%P9 demo%'")['c'] ?? 0);
}
echo "p9_demo_left={$p9}\n";
if ($p9 > 0) {
    echo "FAIL p9 demo strings remain\n";
    $fail++;
}

$rfiZero = (int)(Database::fetch('SELECT COUNT(*) AS c FROM rfis WHERE rfi_number = 0 OR rfi_number IS NULL')['c'] ?? 0);
echo "rfi_number_zero={$rfiZero}\n";
if ($rfiZero > 0) {
    echo "FAIL rfi numbers still zero\n";
    $fail++;
}

$partial = file_get_contents($root . '/app/partials/admin/contractor-submission-page.php');
foreach (['contractor_submission_pagination', 'data-submission-open', 'contractor-submission-peers', 'bucket', 'data-media-picker-open', 'data-submission-media', 'media-picker'] as $needle) {
    if (!str_contains($partial, $needle) && $needle !== 'media-picker') {
        echo "FAIL partial missing {$needle}\n";
        $fail++;
    }
}
if (!str_contains($partial, 'data-media-picker-open')) {
    echo "FAIL media picker not wired in form\n";
    $fail++;
}

foreach (['rfis.php', 'eot-request.php', 'variation-request.php', 'shop-drawing-submit.php', 'material-approval-submit.php'] as $page) {
    $src = file_get_contents($root . '/admin/contractor/' . $page);
    if (!str_contains($src, 'media-picker') || !str_contains($src, 'media-library')) {
        echo "FAIL {$page} missing media-picker assets\n";
        $fail++;
    }
}

$folders = MediaLibrary::folders();
if (!isset($folders['contractor-documents'])) {
    echo "FAIL contractor-documents folder missing from MediaLibrary\n";
    $fail++;
}

$js = file_get_contents($root . '/admin/assets/js/contractor-submissions.js');
if (!str_contains($js, 'AHPTC.request') || str_contains($js, "fetch(form.getAttribute('action')")) {
    // raw fetch without AHPTC is a fail if no AHPTC.request
    if (!str_contains($js, 'AHPTC.request')) {
        echo "FAIL js not using AHPTC.request\n";
        $fail++;
    }
}

echo $fail === 0 ? "SMOKE OK\n" : "SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
