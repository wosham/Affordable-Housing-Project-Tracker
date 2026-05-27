<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(['superadmin']);

$pageTitle = 'Attendance Command Centre';
$pageDescription = 'Global attendance, geo-fence and site gateway monitoring for the AHPTC programme.';
$adminRole = 'superadmin';
$contentClass = 'sa-attendance-page';
$pageKicker = 'Geo Attendance Control';

$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
$projectId = isset($_GET['project_id']) && ctype_digit((string)$_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$constituencyId = isset($_GET['constituency_id']) && ctype_digit((string)$_GET['constituency_id']) ? (int)$_GET['constituency_id'] : 0;
$role = trim((string)($_GET['role'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$gps = trim((string)($_GET['gps'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowedRoles = ['', 'clerk', 'intern', 'manager', 'consultant'];
$allowedStatuses = ['', 'present', 'absent', 'geo-fail', 'outside-window', 'late'];
$role = in_array($role, $allowedRoles, true) ? $role : '';
$status = in_array($status, $allowedStatuses, true) ? $status : '';
$gps = in_array($gps, ['', 'flagged'], true) ? $gps : '';

$filters = array_filter([
    'date' => $date,
    'project_id' => $projectId > 0 ? $projectId : null,
    'constituency_id' => $constituencyId > 0 ? $constituencyId : null,
    'role' => $role,
    'status' => $status,
    'gps' => $gps,
    'q' => $q,
], static fn (mixed $value): bool => $value !== null && $value !== '');

$projects = Database::fetchAll('SELECT id, name FROM projects ORDER BY name ASC');
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
$totalAssignedSitePeople = (int)(Database::fetch(
    'SELECT COUNT(DISTINCT pa.user_id) AS total
     FROM project_assignments pa
     JOIN users u ON u.id = pa.user_id
     JOIN roles r ON r.id = u.role_id
     WHERE u.status = "active" AND r.slug IN ("clerk", "intern")'
)['total'] ?? 0);
$signedAssignedSitePeople = (int)(Database::fetch(
    'SELECT COUNT(DISTINCT ar.user_id) AS total
     FROM attendance_records ar
     JOIN users u ON u.id = ar.user_id
     JOIN roles r ON r.id = u.role_id
     WHERE ar.date = ? AND r.slug IN ("clerk", "intern")',
    [$date]
)['total'] ?? 0);
$missingAssignedSitePeople = max(0, $totalAssignedSitePeople - $signedAssignedSitePeople);
$compliance = $totalAssignedSitePeople > 0 ? percentage(($signedAssignedSitePeople / $totalAssignedSitePeople) * 100) : 0;

$projectCoverage = Database::fetchAll(
    'SELECT p.id, p.name AS project_name, c.name AS constituency_name,
            gf.id AS geo_id, gf.status AS geo_status, gf.radius_meters,
            ag.id AS gateway_id, ag.is_open, ag.opened_at, ag.closes_at,
            CONCAT(opener.first_name, " ", opener.last_name) AS opened_by_name,
            COUNT(DISTINCT au.id) AS expected_people,
            COUNT(DISTINCT ar.user_id) AS signed_people,
            SUM(CASE WHEN ar.status = "geo-fail" THEN 1 ELSE 0 END) AS geo_flags
     FROM projects p
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN geo_fences gf ON gf.project_id = p.id
     LEFT JOIN attendance_gateways ag ON ag.project_id = p.id AND ag.date = ?
     LEFT JOIN users opener ON opener.id = ag.opened_by
     LEFT JOIN project_assignments pa ON pa.project_id = p.id
     LEFT JOIN users au ON au.id = pa.user_id AND au.status = "active"
     LEFT JOIN roles arl ON arl.id = au.role_id AND arl.slug IN ("clerk", "intern")
     LEFT JOIN attendance_records ar ON ar.project_id = p.id AND ar.date = ? AND ar.user_id = au.id
     GROUP BY p.id, p.name, c.name, gf.id, gf.status, gf.radius_meters, ag.id, ag.is_open, ag.opened_at, ag.closes_at, opened_by_name
     ORDER BY p.name ASC',
    [$date, $date]
);

$missingPeople = Database::fetchAll(
    'SELECT u.id, CONCAT(u.first_name, " ", u.last_name) AS user_name, r.slug AS role_slug,
            p.name AS project_name, c.name AS constituency_name
     FROM project_assignments pa
     JOIN users u ON u.id = pa.user_id AND u.status = "active"
     JOIN roles r ON r.id = u.role_id
     JOIN projects p ON p.id = pa.project_id
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN attendance_records ar ON ar.user_id = u.id AND ar.project_id = p.id AND ar.date = ?
     WHERE r.slug IN ("clerk", "intern") AND ar.id IS NULL
     ORDER BY r.slug ASC, p.name ASC, u.first_name ASC
     LIMIT 10',
    [$date]
);

$gpsExceptions = Database::fetchAll(
    'SELECT ar.*, CONCAT(u.first_name, " ", u.last_name) AS user_name, r.slug AS role_slug,
            p.name AS project_name
     FROM attendance_records ar
     JOIN users u ON u.id = ar.user_id
     JOIN roles r ON r.id = u.role_id
     JOIN projects p ON p.id = ar.project_id
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
    'SELECT ag.*, p.name AS project_name, CONCAT(u.first_name, " ", u.last_name) AS opened_by_name
     FROM attendance_gateways ag
     JOIN projects p ON p.id = ag.project_id
     JOIN users u ON u.id = ag.opened_by
     LEFT JOIN attendance_records ar ON ar.user_id = ag.opened_by AND ar.project_id = ag.project_id AND ar.date = ag.date
     WHERE ag.date = ? AND ar.id IS NULL
     ORDER BY ag.opened_at DESC
     LIMIT 10',
    [$date]
);

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
?>

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
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-user-clock" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($missingAssignedSitePeople)) ?></strong><span class="stat-widget__label">Missing Site Staff</span><small class="stat-widget__trend">Clerks and interns without sign-in</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--danger"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number(($stats['geo_fail'] ?? 0) + ($stats['outside_window'] ?? 0))) ?></strong><span class="stat-widget__label">GPS / Window Flags</span><small class="stat-widget__trend">Outside fence or sign-in window</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-door-open" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($openGateways)) ?></strong><span class="stat-widget__label">Open Gateways</span><small class="stat-widget__trend"><?= Security::e(format_number($gatewaysToday)) ?> gateways opened today</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($geoConfigured)) ?></strong><span class="stat-widget__label">Geo-Fenced Projects</span><small class="stat-widget__trend"><?= Security::e(format_number($geoMissing)) ?> projects missing geo-fence</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_percentage($compliance)) ?></strong><span class="stat-widget__label">Site Compliance</span><small class="stat-widget__trend">Assigned clerks/interns signed in</small></span></article>
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
  <article class="card sa-attendance-panel">
    <div class="section-heading"><div><h3>Project Coverage</h3><p>Gateway, geo-fence and sign-in coverage by project.</p></div></div>
    <div class="data-table-wrap"><table class="data-table sa-attendance-table"><thead><tr><th>#</th><th>Project</th><th>Geo</th><th>Gateway</th><th>Expected</th><th>Signed</th><th>Compliance</th><th>GPS Flags</th></tr></thead><tbody>
<?php foreach ($projectCoverage as $index => $row): ?>
      <?php $expected = (int)$row['expected_people']; $signed = (int)$row['signed_people']; $pct = $expected > 0 ? percentage(($signed / $expected) * 100) : 0; ?>
      <tr><td><?= Security::e(format_number($index + 1)) ?></td><td><strong><?= Security::e($row['project_name']) ?></strong><small><?= Security::e($row['constituency_name'] ?: 'County project') ?></small></td><td><span class="badge <?= $row['geo_id'] ? Security::e(status_badge_class($row['geo_status'] ?: 'configured')) : 'badge--warning' ?>"><?= Security::e($row['geo_id'] ? status_label($row['geo_status'] ?: 'configured') : 'Missing') ?></span></td><td><span class="badge <?= (int)($row['is_open'] ?? 0) === 1 ? 'badge--success' : ($row['gateway_id'] ? 'badge--neutral' : 'badge--warning') ?>"><?= Security::e((int)($row['is_open'] ?? 0) === 1 ? 'Open' : ($row['gateway_id'] ? 'Closed' : 'Not Opened')) ?></span><small><?= Security::e($row['opened_by_name'] ?: '-') ?></small></td><td><?= Security::e(format_number($expected)) ?></td><td><?= Security::e(format_number($signed)) ?></td><td><div class="sa-mini-meter"><span style="--bar-width: <?= (int)$pct ?>%;"></span></div><small><?= Security::e(format_percentage($pct)) ?></small></td><td><?= Security::e(format_number($row['geo_flags'] ?? 0)) ?></td></tr>
<?php endforeach; ?>
    </tbody></table></div>
  </article>
</section>

<section class="card sa-attendance-panel">
  <div class="section-heading">
    <div><h3>Global Attendance Records</h3><p>Filter daily sign-ins by project, role, status, GPS trust and person.</p></div>
    <span class="badge badge--neutral"><?= Security::e(format_number($totalRows)) ?> records</span>
  </div>
  <form class="filter-bar sa-attendance-filter" method="get">
    <label><span>Date</span><input type="date" name="date" value="<?= Security::e($date) ?>"></label>
    <label class="sa-filter-wide"><span>Search</span><input type="search" name="q" value="<?= Security::e($q) ?>" placeholder="Name, project, email"></label>
    <label><span>Project</span><select name="project_id"><option value="">All projects</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= $projectId === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>Constituency</span><select name="constituency_id"><option value="">All constituencies</option><?php foreach ($constituencies as $constituency): ?><option value="<?= (int)$constituency['id'] ?>" <?= $constituencyId === (int)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>Role</span><select name="role"><option value="">All roles</option><?php foreach (array_filter($allowedRoles) as $option): ?><option value="<?= Security::e($option) ?>" <?= $role === $option ? 'selected' : '' ?>><?= Security::e(role_label($option)) ?></option><?php endforeach; ?></select></label>
    <label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach (array_filter($allowedStatuses) as $option): ?><option value="<?= Security::e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= Security::e(status_label($option)) ?></option><?php endforeach; ?></select></label>
    <label><span>GPS</span><select name="gps"><option value="">All</option><option value="flagged" <?= $gps === 'flagged' ? 'selected' : '' ?>>Flagged only</option></select></label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/attendance.php')) ?>">Reset</a>
  </form>
  <?php if ($records): ?>
    <div class="data-table-wrap"><table class="data-table sa-attendance-table"><thead><tr><th>#</th><th>User</th><th>Role</th><th>Project</th><th>Time</th><th>Gateway</th><th>GPS / Distance</th><th>Status</th></tr></thead><tbody>
<?php foreach ($records as $index => $record): ?>
      <tr><td><?= Security::e(format_number($offset + $index + 1)) ?></td><td><strong><?= Security::e($record['user_name']) ?></strong><small><?= Security::e($record['email']) ?></small></td><td><?= Security::e(role_label($record['role_slug'])) ?></td><td><strong><?= Security::e($record['project_name']) ?></strong><small><?= Security::e($record['constituency_name'] ?: '-') ?></small></td><td><?= Security::e($record['signin_time'] ?: '-') ?><small><?= Security::e(format_date($record['date'])) ?></small></td><td><span class="badge <?= (int)($record['gateway_is_open'] ?? 0) === 1 ? 'badge--success' : 'badge--neutral' ?>"><?= Security::e((int)($record['gateway_is_open'] ?? 0) === 1 ? 'Open' : 'Closed') ?></span><small><?= Security::e($record['opened_at'] ? format_datetime($record['opened_at']) : '-') ?></small></td><td><strong><?= Security::e($record['distance_from_site_m'] !== null ? format_number($record['distance_from_site_m'], 1) . 'm' : '-') ?></strong><small><?= Security::e($record['site_name'] ?: 'No site name') ?></small></td><td><span class="badge <?= Security::e(status_badge_class($record['status'])) ?>"><?= Security::e(status_label($record['status'])) ?></span></td></tr>
<?php endforeach; ?>
    </tbody></table></div>
    <?= sa_attendance_pagination($page, $totalPages, $totalRows, $perPage) ?>
  <?php else: ?>
    <div class="empty-state sa-attendance-empty"><span class="empty-state__icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><strong class="empty-state__title">No attendance records found</strong><span class="empty-state__text">Records will appear after gateways are opened and assigned site staff sign in.</span></div>
  <?php endif; ?>
</section>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>

<?php
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
        'geo' => ($row['constituency_name'] ?? 'County project') . ' - ' . ($row['geo_status'] ? status_label($row['geo_status']) : 'Missing geo-fence'),
        'missing' => role_label($row['role_slug'] ?? '') . ' - ' . ($row['project_name'] ?? 'Unassigned'),
        'gps' => status_label($row['status'] ?? '') . ' - ' . (($row['distance_from_site_m'] ?? null) !== null ? format_number($row['distance_from_site_m'], 1) . 'm from site' : 'Distance unavailable'),
        'gateway' => ($row['opened_by_name'] ?? 'Clerk') . ' opened gateway without own sign-in',
        default => 'Needs review',
    };

    return match ($type) {
        'geo' => ($row['constituency_name'] ?? 'County project') . ' · ' . ($row['geo_status'] ? status_label($row['geo_status']) : 'Missing geo-fence'),
        'missing' => role_label($row['role_slug'] ?? '') . ' · ' . ($row['project_name'] ?? 'Unassigned'),
        'gps' => status_label($row['status'] ?? '') . ' · ' . (($row['distance_from_site_m'] ?? null) !== null ? format_number($row['distance_from_site_m'], 1) . 'm from site' : 'Distance unavailable'),
        'gateway' => ($row['opened_by_name'] ?? 'Clerk') . ' opened gateway without own sign-in',
        default => 'Needs review',
    };
}

function sa_attendance_pagination(int $page, int $totalPages, int $total, int $perPage): string
{
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $to = min($total, $page * $perPage);
    ob_start();
?>
  <nav class="sa-project-pagination pagination" aria-label="Attendance pagination">
    <span>Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?></span>
    <div class="pagination__links">
      <a class="btn btn--sm btn--outline <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(sa_attendance_page_url(max(1, $page - 1))) ?>">Previous</a>
      <span class="pagination__current">Page <?= Security::e(format_number($page)) ?> of <?= Security::e(format_number($totalPages)) ?></span>
      <a class="btn btn--sm btn--outline <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(sa_attendance_page_url(min($totalPages, $page + 1))) ?>">Next</a>
    </div>
  </nav>
<?php
    return ob_get_clean();
}

function sa_attendance_page_url(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;
    return Url::to('admin/superadmin/attendance.php' . ($query ? '?' . http_build_query($query) : ''));
}
