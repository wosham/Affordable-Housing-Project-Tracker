<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Attendance Command Centre';
$pageDescription = 'Global attendance, geo-fence and site gateway monitoring for the AHPTC programme.';
$adminRole = 'superadmin';
$contentClass = 'sa-attendance-page';
$componentCss = ['attendance'];
$pageKicker = 'Geo Attendance Control';
$csrfForm = 'superadmin_attendance_gateway';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/attendance.php'));
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));
    $actionDate = sa_attendance_clean_date($_POST['date'] ?? date('Y-m-d'));
    $closeTime = sa_attendance_clean_time($_POST['closes_at'] ?? SystemConfig::text('attendance.signin_end', '18:00'));
    $reason = trim(Security::cleanString((string)($_POST['reason'] ?? '')));
    $actorId = (int)Auth::id();

    if (in_array($action, ['review_accept', 'review_flag'], true)) {
        $recordId = Security::cleanInt($_POST['record_id'] ?? 0);
        if ($recordId <= 0) {
            Session::flash('error', 'Choose an attendance record to review.');
            Response::redirect(Url::to('admin/superadmin/attendance.php?date=' . urlencode($actionDate)));
        }

        $reviewStatus = $action === 'review_accept' ? 'accepted' : 'flagged';
        $note = $reason !== '' ? $reason : ($reviewStatus === 'accepted' ? 'Accepted by County Director.' : 'Flagged by County Director.');
        try {
            if (sa_attendance_has_review_notes()) {
                Database::query(
                    'UPDATE attendance_records SET review_status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?',
                    [$reviewStatus, $actorId, $note, $recordId]
                );
            } else {
                Database::query(
                    'UPDATE attendance_records SET review_status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?',
                    [$reviewStatus, $actorId, $recordId]
                );
            }
            Logger::log('admin-review-attendance', 'attendance_records', $recordId, ['review_status' => $reviewStatus, 'reason' => $note]);
            Session::flash('status', 'Attendance record reviewed.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('admin/superadmin/attendance.php?date=' . urlencode($actionDate)));
    }

    if ($reason === '') {
        Session::flash('error', 'Add a reason before using County Director gateway controls.');
        Response::redirect(Url::to('admin/superadmin/attendance.php?date=' . urlencode($actionDate)));
    }

    try {
        if ($action === 'open_project') {
            $targetProjectId = Security::cleanInt($_POST['project_id'] ?? 0);
            if ($targetProjectId <= 0) {
                throw new RuntimeException('Choose a project gateway to open.');
            }
            $gatewayId = AttendanceGateway::openProjectByAdmin($actorId, $targetProjectId, $actionDate, $closeTime, sa_attendance_override_note($reason));
            Logger::log('admin-open-gateway', 'attendance_gateways', $gatewayId, ['project_id' => $targetProjectId, 'date' => $actionDate, 'reason' => $reason]);
            sa_attendance_notify_project_clerks($targetProjectId, 'Gateway opened by County Director', 'County Director opened attendance for your project. Reason: ' . $reason);
            Session::flash('status', 'Project attendance gateway opened.');
        } elseif ($action === 'close_project') {
            $targetProjectId = Security::cleanInt($_POST['project_id'] ?? 0);
            if ($targetProjectId <= 0) {
                throw new RuntimeException('Choose a project gateway to close.');
            }
            $gatewayId = AttendanceGateway::closeProjectByAdmin($actorId, $targetProjectId, $actionDate, sa_attendance_override_note($reason));
            Logger::log('admin-close-gateway', 'attendance_gateways', $gatewayId, ['project_id' => $targetProjectId, 'date' => $actionDate, 'reason' => $reason]);
            sa_attendance_notify_project_clerks($targetProjectId, 'Gateway closed by County Director', 'County Director closed attendance for your project. Reason: ' . $reason);
            Session::flash('status', 'Project attendance gateway closed.');
        } elseif ($action === 'open_all_projects') {
            $gatewayIds = AttendanceGateway::openAllProjectsByAdmin($actorId, $actionDate, $closeTime, sa_attendance_override_note($reason));
            Logger::log('admin-open-all-gateways', 'attendance_gateways', 0, ['date' => $actionDate, 'count' => count($gatewayIds), 'reason' => $reason]);
            Notification::pushRole('clerk', 'attendance', 'All project gateways opened by County Director', 'County Director opened attendance for all project sites. Reason: ' . $reason, 'admin/clerk/attendance-gateway.php');
            Session::flash('status', 'All project attendance gateways opened.');
        } elseif ($action === 'close_all_projects') {
            $gatewayIds = AttendanceGateway::closeAllProjectsByAdmin($actorId, $actionDate, sa_attendance_override_note($reason));
            Logger::log('admin-close-all-gateways', 'attendance_gateways', 0, ['date' => $actionDate, 'count' => count($gatewayIds), 'reason' => $reason]);
            Notification::pushRole('clerk', 'attendance', 'All project gateways closed by County Director', 'County Director closed attendance for all open project sites. Reason: ' . $reason, 'admin/clerk/attendance-gateway.php');
            Session::flash('status', 'All project attendance gateways closed.');
        } elseif ($action === 'open_work_location') {
            $workLocationId = Security::cleanInt($_POST['work_location_id'] ?? 0);
            if ($workLocationId <= 0) {
                throw new RuntimeException('Choose a work location gateway to open.');
            }
            $gatewayId = AttendanceGateway::openWorkLocationByAdmin($actorId, $workLocationId, $actionDate, $closeTime, sa_attendance_override_note($reason));
            Logger::log('admin-open-work-location-gateway', 'attendance_gateways', $gatewayId, ['work_location_id' => $workLocationId, 'date' => $actionDate, 'reason' => $reason]);
            sa_attendance_notify_work_location_users($workLocationId, 'HQ attendance gateway opened', 'County Director opened attendance for your work location. Reason: ' . $reason);
            Session::flash('status', 'Work location attendance gateway opened.');
        } elseif ($action === 'close_work_location') {
            $workLocationId = Security::cleanInt($_POST['work_location_id'] ?? 0);
            if ($workLocationId <= 0) {
                throw new RuntimeException('Choose a work location gateway to close.');
            }
            $gatewayId = AttendanceGateway::closeWorkLocationByAdmin($actorId, $workLocationId, $actionDate, sa_attendance_override_note($reason));
            Logger::log('admin-close-work-location-gateway', 'attendance_gateways', $gatewayId, ['work_location_id' => $workLocationId, 'date' => $actionDate, 'reason' => $reason]);
            sa_attendance_notify_work_location_users($workLocationId, 'HQ attendance gateway closed', 'County Director closed attendance for your work location. Reason: ' . $reason);
            Session::flash('status', 'Work location attendance gateway closed.');
        } else {
            throw new RuntimeException('Unknown attendance gateway action.');
        }
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
    }

    Response::redirect(Url::to('admin/superadmin/attendance.php?date=' . urlencode($actionDate)));
}

$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
$projectId = isset($_GET['project_id']) && ctype_digit((string)$_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$workLocationId = isset($_GET['work_location_id']) && ctype_digit((string)$_GET['work_location_id']) ? (int)$_GET['work_location_id'] : 0;
$constituencyId = isset($_GET['constituency_id']) && ctype_digit((string)$_GET['constituency_id']) ? (int)$_GET['constituency_id'] : 0;
$role = trim((string)($_GET['role'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$reviewStatus = trim((string)($_GET['review_status'] ?? ''));
$gps = trim((string)($_GET['gps'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowedRoles = ['', 'clerk', 'intern', 'manager', 'consultant'];
$allowedStatuses = ['', 'present', 'absent', 'geo-fail', 'outside-window', 'late'];
$allowedReviewStatuses = ['', 'pending', 'accepted', 'flagged'];
$role = in_array($role, $allowedRoles, true) ? $role : '';
$status = in_array($status, $allowedStatuses, true) ? $status : '';
$reviewStatus = in_array($reviewStatus, $allowedReviewStatuses, true) ? $reviewStatus : '';
$gps = in_array($gps, ['', 'flagged'], true) ? $gps : '';

$filters = array_filter([
    'date' => $date,
    'project_id' => $projectId > 0 ? $projectId : null,
    'work_location_id' => $workLocationId > 0 ? $workLocationId : null,
    'constituency_id' => $constituencyId > 0 ? $constituencyId : null,
    'role' => $role,
    'status' => $status,
    'review_status' => $reviewStatus,
    'gps' => $gps,
    'q' => $q,
], static fn (mixed $value): bool => $value !== null && $value !== '');

$projects = Database::fetchAll('SELECT id, name FROM projects ORDER BY name ASC');
$workLocations = WorkLocation::active();
$constituencies = Constituency::findAll([], 'name ASC');
$stats = AttendanceRecord::stats($date, $filters);
$totalRows = AttendanceRecord::countDetailed($filters);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$records = AttendanceRecord::detailed($filters, $perPage, $offset);

$totalProjects = (int)(Database::fetch('SELECT COUNT(*) AS total FROM projects')['total'] ?? 0);
$geoConfigured = (int)(Database::fetch('SELECT COUNT(DISTINCT project_id) AS total FROM geo_fences WHERE status = "configured"')['total'] ?? 0);
$geoMissing = max(0, $totalProjects - $geoConfigured);
$openGateways = (int)(Database::fetch('SELECT COUNT(*) AS total FROM attendance_gateways WHERE date = ? AND is_open = 1', [$date])['total'] ?? 0);
$gatewaysToday = (int)(Database::fetch('SELECT COUNT(*) AS total FROM attendance_gateways WHERE date = ?', [$date])['total'] ?? 0);
// Combined site (project clerk/intern) + HQ/work-location assignees for headline KPIs.
$totalAssignedSitePeople = (int)(Database::fetch(
    'SELECT COUNT(*) AS total FROM (
        SELECT DISTINCT pa.user_id
        FROM project_assignments pa
        JOIN users u ON u.id = pa.user_id AND u.status = "active"
        JOIN roles r ON r.id = u.role_id
        WHERE pa.status = "active" AND r.slug IN ("clerk", "intern")
        UNION
        SELECT DISTINCT wla.user_id
        FROM work_location_assignments wla
        JOIN users u ON u.id = wla.user_id AND u.status = "active"
        JOIN work_locations wl ON wl.id = wla.work_location_id AND wl.status <> "inactive"
        WHERE wla.status = "active"
     ) assigned'
)['total'] ?? 0);
$signedAssignedSitePeople = (int)(Database::fetch(
    'SELECT COUNT(*) AS total FROM (
        SELECT DISTINCT ar.user_id
        FROM attendance_records ar
        JOIN project_assignments pa ON pa.user_id = ar.user_id AND pa.project_id = ar.project_id AND pa.status = "active"
        JOIN users u ON u.id = ar.user_id AND u.status = "active"
        JOIN roles r ON r.id = u.role_id
        WHERE ar.date = ? AND ar.project_id IS NOT NULL AND r.slug IN ("clerk", "intern")
        UNION
        SELECT DISTINCT ar.user_id
        FROM attendance_records ar
        JOIN work_location_assignments wla ON wla.user_id = ar.user_id AND wla.work_location_id = ar.work_location_id AND wla.status = "active"
        JOIN users u ON u.id = ar.user_id AND u.status = "active"
        WHERE ar.date = ? AND ar.work_location_id IS NOT NULL
     ) signed',
    [$date, $date]
)['total'] ?? 0);
$missingAssignedSitePeople = max(0, $totalAssignedSitePeople - $signedAssignedSitePeople);
$compliance = $totalAssignedSitePeople > 0 ? percentage(($signedAssignedSitePeople / $totalAssignedSitePeople) * 100) : 0;

$projectCoverageAll = Database::fetchAll(
    'SELECT p.id, p.name AS project_name, c.name AS constituency_name,
            gf.id AS geo_id, gf.status AS geo_status, gf.radius_meters,
            ag.id AS gateway_id, ag.is_open, ag.opened_at, ag.closes_at,
            CONCAT(opener.first_name, " ", opener.last_name) AS opened_by_name,
            COUNT(DISTINCT CASE WHEN arl.slug IN ("clerk", "intern") THEN au.id END) AS expected_people,
            COUNT(DISTINCT CASE WHEN arl.slug IN ("clerk", "intern") THEN ar.user_id END) AS signed_people,
            SUM(CASE WHEN ar.status = "geo-fail" THEN 1 ELSE 0 END) AS geo_flags
     FROM projects p
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN geo_fences gf ON gf.project_id = p.id
     LEFT JOIN attendance_gateways ag ON ag.project_id = p.id AND ag.date = ?
     LEFT JOIN users opener ON opener.id = ag.opened_by
     LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.status = "active"
     LEFT JOIN users au ON au.id = pa.user_id AND au.status = "active"
     LEFT JOIN roles arl ON arl.id = au.role_id
     LEFT JOIN attendance_records ar ON ar.project_id = p.id AND ar.date = ? AND ar.user_id = au.id
     GROUP BY p.id, p.name, c.name, gf.id, gf.status, gf.radius_meters, ag.id, ag.is_open, ag.opened_at, ag.closes_at, opened_by_name
     ORDER BY p.name ASC',
    [$date, $date]
);

$workLocationCoverageAll = Database::fetchAll(
    'SELECT wl.id AS work_location_id, wl.name AS work_location_name, wl.address, wl.location_type,
            wl.status AS geo_status, wl.radius_meters,
            ag.id AS gateway_id, ag.is_open, ag.opened_at, ag.closes_at, ag.notes,
            CONCAT(opener.first_name, " ", opener.last_name) AS opened_by_name,
            COUNT(DISTINCT au.id) AS expected_people,
            COUNT(DISTINCT ar.user_id) AS signed_people,
            SUM(CASE WHEN ar.status = "geo-fail" THEN 1 ELSE 0 END) AS geo_flags
     FROM work_locations wl
     LEFT JOIN attendance_gateways ag ON ag.work_location_id = wl.id AND ag.date = ?
     LEFT JOIN users opener ON opener.id = ag.opened_by
     LEFT JOIN work_location_assignments wla ON wla.work_location_id = wl.id AND wla.status = "active"
     LEFT JOIN users au ON au.id = wla.user_id AND au.status = "active"
     LEFT JOIN attendance_records ar ON ar.work_location_id = wl.id AND ar.date = ? AND ar.user_id = au.id
     WHERE wl.status <> "inactive"
     GROUP BY wl.id, wl.name, wl.address, wl.location_type, wl.status, wl.radius_meters, ag.id, ag.is_open, ag.opened_at, ag.closes_at, ag.notes, opened_by_name
     ORDER BY wl.name ASC',
    [$date, $date]
);

// Coverage tables: show 10 rows per page (same page size as global records).
$pcPage = max(1, (int)($_GET['pc_page'] ?? 1));
$wlPage = max(1, (int)($_GET['wl_page'] ?? 1));
$projectCoverageTotal = count($projectCoverageAll);
$workLocationCoverageTotal = count($workLocationCoverageAll);
$projectCoveragePages = max(1, (int)ceil($projectCoverageTotal / $perPage));
$workLocationCoveragePages = max(1, (int)ceil($workLocationCoverageTotal / $perPage));
$pcPage = min($pcPage, $projectCoveragePages);
$wlPage = min($wlPage, $workLocationCoveragePages);
$pcOffset = ($pcPage - 1) * $perPage;
$wlOffset = ($wlPage - 1) * $perPage;
$projectCoverage = array_slice($projectCoverageAll, $pcOffset, $perPage);
$workLocationCoverage = array_slice($workLocationCoverageAll, $wlOffset, $perPage);

$missingProjectPeople = Database::fetchAll(
    'SELECT u.id, CONCAT(u.first_name, " ", u.last_name) AS user_name, r.slug AS role_slug,
            p.name AS project_name, c.name AS constituency_name
     FROM project_assignments pa
     JOIN users u ON u.id = pa.user_id AND u.status = "active"
     JOIN roles r ON r.id = u.role_id
     JOIN projects p ON p.id = pa.project_id
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN attendance_records ar ON ar.user_id = u.id AND ar.project_id = p.id AND ar.date = ?
     WHERE pa.status = "active" AND r.slug IN ("clerk", "intern") AND ar.id IS NULL
     ORDER BY r.slug ASC, p.name ASC, u.first_name ASC
     LIMIT 10',
    [$date]
);

$missingWorkLocationPeople = Database::fetchAll(
    'SELECT u.id, CONCAT(u.first_name, " ", u.last_name) AS user_name, r.slug AS role_slug,
            wl.name AS project_name, "Internal Office" AS constituency_name
     FROM work_location_assignments wla
     JOIN users u ON u.id = wla.user_id AND u.status = "active"
     JOIN roles r ON r.id = u.role_id
     JOIN work_locations wl ON wl.id = wla.work_location_id
     LEFT JOIN attendance_records ar ON ar.user_id = u.id AND ar.work_location_id = wl.id AND ar.date = ?
     WHERE wla.status = "active" AND ar.id IS NULL
     ORDER BY r.slug ASC, wl.name ASC, u.first_name ASC
     LIMIT 10',
    [$date]
);
$missingPeople = array_slice(array_merge($missingProjectPeople, $missingWorkLocationPeople), 0, 10);

$gpsExceptions = Database::fetchAll(
    'SELECT ar.*, CONCAT(u.first_name, " ", u.last_name) AS user_name, r.slug AS role_slug,
            COALESCE(p.name, wl.name, "Attendance location") AS project_name
     FROM attendance_records ar
     JOIN users u ON u.id = ar.user_id
     JOIN roles r ON r.id = u.role_id
     LEFT JOIN projects p ON p.id = ar.project_id
     LEFT JOIN work_locations wl ON wl.id = ar.work_location_id
     WHERE ar.date = ? AND ar.status IN ("geo-fail", "outside-window")
     ORDER BY ar.created_at DESC
     LIMIT 10',
    [$date]
);

$geoIssues = Database::fetchAll(
    'SELECT p.id, p.name AS project_name, c.name AS constituency_name, gf.status AS geo_status, gf.radius_meters
     FROM projects p
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN geo_fences gf ON gf.project_id = p.id
     WHERE gf.id IS NULL OR gf.status IN ("missing", "needs-review") OR gf.radius_meters > 1000
     ORDER BY p.name ASC
     LIMIT 10'
);

$unsupervisedGateways = Database::fetchAll(
    'SELECT ag.*, COALESCE(p.name, wl.name, "Attendance gateway") AS project_name, CONCAT(u.first_name, " ", u.last_name) AS opened_by_name
     FROM attendance_gateways ag
     LEFT JOIN projects p ON p.id = ag.project_id
     LEFT JOIN work_locations wl ON wl.id = ag.work_location_id
     JOIN users u ON u.id = ag.opened_by
     LEFT JOIN attendance_records ar ON ar.user_id = ag.opened_by AND ar.date = ag.date AND ((ag.project_id IS NOT NULL AND ar.project_id = ag.project_id) OR (ag.work_location_id IS NOT NULL AND ar.work_location_id = ag.work_location_id))
     WHERE ag.date = ? AND ar.id IS NULL
     ORDER BY ag.opened_at DESC
     LIMIT 10',
    [$date]
);

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
$attendancePolicy = AttendancePolicy::windowPayload();
?>

<section class="card clerk-policy-banner sa-attendance-policy">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-sliders" aria-hidden="true"></i> County Director attendance policy</span>
    <strong><?= Security::e($attendancePolicy['label']) ?></strong>
    <small>
      Auto-open <?= !empty($attendancePolicy['auto_open']) ? 'on' : 'off' ?> ·
      Weekdays Mon–Fri · Timezone <?= Security::e($attendancePolicy['timezone'] ?? 'Africa/Nairobi') ?>.
      Clerks confirm site open only — they cannot edit these times.
      Edit times under <a href="<?= Security::e(Url::to('admin/superadmin/settings.php?group=attendance')) ?>">Settings → Attendance &amp; Geo</a>.
    </small>
  </div>
  <div class="clerk-policy-chips">
    <article><small>Opens</small><strong><?= Security::e($attendancePolicy['open_time']) ?></strong></article>
    <article><small>Expected</small><strong><?= Security::e($attendancePolicy['expected_time']) ?></strong></article>
    <article><small>Late after</small><strong><?= Security::e($attendancePolicy['late_after']) ?></strong></article>
    <article><small>Closes</small><strong><?= Security::e($attendancePolicy['close_time']) ?></strong></article>
  </div>
</section>

<section class="card sa-attendance-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Geo Attendance Control</span>
    <h2>County Attendance Command Centre</h2>
    <p>Monitor site gateway supervision, clerk presence, intern compliance and GPS trust across all AHPTC projects.</p>
  </div>
  <form class="sa-attendance-date" method="get">
    <label><span>Report date</span><input class="form-input" type="date" name="date" value="<?= Security::e($date) ?>"></label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Load Day</button>
  </form>
</section>

<section class="stat-grid sa-attendance-stats" aria-label="Attendance summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['present'] ?? 0)) ?></strong><span class="stat-widget__label">Present</span><small class="stat-widget__trend"><?= Security::e(format_number($stats['total_records'] ?? 0)) ?> records for selected day</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-user-clock" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($missingAssignedSitePeople)) ?></strong><span class="stat-widget__label">Missing Assigned Staff</span><small class="stat-widget__trend">Site clerks/interns and HQ assignees without sign-in</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--danger"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number(($stats['geo_fail'] ?? 0) + ($stats['outside_window'] ?? 0))) ?></strong><span class="stat-widget__label">GPS / Window Flags</span><small class="stat-widget__trend">Outside fence or sign-in window</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-door-open" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($openGateways)) ?></strong><span class="stat-widget__label">Open Gateways</span><small class="stat-widget__trend"><?= Security::e(format_number($gatewaysToday)) ?> gateways opened today</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($geoConfigured)) ?></strong><span class="stat-widget__label">Geo-Fenced Projects</span><small class="stat-widget__trend"><?= Security::e(format_number($geoMissing)) ?> projects missing geo-fence</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_percentage($compliance)) ?></strong><span class="stat-widget__label">Assigned Compliance</span><small class="stat-widget__trend">Site + HQ assigned staff signed in</small></span></article>
</section>

<section class="card sa-attendance-panel">
  <div class="section-heading">
    <div><h3>County Director Gateway Controls</h3><p>Override project gateways or manage HQ/work-location attendance. Clerks and affected interns are notified automatically.</p></div>
    <span class="badge badge--warning">Audited override</span>
  </div>
  <div class="sa-gateway-control-grid">
    <article class="sa-gateway-card">
      <div class="sa-attendance-exception__head"><span><i class="fa-solid fa-building" aria-hidden="true"></i></span><div><strong>Single Project Gateway</strong><small>Open or close one project site</small></div></div>
      <form class="sa-gateway-form" method="post">
        <?= Csrf::field($csrfForm) ?>
        <input type="hidden" name="date" value="<?= Security::e($date) ?>">
        <label><span>Project</span><select name="project_id" required><option value="">Choose project</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>"><?= Security::e((string)$project['name']) ?></option><?php endforeach; ?></select></label>
        <label><span>Closes at</span><input type="time" name="closes_at" value="<?= Security::e(SystemConfig::text('attendance.signin_end', '18:00')) ?>"></label>
        <label class="sa-filter-wide"><span>Reason</span><input type="text" name="reason" placeholder="County Director override reason" required></label>
        <button class="btn btn--primary" type="submit" name="action" value="open_project"><i class="fa-solid fa-door-open" aria-hidden="true"></i> Open</button>
        <button class="btn btn--outline" type="submit" name="action" value="close_project"><i class="fa-solid fa-door-closed" aria-hidden="true"></i> Close</button>
      </form>
    </article>

    <article class="sa-gateway-card">
      <div class="sa-attendance-exception__head"><span><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span><div><strong>All Project Gateways</strong><small>Programme-wide site control</small></div></div>
      <form class="sa-gateway-form" method="post">
        <?= Csrf::field($csrfForm) ?>
        <input type="hidden" name="date" value="<?= Security::e($date) ?>">
        <label><span>Closes at</span><input type="time" name="closes_at" value="<?= Security::e(SystemConfig::text('attendance.signin_end', '18:00')) ?>"></label>
        <label class="sa-filter-wide"><span>Reason</span><input type="text" name="reason" placeholder="Why all project gateways are being overridden" required></label>
        <button class="btn btn--primary" type="submit" name="action" value="open_all_projects"><i class="fa-solid fa-door-open" aria-hidden="true"></i> Open All</button>
        <button class="btn btn--outline" type="submit" name="action" value="close_all_projects"><i class="fa-solid fa-door-closed" aria-hidden="true"></i> Close All</button>
      </form>
    </article>

    <article class="sa-gateway-card">
      <div class="sa-attendance-exception__head"><span><i class="fa-solid fa-building-user" aria-hidden="true"></i></span><div><strong>HQ / Work Location Gateway</strong><small>For office-based interns</small></div></div>
      <form class="sa-gateway-form" method="post">
        <?= Csrf::field($csrfForm) ?>
        <input type="hidden" name="date" value="<?= Security::e($date) ?>">
        <label><span>Location</span><select name="work_location_id" required><option value="">Choose location</option><?php foreach ($workLocations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= Security::e((string)$location['name']) ?></option><?php endforeach; ?></select></label>
        <label><span>Closes at</span><input type="time" name="closes_at" value="<?= Security::e(SystemConfig::text('attendance.signin_end', '18:00')) ?>"></label>
        <label class="sa-filter-wide"><span>Reason</span><input type="text" name="reason" placeholder="HQ/work-location gateway reason" required></label>
        <button class="btn btn--primary" type="submit" name="action" value="open_work_location"><i class="fa-solid fa-door-open" aria-hidden="true"></i> Open HQ</button>
        <button class="btn btn--outline" type="submit" name="action" value="close_work_location"><i class="fa-solid fa-door-closed" aria-hidden="true"></i> Close HQ</button>
      </form>
    </article>
  </div>
</section>

<section class="card sa-attendance-briefing">
  <div class="section-heading">
    <div><h3>Operational Exceptions</h3><p>Priority items that affect site attendance trust and daily reporting to county leadership.</p></div>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('api/attendance/daily-report.php?date=' . urlencode($date))) ?>" target="_blank" rel="noopener noreferrer">JSON Report</a>
  </div>
  <div class="sa-attendance-exceptions">
    <?= sa_attendance_exception_card('Geo-fence Issues', 'fa-map-pin', $geoIssues, 'geo') ?>
    <?= sa_attendance_exception_card('Missing Sign-ins', 'fa-user-xmark', $missingPeople, 'missing') ?>
    <?= sa_attendance_exception_card('GPS Exceptions', 'fa-location-dot', $gpsExceptions, 'gps') ?>
    <?= sa_attendance_exception_card('Unsupervised Gateways', 'fa-door-open', $unsupervisedGateways, 'gateway') ?>
  </div>
</section>

<section class="sa-attendance-grid" aria-label="Attendance operations">
  <article class="card sa-attendance-panel sa-coverage-panel">
    <div class="section-heading"><div><h3>Project Coverage</h3><p>Gateway, geo-fence and sign-in coverage by project.</p></div></div>
    <div class="data-table-wrap"><table class="data-table sa-attendance-table"><thead><tr><th>#</th><th>Project</th><th>Geo</th><th>Gateway</th><th>Expected</th><th>Signed</th><th>Compliance</th><th>GPS Flags</th></tr></thead><tbody>
<?php if ($projectCoverage === []): ?>
      <tr><td colspan="8"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No projects found</strong></div></td></tr>
<?php else: foreach ($projectCoverage as $index => $row): ?>
      <?php $expected = (int)$row['expected_people']; $signed = (int)$row['signed_people']; $pct = $expected > 0 ? percentage(($signed / $expected) * 100) : 0; ?>
      <tr><td><?= Security::e(format_number($pcOffset + $index + 1)) ?></td><td><strong><?= Security::e($row['project_name']) ?></strong><small><?= Security::e($row['constituency_name'] ?: 'County project') ?></small></td><td><span class="badge <?= $row['geo_id'] ? Security::e(status_badge_class($row['geo_status'] ?: 'configured')) : 'badge--warning' ?>"><?= Security::e($row['geo_id'] ? status_label($row['geo_status'] ?: 'configured') : 'Missing') ?></span></td><td><span class="badge <?= (int)($row['is_open'] ?? 0) === 1 ? 'badge--success' : ($row['gateway_id'] ? 'badge--neutral' : 'badge--warning') ?>"><?= Security::e((int)($row['is_open'] ?? 0) === 1 ? 'Open' : ($row['gateway_id'] ? 'Closed' : 'Not Opened')) ?></span><small><?= Security::e($row['opened_by_name'] ?: '-') ?></small></td><td><?= Security::e(format_number($expected)) ?></td><td><?= Security::e(format_number($signed)) ?></td><td><div class="sa-mini-meter"><span style="--bar-width: <?= (int)$pct ?>%;"></span></div><small><?= Security::e(format_percentage($pct)) ?></small></td><td><?= Security::e(format_number($row['geo_flags'] ?? 0)) ?></td></tr>
<?php endforeach; endif; ?>
    </tbody></table></div>
    <?= sa_attendance_pagination($pcPage, $projectCoveragePages, $projectCoverageTotal, $perPage, 'pc_page', 'Project coverage pagination') ?>
  </article>
</section>

<section class="sa-attendance-grid" aria-label="Work location attendance operations">
  <article class="card sa-attendance-panel sa-coverage-panel">
    <div class="section-heading"><div><h3>Work Location Coverage</h3><p>Gateway and sign-in coverage for HQ and other internal attendance locations.</p></div><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/work-locations.php')) ?>">Manage Locations</a></div>
    <div class="data-table-wrap"><table class="data-table sa-attendance-table"><thead><tr><th>#</th><th>Location</th><th>Geo</th><th>Gateway</th><th>Expected</th><th>Signed</th><th>Compliance</th><th>GPS Flags</th></tr></thead><tbody>
<?php if ($workLocationCoverage === []): ?>
      <tr><td colspan="8"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No internal work locations configured</strong><span class="empty-state__text">HQ and office attendance locations will appear here once configured.</span></div></td></tr>
<?php else: foreach ($workLocationCoverage as $index => $row): ?>
      <?php $expected = (int)$row['expected_people']; $signed = (int)$row['signed_people']; $pct = $expected > 0 ? percentage(($signed / $expected) * 100) : 0; ?>
      <tr><td><?= Security::e(format_number($wlOffset + $index + 1)) ?></td><td><strong><?= Security::e($row['work_location_name']) ?></strong><small><?= Security::e($row['address'] ?: status_label((string)$row['location_type'])) ?></small></td><td><span class="badge <?= Security::e(status_badge_class((string)$row['geo_status'])) ?>"><?= Security::e(status_label((string)$row['geo_status'])) ?></span></td><td><span class="badge <?= (int)($row['is_open'] ?? 0) === 1 ? 'badge--success' : ($row['gateway_id'] ? 'badge--neutral' : 'badge--warning') ?>"><?= Security::e((int)($row['is_open'] ?? 0) === 1 ? 'Open' : ($row['gateway_id'] ? 'Closed' : 'Not Opened')) ?></span><small><?= Security::e($row['opened_by_name'] ?: '-') ?></small></td><td><?= Security::e(format_number($expected)) ?></td><td><?= Security::e(format_number($signed)) ?></td><td><div class="sa-mini-meter"><span style="--bar-width: <?= (int)$pct ?>%;"></span></div><small><?= Security::e(format_percentage($pct)) ?></small></td><td><?= Security::e(format_number($row['geo_flags'] ?? 0)) ?></td></tr>
<?php endforeach; endif; ?>
    </tbody></table></div>
    <?= sa_attendance_pagination($wlPage, $workLocationCoveragePages, $workLocationCoverageTotal, $perPage, 'wl_page', 'Work location coverage pagination') ?>
  </article>
</section>

<section class="card sa-attendance-panel sa-records-panel">
  <div class="section-heading">
    <div><h3>Global Attendance Records</h3><p>Filter daily sign-ins by project, role, status, GPS trust and person.</p></div>
    <span class="badge badge--neutral"><?= Security::e(format_number($totalRows)) ?> records</span>
  </div>
  <form class="filter-bar sa-attendance-filter sa-record-filter" method="get">
    <label><span>Date</span><input type="date" name="date" value="<?= Security::e($date) ?>"></label>
    <label class="sa-filter-wide"><span>Search</span><input type="search" name="q" value="<?= Security::e($q) ?>" placeholder="Name, project, email"></label>
    <label><span>Project</span><select name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= $projectId === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>Work location</span><select name="work_location_id"><option value="">All locations</option><?php foreach ($workLocations as $location): ?><option value="<?= (int)$location['id'] ?>" <?= $workLocationId === (int)$location['id'] ? 'selected' : '' ?>><?= Security::e((string)$location['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>Constituency</span><select name="constituency_id"><option value="">All constituencies</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= $constituencyId === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>Role</span><select name="role"><option value="">All roles</option><?php foreach (array_filter($allowedRoles) as $option): ?><option value="<?= Security::e($option) ?>" <?= $role === $option ? 'selected' : '' ?>><?= Security::e(role_label($option)) ?></option><?php endforeach; ?></select></label>
    <label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach (array_filter($allowedStatuses) as $option): ?><option value="<?= Security::e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= Security::e(status_label($option)) ?></option><?php endforeach; ?></select></label>
    <label><span>Review</span><select name="review_status"><option value="">All reviews</option><?php foreach (array_filter($allowedReviewStatuses) as $option): ?><option value="<?= Security::e($option) ?>" <?= $reviewStatus === $option ? 'selected' : '' ?>><?= Security::e(status_label($option)) ?></option><?php endforeach; ?></select></label>
    <label><span>GPS</span><select name="gps"><option value="">All</option><option value="flagged" <?= $gps === 'flagged' ? 'selected' : '' ?>>Flagged only</option></select></label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/attendance.php')) ?>">Reset</a>
  </form>
  <?php if ($records): ?>
    <div class="data-table-wrap"><table class="data-table sa-attendance-table"><thead><tr><th>#</th><th>User</th><th>Role</th><th>Project</th><th>Time</th><th>Gateway</th><th>GPS / Distance</th><th>Status</th><th>Review</th></tr></thead><tbody>
<?php foreach ($records as $index => $record): ?>
      <?php $currentReview = strtolower(trim((string)($record['review_status'] ?? 'pending'))); ?>
      <tr><td><?= Security::e(format_number($offset + $index + 1)) ?></td><td><strong><?= Security::e($record['user_name']) ?></strong><small><?= Security::e($record['email']) ?></small></td><td><?= Security::e(role_label($record['role_slug'])) ?></td><td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['constituency_name'] ?: ($record['work_location_name'] ? 'Internal Office' : '-')) ?></small></td><td><?= Security::e($record['signin_time'] ?: '-') ?><small><?= Security::e(format_date($record['date'])) ?></small></td><td><span class="badge <?= (int)($record['gateway_is_open'] ?? 0) === 1 ? 'badge--success' : 'badge--neutral' ?>"><?= Security::e((int)($record['gateway_is_open'] ?? 0) === 1 ? 'Open' : 'Closed') ?></span><small><?= Security::e($record['opened_at'] ? format_datetime($record['opened_at']) : '-') ?></small></td><td><strong><?= Security::e($record['distance_from_site_m'] !== null ? format_number($record['distance_from_site_m'], 1) . 'm' : '-') ?></strong><small><?= Security::e($record['site_name'] ?: 'No site name') ?></small></td><td><span class="badge <?= Security::e(status_badge_class($record['status'])) ?>"><?= Security::e(status_label($record['status'])) ?></span><small><?= Security::e(status_label($currentReview !== '' ? $currentReview : 'pending')) ?></small></td><td><form class="sa-review-actions" method="post"><?= Csrf::field($csrfForm) ?><input type="hidden" name="date" value="<?= Security::e($date) ?>"><input type="hidden" name="record_id" value="<?= (int)$record['id'] ?>"><input class="form-input" type="text" name="reason" placeholder="Optional note" maxlength="180" aria-label="Review note"><?php if ($currentReview !== 'accepted'): ?><button class="btn btn--sm btn--outline" type="submit" name="action" value="review_accept">Accept</button><?php endif; ?><?php if ($currentReview !== 'flagged'): ?><button class="btn btn--sm btn--outline" type="submit" name="action" value="review_flag">Flag</button><?php endif; ?><?php if ($currentReview === 'accepted' || $currentReview === 'flagged'): ?><span class="badge <?= Security::e(status_badge_class($currentReview)) ?>"><?= Security::e(status_label($currentReview)) ?></span><?php endif; ?></form></td></tr>
<?php endforeach; ?>
    </tbody></table></div>
    <?= sa_attendance_pagination($page, $totalPages, $totalRows, $perPage) ?>
  <?php else: ?>
    <div class="empty-state sa-attendance-empty"><span class="empty-state__icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><strong class="empty-state__title">No attendance records found</strong><span class="empty-state__text">Records will appear after gateways are opened and assigned site staff sign in.</span></div>
  <?php endif; ?>
</section>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>

<?php
function sa_attendance_clean_date(mixed $value): string
{
    $date = trim((string)$value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
}

function sa_attendance_clean_time(mixed $value): string
{
    $time = trim((string)$value);
    return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : SystemConfig::text('attendance.signin_end', '18:00');
}

function sa_attendance_override_note(string $reason): string
{
    return 'County Director override: ' . trim($reason);
}

function sa_attendance_has_review_notes(): bool
{
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }

    $row = Database::fetch(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "attendance_records" AND COLUMN_NAME = "review_notes"'
    );
    $hasColumn = (int)($row['total'] ?? 0) > 0;
    return $hasColumn;
}

function sa_attendance_notify_project_clerks(int $projectId, string $title, string $body): void
{
    $clerks = Database::fetchAll(
        'SELECT DISTINCT u.id
         FROM project_assignments pa
         JOIN users u ON u.id = pa.user_id AND u.status = "active"
         JOIN roles r ON r.id = u.role_id
         WHERE pa.project_id = ? AND pa.status = "active" AND r.slug = "clerk"',
        [$projectId]
    );
    Notification::pushMany(array_map(static fn (array $row): int => (int)$row['id'], $clerks), 'attendance', $title, $body, 'admin/clerk/attendance-gateway.php');
}

function sa_attendance_notify_work_location_users(int $workLocationId, string $title, string $body): void
{
    $users = Database::fetchAll(
        'SELECT DISTINCT u.id
         FROM work_location_assignments wla
         JOIN users u ON u.id = wla.user_id AND u.status = "active"
         WHERE wla.work_location_id = ? AND wla.status = "active"',
        [$workLocationId]
    );
    Notification::pushMany(array_map(static fn (array $row): int => (int)$row['id'], $users), 'attendance', $title, $body, 'admin/intern/sign-in.php');
}

function sa_attendance_exception_card(string $title, string $icon, array $rows, string $type): string
{
    ob_start();
?>
  <article class="sa-attendance-exception">
    <div class="sa-attendance-exception__head"><span><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span><div><strong><?= Security::e($title) ?></strong><small><?= Security::e(format_number(count($rows))) ?> item(s)</small></div></div>
    <?php if (!$rows): ?>
      <p class="sa-attendance-clear">No exception recorded.</p>
    <?php else: ?>
      <ul>
      <?php foreach (array_slice($rows, 0, 4) as $row): ?>
        <li><strong><?= Security::e($row['project_name'] ?? $row['user_name'] ?? 'Record') ?></strong><small><?= Security::e(sa_attendance_exception_text($row, $type)) ?></small></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </article>
<?php
    return ob_get_clean();
}

function sa_attendance_exception_text(array $row, string $type): string
{
    return match ($type) {
        'geo' => ($row['constituency_name'] ?? 'County project') . ' Â· ' . ($row['geo_status'] ? status_label($row['geo_status']) : 'Missing geo-fence'),
        'missing' => role_label($row['role_slug'] ?? '') . ' Â· ' . ($row['project_name'] ?? 'Unassigned'),
        'gps' => status_label($row['status'] ?? '') . ' Â· ' . (($row['distance_from_site_m'] ?? null) !== null ? format_number($row['distance_from_site_m'], 1) . 'm from site' : 'Distance unavailable'),
        'gateway' => ($row['opened_by_name'] ?? 'Clerk') . ' opened gateway without own sign-in',
        default => 'Needs review',
    };
}

function sa_attendance_pagination(
    int $page,
    int $totalPages,
    int $total,
    int $perPage,
    string $pageParam = 'page',
    string $ariaLabel = 'Attendance pagination'
): string {
    if ($total <= 0) {
        return '';
    }

    $from = (($page - 1) * $perPage) + 1;
    $to = min($total, $page * $perPage);
    ob_start();
?>
  <nav class="sa-project-pagination pagination" aria-label="<?= Security::e($ariaLabel) ?>">
    <span>Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> (<?= Security::e(format_number($perPage)) ?> per page)</span>
    <div class="pagination__links">
      <a class="btn btn--sm btn--outline <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(sa_attendance_page_url(max(1, $page - 1), $pageParam)) ?>">Previous</a>
      <span class="pagination__current">Page <?= Security::e(format_number($page)) ?> of <?= Security::e(format_number($totalPages)) ?></span>
      <a class="btn btn--sm btn--outline <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(sa_attendance_page_url(min($totalPages, $page + 1), $pageParam)) ?>">Next</a>
    </div>
  </nav>
<?php
    return ob_get_clean();
}

function sa_attendance_page_url(int $page, string $pageParam = 'page'): string
{
    $query = $_GET;
    $query[$pageParam] = $page;
    return Url::to('admin/superadmin/attendance.php' . ($query ? '?' . http_build_query($query) : ''));
}
