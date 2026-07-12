<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_constituency_edit';
$constituencyId = Security::cleanInt($_GET['id'] ?? 0);
$isCreate = $constituencyId <= 0;
$constituency = $isCreate ? null : Constituency::adminDetail($constituencyId);

if (!$isCreate && !$constituency) {
    Session::flash('error', 'Constituency could not be found.');
    Response::redirect(Url::to('admin/superadmin/constituencies.php'));
}

$errors = [];
$old = constituency_old($constituency ?? []);
$wardRows = $isCreate ? constituency_default_wards() : Constituency::wardsForAdmin($constituencyId);

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        $errors['csrf'] = 'Your session token expired. Please reload and try again.';
    }

    $old = array_merge($old, constituency_form_input());
    $wardRows = is_array($_POST['wards'] ?? null) ? $_POST['wards'] : $wardRows;
    $errors = array_merge($errors, constituency_validate($old));

    $slug = $old['slug'] !== '' ? Constituency::slugify($old['slug']) : Constituency::slugify($old['name']);
    if ($slug !== '' && Constituency::slugExists($slug, $isCreate ? null : $constituencyId)) {
        $errors['slug'] = 'This slug is already used by another constituency.';
    }

    $wardErrors = constituency_validate_wards($wardRows, $isCreate ? 0 : $constituencyId);
    if ($wardErrors !== []) {
        $errors['wards'] = implode(' ', $wardErrors);
    }

    if ($errors === []) {
        Database::beginTransaction();
        try {
            $payload = constituency_payload($old, $slug);
            if ($isCreate) {
                $constituencyId = (int)Constituency::create($payload);
                Logger::log('create', 'constituencies', $constituencyId, ['name' => $old['name']]);
            } else {
                Constituency::update($constituencyId, $payload);
                Logger::log('update', 'constituencies', $constituencyId, ['name' => $old['name']]);
            }

            constituency_save_wards($constituencyId, $wardRows);
            Database::commit();

            Session::flash('status', 'Constituency saved successfully. Public constituency pages now use these details.');
            Response::redirect(Url::to('admin/superadmin/constituency-edit.php?id=' . $constituencyId));
        } catch (Throwable $e) {
            Database::rollBack();
            $errors['form'] = 'Constituency could not be saved. Please check the fields and try again.';
            Logger::error('Constituency save failed', ['constituency_id' => $constituencyId, 'error' => $e->getMessage()]);
        }
    }
}

$summary = $isCreate ? ['live_project_count' => 0, 'live_total_units' => 0, 'live_avg_completion' => 0] : (Constituency::adminDetail($constituencyId) ?: []);

$pageTitle = $isCreate ? 'New Constituency' : 'Edit Constituency';
$pageDescription = 'Create and update constituency public content, wards and CMS visibility.';
$adminRole = 'superadmin';
$contentClass = 'sa-constituency-form-page';
$componentCss = ['media-library', 'news-admin'];
$pageScripts = ['media-picker'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Constituencies', 'url' => Url::to('admin/superadmin/constituencies.php')],
    ['label' => $isCreate ? 'New Constituency' : 'Edit Constituency'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="sa-projects-hero sa-constituency-hero card">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Constituency Editor</span>
    <h2><?= Security::e($old['name'] ?: 'New constituency') ?></h2>
    <p>Update the public hero, profile details, programme visibility and ward list used across project forms and constituency pages.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/constituencies.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Constituencies</a>
<?php if (!$isCreate): ?>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('constituency-detail.php?id=' . urlencode($old['slug']))) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Public Page</a>
<?php endif; ?>
  </div>
</section>

<?php if ($errors): ?>
  <div class="alert alert--danger"><strong>Check the form.</strong><span><?= Security::e($errors['form'] ?? $errors['csrf'] ?? $errors['wards'] ?? 'Some fields need attention before saving.') ?></span></div>
<?php endif; ?>

<section class="sa-form-summary-grid" aria-label="Constituency summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-building" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($summary['live_project_count'] ?? 0)) ?></strong><span class="stat-widget__label">Linked Projects</span><small class="stat-widget__trend">From project records</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-house" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($summary['live_total_units'] ?? 0)) ?></strong><span class="stat-widget__label">Tracked Outputs</span><small class="stat-widget__trend">Calculated live</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number(count(array_filter($wardRows, static fn ($row) => is_array($row) && trim((string)($row['name'] ?? '')) !== '' && (int)($row['is_public'] ?? 1) === 1)))) ?></strong><span class="stat-widget__label">Public Wards</span><small class="stat-widget__trend">Managed below</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_percentage($summary['live_avg_completion'] ?? 0)) ?></strong><span class="stat-widget__label">Avg. Progress</span><small class="stat-widget__trend">Public page metric</small></span></article>
</section>

<form class="sa-project-form sa-constituency-form" method="post" action="<?= Security::e(Url::to('admin/superadmin/constituency-edit.php' . (!$isCreate ? '?id=' . $constituencyId : ''))) ?>">
  <?= Csrf::field($csrfForm) ?>
  <div class="sa-editor-layout">
    <div class="sa-editor-main">
      <section class="card sa-form-card">
        <div class="card__header"><div><h2 class="card__title">Identity & Visibility</h2><p class="card__subtitle">These details power the public constituency pages, cards, filters and map labels.</p></div></div>
        <div class="form-grid form-grid--2">
          <label class="form-field"><span class="form-label">Constituency name</span><input class="form-input <?= isset($errors['name']) ? 'is-error' : '' ?>" name="name" value="<?= Security::e($old['name']) ?>" required><?= field_error($errors, 'name') ?></label>
          <label class="form-field"><span class="form-label">Slug</span><input class="form-input <?= isset($errors['slug']) ? 'is-error' : '' ?>" name="slug" value="<?= Security::e($old['slug']) ?>" placeholder="auto-generated if empty"><?= field_error($errors, 'slug') ?><span class="form-hint">Used in public links like constituency-detail.php?id=saboti.</span></label>
          <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (['planning', 'active', 'completed'] as $status): ?><option value="<?= Security::e($status) ?>" <?= $old['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span class="form-label">Public visibility</span><span class="form-check"><input type="checkbox" name="is_public" value="1" <?= $old['is_public'] === '1' ? 'checked' : '' ?>> Show on public pages</span></label>
          <label class="form-field"><span class="form-label">Sort order</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e($old['sort_order']) ?>"><span class="form-hint">Lower numbers appear first.</span></label>
          <label class="form-field"><span class="form-label">Population</span><input class="form-input" type="number" min="0" name="population" value="<?= Security::e($old['population']) ?>"></label>
          <label class="form-field"><span class="form-label">MP / Lead contact</span><input class="form-input" name="mp" value="<?= Security::e($old['mp']) ?>"></label>
          <label class="form-field"><span class="form-label">MP photo path</span><input class="form-input" name="mp_photo" value="<?= Security::e($old['mp_photo']) ?>"></label>
          <label class="form-field form-field--full"><span class="form-label">Description</span><textarea class="form-textarea" name="description" rows="5" required><?= Security::e($old['description']) ?></textarea><?= field_error($errors, 'description') ?></label>
        </div>
      </section>

      <section class="card sa-form-card sa-hero-library-card">
        <div class="card__header"><div><h2 class="card__title">Hero Image</h2><p class="card__subtitle">Choose or upload the public constituency hero image from the shared Media Library picker.</p></div></div>
        <?php render_constituency_media_control('hero_image', 'Hero image', $old['hero_image'], 'heroes', 'image'); ?>
        <?= field_error($errors, 'hero_image') ?>
      </section>

      <section class="card sa-form-card sa-ward-editor-card">
        <div class="card__header"><div><h2 class="card__title">Wards</h2><p class="card__subtitle">These wards feed public cards and project create/edit dropdowns. Existing linked wards are protected.</p></div></div>
        <div class="sa-ward-editor">
<?php $rows = constituency_rows_for_form($wardRows); foreach ($rows as $index => $ward): ?>
          <div class="sa-ward-row">
            <input type="hidden" name="wards[<?= (int)$index ?>][id]" value="<?= Security::e((string)($ward['id'] ?? 0)) ?>">
            <label class="form-field"><span class="form-label">Ward name</span><input class="form-input" name="wards[<?= (int)$index ?>][name]" value="<?= Security::e((string)($ward['name'] ?? '')) ?>" placeholder="Ward name"></label>
            <label class="form-field"><span class="form-label">Slug</span><input class="form-input" name="wards[<?= (int)$index ?>][slug]" value="<?= Security::e((string)($ward['slug'] ?? '')) ?>" placeholder="auto if empty"></label>
            <label class="form-field"><span class="form-label">Order</span><input class="form-input" type="number" min="0" name="wards[<?= (int)$index ?>][sort_order]" value="<?= Security::e((string)($ward['sort_order'] ?? (($index + 1) * 10))) ?>"></label>
            <label class="form-field sa-ward-check"><span class="form-label">Public</span><span class="form-check"><input type="checkbox" name="wards[<?= (int)$index ?>][is_public]" value="1" <?= (int)($ward['is_public'] ?? 1) === 1 ? 'checked' : '' ?>> Show</span></label>
            <label class="form-field sa-ward-check"><span class="form-label">Remove</span><span class="form-check"><input type="checkbox" name="wards[<?= (int)$index ?>][remove]" value="1"> Remove</span></label>
<?php if ((int)($ward['project_count'] ?? 0) > 0): ?><small class="sa-ward-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> <?= Security::e(format_number($ward['project_count'])) ?> linked project(s); remove will hide, not delete.</small><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
        <?= field_error($errors, 'wards') ?>
      </section>

      <div class="sa-form-actionbar"><span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> CSRF-protected constituency CMS update workflow</span><div><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/constituencies.php')) ?>">Cancel</a><button class="btn btn--primary btn--lg" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Constituency</button></div></div>
    </div>
    <aside class="sa-editor-aside" aria-label="Constituency preview">
      <?php render_constituency_preview($old, $summary, $wardRows); ?>
    </aside>
  </div>
</form>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function constituency_old(array $row): array
{
    return [
        'name' => (string)($row['name'] ?? ''),
        'slug' => (string)($row['slug'] ?? ''),
        'status' => (string)($row['status'] ?? 'planning'),
        'is_public' => (string)($row['is_public'] ?? '1'),
        'sort_order' => (string)($row['sort_order'] ?? '90'),
        'population' => (string)($row['population'] ?? ''),
        'mp' => (string)($row['mp'] ?? ''),
        'mp_photo' => (string)($row['mp_photo'] ?? ''),
        'description' => (string)($row['description'] ?? ''),
        'hero_image' => (string)($row['hero_image'] ?? ''),
    ];
}

function constituency_form_input(): array
{
    return [
        'name' => Security::cleanString((string)($_POST['name'] ?? '')),
        'slug' => Security::cleanString((string)($_POST['slug'] ?? '')),
        'status' => Security::cleanString((string)($_POST['status'] ?? 'planning')),
        'is_public' => isset($_POST['is_public']) ? '1' : '0',
        'sort_order' => (string)max(0, Security::cleanInt($_POST['sort_order'] ?? 0)),
        'population' => (string)max(0, Security::cleanInt($_POST['population'] ?? 0)),
        'mp' => Security::cleanString((string)($_POST['mp'] ?? '')),
        'mp_photo' => trim((string)($_POST['mp_photo'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'hero_image' => trim((string)($_POST['hero_image'] ?? '')),
    ];
}

function constituency_validate(array $data): array
{
    $errors = [];
    if ($data['name'] === '') { $errors['name'] = 'Constituency name is required.'; }
    if ($data['description'] === '') { $errors['description'] = 'Public description is required.'; }
    if (!in_array($data['status'], ['planning', 'active', 'completed'], true)) { $errors['status'] = 'Choose a valid status.'; }
    return $errors;
}

function constituency_payload(array $data, string $slug): array
{
    return [
        'name' => $data['name'],
        'slug' => $slug,
        'mp' => $data['mp'] ?: null,
        'mp_photo' => $data['mp_photo'] ?: null,
        'description' => $data['description'] ?: null,
        'population' => (int)$data['population'] > 0 ? (int)$data['population'] : null,
        'status' => $data['status'],
        'hero_image' => $data['hero_image'] ?: null,
        'sort_order' => (int)$data['sort_order'],
        'is_public' => (int)$data['is_public'],
    ];
}

function render_constituency_media_control(string $name, string $label, string $value, string $folder, string $type): void
{
    $value = trim(str_replace('\\', '/', $value));
    $preview = constituency_editor_asset_url($value);
    $title = $value !== '' ? constituency_asset_label($value) : 'No asset selected';
    $buttonLabel = $type === 'image' ? 'Choose or Upload Image' : 'Choose or Upload File';
    $accept = $type === 'image' ? 'image/*' : '*/*';
?>
  <div class="form-field">
    <span class="form-label"><?= Security::e($label) ?></span>
    <div class="sa-news-media-control sa-constituency-media-control" data-cms-upload data-upload-folder="<?= Security::e($folder) ?>" data-media-kind="<?= Security::e($type) ?>">
      <div class="sa-news-media-control__preview" data-cms-asset-preview>
<?php if ($preview !== ''): ?>
        <img src="<?= Security::e($preview) ?>" alt="">
<?php else: ?>
        <span><i class="fa-solid fa-image" aria-hidden="true"></i></span>
<?php endif; ?>
      </div>
      <div class="sa-news-media-control__body">
        <strong data-cms-asset-name><?= Security::e($title) ?></strong>
        <small>Used as the public constituency hero image.</small>
        <input type="hidden" name="<?= Security::e($name) ?>" value="<?= Security::e($value) ?>" data-cms-upload-target>
        <button class="btn btn--primary btn--sm" type="button" data-media-picker-open data-media-picker-folder="<?= Security::e($folder) ?>" data-media-picker-type="<?= Security::e($type) ?>" data-media-picker-accept="<?= Security::e($accept) ?>" data-media-picker-title="<?= Security::e($buttonLabel) ?>">
          <i class="fa-solid fa-photo-film" aria-hidden="true"></i> <?= Security::e($buttonLabel) ?>
        </button>
      </div>
    </div>
  </div>
<?php
}

function constituency_editor_asset_url(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    return preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) ? $path : Url::asset($path);
}
function constituency_asset_label(string $path): string
{
    $name = pathinfo($path, PATHINFO_FILENAME);
    $name = preg_replace('/[-_]+/', ' ', $name) ?: $name;
    return ucwords(trim((string)$name));
}

function constituency_validate_wards(mixed $rows, int $constituencyId): array
{
    $errors = [];
    $seen = [];
    if (!is_array($rows)) { return $errors; }

    foreach ($rows as $row) {
        if (!is_array($row)) { continue; }
        $name = Security::cleanString((string)($row['name'] ?? ''));
        $remove = isset($row['remove']);
        if ($name === '' || $remove) { continue; }
        $wardSlugInput = Security::cleanString((string)($row['slug'] ?? ''));
        $slug = Constituency::slugify($wardSlugInput !== '' ? $wardSlugInput : $name, 'ward');
        if (isset($seen[$slug])) { $errors[] = 'Ward slugs must be unique inside the constituency.'; break; }
        $seen[$slug] = true;
        $wardId = max(0, Security::cleanInt($row['id'] ?? 0));
        if ($constituencyId > 0 && Constituency::wardSlugExists($constituencyId, $slug, $wardId > 0 ? $wardId : null)) {
            $errors[] = 'Ward slug "' . $slug . '" already exists.';
            break;
        }
    }

    return $errors;
}

function constituency_save_wards(int $constituencyId, mixed $rows): void
{
    if (!is_array($rows)) { return; }

    foreach ($rows as $index => $row) {
        if (!is_array($row)) { continue; }
        $wardId = max(0, Security::cleanInt($row['id'] ?? 0));
        $name = Security::cleanString((string)($row['name'] ?? ''));
        $remove = isset($row['remove']);

        if ($remove && $wardId > 0) {
            if (Constituency::wardProjectCount($wardId) > 0) {
                Database::query('UPDATE wards SET is_public = 0 WHERE id = ? AND constituency_id = ?', [$wardId, $constituencyId]);
            } else {
                Database::query('DELETE FROM wards WHERE id = ? AND constituency_id = ?', [$wardId, $constituencyId]);
            }
            continue;
        }

        if ($name === '') { continue; }

        $wardSlugInput = Security::cleanString((string)($row['slug'] ?? ''));
        $slug = Constituency::slugify($wardSlugInput !== '' ? $wardSlugInput : $name, 'ward');
        $payload = [
            $constituencyId,
            $name,
            $slug,
            max(0, Security::cleanInt($row['sort_order'] ?? (($index + 1) * 10))),
            isset($row['is_public']) ? 1 : 0,
        ];

        if ($wardId > 0) {
            Database::query('UPDATE wards SET name = ?, slug = ?, sort_order = ?, is_public = ? WHERE id = ? AND constituency_id = ?', [$name, $slug, $payload[3], $payload[4], $wardId, $constituencyId]);
        } else {
            Database::query('INSERT INTO wards (constituency_id, name, slug, sort_order, is_public) VALUES (?, ?, ?, ?, ?)', $payload);
        }
    }
}

function constituency_rows_for_form(array $rows): array
{
    $rows = array_values($rows);
    for ($i = 0; $i < 5; $i++) {
        $rows[] = ['id' => 0, 'name' => '', 'slug' => '', 'sort_order' => (count($rows) + 1) * 10, 'is_public' => 1, 'project_count' => 0];
    }
    return $rows;
}

function constituency_default_wards(): array
{
    return [['id' => 0, 'name' => '', 'slug' => '', 'sort_order' => 10, 'is_public' => 1, 'project_count' => 0]];
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="form-error">' . Security::e($errors[$key]) . '</span>' : '';
}

function render_constituency_preview(array $old, array $summary, array $wardRows): void
{
    $hero = trim((string)($old['hero_image'] ?? ''));
    $wards = array_values(array_filter(array_map(static fn ($row): string => is_array($row) && (int)($row['is_public'] ?? 1) === 1 ? (string)($row['name'] ?? '') : '', $wardRows)));
?>
  <div class="sa-editor-preview card sa-constituency-preview">
    <div class="sa-preview-media"><?php if ($hero !== ''): ?><img src="<?= Security::e(Url::asset($hero)) ?>" alt=""><?php else: ?><span><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span><?php endif; ?></div>
    <div class="sa-preview-body"><span class="sa-panel-label"><i class="fa-solid fa-eye" aria-hidden="true"></i> Public Preview</span><h3><?= Security::e($old['name'] ?: 'Untitled constituency') ?></h3><p><?= Security::e($old['description'] ?: 'Add a public description for this constituency.') ?></p><div class="sa-preview-badges"><span class="badge <?= $old['status'] === 'active' ? 'badge--success' : 'badge--warning' ?>"><?= Security::e(status_label($old['status'])) ?></span><span class="badge <?= $old['is_public'] === '1' ? 'badge--lime' : 'badge--warning' ?>"><?= $old['is_public'] === '1' ? 'Public' : 'Hidden' ?></span></div><div class="sa-preview-progress"><span style="width: <?= Security::e((string)percentage($summary['live_avg_completion'] ?? 0)) ?>%"></span></div><dl class="sa-preview-list"><div><dt>Projects</dt><dd><?= Security::e(format_number($summary['live_project_count'] ?? 0)) ?></dd></div><div><dt>Outputs</dt><dd><?= Security::e(format_number($summary['live_total_units'] ?? 0)) ?></dd></div><div><dt>Population</dt><dd><?= Security::e(format_number($old['population'] ?: 0)) ?></dd></div></dl><div class="sa-preview-wards"><?php foreach ($wards as $ward): ?><span><?= Security::e($ward) ?></span><?php endforeach; ?></div></div>
  </div>
<?php
}


