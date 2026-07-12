<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$pageTitle = 'Work Locations';
$pageDescription = 'Configure internal attendance locations such as Headquarters Office.';
$adminRole = 'superadmin';
$contentClass = 'sa-work-locations-page';
$componentCss = ['work-locations'];
$csrfForm = 'work_locations';
$message = null;
$errors = [];

$locationParam = isset($_GET['location_id']) && ctype_digit((string)$_GET['location_id']) ? (int)$_GET['location_id'] : 0;
$assignmentSearch = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    }

    $action = $errors === [] ? Security::cleanString((string)($_POST['action'] ?? '')) : '';
    $locationId = $errors === [] ? Security::cleanInt($_POST['location_id'] ?? 0) : 0;
    $location = $locationId > 0 ? WorkLocation::find($locationId) : null;

    if (!$location) {
        $errors[] = 'Work location could not be found.';
    } elseif ($action === 'save_location') {
        $name = Security::cleanString((string)($_POST['name'] ?? ''));
        $address = Security::cleanString((string)($_POST['address'] ?? ''));
        $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $radius = max(20, min(3000, Security::cleanInt($_POST['radius_meters'] ?? 150)));
        $status = Security::cleanString((string)($_POST['status'] ?? 'missing'));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($name === '') {
            $errors[] = 'Location name is required.';
        }
        if (!in_array($status, WorkLocation::STATUSES, true)) {
            $status = 'missing';
        }
        if ($status === 'configured' && ($latitude === false || $longitude === false)) {
            $errors[] = 'Configured locations require valid latitude and longitude.';
        }

        if ($errors === []) {
            WorkLocation::update($locationId, [
                'name' => $name,
                'address' => $address !== '' ? $address : null,
                'latitude' => $latitude !== false ? (float)$latitude : null,
                'longitude' => $longitude !== false ? (float)$longitude : null,
                'radius_meters' => $radius,
                'status' => $status,
                'notes' => $notes !== '' ? $notes : null,
                'updated_by' => (int)Auth::id(),
            ]);
            Logger::log('update', 'work_locations', $locationId, ['section' => 'profile']);
            $message = 'Work location updated.';
        }
    } elseif ($action === 'remove_user') {
        $userId = Security::cleanInt($_POST['user_id'] ?? 0);
        $assignment = Database::fetch(
            'SELECT id FROM work_location_assignments WHERE work_location_id = ? AND user_id = ? AND status = "active" LIMIT 1',
            [$locationId, $userId]
        );
        if (!$assignment) {
            $errors[] = 'Active work location assignment could not be found.';
        } else {
            WorkLocation::revokeAssignment((int)$assignment['id'], (int)Auth::id());
            Logger::log('revoke', 'work_location_assignments', (int)$assignment['id'], ['work_location_id' => $locationId, 'user_id' => $userId]);
            $message = 'Intern removed from work location.';
        }
    }
}

$locations = WorkLocation::visibleForAdmin();
$selectedLocationId = $locationParam > 0 ? $locationParam : (int)($locations[0]['id'] ?? 0);
if ($selectedLocationId <= 0 && $locations !== []) {
    $selectedLocationId = (int)$locations[0]['id'];
}

$assignmentBindings = [$selectedLocationId];
$assignmentWhere = '';
if ($assignmentSearch !== '') {
    $assignmentWhere .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $assignmentSearch . '%';
    array_push($assignmentBindings, $like, $like, $like);
}

$countRow = Database::fetch(
    'SELECT COUNT(*) AS total
     FROM work_location_assignments wla
     JOIN users u ON u.id = wla.user_id
     JOIN roles r ON r.id = u.role_id
     WHERE wla.work_location_id = ? AND wla.status = "active" AND r.slug = "intern"' . $assignmentWhere,
    $assignmentBindings
);
$totalRows = (int)($countRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$internRows = Database::fetchAll(
    'SELECT u.id, CONCAT(u.first_name, " ", u.last_name) AS user_name, u.email, u.status,
            wla.id AS assignment_id, wla.assigned_by, wla.assigned_at,
            CONCAT(assigner.first_name, " ", assigner.last_name) AS assigned_by_name
     FROM work_location_assignments wla
     JOIN users u ON u.id = wla.user_id
     JOIN roles r ON r.id = u.role_id
     LEFT JOIN users assigner ON assigner.id = wla.assigned_by
     WHERE wla.work_location_id = ? AND wla.status = "active" AND r.slug = "intern"' . $assignmentWhere . '
     ORDER BY u.first_name ASC, u.last_name ASC
     LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset,
    $assignmentBindings
);

$assignedCount = (int)(Database::fetch(
    'SELECT COUNT(*) AS total FROM work_location_assignments WHERE work_location_id = ? AND status = "active"',
    [$selectedLocationId]
)['total'] ?? 0);

include dirname(__DIR__, 2) . '/app/partials/admin/shell-start.php';
?>

<section class="card sa-workloc-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-building-user" aria-hidden="true"></i> Internal attendance</span>
    <h2>Work Locations</h2>
    <p>Keep Headquarters and office-based attendance separate from public projects while maintaining GPS radius, assignment control and reporting.</p>
  </div>
  <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/attendance.php')) ?>"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Attendance Centre</a>
</section>

<?php if ($message): ?><div class="alert alert--success"><?= Security::e($message) ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert--danger"><?= Security::e($error) ?></div><?php endforeach; ?>

<?php foreach ($locations as $location): ?>
<?php $locationId = (int)$location['id']; ?>
<section class="card sa-workloc-card" id="location-<?= $locationId ?>">
  <div class="section-heading">
    <div>
      <h3><?= Security::e((string)$location['name']) ?></h3>
      <p><?= Security::e((string)($location['address'] ?: 'Internal work location')) ?></p>
    </div>
    <span class="badge <?= Security::e(status_badge_class((string)$location['status'])) ?>"><?= Security::e(status_label((string)$location['status'])) ?></span>
  </div>

  <form method="post" class="sa-workloc-form">
    <?= Csrf::field($csrfForm) ?>
    <input type="hidden" name="action" value="save_location">
    <input type="hidden" name="location_id" value="<?= $locationId ?>">

    <section class="sa-workloc-section">
      <div class="sa-workloc-section__head">
        <span><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i></span>
        <div><h4>Location Profile</h4><p>Name, address and administrative notes for this internal attendance point.</p></div>
      </div>
      <div class="sa-workloc-grid">
        <label class="form-field"><span class="form-label">Location name</span><input class="form-input" name="name" value="<?= Security::e((string)$location['name']) ?>" required></label>
        <label class="form-field"><span class="form-label">Address / label</span><input class="form-input" name="address" value="<?= Security::e((string)($location['address'] ?? '')) ?>"></label>
        <label class="form-field is-wide"><span class="form-label">Notes</span><textarea class="form-textarea" name="notes" rows="4"><?= Security::e((string)($location['notes'] ?? '')) ?></textarea></label>
      </div>
    </section>

    <section class="sa-workloc-section">
      <div class="sa-workloc-section__head">
        <span><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></span>
        <div><h4>Geo-Fence Settings</h4><p>GPS boundary used when HQ interns sign in from this location.</p></div>
      </div>
      <div class="sa-workloc-grid sa-workloc-grid--geo">
        <label class="form-field"><span class="form-label">Latitude</span><input class="form-input" name="latitude" value="<?= Security::e((string)($location['latitude'] ?? '')) ?>" placeholder="1.01"></label>
        <label class="form-field"><span class="form-label">Longitude</span><input class="form-input" name="longitude" value="<?= Security::e((string)($location['longitude'] ?? '')) ?>" placeholder="35.00"></label>
        <label class="form-field"><span class="form-label">Allowed radius (m)</span><input class="form-input" type="number" min="20" max="3000" name="radius_meters" value="<?= (int)$location['radius_meters'] ?>"></label>
        <label class="form-field"><span class="form-label">Geo status</span><select class="form-select" name="status"><?php foreach (WorkLocation::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= (string)$location['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
      </div>
    </section>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Location</button>
    </div>
  </form>
</section>
<?php endforeach; ?>

<?php if ($selectedLocationId > 0): ?>
<section class="card sa-workloc-card sa-workloc-assignments">
  <div class="section-heading">
    <div>
      <h3>Assigned HQ Interns</h3>
      <p>Office-based interns actively attached to this work location. Gateway opening remains in Attendance Centre.</p>
    </div>
    <span class="badge badge--neutral"><?= Security::e(format_number($assignedCount)) ?> assigned</span>
  </div>

  <form class="filter-bar sa-workloc-filter" method="get">
    <label><span>Location</span><select name="location_id"><?php foreach ($locations as $location): ?><option value="<?= (int)$location['id'] ?>" <?= $selectedLocationId === (int)$location['id'] ? 'selected' : '' ?>><?= Security::e((string)$location['name']) ?></option><?php endforeach; ?></select></label>
    <label class="sa-filter-wide"><span>Search intern</span><input type="search" name="q" value="<?= Security::e($assignmentSearch) ?>" placeholder="Name or email"></label>
    <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/work-locations.php?location_id=' . $selectedLocationId)) ?>">Reset</a>
  </form>

  <div class="data-table-wrap">
    <table class="data-table sa-workloc-table">
      <thead><tr><th>#</th><th>Office Intern</th><th>Status</th><th>HQ Assignment</th><th>Assigned By</th><th>Action</th></tr></thead>
      <tbody>
      <?php if ($internRows === []): ?>
        <tr><td colspan="6"><div class="empty-state empty-state--compact"><strong class="empty-state__title">No assigned HQ interns found</strong><span class="empty-state__text">Assign office interns from the user profile access section, then they will appear here.</span></div></td></tr>
      <?php else: foreach ($internRows as $index => $intern): ?>
        <tr>
          <td><?= Security::e(format_number($offset + $index + 1)) ?></td>
          <td><strong><?= Security::e((string)$intern['user_name']) ?></strong><small><?= Security::e((string)$intern['email']) ?></small></td>
          <td><span class="badge <?= Security::e(status_badge_class((string)$intern['status'])) ?>"><?= Security::e(status_label((string)$intern['status'])) ?></span></td>
          <td><span class="badge badge--success">Assigned</span><small><?= Security::e($intern['assigned_at'] ? format_datetime($intern['assigned_at']) : '-') ?></small></td>
          <td><?= Security::e((string)($intern['assigned_by_name'] ?: '-')) ?></td>
          <td>
            <form method="post" class="sa-workloc-row-action">
              <?= Csrf::field($csrfForm) ?>
              <input type="hidden" name="location_id" value="<?= $selectedLocationId ?>">
              <input type="hidden" name="user_id" value="<?= (int)$intern['id'] ?>">
              <button class="btn btn--outline btn--sm" type="submit" name="action" value="remove_user"><i class="fa-solid fa-user-minus" aria-hidden="true"></i> Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?= work_location_pagination($page, $totalPages, $totalRows, $perPage, $selectedLocationId, $assignmentSearch) ?>
</section>
<?php endif; ?>

<?php include dirname(__DIR__, 2) . '/app/partials/admin/shell-end.php'; ?>

<?php
function work_location_pagination(int $page, int $totalPages, int $total, int $perPage, int $locationId, string $q): string
{
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $to = min($total, $page * $perPage);
    ob_start();
?>
  <nav class="sa-project-pagination pagination" aria-label="Intern assignment pagination">
    <span>Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?></span>
    <div class="pagination__links">
      <a class="btn btn--sm btn--outline <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(work_location_page_url(max(1, $page - 1), $locationId, $q)) ?>">Previous</a>
      <span class="pagination__current">Page <?= Security::e(format_number($page)) ?> of <?= Security::e(format_number($totalPages)) ?></span>
      <a class="btn btn--sm btn--outline <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(work_location_page_url(min($totalPages, $page + 1), $locationId, $q)) ?>">Next</a>
    </div>
  </nav>
<?php
    return ob_get_clean();
}

function work_location_page_url(int $page, int $locationId, string $q): string
{
    $query = ['location_id' => $locationId, 'page' => $page];
    if ($q !== '') {
        $query['q'] = $q;
    }
    return Url::to('admin/superadmin/work-locations.php?' . http_build_query($query));
}
