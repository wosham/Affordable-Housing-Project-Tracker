<?php
require 'C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing/app/core/bootstrap.php';
$root = 'C:/xampp/htdocs/Trans-Nzoia-Affordable-Housing';
$fail = 0;
function ok($c,$m){ global $fail; echo ($c?'OK  ':'FAIL')." $m\n"; if(!$c)$fail++; }

$pages = glob("$root/admin/finance/*.php");
foreach ($pages as $p) {
  $base = basename($p);
  $o=[]; $c=0; exec('C:\\xampp\\php\\php.exe -l '.escapeshellarg($p).' 2>&1',$o,$c);
  ok($c===0, "lint $base");
  $s = file_get_contents($p);
  ok(str_contains($s, "Guard::exactRole('finance')"), "role $base");
  ok(str_contains($s, 'finance-hub'), "hub $base");
  ok(str_contains($s, 'financial-reports.php'), "reports link $base");
  ok(str_contains($s, 'messages.php') || $base==='messages.php', "messages link $base");
}

// payment process
try { FinancePayment::process(13, ['confirm_payment'=>1,'reference_no'=>'T','payment_date'=>date('Y-m-d'),'amount'=>1,'ipc_id'=>1]); ok(false,'should need review'); }
catch(Throwable $e){ ok(str_contains($e->getMessage(),'checked')||str_contains($e->getMessage(),'Confirm'), 'process safety: '.$e->getMessage()); }

// models
$s = FinancePayment::dashboard();
ok(isset($s['approved_count']), 'dash stats');
foreach (FinanceReports::TYPES as $t) {
  $r = FinanceReports::generate($t,[]);
  ok(str_contains(FinanceReports::html($r,false),'sheet__header'), "print $t");
}
$bad=0; foreach (FinanceBudget::projectRows([],100) as $r) {
  $exp = max((float)$r['contract_sum']-(float)$r['paid_amount']-(float)$r['approved_unpaid'],0);
  if (abs($exp-(float)$r['balance_amount'])>0.02) $bad++;
}
ok($bad===0,'budget formula');

// css/js
ok(str_contains(file_get_contents("$root/admin/assets/css/components/finance-payments.css"),'finance-table--stack'),'css stack');
ok(str_contains(file_get_contents("$root/admin/assets/js/finance-payments.js"),'confirm_review'),'js review');
ok(str_contains(file_get_contents("$root/admin/assets/css/components/finance-reports.css"),'@media print'),'print css');

// api
foreach (['process-payment.php','payment-detail.php','generate-report.php'] as $a) {
  ok(str_contains(file_get_contents("$root/api/finance/$a"),'finance'), "api $a");
}

echo $fail===0?"\nFINANCE MODULE READY — safe to proceed to interns\n":"\nFAIL count=$fail\n";
exit($fail===0?0:1);
