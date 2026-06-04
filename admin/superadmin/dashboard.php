<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Superadmin Dashboard';
$pageDescription = 'Executive control dashboard for the Trans-Nzoia Affordable Housing Programme Tracker.';
$adminRole = 'superadmin';
$componentCss = ['cards', 'tables', 'charts'];
$componentScripts = ['notifications'];
$contentClass = 'sa-dashboard';
$pageKicker = 'County Director Command Centre';

$dashboardError = null;

$scalar = static function (string $sql, array $bindings = [], string $column = 'total', mixed $fallback = 0) use (&$dashboardError): mixed {
    try {
        $row = Database::fetch($sql, $bindings);
        return $row[$column] ?? $fallback;
    } catch (Throwable) {
        $dashboardError = 'Dashboard data is unavailable. Confirm MySQL is running and the AHPTC schema has been imported.';
        return $fallback;
    }
};

$rows = static function (string $sql, array $bindings = []) use (&$dashboardError): array {
    try {
        return Database::fetchAll($sql, $bindings);
    } catch (Throwable) {
        $dashboardError = 'Dashboard data is unavailable. Confirm MySQL is running and the AHPTC schema has been imported.';
        return [];
    }
};

$totalProjects = (int)$scalar('SELECT COUNT(*) AS total FROM projects');
$projectUnits = (int)$scalar('SELECT COALESCE(SUM(units), 0) AS total FROM projects');
$constituencyUnits = (int)$scalar('SELECT COALESCE(SUM(total_units), 0) AS total FROM constituencies');
$totalUnits = max($projectUnits, $constituencyUnits);
$pendingApprovals = (int)$scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status IN ('certified', 'endorsed')");
$activeUsers = (int)$scalar("SELECT COUNT(*) AS total FROM users WHERE status = 'active'");
$contractValue = (float)$scalar('SELECT COALESCE(SUM(contract_sum), 0) AS total FROM projects');
$paidToDate = (float)$scalar('SELECT COALESCE(SUM(amount), 0) AS total FROM payments');
$retentionHeld = (float)$scalar('SELECT COALESCE(SUM(total_held), 0) AS total FROM retention');
$boqValue = (float)$scalar('SELECT COALESCE(SUM(COALESCE(NULLIF(amount, 0), COALESCE(quantity, 0) * COALESCE(rate, 0))), 0) AS total FROM boq_items');
$boqRiskItems = (int)$scalar(
    'SELECT COUNT(*) AS total
     FROM boq_items
     WHERE COALESCE(certified_qty, 0) > COALESCE(quantity, 0)
        OR COALESCE(paid_qty, 0) > COALESCE(certified_qty, 0)
        OR ABS(COALESCE(amount, 0) - (COALESCE(quantity, 0) * COALESCE(rate, 0))) > 1'
);
$programmeProgress = (int)round((float)$scalar('SELECT COALESCE(AVG(pct_complete), 0) AS total FROM programme_tasks'));
$delayedProgrammeTasks = (int)$scalar(
    "SELECT COUNT(*) AS total
     FROM programme_tasks
     WHERE status NOT IN ('complete', 'cancelled')
       AND planned_end IS NOT NULL
       AND planned_end < CURDATE()"
);
$criticalProgrammeTasks = (int)$scalar('SELECT COUNT(*) AS total FROM programme_tasks WHERE critical_path = 1');
$unreadContactsCount = (int)$scalar("SELECT COUNT(*) AS total FROM contact_submissions WHERE is_read = 0 AND status <> 'archived'");
$activeSubscribers = (int)$scalar("SELECT COUNT(*) AS total FROM subscribers WHERE status = 'active'");
$subscribersToday = (int)$scalar("SELECT COUNT(*) AS total FROM subscribers WHERE DATE(subscribed_at) = CURDATE()");
$stalledProjects = (int)$scalar("SELECT COUNT(*) AS total FROM projects WHERE status = 'stalled'");
$overdueProjects = (int)$scalar("SELECT COUNT(*) AS total FROM projects WHERE est_delivery < CURDATE() AND status <> 'completed'");
$approvedAwaitingPayment = (int)$scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status = 'approved'");
$averageCompletion = (int)round((float)$scalar('SELECT COALESCE(AVG(pct_complete), 0) AS total FROM projects'));
$activeProjects = (int)$scalar("SELECT COUNT(*) AS total FROM projects WHERE status = 'active'");
$paymentRatio = $contractValue > 0 ? percentage(($paidToDate / $contractValue) * 100) : 0;
$activeProjectRatio = $totalProjects > 0 ? percentage(($activeProjects / $totalProjects) * 100) : 0;
$hour = (int)date('G');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$submittedIpcs = (int)$scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status = 'submitted'");
$overdueMilestones = (int)$scalar("SELECT COUNT(*) AS total FROM milestones WHERE target_date < CURDATE() AND status <> 'done'");
$openNcrs = (int)$scalar("SELECT COUNT(*) AS total FROM non_conformance_reports WHERE status <> 'closed'");
$openDefects = (int)$scalar("SELECT COUNT(*) AS total FROM defects WHERE status NOT IN ('resolved', 'closed')");
$recentSevereIncidents = (int)$scalar("SELECT COUNT(*) AS total FROM hs_incidents WHERE severity IN ('high', 'critical') AND incident_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$openGateways = (int)$scalar("SELECT COUNT(*) AS total FROM attendance_gateways WHERE date = CURDATE() AND is_open = 1");
$internTotal = (int)$scalar(
    "SELECT COUNT(*) AS total
     FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'intern' AND u.status = 'active'"
);
$internSignedInToday = (int)$scalar(
    "SELECT COUNT(DISTINCT ar.user_id) AS total
     FROM attendance_records ar
     JOIN users u ON u.id = ar.user_id
     JOIN roles r ON r.id = u.role_id
     WHERE ar.date = CURDATE() AND r.slug = 'intern'"
);
$internMissingToday = max(0, $internTotal - $internSignedInToday);
$outsideFenceToday = (int)$scalar("SELECT COUNT(*) AS total FROM attendance_records WHERE date = CURDATE() AND status = 'geo-fail'");
$totalSignedInToday = (int)$scalar("SELECT COUNT(DISTINCT user_id) AS total FROM attendance_records WHERE date = CURDATE()");

$projectStatuses = $rows('SELECT status, COUNT(*) AS total FROM projects GROUP BY status ORDER BY status');
$ipcStatuses = $rows('SELECT status, COUNT(*) AS total FROM ipcs GROUP BY status ORDER BY status');
$unitsByConstituency = $rows(
    'SELECT c.name, GREATEST(COALESCE(SUM(p.units), 0), COALESCE(c.total_units, 0)) AS units
     FROM constituencies c
     LEFT JOIN projects p ON p.constituency_id = c.id
     GROUP BY c.id, c.name, c.total_units
     ORDER BY c.name'
);
$budgetBurn = $rows(
    'SELECT p.name, COALESCE(p.contract_sum, 0) AS contract_sum, COALESCE(SUM(pay.amount), 0) AS paid
     FROM projects p
     LEFT JOIN payments pay ON pay.project_id = p.id
     GROUP BY p.id, p.name, p.contract_sum
     ORDER BY p.contract_sum DESC
     LIMIT 8'
);
$attendanceByProject = $rows(
    "SELECT p.name, COUNT(ar.id) AS total
     FROM projects p
     LEFT JOIN attendance_records ar ON ar.project_id = p.id AND ar.date = CURDATE()
     GROUP BY p.id, p.name
     HAVING total > 0
     ORDER BY total DESC
     LIMIT 8"
);
$bestAttendanceProject = $attendanceByProject[0] ?? null;
$internsMissingSignIn = $rows(
    "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name,
            COALESCE(p.name, 'Unassigned') AS project_name
     FROM users u
     JOIN roles r ON r.id = u.role_id
     LEFT JOIN project_assignments pa ON pa.user_id = u.id
     LEFT JOIN projects p ON p.id = pa.project_id
     LEFT JOIN attendance_records ar ON ar.user_id = u.id AND ar.date = CURDATE()
     WHERE r.slug = 'intern' AND u.status = 'active' AND ar.id IS NULL
     ORDER BY u.first_name, u.last_name
     LIMIT 6"
);
$recentActivity = $rows(
    "SELECT a.action, a.module, a.target_id, a.ip, a.created_at,
            COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') AS user_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 10"
);
$pendingApprovalRows = $rows(
    "SELECT i.id, i.ipc_number, i.net_amount, i.submitted_at, i.certified_at,
            p.name AS project_name,
            COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Contractor') AS contractor_name
     FROM ipcs i
     JOIN projects p ON p.id = i.project_id
     LEFT JOIN users u ON u.id = i.contractor_id
     WHERE i.status IN ('certified', 'endorsed')
     ORDER BY COALESCE(i.certified_at, i.submitted_at, i.created_at) DESC
     LIMIT 8"
);
$unreadContacts = $rows(
    "SELECT id, name, subject, created_at
     FROM contact_submissions
     WHERE is_read = 0 AND status <> 'archived'
     ORDER BY created_at DESC
     LIMIT 5"
);
$announcements = $rows(
    'SELECT id, title, is_pinned, created_at, expires_at
     FROM announcements
     WHERE expires_at IS NULL OR expires_at >= NOW()
     ORDER BY is_pinned DESC, created_at DESC
     LIMIT 5'
);
$projectProgress = $rows(
    'SELECT p.name, p.status, p.pct_complete, p.est_delivery, c.name AS constituency
     FROM projects p
     JOIN constituencies c ON c.id = p.constituency_id
     ORDER BY p.updated_at DESC
     LIMIT 6'
);

$executiveSignals = [];
$addSignal = static function (array &$signals, string $severity, string $icon, string $title, string $text, string $path): void {
    $signals[] = [
        'severity' => $severity,
        'icon' => $icon,
        'title' => $title,
        'text' => $text,
        'href' => Url::to($path),
    ];
};

if ($pendingApprovals > 0) {
    $addSignal($executiveSignals, 'critical', 'fa-clipboard-check', 'Final approvals waiting', format_number($pendingApprovals) . ' IPCs need superadmin review.', 'admin/superadmin/ipcs.php?payment_readiness=approval-ready');
}
if ($approvedAwaitingPayment > 0) {
    $addSignal($executiveSignals, 'action', 'fa-credit-card', 'Approved IPCs unpaid', format_number($approvedAwaitingPayment) . ' approved IPCs are waiting for payment processing.', 'admin/superadmin/financials.php');
}
if ($boqRiskItems > 0) {
    $addSignal($executiveSignals, 'critical', 'fa-list-check', 'BOQ quantity risk', format_number($boqRiskItems) . ' BOQ items need quantity or payment review.', 'admin/superadmin/boq.php?risk=overpaid');
}
if ($delayedProgrammeTasks > 0) {
    $addSignal($executiveSignals, 'critical', 'fa-chart-gantt', 'Programme delays detected', format_number($delayedProgrammeTasks) . ' tasks are past their planned finish date.', 'admin/superadmin/programme-of-works.php?delay=delayed');
}
if ($submittedIpcs > 0) {
    $addSignal($executiveSignals, 'monitor', 'fa-file-invoice', 'IPC submissions entering workflow', format_number($submittedIpcs) . ' IPCs are waiting for consultant certification.', 'admin/superadmin/ipcs.php?status=submitted');
}
if ($stalledProjects > 0 || $overdueProjects > 0) {
    $addSignal($executiveSignals, 'critical', 'fa-road-barrier', 'Delivery risk detected', format_number($stalledProjects) . ' stalled projects and ' . format_number($overdueProjects) . ' overdue deliveries.', 'admin/superadmin/projects.php');
}
if ($overdueMilestones > 0) {
    $addSignal($executiveSignals, 'action', 'fa-calendar-xmark', 'Milestones overdue', format_number($overdueMilestones) . ' milestones have passed target date.', 'admin/superadmin/projects.php');
}
if ($internMissingToday > 0) {
    $addSignal($executiveSignals, 'action', 'fa-user-clock', 'Intern attendance gap', format_number($internMissingToday) . ' active interns have not signed in today.', 'admin/superadmin/attendance.php');
}
if ($outsideFenceToday > 0) {
    $addSignal($executiveSignals, 'monitor', 'fa-location-dot', 'GPS attendance flags', format_number($outsideFenceToday) . ' sign-ins are outside the project geo-fence.', 'admin/superadmin/attendance.php');
}
if ($openGateways > 0) {
    $addSignal($executiveSignals, 'monitor', 'fa-door-open', 'Attendance gateways open', format_number($openGateways) . ' site gateways are currently open.', 'admin/superadmin/attendance.php');
}
if ($openNcrs > 0 || $openDefects > 0 || $recentSevereIncidents > 0) {
    $addSignal($executiveSignals, 'critical', 'fa-shield-halved', 'Quality and safety attention', format_number($openNcrs) . ' NCRs, ' . format_number($openDefects) . ' defects, ' . format_number($recentSevereIncidents) . ' severe incidents.', 'admin/superadmin/reports.php');
}
if ($unreadContactsCount > 0) {
    $addSignal($executiveSignals, 'action', 'fa-envelope-open-text', 'Citizen inbox needs response', format_number($unreadContactsCount) . ' unread public submissions.', 'admin/superadmin/contact-inbox.php');
}
if ($subscribersToday > 0) {
    $addSignal($executiveSignals, 'monitor', 'fa-envelope', 'New programme subscribers', format_number($subscribersToday) . ' people subscribed today.', 'admin/superadmin/subscribers.php');
}

$executiveSignals = array_slice($executiveSignals, 0, 6);

$statusRowsToChart = static function (array $items, string $labelKey = 'status', string $valueKey = 'total'): array {
    return [
        'labels' => array_map(static fn (array $row): string => status_label((string)($row[$labelKey] ?? 'Unknown')), $items),
        'values' => array_map(static fn (array $row): int => (int)($row[$valueKey] ?? 0), $items),
    ];
};

$chartData = [
    'projectStatus' => $statusRowsToChart($projectStatuses),
    'ipcPipeline' => $statusRowsToChart($ipcStatuses),
    'unitsByConstituency' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['name'], $unitsByConstituency),
        'values' => array_map(static fn (array $row): int => (int)$row['units'], $unitsByConstituency),
    ],
    'budgetBurn' => [
        'labels' => array_map(static fn (array $row): string => safe_truncate((string)$row['name'], 28), $budgetBurn),
        'contract' => array_map(static fn (array $row): float => (float)$row['contract_sum'], $budgetBurn),
        'paid' => array_map(static fn (array $row): float => (float)$row['paid'], $budgetBurn),
    ],
    'attendanceByProject' => [
        'labels' => array_map(static fn (array $row): string => safe_truncate((string)$row['name'], 24), $attendanceByProject),
        'values' => array_map(static fn (array $row): int => (int)$row['total'], $attendanceByProject),
    ],
];

$jsonChartData = json_encode($chartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$hasProjectStatusChart = array_sum($chartData['projectStatus']['values']) > 0;
$hasIpcChart = array_sum($chartData['ipcPipeline']['values']) > 0;
$hasUnitsChart = array_sum($chartData['unitsByConstituency']['values']) > 0;
$hasBudgetChart = array_sum($chartData['budgetBurn']['contract']) > 0 || array_sum($chartData['budgetBurn']['paid']) > 0;
$hasAttendanceChart = array_sum($chartData['attendanceByProject']['values']) > 0;

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
?>

<?php if ($dashboardError): ?>
  <div class="alert alert--warning" role="alert">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    <div class="alert__content"><?= Security::e($dashboardError) ?></div>
  </div>
<?php endif; ?>

<section class="sa-hero" aria-label="Executive command centre">
  <div class="sa-hero__identity">
    <div class="sa-hero__meta">
      <span class="sa-live-chip"><span class="badge-dot"></span> Trans-Nzoia Affordable Housing Programme</span>
      <span class="sa-date-chip"><i class="fa-regular fa-calendar" aria-hidden="true"></i><?= Security::e(date('l, d M Y')) ?></span>
    </div>
    <h2><?= Security::e($timeGreeting) ?>, <?= Security::e(current_user_name()) ?></h2>
    <p class="sa-hero__summary">Monitor delivery, approvals, finance exposure and public response from one county command view.</p>
  </div>

  <div class="sa-pulse-panel" aria-label="Programme pulse">
    <span class="sa-panel-label">Programme pulse</span>
    <div class="sa-pulse-grid">
      <span class="sa-pulse-card"><strong><?= Security::e(format_percentage($averageCompletion)) ?></strong><small>Average completion</small></span>
      <span class="sa-pulse-card"><strong><?= Security::e(format_percentage($paymentRatio)) ?></strong><small>Budget paid</small></span>
      <span class="sa-pulse-card"><strong><?= Security::e(format_percentage($activeProjectRatio)) ?></strong><small>Active projects</small></span>
    </div>
  </div>

  <div class="sa-action-panel" aria-label="Quick actions">
    <span class="sa-panel-label">Quick actions</span>
    <div class="sa-action-grid">
      <a class="btn btn--primary" href="<?= Security::e(Url::to('admin/superadmin/project-create.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>New Project</span></a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/user-create.php')) ?>"><i class="fa-solid fa-user-plus" aria-hidden="true"></i><span>New User</span></a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/boq.php')) ?>"><i class="fa-solid fa-list-check" aria-hidden="true"></i><span>BOQ Centre</span></a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/programme-of-works.php')) ?>"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i><span>Programme</span></a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/news-editor.php')) ?>"><i class="fa-solid fa-newspaper" aria-hidden="true"></i><span>News Article</span></a>
      <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms.php')) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Open CMS</span></a>
    </div>
  </div>
</section>

<section class="card sa-briefing" aria-label="Live executive briefing">
  <div class="card__header">
    <div>
      <h2 class="card__title">Live Executive Briefing</h2>
      <p class="card__subtitle">System-generated signals from approvals, delivery, attendance, safety and citizen response.</p>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/audit-log.php')) ?>">Audit Log</a>
  </div>
<?php if ($executiveSignals === []): ?>
  <div class="sa-clear-brief">
    <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
    <div>
      <strong>All priority signals are clear</strong>
      <small>No approval, attendance, delivery, quality or citizen response item needs immediate escalation.</small>
    </div>
  </div>
<?php else: ?>
  <div class="sa-signal-grid">
<?php foreach ($executiveSignals as $signal): ?>
    <a class="sa-signal sa-signal--<?= Security::e($signal['severity']) ?>" href="<?= Security::e($signal['href']) ?>">
      <span class="sa-signal__icon"><i class="fa-solid <?= Security::e($signal['icon']) ?>" aria-hidden="true"></i></span>
      <span class="sa-signal__copy">
        <strong><?= Security::e($signal['title']) ?></strong>
        <small><?= Security::e($signal['text']) ?></small>
      </span>
      <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
    </a>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>

<section class="stat-grid stat-grid--4">
  <article class="stat-widget stat-widget--info">
    <span class="stat-widget__icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($totalProjects)) ?></strong><span class="stat-widget__label">Total Projects</span><span class="stat-widget__trend"><?= Security::e(format_number($activeProjects)) ?> active right now</span></span>
  </article>
  <article class="stat-widget stat-widget--success">
    <span class="stat-widget__icon"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($totalUnits)) ?></strong><span class="stat-widget__label">Units Targeted</span><span class="stat-widget__trend">Across 7 constituencies</span></span>
  </article>
  <article class="stat-widget stat-widget--warning">
    <span class="stat-widget__icon"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($pendingApprovals)) ?></strong><span class="stat-widget__label">Final Approvals</span><span class="stat-widget__trend">Certified and endorsed IPC queue</span></span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($activeUsers)) ?></strong><span class="stat-widget__label">Active Users</span><span class="stat-widget__trend">Portal accounts enabled</span></span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_money($contractValue)) ?></strong><span class="stat-widget__label">Contract Value</span><span class="stat-widget__trend">Approved project sum</span></span>
  </article>
  <article class="stat-widget stat-widget--success">
    <span class="stat-widget__icon"><i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_money($paidToDate)) ?></strong><span class="stat-widget__label">Paid To Date</span><span class="stat-widget__trend"><?= Security::e(format_percentage($paymentRatio)) ?> of contract value</span></span>
  </article>
  <article class="stat-widget stat-widget--info">
    <span class="stat-widget__icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_money($retentionHeld)) ?></strong><span class="stat-widget__label">Retention Held</span><span class="stat-widget__trend">Across paid IPCs</span></span>
  </article>
  <article class="stat-widget stat-widget--info">
    <span class="stat-widget__icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_money($boqValue)) ?></strong><span class="stat-widget__label">BOQ Value</span><span class="stat-widget__trend"><?= Security::e(format_number($boqRiskItems)) ?> quantity risks</span></span>
  </article>
  <article class="stat-widget stat-widget--warning">
    <span class="stat-widget__icon"><i class="fa-solid fa-chart-gantt" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_percentage($programmeProgress)) ?></strong><span class="stat-widget__label">Programme Progress</span><span class="stat-widget__trend"><?= Security::e(format_number($delayedProgrammeTasks)) ?> delayed, <?= Security::e(format_number($criticalProgrammeTasks)) ?> critical</span></span>
  </article>
  <article class="stat-widget stat-widget--danger">
    <span class="stat-widget__icon"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($unreadContactsCount)) ?></strong><span class="stat-widget__label">Unread Contacts</span><span class="stat-widget__trend">Citizen inbox attention</span></span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
    <span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($activeSubscribers)) ?></strong><span class="stat-widget__label">Subscribers</span><span class="stat-widget__trend"><?= Security::e(format_number($subscribersToday)) ?> joined today</span></span>
  </article>
</section>

<section class="sa-attendance-panel">
  <article class="card sa-attendance-summary">
    <div class="card__header">
      <div>
        <h2 class="card__title">Attendance & Intern Oversight</h2>
        <p class="card__subtitle">Today&apos;s sign-ins, site coverage and intern compliance.</p>
      </div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/attendance.php')) ?>">Open Attendance</a>
    </div>
    <div class="sa-attendance-metrics">
      <span><i class="fa-solid fa-user-check" aria-hidden="true"></i><strong><?= Security::e(format_number($totalSignedInToday)) ?></strong><small>Total signed in today</small></span>
      <span><i class="fa-solid fa-user-graduate" aria-hidden="true"></i><strong><?= Security::e(format_number($internSignedInToday)) ?> of <?= Security::e(format_number($internTotal)) ?></strong><small>Interns signed in</small></span>
      <span><i class="fa-solid fa-user-clock" aria-hidden="true"></i><strong><?= Security::e(format_number($internMissingToday)) ?></strong><small>Interns missing sign-in</small></span>
      <span><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i><strong><?= Security::e(format_number($outsideFenceToday)) ?></strong><small>GPS flags</small></span>
    </div>
    <div class="sa-chart-wrap sa-chart-wrap--compact<?= !$hasAttendanceChart ? ' is-empty' : '' ?>">
      <canvas id="attendanceProjectChart"></canvas>
<?php if (!$hasAttendanceChart): ?><div class="sa-chart-empty"><i class="fa-solid fa-user-check" aria-hidden="true"></i><strong>No sign-ins recorded today</strong><span>Attendance will populate after site gateway activity.</span></div><?php endif; ?>
    </div>
  </article>

  <article class="card sa-intern-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Intern Exceptions</h2>
        <p class="card__subtitle">Active interns without a sign-in today.</p>
      </div>
<?php if ($bestAttendanceProject): ?>
      <span class="badge badge--success">Best: <?= Security::e(safe_truncate((string)$bestAttendanceProject['name'], 18)) ?></span>
<?php endif; ?>
    </div>
<?php if ($internsMissingSignIn === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><h3 class="empty-state__title">No intern exceptions</h3><p class="empty-state__text">All active interns have signed in, or no active interns are configured.</p></div>
<?php else: ?>
    <div class="sa-list">
<?php foreach ($internsMissingSignIn as $intern): ?>
      <a class="sa-list-item" href="<?= Security::e(Url::to('admin/superadmin/attendance.php')) ?>">
        <span><strong><?= Security::e($intern['name']) ?></strong><small><?= Security::e($intern['project_name']) ?></small></span>
        <span class="badge badge--warning">Missing</span>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </article>
</section>

<section class="sa-chart-grid">
  <article class="card sa-chart-card">
    <div class="card__header"><div><h2 class="card__title">Projects by Status</h2><p class="card__subtitle">Planning, active, stalled and completed distribution.</p></div></div>
    <div class="sa-chart-wrap<?= !$hasProjectStatusChart ? ' is-empty' : '' ?>">
      <canvas id="projectStatusChart"></canvas>
<?php if (!$hasProjectStatusChart): ?><div class="sa-chart-empty"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i><strong>No project data yet</strong><span>Create projects to activate this chart.</span></div><?php endif; ?>
    </div>
  </article>
  <article class="card sa-chart-card">
    <div class="card__header"><div><h2 class="card__title">IPC Pipeline</h2><p class="card__subtitle">Payment certificates across the workflow.</p></div></div>
    <div class="sa-chart-wrap<?= !$hasIpcChart ? ' is-empty' : '' ?>">
      <canvas id="ipcPipelineChart"></canvas>
<?php if (!$hasIpcChart): ?><div class="sa-chart-empty"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i><strong>No IPCs yet</strong><span>Submitted IPCs will populate the pipeline.</span></div><?php endif; ?>
    </div>
  </article>
  <article class="card sa-chart-card sa-chart-card--wide">
    <div class="card__header"><div><h2 class="card__title">Budget Burn</h2><p class="card__subtitle">Contract value versus paid amount for major projects.</p></div></div>
    <div class="sa-chart-wrap<?= !$hasBudgetChart ? ' is-empty' : '' ?>">
      <canvas id="budgetBurnChart"></canvas>
<?php if (!$hasBudgetChart): ?><div class="sa-chart-empty"><i class="fa-solid fa-chart-column" aria-hidden="true"></i><strong>No budget data yet</strong><span>Contract sums and payments will appear here.</span></div><?php endif; ?>
    </div>
  </article>
  <article class="card sa-chart-card">
    <div class="card__header"><div><h2 class="card__title">Units by Constituency</h2><p class="card__subtitle">Targeted units across Trans-Nzoia.</p></div></div>
    <div class="sa-chart-wrap<?= !$hasUnitsChart ? ' is-empty' : '' ?>">
      <canvas id="unitsChart"></canvas>
<?php if (!$hasUnitsChart): ?><div class="sa-chart-empty"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><strong>No unit targets yet</strong><span>Seed constituency or project unit targets.</span></div><?php endif; ?>
    </div>
  </article>
</section>

<section class="sa-ops-grid">
  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">Pending Final Approvals</h2><p class="card__subtitle">Certified and endorsed IPCs waiting for superadmin action.</p></div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/ipcs.php?payment_readiness=approval-ready')) ?>">Open IPC Centre</a>
    </div>
<?php if ($pendingApprovalRows === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span><h3 class="empty-state__title">No IPCs waiting</h3><p class="empty-state__text">The final approval queue is clear.</p></div>
<?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>IPC #</th><th>Project</th><th>Contractor</th><th>Net Amount</th><th>Certified</th><th>Action</th></tr></thead>
        <tbody>
<?php foreach ($pendingApprovalRows as $ipc): ?>
          <tr>
            <td><?= Security::e((string)$ipc['ipc_number']) ?></td>
            <td><?= Security::e($ipc['project_name']) ?></td>
            <td><?= Security::e($ipc['contractor_name']) ?></td>
            <td><?= Security::e(format_money($ipc['net_amount'])) ?></td>
            <td><?= Security::e(format_datetime($ipc['certified_at'] ?: $ipc['submitted_at'])) ?></td>
            <td><a class="btn btn--primary btn--sm" href="<?= Security::e(Url::to('admin/superadmin/ipc-detail.php?id=' . (int)$ipc['id'])) ?>">Review</a></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php endif; ?>
  </article>

  <article class="card">
    <div class="card__header"><div><h2 class="card__title">Recent Activity</h2><p class="card__subtitle">Latest audit trail events.</p></div></div>
<?php if ($recentActivity === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><h3 class="empty-state__title">No activity yet</h3><p class="empty-state__text">Audit entries will appear here after users start working.</p></div>
<?php else: ?>
    <div class="sa-activity-list">
<?php foreach ($recentActivity as $activity): ?>
      <div class="sa-activity-item">
        <span class="avatar avatar--sm"><?= Security::e(user_initials($activity['user_name'])) ?></span>
        <div>
          <strong><?= Security::e($activity['user_name']) ?></strong>
          <p><?= Security::e(status_label($activity['action'])) ?> in <?= Security::e(status_label($activity['module'])) ?></p>
        </div>
        <time><?= Security::e(time_ago($activity['created_at'])) ?></time>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </article>
</section>

<section class="sa-ops-grid">
  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">Project Progress Watch</h2><p class="card__subtitle">Recently updated projects.</p></div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>">All Projects</a>
    </div>
<?php if ($projectProgress === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span><h3 class="empty-state__title">No projects found</h3><p class="empty-state__text">Create your first project to populate this panel.</p></div>
<?php else: ?>
    <div class="sa-progress-list">
<?php foreach ($projectProgress as $project): ?>
<?php $pct = percentage($project['pct_complete']); ?>
      <div class="sa-project-card">
        <div class="sa-project-card__top">
          <div><strong><?= Security::e($project['name']) ?></strong><small><?= Security::e($project['constituency']) ?> · <?= Security::e(format_date($project['est_delivery'])) ?></small></div>
          <span class="badge <?= Security::e(status_badge_class($project['status'])) ?>"><?= Security::e(status_label($project['status'])) ?></span>
        </div>
        <div class="progress" aria-label="<?= Security::e($project['name']) ?> progress"><span class="progress__bar" style="width: <?= Security::e((string)$pct) ?>%"></span></div>
        <span class="progress__label"><?= Security::e(format_percentage($pct)) ?> complete</span>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </article>

  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">Citizen Contact Inbox</h2><p class="card__subtitle">Unread public submissions.</p></div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/contact-inbox.php')) ?>">Open Inbox</a>
    </div>
<?php if ($unreadContacts === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span><h3 class="empty-state__title">Inbox is clear</h3><p class="empty-state__text">Unread submissions will appear here.</p></div>
<?php else: ?>
    <div class="sa-list">
<?php foreach ($unreadContacts as $contact): ?>
      <a class="sa-list-item" href="<?= Security::e(Url::to('admin/superadmin/contact-inbox.php')) ?>">
        <span><strong><?= Security::e($contact['name']) ?></strong><small><?= Security::e($contact['subject'] ?: 'No subject') ?></small></span>
        <time><?= Security::e(time_ago($contact['created_at'])) ?></time>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </article>

  <article class="card">
    <div class="card__header">
      <div><h2 class="card__title">System Announcements</h2><p class="card__subtitle">Pinned announcements appear first.</p></div>
      <a class="btn btn--outline btn--sm" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>">Manage</a>
    </div>
<?php if ($announcements === []): ?>
    <div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span><h3 class="empty-state__title">No active announcements</h3><p class="empty-state__text">Create announcements for staff and stakeholders.</p></div>
<?php else: ?>
    <div class="sa-list">
<?php foreach ($announcements as $announcement): ?>
      <a class="sa-list-item" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>">
        <span><strong><?= Security::e($announcement['title']) ?></strong><small><?= ((int)$announcement['is_pinned'] === 1) ? 'Pinned · ' : '' ?><?= Security::e(format_date($announcement['created_at'])) ?></small></span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </article>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
  'use strict';
  var data = <?= $jsonChartData ?: '{}' ?>;
  var root = getComputedStyle(document.documentElement);
  var css = function (name) { return root.getPropertyValue(name).trim(); };
  var palette = [css('--admin-primary'), css('--admin-lime'), css('--admin-info'), css('--admin-warning'), css('--admin-success'), css('--admin-danger')];
  var gridColor = css('--admin-border');
  var textColor = css('--admin-text-muted');

  function chart(id, config) {
    var canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return;
    return new Chart(canvas, config);
  }

  function doughnut(id, dataset) {
    chart(id, {
      type: 'doughnut',
      data: { labels: dataset.labels || [], datasets: [{ data: dataset.values || [], backgroundColor: palette, borderWidth: 0 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: textColor, usePointStyle: true } } }, cutout: '68%' }
    });
  }

  function bar(id, labels, datasets) {
    chart(id, {
      type: 'bar',
      data: { labels: labels || [], datasets: datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: textColor, usePointStyle: true } } },
        scales: {
          x: { ticks: { color: textColor }, grid: { color: gridColor } },
          y: { ticks: { color: textColor }, grid: { color: gridColor }, beginAtZero: true }
        }
      }
    });
  }

  doughnut('projectStatusChart', data.projectStatus || {});
  doughnut('ipcPipelineChart', data.ipcPipeline || {});
  bar('unitsChart', (data.unitsByConstituency || {}).labels, [{ label: 'Units', data: (data.unitsByConstituency || {}).values || [], backgroundColor: css('--admin-primary') }]);
  bar('attendanceProjectChart', (data.attendanceByProject || {}).labels, [{ label: 'Signed in today', data: (data.attendanceByProject || {}).values || [], backgroundColor: css('--admin-success') }]);
  bar('budgetBurnChart', (data.budgetBurn || {}).labels, [
    { label: 'Contract Sum', data: (data.budgetBurn || {}).contract || [], backgroundColor: css('--admin-primary') },
    { label: 'Paid', data: (data.budgetBurn || {}).paid || [], backgroundColor: css('--admin-lime') }
  ]);
}());
</script>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>
