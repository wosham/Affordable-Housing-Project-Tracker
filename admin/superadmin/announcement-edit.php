<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_announcement_edit';
$id = max(0, Security::cleanInt($_GET['id'] ?? $_POST['announcement_id'] ?? 0));
$announcement = $id > 0 ? Announcement::findAdmin($id) : null;
$errors = [];

if (!$announcement) {
    Session::flash('error', 'Announcement could not be found.');
    Response::redirect(Url::to('admin/superadmin/announcements.php'));
}

$values = announcement_form_values($announcement);

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/announcement-edit.php?id=' . $id));
    }

    $errors = announcement_validate($_POST);
    if ($errors === []) {
        try {
            Announcement::saveFromAdmin($_POST, $id);
            Logger::log('update', 'announcements', $id, [
                'title' => $_POST['title'] ?? '',
                'status' => $_POST['status'] ?? '',
                'audience' => $_POST['audience_mode'] ?? '',
            ]);
            Session::flash('status', 'Announcement updated successfully.');
            Response::redirect(Url::to('admin/superadmin/announcements.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    $values = announcement_form_values($_POST);
}

$pageTitle = 'Edit Staff Announcement';
$pageDescription = 'Edit a role-targeted staff announcement.';
$adminRole = 'superadmin';
$contentClass = 'sa-announcements-page';
$componentCss = ['announcements'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Staff Announcements', 'url' => Url::to('admin/superadmin/announcements.php')],
    ['label' => 'Edit'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-announcement-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit staff broadcast</span>
    <h2>Edit staff announcement</h2>
    <p>Update copy, schedule, priority and <strong>audience targeting</strong>. Role-only notices stay off other dashboards.</p>
  </div>
  <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/announcements.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to registry</a>
</section>

<?php if ($errors !== []): ?>
  <div class="alert alert--danger"><strong>Fix this first:</strong> <?= Security::e(implode(' ', $errors)) ?></div>
<?php endif; ?>

<section class="card sa-announcement-form-card">
  <div class="card__header">
    <div>
      <h2 class="card__title"><?= Security::e($announcement['title']) ?></h2>
      <p class="card__subtitle">Created <?= Security::e(format_datetime($announcement['created_at'] ?? null)) ?> · Audience: <?= Security::e(implode(', ', Announcement::roleLabels($announcement['target_roles_json'] ?? null))) ?> · Reach ~<?= (int)Announcement::audienceReachCount($announcement['target_roles_json'] ?? null) ?></p>
    </div>
    <span class="badge <?= Security::e(status_badge_class($announcement['status'] ?? 'draft')) ?>"><?= Security::e(status_label($announcement['status'] ?? 'draft')) ?></span>
  </div>
<?php
$formAction = Url::to('admin/superadmin/announcement-edit.php?id=' . $id);
$submitLabel = 'Save changes';
include __DIR__ . '/../../app/partials/admin/announcement-form.php';
?>
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
    $mode = strtolower(trim((string)($input['audience_mode'] ?? '')));
    if (!in_array($mode, ['all', 'roles'], true)) {
        $errors[] = 'Choose an audience: All staff or Selected roles.';
    }
    if ($mode === 'roles') {
        $roles = Announcement::normalizeAudienceFromInput($input);
        if ($roles === [] && (string)($input['status'] ?? '') === 'published') {
            $errors[] = 'Select at least one role, or choose All staff.';
        }
    }
    $ctaUrl = trim((string)($input['cta_url'] ?? ''));
    if ($ctaUrl !== '' && !announcement_valid_url($ctaUrl)) {
        $errors[] = 'CTA URL must be a valid http, https, mailto, tel or internal portal path.';
    }
    return $errors;
}

function announcement_form_values(array $source = []): array
{
    $roles = $source['target_roles'] ?? [];
    if (!is_array($roles)) {
        $roles = json_decode((string)($source['target_roles_json'] ?? ''), true) ?: [];
    }
    $roles = array_values(array_filter(array_map('strval', $roles), static fn ($r) => $r !== 'all' && $r !== ''));

    $mode = strtolower(trim((string)($source['audience_mode'] ?? '')));
    if ($mode === '' && array_key_exists('target_roles_json', $source)) {
        $mode = Announcement::audienceModeFromJson((string)$source['target_roles_json']);
        // Empty legacy null → treat as all when editing old records before save
        if ($mode === 'roles' && $roles === [] && (trim((string)($source['target_roles_json'] ?? '')) === '' || $source['target_roles_json'] === null)) {
            $mode = 'all';
        }
    }
    if ($mode === '') {
        $mode = $roles === [] ? 'all' : 'roles';
    }

    return [
        'title' => (string)($source['title'] ?? ''),
        'body' => (string)($source['body'] ?? ''),
        'type' => (string)($source['type'] ?? 'info'),
        'status' => (string)($source['status'] ?? 'draft'),
        'priority' => (string)($source['priority'] ?? 'normal'),
        'audience_mode' => $mode,
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

function announcement_valid_url(string $url): bool
{
    return preg_match('#^(?:https?://|mailto:|tel:)#i', $url) === 1
        || preg_match('#^(?:/|admin/|api/|[a-z0-9][a-z0-9._/-]*\.php(?:[?#].*)?)#i', $url) === 1;
}
