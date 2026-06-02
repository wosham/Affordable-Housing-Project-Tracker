<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_announcement_create';
$values = announcement_form_values();

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/announcement-create.php'));
    }

    $errors = announcement_validate($_POST);
    if ($errors === []) {
        try {
            $id = Announcement::saveFromAdmin($_POST);
            Logger::log('create', 'announcements', $id, [
                'title' => $_POST['title'] ?? '',
                'status' => $_POST['status'] ?? '',
            ]);
            Session::flash('status', 'Announcement created successfully.');
            Response::redirect(Url::to('admin/superadmin/announcements.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    $values = announcement_form_values($_POST);
}

$pageTitle = 'Create Announcement';
$pageDescription = 'Create a role-targeted staff announcement.';
$adminRole = 'superadmin';
$contentClass = 'sa-announcements-page';
$componentCss = ['announcements'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Announcements', 'url' => Url::to('admin/superadmin/announcements.php')],
    ['label' => 'Create'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-announcement-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Broadcast</span>
    <h2>Create announcement</h2>
    <p>Publish a targeted update for all staff or selected portal roles.</p>
  </div>
  <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to registry</a>
</section>

<?php if (!empty($errors)): ?>
  <div class="alert alert--danger"><strong>Fix this first:</strong> <?= Security::e(implode(' ', $errors)) ?></div>
<?php endif; ?>

<section class="card sa-announcement-form-card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Announcement Details</h2>
      <p class="card__subtitle">Draft first or publish immediately. Leave roles unchecked for all staff.</p>
    </div>
  </div>

  <?php announcement_render_form($csrfForm, $values, Url::to('admin/superadmin/announcement-create.php'), 'Create Announcement'); ?>
</section>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function announcement_validate(array $input): array
{
    $errors = [];
    if (trim((string)($input['title'] ?? '')) === '') {
        $errors[] = 'Title is required.';
    }
    if (trim((string)($input['body'] ?? '')) === '') {
        $errors[] = 'Message body is required.';
    }
    if (!array_key_exists((string)($input['type'] ?? ''), Announcement::TYPES)) {
        $errors[] = 'Choose a valid type.';
    }
    if (!in_array((string)($input['status'] ?? ''), Announcement::STATUSES, true)) {
        $errors[] = 'Choose a valid status.';
    }
    if (!in_array((string)($input['priority'] ?? ''), Announcement::PRIORITIES, true)) {
        $errors[] = 'Choose a valid priority.';
    }
    return $errors;
}

function announcement_form_values(array $source = []): array
{
    $roles = $source['target_roles'] ?? [];
    if (!is_array($roles)) {
        $roles = json_decode((string)($source['target_roles_json'] ?? ''), true) ?: [];
    }

    return [
        'title' => (string)($source['title'] ?? ''),
        'body' => (string)($source['body'] ?? ''),
        'type' => (string)($source['type'] ?? 'info'),
        'status' => (string)($source['status'] ?? 'draft'),
        'priority' => (string)($source['priority'] ?? 'normal'),
        'target_roles' => $roles,
        'is_pinned' => !empty($source['is_pinned']),
        'cta_label' => (string)($source['cta_label'] ?? ''),
        'cta_url' => (string)($source['cta_url'] ?? ''),
        'published_at' => announcement_datetime_local($source['published_at'] ?? null),
        'expires_at' => announcement_datetime_local($source['expires_at'] ?? null),
    ];
}

function announcement_datetime_local(mixed $date): string
{
    if (!$date) {
        return '';
    }
    $timestamp = strtotime((string)$date);
    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
}

function announcement_render_form(string $csrfForm, array $values, string $action, string $submitLabel): void
{
    ?>
    <form class="form-grid sa-announcement-form" method="post" action="<?= Security::e($action) ?>">
      <?= Csrf::field($csrfForm) ?>
      <label class="form-field form-field--full"><span class="form-label">Title <strong>*</strong></span><input class="form-input" name="title" value="<?= Security::e($values['title']) ?>" maxlength="255" required></label>
      <label class="form-field form-field--full"><span class="form-label">Message <strong>*</strong></span><textarea class="form-textarea" name="body" rows="8" required><?= Security::e($values['body']) ?></textarea></label>
      <label class="form-field"><span class="form-label">Type</span><select class="form-select" name="type"><?php foreach (Announcement::TYPES as $type => $meta): ?><option value="<?= Security::e($type) ?>" <?= $values['type'] === $type ? 'selected' : '' ?>><?= Security::e($meta['label']) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Priority</span><select class="form-select" name="priority"><?php foreach (Announcement::PRIORITIES as $priority): ?><option value="<?= Security::e($priority) ?>" <?= $values['priority'] === $priority ? 'selected' : '' ?>><?= Security::e(status_label($priority)) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><?php foreach (Announcement::STATUSES as $status): ?><option value="<?= Security::e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>><?= Security::e(status_label($status)) ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span class="form-label">Publish at</span><input class="form-input" type="datetime-local" name="published_at" value="<?= Security::e($values['published_at']) ?>"></label>
      <label class="form-field"><span class="form-label">Expires at</span><input class="form-input" type="datetime-local" name="expires_at" value="<?= Security::e($values['expires_at']) ?>"></label>
      <label class="form-field"><span class="form-label">CTA label</span><input class="form-input" name="cta_label" value="<?= Security::e($values['cta_label']) ?>" maxlength="120"></label>
      <label class="form-field form-field--full"><span class="form-label">CTA URL</span><input class="form-input" name="cta_url" value="<?= Security::e($values['cta_url']) ?>" maxlength="255"></label>
      <fieldset class="form-field form-field--full"><legend class="form-label">Target roles</legend><div class="checkbox-grid"><?php foreach (Announcement::ROLES as $role): ?><label class="check-option"><input type="checkbox" name="target_roles[]" value="<?= Security::e($role) ?>" <?= in_array($role, $values['target_roles'], true) ? 'checked' : '' ?>> <span><?= Security::e(role_label($role)) ?></span></label><?php endforeach; ?></div><span class="form-hint">Leave all unchecked to broadcast to every staff member.</span></fieldset>
      <label class="check-option form-field--full"><input type="checkbox" name="is_pinned" value="1" <?= $values['is_pinned'] ? 'checked' : '' ?>> <span>Pin this announcement above regular notices</span></label>
      <div class="form-actions form-field--full"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= Security::e($submitLabel) ?></button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>">Cancel</a></div>
    </form>
    <?php
}
