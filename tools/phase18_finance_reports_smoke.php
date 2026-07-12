<?php
require 'C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/app/core/bootstrap.php';
$fail = 0;
function ok($c,$m){ global $fail; echo ($c?'OK  ':'FAIL')." $m\n"; if(!$c)$fail++; }

foreach (['admin/finance/financial-reports.php','admin/finance/messages.php','app/models/FinanceReports.php','api/finance/generate-report.php'] as $f) {
  $p = 'C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/'.$f;
  exec('C:\\xampp\\php\\php.exe -l '.escapeshellarg($p).' 2>&1', $o, $c);
  ok($c===0, "lint $f");
}

// types + filters
foreach (FinanceReports::TYPES as $t) {
  $r = FinanceReports::generate($t, []);
  ok(($r['row_count']??-1)>=0 && !empty($r['columns']), "$t generates");
  $html = FinanceReports::html($r, false);
  ok(str_contains($html, 'sheet__header') && str_contains($html, 'Print / Save as PDF'), "$t professional html");
  $csv = FinanceReports::csv($r);
  ok(strlen($csv)>20, "$t csv");
}

// monthly filters work
$m1 = FinanceReports::generate('monthly_summary', []);
$m2 = FinanceReports::generate('monthly_summary', ['project_id'=>99999]);
ok($m2['row_count']===0 || $m2['row_count'] < $m1['row_count'] || $m1['row_count']===0, 'monthly respects impossible project filter (rows m1='.$m1['row_count'].' m2='.$m2['row_count'].')');

// approved_unpaid date mapping
$src = file_get_contents('C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/app/models/FinanceReports.php');
ok(str_contains($src, 'approved_from'), 'maps approved_from');
ok(str_contains($src, 'filterSupport'), 'filter support');
ok(str_contains($src, 'auto_print') || str_contains($src, 'autoPrint'), 'auto print');

// page features
$page = file_get_contents('C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/admin/finance/financial-reports.php');
foreach (['finance-hub','finance-card--full','Print / PDF',"'print' => 1",'recordRun','typeLabels','data-label','finance-stat--link'] as $n) {
  ok(str_contains($page, $n), "page has $n");
}
$msg = file_get_contents('C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/admin/finance/messages.php');
ok(str_contains($msg, 'finance-hub') && str_contains($msg, 'messages-centre'), 'messages hub + centre');

$css = file_get_contents('C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/admin/assets/css/components/finance-reports.css');
ok(str_contains($css, '@media print') && str_contains($css, 'finance-report-summary'), 'reports css print+summary');

// labels
ok(FinanceReports::title('ld_report')==='Liquidated Damages Report', 'ld title');
ok(isset(FinanceReports::typeLabels()['ld_report']), 'typeLabels');

// recordRun
FinanceReports::recordRun(13, 'payment_register', 'print', [], 1);
$runs = FinanceReports::recentRuns(1);
ok(!empty($runs) && ($runs[0]['type_label']??'')!=='', 'recentRuns labeled');

// disabled dates for budget support
$fs = FinanceReports::filterSupport('budget_summary');
ok($fs['dates']===false, 'budget dates disabled');

echo $fail===0 ? "PHASE18 SMOKE OK\n" : "PHASE18 FAIL $fail\n";
exit($fail===0?0:1);
