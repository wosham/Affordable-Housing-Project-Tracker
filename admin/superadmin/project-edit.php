<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

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
$old = project_edit_old($project, $projectGeoFence);
$milestoneRows = $projectMilestones ?: array_fill(0, 5, ['label' => '', 'target_date' => '', 'actual_date' => '', 'status' => 'pending']);

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        $errors['csrf'] = 'Your session token expired. Please reload and try again.';
    }

    $old = array_merge($old, project_form_input());
    $old = project_apply_uploads($old, $errors);
    $milestoneRows = is_array($_POST['milestones'] ?? null) ? $_POST['milestones'] : $milestoneRows;
    $errors = array_merge($errors, project_validate($old));

    if ($errors === []) {
        $slug = $old['slug'] !== '' ? Project::buildSlug($old['slug']) : Project::buildSlug($old['name']);

        if (Project::slugExists($slug, $projectId)) {
            $errors['slug'] = 'This slug is already used by another project.';
        } else {
            Database::beginTransaction();
            try {
                Project::update($projectId, project_payload($old, $slug));
                save_project_geo_fence($projectId, $old);
                Database::query('DELETE FROM milestones WHERE project_id = ?', [$projectId]);
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
$pageScripts = ['project-form'];
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

<form class="sa-project-form" method="post" enctype="multipart/form-data" action="<?= Security::e(Url::to('admin/superadmin/project-edit.php?id=' . $projectId)) ?>">
  <?= Csrf::field($csrfForm) ?>
  <div class="sa-editor-layout">
    <div class="sa-editor-main">
      <?php render_project_form($old, $errors, $categories, $constituencies, $wards); ?>
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

function project_apply_uploads(array $data, array &$errors): array
{
    $slugHint = Project::buildSlug($data['slug'] ?: $data['name'] ?: 'project');

    if (isset($_FILES['hero_upload']) && is_array($_FILES['hero_upload'])) {
        $heroPath = project_store_uploaded_file($_FILES['hero_upload'], $slugHint . '-hero', $errors, 'hero_upload');
        if ($heroPath !== null) {
            $data['hero_image'] = $heroPath;
            $existing = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $data['images']) ?: [])));
            array_unshift($existing, $heroPath);
            $data['images'] = implode(PHP_EOL, array_values(array_unique($existing)));
        }
    }

    if (isset($_FILES['gallery_uploads']) && is_array($_FILES['gallery_uploads'])) {
        $existing = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $data['images']) ?: [])));
        foreach (project_normalize_uploads($_FILES['gallery_uploads']) as $index => $file) {
            $path = project_store_uploaded_file($file, $slugHint . '-gallery-' . ((int)$index + 1), $errors, 'gallery_uploads');
            if ($path !== null) {
                $existing[] = $path;
            }
        }
        $data['images'] = implode(PHP_EOL, array_values(array_unique($existing)));
    }

    return $data;
}

function project_normalize_uploads(array $files): array
{
    $normalized = [];
    foreach (($files['name'] ?? []) as $index => $name) {
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function project_store_uploaded_file(array $file, string $nameHint, array &$errors, string $key): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[$key] = 'One selected image could not be uploaded.';
        return null;
    }

    $original = (string)($file['name'] ?? '');
    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        $errors[$key] = 'Images must be 5MB or smaller.';
        return null;
    }

    if (!Security::extensionAllowed($original, $allowed) || @getimagesize($tmp) === false) {
        $errors[$key] = 'Only valid JPG, PNG, WebP or GIF images are allowed.';
        return null;
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/projects';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        $errors[$key] = 'Upload folder is not writable.';
        return null;
    }

    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $filename = Project::buildSlug($nameHint) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        $errors[$key] = 'Image upload failed. Please try again.';
        return null;
    }

    return 'uploads/projects/' . $filename;
}

function save_project_milestones(int $projectId, mixed $rows): void
{
    if (!is_array($rows)) {
        return;
    }

    $sequence = 1;
    foreach ($rows as $row) {
        if (!is_array($row)) { continue; }
        $label = Security::cleanString((string)($row['label'] ?? ''));
        if ($label === '') { continue; }
        $status = Security::cleanString((string)($row['status'] ?? 'pending'));
        if (!in_array($status, ['pending', 'current', 'done'], true)) { $status = 'pending'; }
        $target = Security::cleanString((string)($row['target_date'] ?? ''));
        $actual = Security::cleanString((string)($row['actual_date'] ?? ''));
        Database::query(
            "INSERT INTO milestones (project_id, label, target_date, actual_date, status, sequence)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$projectId, $label, project_form_date($target), project_form_date($actual), $status, $sequence++]
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

  <section class="card sa-form-card">
    <div class="card__header"><div><h2 class="card__title">Contract & Public Content</h2><p class="card__subtitle">Contractor, agency, imagery and public description.</p></div></div>
    <div class="form-grid form-grid--2">
      <label class="form-field"><span class="form-label">Contractor</span><input class="form-input" name="contractor_name" value="<?= Security::e($old['contractor_name']) ?>"></label>
      <label class="form-field"><span class="form-label">Site engineer</span><input class="form-input" name="site_engineer" value="<?= Security::e($old['site_engineer']) ?>"></label>
      <label class="form-field"><span class="form-label">Funding source</span><input class="form-input" name="funding_source" value="<?= Security::e($old['funding_source']) ?>"></label>
      <label class="form-field"><span class="form-label">Lead agency</span><input class="form-input" name="lead_agency" value="<?= Security::e($old['lead_agency']) ?>"></label>
      <label class="form-field"><span class="form-label">Hero image</span><span class="sa-upload-box"><input type="file" name="hero_upload" accept="image/*"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><strong>Upload hero image</strong><small>JPG, PNG, WebP or GIF up to 5MB</small></span><?= field_error($errors, 'hero_upload') ?><input class="form-input sa-path-input" name="hero_image" value="<?= Security::e($old['hero_image']) ?>" readonly placeholder="Uploaded hero path will appear here"></label>
      <label class="form-field"><span class="form-label">Featured project</span><span class="form-check"><input type="checkbox" name="is_featured" value="1" <?= $old['is_featured'] === '1' ? 'checked' : '' ?>> Show in featured areas</span></label>
      <label class="form-field form-field--full"><span class="form-label">Project gallery</span><span class="sa-upload-box sa-upload-box--wide"><input type="file" name="gallery_uploads[]" accept="image/*" multiple><i class="fa-solid fa-images" aria-hidden="true"></i><strong>Upload gallery images</strong><small>Multiple images are saved to uploads/projects and stored below.</small></span><?= field_error($errors, 'gallery_uploads') ?><textarea class="form-textarea sa-path-output" name="images" rows="4" readonly placeholder="Uploaded image paths will appear here"><?= Security::e($old['images']) ?></textarea></label>
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
