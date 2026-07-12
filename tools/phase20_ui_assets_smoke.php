<?php
$root = dirname(__DIR__);
$fail = 0;

function ok(bool $c, string $m): void
{
    global $fail;
    echo ($c ? 'OK   ' : 'FAIL ') . $m . "\n";
    if (!$c) {
        $fail++;
    }
}

echo "=== Phase 20 shared UI assets smoke ===\n";

$files = [
    'admin/assets/css/components/cards.css',
    'admin/assets/css/components/tables.css',
    'admin/assets/css/components/forms.css',
    'admin/assets/css/components/modals.css',
    'admin/assets/css/components/reports.css',
    'admin/assets/css/components/gantt.css',
    'admin/assets/css/components/ipc-centre.css',
    'admin/assets/css/components/boq-centre.css',
    'admin/assets/js/data-tables.js',
    'admin/assets/js/file-uploader.js',
    'admin/assets/js/gantt.js',
];

foreach ($files as $f) {
    $path = $root . '/' . $f;
    ok(is_file($path) && filesize($path) > 200, $f . ' exists (' . (is_file($path) ? filesize($path) : 0) . 'b)');
}

$cards = file_get_contents($root . '/admin/assets/css/components/cards.css');
ok(str_contains($cards, 'card--elevated') && str_contains($cards, 'stat-grid--modern') && str_contains($cards, '@media'), 'cards modern variants');

$tables = file_get_contents($root . '/admin/assets/css/components/tables.css');
ok(str_contains($tables, 'data-table--stack') && str_contains($tables, 'filter-bar--grid') && str_contains($tables, 'data-sort-dir'), 'tables stack+filter+sort');
ok(!preg_match('/^\.filter-bar\s*\{/m', $tables), 'tables does not override global .filter-bar');

$forms = file_get_contents($root . '/admin/assets/css/components/forms.css');
ok(str_contains($forms, 'file-uploader') && str_contains($forms, 'form-grid--modern') && str_contains($forms, 'choice-card'), 'forms uploader+grid');
ok(!preg_match('/^\.form-grid\s*\{/m', $forms), 'forms does not override global .form-grid');

$modals = file_get_contents($root . '/admin/assets/css/components/modals.css');
ok(str_contains($modals, 'modal__dialog--wide') && str_contains($modals, 'modal__footer') && str_contains($modals, '@media'), 'modals sizes+mobile');

$reports = file_get_contents($root . '/admin/assets/css/components/reports.css');
ok(str_contains($reports, 'Phase 20 polish') && str_contains($reports, 'report-type.is-active'), 'reports polish');

$gantt = file_get_contents($root . '/admin/assets/css/components/gantt.css');
ok(str_contains($gantt, 'gantt-legend') && str_contains($gantt, 'Phase 20 polish'), 'gantt polish+legend');

$ipc = file_get_contents($root . '/admin/assets/css/components/ipc-centre.css');
ok(str_contains($ipc, 'Phase 20 polish') && str_contains($ipc, 'ipc-status-tabs'), 'ipc polish');

$boq = file_get_contents($root . '/admin/assets/css/components/boq-centre.css');
ok(str_contains($boq, 'Phase 20 polish') && str_contains($boq, 'boq-table-wrap'), 'boq polish');

$dt = file_get_contents($root . '/admin/assets/js/data-tables.js');
ok(str_contains($dt, 'initDataTables') && str_contains($dt, 'exportCsv') && str_contains($dt, 'data-table-count'), 'data-tables.js features');

$fu = file_get_contents($root . '/admin/assets/js/file-uploader.js');
ok(str_contains($fu, 'initFileUploaders') && str_contains($fu, 'is-dragging') && str_contains($fu, 'data-upload-picker'), 'file-uploader.js features');

$gj = file_get_contents($root . '/admin/assets/js/gantt.js');
ok(str_contains($gj, 'AHPTC.toast') && str_contains($gj, 'data-programme-modal'), 'gantt.js toast polish');

// Pages that load components still reference them
$saDash = file_get_contents($root . '/admin/superadmin/dashboard.php');
ok(str_contains($saDash, "'cards'") && str_contains($saDash, "'tables'"), 'SA dashboard loads cards+tables');

$prog = file_get_contents($root . '/admin/manager/programme-of-works.php');
ok(str_contains($prog, 'gantt') && str_contains($prog, 'data-programme-modal'), 'manager programme uses gantt');

$boqPage = file_get_contents($root . '/admin/superadmin/boq.php');
ok(str_contains($boqPage, 'boq-centre'), 'SA boq loads centre css');

$ipcPage = file_get_contents($root . '/admin/superadmin/ipcs.php');
ok(str_contains($ipcPage, 'ipc-centre'), 'SA ipc loads centre css');

echo $fail === 0 ? "PHASE20 SMOKE OK\n" : "PHASE20 FAIL $fail\n";
exit($fail === 0 ? 0 : 1);
