<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Financial Overview';
$pageDescription = 'Director-level financial summary for contracts, IPCs, payments, retention and liquidated damages.';
$adminRole = 'superadmin';
$componentCss = ['cards', 'tables'];
$contentClass = 'sa-financials-page';
$pageKicker = 'Programme Finance Control';

$financeError = null;

$scalar = static function (string $sql, array $bindings = [], string $column = 'total', mixed $fallback = 0) use (&$financeError): mixed {
    try {
        $row = Database::fetch($sql, $bindings);
        return $row[$column] ?? $fallback;
    } catch (Throwable) {
        $financeError = 'Financial data is unavailable. Confirm MySQL is running and the AHPTC schema is up to date.';
        return $fallback;
    }
};

$rows = static function (string $sql, array $bindings = []) use (&$financeError): array {
    try {
        return Database::fetchAll($sql, $bindings);
    } catch (Throwable) {
        $financeError = 'Financial data is unavailable. Confirm MySQL is running and the AHPTC schema is up to date.';
        return [];
    }
};

$projects = $rows('SELECT id, name FROM projects ORDER BY name ASC');
$projectId = isset($_GET['project_id']) && ctype_digit((string)$_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$status = trim((string)($_GET['status'] ?? ''));
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowedStatuses = ['approved', 'paid', 'certified', 'endorsed', 'rejected', 'submitted'];
$status = in_array($status, $allowedStatuses, true) ? $status : '';
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '';

$totalContractValue = (float)$scalar('SELECT COALESCE(SUM(contract_sum), 0) AS total FROM projects');
$approvedVariationValue = (float)$scalar("SELECT COALESCE(SUM(amount), 0) AS total FROM variations WHERE status = 'approved'");
$revisedContractValue = $totalContractValue + $approvedVariationValue;
$approvedIpcValue = (float)$scalar("SELECT COALESCE(SUM(net_amount), 0) AS total FROM ipcs WHERE status IN ('approved', 'paid')");
$approvedAwaitingValue = (float)$scalar("SELECT COALESCE(SUM(net_amount), 0) AS total FROM ipcs WHERE status = 'approved'");
$approvedAwaitingCount = (int)$scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status = 'approved'");
$paidToDate = (float)$scalar("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'processed'");
$retentionHeld = (float)$scalar('SELECT COALESCE(SUM(total_held), 0) AS total FROM retention');
$retentionReleased = (float)$scalar('SELECT COALESCE(SUM(released_amount), 0) AS total FROM retention');
$retentionRemaining = max(0, $retentionHeld - $retentionReleased);
$ldTotal = (float)$scalar('SELECT COALESCE(SUM(total_ld), 0) AS total FROM liquidated_damages');
$budgetUtilisation = $revisedContractValue > 0 ? percentage(($paidToDate / $revisedContractValue) * 100) : 0;
$unpaidApprovedRatio = $approvedIpcValue > 0 ? percentage(($approvedAwaitingValue / $approvedIpcValue) * 100) : 0;
$cashBalance = max(0, $revisedContractValue - $paidToDate);
$financeHealthScore = max(0, 100 - min(45, $unpaidApprovedRatio) - min(25, (int)$approvedAwaitingCount * 5));

$zeroContractProjects = (int)$scalar('SELECT COUNT(*) AS total FROM projects WHERE COALESCE(contract_sum, 0) <= 0');
$overdueWithBalance = (int)$scalar(
    "SELECT COUNT(*) AS total
     FROM projects p
     LEFT JOIN (SELECT project_id, SUM(amount) AS paid FROM payments WHERE status = 'processed' GROUP BY project_id) pay ON pay.project_id = p.id
     WHERE p.est_delivery IS NOT NULL
       AND p.est_delivery < CURDATE()
       AND p.status <> 'completed'
       AND COALESCE(p.contract_sum, 0) > COALESCE(pay.paid, 0)"
);
$retentionDue = (int)$scalar("SELECT COUNT(*) AS total FROM retention WHERE release_date IS NOT NULL AND release_date <= CURDATE() AND COALESCE(total_held, 0) > COALESCE(released_amount, 0)");
$paidOverProgress = (int)$scalar(
    "SELECT COUNT(*) AS total
     FROM projects p
     LEFT JOIN (SELECT project_id, SUM(amount) AS paid FROM payments WHERE status = 'processed' GROUP BY project_id) pay ON pay.project_id = p.id
     WHERE COALESCE(p.contract_sum, 0) > 0
       AND ((COALESCE(pay.paid, 0) / p.contract_sum) * 100) > (COALESCE(p.pct_complete, 0) + 15)"
);

$financeSignals = [];
$addSignal = static function (array &$signals, string $severity, string $icon, string $title, string $text, string $path): void {
    $signals[] = [
        'severity' => $severity,
        'icon' => $icon,
        'title' => $title,
        'text' => $text,
        'href' => Url::to($path),
    ];
};

if ($approvedAwaitingCount > 0) {
    $addSignal($financeSignals, 'critical', 'fa-file-invoice-dollar', 'Approved IPCs awaiting payment', format_number($approvedAwaitingCount) . ' certificates worth ' . format_money($approvedAwaitingValue) . ' are ready for finance processing.', 'admin/finance/approved-ipcs.php');
}
if ($paidOverProgress > 0) {
    $addSignal($financeSignals, 'action', 'fa-scale-balanced', 'Spend ahead of progress', format_number($paidOverProgress) . ' projects have payment percentages materially ahead of physical progress.', 'admin/superadmin/reports.php');
}
if ($retentionDue > 0) {
    $addSignal($financeSignals, 'action', 'fa-vault', 'Retention release review', format_number($retentionDue) . ' retention records have reached release review date.', 'admin/finance/retention.php');
}
if ($overdueWithBalance > 0) {
    $addSignal($financeSignals, 'monitor', 'fa-calendar-xmark', 'Overdue contracts with exposure', format_number($overdueWithBalance) . ' overdue projects still carry unpaid balances.', 'admin/superadmin/projects.php');
}
if ($zeroContractProjects > 0) {
    $addSignal($financeSignals, 'monitor', 'fa-circle-exclamation', 'Contract values incomplete', format_number($zeroContractProjects) . ' projects have no contract value recorded.', 'admin/superadmin/projects.php');
}
if ($ldTotal > 0) {
    $addSignal($financeSignals, 'monitor', 'fa-gavel', 'Liquidated damages applied', format_money($ldTotal) . ' has been recorded across programme projects.', 'admin/finance/liquidated-damages.php');
}

$where = ["pay.status = 'processed'"];
$bindings = [];
if ($projectId > 0) {
    $where[] = 'p.id = ?';
    $bindings[] = $projectId;
}
if ($status !== '') {
    $where[] = 'i.status = ?';
    $bindings[] = $status;
}
if ($dateFrom !== '') {
    $where[] = 'pay.payment_date >= ?';
    $bindings[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'pay.payment_date <= ?';
    $bindings[] = $dateTo;
}
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR pay.reference_no LIKE ? OR pay.bank LIKE ? OR CAST(i.ipc_number AS CHAR) LIKE ? OR contractor.first_name LIKE ? OR contractor.last_name LIKE ?)';
    $term = '%' . $q . '%';
    array_push($bindings, $term, $term, $term, $term, $term, $term);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$paymentTotalRow = $rows(
    "SELECT COUNT(*) AS total
     FROM payments pay
     JOIN ipcs i ON i.id = pay.ipc_id
     JOIN projects p ON p.id = pay.project_id
     LEFT JOIN users contractor ON contractor.id = i.contractor_id
     {$whereSql}",
    $bindings
);
$paymentTotal = (int)($paymentTotalRow[0]['total'] ?? 0);
$paymentPages = max(1, (int)ceil($paymentTotal / $perPage));
$page = min($page, $paymentPages);
$offset = ($page - 1) * $perPage;

$recentPayments = $rows(
    "SELECT pay.*, i.ipc_number, i.status AS ipc_status, p.name AS project_name,
            CONCAT(processor.first_name, ' ', processor.last_name) AS processed_by_name,
            CONCAT(contractor.first_name, ' ', contractor.last_name) AS contractor_name
     FROM payments pay
     JOIN ipcs i ON i.id = pay.ipc_id
     JOIN projects p ON p.id = pay.project_id
     LEFT JOIN users processor ON processor.id = pay.processed_by
     LEFT JOIN users contractor ON contractor.id = i.contractor_id
     {$whereSql}
     ORDER BY pay.payment_date DESC, pay.id DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $bindings
);

$approvedAwaiting = $rows(
    "SELECT i.id, i.ipc_number, i.net_amount, i.approved_at, i.period_from, i.period_to,
            p.name AS project_name, p.slug AS project_slug,
            CONCAT(u.first_name, ' ', u.last_name) AS contractor_name
     FROM ipcs i
     JOIN projects p ON p.id = i.project_id
     JOIN users u ON u.id = i.contractor_id
     WHERE i.status = 'approved'
     ORDER BY i.approved_at ASC, i.updated_at ASC
     LIMIT 8"
);

$projectPositions = $rows(
    "SELECT p.id, p.name, p.slug, p.status, p.pct_complete, p.contract_sum,
            COALESCE(var.approved_variations, 0) AS approved_variations,
            COALESCE(ipc.approved_ipcs, 0) AS approved_ipcs,
            COALESCE(ipc.pending_ipcs, 0) AS pending_ipcs,
            COALESCE(pay.paid, 0) AS paid,
            COALESCE(ret.total_held, 0) AS retention_held,
            COALESCE(ret.released_amount, 0) AS retention_released,
            COALESCE(ld.total_ld, 0) AS total_ld
     FROM projects p
     LEFT JOIN (SELECT project_id, SUM(amount) AS approved_variations FROM variations WHERE status = 'approved' GROUP BY project_id) var ON var.project_id = p.id
     LEFT JOIN (
        SELECT project_id,
               SUM(CASE WHEN status IN ('approved','paid') THEN net_amount ELSE 0 END) AS approved_ipcs,
               SUM(CASE WHEN status IN ('submitted','certified','endorsed') THEN net_amount ELSE 0 END) AS pending_ipcs
        FROM ipcs GROUP BY project_id
     ) ipc ON ipc.project_id = p.id
     LEFT JOIN (SELECT project_id, SUM(amount) AS paid FROM payments WHERE status = 'processed' GROUP BY project_id) pay ON pay.project_id = p.id
     LEFT JOIN (SELECT project_id, SUM(total_held) AS total_held, SUM(released_amount) AS released_amount FROM retention GROUP BY project_id) ret ON ret.project_id = p.id
     LEFT JOIN (SELECT project_id, SUM(total_ld) AS total_ld FROM liquidated_damages GROUP BY project_id) ld ON ld.project_id = p.id
     ORDER BY (COALESCE(p.contract_sum,0) + COALESCE(var.approved_variations,0) - COALESCE(pay.paid,0)) DESC
     LIMIT 10"
);

$retentionRows = $rows(
    "SELECT r.*, p.name AS project_name,
            CONCAT(u.first_name, ' ', u.last_name) AS processed_by_name
     FROM retention r
     JOIN projects p ON p.id = r.project_id
     LEFT JOIN users u ON u.id = r.processed_by
     ORDER BY (COALESCE(r.total_held,0) - COALESCE(r.released_amount,0)) DESC, r.release_date ASC
     LIMIT 8"
);

$ldRows = $rows(
    "SELECT ld.*, p.name AS project_name, i.ipc_number
     FROM liquidated_damages ld
     JOIN projects p ON p.id = ld.project_id
     LEFT JOIN ipcs i ON i.id = ld.applied_to_ipc_id
     ORDER BY ld.created_at DESC, ld.id DESC
     LIMIT 8"
);

$ipcPipeline = $rows(
    "SELECT status, COUNT(*) AS total, COALESCE(SUM(net_amount), 0) AS value
     FROM ipcs
     GROUP BY status
     ORDER BY FIELD(status, 'draft','submitted','clerk-endorsed','certified','endorsed','approved','paid','rejected')"
);
$spendByConstituency = $rows(
    "SELECT c.name, COALESCE(SUM(p.contract_sum), 0) AS contract_sum, COALESCE(SUM(pay.paid), 0) AS paid
     FROM constituencies c
     LEFT JOIN projects p ON p.constituency_id = c.id
     LEFT JOIN (SELECT project_id, SUM(amount) AS paid FROM payments WHERE status = 'processed' GROUP BY project_id) pay ON pay.project_id = p.id
     GROUP BY c.id, c.name
     ORDER BY contract_sum DESC"
);
$monthlyPayments = $rows(
    "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month_label, COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'processed' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
     ORDER BY month_label ASC"
);

$maxProjectPosition = max(1, ...array_map(static fn (array $row): float => (float)$row['contract_sum'] + (float)$row['approved_variations'], $projectPositions ?: [['contract_sum' => 1, 'approved_variations' => 0]]));
$maxPipelineValue = max(1, ...array_map(static fn (array $row): float => (float)$row['value'], $ipcPipeline ?: [['value' => 1]]));
$maxConstituencyValue = max(1, ...array_map(static fn (array $row): float => max((float)$row['contract_sum'], (float)$row['paid']), $spendByConstituency ?: [['contract_sum' => 1, 'paid' => 0]]));
$maxMonthly = max(1, ...array_map(static fn (array $row): float => (float)$row['total'], $monthlyPayments ?: [['total' => 1]]));

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
?>

<?php if ($financeError): ?>
  <div class="alert alert--warning" role="alert">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    <div class="alert__content"><?= Security::e($financeError) ?></div>
  </div>
<?php endif; ?>

<section class="card sa-finance-hero">
  <div class="sa-finance-hero__copy">
    <div class="sa-finance-hero__meta">
      <span class="eyebrow"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Programme financial control</span>
      <span class="sa-date-chip"><i class="fa-regular fa-calendar" aria-hidden="true"></i><?= Security::e(date('l, d M Y')) ?></span>
    </div>
    <h2>Financial command overview</h2>
    <p>Contract exposure, IPC obligations, cash movement, retention balance and penalty pressure in one director-level view.</p>
    <div class="sa-finance-command-strip" aria-label="Finance command metrics">
      <span><small>Contracted</small><strong><?= Security::e(format_money($revisedContractValue)) ?></strong></span>
      <span><small>Paid</small><strong><?= Security::e(format_money($paidToDate)) ?></strong></span>
      <span><small>Outstanding</small><strong><?= Security::e(format_money($cashBalance)) ?></strong></span>
      <span><small>Retention</small><strong><?= Security::e(format_money($retentionRemaining)) ?></strong></span>
    </div>
  </div>
  <div class="sa-finance-hero__side">
    <div class="sa-finance-health">
      <span class="sa-panel-label">Financial health</span>
      <strong><?= Security::e(format_percentage($financeHealthScore)) ?></strong>
      <div class="sa-finance-health__bar"><span style="--bar-width: <?= (int)$financeHealthScore ?>%;"></span></div>
      <small><?= Security::e(format_percentage($budgetUtilisation)) ?> budget utilised Â· <?= Security::e(format_percentage($unpaidApprovedRatio)) ?> approved value unpaid</small>
    </div>
    <div class="sa-finance-hero__actions" aria-label="Financial shortcuts">
      <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> Approved IPCs</a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/process-payment.php')) ?>"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Process Payment</a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/budget-tracker.php')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Budget Tracker</a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/financial-reports.php')) ?>"><i class="fa-solid fa-file-export" aria-hidden="true"></i> Reports</a>
    </div>
  </div>
</section>

<section class="stat-grid sa-finance-stat-grid" aria-label="Financial summary">
  <article class="stat-widget sa-finance-stat"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span><span class="stat-widget__body"><span class="stat-widget__label">Contract Value</span><strong class="stat-widget__value"><?= Security::e(format_money($totalContractValue)) ?></strong><small class="stat-widget__trend"><?= Security::e(format_money($approvedVariationValue)) ?> approved variations</small><span class="sa-stat-meter"><i style="--bar-width: 100%;"></i></span></span></article>
  <article class="stat-widget sa-finance-stat"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></span><span class="stat-widget__body"><span class="stat-widget__label">Approved IPC Value</span><strong class="stat-widget__value"><?= Security::e(format_money($approvedIpcValue)) ?></strong><small class="stat-widget__trend">Approved and paid certificates</small><span class="sa-stat-meter"><i style="--bar-width: <?= $revisedContractValue > 0 ? percentage(($approvedIpcValue / $revisedContractValue) * 100) : 0 ?>%;"></i></span></span></article>
  <article class="stat-widget sa-finance-stat"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i></span><span class="stat-widget__body"><span class="stat-widget__label">Paid To Date</span><strong class="stat-widget__value"><?= Security::e(format_money($paidToDate)) ?></strong><small class="stat-widget__trend"><?= Security::e(format_percentage($budgetUtilisation)) ?> of revised contract value</small><span class="sa-stat-meter"><i style="--bar-width: <?= (int)$budgetUtilisation ?>%;"></i></span></span></article>
  <article class="stat-widget sa-finance-stat"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span><span class="stat-widget__body"><span class="stat-widget__label">Awaiting Payment</span><strong class="stat-widget__value"><?= Security::e(format_money($approvedAwaitingValue)) ?></strong><small class="stat-widget__trend"><?= Security::e(format_number($approvedAwaitingCount)) ?> approved IPCs</small><span class="sa-stat-meter sa-stat-meter--warning"><i style="--bar-width: <?= (int)$unpaidApprovedRatio ?>%;"></i></span></span></article>
  <article class="stat-widget sa-finance-stat"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-vault" aria-hidden="true"></i></span><span class="stat-widget__body"><span class="stat-widget__label">Retention Held</span><strong class="stat-widget__value"><?= Security::e(format_money($retentionHeld)) ?></strong><small class="stat-widget__trend"><?= Security::e(format_money($retentionReleased)) ?> released</small><span class="sa-stat-meter"><i style="--bar-width: <?= $retentionHeld > 0 ? percentage(($retentionRemaining / $retentionHeld) * 100) : 0 ?>%;"></i></span></span></article>
  <article class="stat-widget sa-finance-stat"><span class="stat-widget__icon stat-widget__icon--danger"><i class="fa-solid fa-gavel" aria-hidden="true"></i></span><span class="stat-widget__body"><span class="stat-widget__label">Liquidated Damages</span><strong class="stat-widget__value"><?= Security::e(format_money($ldTotal)) ?></strong><small class="stat-widget__trend">Applied across projects</small><span class="sa-stat-meter sa-stat-meter--danger"><i style="--bar-width: <?= $revisedContractValue > 0 ? percentage(($ldTotal / $revisedContractValue) * 100) : 0 ?>%;"></i></span></span></article>
</section>

<section class="card sa-finance-briefing">
  <div class="section-heading">
    <div>
      <h3>Financial Risk Briefing</h3>
      <p>System-generated signals from contract values, IPC liabilities, payments, retention and penalties.</p>
    </div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/reports.php')) ?>">Open Reports</a>
  </div>
  <?php if ($financeSignals): ?>
    <div class="sa-finance-signal-grid">
      <?php foreach ($financeSignals as $signal): ?>
        <a class="sa-finance-signal sa-finance-signal--<?= Security::e($signal['severity']) ?>" href="<?= Security::e($signal['href']) ?>">
          <span><i class="fa-solid <?= Security::e($signal['icon']) ?>" aria-hidden="true"></i></span>
          <div><strong><?= Security::e($signal['title']) ?></strong><small><?= Security::e($signal['text']) ?></small></div>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="sa-finance-clear">
      <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
      <div>
        <strong>No immediate financial escalation</strong>
        <p>Approved IPC queues, retention releases, LD exposure and contract-value completeness are clear.</p>
      </div>
      <span class="badge badge--success">Clear</span>
    </div>
  <?php endif; ?>
</section>

<section class="sa-finance-grid sa-finance-grid--charts" aria-label="Financial charts">
  <article class="card sa-finance-panel">
    <div class="section-heading"><div><h3>Project Financial Position</h3><p>Contract value, paid amount and remaining exposure by project.</p></div></div>
    <?php if ($projectPositions): ?>
      <div class="sa-finance-bars">
        <?php foreach (array_slice($projectPositions, 0, 8) as $project): ?>
          <?php
            $revised = (float)$project['contract_sum'] + (float)$project['approved_variations'];
            $paid = (float)$project['paid'];
            $balance = max(0, $revised - $paid);
            $paidPct = $revised > 0 ? percentage(($paid / $revised) * 100) : 0;
            $barWidth = percentage(($revised / $maxProjectPosition) * 100);
            $chartTip = $project['name']
                . ' | Revised value: ' . format_money($revised)
                . ' | Paid: ' . format_money($paid)
                . ' (' . format_percentage($paidPct) . ')'
                . ' | Balance: ' . format_money($balance);
          ?>
          <div class="sa-finance-bar-row" data-chart-tip="<?= Security::e($chartTip) ?>" title="<?= Security::e($chartTip) ?>">
            <div class="sa-finance-row-title"><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e(status_label($project['status'])) ?> &middot; <?= Security::e(format_percentage($project['pct_complete'])) ?> complete</small></div>
            <div class="sa-finance-row-meter"><div class="sa-finance-bar-track"><span style="--bar-width: <?= (int)$barWidth ?>%; --bar-paid: <?= (int)$paidPct ?>%;"></span></div><small><b><?= Security::e(format_percentage($paidPct)) ?></b> paid against revised value</small></div>
            <div class="sa-finance-row-money"><strong><?= Security::e(format_money($revised)) ?></strong><small><?= Security::e(format_money($balance)) ?> balance</small></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state sa-finance-empty"><span class="empty-state__icon"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><strong class="empty-state__title">No financial project data</strong><span class="empty-state__text">Projects and contract values will populate this panel.</span></div>
    <?php endif; ?>
  </article>

  <article class="card sa-finance-panel">
    <div class="section-heading"><div><h3>IPC Pipeline Value</h3><p>Certificate value currently sitting at each workflow stage.</p></div></div>
    <?php if ($ipcPipeline): ?>
      <div class="sa-finance-pipeline">
        <?php foreach ($ipcPipeline as $item): ?>
          <?php
            $width = percentage(((float)$item['value'] / $maxPipelineValue) * 100);
            $pipelineTip = status_label($item['status'])
                . ' | Value: ' . format_money($item['value'])
                . ' | IPC count: ' . format_number($item['total']);
          ?>
          <div class="sa-finance-pipeline__row" data-chart-tip="<?= Security::e($pipelineTip) ?>" title="<?= Security::e($pipelineTip) ?>">
            <span class="badge <?= Security::e(status_badge_class($item['status'])) ?>"><?= Security::e(status_label($item['status'])) ?></span>
            <div class="sa-finance-pipeline__track"><span style="--bar-width: <?= (int)$width ?>%;"></span></div>
            <strong><?= Security::e(format_money($item['value'])) ?></strong>
            <small><?= Security::e(format_number($item['total'])) ?> IPCs</small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state sa-finance-empty"><span class="empty-state__icon"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></span><strong class="empty-state__title">No IPC values yet</strong><span class="empty-state__text">Submitted certificates will appear here.</span></div>
    <?php endif; ?>
  </article>
</section>

<section class="sa-finance-grid sa-finance-grid--charts" aria-label="Spend charts">
  <article class="card sa-finance-panel">
    <div class="section-heading"><div><h3>Spend by Constituency</h3><p>Contract allocation and actual payment movement.</p></div></div>
    <div class="sa-finance-bars sa-finance-bars--compact">
      <?php foreach ($spendByConstituency as $row): ?>
        <?php
          $contractWidth = percentage(((float)$row['contract_sum'] / $maxConstituencyValue) * 100);
          $paidWidth = percentage(((float)$row['paid'] / $maxConstituencyValue) * 100);
          $constituencyTip = $row['name']
              . ' | Contract allocation: ' . format_money($row['contract_sum'])
              . ' | Paid: ' . format_money($row['paid'])
              . ' | Green bar is paid movement, dark bar is contract allocation.';
        ?>
        <div class="sa-finance-dual-row" data-chart-tip="<?= Security::e($constituencyTip) ?>" title="<?= Security::e($constituencyTip) ?>">
          <strong><?= Security::e($row['name']) ?></strong>
          <div>
            <span class="sa-finance-track sa-finance-track--contract"><i style="--bar-width: <?= (int)$contractWidth ?>%;"></i></span>
            <span class="sa-finance-track sa-finance-track--paid"><i style="--bar-width: <?= (int)$paidWidth ?>%;"></i></span>
          </div>
          <small><?= Security::e(format_money($row['paid'])) ?> paid</small>
        </div>
      <?php endforeach; ?>
    </div>
  </article>

  <article class="card sa-finance-panel">
    <div class="section-heading"><div><h3>Monthly Payment Trend</h3><p>Cash movement recorded over the last twelve months.</p></div></div>
    <?php if ($monthlyPayments): ?>
      <div class="sa-finance-months">
        <?php foreach ($monthlyPayments as $month): ?>
          <?php
            $height = max(8, percentage(((float)$month['total'] / $maxMonthly) * 100));
            $monthName = date('M Y', strtotime($month['month_label'] . '-01'));
            $monthTip = $monthName . ' | Processed payments: ' . format_money($month['total']);
          ?>
          <div class="sa-finance-month" data-chart-tip="<?= Security::e($monthTip) ?>" title="<?= Security::e($monthTip) ?>">
            <span style="--bar-height: <?= (int)$height ?>%;"></span>
            <strong><?= Security::e(format_money($month['total'])) ?></strong>
            <small><?= Security::e(date('M y', strtotime($month['month_label'] . '-01'))) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state sa-finance-empty"><span class="empty-state__icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span><strong class="empty-state__title">No payment trend yet</strong><span class="empty-state__text">Payments will build the monthly cash trend.</span></div>
    <?php endif; ?>
  </article>
</section>

<section class="card sa-finance-panel">
  <div class="section-heading">
    <div><h3>Approved IPCs Awaiting Payment</h3><p>Final-approved certificates that should move to finance processing.</p></div>
    <span class="badge badge--warning"><?= Security::e(format_money($approvedAwaitingValue)) ?> queued</span>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/approved-ipcs.php')) ?>">Open Queue</a>
  </div>
  <?php if ($approvedAwaiting): ?>
    <div class="data-table-wrap">
      <table class="data-table sa-finance-table">
        <thead><tr><th>#</th><th>IPC</th><th>Project</th><th>Contractor</th><th>Period</th><th>Net Amount</th><th>Approved</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($approvedAwaiting as $index => $ipc): ?>
            <tr>
              <td><?= Security::e(format_number($index + 1)) ?></td>
              <td><strong>IPC #<?= Security::e((string)$ipc['ipc_number']) ?></strong></td>
              <td><strong><?= Security::e($ipc['project_name']) ?></strong><small><?= Security::e($ipc['project_slug']) ?></small></td>
              <td><?= Security::e($ipc['contractor_name']) ?></td>
              <td><?= Security::e(format_date($ipc['period_from'])) ?> - <?= Security::e(format_date($ipc['period_to'])) ?></td>
              <td><strong><?= Security::e(format_money($ipc['net_amount'])) ?></strong></td>
              <td><?= Security::e($ipc['approved_at'] ? time_ago($ipc['approved_at']) : '-') ?></td>
              <td><a class="btn btn--sm btn--primary" href="<?= Security::e(Url::to('admin/finance/process-payment.php?ipc_id=' . (int)$ipc['id'])) ?>">Pay</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state sa-finance-empty sa-finance-empty--wide"><span class="empty-state__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><strong class="empty-state__title">No approved IPCs awaiting payment</strong><span class="empty-state__text">The finance payment queue is clear.</span></div>
  <?php endif; ?>
</section>

<section class="card sa-finance-panel">
  <div class="section-heading">
    <div><h3>Payment History</h3><p>Search and filter processed payments without changing the ledger.</p></div>
    <span class="badge badge--neutral"><?= Security::e(format_number($paymentTotal)) ?> records</span>
  </div>
  <form class="filter-bar sa-finance-filter" method="get">
    <label class="sa-finance-filter__search"><span>Search</span><input type="search" name="q" value="<?= Security::e($q) ?>" placeholder="IPC, reference, bank, contractor"></label>
    <label><span>Project</span><select name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= $projectId === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>IPC status</span><select name="status"><option value="">All statuses</option><?php foreach ($allowedStatuses as $option): ?><option value="<?= Security::e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= Security::e(status_label($option)) ?></option><?php endforeach; ?></select></label>
    <label><span>From</span><input type="date" name="date_from" value="<?= Security::e($dateFrom) ?>"></label>
    <label><span>To</span><input type="date" name="date_to" value="<?= Security::e($dateTo) ?>"></label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/financials.php')) ?>">Reset</a>
  </form>
  <?php if ($recentPayments): ?>
    <div class="data-table-wrap">
      <table class="data-table sa-finance-table">
        <thead><tr><th>#</th><th>IPC</th><th>Project</th><th>Contractor</th><th>Amount</th><th>Payment Date</th><th>Reference</th><th>Bank</th><th>Processed By</th></tr></thead>
        <tbody>
          <?php foreach ($recentPayments as $index => $payment): ?>
            <tr>
              <td><?= Security::e(format_number($offset + $index + 1)) ?></td>
              <td><strong>IPC #<?= Security::e((string)$payment['ipc_number']) ?></strong><small><?= Security::e(status_label($payment['ipc_status'])) ?></small></td>
              <td><?= Security::e($payment['project_name']) ?></td>
              <td><?= Security::e($payment['contractor_name'] ?: '-') ?></td>
              <td><strong><?= Security::e(format_money($payment['amount'])) ?></strong></td>
              <td><?= Security::e(format_date($payment['payment_date'])) ?></td>
              <td><?= Security::e($payment['reference_no'] ?: '-') ?></td>
              <td><?= Security::e($payment['bank'] ?: '-') ?></td>
              <td><?= Security::e($payment['processed_by_name'] ?: 'Finance') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= sa_financial_pagination($page, $paymentPages, $paymentTotal, $perPage) ?>
  <?php else: ?>
    <div class="empty-state sa-finance-empty sa-finance-empty--wide"><span class="empty-state__icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></span><strong class="empty-state__title">No payments match this view</strong><span class="empty-state__text">Processed payments will appear here after finance records them.</span></div>
  <?php endif; ?>
</section>

<section class="sa-finance-grid" aria-label="Retention and liquidated damages">
  <article class="card sa-finance-panel">
    <div class="section-heading"><div><h3>Retention Summary</h3><p>Held, released and remaining retention by project.</p></div><span class="badge badge--neutral"><?= Security::e(format_money($retentionRemaining)) ?> remaining</span><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/retention.php')) ?>">Retention</a></div>
    <?= sa_retention_table($retentionRows) ?>
  </article>
  <article class="card sa-finance-panel">
    <div class="section-heading"><div><h3>Liquidated Damages</h3><p>LD exposure applied against projects and IPCs.</p></div><span class="badge badge--neutral"><?= Security::e(format_money($ldTotal)) ?> recorded</span><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/finance/liquidated-damages.php')) ?>">LD Register</a></div>
    <?= sa_ld_table($ldRows) ?>
  </article>
</section>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>

<?php
function sa_financial_pagination(int $page, int $totalPages, int $total, int $perPage): string
{
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $to = min($total, $page * $perPage);
    ob_start();
?>
  <nav class="sa-project-pagination pagination" aria-label="Payment pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> payments</p>
    <div class="pagination__list">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(sa_financial_page_url(max(1, $page - 1))) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a class="pagination__link<?= $i === $page ? ' is-active' : '' ?>" href="<?= Security::e(sa_financial_page_url($i)) ?>"><?= Security::e(format_number($i)) ?></a>
<?php endfor; ?>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(sa_financial_page_url(min($totalPages, $page + 1))) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
<?php
    return ob_get_clean();
}

function sa_financial_page_url(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;
    return Url::to('admin/superadmin/financials.php' . ($query ? '?' . http_build_query($query) : ''));
}

function sa_retention_table(array $rows): string
{
    if (!$rows) {
        return '<div class="empty-state sa-finance-empty"><span class="empty-state__icon"><i class="fa-solid fa-vault" aria-hidden="true"></i></span><strong class="empty-state__title">No retention records yet</strong><span class="empty-state__text">Retention held and releases will appear here.</span></div>';
    }

    ob_start();
?>
  <div class="data-table-wrap"><table class="data-table sa-finance-table"><thead><tr><th>#</th><th>Project</th><th>Held</th><th>Released</th><th>Remaining</th><th>Release Date</th></tr></thead><tbody>
  <?php foreach ($rows as $index => $row): ?>
    <?php $remaining = max(0, (float)$row['total_held'] - (float)$row['released_amount']); ?>
    <tr><td><?= Security::e(format_number($index + 1)) ?></td><td><strong><?= Security::e($row['project_name']) ?></strong><small><?= Security::e($row['processed_by_name'] ?: 'Not processed') ?></small></td><td><?= Security::e(format_money($row['total_held'])) ?></td><td><?= Security::e(format_money($row['released_amount'])) ?></td><td><strong><?= Security::e(format_money($remaining)) ?></strong></td><td><?= Security::e(format_date($row['release_date'] ?? null)) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php
    return ob_get_clean();
}

function sa_ld_table(array $rows): string
{
    if (!$rows) {
        return '<div class="empty-state sa-finance-empty"><span class="empty-state__icon"><i class="fa-solid fa-gavel" aria-hidden="true"></i></span><strong class="empty-state__title">No liquidated damages recorded</strong><span class="empty-state__text">LD entries will appear when they are applied to projects or IPCs.</span></div>';
    }

    ob_start();
?>
  <div class="data-table-wrap"><table class="data-table sa-finance-table"><thead><tr><th>#</th><th>Project</th><th>Days</th><th>Rate/Day</th><th>Total LD</th><th>Applied IPC</th></tr></thead><tbody>
  <?php foreach ($rows as $index => $row): ?>
    <tr><td><?= Security::e(format_number($index + 1)) ?></td><td><strong><?= Security::e($row['project_name']) ?></strong><small><?= Security::e(safe_truncate($row['notes'] ?? '', 64)) ?></small></td><td><?= Security::e(format_number($row['days_overdue'])) ?></td><td><?= Security::e(format_money($row['rate_per_day'])) ?></td><td><strong><?= Security::e(format_money($row['total_ld'])) ?></strong></td><td><?= $row['ipc_number'] ? 'IPC #' . Security::e((string)$row['ipc_number']) : '-' ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php
    return ob_get_clean();
}
