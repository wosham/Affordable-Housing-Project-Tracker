<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_project_edit';
$lookup = $_GET['id'] ?? ($_GET['slug'] ?? '');
$project = Project::findDetailed((string)$lookup);

if (!$project) {
    Session::flash('error', 'Project could not be found.');
    Response::redirect(Url::to('admin/superadmin/projects.php'));
}

$projectId = (int)$project['id'];
$projectMilestones = Milestone::forProject($projectId);
$projectGeoFence = GeoFenceModel::forProject($projectId);
$errors = [];
$teamUsers = project_team_users();
$teamSelections = project_team_existing($projectId);
$old = project_edit_old($project, $projectGeoFence);
$milestoneRows = $projectMilestones ?: array_fill(0, 5, ['label' => '', 'target_date' => '', 'actual_date' => '', 'status' => 'pending']);

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        $errors['csrf'] = 'Your session token expired. Please reload and try again.';
    }

    $old = array_merge($old, project_form_input());
    $teamSelections = project_team_input($_POST['team'] ?? []);
    $milestoneRows = is_array($_POST['milestones'] ?? null) ? $_POST['milestones'] : $milestoneRows;
    $errors = array_merge($errors, project_validate($old), project_team_validate($teamSelections, $teamUsers));

    if ($errors === []) {
        $slug = $old['slug'] !== '' ? Project::buildSlug($old['slug']) : Project::buildSlug($old['name']);

        if (Project::slugExists($slug, $projectId)) {
            $errors['slug'] = 'This slug is already used by another project.';
        } else {
            Database::beginTransaction();
            try {
                $payload = array_merge(project_payload($old, $slug), project_legacy_person_ids($teamSelections));
                Project::update($projectId, $payload);
                save_project_geo_fence($projectId, $old);
                sync_project_team($projectId, $teamSelections, $teamUsers);
                save_project_milestones($projectId, $milestoneRows);
                Database::commit();

                Logger::log('update', 'projects', $projectId, ['name' => $old['name']]);
                Session::flash('status', 'Project updated successfully.');
                Response::redirect(Url::to('admin/superadmin/project-edit.php?id=' . $projectId));
            } catch (Throwable $e) {
                Database::rollBack();
                $errors['form'] = 'Project could not be updated. Please check the fields and try again.';
                Logger::error('Project update failed', ['project_id' => $projectId, 'error' => $e->getMessage()]);
            }
        }
    }
}

$categories = ProjectCategory::findAll([], 'name ASC');
$constituencies = Constituency::findAll([], 'name ASC');
$wards = Database::fetchAll('SELECT * FROM wards ORDER BY name ASC');
$childSummary = Project::childSummary($projectId);
$relatedRecords = array_sum($childSummary);

$pageTitle = 'Edit Project';
$pageDescription = 'Update project delivery data, content and milestones.';
$adminRole = 'superadmin';
$contentClass = 'sa-project-form-page';
$componentCss = ['cms-editor', 'media-library'];
$pageScripts = ['media-picker', 'project-form'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Projects', 'url' => Url::to('admin/superadmin/projects.php')],
    ['label' => 'Edit Project'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-projects-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Project Editor</span>
    <h2><?= Security::e($project['name']) ?></h2>
    <p>Update delivery status, public content, contractor details, timelines and milestone progress for this project.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Projects</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('project-detail.php?id=' . urlencode((string)$project['slug']))) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public Page</a>
  </div>
</section>

<?php if ($errors): ?>
  <div class="alert alert--danger">
    <strong>Check the form.</strong>
    <span><?= Security::e($errors['form'] ?? $errors['csrf'] ?? 'Some fields need attention before saving.') ?></span>
  </div>
<?php endif; ?>

<section class="sa-form-summary-grid" aria-label="Project record summary">
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_percentage($project['pct_complete'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Current Progress</span>
      <small class="stat-widget__trend"><?= Security::e(status_label($project['status'] ?? 'planning')) ?></small>
    </span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_number($project['units'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Target Units</span>
      <small class="stat-widget__trend"><?= Security::e($project['constituency_name'] ?? 'County programme') ?></small>
    </span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_money($project['contract_sum'] ?? 0)) ?></strong>
      <span class="stat-widget__label">Contract Value</span>
      <small class="stat-widget__trend"><?= Security::e($project['contractor_name'] ?: 'Contractor not assigned') ?></small>
    </span>
  </article>
  <article class="stat-widget">
    <span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_number($relatedRecords)) ?></strong>
      <span class="stat-widget__label">Linked Records</span>
      <small class="stat-widget__trend">Milestones, IPCs, attendance and site data</small>
    </span>
  </article>
</section>

<form class="sa-project-form" method="post" action="<?= Security::e(Url::to('admin/superadmin/project-edit.php?id=' . $projectId)) ?>">
  <?= Csrf::field($csrfForm) ?>
  <div class="sa-editor-layout">
    <div class="sa-editor-main">
      <?php render_project_form($old, $errors, $categories, $constituencies, $wards); ?>
      <?php render_project_team_form($teamSelections, $teamUsers, $errors); ?>
      <?php render_milestone_editor($milestoneRows); ?>
      <div class="sa-form-actionbar">
        <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure CSRF-protected project update workflow</span>
        <div>
          <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/projects.php')) ?>">Cancel</a>
          <button class="btn btn--primary btn--lg" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes</button>
        </div>
      </div>
    </div>
    <aside class="sa-editor-aside" aria-label="Project preview">
      <?php render_project_preview($old, 'Live Editor Preview'); ?>
    </aside>
  </div>
</form>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function project_edit_old(array $project, ?array $geoFence = null): array
{
    $images = json_decode((string)($project['images_json'] ?? '[]'), true);
    $images = is_array($images) ? implode(PHP_EOL, $images) : '';

    return [
        'name' => (string)($project['name'] ?? ''),
        'slug' => (string)($project['slug'] ?? ''),
        'category_id' => (string)($project['category_id'] ?? ''),
        'constituency_id' => (string)($project['constituency_id'] ?? ''),
        'ward_id' => (string)($project['ward_id'] ?? ''),
        'location_label' => (string)($project['location_label'] ?? ''),
        'status' => (string)($project['status'] ?? 'planning'),
        'pct_complete' => (string)($project['pct_complete'] ?? '0'),
        'units' => (string)($project['units'] ?? ''),
        'contract_sum' => (string)($project['contract_sum'] ?? ''),
        'start_date' => (string)($project['start_date'] ?? ''),
        'est_delivery' => (string)($project['est_delivery'] ?? ''),
        'current_milestone' => (string)($project['current_milestone'] ?? ''),
        'contractor_name' => (string)($project['contractor_name'] ?? ''),
        'site_engineer' => (string)($project['site_engineer'] ?? ''),
        'funding_source' => (string)($project['funding_source'] ?? ''),
        'lead_agency' => (string)($project['lead_agency'] ?? ''),
        'hero_image' => (string)($project['hero_image'] ?? ''),
        'images' => $images,
        'description' => (string)($project['description'] ?? ''),
        'is_featured' => (string)($project['is_featured'] ?? '0'),
        'geo_site_name' => (string)($geoFence['site_name'] ?? ''),
        'geo_latitude' => (string)($geoFence['latitude'] ?? ''),
        'geo_longitude' => (string)($geoFence['longitude'] ?? ''),
        'geo_radius_meters' => (string)($geoFence['radius_meters'] ?? max(20, min(3000, SystemConfig::int('attendance.default_geo_radius_m', 150)))),
        'geo_status' => (string)($geoFence['status'] ?? 'configured'),
        'geo_notes' => (string)($geoFence['notes'] ?? ''),
    ];
}

function project_form_input(): array
{
    return [
        'name' => Security::cleanString((string)($_POST['name'] ?? '')),
        'slug' => Project::buildSlug(Security::cleanString((string)($_POST['slug'] ?? ''))),
        'category_id' => (string)Security::cleanInt($_POST['category_id'] ?? 0),
        'constituency_id' => (string)Security::cleanInt($_POST['constituency_id'] ?? 0),
        'ward_id' => (string)Security::cleanInt($_POST['ward_id'] ?? 0),
        'location_label' => Security::cleanString((string)($_POST['location_label'] ?? '')),
        'status' => Security::cleanString((string)($_POST['status'] ?? 'planning')),
        'pct_complete' => (string)percentage($_POST['pct_complete'] ?? 0),
        'units' => (string)max(0, Security::cleanInt($_POST['units'] ?? 0)),
        'contract_sum' => (string)max(0, Security::cleanFloat($_POST['contract_sum'] ?? 0)),
        'start_date' => Security::cleanString((string)($_POST['start_date'] ?? '')),
        'est_delivery' => Security::cleanString((string)($_POST['est_delivery'] ?? '')),
        'current_milestone' => Security::cleanString((string)($_POST['current_milestone'] ?? '')),
        'contractor_name' => Security::cleanString((string)($_POST['contractor_name'] ?? '')),
        'site_engineer' => Security::cleanString((string)($_POST['site_engineer'] ?? '')),
        'funding_source' => Security::cleanString((string)($_POST['funding_source'] ?? '')),
        'lead_agency' => Security::cleanString((string)($_POST['lead_agency'] ?? '')),
        'hero_image' => trim((string)($_POST['hero_image'] ?? '')),
        'images' => trim((string)($_POST['images'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'is_featured' => isset($_POST['is_featured']) ? '1' : '0',
        'geo_site_name' => Security::cleanString((string)($_POST['geo_site_name'] ?? '')),
        'geo_latitude' => trim((string)($_POST['geo_latitude'] ?? '')),
        'geo_longitude' => trim((string)($_POST['geo_longitude'] ?? '')),
        'geo_radius_meters' => (string)max(0, Security::cleanInt($_POST['geo_radius_meters'] ?? SystemConfig::int('attendance.default_geo_radius_m', 150))),
        'geo_status' => Security::cleanString((string)($_POST['geo_status'] ?? 'configured')),
        'geo_notes' => trim((string)($_POST['geo_notes'] ?? '')),
    ];
}

function project_validate(array $data): array
{
    $errors = [];
    if ($data['name'] === '') { $errors['name'] = 'Project name is required.'; }
    if ((int)$data['category_id'] <= 0) { $errors['category_id'] = 'Choose a category.'; }
    if ((int)$data['constituency_id'] <= 0) { $errors['constituency_id'] = 'Choose a constituency.'; }
    if ((int)$data['ward_id'] > 0 && !project_ward_belongs((int)$data['ward_id'], (int)$data['constituency_id'])) {
        $errors['ward_id'] = 'Selected ward does not belong to this constituency.';
    }
    if (!in_array($data['status'], Project::statusOptions(), true)) { $errors['status'] = 'Choose a valid status.'; }
    if ($data['start_date'] !== '' && project_form_date($data['start_date']) === null) { $errors['start_date'] = 'Start date is invalid.'; }
    if ($data['est_delivery'] !== '' && project_form_date($data['est_delivery']) === null) { $errors['est_delivery'] = 'Expected completion date is invalid.'; }
    if ($data['start_date'] !== '' && $data['est_delivery'] !== '' && project_form_date($data['start_date']) !== null && project_form_date($data['est_delivery']) !== null && project_form_date($data['start_date']) > project_form_date($data['est_delivery'])) {
        $errors['est_delivery'] = 'Expected completion cannot be earlier than the start date.';
    }
    $hasGeo = $data['geo_latitude'] !== '' || $data['geo_longitude'] !== '' || $data['geo_site_name'] !== '';
    if ($hasGeo) {
        if ($data['geo_latitude'] === '' || !is_numeric($data['geo_latitude']) || (float)$data['geo_latitude'] < -90 || (float)$data['geo_latitude'] > 90) {
            $errors['geo_latitude'] = 'Enter a valid latitude between -90 and 90.';
        }
        if ($data['geo_longitude'] === '' || !is_numeric($data['geo_longitude']) || (float)$data['geo_longitude'] < -180 || (float)$data['geo_longitude'] > 180) {
            $errors['geo_longitude'] = 'Enter a valid longitude between -180 and 180.';
        }
        if ((int)$data['geo_radius_meters'] < 20 || (int)$data['geo_radius_meters'] > 3000) {
            $errors['geo_radius_meters'] = 'Radius must be between 20m and 3000m.';
        }
        if (!in_array($data['geo_status'], ['configured', 'missing', 'needs-review'], true)) {
            $errors['geo_status'] = 'Choose a valid geo-fence status.';
        }
    }
    return $errors;
}

function project_ward_belongs(int $wardId, int $constituencyId): bool
{
    if ($wardId <= 0) {
        return true;
    }

    if ($constituencyId <= 0) {
        return false;
    }

    return Database::fetch(
        'SELECT id FROM wards WHERE id = ? AND constituency_id = ? LIMIT 1',
        [$wardId, $constituencyId]
    ) !== null;
}

function project_payload(array $data, string $slug): array
{
    $images = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $data['images']) ?: []))));

    return [
        'category_id' => (int)$data['category_id'],
        'constituency_id' => (int)$data['constituency_id'],
        'ward_id' => (int)$data['ward_id'] > 0 ? (int)$data['ward_id'] : null,
        'name' => $data['name'],
        'slug' => $slug,
        'location_label' => $data['location_label'] ?: null,
        'status' => $data['status'],
        'pct_complete' => percentage($data['pct_complete']),
        'units' => (int)$data['units'],
        'contract_sum' => $data['contract_sum'] !== '' ? (float)$data['contract_sum'] : null,
        'start_date' => project_form_date($data['start_date']),
        'est_delivery' => project_form_date($data['est_delivery']),
        'current_milestone' => $data['current_milestone'] ?: null,
        'contractor_name' => $data['contractor_name'] ?: null,
        'description' => $data['description'] ?: null,
        'hero_image' => $data['hero_image'] ?: null,
        'images_json' => json_encode($images, JSON_UNESCAPED_SLASHES),
        'funding_source' => $data['funding_source'] ?: null,
        'lead_agency' => $data['lead_agency'] ?: null,
        'site_engineer' => $data['site_engineer'] ?: null,
        'is_featured' => (int)$data['is_featured'],
    ];
}

function save_project_milestones(int $projectId, mixed $rows): void
{
    if (!is_array($rows)) {
        return;
    }

    $existing = Database::fetchAll('SELECT id FROM milestones WHERE project_id = ?', [$projectId]);
    $existingIds = array_map('intval', array_column($existing, 'id'));
    $sequence = 1;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = max(0, Security::cleanInt($row['id'] ?? 0));
        $label = Security::cleanString((string)($row['label'] ?? ''));
        $status = Security::cleanString((string)($row['status'] ?? 'pending'));
        if (!in_array($status, ['pending', 'current', 'done'], true)) {
            $status = 'pending';
        }
        $target = Security::cleanString((string)($row['target_date'] ?? ''));
        $actual = Security::cleanString((string)($row['actual_date'] ?? ''));

        if ($id > 0 && in_array($id, $existingIds, true) && $label === '') {
            Database::query('DELETE FROM milestones WHERE id = ? AND project_id = ?', [$id, $projectId]);
            continue;
        }

        if ($label === '') {
            continue;
        }

        if ($id > 0 && in_array($id, $existingIds, true)) {
            Database::query(
                "UPDATE milestones
                 SET label = ?, target_date = ?, actual_date = ?, status = ?, sequence = ?, updated_by = ?
                 WHERE id = ? AND project_id = ?",
                [$label, project_form_date($target), project_form_date($actual), $status, $sequence++, (int)(Auth::id() ?: 0) ?: null, $id, $projectId]
            );
            continue;
        }

        Database::query(
            "INSERT INTO milestones (project_id, label, target_date, actual_date, status, sequence, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$projectId, $label, project_form_date($target), project_form_date($actual), $status, $sequence++, (int)(Auth::id() ?: 0) ?: null]
        );
    }
}
function save_project_geo_fence(int $projectId, array $data): void
{
    $lat = trim((string)($data['geo_latitude'] ?? ''));
    $lng = trim((string)($data['geo_longitude'] ?? ''));
    $siteName = trim((string)($data['geo_site_name'] ?? ''));

    if ($lat === '' && $lng === '' && $siteName === '') {
        Database::query('DELETE FROM geo_fences WHERE project_id = ?', [$projectId]);
        return;
    }

    if ($lat === '' || $lng === '') {
        return;
    }

    $existing = GeoFenceModel::forProject($projectId);
    $payload = [
        $siteName !== '' ? $siteName : ($data['name'] ?? 'Project Site'),
        (float)$lat,
        (float)$lng,
        max(20, min(3000, (int)($data['geo_radius_meters'] ?? 200))),
        $data['geo_status'] ?? 'configured',
        (int)Auth::id(),
        date('Y-m-d H:i:s'),
        trim((string)($data['geo_notes'] ?? '')) ?: null,
    ];

    if ($existing) {
        $payload[] = $projectId;
        Database::query(
            "UPDATE geo_fences
             SET site_name = ?, latitude = ?, longitude = ?, radius_meters = ?, status = ?, verified_by = ?, verified_at = ?, notes = ?
             WHERE project_id = ?",
            $payload
        );
        return;
    }

    Database::query(
        "INSERT INTO geo_fences (site_name, latitude, longitude, radius_meters, status, verified_by, verified_at, notes, project_id, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_merge($payload, [$projectId, (int)Auth::id()])
    );
}

function project_form_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="form-error">' . Security::e($errors[$key]) . '</span>' : '';
}

function project_team_roles(): array
{
    return ['manager', 'consultant', 'contractor', 'clerk', 'intern'];
}

function project_team_empty(): array
{
    return array_fill_keys(project_team_roles(), []);
}

function project_team_input(mixed $source): array
{
    $team = project_team_empty();
    if (!is_array($source)) {
        return $team;
    }

    foreach (project_team_roles() as $role) {
        $values = $source[$role] ?? [];
        if (!is_array($values)) {
            $values = [$values];
        }
        $team[$role] = array_values(array_unique(array_filter(array_map('intval', $values), static fn (int $id): bool => $id > 0)));
    }

    return $team;
}

function project_team_users(): array
{
    return Database::fetchAll(
        "SELECT u.id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) AS name,
                u.email, u.job_title, r.slug AS role_slug, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.status = 'active' AND r.slug IN ('manager', 'consultant', 'contractor', 'clerk', 'intern')
         ORDER BY FIELD(r.slug, 'manager', 'consultant', 'contractor', 'clerk', 'intern'), u.first_name ASC, u.last_name ASC"
    );
}

function project_team_existing(int $projectId): array
{
    $team = project_team_empty();
    $rows = Database::fetchAll(
        "SELECT pa.user_id, pa.role
         FROM project_assignments pa
         INNER JOIN users u ON u.id = pa.user_id
         WHERE pa.project_id = ? AND pa.status = 'active' AND u.status = 'active'",
        [$projectId]
    );

    foreach ($rows as $row) {
        $role = (string)($row['role'] ?? '');
        if (array_key_exists($role, $team)) {
            $team[$role][] = (int)$row['user_id'];
        }
    }

    return array_map(static fn (array $ids): array => array_values(array_unique($ids)), $team);
}

function project_team_validate(array $team, array $users): array
{
    $errors = [];
    $userRoles = [];
    foreach ($users as $user) {
        $userRoles[(int)$user['id']] = (string)$user['role_slug'];
    }

    foreach ($team as $role => $ids) {
        if (!in_array($role, project_team_roles(), true)) {
            continue;
        }
        foreach ($ids as $userId) {
            if (($userRoles[(int)$userId] ?? '') !== $role) {
                $errors['team_' . $role] = 'One selected ' . role_label($role) . ' account is no longer valid.';
                break;
            }
        }
    }

    return $errors;
}

function project_legacy_person_ids(array $team): array
{
    return [
        'contractor_id' => !empty($team['contractor']) ? (int)$team['contractor'][0] : null,
        'consultant_id' => !empty($team['consultant']) ? (int)$team['consultant'][0] : null,
    ];
}

function sync_project_team(int $projectId, array $team, array $users): void
{
    $actorId = (int)Auth::id();
    $validByRole = [];
    foreach ($users as $user) {
        $validByRole[(string)$user['role_slug']][] = (int)$user['id'];
    }

    foreach (project_team_roles() as $role) {
        $selected = array_values(array_intersect($team[$role] ?? [], $validByRole[$role] ?? []));
        $existingRows = Database::fetchAll(
            'SELECT id, user_id, status FROM project_assignments WHERE project_id = ? AND role = ?',
            [$projectId, $role]
        );
        $existingByUser = [];
        foreach ($existingRows as $row) {
            $existingByUser[(int)$row['user_id']] = $row;
        }

        foreach ($selected as $index => $userId) {
            if (isset($existingByUser[$userId])) {
                $row = $existingByUser[$userId];
                if ((string)$row['status'] !== 'active') {
                    ProjectAssignment::reactivate((int)$row['id'], $actorId);
                }
                ProjectAssignment::update((int)$row['id'], [
                    'role' => $role,
                    'assignment_type' => ProjectAssignment::defaultTypeForRole($role),
                    'scope' => ProjectAssignment::defaultScopeForRole($role),
                    'is_primary' => $index === 0 ? 1 : 0,
                    'updated_by' => $actorId ?: null,
                ]);
                continue;
            }

            ProjectAssignment::assign([
                'project_id' => $projectId,
                'user_id' => $userId,
                'assignment_type' => ProjectAssignment::defaultTypeForRole($role),
                'scope' => ProjectAssignment::defaultScopeForRole($role),
                'status' => 'active',
                'is_primary' => $index === 0 ? 1 : 0,
                'assigned_by' => $actorId,
                'notes' => 'Assigned from project master record.',
            ]);
        }

        foreach ($existingRows as $row) {
            $userId = (int)$row['user_id'];
            if (!in_array($userId, $selected, true) && (string)$row['status'] === 'active') {
                ProjectAssignment::revoke((int)$row['id'], $actorId);
            }
        }
    }
}

function render_project_team_form(array $team, array $users, array $errors): void
{
    $usersByRole = [];
    foreach ($users as $user) {
        $usersByRole[(string)$user['role_slug']][] = $user;
    }
?>
  <section class="card sa-form-card">
    <div class="card__header"><div><h2 class="card__title">Project Team & Access</h2><p class="card__subtitle">Assign who can see and work on this project across role dashboards.</p></div></div>
    <div class="form-grid form-grid--2">
<?php foreach (project_team_roles() as $role): ?>
      <label class="form-field"><span class="form-label"><?= Security::e(role_label($role)) ?></span>
        <select class="form-select <?= isset($errors['team_' . $role]) ? 'is-error' : '' ?>" name="team[<?= Security::e($role) ?>][]" multiple size="<?= min(5, max(3, count($usersByRole[$role] ?? []))) ?>">
<?php foreach (($usersByRole[$role] ?? []) as $user): ?>
          <option value="<?= (int)$user['id'] ?>" <?= in_array((int)$user['id'], $team[$role] ?? [], true) ? 'selected' : '' ?>><?= Security::e(trim((string)$user['name']) ?: (string)$user['email']) ?><?= trim((string)($user['job_title'] ?? '')) !== '' ? ' - ' . Security::e((string)$user['job_title']) : '' ?></option>
<?php endforeach; ?>
        </select><?= field_error($errors, 'team_' . $role) ?>
        <span class="form-hint">Hold Ctrl to select more than one. First selected contractor/consultant is also saved to the legacy project owner fields.</span>
      </label>
<?php endforeach; ?>
    </div>
  </section>
<?php
}
function project_media_paths(string $value): array
{
    return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value) ?: []))));
}

function project_media_filename(string $path): string
{
    $name = basename(parse_url($path, PHP_URL_PATH) ?: $path);
    return $name !== '' ? $name : 'Selected media';
}

function render_project_hero_picker(string $heroPath): void
{
    $hasHero = trim($heroPath) !== '';
?>
  <div class="sa-project-media-panel sa-project-hero-picker<?= $hasHero ? ' has-media' : '' ?>" data-cms-upload data-upload-folder="projects" data-media-kind="image" data-project-hero-picker>
    <div class="sa-project-media-panel__top">
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-panorama" aria-hidden="true"></i> Hero Image</span>
        <h4 data-cms-asset-name><?= $hasHero ? Security::e(project_media_filename($heroPath)) : 'No hero selected' ?></h4>
      </div>
      <div class="sa-project-media-panel__actions">
        <button class="btn btn--primary btn--sm" type="button" data-media-picker-open data-media-picker-folder="projects" data-media-picker-type="image" data-media-picker-title="Choose project hero"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose</button>
        <button class="btn btn--outline btn--sm" type="button" data-project-clear-hero><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</button>
      </div>
    </div>
    <div class="sa-project-hero-preview" data-cms-asset-preview>
<?php if ($hasHero): ?>
      <img src="<?= Security::e(Url::asset($heroPath)) ?>" alt="">
<?php else: ?>
      <span><i class="fa-solid fa-image" aria-hidden="true"></i></span>
<?php endif; ?>
    </div>
    <input type="hidden" name="hero_image" value="<?= Security::e($heroPath) ?>" data-cms-upload-target data-project-hero-input>
    <p class="sa-project-media-path" data-project-hero-path><?= $hasHero ? Security::e($heroPath) : 'Choose an approved media asset for the public hero.' ?></p>
  </div>
<?php
}

function render_project_gallery_picker(string $images): void
{
    $paths = project_media_paths($images);
?>
  <div class="sa-project-media-panel sa-project-gallery-picker<?= $paths !== [] ? ' has-media' : '' ?>" data-cms-upload data-upload-folder="projects" data-media-kind="image" data-project-gallery-picker>
    <div class="sa-project-media-panel__top">
      <div>
        <span class="sa-panel-label"><i class="fa-solid fa-images" aria-hidden="true"></i> Gallery Images</span>
        <h4 data-cms-asset-name><?= $paths !== [] ? Security::e(count($paths) . ' image' . (count($paths) === 1 ? '' : 's') . ' selected') : 'No gallery images selected' ?></h4>
      </div>
      <div class="sa-project-media-panel__actions">
        <button class="btn btn--primary btn--sm" type="button" data-media-picker-open data-media-picker-multiple data-media-picker-folder="projects" data-media-picker-type="image" data-media-picker-title="Choose project gallery images"><i class="fa-solid fa-photo-film" aria-hidden="true"></i> Choose</button>
      </div>
    </div>
    <textarea class="sa-project-gallery-value" name="images" data-cms-upload-target data-project-gallery-input><?= Security::e(implode(PHP_EOL, $paths)) ?></textarea>
    <div class="sa-project-gallery-grid" data-project-gallery-grid>
<?php if ($paths === []): ?>
      <div class="sa-project-gallery-empty"><i class="fa-solid fa-image" aria-hidden="true"></i><strong>No gallery media selected</strong><span>Use the media library picker to attach project images.</span></div>
<?php else: foreach ($paths as $path): ?>
      <article class="sa-project-gallery-item" data-gallery-path="<?= Security::e($path) ?>">
        <img src="<?= Security::e(Url::asset($path)) ?>" alt="">
        <div><strong><?= Security::e(project_media_filename($path)) ?></strong><small><?= Security::e($path) ?></small></div>
        <button class="btn btn--icon btn--outline" type="button" data-project-remove-gallery="<?= Security::e($path) ?>" aria-label="Remove gallery image"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </article>
<?php endforeach; endif; ?>
    </div>
  </div>
<?php
}
function render_project_form(array $old, array $errors, array $categories, array $constituencies, array $wards): void
{
?>
  <section class="card sa-form-card">
    <div class="card__header"><div><h2 class="card__title">Project Identity</h2><p class="card__subtitle">Core public and administrative details used across dashboards and project pages.</p></div></div>
    <div class="form-grid form-grid--2">
      <label class="form-field"><span class="form-label">Project name</span><input class="form-input <?= isset($errors['name']) ? 'is-error' : '' ?>" name="name" value="<?= Security::e($old['name']) ?>" required><?= field_error($errors, 'name') ?></label>
      <label class="form-field"><span class="form-label">Slug</span><input class="form-input <?= isset($errors['slug']) ? 'is-error' : '' ?>" name="slug" value="<?= Security::e($old['slug']) ?>" placeholder="auto-generated if empty"><?= field_error($errors, 'slug') ?><span class="form-hint">Used in public links and project pages.</span></label>
      <label class="form-field"><span class="form-label">Category</span><select class="form-select <?= isset($errors['category_id']) ? 'is-error' : '' ?>" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= Security::e((string)$category['id']) ?>" <?= (string)$old['category_id'] === (string)$category['id'] ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select><?= field_error($errors, 'category_id') ?></label>
      <label class="form-field"><span class="form-label">Status</span><select class="form-select <?= isset($errors['status']) ? 'is-error' : '' ?>" name="status"><?php foreach (Project::statusOptions() as $status): ?><option value="<?= Security::e($status) ?>" <?= $old['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select><?= field_error($errors, 'status') ?></label>
    </div>
  </section>

  <section class="card sa-form-card">
    <div class="card__header"><div><h2 class="card__title">Location & Delivery</h2><p class="card__subtitle">Constituency, ward, units and programme progress.</p></div></div>
    <div class="form-grid form-grid--3">
      <label class="form-field"><span class="form-label">Constituency</span><select class="form-select <?= isset($errors['constituency_id']) ? 'is-error' : '' ?>" name="constituency_id" data-project-constituency required><option value="">Choose constituency</option><?php foreach ($constituencies as $constituency): ?><option value="<?= Security::e((string)$constituency['id']) ?>" <?= (string)$old['constituency_id'] === (string)$constituency['id'] ? 'selected' : '' ?>><?= Security::e($constituency['name']) ?></option><?php endforeach; ?></select><?= field_error($errors, 'constituency_id') ?></label>
      <label class="form-field"><span class="form-label">Ward</span><select class="form-select <?= isset($errors['ward_id']) ? 'is-error' : '' ?>" name="ward_id" data-project-ward data-selected-ward="<?= Security::e($old['ward_id']) ?>" <?= (int)$old['constituency_id'] <= 0 ? 'disabled' : '' ?>><option value="">Choose constituency first</option><?php foreach ($wards as $ward): ?><option value="<?= Security::e((string)$ward['id']) ?>" data-constituency-id="<?= Security::e((string)$ward['constituency_id']) ?>" <?= (string)$old['ward_id'] === (string)$ward['id'] ? 'selected' : '' ?>><?= Security::e($ward['name']) ?></option><?php endforeach; ?></select><?= field_error($errors, 'ward_id') ?><span class="form-hint">Wards are filtered by the selected constituency.</span></label>
      <label class="form-field"><span class="form-label">Location label</span><input class="form-input" name="location_label" value="<?= Security::e($old['location_label']) ?>" placeholder="Saboti, Matisi Ward"></label>
      <label class="form-field"><span class="form-label">Progress %</span><div class="sa-progress-input"><input class="form-input" type="number" min="0" max="100" name="pct_complete" value="<?= Security::e($old['pct_complete']) ?>"><input type="range" min="0" max="100" value="<?= Security::e($old['pct_complete']) ?>" data-project-progress-range></div></label>
      <label class="form-field"><span class="form-label">Units</span><input class="form-input" type="number" min="0" name="units" value="<?= Security::e($old['units']) ?>"></label>
      <label class="form-field"><span class="form-label">Contract value</span><input class="form-input" type="number" min="0" step="0.01" name="contract_sum" value="<?= Security::e($old['contract_sum']) ?>"></label>
      <label class="form-field"><span class="form-label">Start date</span><input class="form-input <?= isset($errors['start_date']) ? 'is-error' : '' ?>" type="date" name="start_date" value="<?= Security::e($old['start_date']) ?>"><?= field_error($errors, 'start_date') ?></label>
      <label class="form-field"><span class="form-label">Expected completion</span><input class="form-input <?= isset($errors['est_delivery']) ? 'is-error' : '' ?>" type="date" name="est_delivery" value="<?= Security::e($old['est_delivery']) ?>"><?= field_error($errors, 'est_delivery') ?></label>
      <label class="form-field"><span class="form-label">Current milestone</span><input class="form-input" name="current_milestone" value="<?= Security::e($old['current_milestone']) ?>"></label>
    </div>
  </section>

  <section class="card sa-form-card sa-geo-card">
    <div class="card__header"><div><h2 class="card__title">Site Geo-Fence</h2><p class="card__subtitle">GPS boundary used to verify clerk and intern attendance from the project site.</p></div><span class="badge <?= ($old['geo_latitude'] ?? '') !== '' && ($old['geo_longitude'] ?? '') !== '' ? 'badge--success' : 'badge--warning' ?>"><?= ($old['geo_latitude'] ?? '') !== '' && ($old['geo_longitude'] ?? '') !== '' ? 'Configured' : 'Missing' ?></span></div>
    <div class="sa-geo-grid">
      <label class="form-field"><span class="form-label">Site / gate name</span><input class="form-input" name="geo_site_name" value="<?= Security::e($old['geo_site_name'] ?? '') ?>" placeholder="Main gate or site office"></label>
      <label class="form-field"><span class="form-label">Latitude</span><input class="form-input <?= isset($errors['geo_latitude']) ? 'is-error' : '' ?>" type="number" step="0.00000001" name="geo_latitude" value="<?= Security::e($old['geo_latitude'] ?? '') ?>" placeholder="1.01234567"><?= field_error($errors, 'geo_latitude') ?></label>
      <label class="form-field"><span class="form-label">Longitude</span><input class="form-input <?= isset($errors['geo_longitude']) ? 'is-error' : '' ?>" type="number" step="0.00000001" name="geo_longitude" value="<?= Security::e($old['geo_longitude'] ?? '') ?>" placeholder="35.01234567"><?= field_error($errors, 'geo_longitude') ?></label>
      <label class="form-field"><span class="form-label">Allowed radius (m)</span><input class="form-input <?= isset($errors['geo_radius_meters']) ? 'is-error' : '' ?>" type="number" min="20" max="3000" name="geo_radius_meters" value="<?= Security::e($old['geo_radius_meters'] ?? '200') ?>"><?= field_error($errors, 'geo_radius_meters') ?><span class="form-hint">Recommended: 100m to 300m for most sites.</span></label>
      <label class="form-field"><span class="form-label">Geo status</span><select class="form-select <?= isset($errors['geo_status']) ? 'is-error' : '' ?>" name="geo_status"><?php foreach (['configured', 'needs-review', 'missing'] as $status): ?><option value="<?= Security::e($status) ?>" <?= ($old['geo_status'] ?? 'configured') === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select><?= field_error($errors, 'geo_status') ?></label>
      <label class="form-field form-field--full"><span class="form-label">Geo notes</span><textarea class="form-textarea" name="geo_notes" rows="3" placeholder="Boundary notes, landmark, access gate, or verification details"><?= Security::e($old['geo_notes'] ?? '') ?></textarea></label>
    </div>
  </section>

    <section class="card sa-form-card sa-public-content-card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Contract & Public Content</h2>
        <p class="card__subtitle">Contract metadata, publication media and the public project narrative.</p>
      </div>
    </div>

    <div class="sa-project-subsection">
      <div class="sa-project-subsection__header">
        <span><i class="fa-solid fa-file-contract" aria-hidden="true"></i></span>
        <div>
          <h3>Contract Details</h3>
          <p>These fields feed project dashboards, summaries and public project cards.</p>
        </div>
      </div>
      <div class="form-grid form-grid--2">
        <label class="form-field"><span class="form-label">Contractor</span><input class="form-input" name="contractor_name" value="<?= Security::e($old['contractor_name']) ?>"></label>
        <label class="form-field"><span class="form-label">Site engineer</span><input class="form-input" name="site_engineer" value="<?= Security::e($old['site_engineer']) ?>"></label>
        <label class="form-field"><span class="form-label">Funding source</span><input class="form-input" name="funding_source" value="<?= Security::e($old['funding_source']) ?>"></label>
        <label class="form-field"><span class="form-label">Lead agency</span><input class="form-input" name="lead_agency" value="<?= Security::e($old['lead_agency']) ?>"></label>
        <label class="form-field form-field--full"><span class="form-label">Featured project</span><span class="form-check sa-featured-check"><input type="checkbox" name="is_featured" value="1" <?= $old['is_featured'] === '1' ? 'checked' : '' ?>> Show in featured areas</span></label>
      </div>
    </div>

    <div class="sa-project-subsection sa-project-media-section">
      <div class="sa-project-subsection__header">
        <span><i class="fa-solid fa-photo-film" aria-hidden="true"></i></span>
        <div>
          <h3>Project Media</h3>
          <p>Select approved assets from the media library. New uploads happen inside the picker.</p>
        </div>
      </div>
      <div class="sa-project-media-grid">
        <?php render_project_hero_picker((string)($old['hero_image'] ?? '')); ?>
        <?php render_project_gallery_picker((string)($old['images'] ?? '')); ?>
      </div>
    </div>

    <div class="sa-project-subsection">
      <div class="sa-project-subsection__header">
        <span><i class="fa-solid fa-align-left" aria-hidden="true"></i></span>
        <div>
          <h3>Public Description</h3>
          <p>This copy appears on public project detail pages and search results.</p>
        </div>
      </div>
      <label class="form-field form-field--full"><span class="form-label">Description</span><textarea class="form-textarea" name="description" rows="6"><?= Security::e($old['description']) ?></textarea></label>
    </div>
  </section>
<?php
}

function render_project_preview(array $old, string $label): void
{
    $images = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $old['images'] ?? '') ?: [])));
    $hero = $old['hero_image'] ?: ($images[0] ?? '');
?>
  <div class="sa-editor-preview card">
    <div class="sa-preview-media" data-preview-media>
<?php if ($hero !== ''): ?>
      <img src="<?= Security::e(Url::asset($hero)) ?>" alt="">
<?php else: ?>
      <span><i class="fa-solid fa-building" aria-hidden="true"></i></span>
<?php endif; ?>
    </div>
    <div class="sa-preview-body">
      <span class="sa-panel-label"><i class="fa-solid fa-eye" aria-hidden="true"></i> <?= Security::e($label) ?></span>
      <h3 data-preview-name><?= Security::e($old['name'] ?: 'Untitled project') ?></h3>
      <p data-preview-location><?= Security::e($old['location_label'] ?: 'Choose constituency and ward') ?></p>
      <div class="sa-preview-badges">
        <span class="badge <?= Security::e(status_badge_class($old['status'] ?? 'planning')) ?>" data-preview-status><?= Security::e(status_label($old['status'] ?? 'planning')) ?></span>
        <span class="badge badge--lime" data-preview-progress><?= Security::e(format_percentage($old['pct_complete'] ?? 0)) ?></span>
      </div>
      <div class="sa-preview-progress"><span style="width: <?= Security::e((string)percentage($old['pct_complete'] ?? 0)) ?>%" data-preview-progress-bar></span></div>
      <dl class="sa-preview-list">
        <div><dt>Units</dt><dd data-preview-units><?= Security::e($old['units'] ?: '0') ?></dd></div>
        <div><dt>Contractor</dt><dd data-preview-contractor><?= Security::e($old['contractor_name'] ?: 'Not assigned') ?></dd></div>
        <div><dt>Funding</dt><dd data-preview-funding><?= Security::e($old['funding_source'] ?: 'Not set') ?></dd></div>
      </dl>
    </div>
  </div>
<?php
}

function render_milestone_editor(array $milestones): void
{
    $rows = array_values($milestones);
    while (count($rows) < 5) {
        $rows[] = ['label' => '', 'target_date' => '', 'actual_date' => '', 'status' => 'pending'];
    }
?>
  <section class="card sa-form-card">
    <div class="card__header"><div><h2 class="card__title">Milestones</h2><p class="card__subtitle">Maintain the delivery steps shown on project timelines.</p></div></div>
    <div class="sa-milestone-editor">
<?php foreach ($rows as $index => $milestone): ?>
      <div class="sa-milestone-row">
        <span class="sa-milestone-step"><?= Security::e(str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
        <input type="hidden" name="milestones[<?= (int)$index ?>][id]" value="<?= Security::e((string)($milestone['id'] ?? '')) ?>">
        <label class="form-field"><span class="form-label">Milestone</span><input class="form-input" name="milestones[<?= (int)$index ?>][label]" value="<?= Security::e($milestone['label'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Target</span><input class="form-input" type="date" name="milestones[<?= (int)$index ?>][target_date]" value="<?= Security::e($milestone['target_date'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Actual</span><input class="form-input" type="date" name="milestones[<?= (int)$index ?>][actual_date]" value="<?= Security::e($milestone['actual_date'] ?? '') ?>"></label>
        <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="milestones[<?= (int)$index ?>][status]"><?php foreach (['pending', 'current', 'done'] as $status): ?><option value="<?= Security::e($status) ?>" <?= ($milestone['status'] ?? 'pending') === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
      </div>
<?php endforeach; ?>
    </div>
  </section>
<?php
}
