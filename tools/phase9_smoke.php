<?php
require dirname(__DIR__) . '/app/core/bootstrap.php';

$uid = 8;
$role = 'consultant';

$files = [
    'admin/consultant/documents.php',
    'admin/consultant/site-reports.php',
    'admin/consultant/material-approvals.php',
    'admin/consultant/shop-drawings.php',
    'admin/consultant/eot-review.php',
    'admin/consultant/variations.php',
    'app/models/ConsultantDocumentCentre.php',
    'app/models/ConsultantTechnicalReview.php',
    'app/models/ConsultantContractDecision.php',
];
foreach ($files as $f) {
    $path = dirname(__DIR__) . '/' . $f;
    $out = [];
    $code = 0;
    exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    echo ($code === 0 ? 'OK  ' : 'FAIL') . ' ' . $f . ' ' . implode(' ', $out) . PHP_EOL;
}

$d = ConsultantDocumentCentre::documentSummary($uid, $role);
$a = ConsultantDocumentCentre::documentAttentionItems($uid, $role, [], 8);
$s = ConsultantDocumentCentre::siteReportSummary($uid, $role);
$sa = ConsultantDocumentCentre::siteReportAttentionItems($uid, $role, [], 8);
$m = ConsultantTechnicalReview::materialSummary($uid, $role);
$mp = ConsultantTechnicalReview::materialPendingItems($uid, $role, [], 8);
$dr = ConsultantTechnicalReview::drawingSummary($uid, $role);
$dq = ConsultantTechnicalReview::drawingQueueItems($uid, $role, [], 8);
$e = ConsultantContractDecision::eotSummary($uid, $role);
$ep = ConsultantContractDecision::eotPendingItems($uid, $role, [], 8);
$v = ConsultantContractDecision::variationSummary($uid, $role);
$vp = ConsultantContractDecision::variationPendingItems($uid, $role, [], 8);

echo "docs total={$d['total']} pending={$d['pending']} attention=" . count($a) . PHP_EOL;
echo "diaries total={$s['total']} pending={$s['pending']} attention=" . count($sa) . PHP_EOL;
echo "mats total={$m['total']} pending={$m['pending']} side=" . count($mp) . PHP_EOL;
echo "draws total={$dr['total']} under={$dr['under_review']} queue=" . count($dq) . PHP_EOL;
echo "eot total={$e['total']} pending={$e['pending_review']} side=" . count($ep) . PHP_EOL;
echo "vo total={$v['total']} pending={$v['pending_review']} side=" . count($vp) . PHP_EOL;

$doc = ConsultantDocumentCentre::documents($uid, $role, ['status' => 'pending'], 1, 0);
if ($doc) {
    $r = ConsultantDocumentCentre::applyAction('document', (int)$doc[0]['id'], 'review', 'Smoke review note for Phase 9 UAT.', $uid, $role);
    echo 'doc action: ' . $r['message'] . PHP_EOL;
}
$mat = ConsultantTechnicalReview::materialItems($uid, $role, ['status' => 'pending'], 1, 0);
if ($mat) {
    $r = ConsultantTechnicalReview::applyAction('material', (int)$mat[0]['id'], 'approve', 'Smoke material approval.', $uid, $role);
    echo 'mat action: ' . $r['message'] . PHP_EOL;
}
$eot = ConsultantContractDecision::eots($uid, $role, ['review_status' => 'pending'], 1, 0);
if ($eot) {
    $r = ConsultantContractDecision::applyAction('eot', (int)$eot[0]['id'], 'recommend', [
        'note' => 'Smoke EOT recommend',
        'recommended_days' => 7,
        'delay_category' => 'weather',
        'documents_checked' => 1,
    ], $uid, $role);
    echo 'eot action: ' . $r['message'] . PHP_EOL;
}

$threads = Database::fetch(
    "SELECT COUNT(*) AS c FROM message_threads t
     INNER JOIN message_participants mp ON mp.thread_id = t.id AND mp.user_id = ?
     WHERE t.subject LIKE 'P9%'",
    [$uid]
);
echo 'p9 message threads=' . (int)($threads['c'] ?? 0) . PHP_EOL;
echo "SMOKE OK\n";
