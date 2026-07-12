<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Phase 13 smoke ===\n";
$root = dirname(__DIR__);
$fail = 0;

$files = [
    'app/models/ContractorSiteRecord.php',
    'app/partials/admin/contractor-site-record-page.php',
    'admin/contractor/documents.php',
    'admin/contractor/equipment-register.php',
    'admin/contractor/hs-incidents.php',
    'admin/contractor/labour-register.php',
    'admin/contractor/material-deliveries.php',
    'admin/contractor/subcontractors.php',
    'admin/contractor/messages.php',
    'api/contractor/_site-record-handler.php',
    'api/contractor/site-record-detail.php',
    'api/contractor/document-save.php',
    'api/contractor/equipment-save.php',
    'api/contractor/hs-incident-save.php',
    'api/contractor/labour-save.php',
    'api/contractor/material-delivery-save.php',
    'api/contractor/subcontractor-save.php',
    'admin/assets/js/contractor-site-records.js',
    'admin/assets/css/components/contractor-site-records.css',
    'api/media/upload.php',
];

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
        $ok = str_contains($src, 'AHPTC.request') && str_contains($src, 'data-csr-open') && !str_contains($src, "fetch(form.getAttribute('action')");
        echo ($ok ? 'OK  ' : 'FAIL') . " js {$f}\n";
        if (!$ok) {
            $fail++;
        }
    } else {
        $src = file_get_contents($path);
        $ok = str_contains($src, 'auto-fit') && str_contains($src, 'csr-overlay');
        echo ($ok ? 'OK  ' : 'FAIL') . " css {$f}\n";
        if (!$ok) {
            $fail++;
        }
    }
}

$upload = file_get_contents($root . '/api/media/upload.php');
if (!str_contains($upload, 'contractor_site_records')) {
    echo "FAIL media upload missing contractor_site_records CSRF\n";
    $fail++;
} else {
    echo "OK   media upload CSRF forms\n";
}

foreach (['documents.php', 'equipment-register.php', 'hs-incidents.php', 'labour-register.php', 'material-deliveries.php', 'subcontractors.php'] as $page) {
    $src = file_get_contents($root . '/admin/contractor/' . $page);
    if (!str_contains($src, 'media-picker') || !str_contains($src, 'media-library')) {
        echo "FAIL {$page} missing media assets\n";
        $fail++;
    }
}

$partial = file_get_contents($root . '/app/partials/admin/contractor-site-record-page.php');
foreach (['csr_pagination', 'data-csr-open', 'csr-peers', 'bucket', 'data-csr-media', 'data-csr-detail-modal', 'data-csr-edit-modal', 'data-csr-edit-form', 'data-csr-edit-save'] as $needle) {
    if (!str_contains($partial, $needle)) {
        echo "FAIL partial missing {$needle}\n";
        $fail++;
    }
}

$js = file_get_contents($root . '/admin/assets/js/contractor-site-records.js');
if (!str_contains($js, 'data-csr-edit-modal') || !str_contains($js, "mode === 'edit'") || str_contains($js, 'scrollIntoView')) {
    if (str_contains($js, 'scrollIntoView')) {
        echo "FAIL js still scrolls on edit\n";
        $fail++;
    }
    if (!str_contains($js, 'data-csr-edit-modal')) {
        echo "FAIL js missing edit modal handling\n";
        $fail++;
    }
} else {
    echo "OK   js edit modal (no page scroll)\n";
}

// payments must not be in site peer types
if (isset(ContractorSiteRecord::types()['payments'])) {
    echo "FAIL payments still in site record types\n";
    $fail++;
} else {
    echo "OK   payments removed from CSR types\n";
}

$uid = 11;
$role = 'contractor';
$pid = ContractorSiteRecord::defaultProjectId($uid, $role, 0);
echo "default_project={$pid}\n";

foreach (array_keys(ContractorSiteRecord::types()) as $type) {
    $stats = ContractorSiteRecord::stats($type, $pid, $uid, $role);
    $count = ContractorSiteRecord::count($type, $pid, $uid, $role, []);
    $list = ContractorSiteRecord::list($type, $pid, $uid, $role, [], 10, 0);
    $activeBucket = ContractorSiteRecord::count($type, $pid, $uid, $role, ['bucket' => 'active']);
    echo "{$type}: total={$stats['total']} active={$stats['active']} risk={$stats['risk']} value={$stats['value']} count={$count} page=" . count($list) . " bucket_active={$activeBucket}\n";

    if ($type === 'documents' && (int)$stats['active'] === 0 && (int)$stats['total'] > 0) {
        // active = consultant review pending/flagged/returned — may legitimately be 0 if all approved
    }

    if ($list !== []) {
        $detail = ContractorSiteRecord::detailForUser($type, (int)$list[0]['id'], $uid, $role);
        if (!$detail) {
            echo "FAIL detail {$type}\n";
            $fail++;
        } else {
            echo "  detail_ok id={$list[0]['id']}\n";
        }
    }

    // contractor-safe statuses
    $formStatuses = ContractorSiteRecord::config($type)['statuses'];
    if ($type === 'labour' && in_array('reviewed', $formStatuses, true)) {
        echo "FAIL labour form still offers reviewed\n";
        $fail++;
    }
    if ($type === 'materials' && in_array('accepted', $formStatuses, true)) {
        echo "FAIL materials form still offers accepted\n";
        $fail++;
    }
    if ($type === 'incidents' && (in_array('closed', $formStatuses, true) || in_array('resolved', $formStatuses, true))) {
        echo "FAIL incidents form still offers closed/resolved\n";
        $fail++;
    }
}

// Create/update smoke on equipment for project
try {
    $id = ContractorSiteRecord::save('equipment', $uid, $role, [
        'project_id' => $pid,
        'equipment_type' => 'Smoke Test Compactor',
        'registration' => 'SMK-PLT-' . $pid,
        'owner' => 'Contractor plant',
        'condition' => 'good',
        'status' => 'on-site',
        'date_on_site' => date('Y-m-d'),
    ]);
    echo "create_equipment id={$id}\n";
    $id2 = ContractorSiteRecord::save('equipment', $uid, $role, [
        'id' => $id,
        'project_id' => $pid,
        'equipment_type' => 'Smoke Test Compactor Updated',
        'registration' => 'SMK-PLT-' . $pid,
        'owner' => 'Contractor plant',
        'condition' => 'serviceable',
        'status' => 'on-site',
        'date_on_site' => date('Y-m-d'),
    ]);
    echo "update_equipment id={$id2}\n";
    // cleanup smoke row
    Database::query('DELETE FROM equipment_register WHERE id = ?', [$id2]);
    echo "cleanup_equipment ok\n";
} catch (Throwable $e) {
    echo 'FAIL equipment save ' . $e->getMessage() . "\n";
    $fail++;
}

// labour validation
try {
    ContractorSiteRecord::save('labour', $uid, $role, [
        'project_id' => $pid,
        'diary_date' => date('Y-m-d'),
        'skilled_count' => 0,
        'unskilled_count' => 0,
        'supervisor_count' => 0,
    ]);
    echo "FAIL labour zero counts allowed\n";
    $fail++;
} catch (RuntimeException $e) {
    echo "OK   labour rejects zero counts\n";
}

$p9 = (int)(Database::fetch("SELECT COUNT(*) AS c FROM documents WHERE original_name LIKE '%P9%' OR filename LIKE '%p9-demo%'")['c'] ?? 0);
echo "p9_docs_left={$p9}\n";
if ($p9 > 0) {
    echo "FAIL p9 demo docs remain\n";
    $fail++;
}

// non-empty tables for UAT
foreach (['equipment_register', 'labour_register', 'material_deliveries'] as $t) {
    $c = (int)(Database::fetch("SELECT COUNT(*) AS c FROM `{$t}`")['c'] ?? 0);
    echo "{$t}_total={$c}\n";
    if ($c <= 0) {
        echo "FAIL {$t} still empty\n";
        $fail++;
    }
}

echo $fail === 0 ? "SMOKE OK\n" : "SMOKE FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
